@props(['label', 'icon', 'items'])

@php
    $items = json_decode($items, true);
    $isActive = false;
    foreach ($items as $item) {
        if (request()->routeIs($item['route'])) {
            $isActive = true;
            break;
        }
    }
@endphp

<div x-data="{ expanded: {{ $isActive ? 'true' : 'false' }} }">
    <button
        @click="expanded = !expanded"
        class="w-full flex justify-between items-center px-3 py-2 rounded
               {{ $isActive ? 'bg-green-700 text-white' : 'bg-gray-700 text-white hover:bg-gray-600' }}"
    >
        <span class="flex items-center gap-2">
            <span>{{ $icon }}</span>
            {{ $label }}
        </span>
        <span x-text="expanded ? '▲' : '▼'" class="text-xs"></span>
    </button>

    <ul x-show="expanded" x-cloak x-transition class="pl-6 mt-2 space-y-1">
        @foreach ($items as $item)
            <li>
                <a href="{{ route($item['route']) }}"
                   class="block px-3 py-1 rounded transition
                   {{ request()->routeIs($item['route'])
                        ? 'bg-green-600 text-white'
                        : 'text-gray-200 hover:text-white hover:bg-gray-600' }}">
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
