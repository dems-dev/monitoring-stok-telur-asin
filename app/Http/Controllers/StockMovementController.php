<?php

namespace App\Http\Controllers;

use App\Enums\LogType;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\StoreStockOpnameRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function createMasuk(): View
    {
        return view('stock.masuk', [
            'products' => $this->selectableProducts(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function createKeluar(): View
    {
        return view('stock.keluar', [
            'products' => $this->selectableProducts(),
            'statuses' => LogType::KELUAR->allowedStatuses(),
        ]);
    }

    public function createOpname(): View
    {
        return view('stock.opname', [
            'products' => $this->selectableProducts(),
        ]);
    }

    public function storeMasuk(StoreStockMovementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);

        $log = $this->stockService->barangMasuk(
            $product,
            (int) $data['amount'],
            $data,
            $request->user()
        );

        $unitName = $product->unit?->name ?? 'butir';

        return redirect()
            ->route('dashboard')
            ->with('status', "Stok masuk +{$log->amount} {$unitName} ({$product->name}) berhasil disimpan.");
    }

    public function storeKeluar(StoreStockMovementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);

        $log = $this->stockService->barangKeluar(
            $product,
            (int) $data['amount'],
            $data,
            $request->user()
        );

        $unitName = $product->unit?->name ?? 'butir';

        return redirect()
            ->route('dashboard')
            ->with('status', "Transaksi barang keluar -{$log->amount} {$unitName} ({$product->name}) berhasil disimpan.");
    }

    public function storeOpname(StoreStockOpnameRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $product = Product::with('unit')->findOrFail($data['product_id']);

        $log = $this->stockService->penyesuaian(
            $product,
            (int) $data['stok_fisik'],
            $data['alasan'],
            $request->user(),
            $data['logged_date']
        );

        $unitName = $product->unit?->name ?? 'butir';
        $selisih = $log->stok_sesudah - $log->stok_sebelum;
        $tanda = $selisih > 0 ? "+{$selisih}" : ($selisih < 0 ? "{$selisih}" : "0");

        return redirect()
            ->route('dashboard')
            ->with('status', "Stock opname {$product->name} berhasil disimpan. Stok fisik: {$log->stok_sesudah} {$unitName} (Selisih: {$tanda}).");
    }

    private function selectableProducts()
    {
        return Product::with('unit')->orderBy('name')->get(['id', 'sku', 'name', 'stock', 'unit_id']);
    }
}
