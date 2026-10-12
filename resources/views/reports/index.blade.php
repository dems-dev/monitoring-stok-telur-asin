@extends('layouts.app')

@section('title', 'Riwayat & Laporan')

@section('content')
    <div class="hidden print-only mb-6">
        <h1 class="text-xl font-bold">Laporan Mutasi Stok Telur Asin</h1>
        <p class="text-sm">
            Periode: {{ $filters['start_date'] ?? 'Semua' }} s/d {{ $filters['end_date'] ?? 'Semua' }}
            @if ($filters['type'] ?? null)
                &middot; Jenis: {{ $filters['type'] }}
            @endif
        </p>
        <p class="text-xs">Dicetak {{ now()->translatedFormat('d F Y H:i') }}</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 no-print">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Filter Riwayat Mutasi</h3>

            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
                <div>
                    <label for="type" class="block text-xs font-semibold text-slate-600 mb-1">Jenis</label>
                    <select id="type" name="type"
                            class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                        <option value="">Semua</option>
                        @foreach (\App\Enums\LogType::cases() as $case)
                            <option value="{{ $case->value }}" @selected(($filters['type'] ?? null) === $case->value)>
                                {{ $case->value }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="start_date" class="block text-xs font-semibold text-slate-600 mb-1">Dari Tanggal</label>
                    <input id="start_date" name="start_date" type="date" value="{{ $filters['start_date'] ?? '' }}"
                           class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                </div>

                <div>
                    <label for="end_date" class="block text-xs font-semibold text-slate-600 mb-1">Sampai Tanggal</label>
                    <input id="end_date" name="end_date" type="date" value="{{ $filters['end_date'] ?? '' }}"
                           class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold rounded-xl transition-colors">
                        Terapkan
                    </button>

                    <a href="{{ route('reports.export', request()->query()) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-colors">
                        <i class="fa-solid fa-file-csv"></i>
                        CSV
                    </a>

                    <button type="button" onclick="window.print()"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-bold rounded-xl transition-colors">
                        <i class="fa-solid fa-print"></i>
                        Cetak
                    </button>
                </div>
            </form>
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
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-4 font-mono text-xs text-slate-600">{{ $log->logged_date->format('Y-m-d') }}</td>
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
                                <div class="text-xs font-mono text-slate-400">{{ $log->product?->sku }}</div>
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
                                } }}{{ number_format($log->amount, 0, ',', '.') }} {{ $log->product?->unit?->name ?? 'butir' }}
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-600">{{ $log->source_or_target }}</td>
                            <td class="py-3 px-4 text-xs text-slate-500">
                                <span class="font-semibold">{{ $log->status }}</span>
                                @if ($log->note)
                                    <br><span class="text-[11px] text-slate-400">{{ $log->note }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-6 text-slate-400 text-sm">Tidak ada riwayat mutasi transaksi.</td>
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
