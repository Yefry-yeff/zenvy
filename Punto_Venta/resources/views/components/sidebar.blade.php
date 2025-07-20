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
        {{-- Grupos de menú --}}
        @php
            $usuarios = [
                ['label' => 'Lista', 'route' => 'usuarios.index'],
                ['label' => 'Crear', 'route' => 'usuarios.create']
            ];
            $ventas = [
                ['label' => 'Nueva venta', 'route' => 'ventas.create'],
                ['label' => 'Historial', 'route' => 'ventas.index']
            ];
            $inventario = [
                ['label' => 'Productos', 'route' => 'inventario.productos'],
                ['label' => 'Categorías', 'route' => 'inventario.categorias']
            ];
        @endphp

        <x.menu-group label="Usuarios" icon="👥" :items="$usuarios" />
        <x.menu-group label="Sala de Ventas" icon="🛒" :items="$ventas" />
        <x.menu-group label="Inventario" icon="📦" :items="$inventario" />
    </nav>
</aside>
