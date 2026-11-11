<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestaurantSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RestaurantSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = RestaurantSetting::query()->firstOrCreate([], ['restaurant_name' => config('app.name')]);

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = RestaurantSetting::query()->firstOrCreate([], ['restaurant_name' => config('app.name')]);
        $data = $request->validate([
            'restaurant_name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'receipt_note' => ['nullable', 'string', 'max:250'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);
        unset($data['logo']);

        $newLogo = $request->file('logo')?->store('restaurant', 'public');
        if ($newLogo) {
            $oldLogo = $settings->logo_path;
            $data['logo_path'] = $newLogo;
        }

        $settings->update($data);

        if (! empty($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        return back()->with('status', 'Pengaturan restoran berhasil disimpan.');
    }
}
