<?php

namespace App\Providers;

use App\Models\RestaurantSetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layouts.*', function ($view): void {
            $settings = RestaurantSetting::query()->first();
            $view->with('restaurantName', $settings?->restaurant_name ?? config('app.name'));
            $view->with('restaurantLogoPath', $settings?->logo_path);
        });
    }
}
