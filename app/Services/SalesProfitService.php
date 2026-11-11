<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SalesProfitService
{
    /** @return array{grossProfit: int, grossMarginPercent: ?float, uncostedUnits: int, uncostedRevenue: int} */
    public function forPeriod(CarbonImmutable $start, CarbonImmutable $end, ?int $cashierId = null): array
    {
        $query = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$start, $end]);

        if ($cashierId !== null) {
            $query->where('transactions.user_id', $cashierId);
        }

        $totals = $query->selectRaw('
            COALESCE(SUM(CASE WHEN transaction_items.product_cost IS NOT NULL THEN transaction_items.line_total ELSE 0 END), 0) as costed_revenue,
            COALESCE(SUM(CASE WHEN transaction_items.product_cost IS NOT NULL THEN transaction_items.line_total - (transaction_items.product_cost * transaction_items.quantity) ELSE 0 END), 0) as gross_profit,
            COALESCE(SUM(CASE WHEN transaction_items.product_cost IS NULL THEN transaction_items.quantity ELSE 0 END), 0) as uncosted_units,
            COALESCE(SUM(CASE WHEN transaction_items.product_cost IS NULL THEN transaction_items.line_total ELSE 0 END), 0) as uncosted_revenue
        ')->first();

        $costedRevenue = (int) $totals->costed_revenue;
        $grossProfit = (int) $totals->gross_profit;

        return [
            'grossProfit' => $grossProfit,
            'grossMarginPercent' => $costedRevenue > 0 ? round(($grossProfit / $costedRevenue) * 100, 1) : null,
            'uncostedUnits' => (int) $totals->uncosted_units,
            'uncostedRevenue' => (int) $totals->uncosted_revenue,
        ];
    }
}
