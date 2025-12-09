<div x-data="{ theme: localStorage.getItem('theme') || 'verde' }">
    <style>
        [x-cloak] { display: none !important; }

        /* Estilos para el modal de búsqueda de productos */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Scroll suave */
        .smooth-scroll {
            scroll-behavior: smooth;
        }

        /* Animación de entrada para cards */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.3s ease-out forwards;
        }

        /* Hover suave para cards de productos */
        .product-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .product-card:hover {
            transform: translateY(-4px);
        }
    </style>
    <!-- Mensaje emergente si el cliente no existe -->
    @if(session('cliente_no_encontrado'))
        <div class="fixed z-50 px-4 py-2 text-white bg-red-500 rounded shadow-lg top-5 right-5">
            {{ session('cliente_no_encontrado') }}
        </div>
    @endif

    <!-- Alertas de descuentos -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show position-fixed"
             style="top: 20px; right: 20px; z-index: 1050; min-width: 300px;">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show position-fixed"
             style="top: 20px; right: 20px; z-index: 1050; min-width: 300px;">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show position-fixed"
             style="top: 20px; right: 20px; z-index: 1050; min-width: 300px;">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Botón flotante para trámites temporales -->
    <div class="position-fixed" style="top: 100px; right: 20px; z-index: 1040;">
        <button wire:click="abrirModalTramitesTemporales"
                class="btn btn-warning shadow-lg d-flex align-items-center gap-2"
                style="border-radius: 50px; padding: 12px 20px; font-weight: 600;">
            <i class="fas fa-folder-open"></i>
            <span>Trámites Temporales</span>
            @if(($cantidadTramitesTemporales ?? 0) > 0)
                <span class="badge bg-danger rounded-pill">
                    {{ $cantidadTramitesTemporales }}
                </span>
            @endif
        </button>
    </div>

    <!-- Modal de selección de clientes -->
    @if($mostrarModalClientesFlag)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalClientes()"
         @keydown.escape.window="$wire.cerrarModalClientes()">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden"
             x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
            <!-- Header con tema -->
            <div class="flex items-center justify-between px-6 py-4 text-white"
                :class="{
                    'bg-emerald-600': theme === 'verde',
                    'bg-blue-600': theme === 'azul',
                    'bg-gray-900': theme === 'oscuro',
                    'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-users me-2"></i>
                    Seleccionar Cliente
                </h2>
                <button wire:click="cerrarModalClientes" class="text-white transition-colors hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!-- Buscador -->
                <div class="mb-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="text-gray-400 fas fa-search"></i>
                        </div>
                        <input type="text"
                            wire:model.live.debounce.300ms="busquedaCliente"
                            class="w-full py-3 pl-10 pr-4 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                            placeholder="Buscar cliente por nombre, identidad, RTN o correo...">
                    </div>
                </div>

                <!-- Tabla de clientes -->
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Identidad/RTN</th>
                                <th>Correo</th>
                                <th>Teléfono</th>
                                <th>Tipo Persona</th>
                                <th>Tipo Cliente</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clientesModal as $cliente_item)
                                <tr class="align-middle transition-colors cursor-pointer hover:bg-blue-50"
                                    wire:click="seleccionarClienteModal({{ $cliente_item->id }})"
                                    title="Clic para seleccionar este cliente">
                                    <td>{{ $cliente_item->id }}</td>

                                    <td class="text-start">
                                        <div>
                                            <strong>{{ $cliente_item->nombre }}</strong><br>
                                            <small class="text-muted">
                                                Registrado: {{ $cliente_item->created_at ? $cliente_item->created_at->format('d/m/Y') : 'N/A' }}
                                            </small>
                                        </div>
                                    </td>

                                    <td>
                                        @if($cliente_item->identidad)
                                            <span class="badge bg-primary">{{ $cliente_item->identidad }}</span>
                                        @endif
                                        @if(!$cliente_item->identidad)
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>

                                    <td>{{ $cliente_item->correo ?? 'N/A' }}</td>

                                    <td>{{ $cliente_item->telefono ?? 'N/A' }}</td>

                                    <td>
                                        @if($cliente_item->tipoPersona)
                                            <span class="badge bg-info">{{ $cliente_item->tipoPersona->nombre }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($cliente_item->tipoCliente)
                                            <span class="badge bg-warning">
                                                @if($cliente_item->tipoCliente->id == 1)
                                                    Cliente A
                                                @elseif($cliente_item->tipoCliente->id == 2)
                                                    Cliente B
                                                @else
                                                    {{ $cliente_item->tipoCliente->nombre }}
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge {{ $cliente_item->estado_id == 1 ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $cliente_item->estado_id == 1 ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-4 text-center text-muted">
                                        <i class="mb-3 fas fa-users fa-2x"></i><br>
                                        No se encontraron clientes
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Información adicional -->
                @if(count($clientesModal) > 0)
                    <div class="mt-4 text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Mostrando {{ count($clientesModal) }} cliente(s). Haz clic en una fila para seleccionar.
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de stock insuficiente -->
    @if($mostrarModalSinStock)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalSinStock()"
         @keydown.escape.window="$wire.cerrarModalSinStock()">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 text-white bg-red-600">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Stock Insuficiente
                </h2>
                <button wire:click="cerrarModalSinStock" class="text-white transition-colors hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6">
                <div class="text-center">
                    <div class="mx-auto mb-4 text-red-500">
                        <i class="fas fa-times-circle fa-4x"></i>
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-800">
                        No hay suficiente stock disponible
                    </h3>
                    <p class="mb-6 text-gray-600">
                        La cantidad solicitada excede el stock disponible en bodega. Por favor, verifica el inventario o reduce la cantidad.
                    </p>

                    <div class="flex justify-center">
                        <button
                            wire:click="cerrarModalSinStock"
                            class="px-6 py-2 text-white bg-red-600 border border-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-200">
                            <i class="fas fa-check me-2"></i>
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- CONTENIDO PRINCIPAL: Siempre visible (modo manual por defecto) -->
    <div class="mx-auto max-w-7xl">

            <!-- 1. INFORMACIÓN DEL CLIENTE (arriba, ancho completo) -->
            <div class="mb-6 bg-white border border-gray-300 rounded-lg shadow-lg" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-3 font-semibold text-white rounded-t"
                    :class="{
                        'bg-emerald-600': theme === 'verde',
                        'bg-blue-600': theme === 'azul',
                        'bg-gray-900': theme === 'oscuro',
                        'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                    }"
                >
                    <h3 class="mb-0 text-lg">
                        <i class="fas fa-user me-2"></i>
                        Información del Cliente
                    </h3>
                </div>

                <!-- Content -->
                <div class="p-4">
                    <!-- ALERTAS DEL CAI -->
                    @if($alertaCAI)
                        @php
                            $esCritico = str_contains($alertaCAI, 'CRÍTICO') || str_contains($alertaCAI, 'ERROR');
                            $esAviso = str_contains($alertaCAI, 'AVISO') || str_contains($alertaCAI, 'ATENCIÓN');
                        @endphp

                        <div class="alert mb-3 p-3 rounded {{ $esCritico ? 'bg-red-50 border border-red-200' : ($esAviso ? 'bg-yellow-50 border border-yellow-200' : 'bg-blue-50 border border-blue-200') }}">
                            <i class="fas {{ $esCritico ? 'fa-times-circle text-red-600' : ($esAviso ? 'fa-exclamation-triangle text-yellow-600' : 'fa-info-circle text-blue-600') }}"></i>
                            <span class="{{ $esCritico ? 'text-red-800' : ($esAviso ? 'text-yellow-800' : 'text-blue-800') }}">
                                <strong>CAI:</strong> {{ $alertaCAI }}
                            </span>
                        </div>
                    @endif

                    <!-- Información de bodega y alertas de stock -->
                    @if($bodegaPrincipal)
                        <div class="mb-3 alert alert-info d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-warehouse fa-lg"></i>
                            </div>
                            <div>
                                <strong>Tienda:</strong> {{ $tiendaUsuario }}<br>
                                <strong>Bodega Principal:</strong> {{ $bodegaPrincipal->nombre }}
                            </div>
                        </div>
                    @else
                        <div class="mb-3 alert alert-warning d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-exclamation-triangle fa-lg"></i>
                            </div>
                            <div>
                                <strong>Advertencia:</strong> No se encontró bodega principal para su tienda
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4">
                        <!-- Primera fila: RTN/Identidad y Nombre -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">
                                    RTN / Número de Identidad
                                </label>
                                <div class="relative">
                                    <input type="text"
                                        wire:model.live.debounce.500ms="rtnManual"
                                        wire:keydown.enter="buscarClientePorRtn"
                                        class="w-full px-3 py-2 pr-10 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200 {{ $camposBloqueados ? 'bg-gray-100' : '' }}"
                                        placeholder="RTN/Identidad (13-15 dígitos) - Enter para buscar"
                                        maxlength="15"
                                        {{ $camposBloqueados ? 'readonly' : '' }}>
                                    <button type="button"
                                        wire:click="mostrarModalClientes"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-blue-600"
                                        title="Buscar cliente">
                                        <!-- Icono de lupa (SVG) -->
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Nombre del Cliente</label>
                                <input type="text"
                                    wire:model="nombreClienteManual"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200 {{ $camposBloqueados ? 'bg-gray-100' : '' }}"
                                    placeholder="Ingrese nombre del cliente"
                                    {{ $camposBloqueados ? 'readonly' : '' }}>
                            </div>
                        </div>

                        <!-- Segunda fila: Correo y Teléfono -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                                <input type="email"
                                    wire:model="correoClienteManual"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200 {{ $camposBloqueados ? 'bg-gray-100' : '' }}"
                                    placeholder="correo@ejemplo.com"
                                    {{ $camposBloqueados ? 'readonly' : '' }}>
                            </div>

                            <div class="space-y-1" x-data="{ error: false }"
                                @marcar-campo-error.window="if($event.detail === 'telefonoClienteManual') { error = true; setTimeout(() => error = false, 3000) }">
                                <label class="block text-sm font-medium text-gray-700">Teléfono *</label>
                                <input type="tel"
                                    wire:model="telefonoClienteManual"
                                    :class="error ? 'border-red-500 ring-1 ring-red-200 bg-red-50' : 'border-gray-300'"
                                    class="w-full px-3 py-2 border rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200 {{ $camposBloqueados ? 'bg-gray-100' : '' }}"
                                    placeholder="+504 0000-0000"
                                    {{ $camposBloqueados ? 'readonly' : '' }}>
                            </div>
                        </div>

                        <!-- Tercera fila: Dirección -->
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700">Dirección</label>
                            <textarea wire:model="direccionClienteManual"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200 {{ $camposBloqueados ? 'bg-gray-100' : '' }}"
                                rows="2"
                                placeholder="Dirección completa del cliente"
                                {{ $camposBloqueados ? 'readonly' : '' }}></textarea>
                        </div>

                        <!-- Cuarta fila: Tipo de Persona y Tipo de Cliente -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Tipo de Persona</label>
                                <select wire:model="tipoPersonaId"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200 {{ $camposBloqueados ? 'bg-gray-100' : '' }}"
                                    {{ $camposBloqueados ? 'disabled' : '' }}>
                                    <option value="">Seleccione tipo de persona</option>
                                    @forelse($tiposPersona as $tipoPersona)
                                        <option value="{{ $tipoPersona->id }}">{{ $tipoPersona->nombre }}</option>
                                    @empty
                                        <option value="1">Natural</option>
                                    @endforelse
                                </select>
                                @if($camposBloqueados && isset($tiposPersona))
                                    @php
                                        $tipoPersonaSeleccionada = $tiposPersona->firstWhere('id', $tipoPersonaId);
                                    @endphp
                                    @if($tipoPersonaSeleccionada)
                                        <p class="text-sm text-gray-600">{{ $tipoPersonaSeleccionada->nombre }}</p>
                                    @endif
                                @endif
                            </div>

                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Tipo de Cliente</label>
                                <select wire:model="tipoClienteId"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200 {{ $camposBloqueados ? 'bg-gray-100' : '' }}"
                                    {{ $camposBloqueados ? 'disabled' : '' }}>
                                    <option value="">Seleccione tipo de cliente</option>
                                    @forelse($tiposCliente as $tipoCliente)
                                        <option value="{{ $tipoCliente->id }}">
                                            @if($tipoCliente->id == 1)
                                                Cliente A
                                            @elseif($tipoCliente->id == 2)
                                                Cliente B
                                            @else
                                                {{ $tipoCliente->nombre }}
                                            @endif
                                        </option>
                                    @empty
                                        <option value="1">Cliente A</option>
                                        <option value="2">Cliente B</option>
                                    @endforelse
                                </select>
                                @if($camposBloqueados && isset($tiposCliente))
                                    @php
                                        $tipoClienteSeleccionado = $tiposCliente->firstWhere('id', $tipoClienteId);
                                    @endphp
                                    @if($tipoClienteSeleccionado)
                                        <p class="text-sm text-gray-600">
                                            @if($tipoClienteSeleccionado->id == 1)
                                                Cliente A
                                            @elseif($tipoClienteSeleccionado->id == 2)
                                                Cliente B
                                            @else
                                                {{ $tipoClienteSeleccionado->nombre }}
                                            @endif
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <!-- Botones de control -->
                        <div class="flex flex-wrap gap-2">
                            @if(!$camposBloqueados)
                                <button type="button"
                                    wire:click="guardarClienteManual"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-200">
                                    <!-- Icono de check (SVG) -->
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Guardar Cliente
                                </button>
                            @endif

                            <button type="button"
                                wire:click="limpiarDatosCliente"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200">
                                <!-- Icono de basura (SVG) -->
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Limpiar Datos
                            </button>

                            @if($camposBloqueados)
                                <div class="inline-flex items-center px-3 py-2 text-sm text-green-700 bg-green-100 border border-green-200 rounded-lg">
                                    <!-- Icono de candado (SVG) -->
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    Datos bloqueados
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. LAYOUT DINÁMICO: FACTURA (ancho completo si no hay catálogo, o 2/3 si hay catálogo) -->
            <div class="grid grid-cols-1 gap-6 {{ $mostrarCatalogoVisual ? 'lg:grid-cols-3' : 'lg:grid-cols-1' }}">

                <!-- 2.1 FACTURA (ancho completo o 2 columnas según disponibilidad del catálogo) -->
                <div class="{{ $mostrarCatalogoVisual ? 'lg:col-span-2' : 'lg:col-span-1' }} bg-white border border-gray-300 rounded-lg shadow-lg" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-3 font-semibold text-white rounded-t"
                        :class="{
                            'bg-emerald-600': theme === 'verde',
                            'bg-blue-600': theme === 'azul',
                            'bg-gray-900': theme === 'oscuro',
                            'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }"
                    >
                        <h3 class="mb-0 text-lg">
                            🧾 Facturación
                        </h3>
                    </div>

                    <!-- Content -->
                    <div class="p-4">
                        <!-- Escanear producto -->
                        <div x-data="{
                            init() {
                                this.$el.querySelector('#codigo_barras').focus();
                            }
                        }" class="mb-4">
                            <form wire:submit.prevent="agregarProductoPorCodigo">
                                <div class="mb-4">
                                    <!-- Contenedor del código de barras y botón -->
                                    <div class="space-y-1">
                                        <label for="codigo_barras" class="block text-sm font-medium text-gray-700">Escanear código de barras</label>
                                        <div class="flex">
                                            <div class="flex-1">
                                                <input type="text"
                                                    id="codigo_barras"
                                                    wire:model.defer="codigoBarras"
                                                    wire:keydown.enter="agregarProductoPorCodigo"
                                                    class="w-full h-10 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                                                    placeholder="Escanee el código de barras"
                                                    autocomplete="off"
                                                    @keydown.enter="$event.target.value = ''; $event.target.focus()"
                                                    @enfocar-input-codigo.window="$event.target.focus()"
                                                    autofocus>
                                            </div>
                                            <button type="button"
                                                wire:click="abrirModal('busqueda')"
                                                class="flex items-center h-10 px-4 font-medium text-white transition-colors border-l-0 rounded-r-lg whitespace-nowrap"
                                                :class="{
                                                    'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                                    'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                                    'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                                    'bg-slate-700 hover:bg-slate-600': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                                                }">
                                                <i class="fas fa-search"></i>
                                                <span class="ml-2">Buscar</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Lista de productos agregados a la factura -->
                        <div class="mb-4">
                            <div class="table-responsive">
                                <table class="table text-center table-sm table-bordered" style="font-size: 0.7rem;">
                                    <thead class="table-light">
                                        <tr style="font-size: 0.65rem;">
                                            <th>Producto/Servicio</th>
                                            <th>Código de Barras</th>
                                            <th>Precio Unit.</th>
                                            <th>Cantidad</th>
                                            <th>Subtotal</th>
                                            <th>ISV</th>
                                            <th>Total</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-size: 0.65rem;" class="text-center">
                                        @forelse($productosFactura as $item)
                                        @php
                                            // Calcular precio unitario sin ISV (precio incluye ISV)
                                            $tasaIsv = $item['isv'] ?? 0;
                                            if ($tasaIsv > 0) {
                                                $precioUnitarioSinIsv = round($item['precio'] / (1 + ($tasaIsv / 100)), 2);
                                            } else {
                                                $precioUnitarioSinIsv = $item['precio'];
                                            }

                                            // Subtotal inicial: precio unitario sin ISV × cantidad
                                            $subtotalOriginal = round($precioUnitarioSinIsv * $item['cantidad'], 2);

                                            // Descuentos aplicados
                                            $descuentoAplicado = round($item['descuento_aplicado'] ?? 0, 2); // Descuento por edad (total)
                                            $descuentoUnitarioBase = round($item['descuento_unitario_producto'] ?? 0, 2); // Descuento por unidad base
                                            $descuentoIndividual = round($item['descuento_monto'] ?? 0, 2); // Descuento individual por producto

                                            // El descuento unitario se aplica POR CADA CANTIDAD
                                            $descuentoUnitarioTotal = round($descuentoUnitarioBase * $item['cantidad'], 2);

                                            // ISV y Subtotal YA CALCULADOS en el backend (precio incluye ISV)
                                            $isv = round($item['isv_calculado'] ?? 0, 2);
                                            $subtotalConDescuento = round($item['subtotal_con_descuento'] ?? 0, 2);

                                            // Total final del producto
                                            $total = round($item['total'] ?? 0, 2);

                                            // Determinar si es producto o servicio
                                            $esServicio = isset($item['servicio_id']) && $item['servicio_id'] !== null;

                                            // Calcular stock disponible (stock total - cantidad en carrito)
                                            if (!$esServicio) {
                                                // NUEVO: Usar stock_total_unidad si está disponible (para productos con unidades de medida)
                                                if (isset($item['stock_total_unidad']) && isset($item['precio_id'])) {
                                                    // Calcular la suma de TODAS las cantidades en el carrito para este producto+precio_id
                                                    $cantidadTotalEnCarrito = 0;
                                                    foreach($productosFactura as $itemCarrito) {
                                                        if ($itemCarrito['id'] == $item['id'] &&
                                                            isset($itemCarrito['precio_id']) &&
                                                            $itemCarrito['precio_id'] == $item['precio_id']) {
                                                            $cantidadTotalEnCarrito += (int)($itemCarrito['cantidad'] ?? 0);
                                                        }
                                                    }

                                                    // Stock disponible = stock en bodega - total en carrito
                                                    $stockDisponible = max(0, $item['stock_total_unidad'] - $cantidadTotalEnCarrito);
                                                } else {
                                                    // Sistema anterior: calcular con cantidad por unidad
                                                    $cantidadPorUnidad = $item['cantidad_por_unidad'] ?? 1;
                                                    $cantidadEnCarrito = $item['cantidad'] ?? 0;
                                                    $stockDisponible = $this->obtenerStockDisponibleConUnidad(
                                                        $item['id'], 
                                                        $cantidadPorUnidad, 
                                                        $cantidadEnCarrito, 
                                                        $loop->index,
                                                        $item['precio_id'] ?? null
                                                    );
                                                }
                                            } else {
                                                $stockDisponible = null;
                                            }
                                        @endphp
                                        <tr class="{{ $esServicio ? 'table-info' : '' }}" wire:key="item-{{ $loop->index }}-{{ $item['cantidad'] }}">
                                            <td>
                                                {{ $item['nombre'] }}
                                                @if(!$esServicio)
                                                    <br>
                                                    <small class="text-gray-500">
                                                        Stock disponible: {{ $stockDisponible }}
                                                        @if(isset($item['unidad_medida_nombre']))
                                                            {{ $item['unidad_medida_nombre'] }}
                                                        @endif
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!$esServicio && isset($item['precios_disponibles']))
                                                    @php
                                                        $indiceActual = $loop->index;
                                                    @endphp
                                                    <!-- Selector de unidad de medida -->
                                                    <select class="form-select form-select-sm"
                                                            style="min-width: 150px; font-size: 0.75rem;"
                                                            wire:change="cambiarUnidadProducto({{ $indiceActual }}, $event.target.value)">
                                                        @foreach($item['precios_disponibles'] as $precioDisp)
                                                            @php
                                                                // Calcular stock disponible para esta presentación (precio_id)
                                                                $stockUnidad = $this->calcularStockTotalPorUnidad(
                                                                    $item['id'], 
                                                                    $precioDisp->unidad_medida_id,
                                                                    $precioDisp->precio_id
                                                                );
                                                                $cantidadEnCarritoUnidad = 0;
                                                                foreach($productosFactura as $idx => $otroItem) {
                                                                    if ($idx !== $indiceActual && 
                                                                        $otroItem['id'] == $item['id'] &&
                                                                        isset($otroItem['precio_id']) &&
                                                                        $otroItem['precio_id'] == $precioDisp->precio_id) {
                                                                        $cantidadEnCarritoUnidad += (int)($otroItem['cantidad'] ?? 0);
                                                                    }
                                                                }
                                                                $stockDisponibleUnidad = $stockUnidad - $cantidadEnCarritoUnidad;
                                                                $tieneStock = $stockDisponibleUnidad > 0;
                                                            @endphp
                                                            
                                                            @if($tieneStock || ($item['precio_id'] ?? null) == $precioDisp->precio_id)
                                                                <option value="{{ $precioDisp->precio_id }}"
                                                                        {{ ($item['precio_id'] ?? null) == $precioDisp->precio_id ? 'selected' : '' }}>
                                                                    {{ $precioDisp->unidad_nombre }}
                                                                    @if($precioDisp->descripcion)
                                                                        - {{ $precioDisp->descripcion }}
                                                                    @endif
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <small class="d-block text-muted mt-1">{{ $item['codigo'] }}</small>
                                                @else
                                                    {{ $item['codigo'] ?? 'N/A' }}
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    // Calcular precio unitario sin ISV (precio incluye ISV)
                                                    $tasaIsv = $item['isv'] ?? 0;
                                                    if ($tasaIsv > 0) {
                                                        $precioUnitarioSinIsv = round($item['precio'] / (1 + ($tasaIsv / 100)), 2);
                                                    } else {
                                                        $precioUnitarioSinIsv = $item['precio'];
                                                    }
                                                @endphp
                                                @if($esServicio)
                                                    L. {{ number_format($precioUnitarioSinIsv, 2) }}
                                                @elseif(isset($item['precios_disponibles']) && !empty($item['precios_disponibles']))
                                                    L. {{ number_format($precioUnitarioSinIsv, 2) }}
                                                @else
                                                    <!-- Sistema anterior: Dropdown para seleccionar precio (Valencia) -->
                                                    <select class="form-select form-select-sm"
                                                            style="min-width: 120px; font-size: 0.875rem;"
                                                            wire:change="cambiarPrecioProducto({{ $loop->index }}, $event.target.value)">

                                                        @php
                                                            $precios = [];

                                                            // Si es producto de Paperland (producto_valencia = 0)
                                                            if (($item['producto_valencia'] ?? 1) == 0) {
                                                                $precios['precio_base'] = $item['precio_base'] ?? 0;
                                                            } else {
                                                                // Productos de Valencia: agregar precios 1-4 disponibles
                                                                if (($item['precio1'] ?? 0) > 0) $precios['precio1'] = $item['precio1'];
                                                                if (($item['precio2'] ?? 0) > 0) $precios['precio2'] = $item['precio2'];
                                                                if (($item['precio3'] ?? 0) > 0) $precios['precio3'] = $item['precio3'];
                                                                if (($item['precio4'] ?? 0) > 0) $precios['precio4'] = $item['precio4'];

                                                                // Siempre agregar precio_base como opción
                                                                $precios['precio_base'] = $item['precio_base'] ?? 0;
                                                            }

                                                            $tipoPrecioActual = $item['tipo_precio'] ?? 'precio_base';
                                                        @endphp

                                                        @foreach($precios as $tipo => $precio)
                                                            @php
                                                                // Calcular precio sin ISV para mostrar
                                                                if ($tasaIsv > 0) {
                                                                    $precioSinIsv = round($precio / (1 + ($tasaIsv / 100)), 2);
                                                                } else {
                                                                    $precioSinIsv = $precio;
                                                                }
                                                            @endphp
                                                            <option value="{{ $tipo }}"
                                                                    {{ $tipoPrecioActual == $tipo ? 'selected' : '' }}>
                                                                @php
                                                                    $nombrePrecio = $tipo;
                                                                    switch($tipo) {
                                                                        case 'precio1':
                                                                            $nombrePrecio = 'Precio A';
                                                                            break;
                                                                        case 'precio2':
                                                                            $nombrePrecio = 'Precio B';
                                                                            break;
                                                                        case 'precio3':
                                                                            $nombrePrecio = 'Precio C';
                                                                            break;
                                                                        case 'precio4':
                                                                            $nombrePrecio = 'Precio D';
                                                                            break;
                                                                        case 'precio_base':
                                                                            $nombrePrecio = 'Precio Base';
                                                                            break;
                                                                    }
                                                                @endphp
                                                                {{ $nombrePrecio }}: L. {{ number_format($precioSinIsv, 2) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </td>
                                            <td>
                                                @if($esServicio)
                                                    <!-- Para servicios, cantidad editable sin restricción de stock -->
                                                    <input type="number"
                                                        wire:key="servicio-{{ $loop->index }}-{{ $item['cantidad'] }}-{{ $item['unidad_medida_id'] ?? 0 }}"
                                                        wire:model.lazy="productosFactura.{{ $loop->index }}.cantidad"
                                                        @input="
                                                            let val = parseInt($event.target.value) || 1;
                                                            if(val < 1) {
                                                                $event.target.value = 1;
                                                            }
                                                        "
                                                        min="1"
                                                        class="w-20 text-center form-control"
                                                        style="min-width: 60px;">
                                                @elseif(isset($item['precios_disponibles']) && !empty($item['precios_disponibles']))
                                                    @php
                                                        // Calcular cuánto hay en OTRAS líneas del mismo producto+unidad
                                                        $cantidadEnOtrasLineas = 0;
                                                        foreach($productosFactura as $idx => $otroItem) {
                                                            if ($idx != $loop->index &&
                                                                $otroItem['id'] == $item['id'] &&
                                                                isset($otroItem['unidad_medida_id']) &&
                                                                $otroItem['unidad_medida_id'] == $item['unidad_medida_id']) {
                                                                $cantidadEnOtrasLineas += (int)($otroItem['cantidad'] ?? 0);
                                                            }
                                                        }
                                                        // Stock máximo para esta línea = stock total - lo que hay en otras líneas
                                                        $stockMaxParaEstaLinea = max(0, ($item['stock_total_unidad'] ?? 0) - $cantidadEnOtrasLineas);
                                                    @endphp
                                                    <!-- Nuevo sistema: Cantidad editable limitada por stock_total_unidad -->
                                                    <input type="number"
                                                        wire:key="producto-{{ $loop->index }}-{{ $item['cantidad'] }}-{{ $item['unidad_medida_id'] ?? 0 }}"
                                                        wire:model.lazy="productosFactura.{{ $loop->index }}.cantidad"
                                                        x-data="{
                                                            stockMax: {{ $stockMaxParaEstaLinea }}
                                                        }"
                                                        @input="
                                                            let val = parseInt($event.target.value) || 1;
                                                            if(val < 1) {
                                                                $event.target.value = 1;
                                                            } else if(val > stockMax) {
                                                                $event.target.value = stockMax;
                                                            }
                                                        "
                                                        min="1"
                                                        max="{{ $stockMaxParaEstaLinea }}"
                                                        class="w-20 text-center form-control"
                                                        style="min-width: 60px;"
                                                        title="Stock disponible: {{ $stockDisponible }}">
                                                @else
                                                    <!-- Sistema anterior: Para productos, cantidad limitada por stock -->
                                                    <input type="number"
                                                        wire:key="producto-{{ $loop->index }}-{{ $item['cantidad'] }}-{{ $item['unidad_medida_id'] ?? 0 }}"
                                                        wire:model.lazy="productosFactura.{{ $loop->index }}.cantidad"
                                                        x-data="{
                                                            stockMax: {{ $stockDisponible }}
                                                        }"
                                                        @input="
                                                            let val = parseInt($event.target.value) || 1;
                                                            if(val < 1) {
                                                                $event.target.value = 1;
                                                            } else if(val > stockMax) {
                                                                $event.target.value = stockMax;
                                                            }
                                                        "
                                                        min="1"
                                                        max="{{ $stockDisponible }}"
                                                        class="w-20 text-center form-control"
                                                        style="min-width: 60px;"
                                                        title="Stock disponible: {{ $stockDisponible }}">
                                                @endif
                                            </td>
                                            <td>
                                                <!-- Mostrar importe original (precio × cantidad SIN descuentos) -->
                                                <div class="fw-bold">L. {{ number_format($subtotalOriginal, 2) }}</div>

                                                <!-- Mostrar descuentos de productos/servicios guardados primero (si existen) -->
                                                @if($esServicio && isset($descuentosGuardados[$item['servicio_id']]))
                                                    <div class="text-danger small fw-bold">-L. {{ number_format($descuentosGuardados[$item['servicio_id']]['monto_total'], 2) }}</div>
                                                @elseif(!$esServicio && isset($descuentosGuardados[$item['id']]))
                                                    <div class="text-danger small fw-bold">-L. {{ number_format($descuentosGuardados[$item['id']]['monto_total'], 2) }}</div>
                                                @elseif($descuentoUnitarioTotal > 0)
                                                    <!-- Mostrar solo el total del descuento unitario en rojo -->
                                                    <div class="text-danger small fw-bold">-L. {{ number_format($descuentoUnitarioTotal, 2) }}</div>
                                                @endif

                                                <!-- Mostrar descuento individual por producto (si existe) -->
                                                @if($descuentoIndividual > 0)
                                                    <div class="text-warning small fw-bold">
                                                        -L. {{ number_format($descuentoIndividual, 2) }}
                                                        @if(isset($item['porcentaje_descuento']) && $item['porcentaje_descuento'] > 0)
                                                            ({{ number_format($item['porcentaje_descuento'], 1) }}%)
                                                        @endif
                                                    </div>
                                                @endif

                                                <!-- Mostrar descuento de tercera/cuarta edad después -->
                                                @if($descuentoAplicado > 0)
                                                    <div class="text-danger small fw-bold">-L. {{ number_format($descuentoAplicado, 2) }}</div>
                                                @endif

                                                <!-- Mostrar subtotal final con descuentos aplicados si hay descuentos -->
                                                @if($descuentoUnitarioTotal > 0 || $descuentoIndividual > 0 || $descuentoAplicado > 0 ||
                                                    ($esServicio && isset($descuentosGuardados[$item['servicio_id']])) ||
                                                    (!$esServicio && isset($descuentosGuardados[$item['id']])))
                                                    <div class="pt-1 mt-1 text-success fw-bold border-top">L. {{ number_format($subtotalConDescuento, 2) }}</div>
                                                @endif
                                            </td>
                                            <td>L. {{ number_format($isv, 2) }}
                                                <span class="text-xs text-gray-500">({{ $item['isv'] }}%)</span>
                                            </td>
                                            <td>L. {{ number_format($total, 2) }}</td>
                                            <td class="text-center">
                                                <!-- Botón de descuento por producto -->
                                                <button wire:click="mostrarModalDescuentoProducto({{ $loop->index }})"
                                                    class="p-1 mr-2 transition-opacity btn btn-link hover:opacity-75"
                                                    title="Aplicar descuento">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" style="color:#f59e0b;" />
                                                    </svg>
                                                </button>

                                                <!-- Botón de eliminar -->
                                                <button wire:click="eliminarProducto({{ $loop->index }})"
                                                    class="p-0 transition-opacity btn btn-link hover:opacity-75"
                                                    title="Eliminar producto">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7v12a2 2 0 002 2h8a2 2 0 002-2V7M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m-7 0h10" style="color:#e3342f;" />
                                                        <line x1="10" y1="11" x2="10" y2="17" stroke="#e3342f" stroke-width="2"/>
                                                        <line x1="14" y1="11" x2="14" y2="17" stroke="#e3342f" stroke-width="2"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No hay productos o servicios agregados</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Botones de Descuento -->
                        @if(count($productosFactura) > 0)
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <!-- Botón Descuento General a la izquierda -->
                            <button
                                wire:click="abrirModalDescuentoFactura"
                                class="btn btn-primary"
                                title="Aplicar descuento general a toda la factura">
                                <i class="fas fa-percentage me-1"></i>
                                {{ $descuentoFactura > 0 ? 'Desc. Factura: ' . $descuentoFactura . '%' : 'Descuento Factura' }}
                            </button>

                            <!-- Botones de edad a la derecha -->
                            <div class="d-flex gap-2">
                                <!-- Botón 3ra Edad -->
                                <button
                                    wire:click="aplicarDescuentoTerceraEdad"
                                    class="btn me-2 {{ $descuentoTerceraEdad ? 'btn-danger' : 'btn-success' }} {{ $descuentoCuartaEdad ? 'opacity-50' : '' }}"
                                    {{ $descuentoCuartaEdad ? 'disabled' : '' }}
                                    style="{{ $descuentoCuartaEdad ? 'cursor: not-allowed;' : 'cursor: pointer;' }}"
                                    title="{{ $descuentoCuartaEdad ? 'Deshabilitado: ya hay un descuento de 4ta edad aplicado' : 'Descuento para personas de 60-64 años' }}">
                                    <i class="fas fa-user-friends me-1"></i>
                                    {{ $descuentoTerceraEdad ? 'Remover' : 'Aplicar' }} 3ra Edad
                                </button>

                                <!-- Botón 4ta Edad -->
                                <button
                                    wire:click="aplicarDescuentoCuartaEdad"
                                    class="btn {{ $descuentoCuartaEdad ? 'btn-danger' : 'btn-success' }} {{ $descuentoTerceraEdad ? 'opacity-50' : '' }}"
                                    {{ $descuentoTerceraEdad ? 'disabled' : '' }}
                                    style="{{ $descuentoTerceraEdad ? 'cursor: not-allowed;' : 'cursor: pointer;' }}"
                                    title="{{ $descuentoTerceraEdad ? 'Deshabilitado: ya hay un descuento de 3ra edad aplicado' : 'Descuento para personas de 65+ años' }}">
                                    <i class="fas fa-user-check me-1"></i>
                                    {{ $descuentoCuartaEdad ? 'Remover' : 'Aplicar' }} 4ta Edad
                                </button>
                            </div>
                        </div>
                        @endif

                        <!-- Totales -->
                        <div class="flex justify-end mb-4" x-data="{
                            subtotal: @entangle('subtotal'),
                            subtotalBruto: @entangle('subtotalBruto'),
                            totalIsv: @entangle('totalIsv'),
                            total: @entangle('total'),
                            totalDescuentos: @entangle('totalDescuentos'),
                            isvPorTasa: @entangle('isvPorTasa')
                        }">
                            <div class="w-full max-w-md p-4 border border-gray-300 rounded-lg bg-gray-50">
                                <!-- Encabezado -->
                                <div class="mb-3 text-center">
                                    <h6 class="mb-0 font-bold text-gray-700">RESUMEN DE FACTURACIÓN</h6>
                                    <hr class="mt-2">
                                </div>

                                <!-- Subtotal (sin ISV, sin descuentos) -->
                                <div class="flex justify-between mb-2">
                                    <span class="font-medium text-gray-700">Subtotal:</span>
                                    <span class="font-medium" x-text="'L. ' + parseFloat(subtotalBruto).toFixed(2)">L. {{ number_format($subtotalBruto, 2) }}</span>
                                </div>

                                <!-- Desglose de descuentos -->
                                @if($totalDescuentos > 0)
                                    <div class="pl-3 mb-2 border-l-4 border-red-400 bg-red-50">
                                        <div class="flex justify-between mb-1">
                                            <span class="font-medium text-red-700">
                                                <i class="mr-1 fas fa-minus-circle"></i>
                                                Total Descuentos:
                                            </span>
                                            <span class="font-medium text-red-700" x-text="'-L. ' + parseFloat(totalDescuentos).toFixed(2)">-L. {{ number_format($totalDescuentos, 2) }}</span>
                                        </div>

                                        <!-- Desglose por tipo de descuento -->
                                        <div class="mt-1 ml-2 space-y-1">
                                            @if($descuentoTerceraEdad)
                                                @php
                                                    $totalDescuentoTerceraEdad = 0;
                                                    foreach($productosFactura as $item) {
                                                        if(($item['descuento_tercera'] ?? 0) == 1) {
                                                            $subtotalItem = $item['precio'] * $item['cantidad'];
                                                            $totalDescuentoTerceraEdad += $subtotalItem * 0.25;
                                                        }
                                                    }
                                                @endphp
                                                <div class="flex justify-between text-sm text-red-600">
                                                    <span class="ml-2">• Descuento tercera edad (25%):</span>
                                                    <span class="font-medium">L. {{ number_format($totalDescuentoTerceraEdad, 2) }}</span>
                                                </div>
                                            @endif
                                            @if($descuentoCuartaEdad)
                                                @php
                                                    $totalDescuentoCuartaEdad = 0;
                                                    foreach($productosFactura as $item) {
                                                        if(($item['descuento_cuarta'] ?? 0) == 1) {
                                                            $subtotalItem = $item['precio'] * $item['cantidad'];
                                                            $totalDescuentoCuartaEdad += $subtotalItem * 0.35;
                                                        }
                                                    }
                                                @endphp
                                                <div class="flex justify-between text-sm text-red-600">
                                                    <span class="ml-2">• Descuento cuarta edad (35%):</span>
                                                    <span class="font-medium">L. {{ number_format($totalDescuentoCuartaEdad, 2) }}</span>
                                                </div>
                                            @endif
                                            @php
                                                // Calcular total de descuentos de productos individuales
                                                $totalDescuentosIndividuales = 0;
                                                foreach($productosFactura as $item) {
                                                    $totalDescuentosIndividuales += $item['descuento_monto'] ?? 0;
                                                }
                                                // Calcular total de descuentos unitarios
                                                $totalDescuentosUnitarios = array_sum(array_column($productosFactura, 'descuento_unitario_aplicado', 0));
                                                // Suma total de descuentos de productos (unitarios + individuales)
                                                $totalDescuentosProductos = $totalDescuentosIndividuales + $totalDescuentosUnitarios;
                                            @endphp
                                            @if($totalDescuentosProductos > 0)
                                                <div class="flex justify-between text-sm text-red-600">
                                                    <span class="ml-2">• Descuentos unitarios:</span>
                                                    <span class="font-medium">L. {{ number_format($totalDescuentosProductos, 2) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Subtotal con descuentos -->
                                    <div class="flex justify-between mb-2 font-medium text-gray-700">
                                        <span>Subtotal con descuentos:</span>
                                        <span x-text="'L. ' + parseFloat(subtotal).toFixed(2)">L. {{ number_format($subtotal, 2) }}</span>
                                    </div>
                                @endif

                                <!-- Descuento general de la factura -->
                                @if($descuentoFactura > 0)
                                    <div class="pl-3 mb-2 border-l-4 border-orange-400 bg-orange-50">
                                        <div class="flex justify-between mb-1">
                                            <span class="font-medium text-orange-700">
                                                <i class="mr-1 fas fa-tag"></i>
                                                Descuento Factura ({{ $descuentoFactura }}%):
                                            </span>
                                            <span class="font-medium text-orange-700">-L. {{ number_format($montoDescuentoFactura, 2) }}</span>
                                        </div>
                                    </div>

                                    <!-- Subtotal después del descuento de factura -->
                                    <div class="flex justify-between mb-2 font-medium text-gray-700">
                                        <span>Subtotal final:</span>
                                        <span>L. {{ number_format($subtotal - $montoDescuentoFactura, 2) }}</span>
                                    </div>
                                @endif

                                <!-- Desglose del ISV -->
                                @if($totalIsv > 0)
                                    <div class="pl-3 mb-2 border-l-4 border-blue-400 bg-blue-50">
                                        <div class="flex justify-between mb-1">
                                            <span class="font-medium text-blue-700">
                                                <i class="mr-1 fas fa-plus-circle"></i>
                                                Total ISV:
                                            </span>
                                            <span class="font-medium text-blue-700">L. {{ number_format(round($totalIsv, 2), 2) }}</span>
                                        </div>

                                        <!-- Desglose del ISV por tasa si está disponible -->
                                        @if(isset($isvPorTasa) && is_array($isvPorTasa) && count($isvPorTasa) > 0)
                                            @foreach($isvPorTasa as $tasa => $monto)
                                                @if($monto > 0)
                                                    <div class="flex justify-between text-sm text-blue-600">
                                                        <span class="ml-4">• ISV {{ $tasa }}%:</span>
                                                        <span>L. {{ number_format($monto, 2) }}</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @else
                                            <div class="flex justify-between text-sm text-blue-600">
                                                <span class="ml-4">• ISV 15%:</span>
                                                <span x-text="'L. ' + parseFloat(totalIsv).toFixed(2)">L. {{ number_format($totalIsv, 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <hr class="my-3 border-gray-400">

                                <!-- Total final -->
                                <div class="flex justify-between p-3 bg-green-100 border border-green-300 rounded">
                                    <span class="text-xl font-bold text-green-800">
                                        <i class="mr-2 fas fa-calculator"></i>
                                        TOTAL A PAGAR:
                                    </span>
                                    <span class="text-xl font-bold text-green-800" x-text="'L. ' + parseFloat(total).toFixed(2)">L. {{ number_format($total, 2) }}</span>
                                </div>

                                <!-- Información adicional -->
                                <div class="mt-3 text-xs text-center text-gray-500">
                                    @if(count($productosFactura) > 0)
                                        {{ count($productosFactura) }} artículo(s) en la factura
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Botón Procesar Factura -->
                        @if(count($productosFactura) > 0)
                        <div class="mt-4 text-center">
                            <div class="gap-3 d-flex flex-column flex-md-row justify-content-center">
                                <button type="button"
                                    wire:click="guardarTramiteTemporal"
                                    class="px-4 py-3 btn btn-warning"
                                    style="font-size: 1rem; font-weight: 600;">
                                    <i class="fas fa-save me-2"></i>
                                    Guardar Temporal
                                </button>

                                <button type="button"
                                    wire:click="mostrarModalPago"
                                    class="px-5 py-3 btn btn-primary btn-lg"
                                    style="font-size: 1.1rem; font-weight: 600;">
                                    <i class="fas fa-file-invoice-dollar me-2"></i>
                                    Procesar Factura
                                </button>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>



        </div>  <!--End contenedor principal -->

    <!-- Modal de Búsqueda Avanzada -->
    @if($mostrarModalBusqueda)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModal('busqueda')"
         @keydown.escape.window="$wire.cerrarModal('busqueda')">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden"
             x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
            <!-- Header con tema -->
            <div class="flex items-center justify-between px-6 py-4 text-white"
                :class="{
                    'bg-emerald-600': theme === 'verde',
                    'bg-blue-600': theme === 'azul',
                    'bg-gray-900': theme === 'oscuro',
                    'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-search me-2"></i>
                    Búsqueda de Productos
                </h2>
                <button wire:click="cerrarModal('busqueda')" class="text-white transition-colors hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!-- Barra de búsqueda -->
                <div class="mb-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="text-gray-400 fas fa-search"></i>
                        </div>
                        <input type="text"
                            wire:model.live.debounce.150ms="busquedaProductosServicios"
                            class="w-full py-3 pl-10 pr-4 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                            placeholder="Buscar producto por nombre, código de barras o descripción...">
                        <div wire:loading wire:target="busquedaProductosServicios"
                             class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="w-5 h-5 text-blue-500 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Filtros en una fila -->
                <div class="mb-4">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                        <!-- Marca -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Marca</label>
                            <select wire:model.live="marcaSeleccionada"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                <option value="">Todas las marcas</option>
                                @foreach($marcas as $marca)
                                    <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Categoría -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Categoría</label>
                            <select wire:model.live="categoriaSeleccionada"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                <option value="">Todas las categorías</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Subcategoría -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Subcategoría</label>
                            <select wire:model.live="subcategoriaSeleccionada"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                <option value="">Todas las subcategorías</option>
                                @foreach($subcategorias as $subcategoria)
                                    <option value="{{ $subcategoria->id }}">{{ $subcategoria->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filtro de Stock -->
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Disponibilidad</label>
                            <select wire:model.live="filtroStock"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                <option value="todos">Todos los productos</option>
                                <option value="con_stock">Con stock disponible</option>
                                <option value="sin_stock">Sin stock</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Resultados en tarjetas (Grid) -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @forelse($resultadosBusqueda as $item)
                        @php
                            // Usar stock por unidad específica
                            $stockDisponible = $item->stock_disponible_unidad ?? 0;
                            $stockBajo = $stockDisponible > 0 && $stockDisponible <= 5;
                            $sinStock = $stockDisponible <= 0;
                            $puedeVender = ($item->puede_vender ?? false) && $stockDisponible > 0;
                        @endphp
                        <div class="relative overflow-hidden transition-all duration-200 bg-white border-2 rounded-lg shadow-sm hover:shadow-lg {{ $puedeVender ? 'cursor-pointer hover:border-blue-400' : 'cursor-not-allowed opacity-60 border-red-300' }}"
                             @if($puedeVender)
                                wire:click="agregarProductoDesdeModal({{ $item->id }}, '{{ $item->codigo_barra }}', {{ $item->unidad_medida_id }})"
                                title="Click para agregar a la factura"
                             @else
                                title="{{ $sinStock ? 'Sin stock disponible' : 'Unidad no disponible para venta' }}"
                             @endif>

                            <!-- Imagen del producto (si existe) -->
                            @if($item->tiene_imagen && $item->imagen_base64)
                                <div class="relative w-full bg-gray-100 h-36">
                                    <img src="data:image/jpeg;base64,{{ $item->imagen_base64 }}"
                                         alt="{{ $item->nombre }}"
                                         class="object-cover w-full h-full {{ $sinStock ? 'grayscale' : '' }}"
                                         loading="lazy">
                                    <!-- Badge de stock sobre la imagen -->
                                    <div class="absolute top-2 right-2 px-2 py-1 text-xs font-bold text-white rounded {{ $sinStock ? 'bg-red-500' : ($stockBajo ? 'bg-orange-500' : 'bg-green-500') }}">
                                        <i class="mr-1 fas fa-box"></i>
                                        Stock: {{ $stockDisponible }}
                                    </div>
                                </div>
                            @else
                                <!-- Placeholder si no hay imagen -->
                                <div class="relative flex items-center justify-center w-full h-36 bg-gradient-to-br from-gray-100 to-gray-200">
                                    <i class="text-5xl {{ $sinStock ? 'text-gray-300' : 'text-gray-400' }} fas fa-box-open"></i>
                                    <!-- Badge de stock sobre el placeholder -->
                                    <div class="absolute top-2 right-2 px-2 py-1 text-xs font-bold text-white rounded {{ $sinStock ? 'bg-red-500' : ($stockBajo ? 'bg-orange-500' : 'bg-green-500') }}">
                                        <i class="mr-1 fas fa-box"></i>
                                        Stock: {{ $stockDisponible }}
                                    </div>
                                </div>
                            @endif

                            <!-- Contenido de la tarjeta -->
                            <div class="p-4">
                                <!-- Nombre del producto -->
                                <h4 class="mb-2 text-base font-bold text-gray-800 line-clamp-2" style="min-height: 3rem;">
                                    {{ $item->nombre }}
                                </h4>

                                <!-- Unidad de medida (prominente) -->
                                <div class="mb-3">
                                    <div class="inline-flex items-center px-3 py-1.5 text-sm font-semibold rounded-full {{ $sinStock ? 'text-red-800 bg-red-100' : 'text-blue-800 bg-blue-100' }}">
                                        <i class="mr-1.5 fas fa-weight"></i>
                                        {{ $item->unidad_nombre }}
                                        @if($item->cantidad_por_unidad > 1)
                                            <span class="ml-1 text-xs">({{ $item->cantidad_por_unidad }} unids.)</span>
                                        @endif
                                    </div>
                                    @if($item->presentacion_descripcion)
                                        <div class="mt-1 text-xs text-gray-600">
                                            <i class="mr-1 fas fa-info-circle"></i>{{ $item->presentacion_descripcion }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Código -->
                                <div class="mb-2">
                                    <p class="text-xs font-mono text-gray-600">
                                        @if($item->codigo_barra)
                                            <i class="mr-1 fas fa-barcode"></i>{{ $item->codigo_barra }}
                                        @else
                                            <i class="mr-1 fas fa-hashtag"></i>Sin código
                                        @endif
                                    </p>
                                </div>

                                <!-- Categoría y Marca -->
                                <div class="flex flex-wrap gap-2 mb-3">
                                    @if($item->subcategoria_nombre)
                                        <span class="px-2 py-1 text-xs font-medium text-blue-700 bg-blue-50 rounded">
                                            <i class="mr-1 fas fa-tag"></i>
                                            {{ $item->subcategoria_nombre }}
                                        </span>
                                    @endif
                                    @if($item->marca_nombre)
                                        <span class="px-2 py-1 text-xs font-medium text-gray-700 bg-gray-50 rounded">
                                            <i class="mr-1 fas fa-industry"></i>
                                            {{ $item->marca_nombre }}
                                        </span>
                                    @endif
                                </div>

                                <!-- Descripción -->
                                @if($item->descripcion)
                                    <p class="mb-3 text-xs text-gray-500 line-clamp-2">{{ $item->descripcion }}</p>
                                @endif

                                <!-- Precio y botón de agregar -->
                                <div class="flex items-center justify-between pt-3 mt-3 border-t border-gray-200">
                                    <div>
                                        <div class="text-xs text-gray-500">Precio</div>
                                        <div class="text-xl font-bold {{ $sinStock ? 'text-gray-400' : 'text-green-600' }}">
                                            L. {{ number_format($item->precio, 2) }}
                                        </div>
                                    </div>
                                    @if($puedeVender)
                                        <button wire:click.stop="agregarProductoDesdeModal({{ $item->id }}, '{{ $item->codigo_barra }}', {{ $item->unidad_medida_id }})"
                                            :class="{
                                                'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                                'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                                'bg-gray-800 hover:bg-gray-900': theme === 'oscuro',
                                                'bg-slate-600 hover:bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                                            }"
                                            class="flex items-center justify-center w-10 h-10 text-white transition-colors rounded-full">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    @else
                                        <button disabled
                                            class="flex items-center justify-center w-10 h-10 text-white bg-gray-400 opacity-60 cursor-not-allowed rounded-full"
                                            title="{{ $sinStock ? 'Sin stock' : 'Unidad no disponible' }}">
                                            <i class="fas {{ $sinStock ? 'fa-times' : 'fa-ban' }}"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-3 py-8 text-center">
                            <i class="mb-3 text-gray-400 fas fa-search fa-3x"></i>
                            <p class="text-gray-500">No se encontraron productos</p>
                            <p class="text-sm text-gray-400">Intenta con otros filtros o términos de búsqueda</p>
                        </div>
                    @endforelse
                </div>

                <!-- Información adicional -->
                @if(count($resultadosBusqueda) > 0)
                    <div class="mt-4 text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Mostrando {{ count($resultadosBusqueda) }} producto(s). Haz clic para agregar a la factura.
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de Pago -->
    <!-- Modal de métodos de pago -->
    @if($mostrarModalPagoFlag)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalPago()"
         @keydown.escape.window="$wire.cerrarModalPago()">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-hidden"
             x-data="{
                totalModal: @entangle('total'),
                montosPorMetodo: @entangle('montosPorMetodo'),
                get totalDistribuido() {
                    return parseFloat(Object.values(this.montosPorMetodo || {}).reduce((sum, monto) => sum + parseFloat(monto || 0), 0).toFixed(2));
                },
                get diferencia() {
                    return parseFloat((this.totalModal - this.totalDistribuido).toFixed(2));
                },
                get puedeProceesar() {
                    return this.totalDistribuido >= this.totalModal && this.totalDistribuido > 0;
                },
                get metodosConMonto() {
                    return Object.entries(this.montosPorMetodo || {}).filter(([id, monto]) => parseFloat(monto || 0) > 0);
                }
             }"
             x-init="$watch('theme', t => localStorage.setItem('theme', t))">
            <!-- Header compacto -->
            <div class="flex items-center justify-between px-4 py-3 text-white"
                :class="{
                    'bg-emerald-600': theme === 'verde',
                    'bg-blue-600': theme === 'azul',
                    'bg-gray-900': theme === 'oscuro',
                    'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-credit-card me-2"></i>
                    Métodos de Pago
                </h2>
                <button wire:click="cerrarModalPago" class="text-white transition-colors hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Contenido con scroll -->
            <div class="overflow-y-auto max-h-[calc(90vh-120px)]">
                <div class="p-4">
                    <!-- Total compacto -->
                    <div class="p-3 mb-4 text-center rounded-lg bg-gray-50">
                        <div class="text-sm text-gray-600">Total a Pagar</div>
                        <div class="text-xl font-bold text-gray-800" x-text="'L. ' + parseFloat(totalModal).toFixed(2)">L. {{ number_format($total, 2) }}</div>
                    </div>

                    <!-- Métodos de pago compactos -->
                    <div class="mb-4 space-y-3">
                        @forelse($tiposPago as $tipoPago)
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg {{ ($montosPorMetodo[$tipoPago->id] ?? 0) > 0 ? 'bg-blue-50 border-blue-300' : '' }}">
                                <!-- Icono y nombre -->
                                <div class="flex items-center flex-1 min-w-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-2 flex-shrink-0
                                        @if($tipoPago->nombre == 'Efectivo') bg-green-100 text-green-600
                                        @elseif($tipoPago->nombre == 'Tarjeta(POS)') bg-blue-100 text-blue-600
                                        @elseif($tipoPago->nombre == 'Cheque') bg-purple-100 text-purple-600
                                        @else bg-gray-100 text-gray-600 @endif">
                                        @if($tipoPago->nombre == 'Efectivo')
                                            <i class="text-sm fas fa-money-bill-wave"></i>
                                        @elseif($tipoPago->nombre == 'Tarjeta')
                                            <i class="text-sm fas fa-credit-card"></i>
                                        @elseif($tipoPago->nombre == 'Cheque')
                                            <i class="text-sm fas fa-file-invoice-dollar"></i>
                                        @else
                                            <i class="text-sm fas fa-coins"></i>
                                        @endif
                                    </div>
                                    <span class="text-sm font-medium text-gray-800">{{ $tipoPago->nombre }}</span>
                                </div>

                                <!-- Input de monto -->
                                <div class="flex-shrink-0 w-24">
                                    <div class="relative">
                                        <span class="absolute text-xs text-gray-500 transform -translate-y-1/2 left-2 top-1/2">L.</span>
                                        <input type="number"
                                            wire:model.live="montosPorMetodo.{{ $tipoPago->id }}"
                                            step="0.01"
                                            min="0"
                                            x-bind:max="totalModal"
                                            class="w-full py-2 pl-6 pr-2 text-sm text-center border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                            placeholder="0.00">
                                    </div>
                                </div>

                                <!-- Indicador activo -->
                                @if(($montosPorMetodo[$tipoPago->id] ?? 0) > 0)
                                    <div class="flex-shrink-0">
                                        <i class="text-green-500 fas fa-check-circle"></i>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="py-4 text-center text-gray-500">
                                <i class="mb-2 fas fa-exclamation-triangle"></i>
                                <p class="text-sm">No hay métodos de pago configurados</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Resumen compacto con Alpine.js -->
                    <div x-show="totalDistribuido > 0" class="p-3 mb-4 rounded-lg"
                         x-bind:class="puedeProceesar ? 'bg-green-50 border border-green-200' : 'bg-orange-50 border border-orange-200'">
                        <div class="flex items-center justify-between mb-1 text-sm">
                            <span class="font-medium" x-bind:class="puedeProceesar ? 'text-green-700' : 'text-orange-700'">
                                Distribuido:
                            </span>
                            <span class="font-bold" x-bind:class="puedeProceesar ? 'text-green-700' : 'text-orange-700'"
                                  x-text="'L. ' + totalDistribuido.toFixed(2)">
                            </span>
                        </div>

                        <template x-if="puedeProceesar">
                            <div>
                                <template x-if="diferencia < 0">
                                    <div class="flex items-center text-xs text-green-600">
                                        <i class="mr-1 fas fa-info-circle"></i>
                                        <span x-text="'Cambio: L. ' + Math.abs(diferencia).toFixed(2)"></span>
                                    </div>
                                </template>
                                <template x-if="diferencia >= 0">
                                    <div class="flex items-center text-xs text-green-600">
                                        <i class="mr-1 fas fa-check-circle"></i>
                                        Listo para procesar
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!puedeProceesar">
                            <div class="flex items-center text-xs text-orange-600">
                                <i class="mr-1 fas fa-exclamation-triangle"></i>
                                <span x-text="'Falta: L. ' + diferencia.toFixed(2)"></span>
                            </div>
                        </template>

                        <!-- Métodos activos compactos con Alpine.js -->
                        <template x-if="metodosConMonto.length > 0">
                            <div class="flex flex-wrap gap-1 mt-2">
                                <template x-for="[tipoId, monto] in metodosConMonto" :key="tipoId">
                                    <span class="inline-block px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded"
                                          x-text="'Método ' + tipoId + ': L. ' + parseFloat(monto).toFixed(2)">
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Botones fijos en la parte inferior -->
             <div class="flex items-center justify-between gap-3 p-4 border-t border-gray-200 bg-gray-50">
               <!-- <button wire:click="cerrarModalPago"
                    class="px-4 py-2 text-gray-700 transition-colors bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancelar
                </button>-->

                <div class="flex justify-between w-full">
                    <!-- Payment method buttons on the left -->
                    <div class="flex gap-2">
                        @if(count($tiposPago) > 0)
                            <button wire:click="distribuirTotalEnEfectivo"
                                class="px-3 py-2 text-sm text-green-700 transition-colors bg-green-100 rounded-lg hover:bg-green-200">
                                <i class="mr-1 fas fa-money-bill-wave"></i>
                                Efectivo
                            </button>
                            <button wire:click="distribuirTotalEnTarjeta"
                                class="px-3 py-2 text-sm text-blue-700 transition-colors bg-blue-100 rounded-lg hover:bg-blue-200">
                                <i class="mr-1 fas fa-credit-card"></i>
                                Tarjeta
                            </button>
                        @endif
                    </div>

                    <!-- Process button on the right -->
                    <div>
                        <button wire:click="procesarDistribucionPagos"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50"
                            x-bind:disabled="!puedeProceesar"
                            x-bind:class="puedeProceesar ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed'"
                            class="px-4 py-2 text-white transition-colors rounded-lg">
                            <span wire:loading.remove>
                                <i class="mr-1 fas fa-check"></i>
                                <span x-text="puedeProceesar ? 'Procesar' : 'Incompleto'"></span>
                            </span>
                            <span wire:loading>
                                <i class="mr-1 fas fa-spinner fa-spin"></i>
                                ...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de pago en efectivo -->
    @if($mostrarModalEfectivoFlag)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalEfectivo()"
         @keydown.escape.window="$wire.cerrarModalEfectivo()">
        <div class="w-full max-w-lg overflow-hidden bg-white shadow-xl rounded-xl"
             x-data="{ efectivoRecibido: @entangle('efectivoRecibido') }"
             x-init="$watch('theme', t => localStorage.setItem('theme', t))">
            <!-- Header con tema -->
            <div class="flex items-center justify-between px-6 py-4 text-white bg-green-600">
                <h2 class="text-xl font-semibold">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Pago en Efectivo
                </h2>
                <button wire:click="cerrarModalEfectivo" class="text-white transition-colors hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <!-- Información del pago -->
                <div class="mb-6">
                    @if(count($metodosActivosParaPago) > 1)
                        <div class="p-4 mb-4 border border-blue-200 rounded-lg bg-blue-50">
                            <h3 class="mb-2 font-semibold text-blue-800">
                                <i class="mr-2 fas fa-info-circle"></i>
                                Pago Mixto Detectado
                            </h3>
                            <p class="text-sm text-blue-700">
                                Se procesará el efectivo primero, luego continuará con los otros métodos de pago.
                            </p>
                        </div>
                    @endif

                    <!-- Monto que debe recibir en efectivo -->
                    <div class="p-6 mb-6 text-center border-2 border-green-200 bg-green-50 rounded-xl">
                        <div class="mb-1 text-sm font-medium text-green-700">Monto a recibir en efectivo</div>
                        <div class="text-3xl font-bold text-green-800">L. {{ number_format($montoEfectivo ?? 0, 2) }}</div>
                        @if(count($metodosActivosParaPago) > 1)
                            <div class="mt-2 text-sm text-green-600">
                                Restante para otros métodos: L. {{ number_format($total - ($montoEfectivo ?? 0), 2) }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Input de efectivo recibido del cliente -->
                <div class="mb-6">
                    <label for="efectivo_recibido" class="block mb-3 text-lg font-semibold text-gray-800">
                        💵 ¿Cuánto efectivo entregó el cliente?
                    </label>
                    <div class="relative">
                        <span class="absolute text-xl font-bold text-gray-500 transform -translate-y-1/2 left-4 top-1/2">L.</span>
                        <input type="number"
                            id="efectivo_recibido"
                            wire:model.live="efectivoRecibido"
                            step="0.01"
                            min="0"
                            x-init="$el.value = parseFloat($el.value || 0).toFixed(2)"
                            x-on:input="$el.value = parseFloat($el.value || 0).toFixed(2)"
                            x-on:change="$el.value = parseFloat($el.value || 0).toFixed(2)"
                            class="w-full py-4 pl-12 pr-6 text-2xl font-bold text-center transition-all border-2 border-gray-300 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-200"
                            placeholder="0.00"
                            autofocus>
                    </div>
                    <div class="mt-2 text-sm text-center text-gray-600">
                        Ejemplo: Si el cliente le entrega un billete de L. 100, escriba 100.00
                    </div>
                </div>

                <!-- Cálculo de cambio -->
                @if($efectivoRecibido > 0)
                    @php
                        $montoAPagar = round($montoEfectivo ?? 0, 2);
                        $efectivoRecibidoRedondeado = round($efectivoRecibido, 2);
                        $cambio = round($efectivoRecibidoRedondeado - $montoAPagar, 2);
                        $esSuficiente = $efectivoRecibidoRedondeado >= $montoAPagar;
                    @endphp

                    <div class="mb-6 p-6 rounded-xl border-2 {{ $esSuficiente ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300' }}">
                        <h3 class="font-bold text-lg {{ $esSuficiente ? 'text-green-800' : 'text-red-800' }} mb-4">
                            📊 Resumen del Pago
                        </h3>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-700">Monto a cobrar:</span>
                                <span class="font-bold text-gray-800">L. {{ number_format($montoAPagar, 2) }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-700">Efectivo recibido:</span>
                                <span class="font-bold text-blue-600">L. {{ number_format($efectivoRecibido, 2) }}</span>
                            </div>

                            <hr class="border-gray-300">

                            @if($esSuficiente)
                                @if($cambio > 0)
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-green-700">💰 Cambio a entregar:</span>
                                        <span class="text-2xl font-bold text-green-600">L. {{ number_format($cambio, 2) }}</span>
                                    </div>
                                    <div class="mt-2 text-sm text-center text-green-600">
                                        ✅ Devuelva L. {{ number_format($cambio, 2) }} al cliente
                                    </div>
                                @else
                                    <div class="text-center">
                                        <span class="text-lg font-bold text-green-700">✅ Pago exacto</span>
                                        <div class="mt-1 text-sm text-green-600">No hay cambio que entregar</div>
                                    </div>
                                @endif
                            @else
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-red-700">❌ Falta por pagar:</span>
                                    <span class="text-2xl font-bold text-red-600">L. {{ number_format(abs($cambio), 2) }}</span>
                                </div>
                                <div class="mt-2 text-sm text-center text-red-600">
                                    El efectivo recibido es insuficiente
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Botones de acción -->
                <div class="flex justify-end gap-4">
                    <button wire:click="cerrarModalEfectivo"
                        class="px-6 py-3 font-medium text-gray-700 transition-colors bg-gray-200 rounded-xl hover:bg-gray-300">
                        <i class="mr-2 fas fa-times"></i>
                        Cancelar
                    </button>
                    <button wire:click="confirmarEfectivo"
                        class="px-8 py-3 text-white rounded-xl transition-colors font-medium text-lg {{ ($efectivoRecibido > 0 && $efectivoRecibido >= ($montoEfectivo ?? 0)) ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed' }}"
                        @if($efectivoRecibido <= 0 || $efectivoRecibido < ($montoEfectivo ?? 0)) disabled @endif>
                        <i class="mr-2 fas fa-check"></i>
                        @if(count($metodosActivosParaPago) > 1)
                            Continuar con otros pagos
                        @else
                            Finalizar Venta
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de Descuento Adulto Mayor -->
    @if($mostrarModalDescuentoAdulto)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user-friends me-2"></i>
                            Descuento Adulto Mayor
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('mostrarModalDescuentoAdulto', false)"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Por favor, proporcione los datos del cliente para aplicar el descuento:</p>

                        <div class="mb-3">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text"
                                class="form-control"
                                wire:model="nombreAdulto"
                                placeholder="Ingrese el nombre completo">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Número de Identidad *</label>
                            <input type="text"
                                class="form-control"
                                wire:model="dniAdulto"
                                placeholder="Ingrese el número de identidad">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Edad *</label>
                            <input type="number"
                                class="form-control"
                                wire:model="edadAdulto"
                                placeholder="Ingrese la edad"
                                min="60">
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Información:</strong>
                            <br>• Tercera edad (60-64 años): 25% de descuento
                            <br>• Cuarta edad (65+ años): 35% de descuento
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                            class="btn btn-secondary"
                            wire:click="$set('mostrarModalDescuentoAdulto', false)">
                            Cancelar
                        </button>
                        <button type="button"
                            class="btn btn-success"
                            wire:click="confirmarDescuentoAdulto">
                            Aplicar Descuento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Descuento General de Factura -->
    @if($mostrarModalDescuentoFactura)
        <div class="modal fade show" tabindex="-1" role="dialog" style="display: block; background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered" role="document" x-data="{ porcentaje: @entangle('descuentoFactura') }">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-percentage me-2"></i>
                            Aplicar Descuento a la Factura
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="cerrarModalDescuentoFactura"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="fw-bold">Subtotal Actual:</label>
                            <p class="mb-2 text-success fs-5">L {{ number_format($subtotal, 2) }}</p>
                        </div>
                        <div class="mb-3">
                            <label for="porcentaje_descuento_factura" class="form-label">
                                <i class="fas fa-percent me-1"></i>
                                Porcentaje de Descuento (%)
                            </label>
                            <input type="number"
                                id="porcentaje_descuento_factura"
                                class="form-control form-control-lg"
                                wire:model="descuentoFactura"
                                min="0"
                                max="100"
                                step="0.1"
                                placeholder="Ej: 10.5">
                            @error('descuentoFactura')
                                <div class="mt-1 text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        @if($descuentoFactura > 0)
                            <div class="p-3 alert alert-info">
                                <div class="d-flex justify-content-between">
                                    <span><strong>Descuento:</strong></span>
                                    <span class="text-danger fw-bold">-L {{ number_format($subtotal * ($descuentoFactura / 100), 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span><strong>Nuevo Subtotal:</strong></span>
                                    <span class="text-success fw-bold">L {{ number_format($subtotal - ($subtotal * ($descuentoFactura / 100)), 2) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        @if($descuentoFactura > 0)
                            <button type="button"
                                class="btn btn-warning"
                                wire:click="removerDescuentoFactura">
                                <i class="fas fa-times me-1"></i>
                                Remover Descuento
                            </button>
                        @endif
                        <button type="button"
                            class="btn btn-secondary"
                            wire:click="cerrarModalDescuentoFactura">
                            Cancelar
                        </button>
                        <button type="button"
                            class="btn btn-primary"
                            wire:click="aplicarDescuentoFactura"
                            x-bind:disabled="!porcentaje || porcentaje <= 0">
                            <i class="fas fa-check me-1"></i>
                            Aplicar Descuento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Descuento por Producto -->
    @if($modalDescuentoProductoVisible)
        <div class="modal fade show" tabindex="-1" role="dialog" style="display: block; background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered" role="document" x-data="{ porcentaje: @entangle('porcentajeDescuentoProducto') }">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Aplicar Descuento al Producto</h5>
                        <button type="button" class="btn-close" wire:click="$set('modalDescuentoProductoVisible', false)"></button>
                    </div>
                    <div class="modal-body">
                        @if(isset($productoSeleccionadoDescuento))
                            <div class="mb-3">
                                <label class="fw-bold">Producto:</label>
                                <p class="mb-2">{{ $productoSeleccionadoDescuento['nombre'] ?? 'N/A' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Precio Unitario:</label>
                                <p class="mb-2">L {{ number_format($productoSeleccionadoDescuento['precio'] ?? 0, 2) }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Cantidad:</label>
                                <p class="mb-2">{{ $productoSeleccionadoDescuento['cantidad'] ?? 0 }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Total Actual:</label>
                                <p class="mb-2">L {{ number_format($productoSeleccionadoDescuento['total'] ?? (($productoSeleccionadoDescuento['precio'] ?? 0) * ($productoSeleccionadoDescuento['cantidad'] ?? 0)), 2) }}</p>
                            </div>
                            <div class="mb-3">
                                <label for="porcentaje_descuento_producto" class="form-label">
                                    Porcentaje de Descuento (%)
                                </label>
                                <input type="number"
                                    id="porcentaje_descuento_producto"
                                    class="form-control"
                                    wire:model="porcentajeDescuentoProducto"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    placeholder="Ej: 10.5">
                                @error('porcentajeDescuentoProducto')
                                    <div class="mt-1 text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button"
                            class="btn btn-secondary"
                            wire:click="$set('modalDescuentoProductoVisible', false)">
                            Cancelar
                        </button>
                        <button type="button"
                            class="btn btn-warning"
                            wire:click="aplicarDescuentoProducto"
                            x-bind:disabled="!porcentaje || porcentaje <= 0">
                            Aplicar Descuento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Trámites Temporales -->
    @if($mostrarModalTramitesTemporales ?? false)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalTramitesTemporales()"
         @keydown.escape.window="$wire.cerrarModalTramitesTemporales()">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden"
             x-data="{ filtroCliente: '', filtroFecha: '' }">
            <!-- Header con tema -->
            <div class="flex items-center justify-between px-6 py-4 text-white"
                :class="{
                    'bg-emerald-600': theme === 'verde',
                    'bg-blue-600': theme === 'azul',
                    'bg-gray-900': theme === 'oscuro',
                    'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-folder-open me-2"></i>
                    Trámites Temporales de Ventas
                </h2>
                <button wire:click="cerrarModalTramitesTemporales" class="text-white transition-colors hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                @if(count($tramitesTemporales ?? []) > 0)
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            Tienes <strong>{{ count($tramitesTemporales) }}</strong> venta(s) guardada(s) temporalmente
                        </p>
                    </div>

                    <!-- Filtros -->
                    <div class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-sm fw-medium">Buscar por Cliente</label>
                                <input type="text"
                                       x-model="filtroCliente"
                                       class="form-control"
                                       placeholder="Ingrese nombre del cliente...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-sm fw-medium">Buscar por Fecha</label>
                                <input type="date"
                                       x-model="filtroFecha"
                                       class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left border-b">Cliente</th>
                                    <th class="px-4 py-3 text-left border-b">Fecha Guardado</th>
                                    <th class="px-4 py-3 text-left border-b">Productos</th>
                                    <th class="px-4 py-3 text-center border-b">Total</th>
                                    <th class="px-4 py-3 text-center border-b">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tramitesTemporales as $index => $tramite)
                                    @php
                                        $nombreCliente = '';
                                        if($tramite['modo_cliente_manual'] ?? false) {
                                            $nombreCliente = $tramite['cliente_manual']['nombre'] ?? 'Cliente Manual';
                                        } else {
                                            $nombreCliente = $tramite['cliente']['nombre'] ?? 'Consumidor Final';
                                        }
                                        $fechaGuardado = \Carbon\Carbon::parse($tramite['fecha_guardado'])->format('Y-m-d');
                                    @endphp
                                    <tr class="transition-colors hover:bg-gray-50"
                                        x-show="(!filtroCliente || '{{ $nombreCliente }}'.toLowerCase().includes(filtroCliente.toLowerCase())) &&
                                                (!filtroFecha || '{{ $fechaGuardado }}' === filtroFecha)">
                                        <td class="px-4 py-3 border-b">
                                            @if($tramite['modo_cliente_manual'] ?? false)
                                                <div>
                                                    <span class="font-medium">{{ $tramite['cliente_manual']['nombre'] ?? 'Cliente Manual' }}</span>
                                                    @if($tramite['cliente_manual']['rtn'] ?? false)
                                                        <br><small class="text-gray-600">RTN: {{ $tramite['cliente_manual']['rtn'] }}</small>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="font-medium">{{ $tramite['cliente']['nombre'] ?? 'Consumidor Final' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 border-b">
                                            <small class="text-gray-600">
                                                {{ \Carbon\Carbon::parse($tramite['fecha_guardado'])->format('d/m/Y H:i') }}
                                            </small>
                                        </td>
                                        <td class="px-4 py-3 border-b">
                                            <span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded">
                                                {{ count($tramite['productos'] ?? []) }} productos
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center border-b">
                                            <span class="font-bold text-green-600">
                                                L. {{ number_format($tramite['total'] ?? 0, 2) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center border-b">
                                            <div class="flex justify-center gap-2">
                                                <button wire:click="cargarTramiteTemporal({{ $index }})"
                                                        class="px-3 py-1 text-xs font-medium text-white transition-colors bg-blue-600 rounded hover:bg-blue-700"
                                                        title="Continuar con este trámite">
                                                    <i class="fas fa-edit me-1"></i>
                                                    Continuar
                                                </button>
                                                <button wire:click="eliminarTramiteTemporal({{ $index }})"
                                                        class="px-3 py-1 text-xs font-medium text-white transition-colors bg-red-600 rounded hover:bg-red-700"
                                                        title="Eliminar este trámite"
                                                        onclick="return confirm('¿Está seguro de eliminar este trámite temporal?')">
                                                    <i class="fas fa-trash me-1"></i>
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 text-center">
                        <i class="mb-4 text-gray-400 fas fa-folder-open fa-4x"></i>
                        <p class="text-lg text-gray-600">No hay trámites temporales guardados</p>
                        <p class="mt-2 text-sm text-gray-500">
                            Las ventas guardadas temporalmente aparecerán aquí
                        </p>
                    </div>
                @endif
            </div>

            <div class="px-6 py-4 bg-gray-50">
                <button wire:click="cerrarModalTramitesTemporales"
                        class="px-4 py-2 text-white transition-colors bg-gray-600 rounded hover:bg-gray-700">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif

</div>

