<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    <div class="overflow-hidden border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t"
            :class="{
                'bg-emerald-600': theme === 'verde',
                'bg-blue-600': theme === 'azul',
                'bg-gray-900': theme === 'oscuro',
                'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
            }"
        >
            <h5 class="mb-0 text-lg">
                🛒 Nueva Compra de Productos
            </h5>
            <button wire:click="resetFormulario"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>🔄</span> Limpiar
            </button>
            <button wire:click="debugClientes"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-yellow-200 rounded hover:bg-yellow-300">
                <span>🔍</span> Debug Clientes
            </button>
        </div>

        <!-- FORMULARIO -->
        <div class="px-5 py-4">
            <!-- Alerta de validación backend -->
            @if($mostrarAlerta)
                <div class="alert-campo-obligatorio">
                    <strong>⚠️ Error</strong>
                    <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
                    <br><small>{{ $mensajeAlerta }}</small>
                </div>
            @endif

            <form wire:submit.prevent="guardarCompra">

                <!-- Información de la Compra -->
                <div class="p-4 mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📋 Información de la Compra</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="numero_factura" class="form-label">Número de Factura <span class="text-red-600">*</span></label>
                                <input type="text" id="numero_factura" class="form-control" wire:model.defer="compra.numero_factura" autofocus>
                                @error('compra.numero_factura')
                                    <div class="mt-1 text-sm text-danger">❌ {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="proveedor" class="form-label">Proveedor <span class="text-red-600">*</span></label>
                                <select id="proveedor" class="form-select" wire:model.defer="proveedorSeleccionado">
                                    <option value="">Seleccionar proveedor</option>
                                    @forelse($proveedores as $proveedor)
                                        <option value="{{ $proveedor['id'] }}">
                                            {{ $proveedor['nombre'] }}
                                            @if($proveedor['rtn'])
                                                (RTN: {{ $proveedor['rtn'] }})
                                            @endif
                                            - {{ $proveedor['tipo_cliente'] }}
                                        </option>
                                    @empty
                                        <option value="" disabled>No hay proveedores disponibles</option>
                                    @endforelse
                                </select>
                                @error('proveedorSeleccionado')
                                    <div class="mt-1 text-sm text-danger">❌ {{ $message }}</div>
                                @enderror
                                @if(count($proveedores) == 0)
                                    <div class="mt-1 text-sm text-warning">
                                        ⚠️ No se encontraron proveedores. Asegúrese de tener clientes con tipo "Proveedor".
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="fecha_emision" class="form-label">Fecha de Emisión <span class="text-red-600">*</span></label>
                                <input type="date" id="fecha_emision" class="form-control" wire:model.defer="compra.fecha_emision">
                                @error('compra.fecha_emision')
                                    <div class="mt-1 text-sm text-danger">❌ {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="fecha_recepcion" class="form-label">Fecha de Recepción <span class="text-red-600">*</span></label>
                                <input type="date" id="fecha_recepcion" class="form-control" wire:model.defer="compra.fecha_recepcion">
                                @error('compra.fecha_recepcion')
                                    <div class="mt-1 text-sm text-danger">❌ {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                                <input type="date" id="fecha_vencimiento" class="form-control" wire:model.defer="compra.fecha_vencimiento">
                                @error('compra.fecha_vencimiento')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Agregar Productos -->
                <div class="p-4 mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📦 Agregar Productos</h2>
                        
                        <!-- Fila única de facturación -->
                        <div class="p-3 border rounded bg-gray-50">
                            <div class="row align-items-end">
                                <!-- Búsqueda/Selección de Producto -->
                                <div class="mb-3 col-md-3">
                                    <label for="busqueda_producto" class="form-label">
                                        <strong>Producto / Código Barras</strong> <span class="text-red-600">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" 
                                               id="busqueda_producto" 
                                               class="form-control" 
                                               wire:model.live="busquedaProducto" 
                                               placeholder="Escanear o buscar..."
                                               autofocus>
                                        @if($busquedaProducto && $productoTemporal['producto_id'])
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary position-absolute"
                                                    style="right: 5px; top: 5px; padding: 2px 6px;"
                                                    wire:click="limpiarBusqueda">
                                                ✕
                                            </button>
                                        @endif
                                    </div>
                                    
                                    <!-- Lista de productos filtrados -->
                                    @if($mostrarListaProductos && count($productosFiltrados) > 0)
                                        <div class="position-absolute w-100 bg-white border rounded shadow-lg" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                            @foreach($productosFiltrados as $producto)
                                                <div class="p-2 cursor-pointer hover:bg-gray-100" 
                                                     wire:click="seleccionarProducto({{ $producto['id'] }})">
                                                    <strong>{{ $producto['nombre'] }}</strong>
                                                    @if($producto['codigo_barra'])
                                                        <br><small class="text-muted">{{ $producto['codigo_barra'] }} - {{ $producto['marca'] }}</small>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Precio -->
                                <div class="mb-3 col-md-2">
                                    <label for="precio" class="form-label"><strong>Precio</strong> <span class="text-red-600">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">L.</span>
                                        <input type="number" 
                                               id="precio" 
                                               class="form-control" 
                                               step="0.01" 
                                               min="0.01"
                                               wire:model.defer="productoTemporal.precio"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <!-- Cantidad con botones +/- -->
                                <div class="mb-3 col-md-1">
                                    <label for="cantidad" class="form-label"><strong>Cant.</strong> <span class="text-red-600">*</span></label>
                                    <div class="input-group">
                                        <button type="button" class="btn btn-outline-secondary" wire:click="decrementarCantidad">-</button>
                                        <input type="number" 
                                               class="form-control text-center" 
                                               wire:model.defer="productoTemporal.cantidad_ingresada"
                                               min="1"
                                               readonly
                                               style="max-width: 60px;">
                                        <button type="button" class="btn btn-outline-secondary" wire:click="incrementarCantidad">+</button>
                                    </div>
                                </div>

                                <!-- Unidad -->
                                <div class="mb-3 col-md-2">
                                    <label for="unidad_compra" class="form-label"><strong>Unidad</strong> <span class="text-red-600">*</span></label>
                                    <select id="unidad_compra" class="form-select" wire:model.defer="productoTemporal.unidad_compra_id">
                                        <option value="">Seleccionar</option>
                                        @foreach($unidadesCompra as $unidad)
                                            <option value="{{ $unidad['id'] }}">{{ $unidad['nombre'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- ISV -->
                                <div class="mb-3 col-md-1">
                                    <label for="isv" class="form-label"><strong>ISV (%)</strong></label>
                                    <input type="number" 
                                           id="isv" 
                                           class="form-control" 
                                           step="0.01" 
                                           min="0" 
                                           max="100"
                                           wire:model.defer="productoTemporal.isv"
                                           placeholder="0">
                                </div>

                                <!-- Fecha de Expiración -->
                                <div class="mb-3 col-md-2">
                                    <label for="fecha_expiracion" class="form-label"><strong>Exp.</strong></label>
                                    <input type="date" 
                                           id="fecha_expiracion" 
                                           class="form-control" 
                                           wire:model.defer="productoTemporal.fecha_expiracion">
                                </div>

                                <!-- Botón Agregar -->
                                <div class="mb-3 col-md-1">
                                    <button type="button" 
                                            class="btn btn-success w-100 d-flex align-items-center justify-content-center" 
                                            wire:click="agregarProducto"
                                            @if(!$productoTemporal['producto_id'] || !$productoTemporal['precio'] || !$productoTemporal['unidad_compra_id']) disabled @endif>
                                        <span style="font-size: 18px;">➕</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Información del producto seleccionado -->
                            @if($productoTemporal['producto_id'])
                                @php
                                    $productoSeleccionado = collect($productos)->firstWhere('id', $productoTemporal['producto_id']);
                                @endphp
                                @if($productoSeleccionado)
                                    <div class="mt-2 p-2 bg-info bg-opacity-10 border border-info rounded">
                                        <small class="text-info">
                                            <strong>Producto:</strong> {{ $productoSeleccionado['nombre'] }} | 
                                            <strong>Marca:</strong> {{ $productoSeleccionado['marca'] }} | 
                                            <strong>Categoría:</strong> {{ $productoSeleccionado['subcategoria'] }}
                                        </small>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Lista de Productos Agregados -->
                @if(count($productosCompra) > 0)
                <div class="p-4 mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📝 Productos en la Compra</h2>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Código</th>
                                        <th>Precio</th>
                                        <th>Cantidad</th>
                                        <th>Unidad</th>
                                        <th>ISV %</th>
                                        <th>Subtotal</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($productosCompra as $index => $producto)
                                    <tr>
                                        <td>{{ $producto['producto_nombre'] }}</td>
                                        <td>{{ $producto['producto_codigo'] ?? 'N/A' }}</td>
                                        <td>L. {{ number_format($producto['precio'], 2) }}</td>
                                        <td>{{ $producto['cantidad_ingresada'] }}</td>
                                        <td>{{ $producto['unidad_compra_nombre'] }}</td>
                                        <td>{{ $producto['isv'] }}%</td>
                                        <td>L. {{ number_format($producto['sub_total_producto'], 2) }}</td>
                                        <td>L. {{ number_format($producto['precio_total'], 2) }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    wire:click="eliminarProducto({{ $index }})">
                                                🗑️
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-secondary">
                                    <tr>
                                        <td colspan="6" class="text-end"><strong>Subtotal:</strong></td>
                                        <td><strong>L. {{ number_format($subtotal, 2) }}</strong></td>
                                        <td colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="text-end"><strong>ISV:</strong></td>
                                        <td><strong>L. {{ number_format($totalIsv, 2) }}</strong></td>
                                        <td colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="text-end"><strong>TOTAL:</strong></td>
                                        <td><strong>L. {{ number_format($total, 2) }}</strong></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Botones -->
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" wire:click="resetFormulario"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                        🔄 Limpiar Todo
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-white rounded"
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                            'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                            'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                            'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }"
                        @if(count($productosCompra) == 0) disabled @endif>
                        💾 Guardar Compra
                    </button>
                </div>

            </form>
        </div>

    </div>

    <!-- Alerta de validación flotante -->
    @if($mostrarAlerta)
        <div class="alert-campo-obligatorio">
            <strong>⚠️ Error</strong>
            <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
            <br><small>{{ $mensajeAlerta }}</small>
        </div>
    @endif

    <!-- Estilos CSS para validación -->
    <style>
        /* Campo con error - solo rojos */
        .is-invalid, .campo-obligatorio-vacio {
            border: 2px solid #dc3545 !important;
            background-color: #fff5f5 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Mensaje de error personalizado */
        .text-danger {
            color: #dc3545 !important;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Alerta flotante personalizada */
        .alert-campo-obligatorio {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border-left: 4px solid #dc3545;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Estilo para labels de campos obligatorios */
        .text-red-600 {
            color: #dc3545 !important;
            font-weight: bold;
        }

        /* Ocultar elementos antes de que Alpine.js los maneje */
        [x-cloak] {
            display: none !important;
        }

        /* Lista de productos filtrados */
        .cursor-pointer:hover {
            background-color: #f8f9fa;
        }

        /* Tabla responsive */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        /* Botón deshabilitado */
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Estilos para la fila de facturación */
        .bg-gray-50 {
            background-color: #f8f9fa !important;
        }

        /* Botones de cantidad */
        .input-group .btn {
            border-color: #dee2e6;
        }

        .input-group .btn:hover {
            background-color: #e9ecef;
        }

        /* Campo de cantidad centrado */
        .text-center {
            text-align: center !important;
        }

        /* Información del producto seleccionado */
        .bg-info.bg-opacity-10 {
            background-color: rgba(13, 202, 240, 0.1) !important;
        }

        .border-info {
            border-color: #0dcaf0 !important;
        }

        .text-info {
            color: #0dcaf0 !important;
        }

        /* Mejorar la lista de productos filtrados */
        .position-absolute {
            position: absolute !important;
        }

        /* Botón de limpiar búsqueda */
        .position-absolute .btn {
            z-index: 5;
        }

        /* Campos obligatorios destacados */
        .form-label strong {
            font-weight: 600;
        }

        /* Input group con moneda */
        .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
        }
    </style>

    <!-- Modal de Éxito con Alpine.js -->
    <div x-data="{ open: @entangle('mostrarModalExito') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalExito()"
         @keydown.escape.window="$wire.cerrarModalExito()">

        <div class="w-full max-w-md mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="p-4 text-white bg-green-600">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">¡Compra Registrada!</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-lg font-medium text-gray-900">{{ $mensajeModalExito }}</h4>
                    <p class="text-gray-600">La compra se ha registrado correctamente en el sistema.</p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 text-center bg-gray-50">
                    <button wire:click="cerrarModalExito"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-green-600 rounded-md hover:bg-green-700">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Error con Alpine.js -->
    <div x-data="{ open: @entangle('mostrarModalError') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalError()"
         @keydown.escape.window="$wire.cerrarModalError()">

        <div class="w-full max-w-md mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="p-4 text-white bg-red-600">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">Error en la Compra</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-lg font-medium text-gray-900">{{ $mensajeModalError }}</h4>
                    <p class="text-gray-600">Por favor, revise los datos e intente nuevamente.</p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 text-center bg-gray-50">
                    <button wire:click="cerrarModalError"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-red-600 rounded-md hover:bg-red-700">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para auto-focus y manejo de eventos -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus inicial en el campo de búsqueda
            setTimeout(() => {
                const busquedaField = document.getElementById('busqueda_producto');
                if (busquedaField) {
                    busquedaField.focus();
                }
            }, 100);
        });

        document.addEventListener('livewire:init', () => {
            // Escuchar eventos de Livewire para enfocar campos
            Livewire.on('enfocar-busqueda', () => {
                setTimeout(() => {
                    const busquedaField = document.getElementById('busqueda_producto');
                    if (busquedaField) {
                        busquedaField.focus();
                        busquedaField.select();
                    }
                }, 100);
            });

            Livewire.on('enfocar-precio', () => {
                setTimeout(() => {
                    const precioField = document.getElementById('precio');
                    if (precioField) {
                        precioField.focus();
                        precioField.select();
                    }
                }, 100);
            });
        });

        // Manejar Enter en campos para navegación rápida
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                
                // Si está en búsqueda y hay producto seleccionado, ir a precio
                if (activeElement.id === 'busqueda_producto') {
                    const productoId = @this.productoTemporal.producto_id;
                    if (productoId) {
                        e.preventDefault();
                        setTimeout(() => {
                            const precioField = document.getElementById('precio');
                            if (precioField) {
                                precioField.focus();
                                precioField.select();
                            }
                        }, 100);
                    }
                }
                
                // Si está en precio, ir a cantidad (aunque sea readonly, puede activar los botones)
                else if (activeElement.id === 'precio') {
                    e.preventDefault();
                    setTimeout(() => {
                        const cantidadField = document.querySelector('input[wire\\:model\\.defer="productoTemporal.cantidad_ingresada"]');
                        if (cantidadField) {
                            cantidadField.focus();
                        }
                    }, 100);
                }
                
                // Si está en ISV, agregar producto automáticamente
                else if (activeElement.id === 'isv') {
                    e.preventDefault();
                    @this.agregarProducto();
                }
            }
            
            // Atajos de teclado
            if (e.ctrlKey && e.key === '+') {
                e.preventDefault();
                @this.incrementarCantidad();
            }
            if (e.ctrlKey && e.key === '-') {
                e.preventDefault();
                @this.decrementarCantidad();
            }
        });
    </script>

</div> {{-- FIN ELEMENTO RAÍZ --}}
