@extends('layouts.app')

@section('title', $product->exists ? 'Ubah Produk' : 'Tambah Produk')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow max-w-2xl">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">
                {{ $product->exists ? 'Ubah Data Produk' : 'Tambah Varian Telur Asin' }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Stok yang diisi di sini adalah penyesuaian langsung, bukan lewat mutasi masuk/keluar.
            </p>
        </div>

        <form method="POST"
              action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
              class="p-5 space-y-4">
            @csrf
            @if ($product->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="sku" class="block text-xs font-semibold text-slate-600 mb-1">SKU</label>
                    <input id="sku" name="sku" type="text" required value="{{ old('sku', $product->sku) }}"
                           placeholder="TLR-ORIG-01"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:border-amber-500">
                    @error('sku') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="category" class="block text-xs font-semibold text-slate-600 mb-1">Kategori</label>
                    <input id="category" name="category" type="text" required list="categoryOptions"
                           value="{{ old('category', $product->category) }}" placeholder="Original"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    <datalist id="categoryOptions">
                        @foreach ($categories as $category)
                            <option value="{{ $category }}"></option>
                        @endforeach
                    </datalist>
                    @error('category') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-600 mb-1">Nama Produk</label>
                <input id="name" name="name" type="text" required value="{{ old('name', $product->name) }}"
                       placeholder="Telur Asin Original Rebus"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="price" class="block text-xs font-semibold text-slate-600 mb-1">Harga (Rp)</label>
                    <input id="price" name="price" type="number" min="0" step="1" required
                           value="{{ old('price', $product->price ?? 0) }}"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    @error('price') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="stock" class="block text-xs font-semibold text-slate-600 mb-1">Stok (butir)</label>
                    <input id="stock" name="stock" type="number" min="0" step="1" required
                           value="{{ old('stock', $product->stock ?? 0) }}"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    @error('stock') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="min_stock" class="block text-xs font-semibold text-slate-600 mb-1">Batas Minimum</label>
                    <input id="min_stock" name="min_stock" type="number" min="0" step="1" required
                           value="{{ old('min_stock', $product->min_stock ?? 0) }}"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    @error('min_stock') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
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
