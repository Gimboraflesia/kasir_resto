<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()->with('category')->orderBy('name');

        if ($search = trim((string) $request->query('q'))) {
            $products->where('name', 'like', '%'.$search.'%');
        }

        return view('admin.products.index', [
            'products' => $products->paginate(12)->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['image_path'] = $request->file('image')?->store('products', 'public');
        Product::query()->create($data);

        return back()->with('status', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('products', 'public');
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $newPath;
        }

        $product->update($data);

        return back()->with('status', 'Menu berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('status', 'Menu berhasil dinonaktifkan dari daftar. Riwayat akan tetap terjaga.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'cost_price' => ['required', 'integer', 'min:1', 'max:999999999'],
            'profit_percent' => ['required', 'integer', 'min:0', 'max:1000'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:999999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'category_id.required' => 'Kategori menu wajib dipilih.',
            'category_id.integer' => 'Pilihan kategori tidak valid. Silakan pilih kembali.',
            'category_id.exists' => 'Kategori yang dipilih tidak ditemukan.',
            'name.required' => 'Nama menu wajib diisi.',
            'cost_price.required' => 'Harga modal wajib diisi.',
            'cost_price.integer' => 'Harga modal harus angka bulat tanpa titik atau koma.',
            'cost_price.min' => 'Harga modal minimal Rp 1.',
            'cost_price.max' => 'Harga modal maksimal Rp 999.999.999.',
            'profit_percent.required' => 'Persentase keuntungan wajib diisi.',
            'profit_percent.integer' => 'Persentase keuntungan harus berupa angka bulat.',
            'profit_percent.min' => 'Persentase keuntungan tidak boleh kurang dari 0%.',
            'profit_percent.max' => 'Persentase keuntungan maksimal 1000%.',
            'stock_quantity.required' => 'Jumlah stok awal wajib diisi.',
            'stock_quantity.integer' => 'Jumlah stok harus berupa angka bulat.',
            'stock_quantity.min' => 'Jumlah stok tidak boleh kurang dari 0.',
            'stock_quantity.max' => 'Jumlah stok maksimal 999.999.',
            'image.image' => 'File harus berupa gambar.',
            'image.mimes' => 'Gambar harus berformat JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar maksimal 3 MB.',
        ]);

        $profit = intdiv(($data['cost_price'] * $data['profit_percent']) + 50, 100);
        $data['price'] = $data['cost_price'] + $profit;

        if ($data['price'] > 999999999) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'profit_percent' => 'Harga jual hasil perhitungan melebihi batas Rp 999.999.999. Turunkan modal atau persentase keuntungan.',
            ]);
        }

        return $data;
    }
}
