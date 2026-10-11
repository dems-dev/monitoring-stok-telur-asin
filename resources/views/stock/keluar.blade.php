@extends('layouts.app')

@section('title', 'Barang Keluar')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow max-w-2xl">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-file-circle-minus"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Catat Barang Keluar</h3>
                <p class="text-xs text-slate-500 mt-0.5">Penjualan, distribusi, atau barang rusak. Stok dikurangi otomatis.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('stock.keluar.store') }}" class="p-5 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="logged_date" class="block text-xs font-semibold text-slate-600 mb-1">Tanggal</label>
                    <input id="logged_date" name="logged_date" type="date" required
                           max="{{ now()->toDateString() }}"
                           value="{{ old('logged_date', now()->toDateString()) }}"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-rose-500">
                    @error('logged_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="amount" class="block text-xs font-semibold text-slate-600 mb-1">Jumlah (butir)</label>
                    <input id="amount" name="amount" type="number" min="1" step="1" required
                           value="{{ old('amount') }}" placeholder="50"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-rose-500">
                    @error('amount') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="product_id" class="block text-xs font-semibold text-slate-600 mb-1">Varian Telur</label>
                    <select id="product_id" name="product_id" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-rose-500">
                        <option value="">-- Pilih varian --</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                {{ $product->sku }} &mdash; {{ $product->name }} (stok {{ number_format($product->stock, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                    <select id="status" name="status" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-rose-500">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="source_or_target" class="block text-xs font-semibold text-slate-600 mb-1">Tujuan / Nama Pembeli</label>
                <input id="source_or_target" name="source_or_target" type="text" required
                       value="{{ old('source_or_target') }}" placeholder="Toko Oleh-Oleh Berkah / Konsumen"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-rose-500">
                @error('source_or_target') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="note" class="block text-xs font-semibold text-slate-600 mb-1">Catatan (opsional)</label>
                <textarea id="note" name="note" rows="2" placeholder="Pengiriman penjualan"
                          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-rose-500">{{ old('note') }}</textarea>
                @error('note') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-sm font-bold rounded-xl shadow-md transition-colors">
                    Simpan Barang Keluar
                </button>
                <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Batal</a>
            </div>
        </form>
    </div>
@endsection
