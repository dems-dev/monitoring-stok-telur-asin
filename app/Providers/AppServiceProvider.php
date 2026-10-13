<?php

namespace App\Providers;

use App\Models\Product;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Di produksi aplikasi selalu diakses lewat HTTPS; paksa skema https agar
        // URL aset, form, dan redirect tidak diblokir browser sebagai mixed content.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // View Composer untuk badge notifikasi lonceng di header
        View::composer('layouts.app', function ($view): void {
            $view->with([
                'lowStockCount' => Product::lowStock()->count(),
                'expiringSoonCount' => Product::expiringSoon(3)->count(),
            ]);
        });
    }
}
