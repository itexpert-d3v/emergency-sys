<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_login_route_redirects_users_to_their_role_home(): void
    {
        $staff = User::factory()->create(['email' => 'staff@example.gov.ph', 'password' => bcrypt('secret123'), 'role' => 'staff']);
        $admin = User::factory()->create(['email' => 'admin@example.gov.ph', 'password' => bcrypt('secret123'), 'role' => 'admin']);

        $this->post(route('login'), ['email' => $staff->email, 'password' => 'secret123'])
            ->assertRedirect(route('operations.dashboard'));

        auth()->logout();
        $this->post(route('login'), ['email' => $admin->email, 'password' => 'secret123'])
            ->assertRedirect(route('operations.dashboard'));
    }

    public function test_fragmented_login_urls_are_removed(): void
    {
        $this->get('/staff/login')->assertNotFound();
        $this->post('/staff/login')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
        $this->post('/admin/login')->assertNotFound();
    }

    public function test_login_attempts_are_limited_per_ip_and_normalized_email(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => 'locked@example.gov.ph',
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login'), [
            'email' => 'locked@example.gov.ph',
            'password' => 'incorrect-password',
        ])->assertTooManyRequests();
    }
}