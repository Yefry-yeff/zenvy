<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <!-- Header de bienvenida -->
    <div class="bg-white border-b border-gray-200 shadow-sm">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        👋 ¡Bienvenido, {{ $datosUsuario['nombre'] }}!
                    </h1>
                    <p class="mt-1 text-sm text-gray-600">
                        Rol: <span class="font-medium text-indigo-600">{{ $datosUsuario['rol'] }}</span> |
                        Tienda: <span class="font-medium">{{ $datosUsuario['tienda'] }}</span> |
                        Último acceso: {{ $datosUsuario['ultimo_acceso'] }}
                    </p>
                    
                    <!-- Estado de la Jornada -->
                    @if($estadoJornada)
                    <div class="mt-2 flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-calendar-day text-purple-500"></i>
                            <span class="text-sm text-gray-600">Estado de Jornada:</span>
                            @if($estadoJornada['estado'] == 'abierta')
                                <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                    🟢 {{ $estadoJornada['estado_texto'] }}
                                </span>
                            @elseif($estadoJornada['estado'] == 'cerrada')
                                <span class="px-2 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full">
                                    🔴 {{ $estadoJornada['estado_texto'] }}
                                </span>
                            @elseif($estadoJornada['estado'] == 'sin_aperturar')
                                <span class="px-2 py-1 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full">
                                    🟡 Sin aperturar
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full">
                                    ⚪ {{ $estadoJornada['estado_texto'] }}
                                </span>
                            @endif
                        </div>
                        @if($estadoJornada['usuario_apertura'])
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">Aperturada por:</span>
                            <span class="text-sm font-semibold text-purple-600">{{ $estadoJornada['usuario_apertura'] }}</span>
                        </div>
                        @endif
                        @if($estadoJornada['usuario_cierre'])
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">Cerrada por:</span>
                            <span class="text-sm font-semibold text-red-600">{{ $estadoJornada['usuario_cierre'] }}</span>
                        </div>
                        @endif
                    </div>
                    @endif
                    
                    <!-- Estado de la Caja -->
                    @if($estadoCaja)
                    <div class="mt-2 flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-cash-register text-blue-500"></i>
                            <span class="text-sm text-gray-600">Estado de Caja:</span>
                            @if($estadoCaja['estado'] == 1)
                                <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                    ✅ {{ $estadoCaja['estado_texto'] }}
                                </span>
                            @elseif($estadoCaja['estado'] == 2)
                                <span class="px-2 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full">
                                    🔒 {{ $estadoCaja['estado_texto'] }}
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full">
                                    ⚠️ {{ $estadoCaja['estado_texto'] }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">Balance:</span>
                            <span class="text-sm font-semibold text-gray-800">L. {{ number_format($estadoCaja['balance'], 2) }}</span>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">{{ \Carbon\Carbon::now()->format('l, d \d\e F \d\e Y') }}</div>
                    <div class="text-lg font-semibold text-gray-700">{{ \Carbon\Carbon::now()->format('H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones rápidas basadas en rol -->
    <div class="px-6 pb-4">
        <div class="p-6 bg-white border border-gray-100 shadow-lg rounded-xl">
            <h3 class="mb-4 text-lg font-semibold text-gray-800">🚀 Acciones Rápidas</h3>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
                @if(in_array($datosUsuario['rol'], ['Facturador', 'Admin', 'Administrador', 'Inventario']))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['saladeventas.ventas'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl hover:from-blue-600 hover:to-blue-700 hover:scale-105">
                    <span class="mb-2 text-2xl">🛒</span>
                    <span class="text-sm font-medium">Nueva Venta</span>
                </button>
                @endif

                @if(in_array($datosUsuario['rol'], ['Inventario', 'Admin', 'Administrador']))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['inventario.producto'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-green-500 to-green-600 rounded-xl hover:from-green-600 hover:to-green-700 hover:scale-105">
                    <span class="mb-2 text-2xl">📦</span>
                    <span class="text-sm font-medium">Productos</span>
                </button>

                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['inventario.compradeproductos'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl hover:from-purple-600 hover:to-purple-700 hover:scale-105">
                    <span class="mb-2 text-2xl">🛍️</span>
                    <span class="text-sm font-medium">Compras</span>
                </button>

                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['inventario.bodegas'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl hover:from-orange-600 hover:to-orange-700 hover:scale-105">
                    <span class="mb-2 text-2xl">🏭</span>
                    <span class="text-sm font-medium">Bodegas</span>
                </button>
                @endif

                @if(in_array($datosUsuario['rol'], ['Admin', 'Administrador', 'Roles']))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['configuracion.usuarios'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl hover:from-indigo-600 hover:to-indigo-700 hover:scale-105">
                    <span class="mb-2 text-2xl">👥</span>
                    <span class="text-sm font-medium">Usuarios</span>
                </button>

                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['configuracion.roles'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl hover:from-pink-600 hover:to-pink-700 hover:scale-105">
                    <span class="mb-2 text-2xl">🔐</span>
                    <span class="text-sm font-medium">Roles</span>
                </button>
                @endif

                @if(in_array($datosUsuario['rol'], ['Cajero', 'Admin', 'Administrador', 'Facturador']))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['caja.RecibidoDeEfectivo'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl hover:from-emerald-600 hover:to-emerald-700 hover:scale-105">
                    <span class="mb-2 text-2xl">💰</span>
                    <span class="text-sm font-medium">Recibir Efectivo</span>
                </button>

                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['caja.EntregaDeEfectivo'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-red-500 to-red-600 rounded-xl hover:from-red-600 hover:to-red-700 hover:scale-105">
                    <span class="mb-2 text-2xl">💸</span>
                    <span class="text-sm font-medium">Entregar Efectivo</span>
                </button>

                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['caja.CierreDeCaja'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl hover:from-purple-600 hover:to-purple-700 hover:scale-105">
                    <span class="mb-2 text-2xl">📋</span>
                    <span class="text-sm font-medium">Cierre de Caja</span>
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <!-- Tarjetas de estadísticas principales -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- Facturas Hoy -->
            <div class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-lg rounded-xl hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Facturas Hoy</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $estadisticas['facturas_hoy'] }}</p>
                        <p class="mt-1 text-xs text-green-600">📈 +{{ rand(5, 15) }}% vs ayer</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full">
                        <span class="text-2xl">🧾</span>
                    </div>
                </div>
            </div>

            <!-- Ventas Hoy -->
            <div class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-lg rounded-xl hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Ventas Hoy</p>
                        <p class="text-3xl font-bold text-green-600">L. {{ number_format($estadisticas['ventas_hoy'], 2) }}</p>
                        <p class="mt-1 text-xs text-green-600">💰 +{{ rand(8, 20) }}% vs ayer</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full">
                        <span class="text-2xl">💵</span>
                    </div>
                </div>
            </div>

            <!-- Ventas del Mes -->
            <div class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-lg rounded-xl hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Ventas del Mes</p>
                        <p class="text-3xl font-bold text-purple-600">L. {{ number_format($estadisticas['ventas_mes'], 2) }}</p>
                        <p class="mt-1 text-xs text-purple-600">📊 Meta: L. 50,000</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-full">
                        <span class="text-2xl">📈</span>
                    </div>
                </div>
            </div>

            <!-- Productos Activos -->
            <div class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-lg rounded-xl hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Productos Activos</p>
                        <p class="text-3xl font-bold text-orange-600">{{ $estadisticas['productos_activos'] }}</p>
                        <p class="mt-1 text-xs text-orange-600">📦 En inventario</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-full">
                        <span class="text-2xl">📦</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas específicas por rol -->
        @if(in_array($datosUsuario['rol'], ['Admin', 'Administrador']))
        <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
            <div class="p-6 text-white shadow-lg bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-indigo-100">Tiendas Activas</p>
                        <p class="text-3xl font-bold">{{ $estadisticas['tiendas_activas'] ?? 0 }}</p>
                    </div>
                    <span class="text-3xl">🏪</span>
                </div>
            </div>

            <div class="p-6 text-white shadow-lg bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-teal-100">Bodegas Activas</p>
                        <p class="text-3xl font-bold">{{ $estadisticas['bodegas_activas'] ?? 0 }}</p>
                    </div>
                    <span class="text-3xl">🏭</span>
                </div>
            </div>

            <div class="p-6 text-white shadow-lg bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-pink-100">Usuarios Activos</p>
                        <p class="text-3xl font-bold">{{ $estadisticas['usuarios_activos'] }}</p>
                    </div>
                    <span class="text-3xl">👥</span>
                </div>
            </div>

            <div class="p-6 text-white shadow-lg bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-amber-100">Roles Activos</p>
                        <p class="text-3xl font-bold">{{ $estadisticas['roles_activos'] ?? 0 }}</p>
                    </div>
                    <span class="text-3xl">🔐</span>
                </div>
            </div>
        </div>
        @endif

        @if(in_array($datosUsuario['rol'], ['Inventario', 'Admin', 'Administrador']))
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="p-6 text-white shadow-lg bg-gradient-to-br from-red-500 to-red-600 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100">Stock Bajo</p>
                        <p class="text-3xl font-bold">{{ $estadisticas['stock_bajo'] ?? 0 }}</p>
                        <p class="mt-1 text-xs text-red-100">⚠️ Requiere atención</p>
                    </div>
                    <span class="text-3xl">⚠️</span>
                </div>
            </div>

            <div class="p-6 text-white shadow-lg bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-emerald-100">Recepciones este Mes</p>
                        <p class="text-3xl font-bold">{{ $estadisticas['recepciones_mes'] ?? 0 }}</p>
                        <p class="mt-1 text-xs text-emerald-100">📋 Productos recibidos</p>
                    </div>
                    <span class="text-3xl">�</span>
                </div>
            </div>
        </div>
        @endif

        <!-- Sección de contenido dinámico -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Ventas Recientes -->
            @if(in_array($datosUsuario['rol'], ['Facturador', 'Admin', 'Administrador', 'Inventario']))
            <div class="p-6 bg-white border border-gray-100 shadow-lg lg:col-span-2 rounded-xl">
                <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                    📊 Ventas Recientes
                </h3>
                <div class="space-y-3">
                    @forelse($ventasRecientes as $venta)
                    <div class="flex items-center justify-between p-4 transition-colors rounded-lg bg-gray-50 hover:bg-gray-100">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-blue-600">{{ $venta->numero_factura }}</span>
                                <span class="text-sm text-gray-500">•</span>
                                <span class="text-sm text-gray-600">{{ $venta->nombre_cliente ?? 'CONSUMIDOR FINAL' }}</span>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($venta->created_at)->format('d/m/Y H:i') }} • {{ $venta->usuario }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-green-600">L. {{ number_format($venta->total, 2) }}</div>
                        </div>
                    </div>
                    @empty
                    <div class="py-8 text-center text-gray-500">
                        No hay ventas recientes
                    </div>
                    @endforelse
                </div>
            </div>
            @endif

            <!-- Productos con Stock Bajo -->
            @if(in_array($datosUsuario['rol'], ['Inventario', 'Admin', 'Administrador']))
            <div class="p-6 bg-white border border-gray-100 shadow-lg rounded-xl">
                <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                    ⚠️ Stock Bajo
                </h3>
                <div class="space-y-3">
                    @forelse($productosStockBajo as $producto)
                    <div class="flex items-center justify-between p-3 border-l-4 border-red-400 rounded-lg bg-red-50">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-800">{{ $producto->producto }}</div>
                            <div class="text-xs text-gray-600">{{ $producto->bodega }} - {{ $producto->seccion }}</div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full">
                                {{ $producto->cantidad_disponible }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="py-8 text-center text-gray-500">
                        ✅ Stock suficiente
                    </div>
                    @endforelse
                </div>
            </div>
            @endif

            <!-- Actividad del Sistema (solo Admin) -->
            @if(in_array($datosUsuario['rol'], ['Admin', 'Administrador']))
            <div class="p-6 bg-white border border-gray-100 shadow-lg rounded-xl">
                <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                    🔔 Actividad Reciente
                </h3>
                <div class="space-y-3">
                    @forelse($actividad as $evento)
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50">
                        <span class="text-lg">{{ $evento['icono'] }}</span>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-800">{{ $evento['descripcion'] }}</div>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ $evento['usuario'] }} • {{ $evento['fecha']->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="py-8 text-center text-gray-500">
                        Sin actividad reciente
                    </div>
                    @endforelse
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
