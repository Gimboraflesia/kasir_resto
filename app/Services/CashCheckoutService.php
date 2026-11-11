<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashCheckoutService
{
    /** @param array{checkout_key: string, payment_method: string, cash_received: ?int, items: array<int, array{product_id: int, quantity: int}>} $data */
    public function complete(User $cashier, array $data): Transaction
    {
        $existing = Transaction::query()->where('checkout_key', $data['checkout_key'])->first();
        if ($existing) { abort_unless($existing->user_id === $cashier->id, 409); return $existing; }

        try {
            return DB::transaction(function () use ($cashier, $data): Transaction {
                $requested = collect($data['items'])->keyBy(fn (array $item) => (int) $item['product_id']);
                $products = Product::query()->whereIn('id', $requested->keys())->where('is_active', true)->lockForUpdate()->get()->keyBy('id');
                if ($products->count() !== $requested->count()) throw ValidationException::withMessages(['items' => 'Ada menu yang sudah tidak tersedia. Periksa kembali pesanan.']);

                $total = 0; $lines = [];
                foreach ($requested as $productId => $item) {
                    $product = $products->get((int) $productId); $quantity = (int) $item['quantity'];
                    if ($product->stock_quantity !== null && $product->stock_quantity < $quantity) throw ValidationException::withMessages(['items' => 'Stok '.$product->name.' tidak mencukupi. Sisa stok: '.$product->stock_quantity.'.']);
                    $lineTotal = $product->price * $quantity; $total += $lineTotal;
                    $lines[] = ['product_id' => $product->id, 'product_name' => $product->name, 'product_price' => $product->price, 'product_cost' => $product->cost_price, 'quantity' => $quantity, 'line_total' => $lineTotal];
                }
                $paymentMethod = $data['payment_method'];
                $cashReceived = $paymentMethod === 'cash' ? (int) $data['cash_received'] : null;
                if ($paymentMethod === 'cash' && $cashReceived < $total) throw ValidationException::withMessages(['cash_received' => 'Uang yang diterima belum mencukupi total pembayaran.']);
                $orderNumber = 'TRX-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
                $isPaid = in_array($paymentMethod, ['cash', 'qris_manual'], true);
                $transaction = Transaction::query()->create([
                    'user_id' => $cashier->id, 'order_number' => $orderNumber, 'checkout_key' => $data['checkout_key'],
                    'payment_method' => $paymentMethod === 'qris_manual' ? 'qris' : $paymentMethod,
                    'payment_status' => $isPaid ? 'paid' : 'pending', 'total_amount' => $total,
                    'cash_received' => $cashReceived, 'change_amount' => $paymentMethod === 'cash' ? $cashReceived - $total : null,
                    'paid_at' => $isPaid ? now() : null,
                ]);
                $transaction->items()->createMany($lines);
                // Reserve stock for pending Snap payments too; release it if Midtrans expires or cancels the order.
                foreach ($requested as $productId => $item) { $product = $products->get((int) $productId); if ($product->stock_quantity !== null) $product->decrement('stock_quantity', (int) $item['quantity']); }
                return $transaction;
            }, attempts: 3);
        } catch (QueryException $exception) {
            $duplicate = Transaction::query()->where('checkout_key', $data['checkout_key'])->first();
            if ($duplicate) { abort_unless($duplicate->user_id === $cashier->id, 409); return $duplicate; }
            throw $exception;
        }
    }
}
