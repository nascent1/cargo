<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class CargoPortalAuthTest extends TestCase
{
    /**
     * Test portal /me web route returns user data for authenticated user.
     */
    public function test_portal_me_returns_user_data(): void
    {
        $user = User::where('domain', 'agent')->first()
            ?? User::where('domain', 'system_user')->first();

        if (!$user) {
            $this->markTestSkipped('No portal user found');
        }

        $response = $this->actingAs($user, 'web')
            ->getJson('/portal/api/me');

        // Debug: dump response if not JSON
        if (!str_contains($response->headers->get('content-type', ''), 'json')) {
            dump('Response status: ' . $response->getStatusCode());
            dump('Content-Type: ' . $response->headers->get('content-type'));
            dump('Body (first 500): ' . substr($response->getContent(), 0, 500));
        }

        $response->assertOk();
        $response->assertJsonStructure([
            'user' => ['id', 'first_name', 'last_name', 'email', 'domain'],
            'roles',
            'permissions',
        ]);
        $this->assertEquals($user->id, $response->json('user.id'));
    }

    /**
     * Test portal /me is protected (unauthenticated returns redirect/401).
     */
    public function test_portal_me_unauthenticated(): void
    {
        $response = $this->getJson('/portal/api/me');
        // Should return 401 or redirect
        $this->assertTrue(in_array($response->getStatusCode(), [401, 302, 419]));
    }

    /**
     * Test portal cargo types endpoint with auth.
     */
    public function test_portal_cargo_types(): void
    {
        $user = User::where('domain', 'agent')->first()
            ?? User::where('domain', 'system_user')->first();

        if (!$user) {
            $this->markTestSkipped('No portal user found');
        }

        $response = $this->actingAs($user)
            ->getJson('/portal/api/cargo/types');

        $response->assertOk();
    }
}
