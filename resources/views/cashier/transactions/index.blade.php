<x-layouts.cashier title="Riwayat transaksi">
    <section class="cashier-history" aria-labelledby="cashier-history-title">
        <div class="pos-heading">
            <div><p class="eyebrow">AKTIVITAS KASIR</p><h1 id="cashier-history-title">Riwayat transaksi</h1><p class="pos-date">Transaksi yang dibuat dari akun Anda.</p></div>
            <a class="checkout-button cashier-new-order" href="{{ route('cashier.index') }}"><span>Buat pesanan</span><span aria-hidden="true">→</span></a>
        </div>

        <div class="cashier-history-table-wrap">
            <table class="cashier-history-table">
                <thead><tr><th>Nomor transaksi</th><th>Waktu</th><th>Item</th><th>Metode</th><th>Status</th><th>Total</th><th></th></tr></thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td><strong class="transaction-number">{{ $transaction->order_number }}</strong></td>
                            <td>{{ $transaction->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                            <td>{{ $transaction->items_count }}</td>
                            <td>{{ $transaction->payment_method === 'cash' ? 'Tunai' : 'QRIS' }}</td>
                            <td><span class="status-pill status-{{ $transaction->payment_status }}">{{ ['paid' => 'Dibayar', 'pending' => 'Menunggu', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan'][$transaction->payment_status] ?? $transaction->payment_status }}</span></td>
                            <td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                            <td><a class="text-link" href="{{ route('cashier.transactions.receipt', $transaction) }}">Struk →</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">Belum ada transaksi dari akun ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $transactions->links() }}</div>
    </section>
</x-layouts.cashier>
