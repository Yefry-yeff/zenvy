<div>
    <!-- Modal de búsqueda de cliente por identidad -->
    @if(!isset($cliente))
    <div x-data="{ open: true, identidad: '' }"
         x-show="open"
         x-cloak
         @cerrar-modal-busqueda.window="open = false"
         @click.self="open = false"
         @keydown.escape.window="open = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="w-full max-w-md overflow-hidden bg-white rounded-lg shadow-xl"
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
                    Buscar Cliente
                </h2>
                <button @click="open = false" class="text-white transition-colors hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6">
                <form @submit.prevent="$wire.buscarClientePorIdentidad(identidad)">
                    <label for="identidad" class="block mb-2 text-sm font-medium text-gray-700">Número de Identidad:</label>
                    <input type="text"
                        id="identidad"
                        x-model="identidad"
                        class="w-full px-3 py-2 mb-4 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                        maxlength="20"
                        placeholder="Ingrese número de identidad"
                        required
                        autofocus>

                    <div class="flex justify-end gap-3">
                        <button type="button"
                            @click="open = false"
                            class="px-4 py-2 text-gray-700 transition-colors bg-gray-200 rounded-lg hover:bg-gray-300">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-2 text-white transition-colors rounded-lg"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                            }">
                            <i class="fas fa-search me-1"></i>
                            Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

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
                                <th>Dirección</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clientesModal as $cliente)
                                <tr class="align-middle transition-colors cursor-pointer hover:bg-blue-50"
                                    wire:click="seleccionarClienteModal({{ $cliente->id }})"
                                    title="Clic para seleccionar este cliente">
                                    <td>{{ $cliente->id }}</td>

                                    <td class="text-start">
                                        <div>
                                            <strong>{{ $cliente->nombre }}</strong><br>
                                            <small class="text-muted">
                                                Registrado: {{ $cliente->created_at ? $cliente->created_at->format('d/m/Y') : 'N/A' }}
                                            </small>
                                        </div>
                                    </td>

                                    <td>
                                        @if($cliente->identidad)
                                            <span class="badge bg-primary">{{ $cliente->identidad }}</span><br>
                                        @endif
                                        @if($cliente->rtn)
                                            <span class="badge bg-secondary">{{ $cliente->rtn }}</span>
                                        @endif
                                        @if(!$cliente->identidad && !$cliente->rtn)
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>

                                    <td>{{ $cliente->correo ?? 'N/A' }}</td>

                                    <td>{{ $cliente->telefono ?? 'N/A' }}</td>

                                    <td class="text-start">
                                        <small>{{ $cliente->direccion_completa ?? 'Sin dirección' }}</small>
                                    </td>

                                    <td>
                                        <span class="badge {{ $cliente->estado_id == 1 ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $cliente->estado_id == 1 ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-muted">
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

    <!-- Vista de datos del cliente y facturación -->
    @if(isset($cliente))
        <!-- Información de bodega y alertas de stock -->
        @if($bodegaPrincipal)
            <div class="p-3 mb-4 border border-blue-200 rounded-lg bg-blue-50">
                <div class="flex items-center">
                    <i class="mr-2 text-blue-600 fas fa-warehouse"></i>
                    <span class="font-medium text-blue-800">
                        Bodega Principal: <strong>{{ $bodegaPrincipal->nombre }}</strong>
                    </span>
                </div>
            </div>
        @else
            <div class="p-3 mb-4 border border-yellow-200 rounded-lg bg-yellow-50">
                <div class="flex items-center">
                    <i class="mr-2 text-yellow-600 fas fa-exclamation-triangle"></i>
                    <span class="font-medium text-yellow-800">
                        No se encontró bodega principal para su tienda
                    </span>
                </div>
            </div>
        @endif

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

                <div class="grid grid-cols-1 gap-4">
                    <!-- Primera fila: Identidad (bloqueado) y Nombre (bloqueado) -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700">
                                Número de Identidad
                            </label>
                            <div class="relative">
                                <input type="text"
                                    class="w-full px-3 py-2 pr-10 text-gray-700 border border-gray-200 rounded cursor-not-allowed bg-gray-50"
                                    value="{{ !empty($cliente->identidad) ? $cliente->identidad : 'No especificado' }}"
                                    readonly>
                                <button type="button"
                                    wire:click="mostrarModalClientes"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 transition-colors hover:text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700">
                                Nombre Completo
                            </label>
                            <input type="text"
                                class="w-full px-3 py-2 text-gray-700 border border-gray-200 rounded cursor-not-allowed bg-gray-50"
                                value="{{ !empty($cliente->nombre) ? $cliente->nombre : 'No especificado' }}"
                                readonly>
                        </div>
                    </div>

                    <!-- Segunda fila: Teléfono y Correo (bloqueados) -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700">
                                Teléfono
                            </label>
                            <input type="text"
                                class="w-full px-3 py-2 text-gray-700 border border-gray-200 rounded cursor-not-allowed bg-gray-50"
                                value="{{ !empty($cliente->telefono) ? $cliente->telefono : 'No especificado' }}"
                                readonly>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700">
                                Correo Electrónico
                            </label>
                            <input type="text"
                                class="w-full px-3 py-2 text-gray-700 border border-gray-200 rounded cursor-not-allowed bg-gray-50"
                                value="{{ !empty($cliente->correo) ? $cliente->correo : 'No especificado' }}"
                                readonly>
                        </div>
                    </div>

                    <!-- Tercera fila: Dirección completa (bloqueada) -->
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700">
                            Dirección Completa
                        </label>
                        <textarea
                            class="w-full px-3 py-2 text-gray-700 border border-gray-200 rounded cursor-not-allowed resize-none bg-gray-50"
                            rows="2"
                            readonly>{{ !empty($cliente->direccion_completa) ? $cliente->direccion_completa : 'No especificado' }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <!-- Formulario de facturación -->
        <div class="bg-white border border-gray-300 rounded-lg shadow-lg" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
            <!-- Header con tema -->
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
                        <div class="flex flex-wrap gap-2 mb-4">
                            <div class="flex-1">
                                <label for="codigo_barras" class="block mb-1 text-sm font-medium text-gray-700">Escanear código de barras</label>
                                <input type="text"
                                    id="codigo_barras"
                                    wire:model.defer="codigoBarras"
                                    wire:keydown.enter="agregarProductoPorCodigo"
                                    class="w-full form-control"
                                    placeholder="Escanee el código de barras"
                                    autocomplete="off"
                                    @keydown.enter="$event.target.value = ''; $event.target.focus()"
                                    @enfocar-input-codigo.window="$event.target.focus()"
                                    autofocus>
                            </div>
                            <div class="w-32">
                                <label for="cantidad" class="block mb-1 text-sm font-medium text-gray-700">Cantidad</label>
                                <input type="number"
                                    id="cantidad"
                                    wire:model.live="cantidad"
                                    class="w-full form-control"
                                    min="1">
                            </div>
                        </div>
                    </form>
                </div>

            <!-- Tabla de productos agregados -->
            <div class="mb-4 table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th>Código</th>
                            <th>Precio Unit.</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th>ISV</th>
                            <th>Total</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productosFactura as $item)
                        @php
                            $subtotalOriginal = $item['precio'] * $item['cantidad'];
                            $descuentoAplicado = $item['descuento_aplicado'] ?? 0;
                            $subtotalConDescuento = $item['subtotal_con_descuento'] ?? $subtotalOriginal;
                            $isv = $subtotalConDescuento * ($item['isv']/100);
                            $total = $subtotalConDescuento + $isv;
                            $stockDisponible = $this->obtenerStockDisponible($item['id']);
                        @endphp
                        <tr>
                            <td>
                                {{ $item['nombre'] }}
                                @if($descuentoAplicado > 0)
                                    <br><small class="text-success">
                                        <i class="fas fa-percentage"></i>
                                        Descuento aplicado: L. {{ number_format($descuentoAplicado, 2) }}
                                    </small>
                                @endif
                                <br>
                                <small class="text-gray-500">
                                    Stock disponible: {{ $stockDisponible }}
                                </small>
                            </td>
                            <td>{{ $item['codigo'] }}</td>
                            <td>L. {{ number_format($item['precio'], 2) }}</td>
                            <td>
                                <input type="number"
                                    wire:change="modificarCantidad({{ $loop->index }}, $event.target.value)"
                                    value="{{ $item['cantidad'] }}"
                                    min="1"
                                    max="{{ $stockDisponible }}"
                                    class="w-20 text-center form-control"
                                    style="min-width: 60px;"
                                    title="Stock disponible: {{ $stockDisponible }}">
                            </td>
                            <td>
                                @if($descuentoAplicado > 0)
                                    <div class="text-decoration-line-through text-muted small">L. {{ number_format($subtotalOriginal, 2) }}</div>
                                    <div class="text-success fw-bold">L. {{ number_format($subtotalConDescuento, 2) }}</div>
                                @else
                                    L. {{ number_format($subtotalConDescuento, 2) }}
                                @endif
                            </td>
                            <td>L. {{ number_format($isv, 2) }}
                                <span class="text-xs text-gray-500">({{ $item['isv'] }}%)</span>
                            </td>
                            <td>L. {{ number_format($total, 2) }}</td>
                            <td class="text-center">
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
                            <td colspan="8" class="text-center text-muted">No hay productos agregados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

                <!-- Botones de Descuento -->
                @if(count($productosFactura) > 0)
                <div class="mb-3 d-flex justify-content-end">
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
                @endif

                <!-- Totales -->
                <div class="flex justify-end mb-4" x-data="{
                    subtotal: @entangle('subtotal'),
                    totalIsv: @entangle('totalIsv'),
                    total: @entangle('total'),
                    totalDescuentos: @entangle('totalDescuentos'),
                    isvPorTasa: @entangle('isvPorTasa')
                }">
                    <div class="w-full max-w-xs p-4 bg-gray-100 rounded-lg">
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold">Subtotal:</span>
                            <span x-text="'L. ' + parseFloat(subtotal).toFixed(2)">L. {{ number_format($subtotal, 2) }}</span>
                        </div>

                        <!-- Mostrar descuentos si hay alguno aplicado -->
                        @if($totalDescuentos > 0)
                        <div class="flex justify-between mb-2 text-red-600">
                            <span class="font-medium">Descuentos:</span>
                            <span x-text="'-L. ' + parseFloat(totalDescuentos).toFixed(2)">-L. {{ number_format($totalDescuentos, 2) }}</span>
                        </div>
                        @endif

                        <!-- ISV agrupado por tasa -->
                        @if(!empty($isvPorTasa))
                            @foreach($isvPorTasa as $tasa => $montoIsv)
                                @if($tasa > 0)
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm font-medium">ISV ({{ $tasa }}%):</span>
                                        <span class="text-sm">L. {{ number_format($montoIsv, 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            @if($totalIsv > 0)
                                <hr class="my-2 border-gray-300">
                                <div class="flex justify-between mb-2">
                                    <span class="font-semibold">Total ISV:</span>
                                    <span x-text="'L. ' + parseFloat(totalIsv).toFixed(2)">L. {{ number_format($totalIsv, 2) }}</span>
                                </div>
                            @endif
                        @endif

                        <hr class="my-2 border-gray-400">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total:</span>
                            <span x-text="'L. ' + parseFloat(total).toFixed(2)">L. {{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Procesar Pago -->
                <div class="flex justify-end">
                    @php
                        $tieneErrores = count($productosFactura) == 0;
                    @endphp
                    <button wire:click="mostrarModalPago"
                        class="px-6 py-2 text-white rounded-lg transition-colors {{ $tieneErrores ? 'opacity-50 cursor-not-allowed bg-gray-400' : '' }}"
                        @if(!$tieneErrores)
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                            'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                            'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                            'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }"
                        @endif
                        @if($tieneErrores) disabled @endif
                        title="{{ count($productosFactura) == 0 ? 'Agregue productos para procesar' : 'Procesar pago' }}">
                        <i class="fas fa-credit-card me-1"></i>
                        Procesar Pago
                    </button>
                </div>
            </div>
        </div>
    @endif

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
                    return Object.values(this.montosPorMetodo || {}).reduce((sum, monto) => sum + parseFloat(monto || 0), 0);
                },
                get diferencia() {
                    return this.totalModal - this.totalDistribuido;
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
                                        @elseif($tipoPago->nombre == 'Tarjeta') bg-blue-100 text-blue-600
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
                <button wire:click="cerrarModalPago"
                    class="px-4 py-2 text-gray-700 transition-colors bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancelar
                </button>

                <div class="flex gap-2">
                    @if(count($tiposPago) > 0)
                        <button wire:click="distribuirTotalEnEfectivo"
                            class="px-3 py-2 text-sm text-green-700 transition-colors bg-green-100 rounded-lg hover:bg-green-200">
                            <i class="mr-1 fas fa-money-bill-wave"></i>
                            Efectivo
                        </button>
                    @endif

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
                        $montoAPagar = $montoEfectivo ?? 0;
                        $cambio = $efectivoRecibido - $montoAPagar;
                        $esSuficiente = $efectivoRecibido >= $montoAPagar;
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

    <!-- Modal de confirmación de pago con tarjeta/cheque -->
    @if($mostrarModalTarjetaFlag)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalTarjeta()"
         @keydown.escape.window="$wire.cerrarModalTarjeta()">
        <div class="w-full max-w-md overflow-hidden bg-white rounded-lg shadow-xl"
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
                    <i class="fas fa-credit-card me-2"></i>
                    Confirmar Pago
                </h2>
            </div>

            <div class="p-6">
                <!-- Monto a procesar -->
                <div class="p-4 mb-6 text-center rounded-lg bg-blue-50">
                    <span class="text-sm font-medium text-gray-700">Monto a procesar:</span>
                    <div class="text-2xl font-bold text-blue-600">L. {{ number_format($montoTarjeta ?? $total, 2) }}</div>
                </div>

                <!-- Mensaje de confirmación -->
                <div class="mb-6 text-center">
                    <div class="mb-4">
                        <i class="mb-3 text-blue-500 fas fa-credit-card fa-3x"></i>
                        <p class="text-lg font-medium text-gray-800">¿Se procesó correctamente el pago?</p>
                        <p class="mt-2 text-sm text-gray-600">
                            Confirme que la transacción fue exitosa
                            @if(in_array(2, $metodosPagoSeleccionados ?? []))
                                en el terminal de pago
                            @elseif(in_array(3, $metodosPagoSeleccionados ?? []))
                                con el cheque
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Botones de confirmación -->
                <div class="flex justify-center gap-4">
                    <button wire:click="confirmarPagoTarjeta(false)"
                        class="px-6 py-3 text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700">
                        <i class="fas fa-times me-2"></i>
                        No, falló el pago
                    </button>
                    <button wire:click="confirmarPagoTarjeta(true)"
                        class="px-6 py-3 text-white transition-colors bg-green-600 rounded-lg hover:bg-green-700">
                        <i class="fas fa-check me-2"></i>
                        Sí, pago exitoso
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de descuento para adulto mayor -->
    @if($mostrarModalDescuentoAdulto)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalDescuentoAdulto()"
         @keydown.escape.window="$wire.cerrarModalDescuentoAdulto()">
        <div class="w-full max-w-md overflow-hidden bg-white rounded-lg shadow-xl"
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
                    <i class="fas fa-user-friends me-2"></i>
                    Datos del {{ $tipoDescuentoActual === 'tercera' ? 'Adulto Mayor (3ra Edad)' : 'Adulto Mayor (4ta Edad)' }}
                </h2>
                <button wire:click="cerrarModalDescuentoAdulto" class="text-white transition-colors hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6">
                <!-- Información del descuento -->
                <div class="mb-4 p-4 rounded-lg {{ $tipoDescuentoActual === 'tercera' ? 'bg-blue-50 border border-blue-200' : 'bg-purple-50 border border-purple-200' }}">
                    <div class="flex items-center mb-2">
                        <i class="fas {{ $tipoDescuentoActual === 'tercera' ? 'fa-user-friends text-blue-600' : 'fa-user-check text-purple-600' }} mr-2"></i>
                        <span class="font-semibold {{ $tipoDescuentoActual === 'tercera' ? 'text-blue-800' : 'text-purple-800' }}">
                            Descuento {{ $tipoDescuentoActual === 'tercera' ? 'del 25%' : 'del 35%' }}
                        </span>
                    </div>
                    <p class="text-sm {{ $tipoDescuentoActual === 'tercera' ? 'text-blue-700' : 'text-purple-700' }}">
                        {{ $tipoDescuentoActual === 'tercera' ? 'Para personas de 60 a 64 años' : 'Para personas de 65 años en adelante' }}
                    </p>
                </div>

                <!-- Formulario de datos -->
                <form wire:submit.prevent="confirmarDescuentoAdulto">
                    <div class="space-y-4">
                        <!-- DNI/Identidad -->
                        <div>
                            <label for="dni_adulto" class="block mb-1 text-sm font-medium text-gray-700">
                                Número de Identidad <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                id="dni_adulto"
                                wire:model.defer="dniAdulto"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                placeholder="Ej: 0801-1990-12345"
                                maxlength="60"
                                required>
                        </div>

                        <!-- Nombre completo -->
                        <div>
                            <label for="nombre_adulto" class="block mb-1 text-sm font-medium text-gray-700">
                                Nombre Completo <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                id="nombre_adulto"
                                wire:model.defer="nombreAdulto"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                placeholder="Nombre completo del adulto mayor"
                                maxlength="70"
                                required>
                        </div>

                        <!-- Edad -->
                        <div>
                            <label for="edad_adulto" class="block mb-1 text-sm font-medium text-gray-700">
                                Edad <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                id="edad_adulto"
                                wire:model.defer="edadAdulto"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                placeholder="{{ $tipoDescuentoActual === 'tercera' ? '60-64 años' : '65+ años' }}"
                                min="{{ $tipoDescuentoActual === 'tercera' ? '60' : '65' }}"
                                max="120"
                                required>
                        </div>

                        <!-- Información adicional -->
                        <div class="p-3 text-xs text-gray-600 rounded bg-gray-50">
                            <i class="mr-1 fas fa-info-circle"></i>
                            <strong>Importante:</strong> Estos datos se guardarán temporalmente y se registrarán al confirmar la venta.
                            Si remueve el descuento, deberá ingresar los datos nuevamente.
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button"
                            wire:click="cerrarModalDescuentoAdulto"
                            class="px-4 py-2 text-gray-700 transition-colors bg-gray-200 rounded-lg hover:bg-gray-300">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-6 py-2 text-white transition-colors rounded-lg"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                            }">
                            <i class="mr-1 fas fa-check"></i>
                            Aplicar Descuento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de sin stock -->
    <div x-data="{ open: false }"
         x-show="open"
         x-cloak
         @mostrar-sin-stock.window="open = true"
         @click.self="open = false"
         @keydown.escape.window="open = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="w-full max-w-md overflow-hidden bg-white rounded-lg shadow-xl"
             x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
            <!-- Header con tema -->
            <div class="flex items-center justify-between px-6 py-4 text-white"
                :class="{
                    'bg-red-600': theme === 'verde',
                    'bg-red-600': theme === 'azul',
                    'bg-red-600': theme === 'oscuro',
                    'bg-red-600': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Sin Stock Disponible
                </h2>
            </div>

            <!-- Body -->
            <div class="p-6 text-center">
                <div class="mb-4">
                    <i class="mb-3 text-4xl text-red-500 fas fa-box-open"></i>
                    <p class="text-lg text-gray-700">No hay más producto asignado en stock.</p>
                    <p class="mt-2 text-sm text-gray-500">El producto que intentas agregar no tiene stock disponible en la bodega principal.</p>
                </div>

                <button @click="open = false; $wire.dispatch('enfocar-codigo-barras')"
                    class="w-full px-4 py-2 font-medium text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700">
                    <i class="fas fa-check me-2"></i>
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>
