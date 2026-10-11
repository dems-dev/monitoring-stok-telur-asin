@props(['role', 'name'])

@php
    $config = match ($role) {
        \App\Enums\Role::OWNER => [
            'wrapper' => 'border-amber-200 bg-amber-50/80',
            'icon' => 'bg-amber-500',
            'glyph' => 'fa-user-tie',
            'title' => "Selamat Datang, {$name} (Pemilik Toko)",
            'desc' => 'Mode Ringkasan Eksekutif: Akses monitoring stok realtime, analisis visual, dan cetak laporan.',
            'badge' => 'bg-amber-200 text-amber-900',
            'badgeText' => 'Akses Pemilik (Read-Only)',
        ],
        \App\Enums\Role::PETUGAS => [
            'wrapper' => 'border-emerald-200 bg-emerald-50/80',
            'icon' => 'bg-emerald-600',
            'glyph' => 'fa-clipboard-user',
            'title' => "Selamat Datang, {$name} (Petugas Operasional)",
            'desc' => 'Mode Operasional Gudang: Pencatatan mutasi barang masuk & keluar, serta pengawasan stok.',
            'badge' => 'bg-emerald-200 text-emerald-900',
            'badgeText' => 'Akses Petugas Operasional',
        ],
        \App\Enums\Role::ADMIN => [
            'wrapper' => 'border-slate-200 bg-white shadow-sm',
            'icon' => 'bg-slate-800',
            'glyph' => 'fa-user-shield',
            'title' => "Selamat Datang, {$name} (Administrator)",
            'desc' => 'Akses Penuh (Full Control): Kelola Master Data, entri transaksi, hingga manajemen user.',
            'badge' => 'bg-slate-100 text-slate-800 border border-slate-200',
            'badgeText' => 'Super Administrator',
        ],
    };
@endphp

<div class="mb-6 p-4 rounded-2xl border flex items-center justify-between no-print {{ $config['wrapper'] }}">
    <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl text-white flex items-center justify-center text-lg font-bold shadow-sm {{ $config['icon'] }}">
            <i class="fa-solid {{ $config['glyph'] }}"></i>
        </div>
        <div>
            <h3 class="text-sm font-bold text-slate-900">{{ $config['title'] }}</h3>
            <p class="text-xs text-slate-600 mt-0.5">{{ $config['desc'] }}</p>
        </div>
    </div>
    <span class="hidden sm:inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $config['badge'] }}">
        {{ $config['badgeText'] }}
    </span>
</div>
