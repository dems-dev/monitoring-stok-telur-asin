<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Master produk: otorisasi per aksi diatur di ProductController.
    Route::resource('products', ProductController::class)->except('show');

    // Pencatatan mutasi tertutup bagi OWNER yang hanya punya akses baca.
    Route::middleware('role:ADMIN,PETUGAS')->group(function (): void {
        Route::get('/barang-masuk', [StockMovementController::class, 'createMasuk'])->name('stock.masuk.create');
        Route::post('/barang-masuk', [StockMovementController::class, 'storeMasuk'])->name('stock.masuk.store');

        Route::get('/barang-keluar', [StockMovementController::class, 'createKeluar'])->name('stock.keluar.create');
        Route::post('/barang-keluar', [StockMovementController::class, 'storeKeluar'])->name('stock.keluar.store');
    });

    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export', [ReportController::class, 'export'])->name('reports.export');

    // Manajemen pengguna: dibatasi ADMIN di UserController.
    Route::resource('users', UserController::class)->except('show');
});
