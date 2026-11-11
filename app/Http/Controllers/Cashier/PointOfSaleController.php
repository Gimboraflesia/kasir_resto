<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantSetting;
use App\Models\Transaction;
use App\Services\CashCheckoutService;
use App\Services\MidtransSnapService;
use App\Services\SalesProfitService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PointOfSaleController extends Controller
{
    public function index(Request $request, SalesProfitService $profitService): View
    {
        $filters = $request->validate(['start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date']]);
        $timezone = config('app.timezone'); $today = CarbonImmutable::now($timezone)->startOfDay();
        $start = isset($filters['start_date']) ? CarbonImmutable::parse($filters['start_date'], $timezone)->startOfDay() : $today;
        $end = isset($filters['end_date']) ? CarbonImmutable::parse($filters['end_date'], $timezone)->endOfDay() : (isset($filters['start_date']) ? $start->endOfDay() : $today->endOfDay());
        if ($end->lessThan($start)) throw ValidationException::withMessages(['end_date' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.']);
        $cashierId = $request->user()->id;
        $paid = Transaction::query()->where('user_id', $cashierId)->where('payment_status', 'paid')->whereBetween('paid_at', [$start, $end]);
        $revenue = (int) (clone $paid)->sum('total_amount'); $transactionCount = (int) (clone $paid)->count();
        $unitsSold = (int) DB::table('transaction_items')->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')->where('transactions.user_id', $cashierId)->where('transactions.payment_status', 'paid')->whereBetween('transactions.paid_at', [$start, $end])->sum('transaction_items.quantity');
        $profitMetrics = $profitService->forPeriod($start, $end, $cashierId);
        return view('cashier.index', [
            'categories' => Category::query()->whereHas('products', fn ($query) => $query->where('is_active', true))->withCount(['products' => fn ($query) => $query->where('is_active', true)])->orderBy('name')->get(),
            'products' => Product::query()->with('category:id,name')->where('is_active', true)->orderBy('name')->get(['id', 'category_id', 'name', 'price', 'stock_quantity', 'image_path']),
            'checkoutKey' => (string) Str::uuid(), 'salesSummary' => ['revenue' => $revenue, 'transactionCount' => $transactionCount, 'averageTransaction' => $transactionCount ? (int) round($revenue / $transactionCount) : 0, 'unitsSold' => $unitsSold, ...$profitMetrics],
            'salesFilters' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString()],
        ]);
    }

    public function checkout(Request $request, CashCheckoutService $checkout, MidtransSnapService $snap): RedirectResponse
    {
        $data = $request->validate([
            'checkout_key' => ['required', 'uuid'], 'payment_method' => ['required', Rule::in(['cash', 'qris_manual', 'qris'])],
            'cash_received' => ['required_if:payment_method,cash', 'nullable', 'integer', 'min:0', 'max:9999999999999'],
            'items' => ['required', 'array', 'min:1', 'max:50'], 'items.*.product_id' => ['required', 'integer', 'distinct:strict', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ], ['items.required' => 'Keranjang masih kosong.', 'items.min' => 'Tambahkan menu sebelum menyelesaikan pembayaran.', 'items.*.product_id.distinct' => 'Menu yang sama tidak boleh dikirim berulang kali.']);
        $data['cash_received'] = $data['payment_method'] === 'cash' ? (int) $data['cash_received'] : null;
        if ($data['payment_method'] === 'qris' && ! config('services.midtrans.server_key')) return back()->withErrors(['payment_method' => 'Midtrans belum dikonfigurasi. Tambahkan kunci Sandbox di .env.'])->withInput();
        $transaction = $checkout->complete($request->user(), $data);

        if ($transaction->payment_method === 'qris') {
            if ($transaction->midtrans_token) return redirect()->route('cashier.transactions.receipt', $transaction);
            try {
                $snapResult = $snap->createTransaction([
                    'transaction_details' => ['order_id' => $transaction->order_number, 'gross_amount' => $transaction->total_amount],
                    'item_details' => $transaction->items->map(fn ($item) => ['id' => (string) ($item->product_id ?? 'menu'), 'price' => $item->product_price, 'quantity' => $item->quantity, 'name' => mb_substr($item->product_name, 0, 50)])->all(),
                    'customer_details' => ['first_name' => $request->user()->name],
                    'enabled_payments' => ['qris'],
                    'callbacks' => ['finish' => route('cashier.transactions.receipt', $transaction)],
                ]);
                $transaction->update(['midtrans_token' => $snapResult->token]);
            } catch (\Throwable $exception) {
                report($exception);
                return redirect()->route('cashier.transactions.receipt', $transaction)->with('payment_error', 'Transaksi Snap belum dapat dibuat. Hubungi admin untuk memeriksa konfigurasi Midtrans.');
            }
        }
        return redirect()->route('cashier.transactions.receipt', $transaction);
    }

    public function transactions(Request $request): View
    {
        return view('cashier.transactions.index', ['transactions' => Transaction::query()->where('user_id', $request->user()->id)->withCount('items')->latest('created_at')->paginate(15)]);
    }

    public function receipt(Request $request, Transaction $transaction): View
    {
        abort_unless($request->user()->isAdmin() || $transaction->user_id === $request->user()->id, 403);
        return view('cashier.receipt', ['transaction' => $transaction->load(['cashier:id,name', 'items']), 'restaurant' => RestaurantSetting::query()->first(), 'midtransClientKey' => config('services.midtrans.client_key')]);
    }
}
