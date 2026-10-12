<?php

namespace App\Services;

use App\Enums\LogType;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChatbotToolService
{
    /**
     * Tool 1: Cek stok produk berdasarkan nama atau SKU.
     *
     * @return array<string, mixed>
     */
    public function cekStok(string $namaProduk): array
    {
        $term = trim($namaProduk);

        $products = Product::with(['category', 'unit'])
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%");
            })
            ->get();

        if ($products->isEmpty()) {
            return [
                'status' => 'not_found',
                'message' => "Produk dengan kata kunci '{$term}' tidak ditemukan dalam katalog persediaan.",
                'data' => [],
            ];
        }

        $results = $products->map(function (Product $p) {
            $condition = 'Aman';
            if ($p->stock <= 0) {
                $condition = 'Habis';
            } elseif ($p->stock <= $p->min_stock) {
                $condition = 'Menipis';
            }

            return [
                'sku' => $p->sku,
                'nama' => $p->name,
                'kategori' => $p->category?->name ?? 'Umum',
                'stok' => $p->stock,
                'satuan' => $p->unit?->name ?? 'butir',
                'batas_minimum' => $p->min_stock,
                'status_stok' => $condition,
                'harga_jual' => $p->price,
                'harga_beli' => $p->purchase_price,
            ];
        })->values()->all();

        return [
            'status' => 'success',
            'jumlah_ditemukan' => count($results),
            'data' => $results,
        ];
    }

    /**
     * Tool 2: Daftar produk yang stoknya menipis (<= batas minimum).
     *
     * @return array<string, mixed>
     */
    public function daftarStokMenipis(): array
    {
        $products = Product::with(['category', 'unit'])
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock', 'asc')
            ->get();

        if ($products->isEmpty()) {
            return [
                'status' => 'success',
                'total_menipis' => 0,
                'message' => 'Seluruh persediaan aman. Tidak ada produk yang berada di bawah atau mencapai batas minimum.',
                'data' => [],
            ];
        }

        $items = $products->map(function (Product $p) {
            return [
                'sku' => $p->sku,
                'nama' => $p->name,
                'stok_sekarang' => $p->stock,
                'batas_minimum' => $p->min_stock,
                'satuan' => $p->unit?->name ?? 'butir',
                'kategori' => $p->category?->name ?? 'Umum',
                'kondisi' => $p->stock <= 0 ? 'HABIS' : 'MENIPIS',
            ];
        })->values()->all();

        return [
            'status' => 'warning',
            'total_menipis' => count($items),
            'data' => $items,
        ];
    }

    /**
     * Tool 3: Daftar produk terlaris (pengeluaran/penjualan terbanyak).
     *
     * @return array<string, mixed>
     */
    public function produkTerlaris(string $periode = '30_hari'): array
    {
        $days = match ($periode) {
            '7_hari' => 7,
            'bulan_ini' => now()->day,
            default => 30,
        };

        $startDate = now()->subDays($days)->toDateString();

        $top = StockLog::query()
            ->select('product_id', DB::raw('SUM(amount) as total_keluar'))
            ->where('type', LogType::KELUAR)
            ->where('logged_date', '>=', $startDate)
            ->groupBy('product_id')
            ->orderByDesc('total_keluar')
            ->with(['product.unit'])
            ->limit(5)
            ->get();

        if ($top->isEmpty()) {
            return [
                'status' => 'success',
                'periode' => "{$days} hari terakhir",
                'message' => 'Belum ada transaksi barang keluar tercatat pada periode ini.',
                'data' => [],
            ];
        }

        $ranking = 1;
        $items = $top->map(function ($item) use (&$ranking) {
            return [
                'ranking' => $ranking++,
                'nama_produk' => $item->product?->name ?? 'Produk Dihapus',
                'sku' => $item->product?->sku ?? '-',
                'total_terjual' => (int) $item->total_keluar,
                'satuan' => $item->product?->unit?->name ?? 'butir',
            ];
        })->values()->all();

        return [
            'status' => 'success',
            'periode' => "{$days} hari terakhir",
            'total_produk' => count($items),
            'data' => $items,
        ];
    }

    /**
     * Tool 4: Riwayat barang masuk untuk produk tertentu atau semua produk.
     *
     * @return array<string, mixed>
     */
    public function riwayatBarangMasuk(?string $namaProduk = null, int $jumlahHari = 7): array
    {
        $days = max(1, min(90, $jumlahHari));
        $startDate = now()->subDays($days)->toDateString();

        $query = StockLog::with(['product.unit', 'supplier'])
            ->where('type', LogType::MASUK)
            ->where('logged_date', '>=', $startDate);

        if (!empty($namaProduk)) {
            $term = trim($namaProduk);
            $query->whereHas('product', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $logs = $query->latest('logged_date')->latest('id')->limit(10)->get();

        if ($logs->isEmpty()) {
            return [
                'status' => 'success',
                'periode' => "{$days} hari terakhir",
                'message' => 'Tidak ada catatan pasokan barang masuk pada rentang waktu ini.',
                'data' => [],
            ];
        }

        $items = $logs->map(function ($log) {
            return [
                'tanggal' => $log->logged_date->format('Y-m-d'),
                'nama_produk' => $log->product?->name ?? 'Produk Dihapus',
                'jumlah' => $log->amount,
                'satuan' => $log->product?->unit?->name ?? 'butir',
                'sumber' => $log->supplier?->name ?? $log->source_or_target ?? '-',
                'catatan' => $log->note ?? '-',
            ];
        })->values()->all();

        return [
            'status' => 'success',
            'periode' => "{$days} hari terakhir",
            'total_pencatatan' => count($items),
            'data' => $items,
        ];
    }
}
