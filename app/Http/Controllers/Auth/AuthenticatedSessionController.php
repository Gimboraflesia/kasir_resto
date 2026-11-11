<?php

namespace App\Http\Controllers\Auth;

use App\Models\RestaurantSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        $settings = RestaurantSetting::query()->first();
        return view('auth.login', [
            'restaurantName' => $settings?->restaurant_name ?? config('app.name'),
            'restaurantLogoPath' => $settings?->logo_path,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']], [
            'email.required' => 'Email wajib diisi.', 'email.email' => 'Masukkan alamat email yang valid.', 'password.required' => 'Password wajib diisi.',
        ]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) throw ValidationException::withMessages(['email' => 'Email atau password tidak sesuai.']);
        if (! Auth::user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Akun ini sedang dinonaktifkan. Hubungi administrator.']);
        }
        $request->session()->regenerate();
        return Auth::user()->isAdmin() ? redirect()->intended(route('admin.dashboard')) : redirect()->intended(route('cashier.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
