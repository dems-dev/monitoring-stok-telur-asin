<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view): void {
            $view->with([
                'lowStockCount' => \App\Models\Product::lowStock()->count(),
                'expiringSoonCount' => \App\Models\Product::expiringSoon(3)->count(),
            ]);
        });
    }
}
