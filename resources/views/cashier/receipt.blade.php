<x-layouts.cashier title="Struk Transaksi">
    <section class="receipt-page">
        <header class="receipt-actions"><a class="back-link" href="{{ route('cashier.index') }}">← Kembali ke kasir</a><button type="button" class="secondary-button" onclick="window.print()">Cetak struk</button></header>
        @if (session('payment_error'))<div class="notice-error" role="alert">{{ session('payment_error') }}</div>@endif
        <article class="receipt-card">
            <div class="receipt-brand">@if($restaurant?->logo_path)<img class="receipt-logo" src="{{ Storage::disk('public')->url($restaurant->logo_path) }}" alt="">@else<span class="brand-mark">R</span>@endif<p class="eyebrow">{{ $restaurant?->restaurant_name ?? config('app.name') }}</p>@if($restaurant?->address)<p class="receipt-address">{{ $restaurant->address }}</p>@endif @if($restaurant?->phone)<p class="receipt-address">{{ $restaurant->phone }}</p>@endif<h1>{{ $transaction->payment_status === 'paid' ? 'Pembayaran berhasil' : 'Menunggu pembayaran' }}</h1><p>{{ $transaction->payment_status === 'paid' ? 'Terima kasih atas pesanan Anda.' : 'Selesaikan pembayaran QRIS untuk mengonfirmasi pesanan.' }}</p></div>
            <div class="receipt-meta"><div><span>Nomor transaksi</span><strong>{{ $transaction->order_number }}</strong></div><div><span>Waktu</span><strong>{{ ($transaction->paid_at ?? $transaction->created_at)?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</strong></div><div><span>Kasir</span><strong>{{ $transaction->cashier->name }}</strong></div><div><span>Metode pembayaran</span><strong>{{ $transaction->payment_method === 'cash' ? 'Tunai' : ($transaction->payment_method === 'qris' && $transaction->midtrans_token ? 'QRIS Midtrans' : 'QRIS manual') }}</strong></div></div>
            @if($transaction->payment_status !== 'paid' && $transaction->payment_method === 'qris' && $transaction->midtrans_token && $midtransClientKey)
                <section class="receipt-payment"><h2>Bayar dengan QRIS</h2><p>Scan QRIS yang tampil di jendela pembayaran. Status lunas diperbarui setelah notifikasi Midtrans diterima.</p><button class="checkout-button" type="button" id="pay-button">Tampilkan QR pembayaran</button></section>
            @endif
            <div class="receipt-items"><h2>Rincian pesanan</h2>@foreach ($transaction->items as $item)<div class="receipt-item"><div><strong>{{ $item->product_name }}</strong><span>{{ $item->quantity }} × Rp {{ number_format($item->product_price, 0, ',', '.') }}</span></div><strong>Rp {{ number_format($item->line_total, 0, ',', '.') }}</strong></div>@endforeach</div>
            <div class="receipt-payment"><div><span>Total</span><strong>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</strong></div>@if($transaction->payment_method === 'cash')<div><span>Uang diterima</span><strong>Rp {{ number_format($transaction->cash_received, 0, ',', '.') }}</strong></div><div class="receipt-change"><span>Kembalian</span><strong>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</strong></div>@endif</div>
            <footer class="receipt-thanks">{{ $restaurant?->receipt_note ?: 'Semoga harimu menyenangkan.' }}</footer>
        </article>
    </section>
    @if($transaction->midtrans_token && $midtransClientKey)
        <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ $midtransClientKey }}"></script>
        <script>document.getElementById('pay-button')?.addEventListener('click', () => window.snap.pay(@js($transaction->midtrans_token), { onSuccess: () => window.location.reload(), onPending: () => window.location.reload(), onError: () => window.location.reload(), onClose: () => window.location.reload() }));</script>
    @endif
</x-layouts.cashier>

