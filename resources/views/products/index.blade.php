@extends('layouts.app')

@section('title', 'Master Data Stok')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Katalog Varian Telur Asin</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $products->total() }} produk terdaftar</p>
            </div>

            <div class="flex items-center gap-2 no-print">
                <form method="GET" action="{{ route('products.index') }}" class="flex items-center gap-2">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama, SKU, kategori"
                           class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm w-48 focus:outline-none focus:border-amber-500">
                    <button type="submit" class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>

                @can('create', App\Models\Product::class)
                    <a href="{{ route('products.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-md transition-colors">
                        <i class="fa-solid fa-plus"></i>
                        Tambah Produk
                    </a>
                @endcan
            </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3 px-4 text-left font-bold">SKU</th>
                        <th class="py-3 px-4 text-left font-bold">Nama Produk</th>
                        <th class="py-3 px-4 text-left font-bold">Kategori</th>
                        <th class="py-3 px-4 text-right font-bold">Harga</th>
                        <th class="py-3 px-4 text-right font-bold">Stok</th>
                        <th class="py-3 px-4 text-right font-bold">Min</th>
                        <th class="py-3 px-4 text-left font-bold">Status</th>
                        @canany(['update', 'delete'], App\Models\Product::class)
                            <th class="py-3 px-4 text-right font-bold no-print">Aksi</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 font-mono text-xs text-slate-500">{{ $product->sku }}</td>
                            <td class="py-3 px-4 font-medium text-slate-800">{{ $product->name }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-100 text-slate-600">
                                    {{ $product->category }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right text-slate-600">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right font-bold text-slate-800">{{ number_format($product->stock, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right text-xs text-slate-400">{{ number_format($product->min_stock, 0, ',', '.') }}</td>
                            <td class="py-3 px-4">
                                @if ($product->isLowStock())
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700">MENIPIS</span>
                                @else
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700">AMAN</span>
                                @endif
                            </td>
                            @canany(['update', 'delete'], App\Models\Product::class)
                                <td class="py-3 px-4 text-right whitespace-nowrap no-print">
                                    @can('update', $product)
                                        <a href="{{ route('products.edit', $product) }}" title="Ubah"
                                           class="inline-flex p-2 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                    @endcan

                                    @can('delete', $product)
                                        <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline"
                                              data-confirm="Hapus produk {{ $product->name }}? Riwayat mutasinya tetap tersimpan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus"
                                                    class="inline-flex p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            @endcanany
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-6 text-slate-400">
                                {{ $search !== '' ? 'Tidak ada produk yang cocok dengan pencarian.' : 'Belum ada produk terdaftar.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 no-print">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection
