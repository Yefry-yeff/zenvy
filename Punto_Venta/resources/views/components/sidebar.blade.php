@props(['menu'])

<aside
    x-show="sidebarOpen" {{-- Controla la visibilidad del sidebar con Alpine.js --}}
    x-data="{ expandedGroup: null }"
    x-transition:enter="transition duration-300 ease-out"
    x-transition:enter-start="-translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition duration-200 ease-in"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="-translate-x-full opacity-0"
    :class="[
        sidebarOpen ? 'w-64' : 'w-20',
        theme === 'verde' ? 'bg-emerald-800/30' :
        theme === 'azul' ? 'bg-blue-800/30' :
        theme === 'oscuro' ? 'bg-gray-800/50' : 'bg-slate-700/30'
    ]"
    class="h-full overflow-y-auto text-white transition-all duration-300 border-r backdrop-blur-md border-white/10"
>
    <nav class="p-4 space-y-2 text-sm">
        {{-- Dashboard --}}
        <div class="flex justify-center">
            <button
                x-on:click="Livewire.dispatch('cambiarVista', ['dashboard'])"
                class="flex items-center gap-2 px-2 py-1 text-sm font-medium text-white transition hover:text-white/80"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7m-9 2v7m4 0h5a2 2 0 002-2v-5a2 2 0 00-2-2h-3.5" />
                </svg>
                <span>Dashboard</span>
            </button>
        </div>

        {{-- Menú dinámico --}}
        @foreach ($menu as $index => $menuItem)
            <div>
                <button
                    @click="expandedGroup = expandedGroup === {{ $index }} ? null : {{ $index }}"
                    class="flex items-center justify-between w-full px-3 py-2 text-white transition rounded hover:bg-white/10"
                >
                    <span class="flex items-center gap-2">
                        <span>{!! $menuItem['icon'] !!}</span>
                        <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">
                            {{ $menuItem['label'] }}
                        </span>
                    </span>
                    <span x-show="sidebarOpen" x-transition x-text="expandedGroup === {{ $index }} ? '▲' : '▼'" class="text-xs"></span>
                </button>

                @if (!empty($menuItem['items']))
                    <ul
                        x-show="expandedGroup === {{ $index }} && sidebarOpen"
                        x-collapse
                        class="pl-6 mt-2 space-y-1 overflow-hidden"
                    >
                        @foreach ($menuItem['items'] as $child)
                            <li>
                                <button
                                    x-on:click="window.Livewire.dispatch('cambiarVista', ['{{ $child['route'] }}'])"
                                    class="flex items-center w-full gap-2 px-3 py-1 text-left rounded text-white/70 hover:text-white hover:bg-white/10"
                                >
                                    {!! $child['icon'] ?? '' !!}
                                    <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">
                                        {{ $child['label'] }}
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </nav>
</aside>
