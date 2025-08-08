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
                            <th></th>
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
                            <td>
                                <button wire:click="eliminarProducto({{ $loop->index }})" 
                                    class="text-red-600 hover:text-red-800 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No hay productos agregados</td>
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
                                <div class="flex justify-between mb-1">
                                    <span class="font-medium text-sm">ISV ({{ $tasa }}%):</span>
                                    <span class="text-sm">L. {{ number_format($montoIsv, 2) }}</span>
                                </div>
                            @endforeach
                            <hr class="my-2 border-gray-300">
                            <div class="flex justify-between mb-2">
                                <span class="font-semibold">Total ISV:</span>
                                <span>L. {{ number_format($totalIsv, 2) }}</span>
                            </div>
                        @else
                            <div class="flex justify-between mb-2">
                                <span class="font-semibold">ISV (0%):</span>
                                <span>L. 0.00</span>
                            </div>
                        @endif
                        
                        <hr class="my-2 border-gray-400">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total:</span>
                            <span>L. {{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Guardar factura -->
                <div class="flex justify-end">
                    <button wire:click="guardarFactura" 
                        class="px-6 py-2 text-white rounded-lg transition-colors"
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                            'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                            'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                            'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }"
                        @if(count($productosFactura) == 0) disabled @endif>
                        <i class="fas fa-save me-1"></i>
                        Guardar Factura
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
