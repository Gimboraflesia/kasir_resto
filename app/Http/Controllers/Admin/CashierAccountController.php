<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashierAccountController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'cashiers' => User::query()->where('role', 'cashier')->orderBy('name')->paginate(12),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $data['role'] = 'cashier';
        $data['is_active'] = true;
        User::query()->create($data);

        return back()->with('status', 'Akun kasir berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'cashier', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'is_active' => ['required', 'boolean'],
        ]);
        $user->update($data);

        return back()->with('status', $user->is_active ? 'Akun kasir diperbarui dan aktif.' : 'Akun kasir dinonaktifkan. Riwayat transaksinya tetap tersimpan.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'cashier', 404);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $user->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', 'Password akun kasir berhasil diperbarui.');
    }
}
