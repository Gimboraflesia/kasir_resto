<x-layouts.app title="Menu">
    @vite('resources/js/product-form.js')
    <section class="content-section">
        <div class="section-heading">
            <div><h2>Menu &amp; stok</h2><p>Atur stok, harga modal, dan persentase keuntungan per menu.</p></div>
        </div>

        @if ($categories->isEmpty())
            <div class="empty-callout">Buat kategori terlebih dahulu sebelum menambahkan menu. <a href="{{ route('admin.categories.index') }}">Kelola kategori</a></div>
        @else
            <details class="create-disclosure" open>
                <summary>Tambah menu</summary>
                <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="product-form" data-profit-calculator>
                    @csrf
                    <label>Nama menu<input name="name" value="{{ old('name') }}" maxlength="150" required></label>
                    <label>Kategori
                        <select name="category_id" required>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Harga modal (Rp)<input name="cost_price" type="number" inputmode="numeric" min="1" max="999999999" step="1" value="{{ old('cost_price') }}" data-cost-input required><small>Angka bulat tanpa titik atau koma.</small></label>
                    <label>Target keuntungan (%)<input name="profit_percent" type="number" inputmode="numeric" min="0" max="1000" step="1" value="{{ old('profit_percent') }}" data-profit-input required><small>Dihitung dari harga modal.</small></label>
                    <label>Stok awal<input name="stock_quantity" type="number" inputmode="numeric" min="0" max="999999" step="1" value="{{ old('stock_quantity') }}" required></label>
                    <label>Foto menu<input name="image" type="file" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, atau WebP; maksimal 3 MB.</small></label>
                    <div class="price-preview" aria-live="polite"><span>Harga jual yang disarankan</span><strong data-price-preview>Rp 0</strong><small>Laba kotor per porsi: <b data-profit-preview>Rp 0</b></small></div>
                    <label class="check-label"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')> Tersedia untuk dijual</label>
                    <button class="primary-button compact-button">Simpan menu</button>
                </form>
            </details>
        @endif

        <form method="GET" class="search-form">
            <label for="menu-search">Cari menu</label><input id="menu-search" name="q" value="{{ request('q') }}" placeholder="Ketik nama menu"><button class="secondary-button">Cari</button>
            @if (request('q'))<a href="{{ route('admin.products.index') }}">Bersihkan</a>@endif
        </form>

        <div class="table-wrap"><table>
            <thead><tr><th>Menu</th><th>Kategori</th><th>Stok</th><th>Modal</th><th>Harga jual</th><th>Untung / porsi</th><th>Target untung</th><th>Status</th><th class="actions-column">Aksi</th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td><div class="product-cell">@if ($product->image_path)<img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="">@else<span class="photo-placeholder">—</span>@endif<div><strong>{{ $product->name }}</strong><small>#{{ $product->id }}</small></div></div></td>
                        <td>{{ $product->category->name }}</td>
                        <td>@if ($product->stock_quantity === null)<span class="stock-untracked">Belum diatur</span>@elseif ($product->stock_quantity === 0)<span class="stock-empty">Habis</span>@else{{ number_format($product->stock_quantity, 0, ',', '.') }}@endif</td>
                        <td>@if ($product->cost_price === null)Belum diatur @elseRp {{ number_format($product->cost_price, 0, ',', '.') }}@endif</td>
                        <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                        <td>@if ($product->cost_price === null)Belum diatur @elseRp {{ number_format($product->price - $product->cost_price, 0, ',', '.') }}@endif</td>
                        <td>@if ($product->profit_percent === null)Belum diatur @else{{ $product->profit_percent }}%@endif</td>
                        <td><span class="status-pill {{ $product->is_active ? 'status-active' : 'status-inactive' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                        <td class="row-actions">
                            <details class="edit-disclosure">
                                <summary>Edit</summary>
                                <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="product-form edit-form" data-profit-calculator>
                                    @csrf @method('PUT')
                                    <label>Nama menu<input name="name" value="{{ $product->name }}" required maxlength="150"></label>
                                    <label>Kategori<select name="category_id" required>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                                    <label>Harga modal (Rp)<input name="cost_price" type="number" inputmode="numeric" min="1" max="999999999" step="1" value="{{ $product->cost_price }}" data-cost-input required></label>
                                    <label>Target keuntungan (%)<input name="profit_percent" type="number" inputmode="numeric" min="0" max="1000" step="1" value="{{ $product->profit_percent }}" data-profit-input required></label>
                                    <label>Stok<input name="stock_quantity" type="number" inputmode="numeric" min="0" max="999999" step="1" value="{{ $product->stock_quantity }}" required><small>Masukkan hitungan stok fisik terbaru.</small></label>
                                    <label>Ganti foto<input name="image" type="file" accept="image/jpeg,image/png,image/webp"></label>
                                    <div class="price-preview"><span>Harga jual yang disarankan</span><strong data-price-preview>Rp 0</strong><small>Laba kotor per porsi: <b data-profit-preview>Rp 0</b></small></div>
                                    <label class="check-label"><input type="checkbox" name="is_active" value="1" @checked($product->is_active)> Tersedia untuk dijual</label>
                                    <button class="primary-button compact-button">Simpan perubahan</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus menu ini dari daftar aktif? Riwayat akan tetap dipertahankan.')">@csrf @method('DELETE')<button class="text-button danger-button">Hapus</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="empty-state">Tidak ada menu yang sesuai pencarian.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination-wrap">{{ $products->links() }}</div>
    </section>
</x-layouts.app>
