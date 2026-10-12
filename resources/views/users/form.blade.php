@extends('layouts.app')

@section('title', $user->exists ? 'Ubah Pengguna' : 'Tambah Pengguna')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm card-shadow max-w-2xl">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">
                {{ $user->exists ? "Ubah Akun {$user->username}" : 'Tambah Akun Pengguna' }}
            </h3>
            @if ($user->exists)
                <p class="text-xs text-slate-500 mt-0.5">Biarkan kolom password kosong jika tidak ingin menggantinya.</p>
            @endif
        </div>

        <form method="POST"
              action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}"
              class="p-5 space-y-4">
            @csrf
            @if ($user->exists)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block text-xs font-semibold text-slate-600 mb-1">Username</label>
                    <input id="username" name="username" type="text" required autocomplete="off"
                           value="{{ old('username', $user->username) }}" placeholder="petugas"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-mono focus:outline-none focus:border-indigo-500">
                    @error('username') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="role" class="block text-xs font-semibold text-slate-600 mb-1">Peran</label>
                    <select id="role" name="role" required
                            {{ $user->exists && $user->is(auth()->user()) ? 'disabled' : '' }}
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed">
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}"
                                @selected(old('role', $user->role?->value) === $role->value)>
                                {{ $role->label() }} ({{ $role->value }})
                            </option>
                        @endforeach
                    </select>
                    {{-- Disabled select tidak dikirim browser; kirim value via hidden input. --}}
                    @if ($user->exists && $user->is(auth()->user()))
                        <input type="hidden" name="role" value="{{ $user->role->value }}">
                        <p class="mt-1 text-[10px] text-amber-600 font-medium">Anda tidak bisa mengubah peran akun sendiri.</p>
                    @endif
                    @error('role') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap</label>
                <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}"
                       placeholder="Siti Rahma"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email', $user->email) }}"
                       placeholder="petugas@telur.com"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-600 mb-1">
                        Password {{ $user->exists ? '(opsional)' : '' }}
                    </label>
                    <input id="password" name="password" type="password" autocomplete="new-password"
                           {{ $user->exists ? '' : 'required' }} placeholder="Minimal 8 karakter"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-600 mb-1">Ulangi Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                           {{ $user->exists ? '' : 'required' }} placeholder="Ulangi password"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500">
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100">
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 select-none">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $user->exists ? $user->is_active : true))
                           {{ $user->exists && $user->is(auth()->user()) ? 'disabled' : '' }}
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-60">
                    Status Akun Aktif
                </label>
                @if ($user->exists && $user->is(auth()->user()))
                    <input type="hidden" name="is_active" value="1">
                    <p class="mt-1 text-[10px] text-amber-600 font-medium">Anda tidak bisa menonaktifkan akun sendiri.</p>
                @else
                    <p class="mt-0.5 text-[11px] text-slate-400">Akun yang dinonaktifkan tidak dapat masuk ke dalam sistem.</p>
                @endif
                @error('is_active') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl shadow-md transition-colors">
                    {{ $user->exists ? 'Simpan Perubahan' : 'Simpan Pengguna' }}
                </button>
                <a href="{{ route('users.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Batal</a>
            </div>
        </form>
    </div>
@endsection
