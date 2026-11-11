<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\CancelPaidTransactionsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:pending,paid,failed,expired,cancelled'],
            'method' => ['nullable', 'in:cash,qris'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $query = Transaction::query()->with('cashier:id,name')->latest('created_at');

        if (! empty($filters['q'])) {
            $query->where('order_number', 'like', '%'.trim($filters['q']).'%');
        }
        if (! empty($filters['status'])) {
            $query->where('payment_status', $filters['status']);
        }
        if (! empty($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }
        if (! empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date'].' 00:00:00');
        }
        if (! empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date'].' 23:59:59');
        }

        return view('admin.transactions.index', [
            'transactions' => $query->paginate(15)->withQueryString(),
            'filters' => $filters,
            'todayPaidCount' => Transaction::query()->where('payment_status', 'paid')->whereBetween('paid_at', $this->todayRange())->count(),
        ]);
    }

    public function cancel(Request $request, Transaction $transaction, CancelPaidTransactionsService $cancellations): RedirectResponse
    {
        $cancellations->cancelToday($transaction->id);

        return back()->with('status', 'Transaksi hari ini dibatalkan dan stok menu yang tercatat dikembalikan. Riwayat transaksi tetap tersimpan.');
    }

    public function cancelToday(CancelPaidTransactionsService $cancellations): RedirectResponse
    {
        $count = $cancellations->cancelAllToday();

        return back()->with('status', $count > 0
            ? $count.' transaksi hari ini dibatalkan dan stok menu yang tercatat dikembalikan. Riwayat tetap tersimpan.'
            : 'Tidak ada transaksi dibayar hari ini untuk dibatalkan.');
    }

    public function show(Transaction $transaction): View
    {
        return view('admin.transactions.show', [
            'transaction' => $transaction->load(['cashier:id,name', 'items']),
        ]);
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function todayRange(): array
    {
        $today = CarbonImmutable::now(config('app.timezone'));

        return [$today->startOfDay(), $today->endOfDay()];
    }
}
