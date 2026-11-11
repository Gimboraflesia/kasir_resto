<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\SalesProfitService;
use Carbon\CarbonImmutable;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SalesReportController extends Controller
{
    public function index(Request $request, SalesProfitService $profitService): View
    {
        $range = $this->range($request);
        $data = $this->reportData($range['start'], $range['end'], $profitService);

        return view('admin.reports.index', [
            ...$data,
            'period' => $range['period'],
            'filters' => ['start_date' => $range['start']->toDateString(), 'end_date' => $range['end']->toDateString()],
        ]);
    }

    public function export(Request $request)
    {
        $range = $this->range($request);
        $transactions = Transaction::query()
            ->with(['items', 'cashier:id,name'])
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$range['start'], $range['end']])
            ->orderBy('id')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Penjualan');
        $sheet->fromArray(['Nomor transaksi', 'Tanggal', 'Kasir', 'Metode pembayaran', 'Rincian menu', 'Total jual (Rp)', 'Modal tercatat (Rp)', 'Laba kotor tercatat (Rp)'], null, 'A1');
        $row = 2;

        foreach ($transactions as $transaction) {
            $items = $transaction->items->map(fn ($item) => $item->product_name.' x'.$item->quantity)->implode('; ');
            $values = [
                $transaction->order_number,
                $transaction->paid_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '',
                $transaction->cashier->name,
                strtoupper($transaction->payment_method),
                $items,
            ];

            foreach ($values as $column => $value) {
                $sheet->setCellValueExplicit([$column + 1, $row], $value, DataType::TYPE_STRING);
            }
            $sheet->setCellValue([6, $row], $transaction->total_amount);
            $costedItems = $transaction->items->filter(fn ($item) => $item->product_cost !== null);
            $costTotal = (int) $costedItems->sum(fn ($item) => $item->product_cost * $item->quantity);
            $profitTotal = (int) $costedItems->sum(fn ($item) => $item->line_total - ($item->product_cost * $item->quantity));
            $sheet->setCellValue([7, $row], $costTotal);
            $sheet->setCellValue([8, $row], $profitTotal);
            $row++;
        }

        $lastRow = max(1, $row - 1);
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '304B39']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        if ($lastRow > 1) {
            $sheet->getStyle('F2:H'.$lastRow)->getNumberFormat()->setFormatCode('#,##0');
        }
        $sheet->getStyle('A1:H'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(21);
        $sheet->getColumnDimension('E')->setWidth(48);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(22);
        $sheet->getColumnDimension('H')->setWidth(25);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H'.$lastRow);

        $filename = 'laporan-penjualan-'.$range['start']->format('Ymd').'-'.$range['end']->format('Ymd').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request, SalesProfitService $profitService): Response
    {
        $range = $this->range($request);
        $report = $this->reportData($range['start'], $range['end'], $profitService);
        $transactions = Transaction::query()
            ->with(['items', 'cashier:id,name'])
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$range['start'], $range['end']])
            ->orderBy('created_at')
            ->get();
        $html = view('admin.reports.pdf', [
            ...$report,
            'transactions' => $transactions,
            'start' => $range['start'],
            'end' => $range['end'],
        ])->render();
        $options = new Options;
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsRemoteEnabled(false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-penjualan-'.$range['start']->format('Ymd').'-'.$range['end']->format('Ymd').'.pdf"',
        ]);
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable, period: string} */
    private function range(Request $request): array
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);
        $timezone = config('app.timezone');
        $period = $filters['period'] ?? 'daily';
        $today = CarbonImmutable::now($timezone)->startOfDay();

        [$start, $end] = match ($period) {
            'weekly' => [$today->startOfWeek(CarbonImmutable::MONDAY), $today->endOfWeek(CarbonImmutable::SUNDAY)],
            'monthly' => [$today->startOfMonth(), $today->endOfMonth()],
            'custom' => [
                isset($filters['start_date']) ? CarbonImmutable::parse($filters['start_date'], $timezone)->startOfDay() : $today,
                isset($filters['end_date']) ? CarbonImmutable::parse($filters['end_date'], $timezone)->endOfDay() : $today->endOfDay(),
            ],
            default => [$today, $today->endOfDay()],
        };

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.']);
        }

        return compact('start', 'end', 'period');
    }

    private function reportData(CarbonImmutable $start, CarbonImmutable $end, SalesProfitService $profitService): array
    {
        $paid = Transaction::query()->where('payment_status', 'paid')->whereBetween('paid_at', [$start, $end]);
        $revenue = (int) (clone $paid)->sum('total_amount');
        $count = (int) (clone $paid)->count();
        $units = (int) DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$start, $end])
            ->sum('transaction_items.quantity');
        $methods = (clone $paid)
            ->select('payment_method', DB::raw('COUNT(*) as transaction_count'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('payment_method')
            ->get();
        $topProducts = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transactions.paid_at', [$start, $end])
            ->select('transaction_items.product_name', DB::raw('SUM(transaction_items.quantity) as units_sold'), DB::raw('SUM(transaction_items.line_total) as revenue'))
            ->groupBy('transaction_items.product_name')
            ->orderByDesc('units_sold')
            ->limit(10)
            ->get();

        return [
            'revenue' => $revenue,
            'transactionCount' => $count,
            'averageTransaction' => $count ? (int) round($revenue / $count) : 0,
            'unitsSold' => $units,
            'methods' => $methods,
            'topProducts' => $topProducts,
            ...$profitService->forPeriod($start, $end),
        ];
    }
}
