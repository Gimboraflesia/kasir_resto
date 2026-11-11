<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); config(['services.midtrans.server_key' => 'sandbox-test-key']); }

    public function test_signed_settlement_is_idempotent(): void
    {
        $transaction = $this->transaction(); $payload = $this->notification($transaction, 'settlement');
        $this->postJson('/webhooks/midtrans', $payload)->assertOk(); $paidAt = $transaction->fresh()->paid_at;
        $this->postJson('/webhooks/midtrans', $payload)->assertOk();
        $this->assertSame('paid', $transaction->fresh()->payment_status);
        $this->assertSame($paidAt->toDateTimeString(), $transaction->fresh()->paid_at->toDateTimeString());
    }

    public function test_invalid_signature_and_amount_do_not_update_transaction(): void
    {
        $transaction = $this->transaction(); $payload = $this->notification($transaction, 'settlement');
        $this->postJson('/webhooks/midtrans', [...$payload, 'signature_key' => 'invalid'])->assertForbidden();
        $payload['gross_amount'] = '99999.00'; $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'sandbox-test-key');
        $this->postJson('/webhooks/midtrans', $payload)->assertForbidden();
        $this->assertSame('pending', $transaction->fresh()->payment_status);
    }

    public function test_expiry_restores_stock_and_late_settlement_does_not_reopen_order(): void
    {
        $category = Category::query()->create(['name' => 'Menu']);
        $product = Product::query()->create(['category_id' => $category->id, 'name' => 'Bebek', 'price' => 25000, 'stock_quantity' => 3]);
        $transaction = $this->transaction($product);
        $this->postJson('/webhooks/midtrans', $this->notification($transaction, 'expire'))->assertOk();
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->postJson('/webhooks/midtrans', $this->notification($transaction, 'settlement'))->assertOk();
        $this->assertSame('expired', $transaction->fresh()->payment_status);
    }

    private function transaction(?Product $product = null): Transaction
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $transaction = Transaction::query()->create(['user_id' => $cashier->id, 'order_number' => 'TRX-'.Str::upper(Str::random(14)), 'checkout_key' => (string) Str::uuid(), 'payment_method' => 'qris', 'payment_status' => 'pending', 'total_amount' => 25000]);
        if ($product) { $transaction->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'product_price' => $product->price, 'quantity' => 1, 'line_total' => $product->price]); $product->decrement('stock_quantity'); }
        return $transaction;
    }

    private function notification(Transaction $transaction, string $status): array
    {
        $payload = ['order_id' => $transaction->order_number, 'status_code' => '200', 'gross_amount' => '25000.00', 'transaction_status' => $status, 'transaction_id' => 'sandbox-transaction-id'];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'sandbox-test-key');
        return $payload;
    }
}



