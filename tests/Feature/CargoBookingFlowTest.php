<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CargoType;
use App\Models\CargoFare;
use App\Models\Route;
use App\Models\TransportUnit;
use App\Models\TransportService;
use App\Models\CargoInventory;

class CargoBookingFlowTest extends TestCase
{
    private function adminUser(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('slug', 'admin'))->firstOrFail();
    }

    private function getScheduledService(): ?TransportService
    {
        return TransportService::where('status', 'scheduled')
            ->whereHas('cargoInventory')
            ->first();
    }

    /**
     * Test fare calculation API.
     */
    public function test_fare_calculation(): void
    {
        $type = CargoType::where('status', 'active')->first();
        $route = Route::where('status', 'active')->first();
        if (!$type || !$route) {
            $this->markTestSkipped('Need active cargo type and route');
        }

        $response = $this->actingAs($this->adminUser())
            ->postJson('/admin/cargo/api/booking/calculate-fare', [
                'cargo_type_id' => $type->id,
                'route_id' => $route->id,
                'weight_kg' => 50,
                'quantity' => 1,
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['base_fare', 'surcharges', 'total_fare', 'currency', 'fare_id']);
        $this->assertGreaterThanOrEqual(0, $response->json('total_fare'));
    }

    /**
     * Test available services listing.
     */
    public function test_available_services(): void
    {
        $service = $this->getScheduledService();
        if (!$service) {
            $this->markTestSkipped('No scheduled services available');
        }

        $response = $this->actingAs($this->adminUser())
            ->getJson('/admin/cargo/api/booking/available-services?date=' . $service->departure_date);

        $response->assertOk();
        $response->assertJsonStructure([['id', 'departure_date', 'departure_time', 'route', 'transport_unit']]);
    }

    /**
     * Test full booking flow (end-to-end).
     */
    public function test_full_booking_flow(): void
    {
        $service = $this->getScheduledService();
        if (!$service) {
            $this->markTestSkipped('No scheduled services available');
        }

        $type = CargoType::where('status', 'active')->first();
        $route = Route::where('status', 'active')->first();
        if (!$type || !$route) {
            $this->markTestSkipped('Need active cargo type and route');
        }

        // Step 1: Calculate fare
        $fareResponse = $this->actingAs($this->adminUser())
            ->postJson('/admin/cargo/api/booking/calculate-fare', [
                'cargo_type_id' => $type->id,
                'route_id' => $route->id,
                'weight_kg' => 25,
                'quantity' => 1,
            ]);
        $fareResponse->assertOk();
        $fareAmount = $fareResponse->json('total_fare');

        // Step 2: Confirm booking
        $bookResponse = $this->actingAs($this->adminUser())
            ->postJson('/admin/cargo/api/booking/confirm', [
                'transport_service_id' => $service->id,
                'route_id' => $route->id,
                'sender_name' => 'John Mwangi',
                'sender_phone' => '+255712345678',
                'sender_id_type' => 'NIDA',
                'sender_id_number' => '19900101-12345-00001-01',
                'receiver_name' => 'Jane Bakari',
                'receiver_phone' => '+255798765432',
                'payment_method' => 'cash',
                'items' => [
                    [
                        'cargo_type_id' => $type->id,
                        'description' => 'Test parcel - 25kg box',
                        'weight_kg' => 25,
                        'quantity' => 1,
                        'fare_amount' => $fareAmount,
                    ],
                ],
            ]);

        $bookResponse->assertCreated();
        $bookResponse->assertJsonStructure([
            'message',
            'consignment' => ['id', 'consignment_number', 'status', 'total_amount_tzs', 'items'],
        ]);

        $consignment = $bookResponse->json('consignment');
        $this->assertEquals('booked', $consignment['status']);
        $this->assertStringStartsWith('CG-', $consignment['consignment_number']);
        $this->assertCount(1, $consignment['items']);
    }

    /**
     * Test booking validation - missing required fields.
     */
    public function test_booking_validation(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson('/admin/cargo/api/booking/confirm', [
                // Missing all required fields
            ]);

        $response->assertUnprocessable(); // 422
        $response->assertJsonValidationErrors(['transport_service_id', 'route_id', 'sender_name', 'sender_phone', 'receiver_name', 'receiver_phone', 'payment_method', 'items']);
    }
}
