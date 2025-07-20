@props(['icon', 'label', 'items'])

<li x-data="{ open: false }" class="py-1">
    <div @click="open = !open" class="flex items-center justify-between px-3 py-2 hover:bg-white/10 rounded cursor-pointer">
        <div class="flex items-center space-x-2">
            <span>{{ $icon }}</span>
            <span>{{ $label }}</span>
        </div>
        <span x-text="open ? '▲' : '▼'"></span>
    </div>
    <ul x-show="open" x-transition class="ml-6 mt-2 space-y-1">
        @foreach ($items as $item)
            <li>
                <a href="{{ route($item['route']) }}" class="block hover:underline">
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</li>
