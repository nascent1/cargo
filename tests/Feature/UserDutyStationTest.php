<?php

namespace Tests\Feature;

use App\Models\Consignment;
use App\Models\DualControlConfig;
use App\Models\DualControlRequest;
use App\Models\Station;
use App\Models\SystemConfig;
use App\Models\User;
use App\Enums\ConsignmentStatus;
use App\Enums\ServiceType;
use App\Services\DualControlService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Phase 0 foundations seen from the screens: a duty station on each user (ADM-05),
 * the two service-line switches (ADM-08), and the scheduler without the failing nightly command.
 */
class UserDutyStationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $checker;
    private User $staff;
    private Station $dar;
    private Station $other;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake(); // a new user is sent their password

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first()
            ?? $this->markTestSkipped('Needs an admin user.');
        $this->checker = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->where('id', '!=', $this->admin->id)->first()
            ?? $this->markTestSkipped('Needs a second user who can approve.');

        [$this->dar, $this->other] = Station::where('status', 'active')->orderBy('id')->take(2)->get()->all()
            + [null, null];
        if (!$this->other) {
            $this->markTestSkipped('Needs two active stations.');
        }

        $this->staff = $this->createSystemUser(['first_name' => 'Duty', 'last_name' => 'Tester']);

        SystemConfig::setValue('exchange_rate_last_updated', today()->toDateString()); // or admin POSTs are held back
        DualControlConfig::syncFromRegistry();
        DualControlConfig::whereIn('operation_type', ['user_update', 'user_create'])->update(['requires_dual_control' => false]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['2fa_verified' => true]);
    }

    private function setStation(?int $stationId, ?User $by = null)
    {
        return $this->as($by ?? $this->admin)->from("/admin/users/{$this->staff->id}")
            ->patch("/admin/users/{$this->staff->id}/station", ['station_id' => $stationId]);
    }

    #[Test]
    public function a_new_user_has_no_duty_station_and_the_profile_says_so(): void
    {
        $this->assertNull($this->staff->fresh()->station_id);
        $this->assertNull($this->staff->station);

        $this->as($this->admin)->get("/admin/users/{$this->staff->id}")
            ->assertOk()
            ->assertSee('Duty Station')
            ->assertSee('Not set')
            ->assertSee($this->dar->name);
    }

    #[Test]
    public function an_administrator_sets_changes_and_clears_a_duty_station(): void
    {
        $this->setStation($this->dar->id)
            ->assertRedirect("/admin/users/{$this->staff->id}")
            ->assertSessionHas('success', "Duty Tester's duty station is now {$this->dar->name} ({$this->dar->code}).");
        $this->assertSame($this->dar->id, $this->staff->fresh()->station_id);
        $this->assertTrue($this->staff->fresh()->station->is($this->dar));
        $this->assertTrue($this->dar->staff()->whereKey($this->staff->id)->exists());

        $this->setStation($this->other->id)->assertSessionHas('success');
        $this->assertSame($this->other->id, $this->staff->fresh()->station_id);

        $this->setStation(null)->assertSessionHas('success', 'Duty Tester no longer has a duty station.');
        $this->assertNull($this->staff->fresh()->station_id);
    }

    #[Test]
    public function saving_the_same_station_changes_nothing_and_raises_no_request(): void
    {
        $this->staff->update(['station_id' => $this->dar->id]);
        $before = DualControlRequest::count();

        $this->setStation($this->dar->id)->assertSessionHas('success', 'The duty station is unchanged.');

        $this->assertSame($before, DualControlRequest::count());
    }

    #[Test]
    public function the_station_shows_on_the_profile_and_in_the_user_list(): void
    {
        $this->staff->update(['station_id' => $this->dar->id]);

        $this->as($this->admin)->get("/admin/users/{$this->staff->id}")
            ->assertOk()->assertSee("{$this->dar->name} ({$this->dar->code})");

        $this->as($this->admin)->get('/admin/users?search=Duty')
            ->assertOk()->assertSee('Duty Station')->assertSee($this->dar->code);
    }

    #[Test]
    public function a_suspended_or_unknown_station_is_refused(): void
    {
        $this->other->update(['status' => 'inactive']);

        $this->setStation($this->other->id)->assertSessionHasErrors(['station_id' => 'Choose an active station.']);
        $this->setStation(999999)->assertSessionHasErrors('station_id');

        $this->assertNull($this->staff->fresh()->station_id);
    }

    #[Test]
    public function a_user_posted_to_a_station_that_was_later_suspended_still_shows_it(): void
    {
        $this->staff->update(['station_id' => $this->other->id]);
        $this->other->update(['status' => 'inactive']);

        $this->as($this->admin)->get("/admin/users/{$this->staff->id}")
            ->assertOk()->assertSee("{$this->other->name} ({$this->other->code}) — suspended");
    }

    #[Test]
    public function with_the_approval_rule_on_the_station_changes_only_after_another_person_approves(): void
    {
        DualControlConfig::where('operation_type', 'user_update')->update(['requires_dual_control' => true]);

        $this->setStation($this->dar->id)->assertSessionHas('success');
        $this->assertNull($this->staff->fresh()->station_id, 'nothing changes until it is approved');

        $request = DualControlRequest::where('operation_type', 'user_update')->where('resource_id', $this->staff->id)
            ->where('status', 'pending')->latest('id')->firstOrFail();
        $this->assertSame($this->dar->id, $request->payload_after['station_id']);
        $this->assertSame("{$this->dar->name} ({$this->dar->code})", $request->payload_after['duty_station'], 'the checker reads a name');
        $this->assertNull($request->payload_before['station_id']);

        // The checker's page shows the station by name
        $this->as($this->checker)->get("/admin/dual-control/{$request->id}")->assertOk()->assertSee($this->dar->name);

        // The maker cannot approve their own request; another person can
        $this->as($this->admin)->post("/admin/dual-control/{$request->id}/approve")->assertForbidden();
        $this->as($this->checker)->post("/admin/dual-control/{$request->id}/approve")->assertRedirect();

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($this->dar->id, $this->staff->fresh()->station_id);
    }

    #[Test]
    public function setting_a_station_needs_the_permission_to_change_users(): void
    {
        // Staff without admin access are sent to the sales portal, and nothing is changed
        $this->setStation($this->dar->id, $this->staff)->assertRedirect('/portal')->assertSessionMissing('success');
        $this->assertNull($this->staff->fresh()->station_id);

        // Admin access, but not the permission to change users
        $role = \App\Models\Role::firstOrCreate(['slug' => 'cargo_test_admin_viewer'], ['name' => 'Admin viewer', 'display_name' => 'Admin viewer']);
        $role->permissions()->sync(\App\Models\Permission::whereIn('name', ['admin_portal:access', 'user:read'])->pluck('id'));
        $this->staff->roles()->sync([$role->id]);
        $this->setStation($this->dar->id, $this->staff->fresh())->assertForbidden();

        $this->assertNull($this->staff->fresh()->station_id);
    }

    #[Test]
    public function a_user_can_be_created_with_a_duty_station(): void
    {
        $this->as($this->admin)->get('/admin/users/create')
            ->assertOk()
            ->assertSee('Duty Station')
            ->assertSee($this->dar->name)
            ->assertSee('Associate Agent')
            ->assertDontSee('Beekeeper'); // the form used to offer domains that validation rejects

        $email = 'duty-' . uniqid() . '@cargo.test';
        $this->as($this->admin)->post('/admin/users', [
            'first_name' => 'New', 'last_name' => 'Clerk', 'email' => $email,
            'domain' => 'system_user', 'station_id' => $this->dar->id,
        ])->assertRedirect('/admin/users')->assertSessionHas('success');

        $created = User::where('email', $email)->firstOrFail();
        $this->assertSame($this->dar->id, $created->station_id);

        $email2 = 'duty-' . uniqid() . '@cargo.test';
        $this->as($this->admin)->post('/admin/users', [
            'first_name' => 'No', 'last_name' => 'Station', 'email' => $email2, 'domain' => 'agent', 'station_id' => '',
        ])->assertSessionHas('success');
        $this->assertNull(User::where('email', $email2)->firstOrFail()->station_id);
    }

    #[Test]
    public function deleting_a_station_for_good_leaves_its_staff_without_one(): void
    {
        $station = Station::create(['name' => 'Temporary Jetty', 'code' => 'TMPJ', 'station_type' => 'port', 'status' => 'active']);
        $this->staff->update(['station_id' => $station->id]);

        $station->forceDelete();

        $this->assertNull($this->staff->fresh()->station_id);
    }

    #[Test]
    public function every_station_has_a_parcel_office_until_told_otherwise(): void
    {
        $station = Station::create(['name' => 'Temporary Jetty', 'code' => 'TMPJ', 'station_type' => 'port', 'status' => 'active']);

        $this->assertTrue($station->fresh()->has_parcel_office);
        $this->assertSame('Temporary Jetty (TMPJ)', $station->label);

        $station->update(['has_parcel_office' => false]);
        $this->assertFalse($station->fresh()->has_parcel_office);
    }

    #[Test]
    public function both_service_lines_are_switched_off_and_show_as_switches_in_settings(): void
    {
        $this->assertSame('0', SystemConfig::getValue('parcel_receive_flow_enabled'));
        $this->assertSame('0', SystemConfig::getValue('contract_cargo_enabled'));

        $this->as($this->admin)->get('/admin/settings')
            ->assertOk()
            ->assertSee('Parcel Service (Receive, Assign, Board)')
            ->assertSee('Contract Cargo')
            ->assertSee('name="settings[parcel_receive_flow_enabled]" value="1"', false)
            ->assertSee('name="settings[contract_cargo_enabled]" value="1"', false);
    }

    #[Test]
    public function a_service_line_is_switched_on_from_settings(): void
    {
        DualControlConfig::where('operation_type', 'setting_update')->update(['requires_dual_control' => false]);

        $this->as($this->admin)->from('/admin/settings')
            ->post('/admin/settings', ['settings' => ['parcel_receive_flow_enabled' => '1']])
            ->assertRedirect('/admin/settings')->assertSessionHas('success', 'Settings: 1 updated.');

        $this->assertSame('1', SystemConfig::getValue('parcel_receive_flow_enabled'));
        $this->assertSame('0', SystemConfig::getValue('contract_cargo_enabled'));
    }

    #[Test]
    public function existing_consignments_are_parcels_and_their_status_reads_through_the_enum(): void
    {
        $consignment = Consignment::first() ?? $this->markTestSkipped('Needs a consignment.');

        $this->assertSame(ServiceType::Parcel, $consignment->service_type);
        $this->assertSame(ServiceType::Parcel, $consignment->serviceLine());
        $this->assertSame(ServiceType::Parcel, (new Consignment())->serviceLine(), 'the default before it is saved');

        $consignment->status = 'booked';
        $this->assertSame(ConsignmentStatus::Received, $consignment->statusEnum(), 'the name used before the rename');
        $consignment->status = 'in_transit';
        $this->assertSame(ConsignmentStatus::InTransit, $consignment->statusEnum());
        $consignment->status = 'draft';
        $this->assertNull($consignment->statusEnum());

        $this->assertSame('parcel', $consignment->toArray()['service_type'], 'screens and the API still get plain text');
    }

    #[Test]
    public function the_failing_nightly_voyage_command_is_no_longer_scheduled(): void
    {
        $commands = collect(Schedule::events())->map(fn ($event) => (string) $event->command);

        $this->assertFalse($commands->contains(fn ($c) => str_contains($c, 'voyage:generate')));
        $this->assertTrue($commands->contains(fn ($c) => str_contains($c, 'cargo:generate-services')), 'cargo journeys are still generated');
    }
}
