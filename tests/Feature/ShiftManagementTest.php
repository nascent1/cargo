<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DualControlConfig;
use App\Models\DualControlRequest;
use App\Models\Role;
use App\Models\Shift;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\DualControl\DualControlException;
use App\Services\DualControlService;
use App\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sales-portal shifts: a sales officer's sign-in starts one (otherwise the first booking action
 * does), the next may start from midnight / after the minimum break, booking/payment/cancel stop with
 * SHIFT_ENDED when it ends, extensions go through dual control, the clerk can end it with a
 * handover summary, and Operations › Shifts lists, ends and exports them.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class ShiftManagementTest extends TestCase
{
    use DatabaseTransactions;

    private User $clerk;
    private User $admin;
    private User $checker;
    private string $password;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first()
            ?? $this->markTestSkipped('Needs an admin user.');
        $this->checker = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->where('id', '!=', $this->admin->id)->first()
            ?? $this->markTestSkipped('Needs a second user who can approve.');
        $role = Role::where('slug', 'sales_officer')->first() ?? $this->markTestSkipped('Needs the sales_officer role.');
        $this->password = Str::random(16);
        $this->clerk = User::forceCreate([
            'email' => 'shift-clerk-' . Str::random(8) . '@example.test', 'password' => bcrypt($this->password),
            'first_name' => 'Shift', 'last_name' => 'Clerk', 'domain' => 'system_user', 'status' => 'active',
            'password_changed_at' => now(),
        ]);
        $this->clerk->roles()->sync([$role->id]);

        foreach (['shift_management_enabled' => '1', 'default_shift_hours' => '8', 'shift_rest_hours' => '8', 'enable_2fa' => '0',
            'sms_enabled' => '0', 'exchange_rate_last_updated' => today()->toDateString()] as $key => $value) {
            SystemConfig::setValue($key, $value);
        }
        DualControlConfig::syncFromRegistry();
        DualControlConfig::where('operation_type', 'shift_extension')->update(['requires_dual_control' => true]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['2fa_verified' => true]);
    }

    /** A guarded booking action: the shift check runs first, then validation answers 422. */
    private function bookingAction(User $user)
    {
        return $this->as($user)->postJson('/portal/api/cargo/lock-inventory', []);
    }

    /** Sign in through the real login form, then open the sales portal the way the redirect does. */
    private function signInAndOpenPortal(User $user)
    {
        $this->withoutVite();
        $this->post('/login', ['email' => $user->email, 'password' => $this->password])->assertRedirect('/portal');

        return $this->get('/portal');
    }

    private function endedShift(): Shift
    {
        return Shift::create([
            'user_id' => $this->clerk->id, 'start_time' => now()->subHours(8)->subMinutes(5), 'end_time' => now()->subMinutes(5),
            'actual_end_time' => now()->subMinutes(5), 'status' => 'ended', 'end_reason' => Shift::END_EXPIRED,
        ]);
    }

    public function test_the_first_booking_action_starts_a_shift_and_selling_stops_when_it_ends(): void
    {
        $this->as($this->clerk)->getJson('/portal/api/shift')->assertOk()->assertJson(['applies' => true, 'state' => 'not_started']);

        $this->bookingAction($this->clerk)->assertStatus(422);
        $shift = Shift::where('user_id', $this->clerk->id)->sole();
        $this->assertSame('active', $shift->status);
        $this->assertEqualsWithDelta(480, $shift->start_time->diffInMinutes($shift->end_time), 1);
        $this->as($this->clerk)->getJson('/portal/api/shift')->assertJson(['state' => 'active']);

        $shift->update(['end_time' => now()->subMinute()]);
        $this->bookingAction($this->clerk)->assertForbidden()->assertJson(['code' => 'SHIFT_ENDED', 'shift' => ['state' => 'ended']]);
        $this->assertSame(Shift::END_EXPIRED, $shift->fresh()->end_reason);

        // Viewing still works; payments and cancelling are guarded too
        $this->as($this->clerk)->getJson('/portal/api/cargo/consignments')->assertOk();
        $this->as($this->clerk)->postJson('/portal/api/cargo/confirm-booking', [])->assertJson(['code' => 'SHIFT_ENDED']);
    }

    public function test_a_sales_officer_signing_in_starts_the_shift(): void
    {
        $this->withoutVite();
        $this->post('/login', ['email' => $this->clerk->email, 'password' => $this->password])->assertRedirect('/portal');
        $this->assertSame(0, Shift::where('user_id', $this->clerk->id)->count(), 'the password step alone starts nothing');

        $this->get('/portal')->assertOk();
        $shift = Shift::where('user_id', $this->clerk->id)->sole();
        $this->assertSame('active', $shift->status);
        $this->assertEqualsWithDelta(480, $shift->start_time->diffInMinutes($shift->end_time), 1);
        $this->getJson('/portal/api/shift')->assertJson(['state' => 'active']);
        $this->assertSame(ShiftService::VIA_SIGN_IN, AuditLog::where('action', 'shift_start')->where('entity_id', $shift->id)->sole()->after_state['via']);

        // Reloading the page and the portal's polling never start another one
        $this->get('/portal/cargo/bookings')->assertOk();
        $this->getJson('/portal/api/shift')->assertOk();
        $this->assertSame(1, Shift::where('user_id', $this->clerk->id)->count());
    }

    public function test_with_2fa_the_shift_starts_only_once_the_code_is_accepted(): void
    {
        SystemConfig::setValue('enable_2fa', '1');
        $this->clerk->forceFill(['mfa_enabled' => true])->save();

        $this->signInAndOpenPortal($this->clerk)->assertRedirect(route('admin.2fa.challenge'));
        $this->assertSame(0, Shift::where('user_id', $this->clerk->id)->count());

        $this->withSession(['2fa_verified' => true])->get('/portal')->assertOk();
        $this->assertSame('active', Shift::where('user_id', $this->clerk->id)->sole()->status);
    }

    public function test_signing_in_again_carries_on_the_running_shift(): void
    {
        $running = app(ShiftService::class)->startShift($this->clerk);
        $running->update(['start_time' => now()->subHours(2), 'end_time' => now()->addHours(6)]);

        $this->signInAndOpenPortal($this->clerk)->assertOk();
        $this->assertSame([$running->id], Shift::where('user_id', $this->clerk->id)->pluck('id')->all());
        $this->assertEqualsWithDelta(now()->addHours(6)->timestamp, $running->fresh()->end_time->timestamp, 5, 'the clock is not reset');
    }

    public function test_signing_in_during_the_rest_period_starts_no_shift(): void
    {
        $ended = $this->endedShift();

        $this->signInAndOpenPortal($this->clerk)->assertOk();
        $this->assertSame([$ended->id], Shift::where('user_id', $this->clerk->id)->pluck('id')->all());
        $this->getJson('/portal/api/shift')->assertJson(['state' => 'ended']);
    }

    public function test_only_sales_portal_staff_start_a_shift_by_signing_in(): void
    {
        $shifts = app(ShiftService::class);
        $this->assertTrue($shifts->startsOnSignIn($this->clerk));
        $this->assertFalse($shifts->startsOnSignIn($this->admin));

        // Port officers also use the admin portal: their first sales-portal booking starts it instead
        $portOfficer = Role::where('slug', 'port_officer')->first();
        if ($portOfficer) {
            $this->clerk->roles()->sync([$portOfficer->id]);
            $this->clerk->unsetRelation('roles');
            $this->assertTrue($shifts->appliesTo($this->clerk->fresh()));
            $this->assertFalse($shifts->startsOnSignIn($this->clerk->fresh()));
        }

        // Opening the portal without signing in (an existing session) starts nothing either
        $this->withoutVite();
        $this->as($this->clerk)->get('/portal')->assertOk();
        $this->assertSame(0, Shift::where('user_id', $this->clerk->id)->count());
    }

    public function test_administrators_and_agents_never_work_in_shifts(): void
    {
        $shifts = app(ShiftService::class);
        $this->assertFalse($shifts->appliesTo($this->admin));
        $this->assertFalse($shifts->appliesTo($this->checker));
        $this->assertSame('none', $shifts->context($this->admin)['state']);

        SystemConfig::setValue('shift_management_enabled', '0');
        $this->assertFalse($shifts->appliesTo($this->clerk), 'switched off: nobody works in shifts');
    }

    public function test_an_extension_is_requested_by_the_clerk_and_reopens_the_shift_once_approved(): void
    {
        $shift = $this->endedShift();

        $first = $this->as($this->clerk)->postJson('/portal/api/shift/extension', ['hours' => 2, 'reason' => 'Late vessel still loading'])
            ->assertOk()->assertJsonPath('shift.state', 'ended')->assertJsonPath('shift.pending_extension.hours', 2);
        $this->assertStringContainsString('Submitted for approval', $first->json('message'));

        // One open request per shift
        $again = $this->as($this->clerk)->postJson('/portal/api/shift/extension', ['hours' => 1, 'reason' => 'again'])->assertStatus(422);
        $this->assertStringContainsString('awaiting approval', $again->json('message'));

        $request = DualControlRequest::where('subject_key', $shift->subjectKey())->sole();
        $this->assertSame('pending', $request->status);
        $this->actingAs($this->checker);
        app(DualControlService::class)->approve($request);

        $shift->refresh();
        $this->assertSame('active', $shift->status);
        $this->assertSame(120, $shift->extended_minutes);
        $this->assertEqualsWithDelta(120, now()->diffInMinutes($shift->end_time), 1);
        $this->bookingAction($this->clerk)->assertStatus(422);
    }

    public function test_a_clerks_own_request_waits_for_approval_even_with_the_rule_off(): void
    {
        DualControlConfig::where('operation_type', 'shift_extension')->update(['requires_dual_control' => false]);
        $shift = $this->endedShift();

        $this->as($this->clerk)->postJson('/portal/api/shift/extension', ['hours' => 1, 'reason' => 'Queue at the window'])
            ->assertOk()->assertJsonPath('shift.state', 'ended')->assertJsonPath('shift.pending_extension.hours', 1);
        $this->assertSame('ended', $shift->fresh()->status);
        $this->assertSame('pending', DualControlRequest::where('subject_key', $shift->subjectKey())->sole()->status);
    }

    public function test_a_supervisors_extension_follows_the_dual_control_rule(): void
    {
        // Rule off: applies at once
        DualControlConfig::where('operation_type', 'shift_extension')->update(['requires_dual_control' => false]);
        $shift = $this->endedShift();
        $this->as($this->admin)->post("/admin/shifts/{$shift->id}/extend", ['hours' => 1, 'reason' => 'Queue at the window'])->assertSessionHas('success');
        $this->assertSame('active', $shift->fresh()->status);

        // Rule on: waits for a second approver
        DualControlConfig::where('operation_type', 'shift_extension')->update(['requires_dual_control' => true]);
        $shift->fresh()->update(['end_time' => now()->addHours(2)]);
        $this->as($this->admin)->post("/admin/shifts/{$shift->id}/extend", ['hours' => 1, 'reason' => 'Late vessel'])->assertSessionHas('success');
        $this->assertSame('pending', DualControlRequest::where('subject_key', $shift->subjectKey())->latest('id')->first()->status);
        $this->assertEqualsWithDelta(120, now()->diffInMinutes($shift->fresh()->end_time), 1, 'not extended yet');
    }

    public function test_the_next_shift_starts_from_midnight_or_after_the_minimum_break(): void
    {
        $shifts = app(ShiftService::class);
        $next = function (string $start, string $end) use ($shifts) {
            $shift = new Shift(['start_time' => Carbon::parse($start), 'end_time' => Carbon::parse($end), 'actual_end_time' => Carbon::parse($end), 'status' => 'ended']);

            return $shifts->nextShiftFrom($shift)->format('Y-m-d H:i');
        };

        // The rotation, with the 8 h break: each can come back for the same slot
        $this->assertSame('2026-10-19 00:00', $next('2026-10-18 06:00', '2026-10-18 14:00'), 'day shift: midnight');
        $this->assertSame('2026-10-19 06:00', $next('2026-10-18 14:00', '2026-10-18 22:00'), 'afternoon: 8 h break');
        $this->assertSame('2026-10-19 14:00', $next('2026-10-18 22:00', '2026-10-19 06:00'), 'night: in time for 22:00');
        $this->assertSame('2026-10-19 00:00', $next('2026-10-18 07:36', '2026-10-18 15:36'), 'ended early: midnight');

        SystemConfig::setValue('shift_rest_hours', '0');
        $this->assertSame('2026-10-19 00:00', $next('2026-10-18 14:00', '2026-10-18 22:00'), 'no break: midnight only');
        $this->assertSame('2026-10-19 06:00', $next('2026-10-18 22:00', '2026-10-19 06:00'), 'no break: as soon as the night shift ends');
    }

    public function test_selling_resumes_at_midnight_and_midnight_never_ends_a_running_shift(): void
    {
        // Ended at 15:36: paused until 00:00, then the next booking starts a new shift
        $this->travelTo(today()->setTime(15, 36));
        $ended = Shift::create(['user_id' => $this->clerk->id, 'start_time' => now()->subHours(8), 'end_time' => now(),
            'actual_end_time' => now(), 'status' => 'ended', 'end_reason' => Shift::END_SELF]);
        $this->as($this->clerk)->getJson('/portal/api/shift')->assertJson(['state' => 'ended'])
            ->assertJsonPath('shift.next_shift_at', today()->addDay()->toIso8601String());
        $this->travelTo(today()->setTime(23, 59));
        $this->bookingAction($this->clerk)->assertForbidden()->assertJson(['code' => 'SHIFT_ENDED']);

        $this->travelTo(today()->addDay()->startOfDay());
        $this->as($this->clerk)->getJson('/portal/api/shift')->assertJson(['state' => 'not_started']);
        $this->bookingAction($this->clerk)->assertStatus(422);
        $this->assertSame('active', Shift::where('user_id', $this->clerk->id)->where('id', '!=', $ended->id)->sole()->status);

        // A night shift (22:00–06:00) runs through midnight and is only over at 06:00
        $this->travelTo(today()->setTime(22, 0));
        Shift::where('user_id', $this->clerk->id)->delete();
        $night = app(ShiftService::class)->startShift($this->clerk);
        $this->travelTo(today()->addDay()->setTime(0, 30));
        $this->bookingAction($this->clerk)->assertStatus(422);
        $this->assertSame('active', $night->fresh()->status);
        $this->assertSame('06:00', $night->fresh()->end_time->format('H:i'));

        $this->travelTo(today()->setTime(6, 1));
        $this->bookingAction($this->clerk)->assertForbidden()->assertJsonPath('shift.shift.next_shift_at', today()->setTime(14, 0)->toIso8601String());
    }

    public function test_no_extension_once_the_next_shift_may_start(): void
    {
        $this->travelTo(today()->setTime(15, 36));
        $shift = Shift::create(['user_id' => $this->clerk->id, 'start_time' => now()->subHours(8), 'end_time' => now(),
            'actual_end_time' => now(), 'status' => 'ended', 'end_reason' => Shift::END_SELF]);
        $this->as($this->clerk)->postJson('/portal/api/shift/extension', ['hours' => 1, 'reason' => 'Late vessel'])->assertOk();
        $request = DualControlRequest::where('subject_key', $shift->subjectKey())->sole();

        // Approved after midnight: refused, the request stays open, the shift stays ended
        $this->travelTo(today()->addDay()->setTime(0, 5));
        $this->actingAs($this->checker);
        try {
            app(DualControlService::class)->approve($request);
            $this->fail('An extension after the cut-off must be refused.');
        } catch (DualControlException $e) {
            $this->assertStringContainsString('nothing to extend', $e->getMessage());
        }
        $this->assertSame('ended', $shift->fresh()->status);

        // And the clerk is told a new shift starts on its own
        $request->update(['status' => 'cancelled']);
        $this->as($this->clerk)->postJson('/portal/api/shift/extension', ['hours' => 1, 'reason' => 'again'])
            ->assertStatus(422)->assertJsonPath('message', 'You can already start a new shift: it starts when you sign in again or make your next booking.');
    }

    public function test_the_clerk_ends_the_shift_and_gets_the_handover_summary(): void
    {
        $this->bookingAction($this->clerk);

        $this->as($this->clerk)->postJson('/portal/api/shift/end')->assertOk()
            ->assertJsonPath('shift.state', 'ended')
            ->assertJsonStructure(['message', 'shift' => ['summary' => ['bookings', 'collections', 'collected', 'cash_to_hand_over', 'unpaid', 'cancelled']]]);

        $shift = Shift::where('user_id', $this->clerk->id)->sole();
        $this->assertSame(Shift::END_SELF, $shift->end_reason);
        $this->assertSame($this->clerk->id, $shift->ended_by);
        $this->as($this->clerk)->postJson('/portal/api/shift/end')->assertStatus(422);
    }

    public function test_operations_shifts_lists_ends_and_exports(): void
    {
        $this->bookingAction($this->clerk);
        $shift = Shift::where('user_id', $this->clerk->id)->sole();

        $this->as($this->admin)->get('/admin/shifts')->assertOk()->assertSee('On shift now')->assertSee('Shift Clerk');
        $this->as($this->admin)->get("/admin/shifts/{$shift->id}")->assertOk()->assertSee('Cash to hand over');
        $this->as($this->admin)->get('/admin/shifts/export?user_id=' . $this->clerk->id)->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->as($this->admin)->post("/admin/shifts/{$shift->id}/end")->assertRedirect()->assertSessionHas('success');
        $shift->refresh();
        $this->assertSame(Shift::END_ADMIN, $shift->end_reason);
        $this->assertSame($this->admin->id, $shift->ended_by);
        $this->bookingAction($this->clerk)->assertForbidden();

        // Clerks are sent back to the sales portal (admin.access)
        $this->as($this->clerk)->get('/admin/shifts')->assertRedirect();
    }
}
