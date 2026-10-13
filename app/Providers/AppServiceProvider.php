<?php

namespace App\Providers;

use App\Models\Product;
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
        // View Composer untuk badge notifikasi lonceng di header
        View::composer('layouts.app', function ($view): void {
            $view->with([
                'lowStockCount' => Product::lowStock()->count(),
                'expiringSoonCount' => Product::expiringSoon(3)->count(),
            ]);
        });
    }
}
