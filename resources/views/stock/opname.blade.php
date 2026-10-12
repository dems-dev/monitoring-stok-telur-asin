@extends('layouts.app')

@section('title', 'Stock Opname')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow max-w-2xl">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Stock Opname &amp; Penyesuaian Fisik</h3>
                <p class="text-xs text-slate-500 mt-0.5">Sinkronisasi stok sistem dengan hasil perhitungan fisik riil di gudang/toko.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock.opname.store') }}" class="p-5 space-y-4">
            @csrf

            <div>
                <label for="logged_date" class="block text-xs font-semibold text-slate-600 mb-1">Tanggal Pemeriksaan Fisik</label>
                <input id="logged_date" name="logged_date" type="date" required
                       max="{{ now()->toDateString() }}"
                       value="{{ old('logged_date', now()->toDateString()) }}"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                @error('logged_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="product_id" class="block text-xs font-semibold text-slate-600 mb-1">Pilih Varian Telur</label>
                <select id="product_id" name="product_id" required onchange="updateStockCalculation()"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    <option value="" data-stock="0" data-unit="butir">-- Pilih varian untuk dihitung --</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                                data-stock="{{ $product->stock }}"
                                data-unit="{{ $product->unit?->name ?? 'butir' }}"
                                @selected(old('product_id') == $product->id)>
                            {{ $product->sku }} &mdash; {{ $product->name }} (Stok Sistem: {{ number_format($product->stock, 0, ',', '.') }} {{ $product->unit?->name ?? 'butir' }})
                        </option>
                    @endforeach
                </select>
                @error('product_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <!-- Perbandingan Stok Sistem vs Fisik -->
            <div class="p-4 bg-slate-50/80 rounded-xl border border-slate-200/80 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="block text-xs font-semibold text-slate-500 mb-1">Stok Sistem Saat Ini</span>
                        <div class="flex items-center gap-2">
                            <span id="display_system_stock" class="text-xl font-extrabold text-slate-700">0</span>
                            <span id="display_unit_1" class="text-xs text-slate-500 font-medium">butir</span>
                        </div>
                    </div>

                    <div>
                        <label for="stok_fisik" class="block text-xs font-semibold text-slate-700 mb-1">
                            Stok Fisik Ditemukan <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input id="stok_fisik" name="stok_fisik" type="number" min="0" step="1" required
                                   value="{{ old('stok_fisik') }}" placeholder="0" oninput="updateStockCalculation()"
                                   class="w-full px-3.5 py-2 bg-white border border-slate-300 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:border-amber-500">
                        </div>
                        @error('stok_fisik') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Indikator Selisih Otomatis -->
                <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500">Hasil Selisih Stok:</span>
                    <span id="difference_badge" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-slate-200 text-slate-600">
                        Pilih produk dan masukkan fisik
                    </span>
                </div>
            </div>

            <div>
                <label for="alasan" class="block text-xs font-semibold text-slate-600 mb-1">
                    Alasan / Keterangan Penyesuaian <span class="text-rose-500">*</span>
                </label>
                <textarea id="alasan" name="alasan" rows="3" required
                          placeholder="Jelaskan alasan selisih stok (misal: 'Ditemukan 2 butir retak saat pemeriksaan berkala' atau 'Hasil penghitungan fisik opname bulanan sesuai')."
                          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">{{ old('alasan') }}</textarea>
                @error('alasan') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('dashboard') }}"
                   class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-bold rounded-xl transition-colors">
                    Batal
                </a>
                <button type="submit"
                        style="background-color: #f59e0b; color: #ffffff;"
                        class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-md transition-colors flex items-center gap-1.5">
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

        const physicalInput = document.getElementById('stok_fisik');
        const badge = document.getElementById('difference_badge');

        if (!select.value || physicalInput.value === '') {
            badge.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-slate-200 text-slate-600';
            badge.textContent = 'Pilih produk dan masukkan fisik';
            return;
        }

        const physicalStock = parseInt(physicalInput.value, 10);
        if (isNaN(physicalStock)) return;

        const diff = physicalStock - systemStock;

        if (diff === 0) {
            badge.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200';
            badge.innerHTML = `<i class="fa-solid fa-circle-check mr-1"></i> Cocok / Sesuai (Selisih 0 ${unit})`;
        } else if (diff < 0) {
            badge.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-rose-100 text-rose-800 border border-rose-200';
            badge.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1"></i> Selisih Kurang (${diff} ${unit})`;
        } else {
            badge.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-100 text-blue-800 border border-blue-200';
            badge.innerHTML = `<i class="fa-solid fa-arrow-trend-up mr-1"></i> Selisih Lebih (+${diff} ${unit})`;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateStockCalculation();
    });
</script>
@endpush
