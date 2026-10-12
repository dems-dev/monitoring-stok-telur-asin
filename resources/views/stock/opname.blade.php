@extends('layouts.app')

@section('title', 'Stock Opname')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow max-w-2xl">
        <!-- Header Form -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Stock Opname &amp; Penyesuaian Fisik</h3>
                <p class="text-xs text-slate-500 mt-0.5">Pemeriksaan fisik persediaan dan pencatatan selisih stok riil di toko/gudang.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock.opname.store') }}" class="p-5 space-y-4">
            @csrf

            <!-- Baris 1: Tanggal Pemeriksaan & Petugas Pencatat (2 Kolom Seimbang) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="logged_date" class="block text-xs font-semibold text-slate-600 mb-1">
                        Tanggal Pemeriksaan <span class="text-rose-500">*</span>
                    </label>
                    <input id="logged_date" name="logged_date" type="date" required
                           max="{{ now()->toDateString() }}"
                           value="{{ old('logged_date', now()->toDateString()) }}"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                    @error('logged_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">
                        Petugas Pemeriksa
                    </label>
                    <div class="h-11 px-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 flex items-center justify-between">
                        <span class="font-medium text-slate-800">{{ auth()->user()->name }}</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 uppercase tracking-wider">
                            {{ auth()->user()->role->label() }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Baris 2: Pilih Varian Produk (Full Width agar SKU & Nama tidak terpotong) -->
            <div>
                <label for="product_id" class="block text-xs font-semibold text-slate-600 mb-1">
                    Pilih Varian Telur Asin <span class="text-rose-500">*</span>
                </label>
                <select id="product_id" name="product_id" required onchange="updateStockCalculation()"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                    <option value="" data-stock="0" data-unit="butir">-- Pilih Produk yang Akan Diperiksa --</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                                data-stock="{{ $product->stock }}"
                                data-unit="{{ $product->unit?->name ?? 'butir' }}"
                                @selected(old('product_id') == $product->id)>
                            {{ $product->sku }} - {{ $product->name }} (Stok Sistem: {{ number_format($product->stock, 0, ',', '.') }} {{ $product->unit?->name ?? 'butir' }})
                        </option>
                    @endforeach
                </select>
                @error('product_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <!-- Baris 3: Panel Komparasi Stok (Sistem vs Fisik) dengan Hasil Selisih di Bawah -->
            <div class="p-4 bg-slate-50/80 border border-slate-200 rounded-2xl space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-scale-balanced text-amber-500"></i>
                        Perbandingan Saldo Stok
                    </span>
                    <span class="text-[11px] text-slate-400">Sistem vs Fisik Riil</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Kolom Kiri: Stok Sistem -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Stok Sistem (Tercatat Saat Ini)
                        </label>
                        <div class="h-11 px-3.5 bg-white border border-slate-200 rounded-xl flex items-center justify-between shadow-xs">
                            <span id="display_system_stock" class="text-base font-extrabold text-slate-800">0</span>
                            <span id="display_unit_1" class="text-xs font-bold text-slate-400 uppercase">butir</span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">Saldo stok sebelum dilakukan penyesuaian.</p>
                    </div>

                    <!-- Kolom Kanan: Stok Fisik Riil -->
                    <div>
                        <label for="stok_fisik" class="block text-xs font-semibold text-slate-700 mb-1">
                            Stok Fisik Riil (Hasil Hitung) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="stok_fisik" name="stok_fisik" type="number" min="0" step="1" required
                                   value="{{ old('stok_fisik') }}" placeholder="0" oninput="updateStockCalculation()"
                                   class="h-11 w-full px-3.5 bg-white border border-slate-300 rounded-xl text-base font-extrabold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 pr-16 shadow-xs">
                            <span id="display_unit_2" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 uppercase pointer-events-none">
                                butir
                            </span>
                        </div>
                        @error('stok_fisik') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-[11px] text-slate-400">Jumlah fisik aktual yang ada di rak/gudang.</p>
                    </div>
                </div>

                <!-- Indikator Selisih Otomatis di Bawah Komparasi -->
                <div class="pt-3 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <span class="text-xs font-semibold text-slate-600">Status Selisih Persediaan:</span>
                    <span id="difference_badge" class="px-3 py-1.5 text-xs font-bold rounded-xl bg-slate-200 text-slate-600 inline-flex items-center">
                        Pilih varian &amp; isi stok fisik
                    </span>
                </div>
            </div>

            <!-- Baris 4: Alasan / Keterangan Penyesuaian -->
            <div>
                <label for="alasan" class="block text-xs font-semibold text-slate-600 mb-1">
                    Alasan / Keterangan Penyesuaian <span class="text-rose-500">*</span>
                </label>
                <textarea id="alasan" name="alasan" rows="3" required
                          placeholder="Jelaskan alasan selisih stok (misal: 'Ditemukan 2 butir retak saat pemeriksaan rak toko' atau 'Hasil hitung fisik opname bulanan sesuai')."
                          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">{{ old('alasan') }}</textarea>
                @error('alasan') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <!-- Baris 5: Tombol Aksi di Sebelah Kanan (Batal & Simpan Penyesuaian) -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('dashboard') }}"
                   class="px-5 py-2.5 border border-slate-200 hover:border-slate-300 text-slate-600 hover:text-slate-800 hover:bg-slate-50 text-sm font-semibold rounded-xl transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white text-sm font-bold rounded-xl shadow-md shadow-amber-500/20 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    <span>Simpan Penyesuaian</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    function updateStockCalculation() {
        const select = document.getElementById('product_id');
        const selectedOption = select.options[select.selectedIndex];
        const systemStock = selectedOption ? parseInt(selectedOption.getAttribute('data-stock') || '0', 10) : 0;
        const unit = selectedOption ? (selectedOption.getAttribute('data-unit') || 'butir') : 'butir';

        document.getElementById('display_system_stock').textContent = new Intl.NumberFormat('id-ID').format(systemStock);
        document.getElementById('display_unit_1').textContent = unit;
        document.getElementById('display_unit_2').textContent = unit;

        const physicalInput = document.getElementById('stok_fisik');
        const badge = document.getElementById('difference_badge');

        if (!select.value || physicalInput.value === '') {
            badge.className = 'px-3 py-1.5 text-xs font-bold rounded-xl bg-slate-200 text-slate-600 inline-flex items-center';
            badge.textContent = 'Pilih varian & isi stok fisik';
            return;
        }

        const physicalStock = parseInt(physicalInput.value, 10);
        if (isNaN(physicalStock)) return;

        const diff = physicalStock - systemStock;

        if (diff === 0) {
            badge.className = 'px-3 py-1.5 text-xs font-bold rounded-xl bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center';
            badge.innerHTML = `<i class="fa-solid fa-circle-check mr-1.5 text-emerald-600"></i> Cocok (Selisih 0 ${unit})`;
        } else if (diff < 0) {
            badge.className = 'px-3 py-1.5 text-xs font-bold rounded-xl bg-rose-100 text-rose-800 border border-rose-300 inline-flex items-center';
            badge.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1.5 text-rose-600"></i> Selisih Kurang (${diff} ${unit})`;
        } else {
            badge.className = 'px-3 py-1.5 text-xs font-bold rounded-xl bg-blue-100 text-blue-800 border border-blue-300 inline-flex items-center';
            badge.innerHTML = `<i class="fa-solid fa-arrow-trend-up mr-1.5 text-blue-600"></i> Selisih Lebih (+${diff} ${unit})`;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateStockCalculation();
    });
</script>
@endpush
