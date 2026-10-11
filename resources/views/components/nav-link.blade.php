@props([
    'href',
    'active' => false,
    'icon',
    'iconColor' => null,
    'label',
    'badge' => null,
])

<a href="{{ $href }}"
   @class([
       'w-full flex items-center justify-between px-4 py-3 rounded-xl font-medium text-sm transition-all',
       'bg-amber-50 text-amber-900 border border-amber-200' => $active,
       'text-slate-600 hover:bg-slate-100 border border-transparent' => ! $active,
   ])>
    <span class="flex items-center space-x-3">
        <i class="fa-solid {{ $icon }} w-5 text-center text-lg {{ $active ? 'text-amber-600' : ($iconColor ?? '') }}"></i>
        <span>{!! $label !!}</span>
    </span>

    @if (! is_null($badge))
        <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded-full font-semibold">{{ $badge }}</span>
    @endif
</a>
