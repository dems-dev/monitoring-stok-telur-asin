<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $products = Product::with(['category', 'unit'])->orderBy('name')->get();

        // Mutasi hari ini dihitung dari logged_date, bukan created_at, agar cocok
        // dengan tanggal yang diisi petugas seperti di prototipe.
        $todayMutation = (int) StockLog::whereDate('logged_date', today())->sum('amount');

        $lowStockItems = $products->filter->isLowStock()->values();

        $recentLogs = StockLog::with(['product.unit', 'supplier'])
            ->latest('id')
            ->take(5)
            ->get();

        // Total stok dipisah per satuan (butir / pack) agar tidak menjumlahkan satuan berbeda.
        $stockByUnit = $products
            ->groupBy(fn ($p) => $p->unit?->name ?? 'butir')
            ->map(fn ($group) => (int) $group->sum('stock'));

        $totalStockDisplay = $stockByUnit
            ->map(fn ($total, $unit) => number_format($total, 0, ',', '.') . ' ' . $unit)
            ->join(' · ');

        $byCategory = $products
            ->groupBy(fn ($p) => $p->category?->name ?? 'Lainnya')
            ->map(fn ($group) => (int) $group->sum('stock'));

        return view('dashboard', [
            'totalTypes' => $products->count(),
            'totalStockDisplay' => $totalStockDisplay ?: '0 butir',
            'stockByUnit' => $stockByUnit,
            'todayMutation' => $todayMutation,
            'lowStockItems' => $lowStockItems,
            'recentLogs' => $recentLogs,
            'chartData' => [
                'labels' => $products->pluck('name'),
                'stock' => $products->pluck('stock'),
                'minStock' => $products->pluck('min_stock'),
                'categoryLabels' => $byCategory->keys(),
                'categoryValues' => $byCategory->values(),
            ],
        ]);
    }
}
