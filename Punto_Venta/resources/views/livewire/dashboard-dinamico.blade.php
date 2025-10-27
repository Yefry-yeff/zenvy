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
                    @if($estadoJornada && is_array($estadoJornada))
                    <div class="flex items-center mt-2 space-x-4">
                        <div class="flex items-center space-x-2">
                            <i class="text-purple-500 fas fa-calendar-day"></i>
                            <span class="text-sm text-gray-600">Estado de Jornada:</span>
                            @if(isset($estadoJornada['estado']) && $estadoJornada['estado'] == 'abierta')
                                <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                    🟢 {{ $estadoJornada['estado_texto'] ?? 'Abierta' }}
                                </span>
                            @elseif(isset($estadoJornada['estado']) && $estadoJornada['estado'] == 'cerrada')
                                <span class="px-2 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full">
                                    🔴 {{ $estadoJornada['estado_texto'] ?? 'Cerrada' }}
                                </span>
                            @elseif(isset($estadoJornada['estado']) && $estadoJornada['estado'] == 'sin_aperturar')
                                <span class="px-2 py-1 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full">
                                    🟡 {{ $estadoJornada['estado_texto'] ?? 'Sin aperturar' }}
                                </span>
                            @elseif(isset($estadoJornada['estado']) && $estadoJornada['estado'] == 'sin_jornada_hoy')
                                <span class="px-2 py-1 text-xs font-medium text-orange-800 bg-orange-100 rounded-full">
                                    🟠 Sin jornada hoy
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full">
                                    ⚪ {{ $estadoJornada['estado_texto'] ?? 'Desconocido' }}
                                </span>
                            @endif

                            <!-- Fecha del estado actual -->
                            @if(isset($estadoJornada['fecha']))
                                <span class="text-xs text-gray-500">
                                    ({{ \Carbon\Carbon::parse($estadoJornada['fecha'])->format('d/m/Y') }})
                                </span>
                            @endif

                            <!-- Advertencia si no es jornada de hoy -->
                            @if(isset($estadoJornada['es_jornada_hoy']) && !$estadoJornada['es_jornada_hoy'])
                                <span class="px-1 py-0.5 text-xs font-medium text-orange-700 bg-orange-200 rounded">
                                    ⚠️ Anterior
                                </span>
                            @endif
                        </div>

                        <!-- Información adicional de apertura/cierre -->
                        @if(isset($estadoJornada['fecha_actualizacion']) && $estadoJornada['fecha_actualizacion'])
                            <div class="flex items-center space-x-1 text-xs text-gray-500">
                                <i class="fas fa-clock"></i>
                                <span>
                                    @if($estadoJornada['estado'] == 'abierta')
                                        Aperturada: {{ \Carbon\Carbon::parse($estadoJornada['fecha_actualizacion'])->format('d/m/Y H:i') }}
                                        @if(isset($estadoJornada['usuario_apertura']))
                                            por {{ $estadoJornada['usuario_apertura'] }}
                                        @endif
                                    @elseif($estadoJornada['estado'] == 'cerrada')
                                        Cerrada: {{ \Carbon\Carbon::parse($estadoJornada['fecha_actualizacion'])->format('d/m/Y H:i') }}
                                        @if(isset($estadoJornada['usuario_cierre']))
                                            por {{ $estadoJornada['usuario_cierre'] }}
                                        @endif
                                    @else
                                        Última actualización: {{ \Carbon\Carbon::parse($estadoJornada['fecha_actualizacion'])->format('d/m/Y H:i') }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                    @endif

                    <!-- Estado de la Caja -->
                    @if($estadoCaja && is_array($estadoCaja))
                    <div class="flex items-center mt-2 space-x-4">
                        <div class="flex items-center space-x-2">
                            <i class="text-blue-500 fas fa-cash-register"></i>
                            <span class="text-sm text-gray-600">Estado de Caja:</span>
                            @if(isset($estadoCaja['estado']) && $estadoCaja['estado'] == 1)
                                <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                    ✅ {{ $estadoCaja['estado_texto'] ?? 'Abierta' }}
                                </span>
                            @elseif(isset($estadoCaja['estado']) && $estadoCaja['estado'] == 2)
                                <span class="px-2 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full">
                                    🔒 {{ $estadoCaja['estado_texto'] ?? 'Cerrada' }}
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full">
                                    ⚠️ {{ $estadoCaja['estado_texto'] ?? 'Sin usar' }}
                                </span>
                            @endif

                            <!-- Fecha de apertura de la caja -->
                            @if(isset($estadoCaja['fecha_apertura']) && $estadoCaja['fecha_apertura'])
                                <span class="text-xs text-gray-500">
                                    ({{ \Carbon\Carbon::parse($estadoCaja['fecha_apertura'])->format('d/m/Y') }})
                                </span>
                            @endif

                            <!-- Advertencia si no es caja de hoy -->
                            @if(isset($estadoCaja['es_caja_hoy']) && !$estadoCaja['es_caja_hoy'])
                                <span class="px-1 py-0.5 text-xs font-medium text-orange-700 bg-orange-200 rounded">
                                    ⚠️ Anterior
                                </span>
                            @endif

                            <!-- Mensaje si no tiene caja hoy -->
                            @if(isset($estadoCaja['tiene_caja_hoy']) && !$estadoCaja['tiene_caja_hoy'])
                                <span class="px-1 py-0.5 text-xs font-medium text-red-700 bg-red-200 rounded">
                                    ❌ Sin caja hoy
                                </span>
                            @endif
                        </div>

                        @if(isset($estadoCaja['balance']))
                        <div x-data="{ mostrarDetalle: false }" class="space-y-2">
                            <!-- Botón para mostrar/ocultar detalle -->
                            <button @click="mostrarDetalle = !mostrarDetalle"
                                    class="flex items-center justify-between w-full p-2 text-left transition-colors duration-200 rounded-md bg-gray-50 hover:bg-gray-100">
                                <div class="flex items-center space-x-2">
                                    <i class="text-sm text-blue-500 fas fa-chart-line"></i>
                                    <span class="text-sm font-medium text-gray-700">Flujo de Caja</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-bold text-gray-900">L. {{ number_format($estadoCaja['balance_total_calculado'] ?? 0, 2) }}</span>
                                    <i class="text-xs text-gray-400 transition-transform duration-200 transform fas fa-chevron-down"
                                       :class="{ 'rotate-180': mostrarDetalle }"></i>
                                </div>
                            </button>

                            <!-- Contenido colapsable del detalle -->
                            <div x-show="mostrarDetalle"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-200"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="p-3 space-y-2 bg-white border border-gray-200 rounded-md">

                                <div class="pb-1 mb-2 text-xs font-medium text-gray-600 border-b">Balance por tipo de pago:</div>

                                <!-- Balance Efectivo -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <i class="text-xs text-green-500 fas fa-money-bill-wave"></i>
                                        <span class="text-xs text-gray-600">Efectivo:</span>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-800">L. {{ number_format($estadoCaja['balance_efectivo'] ?? 0, 2) }}</span>
                                </div>

                                <!-- Balance Tarjeta -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <i class="text-xs text-blue-500 fas fa-credit-card"></i>
                                        <span class="text-xs text-gray-600">Tarjeta:</span>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-800">L. {{ number_format($estadoCaja['balance_tarjeta'] ?? 0, 2) }}</span>
                                </div>

                                <!-- Balance Cheque -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <i class="text-xs text-purple-500 fas fa-money-check"></i>
                                        <span class="text-xs text-gray-600">Cheque:</span>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-800">L. {{ number_format($estadoCaja['balance_cheque'] ?? 0, 2) }}</span>
                                </div>

                                <!-- Balance Transferencia -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <i class="text-xs text-orange-500 fas fa-exchange-alt"></i>
                                        <span class="text-xs text-gray-600">Transferencia:</span>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-800">L. {{ number_format($estadoCaja['balance_transferencia'] ?? 0, 2) }}</span>
                                </div>

                                <!-- Línea divisoria -->
                                <hr class="my-2 border-gray-200">

                                <!-- Total -->
                                <div class="flex items-center justify-between p-2 rounded bg-gray-50">
                                    <span class="text-sm font-medium text-gray-700">Total:</span>
                                    <span class="text-sm font-bold text-gray-900">L. {{ number_format($estadoCaja['balance_total_calculado'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Información adicional de última actualización -->
                        @if(isset($estadoCaja['fecha_actualizacion']) && $estadoCaja['fecha_actualizacion'])
                            <div class="flex items-center space-x-1 text-xs text-gray-500">
                                <i class="fas fa-clock"></i>
                                <span>Última actualización: {{ \Carbon\Carbon::parse($estadoCaja['fecha_actualizacion'])->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
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

    <!-- Estadísticas principales -->
    <div class="p-6 space-y-6">
        <!-- Estadísticas generales en cards -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- Facturas Hoy -->
            <div class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-lg rounded-xl hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Facturas Hoy</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $estadisticas['facturas_hoy'] }}</p>
                        <p class="mt-1 text-xs text-blue-600">🧾 Documentos</p>
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
                        <p class="mt-1 text-xs text-green-600">💰 Ingresos</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full">
                        <span class="text-2xl">💰</span>
                    </div>
                </div>
            </div>

            <!-- Ventas del Mes -->
            <div class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-lg rounded-xl hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Ventas del Mes</p>
                        <p class="text-3xl font-bold text-purple-600">L. {{ number_format($estadisticas['ventas_mes'], 2) }}</p>
                        <p class="mt-1 text-xs text-purple-600">📊 Total mensual</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-full">
                        <span class="text-2xl">📊</span>
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

        <!-- Acceso Rápido - Más compacto y en una fila -->
        <div class="p-4 bg-white border border-gray-100 shadow-lg rounded-xl">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">🚀 Acceso Rápido</h3>
            <div class="grid grid-cols-6 gap-2 md:grid-cols-8 lg:grid-cols-12">
                @if($this->tienePermiso('SalaDeVentas.Ventas'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['SalaDeVentas.Ventas'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg hover:from-blue-600 hover:to-blue-700 hover:scale-105">
                    <span class="text-lg">🧾</span>
                    <span class="mt-1 text-xs font-medium">Facturar</span>
                </button>
                @endif

                @if($this->tienePermiso('Inventario.Producto'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Inventario.Producto'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-green-500 to-green-600 rounded-lg hover:from-green-600 hover:to-green-700 hover:scale-105">
                    <span class="text-lg">📦</span>
                    <span class="mt-1 text-xs font-medium">Productos</span>
                </button>
                @endif

                @if($this->tienePermiso('Inventario.CompraDeProductos'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Inventario.CompraDeProductos'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg hover:from-purple-600 hover:to-purple-700 hover:scale-105">
                    <span class="text-lg">🛍️</span>
                    <span class="mt-1 text-xs font-medium">Compras</span>
                </button>
                @endif

                @if($this->tienePermiso('Inventario.Bodegas'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Inventario.Bodegas'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg hover:from-orange-600 hover:to-orange-700 hover:scale-105">
                    <span class="text-lg">🏭</span>
                    <span class="mt-1 text-xs font-medium">Bodegas</span>
                </button>
                @endif

                @if($this->tienePermiso('Configuracion.Usuarios'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Configuracion.Usuarios'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg hover:from-indigo-600 hover:to-indigo-700 hover:scale-105">
                    <span class="text-lg">👥</span>
                    <span class="mt-1 text-xs font-medium">Usuarios</span>
                </button>
                @endif

                @if($this->tienePermiso('Configuracion.Roles'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Configuracion.Roles'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-pink-500 to-pink-600 rounded-lg hover:from-pink-600 hover:to-pink-700 hover:scale-105">
                    <span class="text-lg">🔐</span>
                    <span class="mt-1 text-xs font-medium">Roles</span>
                </button>
                @endif

                @if($this->tienePermiso('Caja.RecibidoDeEfectivo'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.RecibidoDeEfectivo'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg hover:from-emerald-600 hover:to-emerald-700 hover:scale-105">
                    <span class="text-lg">💰</span>
                    <span class="mt-1 text-xs font-medium">Recibir</span>
                </button>
                @endif

                @if($this->tienePermiso('Caja.EntregaDeEfectivo'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.EntregaDeEfectivo'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-red-500 to-red-600 rounded-lg hover:from-red-600 hover:to-red-700 hover:scale-105">
                    <span class="text-lg">💸</span>
                    <span class="mt-1 text-xs font-medium">Entregar</span>
                </button>
                @endif

                @if($this->tienePermiso('Caja.SaldoInicial'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.SaldoInicial'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-lg hover:from-cyan-600 hover:to-cyan-700 hover:scale-105">
                    <span class="text-lg">🏦</span>
                    <span class="mt-1 text-xs font-medium">Saldo</span>
                </button>
                @endif

                @if($this->tienePermiso('Caja.CierreDeCaja'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.CierreDeCaja'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-slate-500 to-slate-600 rounded-lg hover:from-slate-600 hover:to-slate-700 hover:scale-105">
                    <span class="text-lg">🔒</span>
                    <span class="mt-1 text-xs font-medium">Cerrar</span>
                </button>
                @endif

                @if($this->tienePermiso('Clientes.Clientes'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Clientes.Clientes'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-teal-500 to-teal-600 rounded-lg hover:from-teal-600 hover:to-teal-700 hover:scale-105">
                    <span class="text-lg">👤</span>
                    <span class="mt-1 text-xs font-medium">Clientes</span>
                </button>
                @endif

                @if($this->tienePermiso('Proveedores.Proveedores'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Proveedores.Proveedores'])"
                    class="flex flex-col items-center justify-center p-2 text-white transition-all duration-200 transform bg-gradient-to-br from-amber-500 to-amber-600 rounded-lg hover:from-amber-600 hover:to-amber-700 hover:scale-105">
                    <span class="text-lg">🚚</span>
                    <span class="mt-1 text-xs font-medium">Proveedores</span>
                </button>
                @endif
            </div>
        </div>

        <!-- Gráficos de métricas -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Gráfico de Ventas de la Semana -->
            @if($this->tienePermiso(['SalaDeVentas.Ventas']))
            <div class="p-6 bg-white border border-gray-100 shadow-lg rounded-xl">
                <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                    📈 Ventas de la Última Semana
                </h3>
                <div style="height: 300px;">
                    <canvas id="chartVentasSemana"></canvas>
                </div>
            </div>
            @endif

            <!-- Gráfico de Productos Más Vendidos -->
            @if($this->tienePermiso(['Inventario.Producto', 'SalaDeVentas.Ventas']))
            <div class="p-6 bg-white border border-gray-100 shadow-lg rounded-xl">
                <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                    🏆 Top 5 Productos Más Vendidos
                </h3>
                <div style="height: 300px;">
                    <canvas id="chartProductosVendidos"></canvas>
                </div>
            </div>
            @endif
        </div>

        <!-- Gráfico de Métodos de Pago -->
        @if($this->tienePermiso(['SalaDeVentas.Ventas', 'Caja.RecibidoDeEfectivo']))
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="p-6 bg-white border border-gray-100 shadow-lg rounded-xl">
                <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                    💳 Ventas por Método de Pago (Hoy)
                </h3>
                <div style="height: 300px;">
                    <canvas id="chartMetodosPago"></canvas>
                </div>
            </div>

            <!-- Gráfico de Stock por Bodega -->
            @if($this->tienePermiso(['Inventario.Bodegas', 'Inventario.Producto']))
            <div class="p-6 bg-white border border-gray-100 shadow-lg rounded-xl">
                <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                    👥 Top 5 Clientes que Más Compran
                </h3>
                <div style="height: 300px;">
                    <canvas id="chartTopClientes"></canvas>
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Acceso Rápido - Movido más abajo -->
        <div class="p-6 bg-white border border-gray-100 shadow-lg rounded-xl">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">🚀 Acceso Rápido</h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
                @if($this->tienePermiso('SalaDeVentas.Ventas'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['SalaDeVentas.Ventas'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl hover:from-blue-600 hover:to-blue-700 hover:scale-105">
                    <span class="mb-2 text-2xl">🧾</span>
                    <span class="text-sm font-medium">Facturar</span>
                </button>
                @endif

                @if($this->tienePermiso('Inventario.Producto'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Inventario.Producto'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-green-500 to-green-600 rounded-xl hover:from-green-600 hover:to-green-700 hover:scale-105">
                    <span class="mb-2 text-2xl">📦</span>
                    <span class="text-sm font-medium">Productos</span>
                </button>
                @endif

                @if($this->tienePermiso('Inventario.CompraDeProductos'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Inventario.CompraDeProductos'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl hover:from-purple-600 hover:to-purple-700 hover:scale-105">
                    <span class="mb-2 text-2xl">🛍️</span>
                    <span class="text-sm font-medium">Compras</span>
                </button>
                @endif

                @if($this->tienePermiso('Inventario.Bodegas'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Inventario.Bodegas'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl hover:from-orange-600 hover:to-orange-700 hover:scale-105">
                    <span class="mb-2 text-2xl">🏭</span>
                    <span class="text-sm font-medium">Bodegas</span>
                </button>
                @endif

                @if($this->tienePermiso('Configuracion.Usuarios'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Configuracion.Usuarios'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl hover:from-indigo-600 hover:to-indigo-700 hover:scale-105">
                    <span class="mb-2 text-2xl">👥</span>
                    <span class="text-sm font-medium">Usuarios</span>
                </button>
                @endif

                @if($this->tienePermiso('Configuracion.Roles'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Configuracion.Roles'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl hover:from-pink-600 hover:to-pink-700 hover:scale-105">
                    <span class="mb-2 text-2xl">🔐</span>
                    <span class="text-sm font-medium">Roles</span>
                </button>
                @endif

                @if($this->tienePermiso(['SalaDeVentas.Ventas', 'Caja.RecibidoDeEfectivo', 'Caja.EntregaDeEfectivo', 'Caja.SaldoInicial', 'Caja.CierreDeCaja']))
                @if($this->tienePermiso('Caja.RecibidoDeEfectivo'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.RecibidoDeEfectivo'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl hover:from-emerald-600 hover:to-emerald-700 hover:scale-105">
                    <span class="mb-2 text-2xl">💰</span>
                    <span class="text-sm font-medium">Recibir Efectivo</span>
                </button>
                @endif

                @if($this->tienePermiso('Caja.EntregaDeEfectivo'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.EntregaDeEfectivo'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-red-500 to-red-600 rounded-xl hover:from-red-600 hover:to-red-700 hover:scale-105">
                    <span class="mb-2 text-2xl">💸</span>
                    <span class="text-sm font-medium">Entregar Efectivo</span>
                </button>
                @endif

                @if($this->tienePermiso('Caja.CierreDeCaja'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.CierreDeCaja'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl hover:from-purple-600 hover:to-purple-700 hover:scale-105">
                    <span class="mb-2 text-2xl">📋</span>
                    <span class="text-sm font-medium">Cierre de Caja</span>
                </button>
                @endif

                @if($this->tienePermiso('SalaDeVentas.Ventas'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['SalaDeVentas.Ventas'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl hover:from-yellow-600 hover:to-yellow-700 hover:scale-105">
                    <span class="mb-2 text-2xl">💵</span>
                    <span class="text-sm font-medium">Facturación</span>
                </button>
                @endif
                @endif

                @if($this->tienePermiso('Gestion.Cai'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Gestion.Cai'])"
                    class="flex flex-col items-center justify-center p-4 text-white transition-all duration-200 transform bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl hover:from-teal-600 hover:to-teal-700 hover:scale-105">
                    <span class="mb-2 text-2xl">📄</span>
                    <span class="text-sm font-medium">CAI</span>
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="p-6 space-y-6">
        <!-- Estadísticas generales -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- Facturas Hoy -->
            <div class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-lg rounded-xl hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Facturas Hoy</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $estadisticas['facturas_hoy'] }}</p>
                        <p class="mt-1 text-xs text-blue-600">🧾 Documentos</p>
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
                        <p class="mt-1 text-xs text-green-600">💰 Ingresos</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full">
                        <span class="text-2xl">💰</span>
                    </div>
                </div>
            </div>

            <!-- Ventas del Mes -->
            <div class="p-6 transition-all duration-300 bg-white border border-gray-100 shadow-lg rounded-xl hover:shadow-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Ventas del Mes</p>
                        <p class="text-3xl font-bold text-purple-600">L. {{ number_format($estadisticas['ventas_mes'], 2) }}</p>
                        <p class="mt-1 text-xs text-purple-600">📊 Total mensual</p>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 bg-purple-100 rounded-full">
                        <span class="text-2xl">📊</span>
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
        @if($this->tienePermiso(['Configuracion.Usuarios', 'Configuracion.Roles']))
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="p-6 text-white shadow-lg bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-pink-100">Usuarios Activos</p>
                        <p class="text-3xl font-bold">{{ $estadisticas['usuarios_activos'] }}</p>
                    </div>
                    <span class="text-3xl">👥</span>
                </div>
            </div>
        </div>
        @endif

        @if($this->tienePermiso(['Inventario.Producto', 'Inventario.CompraDeProductos', 'Inventario.Bodegas']))
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
        </div>
        @endif

        <!-- Sección de contenido dinámico -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Ventas Recientes -->
            @if($this->tienePermiso(['SalaDeVentas.Ventas', 'Inventario.Producto']))
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
            @if($this->tienePermiso(['Inventario.Producto', 'Inventario.CompraDeProductos', 'Inventario.Bodegas']))
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
        </div>
    </div>

    <!-- Script para Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuración común para todos los gráficos
            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            };

            // Gráfico de Ventas de la Semana
            const ctxVentas = document.getElementById('chartVentasSemana');
            if (ctxVentas) {
                new Chart(ctxVentas, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($diasSemanaLabels ?: ['Día 1', 'Día 2', 'Día 3', 'Día 4', 'Día 5', 'Día 6', 'Día 7']) !!},
                        datasets: [{
                            label: 'Ventas (L.)',
                            data: {!! json_encode($ventasSemana ?: [0, 0, 0, 0, 0, 0, 0]) !!},
                            backgroundColor: 'rgba(34, 197, 94, 0.7)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 2,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'L. ' + value.toLocaleString();
                                    }
                                }
                            }
                        },
                        plugins: {
                            ...commonOptions.plugins,
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Ventas: L. ' + context.parsed.y.toLocaleString('es-HN', {minimumFractionDigits: 2});
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de Productos Más Vendidos
            const ctxProductos = document.getElementById('chartProductosVendidos');
            if (ctxProductos) {
                new Chart(ctxProductos, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($topProductosLabels ?: ['Sin datos']) !!},
                        datasets: [{
                            label: 'Cantidad Vendida',
                            data: {!! json_encode($topProductosData ?: [0]) !!},
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 2,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        ...commonOptions,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            ...commonOptions.plugins,
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Vendido: ' + context.parsed.x + ' unidades';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de Métodos de Pago
            const ctxPagos = document.getElementById('chartMetodosPago');
            if (ctxPagos) {
                new Chart(ctxPagos, {
                    type: 'bar',
                    data: {
                        labels: ['Efectivo', 'Tarjeta', 'Transferencia', 'Cheque'],
                        datasets: [{
                            label: 'Monto (L.)',
                            data: {!! json_encode($metodosPagoData ?: [0, 0, 0, 0]) !!},
                            backgroundColor: [
                                'rgba(34, 197, 94, 0.7)',
                                'rgba(59, 130, 246, 0.7)',
                                'rgba(249, 115, 22, 0.7)',
                                'rgba(168, 85, 247, 0.7)'
                            ],
                            borderColor: [
                                'rgba(34, 197, 94, 1)',
                                'rgba(59, 130, 246, 1)',
                                'rgba(249, 115, 22, 1)',
                                'rgba(168, 85, 247, 1)'
                            ],
                            borderWidth: 2,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'L. ' + value.toLocaleString();
                                    }
                                }
                            }
                        },
                        plugins: {
                            ...commonOptions.plugins,
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Total: L. ' + context.parsed.y.toLocaleString('es-HN', {minimumFractionDigits: 2});
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Gráfico de Top Clientes
            const ctxClientes = document.getElementById('chartTopClientes');
            if (ctxClientes) {
                new Chart(ctxClientes, {
                    type: 'bar',
                    data: {
                        labels: {!! json_encode($topClientesLabels ?: ['Sin datos']) !!},
                        datasets: [{
                            label: 'Total Gastado (L.)',
                            data: {!! json_encode($topClientesData ?: [0]) !!},
                            backgroundColor: 'rgba(168, 85, 247, 0.7)',
                            borderColor: 'rgba(168, 85, 247, 1)',
                            borderWidth: 2,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        ...commonOptions,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'L. ' + value.toLocaleString();
                                    }
                                }
                            }
                        },
                        plugins: {
                            ...commonOptions.plugins,
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Total: L. ' + context.parsed.x.toLocaleString('es-HN', {minimumFractionDigits: 2});
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</div>
