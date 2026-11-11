<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 32px; }
        body { color: #333a34; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin: 0; color: #27392d; font-size: 20px; }
        .subheading { margin: 5px 0 18px; color: #737a73; font-size: 10px; }
        .summary { width: 100%; margin-bottom: 18px; border-collapse: collapse; }
        .summary td { width: 20%; padding: 10px 8px; border: 1px solid #e5e9e3; background: #f8faf7; }
        .summary span { display: block; margin-bottom: 5px; color: #737a73; font-size: 8px; }
        .summary strong { color: #2f4937; font-size: 13px; }
        h2 { margin: 15px 0 7px; color: #354238; font-size: 11px; }
        table.data { width: 100%; border-collapse: collapse; }
        .data th { padding: 7px 6px; background: #304b39; color: #fff; font-size: 8px; text-align: left; }
        .data td { padding: 6px; border-bottom: 1px solid #e9ece7; vertical-align: top; }
        .numeric { text-align: right; white-space: nowrap; }
        .subtable { width: 100%; margin: 0; border-collapse: collapse; }
        .subtable td { padding: 1px 0; border: 0; }
        .footer { margin-top: 14px; color: #777d76; font-size: 8px; }
    </style>
</head>
<body>
    <h1>Laporan Penjualan</h1>
    <p class="subheading">{{ config('app.name') }} · {{ $start->translatedFormat('d F Y') }} — {{ $end->translatedFormat('d F Y') }}</p>

    <table class="summary"><tr>
        <td><span>Pendapatan dibayar</span><strong>Rp {{ number_format($revenue, 0, ',', '.') }}</strong></td>
        <td><span>Laba kotor tercatat</span><strong>Rp {{ number_format($grossProfit, 0, ',', '.') }}</strong></td>
        <td><span>Margin kotor</span><strong>@if ($grossMarginPercent !== null){{ number_format($grossMarginPercent, 1, ',', '.') }}%@else—@endif</strong></td>
        <td><span>Transaksi</span><strong>{{ number_format($transactionCount, 0, ',', '.') }}</strong></td>
        <td><span>Produk terjual</span><strong>{{ number_format($unitsSold, 0, ',', '.') }}</strong></td>
    </tr></table>

    <h2>Rekap metode pembayaran</h2>
    <table class="data"><thead><tr><th>Metode</th><th>Transaksi</th><th class="numeric">Pendapatan</th></tr></thead><tbody>
        @forelse ($methods as $method)
            <tr><td>{{ $method->payment_method === 'cash' ? 'Tunai' : 'QRIS' }}</td><td>{{ number_format($method->transaction_count, 0, ',', '.') }}</td><td class="numeric">Rp {{ number_format($method->revenue, 0, ',', '.') }}</td></tr>
        @empty
            <tr><td colspan="3">Belum ada pembayaran pada periode ini.</td></tr>
        @endforelse
    </tbody></table>

    <h2>Menu terlaris</h2>
    <table class="data"><thead><tr><th>Menu</th><th>Terjual</th><th class="numeric">Pendapatan</th></tr></thead><tbody>
        @forelse ($topProducts as $product)
            <tr><td>{{ $product->product_name }}</td><td>{{ number_format($product->units_sold, 0, ',', '.') }}</td><td class="numeric">Rp {{ number_format($product->revenue, 0, ',', '.') }}</td></tr>
        @empty
            <tr><td colspan="3">Belum ada menu terjual pada periode ini.</td></tr>
        @endforelse
    </tbody></table>

    @if ($uncostedUnits > 0)<p class="footer">Laba kotor belum mencakup {{ number_format($uncostedUnits, 0, ',', '.') }} menu terjual karena modal transaksi lama belum tercatat.</p>@endif

    <h2>Transaksi dibayar</h2>
    <table class="data"><thead><tr><th>Nomor</th><th>Waktu</th><th>Kasir</th><th>Metode</th><th>Rincian menu</th><th class="numeric">Total jual</th><th class="numeric">Modal tercatat</th><th class="numeric">Laba kotor</th></tr></thead><tbody>
        @forelse ($transactions as $transaction)
            @php
                $costedItems = $transaction->items->filter(fn ($item) => $item->product_cost !== null);
                $transactionCost = (int) $costedItems->sum(fn ($item) => $item->product_cost * $item->quantity);
                $transactionProfit = (int) $costedItems->sum(fn ($item) => $item->line_total - ($item->product_cost * $item->quantity));
            @endphp
            <tr>
                <td>{{ $transaction->order_number }}</td>
                <td>{{ $transaction->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>{{ $transaction->cashier->name }}</td>
                <td>{{ $transaction->payment_method === 'cash' ? 'Tunai' : 'QRIS' }}</td>
                <td><table class="subtable">@foreach ($transaction->items as $item)<tr><td>{{ $item->product_name }} × {{ $item->quantity }}</td></tr>@endforeach</table></td>
                <td class="numeric">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                <td class="numeric">Rp {{ number_format($transactionCost, 0, ',', '.') }}</td>
                <td class="numeric">Rp {{ number_format($transactionProfit, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="8">Belum ada transaksi dibayar pada periode ini.</td></tr>
        @endforelse
    </tbody></table>
    <p class="footer">Laporan ini hanya mencakup transaksi yang sudah dibayar. Laba kotor menggunakan snapshot modal yang tersedia saat transaksi.</p>
</body>
</html>
