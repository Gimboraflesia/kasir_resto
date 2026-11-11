<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_categories_and_cannot_remove_a_category_with_menu_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/categories')->assertOk();
        $this->get('/admin/products')->assertOk();
        $this->post('/admin/categories', ['name' => 'Makanan'])->assertRedirect();
        $category = Category::query()->firstOrFail();
        $this->assertSame('Makanan', $category->name);

        $this->put('/admin/categories/'.$category->id, ['name' => 'Menu utama'])->assertRedirect();
        $category->refresh();
        $this->assertSame('Menu utama', $category->name);

        Product::query()->create(['category_id' => $category->id, 'name' => 'Nasi goreng', 'price' => 25000]);
        $this->delete('/admin/categories/'.$category->id)->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_admin_can_create_edit_and_archive_a_product_with_a_safe_image_upload(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create(['name' => 'Minuman']);
        $this->actingAs($admin)->post('/admin/products', [
            'category_id' => $category->id,
            'name' => 'Es teh',
            'cost_price' => 8000,
            'profit_percent' => 25,
            'stock_quantity' => 20,
            'is_active' => 1,
            'image' => UploadedFile::fake()->image('es-teh.jpg'),
        ])->assertRedirect();

        $product = Product::query()->firstOrFail();
        $this->assertSame(10000, $product->price);
        $this->assertSame(8000, $product->cost_price);
        $this->assertSame(25, $product->profit_percent);
        $this->assertSame(20, $product->stock_quantity);
        Storage::disk('public')->assertExists($product->image_path);

        $this->put('/admin/products/'.$product->id, [
            'category_id' => $category->id,
            'name' => 'Es teh manis',
            'cost_price' => 9000,
            'profit_percent' => 20,
            'stock_quantity' => 15,
        ])->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Es teh manis',
            'price' => 10800,
            'cost_price' => 9000,
            'profit_percent' => 20,
            'stock_quantity' => 15,
            'is_active' => 0,
        ]);

        $this->delete('/admin/products/'.$product->id)->assertRedirect();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_cashier_cannot_manage_menu(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)->get('/admin/products')->assertForbidden();
    }

    public function test_product_cost_validation_explains_that_rupiah_must_be_a_whole_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create(['name' => 'Makanan']);

        $this->actingAs($admin)->post('/admin/products', [
            'category_id' => $category->id,
            'name' => 'Nasi bebek',
            'cost_price' => '15.000',
            'profit_percent' => 30,
            'stock_quantity' => 10,
        ])->assertSessionHasErrors([
            'cost_price' => 'Harga modal harus angka bulat tanpa titik atau koma.',
        ]);

        $this->assertDatabaseCount('products', 0);
    }
}
