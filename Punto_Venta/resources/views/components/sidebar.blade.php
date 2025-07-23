@props(['menu'])

<aside
    x-show="sidebarOpen"
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
 {{-- Botón dinámico de Inicio centrado sin sombreado --}}
<div class="flex justify-center">
    <button
        x-on:click="Livewire.dispatch('cambiarVista', ['dashboard'])"
        class="flex items-center gap-2 px-2 py-1 text-sm font-medium text-white transition hover:text-white/80"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24"
             stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/>
        </svg>
        <span>Inicio</span>
    </button>
</div>
        @foreach ($menu as $menuItem)
    <div x-data="{ expanded: false }">
        <button @click="expanded = !expanded"
                class="flex items-center justify-between w-full px-3 py-2 text-white transition rounded hover:bg-white/10">
            <span class="flex items-center gap-2">
                <span>{!! $menuItem['icon'] !!}</span>
                <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ $menuItem['label'] }}</span>
            </span>
            <span x-show="sidebarOpen" x-transition x-text="expanded ? '▲' : '▼'" class="text-xs"></span>
        </button>

        @if (!empty($menuItem['items']))
            <ul x-show="expanded && sidebarOpen" x-cloak x-transition class="pl-6 mt-2 space-y-1">
                @foreach ($menuItem['items'] as $child)
                    <li>
                        <button
                            x-on:click="window.Livewire.dispatch('cambiarVista', ['{{ $child['route'] }}'])"
                            class="flex items-center w-full gap-2 px-3 py-1 text-left rounded text-white/70 hover:text-white hover:bg-white/10"
                        >
                            {!! $child['icon'] ?? '' !!}
                            <span x-show="sidebarOpen" x-transition class="whitespace-nowrap">{{ $child['label'] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endforeach
    </nav>
</aside>
