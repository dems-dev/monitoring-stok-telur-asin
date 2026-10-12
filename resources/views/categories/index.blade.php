@extends('layouts.app')

@section('title', 'Master Data Kategori')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Kategori Produk Telur Asin</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $categories->count() }} kategori terdaftar</p>
            </div>

            <a href="{{ route('categories.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-md transition-colors">
                <i class="fa-solid fa-plus"></i>
                Tambah Kategori
            </a>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3 px-4 text-left font-bold">#</th>
                        <th class="py-3 px-4 text-left font-bold">Nama Kategori</th>
                        <th class="py-3 px-4 text-center font-bold">Jumlah Varian Produk</th>
                        <th class="py-3 px-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($categories as $index => $category)
                        <tr class="hover:bg-slate-50">
                            <td class="py-3 px-4 text-xs font-mono text-slate-400">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $category->name }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ $category->products_count }} produk
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('categories.edit', $category) }}" title="Ubah"
                                   class="inline-flex p-2 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <form method="POST" action="{{ route('categories.destroy', $category) }}" class="inline"
                                      data-confirm="Hapus kategori {{ $category->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus"
                                            class="inline-flex p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-8 text-slate-400">Belum ada kategori terdaftar</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
