@extends('layouts.guest')

@section('title', 'Lupa Password')
@section('subtitle', 'Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat password baru.')

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                   autocomplete="email" placeholder="nama@contoh.com"
                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-sm transition-colors shadow-md">
            Kirim Tautan Reset
        </button>
    </form>

    <p class="mt-4 text-center text-xs text-slate-500">
        <a href="{{ route('login') }}" class="font-semibold text-amber-600 hover:text-amber-700">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke halaman masuk
        </a>
    </p>
@endsection
