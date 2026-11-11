<?php

namespace Tests\Feature;

use App\Models\RestaurantSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_restaurant_identity_and_upload_a_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/settings')->assertOk();
        $this->put('/admin/settings', [
            'restaurant_name' => 'Bebek Kaka',
            'address' => 'Jakarta Selatan',
            'phone' => '021-555-0100',
            'receipt_note' => 'Terima kasih sudah makan di sini.',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();

        $settings = RestaurantSetting::query()->firstOrFail();
        $this->assertSame('Bebek Kaka', $settings->restaurant_name);
        $this->assertSame('Jakarta Selatan', $settings->address);
        Storage::disk('public')->assertExists($settings->logo_path);
        $this->get('/admin/settings')->assertOk()->assertSee('Bebek Kaka');
    }
}
