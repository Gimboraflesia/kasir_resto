<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\SalesProfitService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesReportController extends Controller
{
    public function index(Request $request, SalesProfitService $profitService): View
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $timezone = config('app.timezone');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $start = isset($filters['start_date'])
            ? CarbonImmutable::parse($filters['start_date'], $timezone)->startOfDay()
            : $today;
        $end = isset($filters['end_date'])
            ? CarbonImmutable::parse($filters['end_date'], $timezone)->endOfDay()
            : (isset($filters['start_date']) ? $start->endOfDay() : $today->endOfDay());

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.']);
        }

        $cashierId = $request->user()->id;
        $paid = Transaction::query()
            ->where('user_id', $cashierId)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$start, $end]);
        $revenue = (int) (clone $paid)->sum('total_amount');
        $transactionCount = (int) (clone $paid)->count();
        $periodDays = (int) $start->diffInDays($end) + 1;
        $previousEnd = $start->subDay()->endOfDay();
        $previousStart = $previousEnd->subDays($periodDays - 1)->startOfDay();
        $previousRevenue = (int) Transaction::query()
            ->where('user_id', $cashierId)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])
            ->sum('total_amount');
        $revenueChange = $previousRevenue > 0
            ? round((($revenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : null;
        $profitMetrics = $profitService->forPeriod($start, $end, $cashierId);
        $unitsSold = (int) DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.user_id', $cashierId)
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$start, $end])
            ->sum('transaction_items.quantity');
        $topProducts = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.user_id', $cashierId)
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
            ->where('user_id', $cashierId)
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$chartStart, $chartEnd])
            ->get(['total_amount', 'paid_at']);

        return view('cashier.reports.index', [
            'filters' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
            'revenue' => $revenue,
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
        $totals = $transactions->groupBy(fn (Transaction $transaction) => $transaction->paid_at->timezone($timezone)->toDateString())
            ->map(fn ($items) => (int) $items->sum('total_amount'));
        $labels = [];
        $values = [];

        for ($offset = 6; $offset >= 0; $offset--) {
            $date = $today->subDays($offset);
            $labels[] = $date->translatedFormat('D');
            $values[] = $totals->get($date->toDateString(), 0);
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
