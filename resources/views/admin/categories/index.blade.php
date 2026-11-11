<x-layouts.app title="Kategori">
    <section class="content-section">
        <div class="section-heading"><div><h2>Kategori menu</h2><p>Atur kelompok menu agar mudah ditemukan kasir.</p></div></div>
        <form method="POST" action="{{ route('admin.categories.store') }}" class="inline-create">
            @csrf
            <label class="sr-only" for="category-name">Nama kategori baru</label>
            <input id="category-name" name="name" value="{{ old('name') }}" placeholder="Contoh: Makanan utama" required maxlength="100">
            <button class="primary-button compact-button">Tambah kategori</button>
        </form>
        <div class="table-wrap"><table><thead><tr><th>Nama kategori</th><th>Jumlah menu</th><th class="actions-column">Aksi</th></tr></thead><tbody>
            @forelse ($categories as $category)
                <tr>
                    <td><form id="category-{{ $category->id }}" method="POST" action="{{ route('admin.categories.update', $category) }}">@csrf @method('PUT')<input class="table-input" name="name" value="{{ $category->name }}" required maxlength="100" aria-label="Nama kategori"></form></td>
                    <td>{{ $category->products_count }}</td>
                    <td class="row-actions"><button form="category-{{ $category->id }}" class="text-button">Simpan</button><form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')">@csrf @method('DELETE')<button class="text-button danger-button">Hapus</button></form></td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty-state">Belum ada kategori. Tambahkan kategori pertama untuk mulai mengatur menu.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $categories->links() }}</div>
    </section>
</x-layouts.app>
