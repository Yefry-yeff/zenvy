<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    <!-- MENSAJES DE SESIÓN -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>✅ Éxito:</strong> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>⚠️ Advertencia:</strong> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>❌ Error:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Sección de Productos de Zenvy --}}
    <div class="overflow-hidden border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO PRODUCTOS ZENVY -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t"
            :class="{
                'bg-emerald-600': theme === 'verde',
                'bg-blue-600': theme === 'azul',
                'bg-gray-900': theme === 'oscuro',
                'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
            }"
        >
            <h5 class="mb-0 text-lg">🏠 Productos de Paperland</h5>
            <button wire:click="abrirModalCrear"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Agregar Producto
            </button>
        </div>

        <!-- TABLA PRODUCTOS ZENVY -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="productosZenvyTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Subcategoría</th>
                            <th>Marca</th>
                            <th style="width: 150px;">Fecha Creación</th>
                            <th style="width: 60px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productosZenvy as $producto)
                            <tr class="text-center align-middle hover:bg-gray-50">
                                <td class="cursor-pointer text-start" wire:click="editar({{ $producto->id }})">{{ $producto->nombre }}</td>
                                <td class="cursor-pointer text-start" wire:click="editar({{ $producto->id }})">{{ $producto->descripcion ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $producto->id }})">{{ $producto->subcategoria->categoria->nombre ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $producto->id }})">{{ $producto->subcategoria->nombre ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $producto->id }})">{{ $producto->marca->nombre ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $producto->id }})">{{ (isset($producto->created_at) && $producto->created_at) ? $producto->created_at->format('d/m/Y') : 'N/A' }}</td>
                                <td>
                                    <button type="button" class="p-0 btn btn-link" wire:click="confirmarEliminar({{ $producto->id }})" title="Eliminar" onclick="event.stopPropagation();">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7v12a2 2 0 002 2h8a2 2 0 002-2V7M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m-7 0h10" style="color:#e3342f;" />
                                            <line x1="10" y1="11" x2="10" y2="17" stroke="#e3342f" stroke-width="2"/>
                                            <line x1="14" y1="11" x2="14" y2="17" stroke="#e3342f" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-muted">No hay productos propios de Zenvy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Sección de Productos de Valencia --}}
    <div class="overflow-hidden border border-orange-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO PRODUCTOS VALENCIA -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white bg-orange-600 rounded-t">
            <h5 class="mb-0 text-lg">🏢 Productos de Valencia</h5>
            <button wire:click="sincronizarProductosValencia"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100 disabled:opacity-50"
                wire:loading.attr="disabled"
                wire:target="sincronizarProductosValencia">
                <span wire:loading.remove wire:target="sincronizarProductosValencia">🔄</span>
                <span wire:loading wire:target="sincronizarProductosValencia">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
                <span wire:loading.remove wire:target="sincronizarProductosValencia">Sincronizar</span>
                <span wire:loading wire:target="sincronizarProductosValencia">Sincronizando...</span>
            </button>
        </div>

        <!-- BARRA DE PROGRESO -->
        @if($sincronizandoValencia)
        <div class="px-5 py-3 bg-orange-50">
            <div class="mb-2">
                <div class="flex justify-between text-sm">
                    <span class="font-medium text-orange-700">Sincronizando productos de Valencia...</span>
                    <span class="text-orange-600">{{ $progreso }}%</span>
                </div>
            </div>
            <div class="w-full bg-orange-200 rounded-full h-2">
                <div class="bg-orange-600 h-2 rounded-full transition-all duration-500 ease-out"
                     style="width: {{ $progreso }}%"></div>
            </div>
        </div>
        @endif

        <!-- TABLA PRODUCTOS VALENCIA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="productosValenciaTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Subcategoría</th>
                            <th>Marca</th>
                            <th style="width: 150px;">Fecha Creación</th>
                            <th style="width: 100px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productosValencia as $producto)
                            <tr class="text-center align-middle bg-orange-50 cursor-pointer" wire:click="editar({{ $producto->id }})">
                                <td class="text-start">{{ $producto->nombre ?? 'SIN NOMBRE' }}</td>
                                <td class="text-start">{{ $producto->descripcion ?? 'N/A' }}</td>
                                <td>{{ $producto->subcategoria->categoria->nombre ?? 'N/A' }}</td>
                                <td>{{ $producto->subcategoria->nombre ?? 'N/A' }}</td>
                                <td>{{ $producto->marca->nombre ?? 'N/A' }}</td>
                                <td>{{ (isset($producto->created_at) && $producto->created_at) ? $producto->created_at->format('d/m/Y') : 'N/A' }}</td>
                                <td>
                                    <span class="px-2 py-1 text-xs text-orange-800 bg-orange-100 rounded-full font-medium">
                                        🏢 Valencia
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-muted">No hay productos de Valencia sincronizados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Confirmar Eliminación -->
    <div wire:key="modal-confirmar-eliminar">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($modalEliminarAbierto) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cerrarModalEliminar()"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="text-white modal-header bg-danger">
                        <h5 class="modal-title">⚠️ ¿Eliminar producto?</h5>
                    </div>
                    <div class="modal-body">
                        @if($productoSeleccionado)
                            <!-- Información del producto -->
                            <div class="mb-3 alert alert-info">
                                <h6><strong>📦 Información del Producto</strong></h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small><strong>Nombre:</strong></small><br>
                                        <span>{{ $productoSeleccionado->nombre ?? '' }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <small><strong>Código de Barras:</strong></small><br>
                                        <span>{{ $productoSeleccionado->codigo_barra ?? 'Sin código' }}</span>
                                    </div>
                                    <div class="mt-2 col-md-4">
                                        <small><strong>Marca:</strong></small><br>
                                        <span>{{ $productoSeleccionado->marca ?? 'N/A' }}</span>
                                    </div>
                                    <div class="mt-2 col-md-4">
                                        <small><strong>Categoría:</strong></small><br>
                                        <span>{{ $productoSeleccionado->categoria ?? 'N/A' }}</span>
                                    </div>
                                    <div class="mt-2 col-md-4">
                                        <small><strong>Subcategoría:</strong></small><br>
                                        <span>{{ $productoSeleccionado->subcategoria ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            @if(!$puedeEliminar)
                                <div class="alert alert-warning">
                                    <h6><strong>⚠️ No se puede eliminar este producto</strong></h6>
                                    <p>El producto no cumple con los requisitos para ser eliminado:</p>
                                    <ul class="mb-2">
                                        @if($tieneCodigoBarras)
                                            <li><strong>Código de barras asignado:</strong> El producto tiene el código "{{ $productoSeleccionado->codigo_barra ?? '' }}" asignado.</li>
                                        @endif
                                        @if($stockDisponible > 0)
                                            <li><strong>Stock disponible:</strong> El producto tiene {{ $stockDisponible }} unidades disponibles en stock.</li>
                                        @endif
                                        @if($tieneComprasActivas)
                                            <li><strong>Compras pendientes:</strong> El producto tiene compras activas o pendientes con cantidad sin asignar.</li>
                                        @endif
                                    </ul>
                                </div>

                                <div class="alert alert-info">
                                    <small>
                                        <strong>💡 Para poder eliminar este producto debe:</strong><br>
                                        @if($tieneCodigoBarras)
                                            • Ir al módulo de <strong>Editar Producto</strong> y eliminar el código de barras<br>
                                        @endif
                                        @if($stockDisponible > 0)
                                            • Agotar el stock disponible mediante ventas o ajustes de inventario<br>
                                        @endif
                                        @if($tieneComprasActivas)
                                            • Recibir o anular todas las compras pendientes del producto<br>
                                        @endif
                                        •No debe tener Codigo de Barras asignado
                                    </small>
                                </div>

                                <div class="flex justify-end gap-2 mt-4">
                                    <button type="button" class="btn btn-secondary" wire:click="cerrarModalEliminar">
                                        <i class="fas fa-times me-1"></i> Cerrar
                                    </button>
                                </div>
                            @else
                                <div class="alert alert-success">
                                    <h6><strong>✅ Este producto se puede eliminar</strong></h6>
                                    <p>El producto cumple con todos los requisitos:</p>
                                    <ul class="mb-2">
                                        <li>✅ No tiene código de barras asignado</li>
                                        <li>✅ No tiene stock disponible</li>
                                        <li>✅ No tiene compras activas o pendientes</li>
                                    </ul>
                                </div>

                                <p><strong>¿Estás seguro que deseas eliminar este producto?</strong></p>
                                <p class="text-muted">Esta acción cambiará el estado del producto a inactivo y no se puede deshacer.</p>

                                <div class="flex justify-end gap-2 mt-4">
                                    <button type="button" class="btn btn-secondary" wire:click="cerrarModalEliminar">
                                        <i class="fas fa-times me-1"></i> No, cancelar
                                    </button>
                                    <button type="button" class="btn btn-danger" wire:click="eliminarProducto">
                                        <i class="fas fa-trash me-1"></i> Sí, eliminar
                                    </button>
                                </div>
                            @endif
                        @else
                            <p>Cargando información del producto...</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('mensaje'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="mt-3 mb-0 transition-opacity duration-300 alert alert-success">
            {{ session('mensaje') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="mt-3 mb-0 transition-opacity duration-300 alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <!-- Modal Detalles de Sincronización -->
    @if($detallesSincronizacion)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            {{-- Overlay --}}
            <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="cerrarDetallesSincronizacion"></div>
            
            {{-- Modal --}}
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">📊 Sincronización de Productos Completada</h3>
                    <button wire:click="cerrarDetallesSincronizacion" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded">
                        <span class="font-medium text-green-800">✅ Productos procesados:</span>
                        <span class="font-bold text-green-600">{{ $detallesSincronizacion['productos_sincronizados'] ?? 0 }}</span>
                    </div>
                    
                    @if(($detallesSincronizacion['productos_creados'] ?? 0) > 0)
                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded">
                            <span class="font-medium text-blue-800">🆕 Productos nuevos:</span>
                            <span class="font-bold text-blue-600">{{ $detallesSincronizacion['productos_creados'] }}</span>
                        </div>
                    @endif
                    
                    @if(($detallesSincronizacion['productos_actualizados'] ?? 0) > 0)
                        <div class="flex justify-between items-center p-3 bg-yellow-50 rounded">
                            <span class="font-medium text-yellow-800">🔄 Productos actualizados:</span>
                            <span class="font-bold text-yellow-600">{{ $detallesSincronizacion['productos_actualizados'] }}</span>
                        </div>
                    @endif
                    
                    @if(($detallesSincronizacion['sin_cambios'] ?? 0) > 0)
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="font-medium text-gray-800">⚪ Sin cambios:</span>
                            <span class="font-bold text-gray-600">{{ $detallesSincronizacion['sin_cambios'] }}</span>
                        </div>
                    @endif
                    
                    <div class="flex justify-between items-center p-3 bg-orange-50 rounded">
                        <span class="font-medium text-orange-800">📈 Total procesados:</span>
                        <span class="font-bold text-orange-600">{{ $detallesSincronizacion['total_procesados'] ?? 0 }}</span>
                    </div>
                    
                    @if(($detallesSincronizacion['errores'] ?? 0) > 0)
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                            <span class="font-medium text-red-800">❌ Errores:</span>
                            <span class="font-bold text-red-600">{{ $detallesSincronizacion['errores'] }}</span>
                        </div>
                    @endif
                    
                    <div class="flex justify-between items-center p-3 bg-purple-50 rounded">
                        <span class="font-medium text-purple-800">⏱️ Tiempo:</span>
                        <span class="font-bold text-purple-600">{{ $detallesSincronizacion['tiempo_ejecucion'] ?? '~2 segundos' }}</span>
                    </div>
                </div>
                
                <div class="mt-6 text-center">
                    <button wire:click="cerrarDetallesSincronizacion" 
                            class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

</div> {{-- FIN ELEMENTO RAÍZ --}}
