<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Beranda' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-page">
    <aside class="sidebar">
        <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">@if($restaurantLogoPath)<img class="brand-logo" src="{{ Storage::disk('public')->url($restaurantLogoPath) }}" alt="">@else<span class="brand-mark">R</span>@endif<span>{{ $restaurantName }}</span></a>
        <p class="nav-caption">OPERASIONAL</p>
        <nav class="main-nav" aria-label="Navigasi utama">
            <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Ringkasan</a>
            <a href="{{ route('admin.products.index') }}" @class(['active' => request()->routeIs('admin.products.*')])>Menu</a>
            <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])>Kategori</a>
            <a href="{{ route('admin.transactions.index') }}" @class(['active' => request()->routeIs('admin.transactions.*')])>Transaksi</a>
            <a href="{{ route('admin.reports.index') }}" @class(['active' => request()->routeIs('admin.reports.*')])>Laporan</a>
            <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.users.*')])>Akun kasir</a>
            <a href="{{ route('admin.settings.edit') }}" @class(['active' => request()->routeIs('admin.settings.*')])>Pengaturan</a>
        </nav>
        <div class="sidebar-bottom">
            <span class="user-label">{{ auth()->user()->name }}<small>Administrator</small></span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-button">Keluar</button></form>
        </div>
    </aside>
    <main class="main-content">
        <header class="page-header"><div><p class="eyebrow">{{ $restaurantName }}</p><h1>{{ $title ?? 'Beranda' }}</h1></div></header>
        @if (session('status'))<div class="notice-success" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="notice-error" role="alert">{{ $errors->first() }}</div>@endif
        {{ $slot }}
    </main>
</body>
</html>
