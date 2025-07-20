@props(['label', 'icon', 'items'])

@php
    $isActive = collect($items)->contains(fn($item) => request()->routeIs($item['route']));
@endphp

<div x-data='{ "expanded": @json($isActive) }' class="text-white">
    <button
        @click="expanded = !expanded"
        class="w-full flex justify-between items-center px-3 py-2 rounded transition
               {{ $isActive ? 'bg-white/20' : 'hover:bg-white/10' }}">
        <span class="flex items-center gap-2">
            <span>{{ $icon }}</span>
            <span>{{ $label }}</span>
        </span>
        <span x-text="expanded ? '▲' : '▼'" class="text-xs"></span>
    </button>

    <ul x-show="expanded" x-transition x-cloak class="pl-6 mt-2 space-y-1">
        @foreach ($items as $item)
            <li>
                <a href="{{ route($item['route']) }}"
                   class="block px-3 py-1 rounded transition
                          {{ request()->routeIs($item['route'])
                              ? 'bg-white/20 text-white font-semibold'
                              : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
