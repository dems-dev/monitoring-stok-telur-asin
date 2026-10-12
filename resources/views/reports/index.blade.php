@extends('layouts.app')

@section('title', 'Riwayat & Laporan')

@section('content')
    <!-- Tab Navigasi Menu Laporan -->
    <div class="flex items-center gap-2 mb-5 border-b border-slate-200 pb-3 no-print">
        <a href="{{ route('reports.index') }}"
           class="px-4 py-2 text-sm font-bold rounded-xl bg-amber-500 text-white shadow-sm transition-colors">
            <i class="fa-solid fa-clock-rotate-left mr-1.5"></i>
            Riwayat Mutasi Lengkap
        </a>
        <a href="{{ route('reports.stock_card') }}"
           class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 hover:text-slate-800 hover:bg-slate-100 transition-colors">
            <i class="fa-solid fa-id-card-clip mr-1.5 text-slate-400"></i>
            Kartu Stok per Produk
        </a>
    </div>

    <!-- Print-Only Header untuk Cetak Kertas -->
    <div class="hidden print-only mb-6 border-b-2 border-slate-900 pb-3">
        <h1 class="text-xl font-extrabold uppercase tracking-wide">SIM-STOK TELUR ASIN - LAPORAN MUTASI</h1>
        <p class="text-xs text-slate-600 mt-1">
            Periode: {{ $filters['start_date'] ?? 'Semua' }} s/d {{ $filters['end_date'] ?? 'Semua' }}
            @if (!empty($filters['type'])) &middot; Jenis: {{ $filters['type'] }} @endif
            @if (!empty($filters['product_name'])) &middot; Produk: {{ $filters['product_name'] }} @endif
            @if (!empty($filters['user_name'])) &middot; Petugas: {{ $filters['user_name'] }} @endif
        </p>
        <p class="text-[10px] text-slate-500 mt-0.5">Dicetak pada {{ now()->translatedFormat('d F Y H:i') }} oleh {{ auth()->user()->name }}</p>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-100 no-print">
            <h3 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-filter text-amber-500"></i>
                <span>Filter Riwayat Mutasi Persediaan</span>
            </h3>

            <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 items-end">
                <!-- Filter Jenis Mutasi -->
                <div>
                    <label for="type" class="block text-xs font-semibold text-slate-600 mb-1">Jenis Mutasi</label>
                    <select id="type" name="type"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                        <option value="">Semua Jenis</option>
                        @foreach (\App\Enums\LogType::cases() as $case)
                            <option value="{{ $case->value }}" @selected(($filters['type'] ?? null) === $case->value)>
                                {{ $case->value }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Varian Produk -->
                <div>
                    <label for="product_id" class="block text-xs font-semibold text-slate-600 mb-1">Varian Produk</label>
                    <select id="product_id" name="product_id"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                        <option value="">Semua Produk</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? null) == $product->id)>
                                {{ $product->sku }} - {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Petugas / User -->
                <div>
                    <label for="user_id" class="block text-xs font-semibold text-slate-600 mb-1">Petugas Pencatat</label>
                    <select id="user_id" name="user_id"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                        <option value="">Semua Petugas</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? null) == $user->id)>
                                {{ $user->name }} ({{ $user->username }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dari Tanggal -->
                <div>
                    <label for="start_date" class="block text-xs font-semibold text-slate-600 mb-1">Dari Tanggal</label>
                    <input id="start_date" name="start_date" type="date" value="{{ $filters['start_date'] ?? '' }}"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                </div>

                <!-- Sampai Tanggal -->
                <div>
                    <label for="end_date" class="block text-xs font-semibold text-slate-600 mb-1">Sampai Tanggal</label>
                    <input id="end_date" name="end_date" type="date" value="{{ $filters['end_date'] ?? '' }}"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                </div>

                <!-- Tombol Aksi & Ekspor -->
                <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl transition-colors shadow-sm">
                            <i class="fa-solid fa-filter mr-1.5"></i> Terapkan
                        </button>
                        <a href="{{ route('reports.index') }}"
                           class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-semibold rounded-xl transition-colors">
                            Reset
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('reports.export.pdf', request()->query()) }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                            <i class="fa-solid fa-file-pdf"></i>
                            <span>PDF</span>
                        </a>

                        <a href="{{ route('reports.export.excel', request()->query()) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>Excel</span>
                        </a>

                        <a href="{{ route('reports.export.csv', request()->query()) }}"
                           class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                            <i class="fa-solid fa-file-csv"></i>
                            <span>CSV</span>
                        </a>

                        <button type="button" onclick="window.print()"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold rounded-xl transition-colors">
                            <i class="fa-solid fa-print"></i>
                            <span>Cetak</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabel Riwayat Mutasi -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3 px-4 text-left font-bold">Tanggal</th>
                        <th class="py-3 px-4 text-left font-bold">Tipe</th>
                        <th class="py-3 px-4 text-left font-bold">Produk</th>
                        <th class="py-3 px-4 text-left font-bold">Jumlah</th>
                        <th class="py-3 px-4 text-left font-bold">Stok Awal &rarr; Akhir</th>
                        <th class="py-3 px-4 text-left font-bold">Sumber / Tujuan</th>
                        <th class="py-3 px-4 text-left font-bold">Status</th>
                        <th class="py-3 px-4 text-left font-bold">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-4 font-mono text-xs text-slate-600 whitespace-nowrap">{{ $log->logged_date->format('Y-m-d') }}</td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span @class([
                                    'px-2 py-0.5 text-[10px] font-bold rounded-full',
                                    'bg-emerald-100 text-emerald-700' => $log->type === \App\Enums\LogType::MASUK,
                                    'bg-rose-100 text-rose-700' => $log->type === \App\Enums\LogType::KELUAR,
                                    'bg-blue-100 text-blue-700' => $log->type === \App\Enums\LogType::PENYESUAIAN,
                                ])>{{ $log->type->value }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-800">{{ $log->product?->name ?? 'Produk dihapus' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $log->product?->sku }}</div>
                            </td>
                            <td @class([
                                'py-3 px-4 font-bold whitespace-nowrap',
                                'text-emerald-600' => $log->type === \App\Enums\LogType::MASUK,
                                'text-rose-600' => $log->type === \App\Enums\LogType::KELUAR,
                                'text-blue-600' => $log->type === \App\Enums\LogType::PENYESUAIAN,
                            ])>
                                {{ match($log->type) {
                                    \App\Enums\LogType::MASUK => '+',
                                    \App\Enums\LogType::KELUAR => '-',
                                    \App\Enums\LogType::PENYESUAIAN => '±',
                                } }}{{ number_format($log->amount, 0, ',', '.') }} {{ $log->product?->unit?->name ?? 'butir' }}
                            </td>
                            <td class="py-3 px-4 font-mono text-xs text-slate-600 whitespace-nowrap">
                                {{ number_format($log->stok_sebelum, 0, ',', '.') }} &rarr;
                                <b class="text-slate-800">{{ number_format($log->stok_sesudah, 0, ',', '.') }}</b>
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-600">{{ $log->supplier?->name ?? $log->source_or_target }}</td>
                            <td class="py-3 px-4 text-xs text-slate-500">
                                <span class="font-semibold">{{ $log->status }}</span>
                                @if ($log->note)
                                    <div class="text-[11px] text-slate-400 mt-0.5">{{ $log->note }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-600 whitespace-nowrap">
                                {{ $log->user?->name ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400 text-sm">Tidak ada riwayat mutasi transaksi yang sesuai filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 no-print">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
