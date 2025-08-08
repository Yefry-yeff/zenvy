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
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden"
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
                <button @click="open = false" class="text-white hover:text-gray-200 transition-colors">
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200 mb-4" 
                        maxlength="20" 
                        placeholder="Ingrese número de identidad" 
                        required 
                        autofocus>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" 
                            @click="open = false"
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 text-white rounded-lg transition-colors"
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
        <div class="fixed top-5 right-5 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50">
            {{ session('cliente_no_encontrado') }}
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
            <div class="flex justify-between items-center px-6 py-4 text-white"
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
                <button wire:click="cerrarModalClientes" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!-- Buscador -->
                <div class="mb-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" 
                            wire:model.live.debounce.300ms="busquedaCliente"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
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
                                <tr class="align-middle cursor-pointer hover:bg-blue-50 transition-colors" 
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
        <div class="bg-white rounded-lg shadow-lg mb-6 border border-gray-300" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
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
                <div class="grid grid-cols-1 gap-4">
                    <!-- Primera fila: Identidad (bloqueado) y Nombre (bloqueado) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700">
                                Número de Identidad
                            </label>
                            <div class="relative">
                                <input type="text" 
                                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded text-gray-700 cursor-not-allowed pr-10"
                                    value="{{ !empty($cliente->identidad) ? $cliente->identidad : 'No especificado' }}"
                                    readonly>
                                <button type="button" 
                                    wire:click="mostrarModalClientes"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-blue-600 transition-colors">
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
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded text-gray-700 cursor-not-allowed"
                                value="{{ !empty($cliente->nombre) ? $cliente->nombre : 'No especificado' }}"
                                readonly>
                        </div>
                    </div>

                    <!-- Segunda fila: Teléfono y Correo (bloqueados) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700">
                                Teléfono
                            </label>
                            <input type="text" 
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded text-gray-700 cursor-not-allowed"
                                value="{{ !empty($cliente->telefono) ? $cliente->telefono : 'No especificado' }}"
                                readonly>
                        </div>
                        
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700">
                                Correo Electrónico
                            </label>
                            <input type="text" 
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded text-gray-700 cursor-not-allowed"
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
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded text-gray-700 cursor-not-allowed resize-none"
                            rows="2"
                            readonly>{{ !empty($cliente->direccion_completa) ? $cliente->direccion_completa : 'No especificado' }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <!-- Formulario de facturación -->
        <div class="bg-white rounded-lg shadow-lg border border-gray-300" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
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
                                <label for="codigo_barras" class="block text-sm font-medium text-gray-700 mb-1">Escanear código de barras</label>
                                <input type="text" 
                                    id="codigo_barras" 
                                    wire:model.defer="codigoBarras" 
                                    wire:keydown.enter="agregarProductoPorCodigo"
                                    class="form-control w-full" 
                                    placeholder="Escanee el código de barras"
                                    autocomplete="off"
                                    @keydown.enter="$event.target.value = ''; $event.target.focus()"
                                    autofocus>
                            </div>
                            <div class="w-32">
                                <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                                <input type="number" 
                                    id="cantidad" 
                                    wire:model.defer="cantidad" 
                                    class="form-control w-full" 
                                    min="1" 
                                    value="1">
                            </div>
                        </div>
                    </form>
                </div>

            <!-- Tabla de productos agregados -->
            <div class="table-responsive mb-4">
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
                            $subtotal = $item['precio'] * $item['cantidad'];
                            $isv = $subtotal * ($item['isv']/100);
                            $total = $subtotal + $isv;
                        @endphp
                        <tr>
                            <td>{{ $item['nombre'] }}</td>
                            <td>{{ $item['codigo'] }}</td>
                            <td>L. {{ number_format($item['precio'], 2) }}</td>
                            <td>
                                <input type="number" 
                                    wire:change="modificarCantidad({{ $loop->index }}, $event.target.value)"
                                    value="{{ $item['cantidad'] }}"
                                    min="1"
                                    class="form-control w-20 text-center"
                                    style="min-width: 60px;">
                            </td>
                            <td>L. {{ number_format($subtotal, 2) }}</td>
                            <td>L. {{ number_format($isv, 2) }}
                                <span class="text-xs text-gray-500">({{ $item['isv'] }}%)</span>
                            </td>
                            <td>L. {{ number_format($total, 2) }}</td>
                            <td class="text-center">
                                <button wire:click="eliminarProducto({{ $loop->index }})" 
                                    class="btn btn-link p-0 hover:opacity-75 transition-opacity"
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

                <!-- Totales -->
                <div class="flex justify-end mb-4">
                    <div class="bg-gray-100 rounded-lg p-4 w-full max-w-xs">
                        <div class="flex justify-between mb-2">
                            <span class="font-semibold">Subtotal:</span>
                            <span>L. {{ number_format($subtotal, 2) }}</span>
                        </div>
                        
                        <!-- ISV agrupado por tasa -->
                        @if(!empty($isvPorTasa))
                            @foreach($isvPorTasa as $tasa => $montoIsv)
                                @if($tasa > 0)
                                    <div class="flex justify-between mb-1">
                                        <span class="font-medium text-sm">ISV ({{ $tasa }}%):</span>
                                        <span class="text-sm">L. {{ number_format($montoIsv, 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            @if($totalIsv > 0)
                                <hr class="my-2 border-gray-300">
                                <div class="flex justify-between mb-2">
                                    <span class="font-semibold">Total ISV:</span>
                                    <span>L. {{ number_format($totalIsv, 2) }}</span>
                                </div>
                            @endif
                        @endif
                        
                        <hr class="my-2 border-gray-400">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total:</span>
                            <span>L. {{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Procesar Pago -->
                <div class="flex justify-end">
                    <button wire:click="mostrarModalPago" 
                        class="px-6 py-2 text-white rounded-lg transition-colors"
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                            'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                            'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                            'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }"
                        @if(count($productosFactura) == 0) disabled @endif>
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
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl overflow-hidden"
             x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
            <!-- Header con tema -->
            <div class="flex justify-between items-center px-6 py-4 text-white"
                :class="{
                    'bg-emerald-600': theme === 'verde',
                    'bg-blue-600': theme === 'azul',
                    'bg-gray-900': theme === 'oscuro',
                    'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }">
                <h2 class="text-lg font-semibold">
                    <i class="fas fa-credit-card me-2"></i>
                    Seleccionar Método de Pago
                </h2>
                <button wire:click="cerrarModalPago" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6">
                <!-- Total a pagar -->
                <div class="mb-6 p-4 bg-gray-100 rounded-lg">
                    <div class="text-center">
                        <span class="text-lg font-semibold text-gray-700">Total a Pagar:</span>
                        <div class="text-2xl font-bold text-green-600">L. {{ number_format($total, 2) }}</div>
                    </div>
                </div>

                <!-- Métodos de pago -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-800 mb-4">Distribución de pagos:</h3>
                    <p class="text-sm text-gray-600 mb-6">Ingrese el monto para cada método de pago. La suma debe ser igual o mayor al total.</p>
                    
                    <div class="space-y-6">
                        @forelse($tiposPago as $tipoPago)
                            <div class="p-5 border-2 border-gray-200 rounded-xl hover:border-gray-300 transition-colors {{ ($montosPorMetodo[$tipoPago->id] ?? 0) > 0 ? 'bg-blue-50 border-blue-300' : 'bg-white' }}">
                                <!-- Header del método de pago -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3
                                            @if($tipoPago->nombre == 'Efectivo') bg-green-100 text-green-600
                                            @elseif($tipoPago->nombre == 'Tarjeta') bg-blue-100 text-blue-600
                                            @elseif($tipoPago->nombre == 'Cheque') bg-purple-100 text-purple-600
                                            @else bg-gray-100 text-gray-600 @endif">
                                            @if($tipoPago->nombre == 'Efectivo')
                                                <i class="fas fa-money-bill-wave"></i>
                                            @elseif($tipoPago->nombre == 'Tarjeta')
                                                <i class="fas fa-credit-card"></i>
                                            @elseif($tipoPago->nombre == 'Cheque')
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            @else
                                                <i class="fas fa-coins"></i>
                                            @endif
                                        </div>
                                        <h4 class="text-xl font-semibold text-gray-800">{{ $tipoPago->nombre }}</h4>
                                    </div>
                                    
                                    @if(($montosPorMetodo[$tipoPago->id] ?? 0) > 0)
                                        <div class="flex items-center text-green-600">
                                            <i class="fas fa-check-circle text-lg mr-2"></i>
                                            <span class="font-medium">Activo</span>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Input de monto en layout horizontal -->
                                <div class="flex items-center space-x-4">
                                    <label for="monto_{{ $tipoPago->id }}" class="text-lg font-medium text-gray-700 min-w-0 flex-shrink-0">
                                        Monto:
                                    </label>
                                    <div class="flex-1 relative">
                                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-bold text-lg">L.</span>
                                        <input type="number" 
                                            id="monto_{{ $tipoPago->id }}"
                                            wire:model.live="montosPorMetodo.{{ $tipoPago->id }}"
                                            step="0.01"
                                            min="0"
                                            max="{{ $total }}"
                                            class="w-full pl-12 pr-6 py-4 border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-xl font-medium text-center transition-all"
                                            placeholder="0.00">
                                    </div>
                                </div>
                                
                                <!-- Información adicional para efectivo -->
                                @if($tipoPago->nombre == 'Efectivo' && ($montosPorMetodo[$tipoPago->id] ?? 0) > 0)
                                    @php
                                        $montoEfectivo = $montosPorMetodo[$tipoPago->id] ?? 0;
                                        $cambio = $montoEfectivo > 0 ? max(0, $montoEfectivo - $montoEfectivo) : 0; // Se calculará cuando el cliente pague
                                    @endphp
                                    <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                                        <div class="text-sm text-green-700">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <strong>Información del efectivo:</strong>
                                        </div>
                                        <div class="mt-2 text-sm text-gray-700">
                                            • Monto a recibir: <strong>L. {{ number_format($montoEfectivo, 2) }}</strong><br>
                                            • El cambio se calculará cuando el cliente entregue el dinero
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-8">
                                <i class="fas fa-exclamation-triangle fa-3x mb-4"></i>
                                <p class="text-lg">No hay métodos de pago configurados</p>
                                <p class="text-sm mt-2">Contacte al administrador del sistema</p>
                            </div>
                        @endforelse
                    </div>
                    <!-- Resumen de distribución -->
                    @php
                        $totalDistribuido = array_sum($montosPorMetodo ?? []);
                        $diferencia = $total - $totalDistribuido;
                        $metodosConMonto = array_filter($montosPorMetodo ?? [], function($monto) { return $monto > 0; });
                        $puedeProceesar = $totalDistribuido >= $total; // Cambio: ahora puede ser igual o mayor
                    @endphp
                    
                    @if($totalDistribuido > 0)
                        <div class="mt-6 p-6 rounded-xl {{ $puedeProceesar ? 'bg-green-50 border-2 border-green-300' : 'bg-orange-50 border-2 border-orange-300' }}">
                            <!-- Título del resumen -->
                            <h4 class="text-lg font-bold {{ $puedeProceesar ? 'text-green-800' : 'text-orange-800' }} mb-4">
                                <i class="fas {{ $puedeProceesar ? 'fa-check-circle' : 'fa-exclamation-triangle' }} mr-2"></i>
                                Resumen de Distribución
                            </h4>
                            
                            <!-- Detalles -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div class="text-center p-3 bg-white rounded-lg border">
                                    <div class="text-sm text-gray-600">Total a Pagar</div>
                                    <div class="text-xl font-bold text-gray-800">L. {{ number_format($total, 2) }}</div>
                                </div>
                                
                                <div class="text-center p-3 bg-white rounded-lg border">
                                    <div class="text-sm text-gray-600">Total Distribuido</div>
                                    <div class="text-xl font-bold {{ $puedeProceesar ? 'text-green-600' : 'text-orange-600' }}">
                                        L. {{ number_format($totalDistribuido, 2) }}
                                    </div>
                                </div>
                                
                                <div class="text-center p-3 bg-white rounded-lg border">
                                    <div class="text-sm text-gray-600">
                                        {{ $diferencia > 0 ? 'Falta Distribuir' : ($diferencia < 0 ? 'Cambio/Exceso' : 'Perfecto') }}
                                    </div>
                                    <div class="text-xl font-bold {{ $diferencia > 0 ? 'text-red-600' : ($diferencia < 0 ? 'text-blue-600' : 'text-green-600') }}">
                                        @if($diferencia == 0)
                                            <i class="fas fa-check"></i> Exacto
                                        @else
                                            L. {{ number_format(abs($diferencia), 2) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Estado y mensaje -->
                            @if($puedeProceesar)
                                <div class="flex items-center justify-center p-3 bg-green-100 rounded-lg">
                                    <i class="fas fa-thumbs-up text-green-600 text-lg mr-3"></i>
                                    <div>
                                        <div class="font-bold text-green-800">¡Distribución válida!</div>
                                        <div class="text-sm text-green-700">
                                            @if($diferencia < 0)
                                                Se procesará el pago y se dará cambio de L. {{ number_format(abs($diferencia), 2) }}
                                            @else
                                                Puede procesar la factura
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center justify-center p-3 bg-orange-100 rounded-lg">
                                    <i class="fas fa-exclamation-triangle text-orange-600 text-lg mr-3"></i>
                                    <div>
                                        <div class="font-bold text-orange-800">Distribución incompleta</div>
                                        <div class="text-sm text-orange-700">
                                            Faltan L. {{ number_format($diferencia, 2) }} por distribuir en los métodos de pago
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Lista de métodos activos -->
                            @if(count($metodosConMonto) > 0)
                                <div class="mt-4">
                                    <div class="text-sm font-medium text-gray-700 mb-2">Métodos de pago activos:</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($metodosConMonto as $tipoId => $monto)
                                            @php
                                                $tipoPago = collect($tiposPago)->firstWhere('id', $tipoId);
                                            @endphp
                                            @if($tipoPago)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                    @if($tipoPago->nombre == 'Efectivo') bg-green-100 text-green-800
                                                    @elseif($tipoPago->nombre == 'Tarjeta') bg-blue-100 text-blue-800
                                                    @elseif($tipoPago->nombre == 'Cheque') bg-purple-100 text-purple-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ $tipoPago->nombre }}: L. {{ number_format($monto, 2) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Botones de acción -->
                <div class="flex justify-between items-center gap-4 pt-4 border-t border-gray-200">
                    <button wire:click="cerrarModalPago" 
                        class="px-6 py-3 text-gray-700 bg-gray-200 rounded-xl hover:bg-gray-300 transition-colors font-medium">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </button>
                    
                    <div class="flex gap-3">
                        <!-- Botón de distribución rápida -->
                        @if(count($tiposPago) > 0)
                            <button wire:click="distribuirTotalEnEfectivo" 
                                class="px-5 py-3 text-green-700 bg-green-100 rounded-xl hover:bg-green-200 transition-colors font-medium">
                                <i class="fas fa-money-bill-wave mr-2"></i>
                                Todo en Efectivo
                            </button>
                        @endif
                        
                        @php
                            $totalDistribuido = array_sum($montosPorMetodo ?? []);
                            $puedeProceesar = $totalDistribuido >= $total && $totalDistribuido > 0;
                        @endphp
                        
                        <button wire:click="procesarDistribucionPagos" 
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50"
                            class="px-8 py-3 text-white rounded-xl transition-colors font-medium text-lg {{ $puedeProceesar ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed' }}"
                            @if(!$puedeProceesar) disabled @endif>
                            <span wire:loading.remove>
                                @if($puedeProceesar)
                                    <i class="fas fa-check mr-2"></i>
                                    Procesar Pago
                                @else
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Completar Distribución
                                @endif
                            </span>
                            <span wire:loading>
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Procesando...
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
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden"
             x-data="{ efectivoRecibido: @entangle('efectivoRecibido') }"
             x-init="$watch('theme', t => localStorage.setItem('theme', t))">
            <!-- Header con tema -->
            <div class="flex justify-between items-center px-6 py-4 text-white bg-green-600">
                <h2 class="text-xl font-semibold">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Pago en Efectivo
                </h2>
                <button wire:click="cerrarModalEfectivo" class="text-white hover:text-gray-200 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6">
                <!-- Información del pago -->
                <div class="mb-6">
                    @if(count($metodosActivosParaPago) > 1)
                        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <h3 class="font-semibold text-blue-800 mb-2">
                                <i class="fas fa-info-circle mr-2"></i>
                                Pago Mixto Detectado
                            </h3>
                            <p class="text-sm text-blue-700">
                                Se procesará el efectivo primero, luego continuará con los otros métodos de pago.
                            </p>
                        </div>
                    @endif
                    
                    <!-- Monto que debe recibir en efectivo -->
                    <div class="text-center p-6 bg-green-50 rounded-xl border-2 border-green-200 mb-6">
                        <div class="text-sm font-medium text-green-700 mb-1">Monto a recibir en efectivo</div>
                        <div class="text-3xl font-bold text-green-800">L. {{ number_format($montoEfectivo ?? 0, 2) }}</div>
                        @if(count($metodosActivosParaPago) > 1)
                            <div class="text-sm text-green-600 mt-2">
                                Restante para otros métodos: L. {{ number_format($total - ($montoEfectivo ?? 0), 2) }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Input de efectivo recibido del cliente -->
                <div class="mb-6">
                    <label for="efectivo_recibido" class="block text-lg font-semibold text-gray-800 mb-3">
                        💵 ¿Cuánto efectivo entregó el cliente?
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-bold text-xl">L.</span>
                        <input type="number" 
                            id="efectivo_recibido"
                            wire:model.live="efectivoRecibido"
                            step="0.01"
                            min="0"
                            class="w-full pl-12 pr-6 py-4 border-2 border-gray-300 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-200 text-2xl font-bold text-center transition-all"
                            placeholder="0.00"
                            autofocus>
                    </div>
                    <div class="text-sm text-gray-600 mt-2 text-center">
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
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-700">Monto a cobrar:</span>
                                <span class="font-bold text-gray-800">L. {{ number_format($montoAPagar, 2) }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-700">Efectivo recibido:</span>
                                <span class="font-bold text-blue-600">L. {{ number_format($efectivoRecibido, 2) }}</span>
                            </div>
                            
                            <hr class="border-gray-300">
                            
                            @if($esSuficiente)
                                @if($cambio > 0)
                                    <div class="flex justify-between items-center">
                                        <span class="font-bold text-green-700">💰 Cambio a entregar:</span>
                                        <span class="font-bold text-2xl text-green-600">L. {{ number_format($cambio, 2) }}</span>
                                    </div>
                                    <div class="text-sm text-green-600 text-center mt-2">
                                        ✅ Devuelva L. {{ number_format($cambio, 2) }} al cliente
                                    </div>
                                @else
                                    <div class="text-center">
                                        <span class="font-bold text-green-700 text-lg">✅ Pago exacto</span>
                                        <div class="text-sm text-green-600 mt-1">No hay cambio que entregar</div>
                                    </div>
                                @endif
                            @else
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-red-700">❌ Falta por pagar:</span>
                                    <span class="font-bold text-2xl text-red-600">L. {{ number_format(abs($cambio), 2) }}</span>
                                </div>
                                <div class="text-sm text-red-600 text-center mt-2">
                                    El efectivo recibido es insuficiente
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Botones de acción -->
                <div class="flex justify-end gap-4">
                    <button wire:click="cerrarModalEfectivo" 
                        class="px-6 py-3 text-gray-700 bg-gray-200 rounded-xl hover:bg-gray-300 transition-colors font-medium">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </button>
                    <button wire:click="confirmarEfectivo" 
                        class="px-8 py-3 text-white rounded-xl transition-colors font-medium text-lg {{ ($efectivoRecibido > 0 && $efectivoRecibido >= ($montoEfectivo ?? 0)) ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed' }}"
                        @if($efectivoRecibido <= 0 || $efectivoRecibido < ($montoEfectivo ?? 0)) disabled @endif>
                        <i class="fas fa-check mr-2"></i>
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
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden"
             x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
            <!-- Header con tema -->
            <div class="flex justify-between items-center px-6 py-4 text-white"
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
                <div class="mb-6 p-4 bg-blue-50 rounded-lg text-center">
                    <span class="text-sm font-medium text-gray-700">Monto a procesar:</span>
                    <div class="text-2xl font-bold text-blue-600">L. {{ number_format($montoTarjeta ?? $total, 2) }}</div>
                </div>

                <!-- Mensaje de confirmación -->
                <div class="mb-6 text-center">
                    <div class="mb-4">
                        <i class="fas fa-credit-card fa-3x text-blue-500 mb-3"></i>
                        <p class="text-lg font-medium text-gray-800">¿Se procesó correctamente el pago?</p>
                        <p class="text-sm text-gray-600 mt-2">
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
                        class="px-6 py-3 text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                        <i class="fas fa-times me-2"></i>
                        No, falló el pago
                    </button>
                    <button wire:click="confirmarPagoTarjeta(true)" 
                        class="px-6 py-3 text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                        <i class="fas fa-check me-2"></i>
                        Sí, pago exitoso
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
