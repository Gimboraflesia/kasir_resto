<?php

namespace App\Services;

use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelPaidTransactionsService
{
    public function cancelToday(int $transactionId): void
    {
        DB::transaction(function () use ($transactionId): void {
            [$start, $end] = $this->todayRange();
            $transaction = Transaction::query()->whereKey($transactionId)->lockForUpdate()->firstOrFail();

            if ($transaction->payment_status !== 'paid' || ! $transaction->paid_at?->betweenIncluded($start, $end)) {
                throw ValidationException::withMessages([
                    'transaction' => 'Hanya transaksi yang dibayar hari ini yang dapat dibatalkan.',
                ]);
            }

            $this->cancelLockedTransaction($transaction);
        }, attempts: 3);
    }

    public function cancelAllToday(): int
    {
        return DB::transaction(function (): int {
            [$start, $end] = $this->todayRange();
            $transactions = Transaction::query()
                ->where('payment_status', 'paid')
                ->whereBetween('paid_at', [$start, $end])
                ->lockForUpdate()
                ->get();

            foreach ($transactions as $transaction) {
                $this->cancelLockedTransaction($transaction);
            }

            return $transactions->count();
        }, attempts: 3);
    }

    private function cancelLockedTransaction(Transaction $transaction): void
    {
        $items = $transaction->items()->get();

        foreach ($items->whereNotNull('product_id')->groupBy('product_id') as $productId => $productItems) {
            $quantity = (int) $productItems->sum('quantity');
            $product = DB::table('products')->where('id', $productId)->lockForUpdate()->first();

            if ($product && $product->stock_quantity !== null) {
                DB::table('products')->where('id', $productId)->update([
                    'stock_quantity' => $product->stock_quantity + $quantity,
                    'updated_at' => now(),
                ]);
            }
        }

        $transaction->update(['payment_status' => 'cancelled']);
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function todayRange(): array
    {
        $today = CarbonImmutable::now(config('app.timezone'));

        return [$today->startOfDay(), $today->endOfDay()];
    }
}
