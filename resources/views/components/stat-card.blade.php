@props(['label', 'value', 'unit' => null, 'icon', 'tone' => 'slate'])

@php
    $tones = [
        'amber' => 'bg-amber-50 text-amber-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm card-shadow">
    <div class="flex items-start justify-between">
        <div>
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</div>
            <div class="mt-2 text-2xl font-bold text-slate-900">
                {{ $value }}
                @if ($unit)
                    <span class="text-xs font-normal text-slate-500">{{ $unit }}</span>
                @endif
            </div>
        </div>
        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg {{ $tones[$tone] }}">
            <i class="fa-solid {{ $icon }}"></i>
        </div>
    </div>
</div>
