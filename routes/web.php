<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
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

    // Master data kategori, satuan, dan supplier (khusus ADMIN).
    Route::middleware('role:ADMIN')->group(function (): void {
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('units', UnitController::class)->except('show');
        Route::resource('suppliers', SupplierController::class)->except('show');
    });

    // Pencatatan mutasi tertutup bagi OWNER yang hanya punya akses baca.
    Route::middleware('role:ADMIN,PETUGAS')->group(function (): void {
        Route::get('/barang-masuk', [StockMovementController::class, 'createMasuk'])->name('stock.masuk.create');
        Route::post('/barang-masuk', [StockMovementController::class, 'storeMasuk'])->name('stock.masuk.store');

        Route::get('/barang-keluar', [StockMovementController::class, 'createKeluar'])->name('stock.keluar.create');
        Route::post('/barang-keluar', [StockMovementController::class, 'storeKeluar'])->name('stock.keluar.store');

        Route::get('/stock-opname', [StockMovementController::class, 'createOpname'])->name('stock.opname.create');
        Route::post('/stock-opname', [StockMovementController::class, 'storeOpname'])->name('stock.opname.store');
    });

    Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/laporan/export', [ReportController::class, 'export'])->name('reports.export');

    // Manajemen pengguna: dibatasi ADMIN di UserController.
    Route::resource('users', UserController::class)->except('show');
});
