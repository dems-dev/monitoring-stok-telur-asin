@extends('layouts.app')

@section('title', 'Dashboard Analytics')

@section('content')
    <!-- Kartu Statistik Utama -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 {{ $canViewInventoryValue ? 'xl:grid-cols-6' : 'xl:grid-cols-5' }} gap-4 mb-6">
        <x-stat-card label="Varian Produk" :value="number_format($totalTypes, 0, ',', '.')" unit="varian"
                     icon="fa-boxes-stacked" tone="slate" />

        <x-stat-card label="Total Stok Fisik" :value="$totalStockDisplay"
                     icon="fa-egg" tone="amber" />

        @if ($canViewInventoryValue)
            <x-stat-card label="Nilai Persediaan" :value="'Rp ' . number_format($totalInventoryValue, 0, ',', '.')"
                         icon="fa-wallet" tone="indigo" />
        @endif

        <x-stat-card label="Masuk Hari Ini" :value="$masukTodayDisplay"
                     icon="fa-arrow-down" tone="emerald" />

        <x-stat-card label="Keluar Hari Ini" :value="$keluarTodayDisplay"
                     icon="fa-arrow-up" tone="rose" />

        <x-stat-card label="Stok Menipis" :value="number_format($lowStockCount, 0, ',', '.')" unit="varian"
                     icon="fa-triangle-exclamation" tone="{{ $lowStockCount > 0 ? 'rose' : 'slate' }}" />
    </div>

    <!-- Banner Peringatan Stok Menipis -->
    @if ($lowStockItems->isNotEmpty())
        <div class="mb-5 p-4 rounded-2xl border border-rose-200 bg-rose-50/90 shadow-sm">
            <div class="flex items-center gap-2 text-sm font-bold text-rose-800 mb-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Peringatan Stok Menipis (Di Bawah / Mencapai Batas Minimum)</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($lowStockItems as $item)
                    <div class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-white text-rose-800 border border-rose-200 shadow-xs">
                        <span class="font-bold text-slate-800">{{ $item->name }}</span>
                        <span class="mx-1.5 text-slate-300">|</span>
                        <span>Stok: <b class="text-rose-600">{{ number_format($item->stock, 0, ',', '.') }} {{ $item->unit?->name ?? 'butir' }}</b></span>
                        <span class="ml-1 text-[11px] text-slate-500">(Batas Min: {{ number_format($item->min_stock, 0, ',', '.') }})</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Banner Peringatan Produk Mendekati Kedaluwarsa (<= 3 Hari) -->
    @if ($expiringItems->isNotEmpty())
        <div class="mb-5 p-4 rounded-2xl border border-amber-200 bg-amber-50/90 shadow-sm">
            <div class="flex items-center gap-2 text-sm font-bold text-amber-900 mb-2">
                <i class="fa-solid fa-calendar-xmark text-amber-700"></i>
                <span>Peringatan Produk Mendekati Kedaluwarsa (&le; 3 Hari)</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($expiringItems as $item)
                    @php
                        $daysDiff = today()->diffInDays($item->nearest_expiry_date, false);
                    @endphp
                    <div class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-white text-amber-900 border border-amber-200 shadow-xs">
                        <span class="font-bold text-slate-800">{{ $item->name }}</span>
                        <span class="mx-1.5 text-slate-300">|</span>
                        <span>Exp: <b class="text-amber-800">{{ $item->nearest_expiry_date->format('d/m/Y') }}</b></span>
                        <span class="ml-1.5 px-2 py-0.5 rounded-md text-[10px] font-bold {{ $daysDiff <= 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">
                            {{ $daysDiff < 0 ? 'Sudah lewat ' . abs($daysDiff) . ' hari' : ($daysDiff === 0 ? 'Hari ini' : $daysDiff . ' hari lagi') }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Section Grafik Visualisasi Chart.js -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Grafik 1: Stok Fisik vs Garis Batas Minimum -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm card-shadow">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-bold text-slate-900">Stok per Produk vs Batas Minimum</h3>
                <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Bar + Line</span>
            </div>
            <p class="text-xs text-slate-500 mb-4">Garis putus-putus merah menandakan batas minimum yang harus dipenuhi.</p>
            <div class="h-72">
                <canvas id="stockMinChart"></canvas>
            </div>
        </div>

        <!-- Grafik 2: Tren Barang Masuk vs Keluar 30 Hari Terakhir -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm card-shadow">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-bold text-slate-900">Tren Mutasi Masuk vs Keluar (30 Hari Terakhir)</h3>
                <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Line Chart</span>
            </div>
            <p class="text-xs text-slate-500 mb-4">Pergerakan pasokan barang masuk (hijau) berbanding penjualan keluar (merah).</p>
            <div class="h-72">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Grafik 3: 5 Produk Terlaris Bulan Ini & Aktivitas Transaksi Terakhir -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- 5 Produk Terlaris Bulan Ini -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm card-shadow">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-bold text-slate-900">5 Produk Terlaris Bulan Ini</h3>
                <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">Horizontal Bar</span>
            </div>
            <p class="text-xs text-slate-500 mb-4">Varian dengan akumulasi penjualan keluar terbanyak di bulan berjalan.</p>
            <div class="h-72">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>

        <!-- Tabel 5 Transaksi Terakhir -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow overflow-hidden flex flex-col justify-between">
            <div>
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">5 Transaksi Terakhir</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Urutan riwayat mutasi berdasarkan tanggal entri &amp; waktu pencatatan.</p>
                    </div>
                    <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">
                        Lihat semua &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-3 px-4 text-left font-bold">Tanggal</th>
                                <th class="py-3 px-4 text-left font-bold">Tipe</th>
                                <th class="py-3 px-4 text-left font-bold">Produk</th>
                                <th class="py-3 px-4 text-left font-bold">Jumlah</th>
                                <th class="py-3 px-4 text-left font-bold">Sumber / Tujuan</th>
                                <th class="py-3 px-4 text-left font-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($recentLogs as $log)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="py-3 px-4 font-mono text-xs text-slate-600">
                                        {{ $log->logged_date->format('Y-m-d') }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span @class([
                                            'px-2 py-0.5 text-[10px] font-bold rounded-full',
                                            'bg-emerald-100 text-emerald-700' => $log->type === \App\Enums\LogType::MASUK,
                                            'bg-rose-100 text-rose-700' => $log->type === \App\Enums\LogType::KELUAR,
                                            'bg-blue-100 text-blue-700' => $log->type === \App\Enums\LogType::PENYESUAIAN,
                                        ])>{{ $log->type->value }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-medium text-slate-800">{{ $log->product?->name ?? 'Produk dihapus' }}</div>
                                        <div class="text-[10px] font-mono text-slate-400">{{ $log->product?->sku }}</div>
                                    </td>
                                    <td @class([
                                        'py-3 px-4 font-bold',
                                        'text-emerald-600' => $log->type === \App\Enums\LogType::MASUK,
                                        'text-rose-600' => $log->type === \App\Enums\LogType::KELUAR,
                                        'text-blue-600' => $log->type === \App\Enums\LogType::PENYESUAIAN,
                                    ])>
                                        {{ match($log->type) {
                                            \App\Enums\LogType::MASUK => '+',
                                            \App\Enums\LogType::KELUAR => '-',
                                            \App\Enums\LogType::PENYESUAIAN => '±',
                                        } }}{{ number_format($log->amount, 0, ',', '.') }}
                                        <span class="text-xs font-normal text-slate-500">{{ $log->product?->unit?->name ?? 'butir' }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-600">
                                        {{ $log->supplier?->name ?? $log->source_or_target }}
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-500">
                                        <span class="font-semibold">{{ $log->status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-slate-400">Belum ada aktivitas mutasi tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const chartsData = @json($charts);

        // 1. Grafik Stok Fisik vs Batas Minimum (Bar + Line threshold)
        const stockLabels = chartsData.stock.labels.map(name =>
            name.length > 16 ? name.slice(0, 16) + '...' : name
        );

        new Chart(document.getElementById('stockMinChart'), {
            data: {
                labels: stockLabels,
                datasets: [
                    {
                        type: 'line',
                        label: 'Batas Minimum',
                        data: chartsData.stock.minStock,
                        borderColor: '#ef4444',
                        backgroundColor: '#ef4444',
                        borderWidth: 2,
                        borderDash: [5, 4],
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.1,
                        order: 1
                    },
                    {
                        type: 'bar',
                        label: 'Stok Fisik',
                        data: chartsData.stock.stock,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                        order: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // 2. Grafik Tren Barang Masuk vs Keluar (30 Hari Terakhir)
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: chartsData.trend.labels,
                datasets: [
                    {
                        label: 'Barang Masuk',
                        data: chartsData.trend.masuk,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2
                    },
                    {
                        label: 'Barang Keluar',
                        data: chartsData.trend.keluar,
                        borderColor: '#f43f5e',
                        backgroundColor: 'rgba(244, 63, 94, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // 3. Grafik 5 Produk Terlaris Bulan Ini (Horizontal Bar)
        const topLabels = chartsData.top.labels.length > 0 ? chartsData.top.labels : ['Belum ada penjualan'];
        const topValues = chartsData.top.values.length > 0 ? chartsData.top.values : [0];

        new Chart(document.getElementById('topProductsChart'), {
            type: 'bar',
            data: {
                labels: topLabels,
                datasets: [{
                    label: 'Jumlah Terjual',
                    data: topValues,
                    backgroundColor: '#3b82f6',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    </script>
@endpush
