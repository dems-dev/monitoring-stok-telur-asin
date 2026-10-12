@extends('layouts.app')

@section('title', 'Dashboard Analytics')

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-stat-card label="Jenis Produk" :value="number_format($totalTypes, 0, ',', '.')" unit="jenis"
                     icon="fa-boxes-stacked" tone="slate" />
        <x-stat-card label="Total Stok Fisik" :value="$totalStockDisplay"
                     icon="fa-egg" tone="amber" />
        <x-stat-card label="Mutasi Hari Ini" :value="number_format($todayMutation, 0, ',', '.')"
                     icon="fa-right-left" tone="emerald" />
        <x-stat-card label="Stok Menipis" :value="number_format($lowStockItems->count(), 0, ',', '.')" unit="varian"
                     icon="fa-triangle-exclamation" tone="rose" />
    </div>

    @if ($lowStockItems->isNotEmpty())
        <div class="mb-6 p-4 rounded-2xl border border-rose-200 bg-rose-50/80">
            <div class="flex items-center gap-2 text-sm font-bold text-rose-800 mb-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Peringatan Stok Menipis
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($lowStockItems as $item)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-300">
                        {{ $item->name }}: <b class="ml-1">{{ number_format($item->stock, 0, ',', '.') }} {{ $item->unit?->name ?? 'butir' }}</b>
                        <span class="ml-1 font-normal">(Batas Min: {{ number_format($item->min_stock, 0, ',', '.') }})</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm card-shadow">
            <h3 class="text-sm font-bold text-slate-900 mb-1">Stok Fisik vs Batas Minimum</h3>
            <p class="text-xs text-slate-500 mb-4">Varian yang batangnya tertimpa warna merah sudah menyentuh ambang.</p>
            <div class="h-72">
                <canvas id="stockChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm card-shadow">
            <h3 class="text-sm font-bold text-slate-900 mb-1">Komposisi per Kategori</h3>
            <p class="text-xs text-slate-500 mb-4">Pembagian total stok menurut kategori produk.</p>
            <div class="h-72">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Aktivitas Terbaru</h3>
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentLogs as $log)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono text-xs text-slate-500">{{ $log->logged_date->format('Y-m-d') }}</td>
                            <td class="py-3 px-4">
                                <span @class([
                                    'px-2 py-0.5 text-[10px] font-bold rounded-full',
                                    'bg-emerald-100 text-emerald-700' => $log->type === \App\Enums\LogType::MASUK,
                                    'bg-rose-100 text-rose-700' => $log->type === \App\Enums\LogType::KELUAR,
                                    'bg-blue-100 text-blue-700' => $log->type === \App\Enums\LogType::PENYESUAIAN,
                                ])>{{ $log->type->value }}</span>
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-800">{{ $log->product?->name ?? '-' }}</td>
                            <td class="py-3 px-4 font-bold text-slate-700">
                                {{ number_format($log->amount, 0, ',', '.') }}
                                <span class="text-xs font-normal text-slate-500">{{ $log->product?->unit?->name ?? 'butir' }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-500">
                                {{ $log->supplier?->name ?? $log->source_or_target }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-6 text-slate-400">Belum ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const chartData = @json($chartData);

        // Nama panjang dipendekkan agar label sumbu X tetap terbaca.
        const shortLabels = chartData.labels.map(
            (name) => (name.length > 15 ? name.slice(0, 15) + '...' : name)
        );

        new Chart(document.getElementById('stockChart'), {
            type: 'bar',
            data: {
                labels: shortLabels,
                datasets: [
                    { label: 'Stok Fisik', data: chartData.stock, backgroundColor: '#f59e0b', borderRadius: 6 },
                    { label: 'Batas Minimum', data: chartData.minStock, backgroundColor: '#ef4444', borderRadius: 6 },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } },
            },
        });

        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: chartData.categoryLabels,
                datasets: [{
                    data: chartData.categoryValues,
                    backgroundColor: ['#f59e0b', '#22c55e', '#06b6d4', '#ec4899', '#8b5cf6', '#64748b'],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
            },
        });
    </script>
@endpush
