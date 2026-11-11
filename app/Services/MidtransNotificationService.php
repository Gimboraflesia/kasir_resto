<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransNotificationService
{
    public function handle(array $payload): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');
        if ($serverKey === '') return false;
        $expected = hash('sha512', (string) ($payload['order_id'] ?? '').(string) ($payload['status_code'] ?? '').(string) ($payload['gross_amount'] ?? '').$serverKey);
        if (! hash_equals($expected, (string) ($payload['signature_key'] ?? ''))) return false;

        return DB::transaction(function () use ($payload): bool {
            $transaction = Transaction::query()->where('order_number', $payload['order_id'] ?? '')->lockForUpdate()->first();
            if (! $transaction || $transaction->payment_method !== 'qris' || (int) round((float) $payload['gross_amount']) !== $transaction->total_amount) return false;
            $status = (string) ($payload['transaction_status'] ?? ''); $fraud = (string) ($payload['fraud_status'] ?? '');
            $mapped = match (true) {
                $status === 'settlement', $status === 'capture' && $fraud === 'accept' => 'paid',
                $status === 'pending' => 'pending', $status === 'expire' => 'expired', $status === 'cancel' => 'cancelled', $status === 'deny' => 'failed', default => null,
            };
            if ($mapped === null) return true;
            if (in_array($transaction->payment_status, ['paid', 'expired', 'cancelled', 'failed'], true)) return true;
            if (in_array($mapped, ['expired', 'cancelled', 'failed'], true)) $this->restoreReservedStock($transaction);
            $transaction->payment_status = $mapped;
            $transaction->midtrans_transaction_id = $payload['transaction_id'] ?? $transaction->midtrans_transaction_id;
            $transaction->paid_at = $mapped === 'paid' ? ($transaction->paid_at ?? now()) : null;
            $transaction->save();
            Log::info('Midtrans notification processed', ['order_id' => $transaction->order_number, 'status' => $mapped]);
            return true;
        });
    }

    private function restoreReservedStock(Transaction $transaction): void
    {
        $items = $transaction->items()->get();
        foreach ($items->whereNotNull('product_id')->groupBy('product_id') as $productId => $productItems) {
            $quantity = (int) $productItems->sum('quantity');
            $product = DB::table('products')->where('id', $productId)->lockForUpdate()->first();
            if ($product && $product->stock_quantity !== null) DB::table('products')->where('id', $productId)->update(['stock_quantity' => $product->stock_quantity + $quantity, 'updated_at' => now()]);
        }
    }
}
