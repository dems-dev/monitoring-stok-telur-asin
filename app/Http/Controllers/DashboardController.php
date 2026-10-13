<?php

namespace App\Http\Controllers;

use App\Enums\LogType;
use App\Enums\Role;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $products = Product::with(['category', 'unit'])->orderBy('name')->get();

        // 1. Total Produk & Total Stok Fisik per Satuan
        $stockByUnit = $products
            ->groupBy(fn ($p) => $p->unit?->name ?? 'butir')
            ->map(fn ($group) => (int) $group->sum('stock'));

        $totalStockDisplay = $stockByUnit
            ->map(fn ($total, $unit) => number_format($total, 0, ',', '.').' '.$unit)
            ->join(' · ');

        // 2. Nilai Persediaan (Stok x Harga Beli riil, khusus Admin & Pemilik).
        // Produk tanpa harga beli tidak ditaksir; jumlahnya ditampilkan sebagai peringatan.
        $canViewInventoryValue = in_array($user?->role, [Role::ADMIN, Role::OWNER], true);
        $totalInventoryValue = 0;
        $missingPurchasePriceCount = 0;
        if ($canViewInventoryValue) {
            $totalInventoryValue = (int) $products->sum(fn ($p) => max(0, (int) $p->stock) * $p->purchase_price);
            $missingPurchasePriceCount = $products->filter->isMissingPurchasePrice()->count();
        }

        // 3. Mutasi Hari Ini (DIPISAH: Masuk vs Keluar)
        $masukTodayLogs = StockLog::with('product.unit')
            ->where('type', LogType::MASUK)
            ->whereDate('logged_date', today())
            ->get();

        $masukTodayByUnit = $masukTodayLogs
            ->groupBy(fn ($l) => $l->product?->unit?->name ?? 'butir')
            ->map(fn ($group) => (int) $group->sum('amount'));

        $masukTodayDisplay = $masukTodayByUnit->isEmpty()
            ? '0 butir'
            : $masukTodayByUnit->map(fn ($total, $unit) => number_format($total, 0, ',', '.').' '.$unit)->join(' · ');

        $keluarTodayLogs = StockLog::with('product.unit')
            ->where('type', LogType::KELUAR)
            ->whereDate('logged_date', today())
            ->get();

        $keluarTodayByUnit = $keluarTodayLogs
            ->groupBy(fn ($l) => $l->product?->unit?->name ?? 'butir')
            ->map(fn ($group) => (int) $group->sum('amount'));

        $keluarTodayDisplay = $keluarTodayByUnit->isEmpty()
            ? '0 butir'
            : $keluarTodayByUnit->map(fn ($total, $unit) => number_format($total, 0, ',', '.').' '.$unit)->join(' · ');

        // 4. Banner Peringatan Stok Menipis (stok <= stok_minimum)
        $lowStockItems = $products->filter->isLowStock()->values();
        $lowStockCount = $lowStockItems->count();

        // 5. Banner Peringatan Kedaluwarsa (<= 3 hari)
        $expiringItems = $products->filter(fn ($p) => $p->isExpiringSoon(3))->sortBy('nearest_expiry_date')->values();
        $expiringCount = $expiringItems->count();

        // 6. Grafik a: Stok per Produk vs Batas Minimum
        $stockLabels = $products->pluck('name');
        $stockValues = $products->pluck('stock');
        $minStockValues = $products->pluck('min_stock');

        // 7. Grafik b: Tren Barang Masuk vs Keluar 30 Hari Terakhir (Server Aggregation)
        $startDate = today()->subDays(29);
        $trendLogs = StockLog::where('logged_date', '>=', $startDate)
            ->select('logged_date', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('logged_date', 'type')
            ->get()
            // logged_date di-cast ke Carbon, jadi kunci dibentuk dari string tanggal
            // agar bisa dicocokkan dengan tanggal pada sumbu grafik.
            ->mapWithKeys(fn ($row) => [$row->logged_date->toDateString().'|'.$row->type->value => (int) $row->total]);

        $trendLabels = [];
        $trendMasuk = [];
        $trendKeluar = [];

        for ($i = 29; $i >= 0; $i--) {
            $currentDate = today()->subDays($i);
            $dateString = $currentDate->toDateString();

            $trendLabels[] = $currentDate->format('d M');
            $trendMasuk[] = $trendLogs[$dateString.'|'.LogType::MASUK->value] ?? 0;
            $trendKeluar[] = $trendLogs[$dateString.'|'.LogType::KELUAR->value] ?? 0;
        }

        // 8. Grafik c: 5 Produk Terlaris Bulan Ini (Horizontal Bar)
        $topProducts = StockLog::where('type', LogType::KELUAR)
            ->whereMonth('logged_date', today()->month)
            ->whereYear('logged_date', today()->year)
            ->select('product_id', DB::raw('SUM(amount) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->with(['product.unit'])
            ->get();

        $topLabels = $topProducts->map(fn ($item) => $item->product?->name ?? 'Varian Dihapus')->values();
        $topValues = $topProducts->pluck('total_sold')->values();

        // 9. Tabel 5 Transaksi Terakhir (diurutkan logged_date lalu created_at)
        $recentLogs = StockLog::with(['product.unit', 'supplier', 'user'])
            ->orderByDesc('logged_date')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('dashboard', [
            'totalTypes' => $products->count(),
            'totalStockDisplay' => $totalStockDisplay ?: '0 butir',
            'canViewInventoryValue' => $canViewInventoryValue,
            'totalInventoryValue' => $totalInventoryValue,
            'missingPurchasePriceCount' => $missingPurchasePriceCount,
            'masukTodayDisplay' => $masukTodayDisplay,
            'keluarTodayDisplay' => $keluarTodayDisplay,
            'lowStockItems' => $lowStockItems,
            'lowStockCount' => $lowStockCount,
            'expiringItems' => $expiringItems,
            'expiringCount' => $expiringCount,
            'recentLogs' => $recentLogs,
            'charts' => [
                'stock' => [
                    'labels' => $stockLabels,
                    'stock' => $stockValues,
                    'minStock' => $minStockValues,
                ],
                'trend' => [
                    'labels' => $trendLabels,
                    'masuk' => $trendMasuk,
                    'keluar' => $trendKeluar,
                ],
                'top' => [
                    'labels' => $topLabels,
                    'values' => $topValues,
                ],
            ],
        ]);
    }
}
