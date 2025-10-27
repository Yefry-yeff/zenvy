<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50" 
     x-data="{ chartsReady: false }" 
     x-init="
        $nextTick(() => {
            setTimeout(() => {
                if (typeof initCharts === 'function') {
                    initCharts();
                    chartsReady = true;
                }
            }, 200);
        })
     ">
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
        <div class="p-5 bg-white border border-gray-100 shadow-lg rounded-xl">
            <h3 class="mb-4 text-base font-semibold text-gray-800">🚀 Acceso Rápido</h3>
            <div class="flex flex-wrap gap-3">
                @if($this->tienePermiso('SalaDeVentas.Ventas'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['SalaDeVentas.Ventas'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg hover:from-blue-600 hover:to-blue-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">🧾</span>
                    <span>Facturar</span>
                </button>
                @endif

                @if($this->tienePermiso('Inventario.Producto'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Inventario.Producto'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-green-500 to-green-600 rounded-lg hover:from-green-600 hover:to-green-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">📦</span>
                    <span>Productos</span>
                </button>
                @endif

                @if($this->tienePermiso('Inventario.CompraDeProductos'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Inventario.CompraDeProductos'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg hover:from-purple-600 hover:to-purple-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">🛍️</span>
                    <span>Compras</span>
                </button>
                @endif

                @if($this->tienePermiso('Inventario.Bodegas'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Inventario.Bodegas'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg hover:from-orange-600 hover:to-orange-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">🏭</span>
                    <span>Bodegas</span>
                </button>
                @endif

                @if($this->tienePermiso('Configuracion.Usuarios'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Configuracion.Usuarios'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-lg hover:from-indigo-600 hover:to-indigo-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">👥</span>
                    <span>Usuarios</span>
                </button>
                @endif

                @if($this->tienePermiso('Configuracion.Roles'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Configuracion.Roles'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-pink-500 to-pink-600 rounded-lg hover:from-pink-600 hover:to-pink-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">🔐</span>
                    <span>Roles</span>
                </button>
                @endif

                @if($this->tienePermiso('Caja.RecibidoDeEfectivo'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.RecibidoDeEfectivo'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-lg hover:from-emerald-600 hover:to-emerald-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">💰</span>
                    <span>Recibir</span>
                </button>
                @endif

                @if($this->tienePermiso('Caja.EntregaDeEfectivo'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.EntregaDeEfectivo'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-red-500 to-red-600 rounded-lg hover:from-red-600 hover:to-red-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">💸</span>
                    <span>Entregar</span>
                </button>
                @endif

                @if($this->tienePermiso('Caja.SaldoInicial'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.SaldoInicial'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-cyan-500 to-cyan-600 rounded-lg hover:from-cyan-600 hover:to-cyan-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">🏦</span>
                    <span>Saldo</span>
                </button>
                @endif

                @if($this->tienePermiso('Caja.CierreDeCaja'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Caja.CierreDeCaja'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-slate-500 to-slate-600 rounded-lg hover:from-slate-600 hover:to-slate-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">🔒</span>
                    <span>Cerrar</span>
                </button>
                @endif

                @if($this->tienePermiso('Clientes.Clientes'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Clientes.Clientes'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-teal-500 to-teal-600 rounded-lg hover:from-teal-600 hover:to-teal-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">👤</span>
                    <span>Clientes</span>
                </button>
                @endif

                @if($this->tienePermiso('Proveedores.Proveedores'))
                <button
                    x-on:click="window.Livewire.dispatch('cambiarVista', ['Proveedores.Proveedores'])"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white transition-all duration-200 transform bg-gradient-to-r from-amber-500 to-amber-600 rounded-lg hover:from-amber-600 hover:to-amber-700 hover:scale-105 shadow-sm hover:shadow-md">
                    <span class="text-base">🚚</span>
                    <span>Proveedores</span>
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
                <div style="height: 300px;" wire:ignore>
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
                <div style="height: 300px;" wire:ignore>
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
                <div style="height: 300px;" wire:ignore>
                    <canvas id="chartMetodosPago"></canvas>
                </div>
            </div>

            <!-- Gráfico de Stock por Bodega -->
            @if($this->tienePermiso(['Inventario.Bodegas', 'Inventario.Producto']))
            <div class="p-6 bg-white border border-gray-100 shadow-lg rounded-xl">
                <h3 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                    👥 Top 5 Clientes que Más Compran
                </h3>
                <div style="height: 300px;" wire:ignore>
                    <canvas id="chartTopClientes"></canvas>
                </div>
            </div>
            @endif
        </div>
        @endif

    <!-- Ventas Recientes y Stock Bajo -->
    <div class="p-6">
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
        // Variable para rastrear si los gráficos están inicializados
        window.chartsInitialized = false;

        function destroyCharts() {
            console.log('Destruyendo gráficos...');
            if (window.chartVentas) {
                try { window.chartVentas.destroy(); } catch(e) {}
                window.chartVentas = null;
            }
            if (window.chartProductos) {
                try { window.chartProductos.destroy(); } catch(e) {}
                window.chartProductos = null;
            }
            if (window.chartPagos) {
                try { window.chartPagos.destroy(); } catch(e) {}
                window.chartPagos = null;
            }
            if (window.chartClientes) {
                try { window.chartClientes.destroy(); } catch(e) {}
                window.chartClientes = null;
            }
            window.chartsInitialized = false;
        }

        window.initCharts = function() {
            console.log('Intentando inicializar gráficos...', {
                initialized: window.chartsInitialized,
                canvasVentas: !!document.getElementById('chartVentasSemana'),
                canvasProductos: !!document.getElementById('chartProductosVendidos'),
                canvasPagos: !!document.getElementById('chartMetodosPago'),
                canvasClientes: !!document.getElementById('chartTopClientes')
            });

            // Si ya están inicializados, destruir primero
            if (window.chartsInitialized) {
                destroyCharts();
            }

            // Esperar un momento para que el DOM esté completamente listo
            setTimeout(() => {
                // Solo crear gráficos si no existen ya
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
            if (ctxVentas && ctxVentas.getContext) {
                try {
                    window.chartVentas = new Chart(ctxVentas, {
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
                } catch(e) {
                    console.error('Error creando gráfico de ventas:', e);
                }
            }

            // Gráfico de Productos Más Vendidos
            const ctxProductos = document.getElementById('chartProductosVendidos');
            if (ctxProductos && ctxProductos.getContext) {
                try {
                    window.chartProductos = new Chart(ctxProductos, {
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
                } catch(e) {
                    console.error('Error creando gráfico de productos:', e);
                }
            }

            // Gráfico de Métodos de Pago
            const ctxPagos = document.getElementById('chartMetodosPago');
            if (ctxPagos && ctxPagos.getContext) {
                try {
                    const metodosPagoLabels = {!! json_encode($metodosPagoLabels ?: ['Sin datos']) !!};
                    const metodosPagoData = {!! json_encode($metodosPagoData ?: [0]) !!};
                    
                    // Colores dinámicos según la cantidad de métodos
                    const colores = [
                        { bg: 'rgba(34, 197, 94, 0.7)', border: 'rgba(34, 197, 94, 1)' },
                        { bg: 'rgba(59, 130, 246, 0.7)', border: 'rgba(59, 130, 246, 1)' },
                        { bg: 'rgba(249, 115, 22, 0.7)', border: 'rgba(249, 115, 22, 1)' },
                        { bg: 'rgba(168, 85, 247, 0.7)', border: 'rgba(168, 85, 247, 1)' }
                    ];
                
                window.chartPagos = new Chart(ctxPagos, {
                    type: 'bar',
                    data: {
                        labels: metodosPagoLabels,
                        datasets: [{
                            label: 'Monto (L.)',
                            data: metodosPagoData,
                            backgroundColor: colores.slice(0, metodosPagoLabels.length).map(c => c.bg),
                            borderColor: colores.slice(0, metodosPagoLabels.length).map(c => c.border),
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
                } catch(e) {
                    console.error('Error creando gráfico de pagos:', e);
                }
            }

            // Gráfico de Top Clientes
            const ctxClientes = document.getElementById('chartTopClientes');
            if (ctxClientes && ctxClientes.getContext) {
                try {
                    window.chartClientes = new Chart(ctxClientes, {
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
                } catch(e) {
                    console.error('Error creando gráfico de clientes:', e);
                }
            }
            
            // Marcar como inicializados
            window.chartsInitialized = true;
            console.log('Gráficos inicializados correctamente');
            }, 50); // Pequeño delay para asegurar que el DOM esté listo
        }

        // Solo ejecutar en la primera carga
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', window.initCharts);
        } else {
            // DOM ya está listo
            window.initCharts();
        }
    </script>
</div>
