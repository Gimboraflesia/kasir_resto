<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\SalesProfitService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesDashboardController extends Controller
{
    public function __invoke(Request $request, SalesProfitService $profitService): View
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);
        $timezone = config('app.timezone');
        $start = isset($filters['start_date'])
            ? CarbonImmutable::parse($filters['start_date'], $timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->startOfDay();
        $end = isset($filters['end_date'])
            ? CarbonImmutable::parse($filters['end_date'], $timezone)->endOfDay()
            : $start->endOfDay();

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.']);
        }

        $paidTransactions = Transaction::query()
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$start, $end]);
        $revenue = (int) (clone $paidTransactions)->sum('total_amount');
        $periodDays = (int) $start->diffInDays($end) + 1;
        $previousEnd = $start->subDay()->endOfDay();
        $previousStart = $previousEnd->subDays($periodDays - 1)->startOfDay();
        $previousRevenue = (int) Transaction::query()
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])
            ->sum('total_amount');
        $revenueChange = $previousRevenue > 0
            ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : null;
        $profitMetrics = $profitService->forPeriod($start, $end);
        $transactionCount = (int) (clone $paidTransactions)->count();
        $unitsSold = (int) TransactionItem::query()
            ->whereHas('transaction', fn ($query) => $query->where('payment_status', 'paid')->whereBetween('paid_at', [$start, $end]))
            ->sum('quantity');

        $topProducts = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$start, $end])
            ->select('transaction_items.product_name', DB::raw('SUM(transaction_items.quantity) as units_sold'))
            ->groupBy('transaction_items.product_name')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get();

        $chartStart = CarbonImmutable::now($timezone)->startOfMonth()->subMonths(5)->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
        $chartEnd = CarbonImmutable::now($timezone)->endOfDay();
        $chartTransactions = Transaction::query()
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$chartStart, $chartEnd])
            ->get(['total_amount', 'paid_at']);

        return view('admin.dashboard', [
            'filters' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
            'revenue' => $revenue,
            'previousRevenue' => $previousRevenue,
            'revenueChange' => $revenueChange,
            ...$profitMetrics,
            'transactionCount' => $transactionCount,
            'averageTransaction' => $transactionCount ? (int) round($revenue / $transactionCount) : 0,
            'unitsSold' => $unitsSold,
            'topProducts' => $topProducts,
            'dailyChart' => $this->dailySeries($chartTransactions, $timezone),
            'weeklyChart' => $this->weeklySeries($chartTransactions, $timezone),
            'monthlyChart' => $this->monthlySeries($chartTransactions, $timezone),
        ]);
    }

    private function dailySeries($transactions, string $timezone): array
    {
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $labels = [];
        $values = [];
        $totals = $transactions->groupBy(fn (Transaction $transaction) => $transaction->paid_at->timezone($timezone)->toDateString())
            ->map(fn ($items) => (int) $items->sum('total_amount'));

        for ($offset = 6; $offset >= 0; $offset--) {
            $date = $today->subDays($offset);
            $key = $date->toDateString();
            $labels[] = $date->translatedFormat('D');
            $values[] = $totals->get($key, 0);
        }

        return compact('labels', 'values');
    }

    private function weeklySeries($transactions, string $timezone): array
    {
        $thisWeek = CarbonImmutable::now($timezone)->startOfWeek(CarbonImmutable::MONDAY);
        $totals = $transactions->groupBy(fn (Transaction $transaction) => $transaction->paid_at->timezone($timezone)->startOfWeek(CarbonImmutable::MONDAY)->toDateString())
            ->map(fn ($items) => (int) $items->sum('total_amount'));
        $labels = [];
        $values = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $week = $thisWeek->subWeeks($offset);
            $labels[] = $week->translatedFormat('d M');
            $values[] = $totals->get($week->toDateString(), 0);
        }

        return compact('labels', 'values');
    }

    private function monthlySeries($transactions, string $timezone): array
    {
        $thisMonth = CarbonImmutable::now($timezone)->startOfMonth();
        $totals = $transactions->groupBy(fn (Transaction $transaction) => $transaction->paid_at->timezone($timezone)->format('Y-m'))
            ->map(fn ($items) => (int) $items->sum('total_amount'));
        $labels = [];
        $values = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $month = $thisMonth->subMonths($offset);
            $labels[] = $month->translatedFormat('M Y');
            $values[] = $totals->get($month->format('Y-m'), 0);
        }

        return compact('labels', 'values');
    }
}
