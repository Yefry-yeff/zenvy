@props(['menu'])

<aside
    x-show="sidebarOpen"
    x-transition:enter="transition-all duration-500 ease-in-out"
    x-transition:enter-start="-translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition-all duration-500 ease-in-out"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="-translate-x-full opacity-0"
    x-data="{ expandedGroup: null }"
    :class="[
        sidebarOpen ? 'w-64' : 'hidden',
        theme === 'verde' ? 'bg-emerald-800/30' :
        theme === 'azul' ? 'bg-blue-800/30' :
        theme === 'oscuro' ? 'bg-gray-800/50' : 'bg-slate-700/30'
    ]"
    class="h-full flex flex-col text-white border-r backdrop-blur-md border-white/10"
>
    <nav class="flex-1 p-4 space-y-2 text-sm overflow-y-auto" style="padding-bottom: 120px;">
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
                        <span x-show="sidebarOpen" x-transition>{!! $menuItem['icon'] !!}</span>
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
                                    <span x-show="sidebarOpen" x-transition>{!! $child['icon'] ?? '' !!}</span>
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

    {{-- Copyright y versión al final del sidebar --}}
    <div
        x-show="sidebarOpen"
        x-transition
        class="sticky bottom-0 left-0 right-0 p-4 text-center border-t border-white/10 bg-gradient-to-t from-black/20 to-transparent"
    >
        <div class="space-y-1">
            <div class="flex items-center justify-center gap-1 text-xs text-white/60">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <span>© 2025 ZENVY POS</span>
            </div>
            <div class="font-mono text-xs text-white/40">
                v4.0.1
            </div>
            <div class="text-xs text-white/30">
                Desarrollado por Cadss
            </div>
        </div>
    </div>
</aside>
