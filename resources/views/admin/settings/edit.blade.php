<x-layouts.app title="Pengaturan Restoran">
    <section class="content-section settings-section">
        <div class="section-heading"><div><h2>Identitas restoran</h2><p>Informasi ini digunakan pada navigasi dan struk transaksi.</p></div></div>
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="settings-form">
            @csrf @method('PUT')
            <label>Nama restoran<input name="restaurant_name" value="{{ old('restaurant_name', $settings->restaurant_name) }}" maxlength="150" required></label>
            <label>Alamat<input name="address" value="{{ old('address', $settings->address) }}" maxlength="255" placeholder="Alamat yang dicetak pada struk"></label>
            <label>Nomor telepon<input name="phone" type="tel" value="{{ old('phone', $settings->phone) }}" maxlength="30" placeholder="Nomor kontak restoran"></label>
            <label>Catatan pada struk<textarea name="receipt_note" maxlength="250" rows="3" placeholder="Contoh: Terima kasih, silakan datang kembali.">{{ old('receipt_note', $settings->receipt_note) }}</textarea></label>
            <label>Logo restoran<input name="logo" type="file" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, atau WebP; maksimal 3 MB.</small></label>
            @if($settings->logo_path)<div class="current-logo"><span>Logo saat ini</span><img src="{{ Storage::disk('public')->url($settings->logo_path) }}" alt="Logo restoran"></div>@endif
            <button class="primary-button compact-button">Simpan pengaturan</button>
        </form>
    </section>
</x-layouts.app>
