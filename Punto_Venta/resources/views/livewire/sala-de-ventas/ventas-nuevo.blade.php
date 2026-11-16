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
                        <button type="button"
                            wire:click="activarModoClienteManual"
                            class="px-4 py-2 text-white transition-colors bg-orange-600 rounded-lg hover:bg-orange-700">
                            <i class="fas fa-edit me-1"></i>
                            RTN
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
                                            <span class="badge bg-primary">{{ $cliente_item->identidad }}</span><br>
                                        @endif
                                        @if($cliente_item->rtn)
                                            <span class="badge bg-secondary">{{ $cliente_item->rtn }}</span>
                                        @endif
                                        @if(!$cliente_item->identidad && !$cliente_item->rtn)
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>

                                    <td>{{ $cliente_item->correo ?? 'N/A' }}</td>

                                    <td>{{ $cliente_item->telefono ?? 'N/A' }}</td>

                                    <td class="text-start">
                                        <small>{{ $cliente_item->direccion_completa ?? 'Sin dirección' }}</small>
                                    </td>

                                    <td>
                                        <span class="badge {{ $cliente_item->estado_id == 1 ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $cliente_item->estado_id == 1 ? 'Activo' : 'Inactivo' }}
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

    <!-- CONTENIDO PRINCIPAL: Solo cuando hay cliente seleccionado o en modo manual -->
    @if(isset($cliente) || $modoClienteManual)
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
                        <div class="alert alert-info d-flex align-items-center mb-3">
                            <div class="me-3">
                                <i class="fas fa-warehouse fa-lg"></i>
                            </div>
                            <div>
                                <strong>Tienda:</strong> {{ $tiendaUsuario }}<br>
                                <strong>Bodega Principal:</strong> {{ $bodegaPrincipal->nombre }}
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning d-flex align-items-center mb-3">
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
                                    @if($modoClienteManual) RTN / Número de Identidad @else Número de Identidad @endif
                                </label>
                                <div class="relative">
                                    @if($modoClienteManual)
                                        <input type="text"
                                            wire:model="rtnManual"
                                            class="w-full px-3 py-2 pr-10 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                            placeholder="Ingrese RTN o número de identidad">
                                    @else
                                        <input type="text"
                                            wire:model="numeroIdentidad"
                                            class="w-full px-3 py-2 pr-10 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                            placeholder="Número de identidad del cliente"
                                            readonly>
                                    @endif
                                    <button type="button"
                                        wire:click="mostrarModalClientes"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-blue-600"
                                        title="Buscar cliente">
                                        <i class="w-4 h-4 fas fa-search"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Nombre del Cliente</label>
                                @if($modoClienteManual)
                                    <input type="text"
                                        wire:model="nombreClienteManual"
                                        class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                        placeholder="Ingrese nombre del cliente">
                                @else
                                    <input type="text"
                                        value="{{ $cliente->nombre ?? '' }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-50"
                                        placeholder="Nombre del cliente seleccionado"
                                        readonly>
                                @endif
                            </div>
                        </div>

                        <!-- Segunda fila: Correo y Teléfono -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                                @if($modoClienteManual)
                                    <input type="email"
                                        wire:model="correoClienteManual"
                                        class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                        placeholder="correo@ejemplo.com">
                                @else
                                    <input type="email"
                                        value="{{ $cliente->correo ?? '' }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-50"
                                        placeholder="Correo del cliente seleccionado"
                                        readonly>
                                @endif
                            </div>

                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                                @if($modoClienteManual)
                                    <input type="tel"
                                        wire:model="telefonoClienteManual"
                                        class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                        placeholder="+504 0000-0000">
                                @else
                                    <input type="tel"
                                        value="{{ $cliente->telefono ?? '' }}"
                                        class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-50"
                                        placeholder="Teléfono del cliente seleccionado"
                                        readonly>
                                @endif
                            </div>
                        </div>

                        <!-- Tercera fila: Dirección -->
                        <div class="space-y-1">
                            <label class="block text-sm font-medium text-gray-700">Dirección</label>
                            @if($modoClienteManual)
                                <textarea wire:model="direccionClienteManual"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                    rows="2"
                                    placeholder="Dirección completa del cliente"></textarea>
                            @else
                                <textarea class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-50"
                                    rows="2"
                                    placeholder="Dirección del cliente seleccionado"
                                    readonly>{{ $cliente->direccion_completa ?? '' }}</textarea>
                            @endif
                        </div>

                        <!-- Botones de control -->
                        <div class="flex flex-wrap gap-2">
                            @if($modoClienteManual)
                                <button type="button"
                                    wire:click="desactivarModoClienteManual"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-50 border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-200">
                                    <i class="w-4 h-4 mr-2 fas fa-times"></i>
                                    Desactivar Modo Manual
                                </button>
                                <button type="button"
                                    wire:click="guardarClienteManual"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-200">
                                    <i class="w-4 h-4 mr-2 fas fa-save"></i>
                                    Guardar Cliente
                                </button>
                            @else
                                @if(!$cliente)
                                    <button type="button"
                                        wire:click="activarModoClienteManual"
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-orange-700 bg-orange-50 border border-orange-300 rounded-lg hover:bg-orange-100 focus:ring-4 focus:ring-orange-200">
                                        <i class="w-4 h-4 mr-2 fas fa-edit"></i>
                                        Activar Modo Manual
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. LAYOUT DE DOS COLUMNAS: FACTURA + CATÁLOGO -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                
                <!-- 2.1 FACTURA (izquierda) -->
                <div class="bg-white border border-gray-300 rounded-lg shadow-lg" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
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

                        <!-- Lista de productos agregados a la factura -->
                        <!-- Aquí incluirías el resto del contenido de facturación existente -->
                        <!-- Solo quitamos el catálogo que estaba dentro -->
                        
                    </div>
                </div>

                <!-- 2.2 CATÁLOGO (derecha) -->
                <div class="bg-white border border-gray-300 rounded-lg shadow-lg" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
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
                            <i class="fas fa-th-large me-2"></i>
                            Catálogo Visual
                        </h3>
                    </div>

                    <!-- Content -->
                    <div class="p-4">
                        <!-- Filtros de tipo -->
                        <div class="mb-3 btn-group w-100" role="group">
                            <button type="button" 
                                wire:click="$set('tipoSeleccion', 'todos')"
                                class="btn {{ $tipoSeleccion === 'todos' ? 'btn-primary' : 'btn-outline-primary' }}">
                                <i class="fas fa-th me-1"></i>Todos
                            </button>
                            <button type="button" 
                                wire:click="$set('tipoSeleccion', 'productos')"
                                class="btn {{ $tipoSeleccion === 'productos' ? 'btn-success' : 'btn-outline-success' }}">
                                <i class="fas fa-box me-1"></i>Productos
                            </button>
                            <button type="button" 
                                wire:click="$set('tipoSeleccion', 'servicios')"
                                class="btn {{ $tipoSeleccion === 'servicios' ? 'btn-info' : 'btn-outline-info' }}">
                                <i class="fas fa-concierge-bell me-1"></i>Servicios
                            </button>
                        </div>

                        <!-- Búsqueda -->
                        <div class="mb-3">
                            <input type="text"
                                wire:model.live="busquedaProductosServicios"
                                class="form-control"
                                placeholder="Buscar productos y servicios...">
                        </div>

                        <!-- Grid de productos y servicios con scroll -->
                        <div style="height: 600px; overflow-y: auto;" class="border rounded">
                            <div class="row p-2">
                                @php
                                    $items = $this->obtenerProductosYServiciosFiltrados();
                                @endphp
                                @foreach($items as $item)
                                    <div class="mb-3 col-12 col-md-6 col-xl-4">
                                        <div class="border card h-100 position-relative" 
                                             style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
                                             wire:click="{{ $item->esServicio ? 'agregarServicio' : 'agregarProductoPorClic' }}({{ $item->id }})"
                                             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.15)';"
                                             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)';">
                                            
                                            <!-- Badge de tipo -->
                                            <span class="badge {{ $item->esServicio ? 'bg-info' : 'bg-success' }} position-absolute top-0 start-0 m-2" style="z-index: 10;">
                                                <i class="fas {{ $item->esServicio ? 'fa-concierge-bell' : 'fa-box' }} me-1"></i>
                                                {{ $item->esServicio ? 'Servicio' : 'Producto' }}
                                            </span>
                                            
                                            <!-- Imagen -->
                                            @php
                                                $imagenBase64 = $item->esServicio ? $this->getServicioImagen($item->id) : $this->getProductoImagen($item->id);
                                            @endphp
                                            @if($imagenBase64)
                                                <img src="data:image/jpeg;base64,{{ $imagenBase64 }}" 
                                                     class="card-img-top" 
                                                     style="height: 120px; object-fit: cover;"
                                                     alt="{{ $item->nombre }}">
                                            @else
                                                <div class="bg-light card-img-top d-flex align-items-center justify-content-center" 
                                                     style="height: 120px;">
                                                    <i class="text-muted fas {{ $item->esServicio ? 'fa-concierge-bell' : 'fa-box' }} fa-2x"></i>
                                                </div>
                                            @endif
                                            
                                            <div class="card-body p-2">
                                                <h6 class="card-title mb-1 fw-bold" style="font-size: 0.85rem;">{{ $item->nombre }}</h6>
                                                
                                                @if(!$item->esServicio && !empty($item->codigo_barra))
                                                    <p class="mb-1 small text-muted" style="font-size: 0.75rem;">
                                                        <i class="fas fa-barcode me-1"></i>{{ $item->codigo_barra }}
                                                    </p>
                                                @endif
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="text-success fw-bold" style="font-size: 0.85rem;">
                                                            L. {{ number_format($item->precio_base, 2) }}
                                                        </span>
                                                        @if(!$item->esServicio)
                                                            <br><small class="text-muted" style="font-size: 0.7rem;">
                                                                Stock: {{ $item->stockDisponible ?? 0 }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                    <button type="button"
                                                        class="btn {{ $item->esServicio ? 'btn-info' : 'btn-success' }} btn-sm"
                                                        onclick="event.stopPropagation();"
                                                        wire:click="{{ $item->esServicio ? 'agregarServicio' : 'agregarProductoPorClic' }}({{ $item->id }})">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                
                                @if($items->isEmpty())
                                    <div class="col-12 text-center p-4">
                                        <i class="fas fa-search fa-2x text-muted mb-3"></i>
                                        <p class="text-muted">No se encontraron {{ $tipoSeleccion === 'productos' ? 'productos' : ($tipoSeleccion === 'servicios' ? 'servicios' : 'elementos') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div> <!-- End grid de dos columnas -->

        </div> <!-- End contenedor principal -->
    @endif <!-- End if cliente -->
</div>
