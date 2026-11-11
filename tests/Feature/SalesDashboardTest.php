<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summary_counts_only_paid_transactions_in_selected_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $paid = Transaction::query()->create([
            'user_id' => $admin->id,
            'order_number' => 'TRX-261107-DASH0001',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 25000,
            'cash_received' => 30000,
            'change_amount' => 5000,
            'paid_at' => now()->startOfDay()->addHours(10),
        ]);
        $paid->items()->create([
            'product_name' => 'Nasi bebek', 'product_price' => 25000,
            'product_cost' => 15000, 'quantity' => 2, 'line_total' => 50000,
        ]);

        Transaction::query()->create([
            'user_id' => $admin->id,
            'order_number' => 'TRX-261107-DASH0002',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'qris',
            'payment_status' => 'pending',
            'total_amount' => 99000,
            'paid_at' => now()->startOfDay()->addHours(11),
        ]);

        $this->actingAs($admin)->get('/admin?start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertSee('Rp 25.000')
            ->assertSee('Nasi bebek')
            ->assertSee('Laba kotor')
            ->assertSee('Rp 20.000')
            ->assertDontSee('Rata-rata transaksi')
            ->assertSee('Margin 40,0%')
            ->assertSee('2', false)
            ->assertDontSee('Rp 99.000');
    }

    public function test_dashboard_rejects_an_end_date_before_start_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin?start_date=2026-11-07&end_date=2026-11-06')
            ->assertRedirect()
            ->assertSessionHasErrors('end_date');
    }

    public function test_dashboard_compares_paid_revenue_with_previous_equal_length_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Transaction::query()->create([
            'user_id' => $admin->id,
            'order_number' => 'TRX-261107-COMP0001',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 15000,
            'cash_received' => 15000,
            'change_amount' => 0,
            'paid_at' => now()->subDay()->setTime(11, 0),
        ]);
        Transaction::query()->create([
            'user_id' => $admin->id,
            'order_number' => 'TRX-261107-COMP0002',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 30000,
            'cash_received' => 30000,
            'change_amount' => 0,
            'paid_at' => now()->setTime(11, 0),
        ]);

        $this->actingAs($admin)->get('/admin?start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertSee('+100,0% dari periode sebelumnya');
    }
}
