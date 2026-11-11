<x-layouts.guest title="Masuk">
    <section class="login-panel" aria-labelledby="login-title">
        <header class="login-brand">
            @if ($restaurantLogoPath)
                <img class="login-logo" src="{{ Storage::disk('public')->url($restaurantLogoPath) }}" alt="Logo {{ $restaurantName }}">
            @else
                <span class="login-logo-fallback" aria-hidden="true">{{ mb_strtoupper(mb_substr($restaurantName, 0, 1)) }}</span>
            @endif
            <div><p class="eyebrow">{{ $restaurantName }}</p><span class="login-access-label">Sistem kasir</span></div>
        </header>
        <div class="login-heading"><h1 id="login-title">Masuk ke akun</h1><p class="login-intro">Gunakan email dan password yang diberikan administrator.</p></div>
        <form method="POST" action="{{ route('login.store') }}" class="login-form">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" placeholder="nama@restoran.com" required autofocus>
            @error('email')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            <label for="password">Password</label>
            <div class="password-field"><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Masukkan password" required><button class="password-toggle" type="button" data-password-toggle aria-label="Tampilkan password">Lihat</button></div>
            @error('password')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            <label class="remember-row"><input type="checkbox" name="remember" value="1"> <span>Ingat saya di perangkat ini</span></label>
            <button class="primary-button" type="submit">Masuk</button>
        </form>
        <footer class="login-footnote">Akses hanya untuk akun yang dikelola administrator.</footer>
    </section>
</x-layouts.guest>
