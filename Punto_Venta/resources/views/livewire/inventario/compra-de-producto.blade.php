<div x-data="{ theme: localStorage.getItem('theme') || 'verde' }">
    <style>
        [x-cloak] { display: none !important; }
        
        /* Animación suave */
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

        /* Input de cantidad grande y visible */
        .cantidad-input-grande {
            font-size: 1.25rem;
            font-weight: 600;
            text-align: center;
            height: 3rem;
        }

        /* Clase form-control base (igual que cliente-form) */
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: all 0.15s ease-in-out;
        }

        /* Focus states mejorados */
        input:focus, select:focus, .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Campo con error - solo rojos (igual que cliente-form) */
        .is-invalid, .campo-obligatorio-vacio {
            border: 2px solid #dc3545 !important;
            background-color: #fff5f5 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Mensaje de error personalizado (igual que cliente-form) */
        .text-danger {
            color: #dc3545 !important;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Alerta flotante personalizada (igual que cliente-form) */
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
    </style>



    <!-- Alertas -->
    @if(session()->has('error'))
        <div class="fixed z-50 px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded shadow-lg top-5 right-5 animate-fade-in-up" role="alert">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Alerta de validación backend (igual que cliente-form) -->
            @if($mostrarAlerta)
                <div class="alert-campo-obligatorio">
                    <strong>⚠️ Campo Obligatorio</strong>
                    <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
                    <br><small>{{ $mensajeAlerta }}</small>
                </div>
            @endif    <!-- Contenedor principal -->
    <div class="mx-auto space-y-6 max-w-7xl">
        
        <!-- HEADER -->
        <div class="overflow-hidden bg-white border border-gray-300 rounded-lg shadow-lg">
            <div class="flex items-center justify-between px-5 py-3 font-semibold text-white rounded-t"
                :class="{
                    'bg-emerald-600': theme === 'verde',
                    'bg-blue-600': theme === 'azul',
                    'bg-gray-900': theme === 'oscuro',
                    'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }">
                <h3 class="mb-0 text-lg">
                    🛒 Nueva Compra de Productos
                </h3>
                <button type="button"
                        wire:click="volver"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 transition-colors bg-white rounded hover:bg-gray-100">
                    <span>←</span> Volver
                </button>
            </div>

            <!-- INFORMACIÓN DE LA COMPRA (compacta) -->
            <div class="p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Número de Factura <span class="text-red-600">*</span>
                        </label>
                        <input type="text" 
                               wire:model.live="compra.numero_factura"
                               class="form-control {{ $this->getClaseCampo('compra.numero_factura') }}"
                               placeholder="000-000-00-00000000">
                        @error('compra.numero_factura')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Proveedor <span class="text-red-600">*</span>
                        </label>
                        <select wire:model.defer="proveedorSeleccionado"
                                class="form-control {{ $this->getClaseCampo('proveedorSeleccionado') }}">
                            <option value="">Seleccionar proveedor</option>
                            @forelse($proveedores as $proveedor)
                                <option value="{{ $proveedor['id'] }}">
                                    {{ $proveedor['nombre'] }}
                                    @if($proveedor['rtn']) ({{ $proveedor['rtn'] }}) @endif
                                </option>
                            @empty
                                <option value="" disabled>No hay proveedores</option>
                            @endforelse
                        </select>
                        @error('proveedorSeleccionado')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Fecha de Emisión <span class="text-red-600">*</span>
                        </label>
                        <input type="date" 
                               wire:model.defer="compra.fecha_emision"
                               class="form-control {{ $this->getClaseCampo('compra.fecha_emision') }}">
                        @error('compra.fecha_emision')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Fechas adicionales (colapsables) -->
                <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-2" x-data="{ mostrarMas: false }">
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Fecha de Recepción <span class="text-red-600">*</span>
                        </label>
                        <input type="date" 
                               wire:model.defer="compra.fecha_recepcion"
                               class="form-control {{ $this->getClaseCampo('compra.fecha_recepcion') }}">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">
                            Fecha de Vencimiento
                        </label>
                        <input type="date" 
                               wire:model.defer="compra.fecha_vencimiento"
                               class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- AGREGAR PRODUCTOS (tipo facturación) -->
        <div class="overflow-hidden bg-white border border-gray-300 rounded-lg shadow-lg">
            <div class="flex items-center justify-between px-5 py-3 font-semibold text-white rounded-t"
                :class="{
                    'bg-emerald-600': theme === 'verde',
                    'bg-blue-600': theme === 'azul',
                    'bg-gray-900': theme === 'oscuro',
                    'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }">
                <h3 class="mb-0 text-lg">
                    🧾 Ingreso de Productos
                </h3>
            </div>

            <div class="p-4">
                <!-- Escanear producto -->
                <div class="mb-4" wire:key="seccion-productos-main">
                    <form wire:submit.prevent="agregarProducto" wire:key="form-agregar-producto">
                        <!-- FILA 1: Código de barras + Botón Buscar (igual que ventas) -->
                        <div class="mb-4">
                            <div class="space-y-1">
                                <label for="codigo_barras_compra" class="block text-sm font-medium text-gray-700">Escanear código de barras</label>
                                <div class="flex">
                                    <div class="flex-1">
                                        <input type="text"
                                            id="codigo_barras_compra"
                                            wire:model.defer="codigoBarras"
                                            wire:key="codigo-barras-input"
                                            class="w-full h-10 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500"
                                            placeholder="Escanee el código de barras o presione Enter"
                                            autocomplete="off"
                                            x-init="
                                                $el && document.contains($el) && $nextTick(() => $el.focus());
                                                $wire.on('producto-agregado', () => $el && document.contains($el) && $nextTick(() => $el.focus()));
                                                $wire.on('producto-encontrado', () => $el && document.contains($el) && $nextTick(() => $el.focus()));
                                            "
                                            @keydown.enter.prevent="
                                                $event.target.value.trim() && $wire.agregarProductoPorCodigo();
                                                $event.target.value = '';
                                                $event.target && document.contains($event.target) && $nextTick(() => $event.target.focus());
                                            "
                                            autofocus>
                                    </div>
                                    <button type="button"
                                        wire:click="abrirModalBusqueda"
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

                        <!-- FILA 2: Cuadro de producto encontrado -->
                        <div class="mb-4" wire:key="producto-temporal-container">
                            @if($productoTemporal['producto_id'])
                                @php
                                    $productoSeleccionado = collect($productos)->firstWhere('id', $productoTemporal['producto_id']);
                                @endphp
                                @if($productoSeleccionado)
                                    <div class="p-3 border border-blue-200 rounded-lg bg-blue-50" wire:key="producto-info-{{ $productoTemporal['producto_id'] }}">
                                        <p class="text-sm font-semibold text-blue-900">
                                            📦 {{ $productoSeleccionado->nombre ?? $productoSeleccionado['nombre'] ?? 'N/A' }}
                                        </p>
                                        <p class="text-xs text-blue-700">
                                            Marca: {{ $productoSeleccionado->marca ?? $productoSeleccionado['marca'] ?? 'N/A' }} | 
                                            Categoría: {{ $productoSeleccionado->subcategoria ?? $productoSeleccionado['subcategoria'] ?? 'N/A' }}
                                        </p>
                                    </div>
                                @endif
                            @else
                                <div class="p-3 text-center border border-gray-200 rounded-lg bg-gray-50" wire:key="esperando-producto">
                                    <p class="text-sm text-gray-500">Esperando escaneo de producto...</p>
                                </div>
                            @endif
                        </div>                        <!-- FILA 3: Campos del producto (Precio, Unidad, Cantidad, Stock, ISV, F.Exp) -->
                        <div class="grid grid-cols-2 gap-3 mb-4 md:grid-cols-3 lg:grid-cols-6" wire:key="formulario-producto-{{ $productoTemporal['producto_id'] ?? 'empty' }}">
                            <!-- Precio Unit. -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Precio Unit.</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 text-sm text-gray-900 bg-gray-200 border border-r-0 border-gray-300 rounded-l-lg">L.</span>
                                    <input type="number"
                                           wire:model.live.debounce.300ms="productoTemporal.precio"
                                           step="0.01"
                                           class="flex-1 w-full h-10 px-3 text-sm border border-gray-300 rounded-r-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                           placeholder="0.00">
                                </div>
                            </div>

                            <!-- Unidad Comprada -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Unidad Comprada</label>
                                <select wire:model.live.debounce.300ms="productoTemporal.unidad_medida_id"
                                        class="w-full h-10 px-3 text-sm border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                                    <option value="">Seleccionar</option>
                                    @foreach($unidadesMedida as $unidad)
                                        <option value="{{ $unidad['id'] }}">{{ $unidad['nombre'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Cantidad Recibida (sin botones +/-) -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Cant. Recibida</label>
                                <input type="number"
                                       wire:model.live.debounce.300ms="productoTemporal.cantidad_recibida"
                                       min="1"
                                       class="w-full h-10 px-3 text-sm font-semibold text-center border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                       placeholder="1">
                            </div>

                            <!-- Cant. Unitaria x (Unidad) -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">
                                    Cant. Unitaria x 
                                    @php
                                        $unidadSeleccionada = collect($unidadesMedida)->firstWhere('id', $productoTemporal['unidad_medida_id']);
                                    @endphp
                                    ({{ $unidadSeleccionada['nombre'] ?? 'Unidad' }})
                                </label>
                                <input type="number"
                                       wire:model.live.debounce.300ms="productoTemporal.cantidad_por_unidad"
                                       min="1"
                                       class="w-full h-10 px-3 text-sm font-bold text-center border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                       placeholder="1">
                            </div>

                            <!-- ISV -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">ISV (%)</label>
                                <input type="number"
                                       wire:model.live.debounce.300ms="productoTemporal.isv"
                                       step="0.01"
                                       min="0"
                                       max="100"
                                       class="w-full h-10 px-3 text-sm text-center border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                       placeholder="0">
                            </div>

                            <!-- Fecha Expiración -->
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">F. Expiración</label>
                                <input type="date"
                                       wire:model.live.debounce.300ms="productoTemporal.fecha_expiracion"
                                       class="w-full h-10 px-3 text-sm border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                            </div>
                        </div>

                        <!-- FILA 4: Botón Agregar -->
                        <div class="flex justify-center">
                            <button type="submit"
                                    @disabled(!$this->botonHabilitado)
                                    class="px-8 py-3 text-sm font-semibold text-white transition-all rounded-lg shadow-md disabled:opacity-50 disabled:cursor-not-allowed hover:shadow-lg"
                                    :class="{
                                        'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                        'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                        'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                        'bg-slate-700 hover:bg-slate-600': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                                    }">
                                <i class="mr-2 fas fa-plus"></i>
                                Agregar Producto
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tabla de productos (se muestra directamente sin título) -->
                @if(count($productosCompra) > 0)
                <div class="table-responsive">
                    <table class="table text-center table-sm table-bordered" style="font-size: 0.7rem;">
                        <thead class="table-light">
                            <tr style="font-size: 0.65rem;">
                                <th>Producto</th>
                                <th>Código</th>
                                <th>Unidad</th>
                                <th>Precio Unit.</th>
                                <th>Cant. Recibida</th>
                                <th>Cant. Unitaria</th>
                                <th>Subtotal</th>
                                <th>ISV</th>
                                <th>Total</th>
                                <th>F. Exp.</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 0.65rem;" class="text-center">
                            @foreach($productosCompra as $index => $producto)
                                <tr wire:key="producto-compra-{{ $index }}-{{ $producto['producto_id'] ?? 'unknown' }}">
                                    <td class="text-left">
                                        <strong>{{ $producto['producto_nombre'] }}</strong>
                                    </td>
                                    <td>{{ $producto['producto_codigo'] ?? 'N/A' }}</td>
                                    <td>
                                        <span class="px-2 py-1 text-white badge bg-secondary">
                                            {{ $producto['unidad_medida_nombre'] }}
                                        </span>
                                    </td>
                                    <td class="text-end">L. {{ number_format($producto['precio'], 2) }}</td>
                                    <td>
                                        <!-- Cant. Recibida editable -->
                                        <input type="number"
                                               value="{{ $producto['cantidad_recibida'] ?? 0 }}"
                                               wire:change="actualizarCantidadRecibida({{ $index }}, $event.target.value)"
                                               min="1"
                                               class="px-2 py-1 text-center border border-gray-300 rounded"
                                               style="width: 60px; font-size: 0.75rem;">
                                    </td>
                                    <td>
                                        <strong class="text-success">{{ $producto['cantidad_ingresada'] }}</strong>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-primary">L. {{ number_format($producto['sub_total_producto'], 2) }}</strong>
                                    </td>
                                    <td>
                                        @if($producto['isv'] > 0)
                                            <span class="badge bg-info">{{ $producto['isv'] }}%</span>
                                            <br>
                                            <small class="text-muted">L. {{ number_format($producto['sub_total_producto'] * ($producto['isv'] / 100), 2) }}</small>
                                        @else
                                            <span class="text-muted">Exento</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success" style="font-size: 0.75rem;">
                                            L. {{ number_format($producto['precio_total'], 2) }}
                                        </strong>
                                    </td>
                                    <td>
                                        @if($producto['fecha_expiracion'])
                                            <small class="text-muted">{{ date('d/m/Y', strtotime($producto['fecha_expiracion'])) }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button"
                                                wire:click="eliminarProducto({{ $index }})"
                                                class="btn btn-danger btn-sm"
                                                title="Eliminar producto">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- TOTALES (alineados a la derecha como en ventas) -->
                <div class="flex justify-end mt-4 mb-4">
                    <div class="w-full max-w-md p-4 border border-gray-300 rounded-lg bg-gray-50">
                        <!-- Encabezado -->
                        <div class="mb-3 text-center">
                            <h6 class="mb-0 font-bold text-gray-700">RESUMEN DE COMPRA</h6>
                            <hr class="mt-2">
                        </div>

                        <!-- Subtotal -->
                        <div class="flex justify-between py-2 mb-2 font-medium text-gray-700">
                            <span>Subtotal:</span>
                            <span class="font-bold">L. {{ number_format($subtotal, 2) }}</span>
                        </div>
                        
                        <!-- Desglose del ISV -->
                        @php
                            $isvPorcentajes = [];
                            $totalIsvMonto = 0;
                            foreach($productosCompra as $producto) {
                                $porcentaje = $producto['isv'];
                                if ($porcentaje > 0) {
                                    if (!isset($isvPorcentajes[$porcentaje])) {
                                        $isvPorcentajes[$porcentaje] = 0;
                                    }
                                    $subtotalProducto = $producto['precio'] * $producto['cantidad_ingresada'];
                                    $isvProducto = $subtotalProducto * ($porcentaje / 100);
                                    $isvPorcentajes[$porcentaje] += $isvProducto;
                                    $totalIsvMonto += $isvProducto;
                                }
                            }
                            ksort($isvPorcentajes);
                        @endphp

                        @if($totalIsvMonto > 0)
                            <div class="pl-3 mb-2 border-l-4 border-blue-400 bg-blue-50">
                                <div class="flex justify-between mb-1">
                                    <span class="font-medium text-blue-700">
                                        <i class="mr-1 fas fa-plus-circle"></i>
                                        Total ISV:
                                    </span>
                                    <span class="font-medium text-blue-700">L. {{ number_format($totalIsvMonto, 2) }}</span>
                                </div>

                                <!-- Desglose por porcentaje -->
                                <div class="mt-1 ml-2 space-y-1">
                                    @foreach($isvPorcentajes as $porcentaje => $montoIsv)
                                        <div class="flex justify-between text-sm text-blue-600">
                                            <span class="ml-2">• ISV {{ $porcentaje }}%:</span>
                                            <span class="font-medium">L. {{ number_format($montoIsv, 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Total final -->
                        <div class="flex justify-between pt-3 mt-2 border-t-2 border-gray-300">
                            <span class="text-lg font-bold text-green-700">TOTAL A PAGAR:</span>
                            <span class="text-2xl font-bold text-green-700">L. {{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                </div>
                @endif

        <!-- BOTONES DE ACCIÓN (en una sola fila) -->
        <div class="flex flex-wrap gap-3 mb-4 md:justify-end">
            <button type="button"
                    wire:click="resetFormulario"
                    class="px-6 py-3 text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="mr-2 fas fa-redo"></i>
                Limpiar
            </button>

            <button type="button"
                    wire:click="guardarTramiteTemporal"
                    @disabled(!$this->botonGuardarHabilitado)
                    class="px-6 py-3 text-white transition-colors bg-yellow-500 rounded-lg hover:bg-yellow-600 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="mr-2 fas fa-save"></i>
                Guardar Temporal
            </button>

            <button type="button"
                    wire:click="mostrarConfirmacionProcesar"
                    @disabled(!$this->botonGuardarHabilitado)
                    class="px-6 py-3 font-semibold text-white transition-colors rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="{
                        'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                        'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                        'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                        'bg-slate-700 hover:bg-slate-600': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                    }">
                <i class="mr-2 fas fa-check"></i>
                Procesar Compra
            </button>
        </div>
    </div>

    <!-- MODAL DE BÚSQUEDA DE PRODUCTOS -->
    @if($mostrarModalBusqueda)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalBusqueda()"
         @keydown.escape.window="$wire.cerrarModalBusqueda()">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden">
            <!-- Header del Modal -->
            <div class="flex items-center justify-between px-6 py-4 text-white"
                :class="{
                    'bg-emerald-600': theme === 'verde',
                    'bg-blue-600': theme === 'azul',
                    'bg-gray-900': theme === 'oscuro',
                    'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }">
                <h2 class="text-lg font-semibold">
                    <i class="mr-2 fas fa-search"></i>
                    Buscar Producto
                </h2>
                <button wire:click="cerrarModalBusqueda" class="text-white transition-colors hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!-- Filtros de búsqueda -->
                <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-4">
                    <!-- Búsqueda por texto -->
                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Buscar por nombre o código</label>
                        <input type="text"
                               wire:model.live.debounce.500ms="busquedaModalProductos"
                               class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                               placeholder="Buscar producto...">
                    </div>

                    <!-- Filtro por Marca -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Marca</label>
                        <select wire:model.live="marcaSeleccionadaModal"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                            <option value="">Todas</option>
                            @foreach($marcasDisponibles as $marca)
                                <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filtro por Categoría -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Categoría</label>
                        <select wire:model.live="categoriaSeleccionadaModal"
                                class="w-full px-3 py-2 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-200">
                            <option value="">Todas</option>
                            @foreach($categoriasDisponibles as $categoria)
                                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Resultados de búsqueda -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left">Código</th>
                                <th class="px-4 py-3 text-left">Producto</th>
                                <th class="px-4 py-3 text-left">Marca</th>
                                <th class="px-4 py-3 text-left">Categoría</th>
                                <th class="px-4 py-3 text-right">Precio Base</th>
                                <th class="px-4 py-3 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($resultadosBusquedaModal as $resultado)
                                <tr wire:key="resultado-busqueda-{{ $resultado['id'] }}" 
                                    class="transition-colors cursor-pointer hover:bg-gray-50" 
                                    @dblclick="$wire.seleccionarProductoDesdeModal({{ $resultado['id'] }})"
                                    title="Doble clic para seleccionar">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $resultado['codigo_barra'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ $resultado['nombre'] }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $resultado['marca'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $resultado['categoria'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 font-semibold text-right">L. {{ number_format($resultado['precio_base'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button"
                                                wire:click="seleccionarProductoDesdeModal({{ $resultado['id'] }})"
                                                class="px-3 py-1 text-sm text-white transition-colors rounded"
                                                :class="{
                                                    'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                                    'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                                    'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                                    'bg-slate-700 hover:bg-slate-600': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                                                }">
                                            <i class="fas fa-check"></i> Seleccionar
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        @if(strlen($busquedaModalProductos ?? '') > 0 && strlen($busquedaModalProductos) < 3)
                                            Escribe al menos 3 caracteres para buscar
                                        @else
                                            No se encontraron productos
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(count($resultadosBusquedaModal) >= 30)
                    <p class="mt-3 text-sm text-center text-gray-600">
                        Mostrando los primeros 30 resultados. Refina tu búsqueda para ver más productos específicos.
                    </p>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL DE CONFIRMACIÓN PARA PROCESAR COMPRA -->
    @if($mostrarModalConfirmacion)
        <div class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm" 
             style="background: rgba(0, 0, 0, 0.4);"
             x-data="{ show: @entangle('mostrarModalConfirmacion') }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            
            <div class="relative w-full max-w-lg p-8 mx-4 bg-white rounded-2xl shadow-2xl border border-gray-100"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-90 translate-y-8"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                
                <!-- Encabezado del modal con gradiente -->
                <div class="text-center mb-6">
                    <div class="relative inline-flex items-center justify-center w-20 h-20 mx-auto mb-4">
                        <div class="absolute inset-0 bg-gradient-to-r from-amber-400 via-orange-500 to-amber-600 rounded-full shadow-lg animate-pulse"></div>
                        <div class="relative flex items-center justify-center w-16 h-16 bg-white rounded-full shadow-inner">
                            <i class="text-3xl text-amber-600 fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">
                        ¿Procesar Compra?
                    </h3>
                    <div class="w-16 h-1 bg-gradient-to-r from-amber-500 to-orange-600 mx-auto rounded-full"></div>
                </div>
                
                <!-- Información de la compra con diseño mejorado -->
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-6 mb-6 border border-amber-100">
                    <div class="text-center space-y-3">
                        <div class="flex items-center justify-center space-x-2">
                            <i class="text-amber-600 fas fa-file-invoice-dollar"></i>
                            <span class="text-sm font-medium text-gray-600">Factura N°</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-800">{{ $compra['numero_factura'] }}</div>
                        
                        <div class="border-t border-amber-200 pt-3">
                            <div class="flex items-center justify-center space-x-2 mb-1">
                                <i class="text-orange-600 fas fa-cash-register"></i>
                                <span class="text-sm font-medium text-gray-600">Total a Procesar</span>
                            </div>
                            <div class="text-3xl font-bold text-orange-600">
                                L. {{ number_format($total, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones con diseño profesional -->
                <div class="flex space-x-4">
                    <button type="button"
                            wire:click="cancelarProcesamiento"
                            class="flex-1 px-6 py-3 text-sm font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <i class="mr-2 fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="button"
                            wire:click="confirmarProcesamiento"
                            class="flex-1 px-6 py-3 text-sm font-semibold text-white bg-orange-500 hover:bg-orange-600 rounded-xl transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-xl">
                        <i class="mr-2 fas fa-shopping-cart"></i>
                        Sí, Procesar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL DE COMPRA EXITOSA -->
    @if($mostrarModalCompraExitosa)
        <div class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm" 
             style="background: rgba(0, 0, 0, 0.4);"
             x-data="{ show: @entangle('mostrarModalCompraExitosa') }"
             x-show="show"
             x-transition:enter="transition ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            
            <div class="relative w-full max-w-lg p-8 mx-4 bg-white rounded-2xl shadow-2xl border border-gray-100"
                 x-transition:enter="transition ease-out duration-500 transform"
                 x-transition:enter-start="opacity-0 scale-75 translate-y-16"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                
                <!-- Celebración visual -->
                <div class="absolute -top-4 -right-4 w-8 h-8 bg-gradient-to-r from-yellow-400 to-amber-500 rounded-full animate-bounce delay-100 shadow-lg">
                    <i class="text-white text-xs fas fa-star absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></i>
                </div>
                <div class="absolute -top-2 -left-4 w-6 h-6 bg-gradient-to-r from-emerald-400 to-green-500 rounded-full animate-bounce delay-300 shadow-lg">
                    <i class="text-white text-xs fas fa-check absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2"></i>
                </div>
                <div class="absolute top-8 -right-2 w-4 h-4 bg-gradient-to-r from-blue-400 to-cyan-500 rounded-full animate-bounce delay-500 shadow-lg"></div>
                
                <!-- Encabezado del modal con animación de éxito -->
                <div class="text-center mb-6">
                    <div class="relative inline-flex items-center justify-center w-24 h-24 mx-auto mb-4">
                        <div class="absolute inset-0 bg-gradient-to-r from-green-400 via-emerald-500 to-teal-500 rounded-full shadow-lg animate-pulse"></div>
                        <div class="relative flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-inner">
                            <i class="text-4xl text-green-500 fas fa-check-circle animate-bounce"></i>
                        </div>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">
                        ¡Compra Procesada Exitosamente!
                    </h3>
                    <div class="w-20 h-1 bg-gradient-to-r from-green-400 to-emerald-500 mx-auto rounded-full"></div>
                </div>
                
                <!-- Información de confirmación con diseño mejorado -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 mb-6 border border-green-100">
                    <div class="text-center space-y-4">
                        <!-- Número de factura -->
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-green-200">
                            <div class="flex items-center justify-center space-x-2 mb-2">
                                <i class="text-green-600 fas fa-receipt"></i>
                                <span class="text-sm font-medium text-gray-600">Factura Procesada</span>
                            </div>
                            <div class="text-xl font-bold text-gray-800">{{ $numeroFacturaProcesada }}</div>
                        </div>
                        
                        <!-- Total procesado -->
                        <div class="bg-white rounded-lg p-4 shadow-sm border border-green-200">
                            <div class="flex items-center justify-center space-x-2 mb-2">
                                <i class="text-emerald-600 fas fa-coins"></i>
                                <span class="text-sm font-medium text-gray-600">Total Procesado</span>
                            </div>
                            <div class="text-2xl font-bold text-emerald-600">
                                L. {{ number_format($totalCompraProcesada, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción con diseño profesional -->
                <div class="space-y-3">
                    <button type="button"
                            wire:click="recibirProductos"
                            class="w-full px-6 py-4 text-sm font-semibold text-white bg-green-500 hover:bg-green-600 rounded-xl transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-lg hover:shadow-xl">
                        <div class="flex items-center justify-center space-x-3">
                            <i class="text-lg fas fa-box-open"></i>
                            <span>Recibir Productos</span>
                            <i class="text-xs fas fa-chevron-right"></i>
                        </div>
                    </button>
                    
                    <button type="button"
                            wire:click="nuevaCompra"
                            class="w-full px-6 py-4 text-sm font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <div class="flex items-center justify-center space-x-3">
                            <i class="text-lg fas fa-file-plus"></i>
                            <span>Ingresar Nueva Compra</span>
                            <i class="text-xs fas fa-plus"></i>
                        </div>
                    </button>
                </div>
                
                <!-- Mensaje adicional -->
                <div class="mt-4 text-center">
                    <p class="text-xs text-gray-500">
                        <i class="mr-1 fas fa-info-circle"></i>
                        La compra ha sido registrada exitosamente en el sistema
                    </p>
                </div>
            </div>
        </div>
    @endif

</div>

<!-- Script de limpieza simplificado -->
<script>
document.addEventListener('livewire:initialized', () => {
    Livewire.on('cleanup-component', () => {
        // Limpiar timeouts activos de forma segura
        try {
            for (let i = 1; i < 1000; i++) {
                clearTimeout(i);
            }
        } catch (e) {
            // Ignorar errores de limpieza
        }
    });
});
</script>
