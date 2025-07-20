@php
    $sidebarColor = session('sidebar_color', 'from-red-700 to-red-900'); // por defecto rojo oscuro
@endphp

<aside 
    class="w-64 h-screen bg-gradient-to-b {{ $sidebarColor }} text-white fixed overflow-y-auto"
    x-data="{ openMenus: {} }"
>
    <div class="p-4 text-center border-b border-white/20">
        <img src="/logo.png" alt="Logo" class="mx-auto w-12 h-12 mb-2">
        <p class="font-semibold">Johann</p>
        <p class="text-sm text-gray-200">Administrador</p>
    </div>

    <nav class="mt-4 px-2">
        <x-menu-item icon="🏠" label="Dashboard" route="dashboard" />
        
        <x-menu-group icon="👥" label="Usuarios" :items="[
            ['label' => 'Lista', 'route' => 'usuarios.index'],
            ['label' => 'Crear', 'route' => 'usuarios.create']
        ]"/>

        <x-menu-group icon="🛒" label="Sala de Ventas" :items="[
            ['label' => 'Nueva Venta', 'route' => 'ventas.create'],
            ['label' => 'Historial', 'route' => 'ventas.index']
        ]"/>

        <x-menu-group icon="📦" label="Inventario" :items="[
            ['label' => 'Productos', 'route' => 'productos.index'],
            ['label' => 'Categorías', 'route' => 'categorias.index']
        ]"/>

        <!-- Más secciones aquí -->
    </nav>
</aside>
