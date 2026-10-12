<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') &middot; {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-slate-800 antialiased">
<div class="fixed inset-0 overflow-y-auto bg-slate-900 flex items-center justify-center p-4 bg-radial-[ellipse_at_top] from-amber-900/20 via-slate-900 to-slate-950">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 shadow-2xl border border-slate-100">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-amber-500 text-white flex items-center justify-center mx-auto mb-4 shadow-lg shadow-amber-500/30">
                <i class="fa-solid fa-egg text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900">SIM-STOK TELUR ASIN</h2>
            <p class="text-sm text-slate-500 mt-1">@yield('subtitle')</p>
        </div>

        @if (session('status'))
            <div class="mb-4 text-xs text-emerald-800 bg-emerald-50 border border-emerald-200 p-2.5 rounded-xl font-medium">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </div>
</div>
</body>
</html>
