@extends('layouts.app')

@section('title', 'Kartu Stok per Produk')

@section('content')
    <!-- Tab Navigasi Menu Laporan -->
    <div class="flex items-center gap-2 mb-5 border-b border-slate-200 pb-3 no-print">
        <a href="{{ route('reports.index') }}"
           class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 hover:text-slate-800 hover:bg-slate-100 transition-colors">
            <i class="fa-solid fa-clock-rotate-left mr-1.5 text-slate-400"></i>
            Riwayat Mutasi Lengkap
        </a>
        <a href="{{ route('reports.stock_card', request()->query()) }}"
           class="px-4 py-2 text-sm font-bold rounded-xl bg-amber-500 text-white shadow-sm transition-colors">
            <i class="fa-solid fa-id-card-clip mr-1.5"></i>
            Kartu Stok per Produk
        </a>
    </div>

    <!-- Filter Kartu Stok -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow p-5 mb-6 no-print">
        <h3 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-filter text-amber-500"></i>
            <span>Filter Buku Besar Kartu Stok</span>
        </h3>

        <form method="GET" action="{{ route('reports.stock_card') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <!-- Pilihan Produk -->
            <div class="lg:col-span-2">
                <label for="product_id" class="block text-xs font-semibold text-slate-600 mb-1">
                    Pilih Varian Telur Asin <span class="text-rose-500">*</span>
                </label>
                <select id="product_id" name="product_id" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" @selected(($selectedProduct?->id ?? null) === $p->id)>
                            {{ $p->sku }} &mdash; {{ $p->name }} (Stok Saat Ini: {{ number_format($p->stock, 0, ',', '.') }} {{ $p->unit?->name ?? 'butir' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Dari Tanggal -->
            <div>
                <label for="start_date" class="block text-xs font-semibold text-slate-600 mb-1">Dari Tanggal</label>
                <input id="start_date" name="start_date" type="date" value="{{ $filters['start_date'] ?? '' }}"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
            </div>

            <!-- Sampai Tanggal -->
            <div>
                <label for="end_date" class="block text-xs font-semibold text-slate-600 mb-1">Sampai Tanggal</label>
                <input id="end_date" name="end_date" type="date" value="{{ $filters['end_date'] ?? '' }}"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
            </div>

            <!-- Tombol Aksi -->
            <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100">
                <button type="submit"
                        class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl transition-colors shadow-sm">
                    <i class="fa-solid fa-magnifying-glass mr-1.5"></i> Tampilkan Kartu
                </button>

                @if ($selectedProduct)
                    <a href="{{ route('reports.stock_card.pdf', request()->query()) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>Ekspor PDF</span>
                    </a>

                    <a href="{{ route('reports.stock_card.excel', request()->query()) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-colors shadow-sm">
                        <i class="fa-solid fa-file-excel"></i>
                        <span>Ekspor Excel</span>
                    </a>

                    <button type="button" onclick="window.print()"
                            class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-bold rounded-xl transition-colors">
                        <i class="fa-solid fa-print"></i>
                        <span>Cetak</span>
                    </button>
                @endif
            </div>
        </form>
    </div>

    @if ($selectedProduct)
        <!-- Identitas Produk yang Dipilih -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow p-5 mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-xs">
                        <i class="fa-solid fa-egg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-bold text-slate-400 uppercase">{{ $selectedProduct->sku }}</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">
                                {{ $selectedProduct->category?->name ?? 'Uncategorized' }}
                            </span>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900 leading-tight mt-0.5">{{ $selectedProduct->name }}</h2>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <div class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-slate-400">Batas Min:</span>
                        <b class="text-slate-700 ml-1">{{ number_format($selectedProduct->min_stock, 0, ',', '.') }} {{ $selectedProduct->unit?->name ?? 'butir' }}</b>
                    </div>
                    <div class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="text-slate-400">Harga Jual:</span>
                        <b class="text-slate-700 ml-1">Rp {{ number_format($selectedProduct->price, 0, ',', '.') }}</b>
                    </div>
                    <div class="px-3 py-1.5 rounded-xl {{ $selectedProduct->stock <= $selectedProduct->min_stock ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                        <span>Stok Fisik Saat Ini:</span>
                        <b class="ml-1">{{ number_format($selectedProduct->stock, 0, ',', '.') }} {{ $selectedProduct->unit?->name ?? 'butir' }}</b>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5 Kartu Ringkasan Periode Mutasi -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-6">
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Saldo Awal</div>
                <div class="text-lg font-extrabold text-slate-800 mt-1">
                    {{ number_format($summary['initial_stock'], 0, ',', '.') }}
                    <span class="text-xs font-normal text-slate-400">{{ $selectedProduct->unit?->name ?? 'butir' }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Total Masuk (+)</div>
                <div class="text-lg font-extrabold text-emerald-600 mt-1">
                    +{{ number_format($summary['total_masuk'], 0, ',', '.') }}
                    <span class="text-xs font-normal text-slate-400">{{ $selectedProduct->unit?->name ?? 'butir' }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Total Keluar (-)</div>
                <div class="text-lg font-extrabold text-rose-600 mt-1">
                    -{{ number_format($summary['total_keluar'], 0, ',', '.') }}
                    <span class="text-xs font-normal text-slate-400">{{ $selectedProduct->unit?->name ?? 'butir' }}</span>
                </div>
            </div>

            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-blue-600">Opname (&plusmn;)</div>
                <div class="text-lg font-extrabold text-blue-600 mt-1">
                    {{ $summary['total_adjustments'] > 0 ? '+'.$summary['total_adjustments'] : $summary['total_adjustments'] }}
                    <span class="text-xs font-normal text-slate-400">{{ $selectedProduct->unit?->name ?? 'butir' }}</span>
                </div>
            </div>

            <div class="col-span-2 sm:col-span-1 bg-amber-50/70 p-4 rounded-2xl border border-amber-200/80 shadow-2xs">
                <div class="text-[10px] font-bold uppercase tracking-wider text-amber-800">Saldo Akhir</div>
                <div class="text-lg font-extrabold text-amber-900 mt-1">
                    {{ number_format($summary['final_stock'], 0, ',', '.') }}
                    <span class="text-xs font-normal text-amber-700">{{ $selectedProduct->unit?->name ?? 'butir' }}</span>
                </div>
            </div>
        </div>

        <!-- Tabel Buku Besar Kartu Stok -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Buku Besar Riwayat Pergerakan Stok</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Urutan kronologis transaksi masuk, keluar, dan penyesuaian opname.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600">
                    {{ count($movements) }} Transaksi
                </span>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="py-3 px-4 text-left font-bold">Tanggal</th>
                            <th class="py-3 px-4 text-left font-bold">Ref ID</th>
                            <th class="py-3 px-4 text-left font-bold">Jenis</th>
                            <th class="py-3 px-4 text-left font-bold">Sumber / Tujuan / Keterangan</th>
                            <th class="py-3 px-4 text-right font-bold">Masuk (+)</th>
                            <th class="py-3 px-4 text-right font-bold">Keluar (-)</th>
                            <th class="py-3 px-4 text-right font-bold">Opname (&plusmn;)</th>
                            <th class="py-3 px-4 text-right font-bold">Saldo Berjalan</th>
                            <th class="py-3 px-4 text-left font-bold">Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Baris Saldo Awal -->
                        <tr class="bg-slate-50/60 font-medium text-slate-500">
                            <td class="py-3 px-4 font-mono text-xs">{{ $filters['start_date'] ?? 'Awal' }}</td>
                            <td class="py-3 px-4 font-mono text-xs">-</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-200 text-slate-700">SALDO AWAL</span>
                            </td>
                            <td class="py-3 px-4 text-xs italic">Saldo persediaan sebelum periode pencarian</td>
                            <td class="py-3 px-4 text-right font-mono text-xs">-</td>
                            <td class="py-3 px-4 text-right font-mono text-xs">-</td>
                            <td class="py-3 px-4 text-right font-mono text-xs">-</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">
                                {{ number_format($summary['initial_stock'], 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-xs">Sistem</td>
                        </tr>

                        <!-- Daftar Pergerakan Transaksi -->
                        @forelse ($movements as $log)
                            @php
                                $isMasuk = $log->type === \App\Enums\LogType::MASUK;
                                $isKeluar = $log->type === \App\Enums\LogType::KELUAR;
                                $isOpname = $log->type === \App\Enums\LogType::PENYESUAIAN;
                                $diff = $isOpname ? ($log->stok_sesudah - $log->stok_sebelum) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3 px-4 font-mono text-xs text-slate-600">{{ $log->logged_date->format('Y-m-d') }}</td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-400">#{{ $log->id }}</td>
                                <td class="py-3 px-4">
                                    <span @class([
                                        'px-2 py-0.5 text-[10px] font-bold rounded-full',
                                        'bg-emerald-100 text-emerald-700' => $isMasuk,
                                        'bg-rose-100 text-rose-700' => $isKeluar,
                                        'bg-blue-100 text-blue-700' => $isOpname,
                                    ])>{{ $log->type->value }}</span>
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-700">
                                    <span class="font-medium">{{ $log->supplier?->name ?? $log->source_or_target ?? '-' }}</span>
                                    @if ($log->note)
                                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $log->note }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600">
                                    {{ $isMasuk ? '+'.number_format($log->amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-rose-600">
                                    {{ $isKeluar ? '-'.number_format($log->amount, 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-blue-600">
                                    {{ $isOpname ? ($diff > 0 ? '+'.$diff : $diff) : '-' }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-extrabold text-slate-900">
                                    {{ number_format($log->stok_sesudah, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-4 text-xs text-slate-600">{{ $log->user?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-8 text-slate-400 text-sm">
                                    Tidak ada catatan mutasi persediaan untuk produk ini pada periode tersebut.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
