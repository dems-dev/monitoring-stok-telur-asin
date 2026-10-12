@extends('layouts.guest')

@section('title', 'Masuk')
@section('subtitle', 'Masuk untuk mengakses sistem monitoring persediaan.')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="username" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Username</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                   autocomplete="username" placeholder="admin / petugas / owner"
                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">
                    Lupa password?
                </a>
            </div>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   placeholder="Masukkan password"
                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
        </div>

        @error('username')
            <div class="text-xs text-rose-600 bg-rose-50 border border-rose-200 p-2.5 rounded-xl font-medium">
                {{ $message }}
            </div>
        @enderror

        <label class="flex items-center gap-2 text-xs text-slate-600 select-none">
            <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
            Ingat saya di perangkat ini
        </label>

        <button type="submit"
                class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-sm transition-colors shadow-md">
            Masuk ke Sistem
        </button>
    </form>

    <p class="mt-4 text-center text-xs text-slate-500">
        Tidak punya akses ke email? <span class="font-semibold text-slate-700">Hubungi admin</span> untuk reset password.
    </p>

    @if (app()->environment('local'))
        {{-- Petunjuk akun contoh hanya tampil di lingkungan lokal. --}}
        <div class="mt-6 pt-5 border-t border-slate-100">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Akun contoh (hanya lokal)</p>
            <ul class="text-xs text-slate-500 space-y-1 font-mono">
                <li>admin / admin123 - Super Admin</li>
                <li>petugas / petugas123 - Petugas Gudang</li>
                <li>owner / owner123 - Pemilik Toko (read-only)</li>
            </ul>
        </div>
    @endif
@endsection
