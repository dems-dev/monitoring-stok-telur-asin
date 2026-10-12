<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') &middot; {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-800 min-h-screen flex flex-col antialiased">
@php
    $user = auth()->user();
    $role = $user->role;
    $lowStockCount = \App\Models\Product::lowStock()->count();
@endphp

<header class="bg-white border-b border-slate-200 sticky top-0 z-30 no-print">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center text-white shadow-md shadow-amber-200">
                    <i class="fa-solid fa-egg text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg text-slate-900 leading-tight">SIM-STOK TELUR ASIN</h1>
                    <p class="text-xs text-slate-500 font-medium hidden sm:block">Sistem Monitoring Persediaan Barang Web</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('products.index') }}" title="Produk dengan stok rendah"
                   class="relative p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <i class="fa-regular fa-bell text-xl"></i>
                    @if ($lowStockCount > 0)
                        <span class="absolute top-1 right-1 min-w-4 h-4 px-1 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-sm">
                            {{ $lowStockCount }}
                        </span>
                    @endif
                </a>

                <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

                <div class="flex items-center space-x-3 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                    <div class="w-8 h-8 rounded-lg text-white flex items-center justify-center text-xs font-bold shadow-sm {{ $role->color() }}">
                        {{ Str::substr($role->value, 0, 3) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="text-xs font-bold text-slate-800">{{ $user->name }}</div>
                        <div class="text-[10px] font-semibold text-slate-500">{{ $role->label() }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Keluar / Logout"
                            class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-colors border border-transparent hover:border-rose-200">
                        <i class="fa-solid fa-right-from-bracket text-lg"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<div class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col md:flex-row gap-6">
    <aside class="w-full md:w-64 shrink-0 no-print">
        <nav class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm space-y-1.5 md:sticky md:top-22">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Menu Navigasi</div>

            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')"
                        icon="fa-chart-pie" label="Dashboard Analytics" />

            <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')"
                        icon="fa-boxes-stacked" label="Master Data Stok"
                        :badge="\App\Models\Product::count()" />

            @if ($role->canManageMasterData())
                <x-nav-link :href="route('categories.index')" :active="request()->routeIs('categories.*')"
                            icon="fa-tags" icon-color="text-amber-600" label="Kategori Produk" />

                <x-nav-link :href="route('units.index')" :active="request()->routeIs('units.*')"
                            icon="fa-scale-balanced" icon-color="text-amber-600" label="Satuan (Units)" />

                <x-nav-link :href="route('suppliers.index')" :active="request()->routeIs('suppliers.*')"
                            icon="fa-truck-field" icon-color="text-amber-600" label="Supplier / Mitra" />
            @endif

            @if ($role->canRecordMutation())
                <x-nav-link :href="route('stock.masuk.create')" :active="request()->routeIs('stock.masuk.*')"
                            icon="fa-file-circle-plus" icon-color="text-emerald-600" label="Barang Masuk" />

                <x-nav-link :href="route('stock.keluar.create')" :active="request()->routeIs('stock.keluar.*')"
                            icon="fa-file-circle-minus" icon-color="text-rose-600" label="Barang Keluar" />
            @endif

            <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')"
                        icon="fa-clock-rotate-left" icon-color="text-amber-600" label="Riwayat &amp; Laporan" />

            @if ($role->canManageMasterData())
                <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')"
                            icon="fa-users-gear" icon-color="text-indigo-600" label="Kelola User System" />
            @endif
        </nav>
    </aside>

    <main class="flex-1 min-w-0">
        <x-role-banner :role="$role" :name="$user->name" />

        @if (session('status'))
            <div class="mb-6 p-4 rounded-2xl border border-emerald-200 bg-emerald-50 text-sm font-medium text-emerald-800 flex items-start gap-3 no-print">
                <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-2xl border border-rose-200 bg-rose-50 text-sm text-rose-800 no-print">
                <div class="font-bold mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    Periksa kembali isian berikut
                </div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

@stack('scripts')
</body>
</html>
