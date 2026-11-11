<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_deactivate_and_reset_a_cashier_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->post('/admin/users', [
            'name' => 'Kasir Satu',
            'email' => 'kasir@example.test',
            'password' => 'secure-password-1',
            'password_confirmation' => 'secure-password-1',
        ])->assertRedirect();

        $cashier = User::query()->where('email', 'kasir@example.test')->firstOrFail();
        $this->assertSame('cashier', $cashier->role);
        $this->assertTrue($cashier->is_active);

        $this->put('/admin/users/'.$cashier->id, [
            'name' => 'Kasir Pagi', 'email' => 'pagi@example.test', 'is_active' => '0',
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $cashier->id, 'email' => 'pagi@example.test', 'is_active' => 0]);

        $this->put('/admin/users/'.$cashier->id.'/password', [
            'password' => 'secure-password-2', 'password_confirmation' => 'secure-password-2',
        ])->assertRedirect();
        $cashier->refresh();
        $this->assertTrue(password_verify('secure-password-2', $cashier->password));
    }

    public function test_eight_digit_numeric_password_is_accepted_for_cashier_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Kasir Angka',
            'email' => 'angka@example.test',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cashier = User::query()->where('email', 'angka@example.test')->firstOrFail();
        $this->put('/admin/users/'.$cashier->id.'/password', [
            'password' => '87654321',
            'password_confirmation' => '87654321',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(password_verify('87654321', $cashier->fresh()->password));
    }

    public function test_cashier_password_shorter_than_eight_characters_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Kasir Pendek',
            'email' => 'pendek@example.test',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ])->assertSessionHasErrors('password');
    }
}
