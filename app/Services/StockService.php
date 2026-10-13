<?php

namespace App\Services;

use App\Enums\LogType;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Catat barang masuk dan tambah stok fisik produk.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Throwable
     */
    public function barangMasuk(Product $product, int $jumlah, array $data = [], ?User $user = null): StockLog
    {
        return DB::transaction(function () use ($product, $jumlah, $data, $user): StockLog {
            // lockForUpdate mencegah race condition pada pembacaan/penulisan stok bersamaan.
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            $stokSebelum = (int) $lockedProduct->stock;
            $stokSesudah = $stokSebelum + $jumlah;

            $lockedProduct->stock = $stokSesudah;
            $lockedProduct->save();

            $userId = $user?->id ?? auth()->id();

            return StockLog::create([
                'type' => LogType::MASUK,
                'product_id' => $lockedProduct->id,
                'user_id' => $userId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'amount' => $jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'source_or_target' => $data['source_or_target'] ?? 'Pemasok',
                'status' => $data['status'] ?? 'Selesai',
                'note' => $data['note'] ?? null,
                'logged_date' => $data['logged_date'] ?? today()->toDateString(),
            ]);
        });
    }

    /**
     * Catat barang keluar dan kurangi stok fisik produk.
     * Ditolak dengan ValidationException jika stok fisik tidak mencukupi.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException|\Throwable
     */
    public function barangKeluar(Product $product, int $jumlah, array $data = [], ?User $user = null): StockLog
    {
        return DB::transaction(function () use ($product, $jumlah, $data, $user): StockLog {
            $lockedProduct = Product::with('unit')->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($lockedProduct->stock < $jumlah) {
                $unitName = $lockedProduct->unit?->name ?? 'butir';
                throw ValidationException::withMessages([
                    'amount' => "Stok fisik tidak mencukupi. Stok {$lockedProduct->name} saat ini {$lockedProduct->stock} {$unitName}.",
                ]);
            }

            $stokSebelum = (int) $lockedProduct->stock;
            $stokSesudah = $stokSebelum - $jumlah;

            $lockedProduct->stock = $stokSesudah;
            $lockedProduct->save();

            $userId = $user?->id ?? auth()->id();

            return StockLog::create([
                'type' => LogType::KELUAR,
                'product_id' => $lockedProduct->id,
                'user_id' => $userId,
                'supplier_id' => null,
                'amount' => $jumlah,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'source_or_target' => $data['source_or_target'] ?? 'Pelanggan / Toko',
                'status' => $data['status'] ?? 'Terjual',
                'note' => $data['note'] ?? null,
                'logged_date' => $data['logged_date'] ?? today()->toDateString(),
            ]);
        });
    }

    /**
     * Catat penyesuaian stok (Stock Opname): sinkronisasi antara stok sistem dan stok fisik riil.
     *
     * @throws \Throwable
     */
    public function penyesuaian(
        Product $product,
        int $stokFisik,
        string $alasan,
        ?User $user = null,
        ?string $loggedDate = null
    ): StockLog {
        return DB::transaction(function () use ($product, $stokFisik, $alasan, $user, $loggedDate): StockLog {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

            $stokSebelum = (int) $lockedProduct->stock;
            $stokSesudah = $stokFisik;
            $selisih = $stokSesudah - $stokSebelum;

            $lockedProduct->stock = $stokSesudah;
            $lockedProduct->save();

            $status = match (true) {
                $selisih > 0 => 'Selisih Lebih',
                $selisih < 0 => 'Selisih Kurang',
                default => 'Sesuai',
            };

            $userId = $user?->id ?? auth()->id();

            return StockLog::create([
                'type' => LogType::PENYESUAIAN,
                'product_id' => $lockedProduct->id,
                'user_id' => $userId,
                'supplier_id' => null,
                'amount' => abs($selisih),
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'source_or_target' => 'Stock Opname Fisik',
                'status' => $status,
                'note' => $alasan,
                'logged_date' => $loggedDate ?? today()->toDateString(),
            ]);
        });
    }
}
