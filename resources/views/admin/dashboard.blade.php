<x-layouts.app title="Ringkasan">
    @vite('resources/js/dashboard.js')
    <section class="dashboard-content">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="period-filter">
            <div><label for="start-date">Periode laporan</label><div class="period-inputs"><input id="start-date" type="date" name="start_date" value="{{ $filters['start_date'] }}" required><span>sampai</span><input type="date" name="end_date" value="{{ $filters['end_date'] }}" required></div></div>
            <button class="secondary-button" type="submit">Terapkan</button>
        </form>

        <section class="metric-strip" aria-label="Ringkasan penjualan">
            <div class="metric">
                <span>Pendapatan</span>
                <strong>Rp {{ number_format($revenue, 0, ',', '.') }}</strong>
                <small @class(['metric-comparison', 'trend-positive' => $revenueChange !== null && $revenueChange > 0, 'trend-negative' => $revenueChange !== null && $revenueChange < 0])>
                    @if ($revenueChange === null)
                        {{ $revenue > 0 ? 'Ada pendapatan baru' : 'Belum ada pendapatan' }}
                    @else
                        {{ $revenueChange > 0 ? '+' : '' }}{{ number_format($revenueChange, 1, ',', '.') }}% dari periode sebelumnya
                    @endif
                </small>
            </div>
            <div class="metric"><span>Transaksi dibayar</span><strong>{{ number_format($transactionCount, 0, ',', '.') }}</strong></div>
            <div class="metric"><span>Laba kotor</span><strong>Rp {{ number_format($grossProfit, 0, ',', '.') }}</strong>@if ($grossMarginPercent !== null)<small class="metric-profit-line">Margin {{ number_format($grossMarginPercent, 1, ',', '.') }}%</small>@endif</div>
            <div class="metric"><span>Produk terjual</span><strong>{{ number_format($unitsSold, 0, ',', '.') }}</strong></div>
        </section>
        @if ($uncostedUnits > 0)
            <p class="profit-coverage-note">Laba belum mencakup {{ number_format($uncostedUnits, 0, ',', '.') }} menu terjual (Rp {{ number_format($uncostedRevenue, 0, ',', '.') }} penjualan) karena modal transaksi lama belum tercatat.</p>
        @endif

        <div class="dashboard-grid">
            <section class="dashboard-panel sales-panel">
                <div class="dashboard-panel-heading"><div><h2>Performa penjualan</h2><p>Grafik transaksi berstatus dibayar.</p></div><div class="chart-switch" role="group" aria-label="Periode grafik"><button type="button" class="is-active" data-chart-period="daily" aria-pressed="true">Harian</button><button type="button" data-chart-period="weekly" aria-pressed="false">Mingguan</button><button type="button" data-chart-period="monthly" aria-pressed="false">Bulanan</button></div></div>
                <div class="chart-area"><canvas id="sales-chart" aria-label="Grafik penjualan" role="img"></canvas></div>
                <script type="application/json" id="sales-chart-data">@json(['daily' => $dailyChart, 'weekly' => $weeklyChart, 'monthly' => $monthlyChart])</script>
            </section>

            <section class="dashboard-panel top-products-panel">
                <div class="dashboard-panel-heading"><div><h2>Menu terlaris</h2><p>Paling banyak dipesan pada periode ini.</p></div></div>
                <ol class="top-products-list">
                    @forelse ($topProducts as $index => $product)
                        <li><span class="product-rank">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><span class="top-product-name">{{ $product->product_name }}</span><strong>{{ number_format($product->units_sold, 0, ',', '.') }} <small>terjual</small></strong></li>
                    @empty
                        <li class="top-products-empty">Belum ada transaksi dibayar pada periode ini.</li>
                    @endforelse
                </ol>
            </section>
        </div>
    </section>
</x-layouts.app>
