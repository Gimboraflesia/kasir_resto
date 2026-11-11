<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashierCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_receives_active_products_and_categories_only(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::query()->create(['name' => 'Makanan']);
        Product::query()->create(['category_id' => $category->id, 'name' => 'Nasi ayam', 'price' => 18000, 'is_active' => true]);
        Product::query()->create(['category_id' => $category->id, 'name' => 'Menu habis', 'price' => 12000, 'is_active' => false]);

        $this->actingAs($cashier)->get('/cashier')
            ->assertOk()
            ->assertSee('Nasi ayam')
            ->assertDontSee('Menu habis');
    }

    public function test_cashier_dashboard_shows_their_paid_sales_summary_for_selected_dates(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $otherCashier = User::factory()->create(['role' => 'cashier']);
        $ownPaidTransaction = Transaction::query()->create([
            'user_id' => $cashier->id,
            'order_number' => 'TRX-261107-SUMM0001',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 36000,
            'cash_received' => 40000,
            'change_amount' => 4000,
            'paid_at' => now()->startOfDay()->addHours(10),
        ]);
        $ownPaidTransaction->items()->create([
            'product_name' => 'Nasi bebek', 'product_price' => 18000, 'product_cost' => 10000,
            'quantity' => 2, 'line_total' => 36000,
        ]);
        Transaction::query()->create([
            'user_id' => $otherCashier->id,
            'order_number' => 'TRX-261107-SUMM0002',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 50000,
            'cash_received' => 50000,
            'change_amount' => 0,
            'paid_at' => now()->startOfDay()->addHours(11),
        ]);
        Transaction::query()->create([
            'user_id' => $cashier->id,
            'order_number' => 'TRX-261107-SUMM0003',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'qris',
            'payment_status' => 'pending',
            'total_amount' => 99000,
        ]);

        $this->actingAs($cashier)->get('/cashier?start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertSee('Rp 36.000')
            ->assertSee('Laba kotor')
            ->assertSee('Rp 16.000')
            ->assertDontSee('Rata-rata transaksi')
            ->assertSee('Transaksi dibayar')
            ->assertSee('Menu terjual')
            ->assertDontSee('Rp 50.000')
            ->assertDontSee('Rp 99.000');
    }

    public function test_cashier_sales_report_shows_scoped_metrics_chart_and_top_products(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $otherCashier = User::factory()->create(['role' => 'cashier']);
        $ownTransaction = Transaction::query()->create([
            'user_id' => $cashier->id,
            'order_number' => 'TRX-261107-REPT0001',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 20000,
            'cash_received' => 25000,
            'change_amount' => 5000,
            'paid_at' => now()->startOfDay()->addHours(10),
        ]);
        $ownTransaction->items()->create([
            'product_name' => 'Bebek bakar', 'product_price' => 10000, 'product_cost' => 5000,
            'quantity' => 2, 'line_total' => 20000,
        ]);
        $otherTransaction = Transaction::query()->create([
            'user_id' => $otherCashier->id,
            'order_number' => 'TRX-261107-REPT0002',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 70000,
            'cash_received' => 70000,
            'change_amount' => 0,
            'paid_at' => now()->startOfDay()->addHours(11),
        ]);
        $otherTransaction->items()->create([
            'product_name' => 'Menu kasir lain', 'product_price' => 70000, 'quantity' => 1, 'line_total' => 70000,
        ]);

        $this->actingAs($cashier)->get('/cashier/reports?start_date='.now()->toDateString().'&end_date='.now()->toDateString())
            ->assertOk()
            ->assertSee('Laporan penjualan')
            ->assertSee('Rp 20.000')
            ->assertSee('Laba kotor')
            ->assertSee('Rp 10.000')
            ->assertDontSee('Rata-rata transaksi')
            ->assertSee('Bebek bakar')
            ->assertSee('id="sales-chart"', false)
            ->assertDontSee('Rp 70.000')
            ->assertDontSee('Menu kasir lain');
    }

    public function test_checkout_recalculates_prices_saves_snapshots_and_is_idempotent(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::query()->create(['name' => 'Makanan']);
        $product = Product::query()->create([
            'category_id' => $category->id, 'name' => 'Nasi ayam', 'price' => 12000,
            'cost_price' => 5000, 'profit_percent' => 140, 'stock_quantity' => 5,
        ]);
        $payload = [
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'cash_received' => 40000,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'total_amount' => 1,
        ];

        $response = $this->actingAs($cashier)->post('/cashier/checkout', $payload)->assertRedirect();
        $receiptUrl = $response->headers->get('Location');

        $product->update(['name' => 'Nasi ayam spesial', 'price' => 20000]);
        $this->post('/cashier/checkout', $payload)->assertRedirect($receiptUrl);

        $this->assertDatabaseCount('transactions', 1);
        $transaction = Transaction::query()->with('items')->firstOrFail();
        $this->assertSame(24000, $transaction->total_amount);
        $this->assertSame(40000, $transaction->cash_received);
        $this->assertSame(16000, $transaction->change_amount);
        $this->assertSame('paid', $transaction->payment_status);
        $this->assertSame('Nasi ayam', $transaction->items->first()->product_name);
        $this->assertSame(12000, $transaction->items->first()->product_price);
        $this->assertSame(5000, $transaction->items->first()->product_cost);
        $this->assertSame(3, $product->fresh()->stock_quantity);

        $this->get($receiptUrl)->assertOk()->assertSee($transaction->order_number)->assertSee('Nasi ayam');
    }

    public function test_insufficient_cash_or_inactive_product_does_not_create_a_transaction(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::query()->create(['name' => 'Makanan']);
        $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Nasi ayam', 'price' => 12000]);
        $base = ['checkout_key' => (string) Str::uuid(), 'payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 1]]];

        $this->actingAs($cashier)->post('/cashier/checkout', $base + ['cash_received' => 11000])
            ->assertSessionHasErrors('cash_received');
        $this->assertDatabaseCount('transactions', 0);

        $product->update(['is_active' => false]);
        $this->post('/cashier/checkout', $base + ['cash_received' => 12000])
            ->assertSessionHasErrors('items');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_refuses_to_sell_more_than_available_stock(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::query()->create(['name' => 'Makanan']);
        $product = Product::query()->create([
            'category_id' => $category->id, 'name' => 'Sate bebek', 'price' => 30000, 'stock_quantity' => 1,
        ]);

        $this->actingAs($cashier)->post('/cashier/checkout', [
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'cash_received' => 60000,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_cashier_can_confirm_manual_qris_payment_without_cash_received(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::query()->create(['name' => 'Makanan']);
        $product = Product::query()->create([
            'category_id' => $category->id, 'name' => 'Bebek bakar', 'price' => 25000, 'stock_quantity' => 4,
        ]);

        $response = $this->actingAs($cashier)->post('/cashier/checkout', [
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'qris_manual',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertRedirect();

        $transaction = Transaction::query()->firstOrFail();
        $this->assertSame('qris', $transaction->payment_method);
        $this->assertSame('paid', $transaction->payment_status);
        $this->assertSame(50000, $transaction->total_amount);
        $this->assertNull($transaction->cash_received);
        $this->assertNull($transaction->change_amount);
        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('QRIS manual');
    }

    public function test_checkout_rejects_unknown_payment_method(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $category = Category::query()->create(['name' => 'Makanan']);
        $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Nasi ayam', 'price' => 12000]);

        $this->actingAs($cashier)->post('/cashier/checkout', [
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'transfer-random',
            'cash_received' => 12000,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cashier_can_only_view_their_own_receipt(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $otherCashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::query()->create([
            'user_id' => $otherCashier->id,
            'order_number' => 'TRX-261107-OWNR0001',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 10000,
            'cash_received' => 10000,
            'change_amount' => 0,
            'paid_at' => now(),
        ]);

        $this->actingAs($cashier)->get(route('cashier.transactions.receipt', $transaction))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('cashier.transactions.receipt', $transaction))->assertOk();
    }

    public function test_cashier_history_shows_only_transactions_created_by_that_cashier(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $otherCashier = User::factory()->create(['role' => 'cashier']);
        $ownTransaction = Transaction::query()->create([
            'user_id' => $cashier->id,
            'order_number' => 'TRX-261107-MYTX0001',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 10000,
            'cash_received' => 10000,
            'change_amount' => 0,
            'paid_at' => now(),
        ]);
        Transaction::query()->create([
            'user_id' => $otherCashier->id,
            'order_number' => 'TRX-261107-OTHX0001',
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 20000,
            'cash_received' => 20000,
            'change_amount' => 0,
            'paid_at' => now(),
        ]);

        $this->actingAs($cashier)->get(route('cashier.transactions.index'))
            ->assertOk()
            ->assertSee($ownTransaction->order_number)
            ->assertDontSee('TRX-261107-OTHX0001');
    }
}



