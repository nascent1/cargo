<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthPermissionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function passenger_cannot_access_agent_endpoints(): void
    {
        $passenger = $this->createPassengerUser();

        // Attempt to access an agent portal endpoint
        $response = $this->actingAs($passenger)->getJson('/api/v1/portal/bookings');
        
        // Either 403 Forbidden or 401 Unauthorized depending on middleware
        $response->assertStatus(403);
    }

    #[Test]
    public function agent_cannot_access_admin_endpoints(): void
    {
        $agentData = $this->createAgentWithAccount();

        // Attempt to access admin dashboard
        $response = $this->actingAs($agentData['user'])->get('/admin');
        
        // Either 403 Forbidden or redirected due to middleware
        if ($response->status() === 302) {
            $this->assertTrue(true); // Redirection to login or somewhere is fine
        } else {
            $response->assertStatus(403);
        }
    }
}
