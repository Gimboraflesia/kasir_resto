<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminTransactionCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_cancel_one_paid_transaction_from_today_and_restore_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->trackedProduct(3);
        $transaction = $this->paidTransaction($product, 2, now());

        $this->actingAs($admin)->post(route('admin.transactions.cancel', $transaction))
            ->assertRedirect()->assertSessionHas('status');

        $this->assertSame('cancelled', $transaction->fresh()->payment_status);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'order_number' => $transaction->order_number]);

        $this->actingAs($admin)->post(route('admin.transactions.cancel', $transaction))->assertSessionHasErrors('transaction');
        $this->assertSame(5, $product->fresh()->stock_quantity);
    }

    public function test_admin_can_cancel_all_paid_transactions_today_without_touching_older_sales(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->trackedProduct(0);
        $todayOne = $this->paidTransaction($product, 2, now()->startOfDay()->addHours(9));
        $todayTwo = $this->paidTransaction($product, 3, now()->startOfDay()->addHours(12));
        $yesterday = $this->paidTransaction($product, 4, now()->subDay()->startOfDay()->addHours(23));

        $this->actingAs($admin)->get(route('admin.transactions.index'))
            ->assertOk()->assertSee('Batalkan semua penjualan hari ini')->assertSee('(2)');
        $this->post(route('admin.transactions.cancel-today'))->assertRedirect()->assertSessionHas('status');

        $this->assertSame('cancelled', $todayOne->fresh()->payment_status);
        $this->assertSame('cancelled', $todayTwo->fresh()->payment_status);
        $this->assertSame('paid', $yesterday->fresh()->payment_status);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('transactions', 3);
    }

    public function test_cashier_cannot_cancel_sales(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $product = $this->trackedProduct(0);
        $transaction = $this->paidTransaction($product, 1, now());

        $this->actingAs($cashier)->post(route('admin.transactions.cancel', $transaction))->assertForbidden();
        $this->post(route('admin.transactions.cancel-today'))->assertForbidden();
        $this->assertSame('paid', $transaction->fresh()->payment_status);
        $this->assertSame(0, $product->fresh()->stock_quantity);
    }

    private function trackedProduct(int $stock): Product
    {
        $category = Category::query()->create(['name' => 'Makanan']);

        return Product::query()->create([
            'category_id' => $category->id, 'name' => 'Bebek goreng', 'price' => 20000, 'stock_quantity' => $stock,
        ]);
    }

    private function paidTransaction(Product $product, int $quantity, $paidAt): Transaction
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::query()->create([
            'user_id' => $cashier->id,
            'order_number' => 'TRX-'.Str::upper(Str::random(12)),
            'checkout_key' => (string) Str::uuid(),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'total_amount' => 20000 * $quantity,
            'cash_received' => 20000 * $quantity,
            'change_amount' => 0,
            'paid_at' => $paidAt,
        ]);
        $transaction->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => 20000,
            'quantity' => $quantity,
            'line_total' => 20000 * $quantity,
        ]);

        return $transaction;
    }
}
