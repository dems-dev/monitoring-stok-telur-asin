<?php

namespace App\Http\Controllers;

use App\Enums\LogType;
use App\Http\Requests\StoreStockMovementRequest;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function createMasuk(): View
    {
        return view('stock.masuk', [
            'products' => $this->selectableProducts(),
        ]);
    }

    public function createKeluar(): View
    {
        return view('stock.keluar', [
            'products' => $this->selectableProducts(),
            'statuses' => LogType::KELUAR->allowedStatuses(),
        ]);
    }

    public function storeMasuk(StoreStockMovementRequest $request): RedirectResponse
    {
        $amount = $this->record($request, LogType::MASUK);

        return redirect()
            ->route('dashboard')
            ->with('status', "Stok masuk +{$amount} butir berhasil disimpan.");
    }

    public function storeKeluar(StoreStockMovementRequest $request): RedirectResponse
    {
        $amount = $this->record($request, LogType::KELUAR);

        return redirect()
            ->route('dashboard')
            ->with('status', "Transaksi barang keluar -{$amount} butir berhasil disimpan.");
    }

    /**
     * Catat mutasi dan sesuaikan stok dalam satu transaksi.
     *
     * Baris produk dikunci lewat lockForUpdate agar dua entri bersamaan tidak
     * saling menimpa dan membuat stok keluar melebihi stok fisik.
     */
    private function record(StoreStockMovementRequest $request, LogType $type): int
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $type, $request): int {
            $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            $amount = (int) $data['amount'];

            if ($type === LogType::KELUAR && $product->stock < $amount) {
                throw ValidationException::withMessages([
                    'amount' => "Stok fisik tidak mencukupi. Stok {$product->name} saat ini {$product->stock} butir.",
                ]);
            }

            $product->stock += $type->sign() * $amount;
            $product->save();

            StockLog::create([
                'type' => $type,
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'amount' => $amount,
                'source_or_target' => $data['source_or_target'],
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
                'logged_date' => $data['logged_date'],
            ]);

            return $amount;
        });
    }

    private function selectableProducts()
    {
        return Product::orderBy('name')->get(['id', 'sku', 'name', 'stock']);
    }
}
