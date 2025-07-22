@props(['menu'])

<aside
    x-show="sidebarOpen"
    x-transition:enter="transition duration-300 ease-out"
    x-transition:enter-start="-translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition duration-200 ease-in"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="-translate-x-full opacity-0"
    class="w-64 h-full bg-[var(--tema)] text-white overflow-y-auto"
>
    <nav class="p-4 space-y-2 text-sm">
        @foreach ($menu as $menuItem)
            <div x-data="{ expanded: false }">
                <button @click="expanded = !expanded"
                        class="flex items-center justify-between w-full px-3 py-2 text-white rounded bg-white/10 hover:bg-white/20">
                    <span class="flex items-center gap-2">
                        <span>{{ $menuItem['icon'] }}</span>
                        {{ $menuItem['label'] }}
                    </span>
                    <span x-text="expanded ? '▲' : '▼'" class="text-xs"></span>
                </button>

                @if (!empty($menuItem['items']))
                    <ul x-show="expanded" x-cloak x-transition class="pl-6 mt-2 space-y-1">
                        @foreach ($menuItem['items'] as $child)
                            <li>
                            <button
                                x-on:click="Livewire.dispatch('cambiarVista', [@js($child['route'])])"
                                class="w-full px-3 py-1 text-left rounded text-white/70 hover:text-white hover:bg-white/10"
                            >
                                {{ $child['label'] }}
                            </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </nav>
</aside>
