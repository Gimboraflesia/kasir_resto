<x-layouts.app title="Akun Kasir">
    <section class="content-section">
        <div class="section-heading"><div><h2>Kelola akun kasir</h2><p>Buat dan atur akses kasir. Akun yang dinonaktifkan tidak dapat masuk.</p></div></div>
        <details class="create-disclosure">
            <summary>Tambah akun kasir</summary>
            <form method="POST" action="{{ route('admin.users.store') }}" class="product-form user-create-form">
                @csrf
                <label>Nama<input name="name" value="{{ old('name') }}" maxlength="120" required></label>
                <label>Email<input name="email" type="email" value="{{ old('email') }}" maxlength="255" required></label>
                <label>Password awal<input name="password" type="password" minlength="8" autocomplete="new-password" required><small>Minimal 8 karakter.</small></label>
                <label>Ulangi password<input name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></label>
                <button class="primary-button compact-button">Buat akun kasir</button>
            </form>
        </details>
        <div class="table-wrap"><table><thead><tr><th>Nama dan email</th><th>Status</th><th>Perbarui akun</th><th>Atur password</th></tr></thead><tbody>
            @forelse($cashiers as $cashier)
                <tr>
                    <td><form id="cashier-{{ $cashier->id }}" method="POST" action="{{ route('admin.users.update', $cashier) }}">@csrf @method('PUT')<div class="cashier-account-fields"><input name="name" value="{{ $cashier->name }}" required maxlength="120" aria-label="Nama kasir"><input name="email" type="email" value="{{ $cashier->email }}" required maxlength="255" aria-label="Email kasir"></div></form></td>
                    <td><label class="active-toggle"><input form="cashier-{{ $cashier->id }}" type="hidden" name="is_active" value="0"><input form="cashier-{{ $cashier->id }}" type="checkbox" name="is_active" value="1" @checked($cashier->is_active)> {{ $cashier->is_active ? 'Aktif' : 'Nonaktif' }}</label></td>
                    <td><button form="cashier-{{ $cashier->id }}" class="text-button">Simpan</button></td>
                    <td><details class="reset-password-disclosure"><summary>Ganti</summary><form method="POST" action="{{ route('admin.users.password', $cashier) }}" class="reset-password-form">@csrf @method('PUT')<label>Password baru<input type="password" name="password" minlength="8" autocomplete="new-password" required></label><label>Ulangi password<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label><button class="primary-button compact-button">Simpan password</button></form></details></td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty-state">Belum ada akun kasir. Tambahkan akun untuk mulai mengatur akses.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $cashiers->links() }}</div>
    </section>
</x-layouts.app>
