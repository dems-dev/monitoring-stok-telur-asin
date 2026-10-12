<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk &middot; {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-slate-800 antialiased">
<div class="fixed inset-0 bg-slate-900 flex items-center justify-center p-4 bg-radial-[ellipse_at_top] from-amber-900/20 via-slate-900 to-slate-950">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 shadow-2xl border border-slate-100">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-amber-500 text-white flex items-center justify-center mx-auto mb-4 shadow-lg shadow-amber-500/30">
                <i class="fa-solid fa-egg text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900">SIM-STOK TELUR ASIN</h2>
            <p class="text-sm text-slate-500 mt-1">Masuk untuk mengakses sistem monitoring persediaan.</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="username" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                       autocomplete="username" placeholder="admin / petugas / owner"
                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
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
            Lupa password? <span class="font-semibold text-slate-700">Hubungi admin</span> untuk reset.
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
    </div>
</div>
</body>
</html>
