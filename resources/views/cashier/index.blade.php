<x-layouts.cashier title="Kasir">
    <div class="pos-heading">
        <div><p class="eyebrow">LAYANAN MEJA</p><h1>Buat pesanan</h1><p class="pos-date">{{ now()->translatedFormat('l, d F Y') }}</p></div>
        <div class="pos-open-indicator"><span></span> Kasir siap</div>
    </div>

    <section class="cashier-sales-summary" aria-labelledby="cashier-sales-title">
        <div class="cashier-sales-heading">
            <div><h2 id="cashier-sales-title">Ringkasan penjualan</h2><p>Transaksi dibayar yang dibuat oleh akun kasir ini.</p></div>
            <form method="GET" action="{{ route('cashier.index') }}" class="cashier-sales-filter">
                <label>Dari<input type="date" name="start_date" value="{{ $salesFilters['start_date'] }}" required></label>
                <label>Sampai<input type="date" name="end_date" value="{{ $salesFilters['end_date'] }}" required></label>
                <button type="submit">Tampilkan</button>
            </form>
        </div>
        <div class="metric-strip cashier-sales-metrics" aria-label="Ringkasan penjualan kasir">
            <div class="metric"><span>Pendapatan</span><strong>Rp {{ number_format($salesSummary['revenue'], 0, ',', '.') }}</strong></div>
            <div class="metric"><span>Transaksi dibayar</span><strong>{{ number_format($salesSummary['transactionCount'], 0, ',', '.') }}</strong></div>
            <div class="metric"><span>Laba kotor</span><strong>Rp {{ number_format($salesSummary['grossProfit'], 0, ',', '.') }}</strong>@if ($salesSummary['grossMarginPercent'] !== null)<small class="metric-profit-line">Margin {{ number_format($salesSummary['grossMarginPercent'], 1, ',', '.') }}%</small>@endif</div>
            <div class="metric"><span>Menu terjual</span><strong>{{ number_format($salesSummary['unitsSold'], 0, ',', '.') }}</strong></div>
        </div>
        @if ($salesSummary['uncostedUnits'] > 0)
            <p class="profit-coverage-note">Laba belum mencakup {{ number_format($salesSummary['uncostedUnits'], 0, ',', '.') }} menu terjual karena modal transaksi lama belum tercatat.</p>
        @endif
    </section>

    @if ($errors->any())
        <div class="notice-error pos-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="pos-workspace">
        <section class="menu-browser" aria-labelledby="menu-title">
            <div class="menu-toolbar">
                <div><h2 id="menu-title">Pilih menu</h2><p>{{ $products->count() }} menu tersedia</p></div>
                <label class="pos-search"><span class="sr-only">Cari menu</span><span aria-hidden="true">⌕</span><input type="search" placeholder="Cari menu" data-menu-search autocomplete="off"></label>
            </div>
            <nav class="category-filters" aria-label="Filter kategori">
                <button type="button" class="category-chip is-selected" data-category-filter="all">Semua menu</button>
                @foreach ($categories as $category)
                    <button type="button" class="category-chip" data-category-filter="{{ $category->id }}">{{ $category->name }}</button>
                @endforeach
            </nav>
            <div class="menu-grid" data-menu-grid>
                @forelse ($products as $product)
                    <button type="button" class="menu-item" data-product-add data-product-id="{{ $product->id }}" data-category-id="{{ $product->category_id }}" data-product-name="{{ $product->name }}" data-product-price="{{ $product->price }}" data-product-stock="{{ $product->stock_quantity ?? '' }}" @disabled($product->stock_quantity !== null && $product->stock_quantity === 0) aria-label="Tambah {{ $product->name }} ke pesanan">
                        <span class="menu-photo">
                            @if ($product->image_path)
                                <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="" loading="lazy">
                            @else
                                <span class="menu-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</span>
                            @endif
                            <span class="menu-add-mark" aria-hidden="true">+</span>
                            <span class="menu-quantity" data-cart-count="{{ $product->id }}" hidden></span>
                        </span>
                        <span class="menu-item-copy"><span class="menu-item-name">{{ $product->name }}</span><span class="menu-item-category">{{ $product->category->name }}</span><span class="menu-stock">@if($product->stock_quantity === null) Stok belum dicatat @elseif($product->stock_quantity === 0) Stok habis @else Stok {{ number_format($product->stock_quantity, 0, ',', '.') }} @endif</span><strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong></span>
                    </button>
                @empty
                    <div class="pos-empty"><span class="empty-icon">＋</span><h3>Menu belum tersedia</h3><p>Menu aktif yang dibuat admin akan muncul di sini.</p></div>
                @endforelse
            </div>
            <p class="no-results" data-no-results hidden>Menu tidak ditemukan. Coba kata kunci lain.</p>
        </section>

        <aside class="order-panel" aria-labelledby="order-title">
            <form method="POST" action="{{ route('cashier.checkout') }}" data-checkout-form>
                @csrf
                <input type="hidden" name="checkout_key" value="{{ old('checkout_key', $checkoutKey) }}">
                <div class="order-heading"><div><p class="eyebrow">PESANAN AKTIF</p><h2 id="order-title">Ringkasan</h2></div><span class="order-count" data-order-count>0 item</span></div>
                <div class="order-lines" data-order-lines>
                    <div class="cart-empty"><span class="cart-empty-mark">＋</span><strong>Belum ada menu</strong><span>Pilih menu untuk memulai pesanan.</span></div>
                </div>
                <button class="clear-order" type="button" data-clear-cart disabled>Kosongkan pesanan</button>

                <div class="order-totals">
                    <div><span>Subtotal</span><strong data-subtotal>Rp 0</strong></div>
                    <div class="grand-total"><span>Total pembayaran</span><strong data-grand-total>Rp 0</strong></div>
                </div>

                <section class="cash-payment" aria-labelledby="payment-heading">
                    <div class="payment-title"><span class="payment-icon" aria-hidden="true">Rp</span><div><h3 id="payment-heading">Metode pembayaran</h3><p>Pilih cara pelanggan membayar.</p></div></div>
                    <div class="payment-method-options" role="group" aria-label="Metode pembayaran">
                        <label class="payment-method-option"><input type="radio" name="payment_method" value="cash" checked data-payment-method><span><strong>Tunai</strong><small>Catat uang diterima</small></span></label>
                        <label class="payment-method-option"><input type="radio" name="payment_method" value="qris_manual" data-payment-method><span><strong>QRIS manual</strong><small>Kasir konfirmasi pembayaran</small></span></label>
                        <label class="payment-method-option"><input type="radio" name="payment_method" value="qris" data-payment-method><span><strong>QRIS Midtrans</strong><small>Snap Sandbox</small></span></label>
                    </div>
                    <div class="cash-fields" data-cash-fields>
                    <div class="payment-title payment-subtitle"><span class="payment-icon" aria-hidden="true">Rp</span><div><h3 id="cash-heading">Pembayaran tunai</h3><p>Masukkan uang yang diterima.</p></div></div>
                    <label class="cash-label" for="cash-received">Uang diterima</label>
                    <div class="cash-input-wrap"><span>Rp</span><input id="cash-received" name="cash_received" type="number" min="0" step="1" inputmode="numeric" placeholder="0" value="{{ old('cash_received') }}" data-cash-input required></div>
                    <div class="cash-presets" aria-label="Nominal cepat">
                        <button type="button" data-cash-preset="exact" disabled>Uang pas</button>
                        <button type="button" data-cash-preset="50000">50 rb</button>
                        <button type="button" data-cash-preset="100000">100 rb</button>
                        <button type="button" data-cash-preset="200000">200 rb</button>
                    </div>
                    <div class="change-row"><span>Kembalian</span><strong data-change-amount>Rp 0</strong></div>
                    </div>
                    <p class="qris-manual-note" data-qris-note hidden>Pilih QRIS manual untuk mencatat pembayaran tanpa verifikasi otomatis. Pilih QRIS Midtrans untuk membuat QR pembayaran Sandbox.</p>
                </section>

                <div class="checkout-hidden-items" data-checkout-items></div>
                <button class="checkout-button" type="submit" data-checkout-button disabled><span>Konfirmasi pembayaran tunai</span><span aria-hidden="true">→</span></button>
                <p class="checkout-hint" data-checkout-hint>Pilih menu untuk mulai membuat pesanan.</p>
            </form>
        </aside>
    </div>
</x-layouts.cashier>



