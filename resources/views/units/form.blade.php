@extends('layouts.app')

@section('title', $unit->exists ? 'Ubah Satuan' : 'Tambah Satuan')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow max-w-xl">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">
                {{ $unit->exists ? "Ubah Satuan: {$unit->name}" : 'Tambah Satuan Baru' }}
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">Satuan kuantitas produk, misalnya butir, pack, atau karton.</p>
        </div>

        <form method="POST"
              action="{{ $unit->exists ? route('units.update', $unit) : route('units.store') }}"
              class="p-5 space-y-4">
            @csrf
            @if ($unit->exists)
                @method('PUT')
            @endif

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-600 mb-1">Nama Satuan</label>
                <input id="name" name="name" type="text" required autofocus
                       value="{{ old('name', $unit->name) }}" placeholder="Contoh: butir, pack, box"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-md transition-colors">
                    {{ $unit->exists ? 'Simpan Perubahan' : 'Simpan Satuan' }}
                </button>
                <a href="{{ route('units.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Batal</a>
            </div>
        </form>
    </div>
@endsection
