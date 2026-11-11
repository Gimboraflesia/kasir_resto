<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Kasir' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/cashier.js'])
</head>
<body class="pos-page">
    <header class="pos-topbar">
        <a class="pos-brand" href="{{ route('cashier.index') }}">@if($restaurantLogoPath)<img class="brand-logo" src="{{ Storage::disk('public')->url($restaurantLogoPath) }}" alt="">@else<span class="brand-mark">R</span>@endif<span>{{ $restaurantName }}</span></a>
        <div class="pos-session"><a class="pos-history-link" href="{{ route('cashier.index') }}">Kasir</a><a class="pos-history-link" href="{{ route('cashier.reports.index') }}">Laporan</a><a class="pos-history-link" href="{{ route('cashier.transactions.index') }}">Riwayat</a><span class="cashier-name">{{ auth()->user()->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="text-button">Keluar</button></form></div>
    </header>
    <main class="pos-main">
        {{ $slot }}
    </main>
</body>
</html>
