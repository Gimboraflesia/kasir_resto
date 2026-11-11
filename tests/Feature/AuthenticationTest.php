<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login_and_cannot_open_internal_pages(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/cashier')->assertRedirect(route('login'));
    }

    public function test_admin_and_cashier_are_sent_to_their_role_home_after_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->post('/logout')->assertRedirect(route('login'));

        $this->post('/login', ['email' => $cashier->email, 'password' => 'password'])
            ->assertRedirect(route('cashier.index'));
    }

    public function test_cashier_cannot_access_admin_dashboard(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)->get('/admin')->assertForbidden();
    }

    public function test_disabled_cashier_cannot_sign_in(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier', 'is_active' => false]);

        $this->post('/login', ['email' => $cashier->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
