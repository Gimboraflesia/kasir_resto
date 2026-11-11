<x-layouts.app title="Transaksi">
    <section class="content-section">
        <div class="section-heading transaction-heading"><div><h2>Riwayat transaksi</h2><p>Semua pembayaran dan status pesanan tercatat di sini.</p></div><form method="POST" action="{{ route('admin.transactions.cancel-today') }}" class="transaction-admin-actions" onsubmit="return confirm('Batalkan semua {{ $todayPaidCount }} transaksi dibayar hari ini? Transaksi tetap tersimpan sebagai riwayat dan stok menu yang tercatat akan dikembalikan.');">@csrf<button class="secondary-button" type="submit" @disabled($todayPaidCount === 0)>Batalkan semua penjualan hari ini <span>({{ number_format($todayPaidCount, 0, ',', '.') }})</span></button></form></div>
        <p class="transaction-cancel-note">Pembatalan hanya mengeluarkan transaksi dari laporan. Riwayat tetap tersimpan dan stok menu yang tercatat dikembalikan.</p>
        <form method="GET" action="{{ route('admin.transactions.index') }}" class="history-filters">
            <label class="history-search">Cari nomor<input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="TRX-…"></label>
            <label>Status<select name="status"><option value="">Semua status</option>@foreach(['paid' => 'Dibayar', 'pending' => 'Menunggu', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Metode<select name="method"><option value="">Semua metode</option><option value="cash" @selected(($filters['method'] ?? '') === 'cash')>Tunai</option><option value="qris" @selected(($filters['method'] ?? '') === 'qris')>QRIS</option></select></label>
            <label>Dari<input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"></label>
            <label>Sampai<input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"></label>
            <button class="secondary-button">Filter</button>
        </form>
        <div class="table-wrap"><table><thead><tr><th>Nomor transaksi</th><th>Tanggal</th><th>Kasir</th><th>Metode</th><th>Status</th><th>Total</th><th>Aksi</th></tr></thead><tbody>
            @forelse($transactions as $transaction)
                <tr><td><strong class="transaction-number">{{ $transaction->order_number }}</strong></td><td>{{ $transaction->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td><td>{{ $transaction->cashier->name }}</td><td>{{ $transaction->payment_method === 'cash' ? 'Tunai' : 'QRIS' }}</td><td><span class="status-pill status-{{ $transaction->payment_status }}">{{ ['paid' => 'Dibayar', 'pending' => 'Menunggu', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan'][$transaction->payment_status] ?? $transaction->payment_status }}</span></td><td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td><td class="transaction-actions"><a class="text-link" href="{{ route('admin.transactions.show', $transaction) }}">Detail</a>@if($transaction->payment_status === 'paid' && $transaction->paid_at?->timezone(config('app.timezone'))->isToday())<form method="POST" action="{{ route('admin.transactions.cancel', $transaction) }}" onsubmit="return confirm('Batalkan transaksi {{ $transaction->order_number }}? Riwayat tetap tersimpan dan stok menu yang tercatat dikembalikan.');">@csrf<button class="text-button transaction-cancel-button" type="submit">Batalkan</button></form>@endif</td></tr>
            @empty
                <tr><td colspan="7" class="empty-state">Belum ada transaksi untuk filter ini.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $transactions->links() }}</div>
    </section>
</x-layouts.app>
