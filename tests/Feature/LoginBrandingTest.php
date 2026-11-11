<?php

namespace Tests\Feature;

use App\Models\RestaurantSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoginBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_restaurant_name_and_admin_uploaded_logo(): void
    {
        Storage::fake('public');
        $logoPath = UploadedFile::fake()->image('brand.png')->store('restaurant', 'public');
        RestaurantSetting::query()->create(['restaurant_name' => 'Bebek Kaka', 'logo_path' => $logoPath]);

        $this->get('/login')->assertOk()->assertSee('Bebek Kaka')->assertSee(Storage::disk('public')->url($logoPath), false)->assertSee('Masuk ke akun');
    }
}
