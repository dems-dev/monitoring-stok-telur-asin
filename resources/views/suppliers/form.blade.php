@extends('layouts.app')

@section('title', $supplier->exists ? 'Ubah Supplier' : 'Tambah Supplier')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow max-w-xl">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">
                {{ $supplier->exists ? "Ubah Supplier: {$supplier->name}" : 'Tambah Supplier Baru' }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">Data peternak bebek atau mitra pemasok telur asin.</p>
        </div>

        <form method="POST"
              action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
              class="p-5 space-y-4">
            @csrf
            @if ($supplier->exists)
                @method('PUT')
            @endif

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-600 mb-1">Nama Supplier / Mitra</label>
                <input id="name" name="name" type="text" required autofocus
                       value="{{ old('name', $supplier->name) }}" placeholder="Contoh: Peternak Bebek Brebes"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-600 mb-1">Nomor Telepon / WhatsApp</label>
                <input id="phone" name="phone" type="text"
                       value="{{ old('phone', $supplier->phone) }}" placeholder="081234567890"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="address" class="block text-xs font-semibold text-slate-600 mb-1">Alamat</label>
                <textarea id="address" name="address" rows="3" placeholder="Alamat kandang / gudang supplier"
                          class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">{{ old('address', $supplier->address) }}</textarea>
                @error('address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-md transition-colors">
                    {{ $supplier->exists ? 'Simpan Perubahan' : 'Simpan Supplier' }}
                </button>
                <a href="{{ route('suppliers.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Batal</a>
            </div>
        </form>
    </div>
@endsection
