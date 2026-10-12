@extends('layouts.app')

@section('title', $product->exists ? 'Ubah Produk' : 'Tambah Produk')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow max-w-2xl">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">
                {{ $product->exists ? "Ubah Data Produk: {$product->name}" : 'Tambah Varian Telur Asin' }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Stok produk dikelola secara otomatis melalui transaksi mutasi (Barang Masuk / Keluar).
            </p>
        </div>

        <form method="POST"
              action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
              class="p-5 space-y-4">
            @csrf
            @if ($product->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="sku" class="block text-xs font-semibold text-slate-600 mb-1">SKU</label>
                    <input id="sku" name="sku" type="text" required value="{{ old('sku', $product->sku) }}"
                           placeholder="TLR-ORIG-01"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:border-amber-500">
                    @error('sku') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="category_id" class="block text-xs font-semibold text-slate-600 mb-1">Kategori</label>
                    <select id="category_id" name="category_id" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="unit_id" class="block text-xs font-semibold text-slate-600 mb-1">Satuan (Unit)</label>
                    @if ($unitLocked)
                        {{-- Select disabled tidak ikut terkirim; nilainya tetap divalidasi di ProductRequest. --}}
                        <input type="hidden" name="unit_id" value="{{ $product->unit_id }}">
                    @endif
                    <select id="unit_id" @unless ($unitLocked) name="unit_id" @endunless required @disabled($unitLocked)
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 disabled:opacity-60 disabled:cursor-not-allowed">
                        <option value="">-- Pilih Satuan --</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected(old('unit_id', $product->unit_id) == $unit->id)>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($unitLocked)
                        <p class="mt-1 text-[11px] text-slate-500">
                            <i class="fa-solid fa-lock mr-0.5"></i> Terkunci: produk sudah memiliki riwayat mutasi.
                        </p>
                    @endif
                    @error('unit_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-600 mb-1">Nama Produk</label>
                <input id="name" name="name" type="text" required value="{{ old('name', $product->name) }}"
                       placeholder="Telur Asin Original Rebus"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="price" class="block text-xs font-semibold text-slate-600 mb-1">Harga Jual (Rp)</label>
                    <input id="price" name="price" type="number" min="0" step="1" required
                           value="{{ old('price', $product->price ?? 0) }}"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    @error('price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="purchase_price" class="block text-xs font-semibold text-slate-600 mb-1">
                        Harga Beli / Modal (Rp)
                    </label>
                    <input id="purchase_price" name="purchase_price" type="number" min="1" step="1" required
                           value="{{ old('purchase_price', $product->purchase_price ?: null) }}"
                           placeholder="Contoh: 2800"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    @error('purchase_price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="min_stock" class="block text-xs font-semibold text-slate-600 mb-1">Batas Minimum Stok</label>
                    <input id="min_stock" name="min_stock" type="number" min="0" step="1" required
                           value="{{ old('min_stock', $product->min_stock ?? 0) }}"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    @error('min_stock') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="nearest_expiry_date" class="block text-xs font-semibold text-slate-600 mb-1">
                        Tanggal Kedaluwarsa Terdekat <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input id="nearest_expiry_date" name="nearest_expiry_date" type="date"
                           value="{{ old('nearest_expiry_date', $product->nearest_expiry_date?->toDateString()) }}"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    @error('nearest_expiry_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Informasi Stok Fisik (tidak diedit langsung di form) --}}
            <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl text-xs text-amber-900">
                @if ($product->exists)
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-bold">Stok Saat Ini:</span>
                            <span class="font-mono font-bold text-amber-800">{{ number_format($product->stock, 0, ',', '.') }}</span>
                            <span>{{ $product->unit?->name ?? 'butir' }}</span>
                        </div>
                        <span class="text-[11px] text-amber-700">Perubahan stok dilakukan via menu transaksi.</span>
                    </div>
                @else
                    <p class="text-[11px] text-amber-800">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        Stok awal otomatis bernilai <b>0</b>. Penambahan stok pertama dilakukan melalui menu <b>Barang Masuk</b>.
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-md transition-colors">
                    {{ $product->exists ? 'Simpan Perubahan' : 'Simpan Produk' }}
                </button>
                <a href="{{ route('products.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">
                    Batal
                </a>
            </div>
        </form>
    </div>
@endsection
