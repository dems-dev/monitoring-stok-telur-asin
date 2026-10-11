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
        $products = Product::orderBy('name')->get();

        // Mutasi hari ini dihitung dari logged_date, bukan created_at, agar cocok
        // dengan tanggal yang diisi petugas seperti di prototipe.
        $todayMutation = (int) StockLog::whereDate('logged_date', today())->sum('amount');

        $lowStockItems = $products->filter->isLowStock()->values();

        $recentLogs = StockLog::with('product')
            ->latest('id')
            ->take(5)
            ->get();

        $byCategory = $products
            ->groupBy('category')
            ->map(fn ($group) => (int) $group->sum('stock'));

        return view('dashboard', [
            'totalTypes' => $products->count(),
            'totalStock' => (int) $products->sum('stock'),
            'todayMutation' => $todayMutation,
            'lowStockItems' => $lowStockItems,
            'recentLogs' => $recentLogs,
            // Satu array utuh supaya view cukup memanggil @json($chartData)
            // dalam satu baris; @json([...]) multi-baris menggagalkan
            // parser direktif Blade.
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
