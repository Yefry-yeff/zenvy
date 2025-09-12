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
    <div class="modal fade show"
         tabindex="-1"
         style="display: block; background: rgba(0,0,0,0.5); z-index: 1050;"
         aria-modal="true"
         role="dialog"
         x-data="{ autoClose: false, timeoutId: null }"
         x-init="
            this.timeoutId = setTimeout(() => {
                this.autoClose = true;
            }, 5000);
            $watch('autoClose', value => {
                if(value) {
                    if(this.timeoutId) clearTimeout(this.timeoutId);
                    $wire.cerrarDetallesSincronizacion();
                }
            });
            // Limpiar timeout si el modal se cierra manualmente
            $watch('$wire.detallesSincronizacion', value => {
                if(!value && this.timeoutId) {
                    clearTimeout(this.timeoutId);
                    this.timeoutId = null;
                }
            })
         ">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="text-white modal-header bg-gradient-to-r from-orange-500 to-orange-600">
                    <h5 class="modal-title font-bold flex items-center gap-2">
                        @if(isset($detallesSincronizacion['error']) && $detallesSincronizacion['error'])
                            <span class="text-2xl">❌</span> Error en Sincronización
                        @else
                            <span class="text-2xl">✅</span> Sincronización Completada
                        @endif
                    </h5>
                    <button type="button"
                            class="btn-close btn-close-white"
                            wire:click="cerrarDetallesSincronizacion"></button>
                </div>

                <div class="modal-body p-6">
                    @if(isset($detallesSincronizacion['error']) && $detallesSincronizacion['error'])
                        <!-- Error de sincronización -->
                        <div class="text-center">
                            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <h6 class="text-red-800 font-semibold mb-2">⚠️ Error durante la sincronización</h6>
                                <p class="text-red-700 text-sm">{{ $detallesSincronizacion['mensaje_error'] ?? 'Error desconocido' }}</p>
                            </div>
                        </div>
                    @else
                        <!-- Detalles exitosos -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                                <div class="text-3xl font-bold text-green-600">{{ $detallesSincronizacion['productos_sincronizados'] }}</div>
                                <div class="text-sm text-green-700 font-medium">Productos Sincronizados</div>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                                <div class="text-3xl font-bold text-blue-600">{{ $detallesSincronizacion['total_procesados'] }}</div>
                                <div class="text-sm text-blue-700 font-medium">Total Procesados</div>
                            </div>

                            @if($detallesSincronizacion['errores'] > 0)
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                                <div class="text-3xl font-bold text-red-600">{{ $detallesSincronizacion['errores'] }}</div>
                                <div class="text-sm text-red-700 font-medium">Errores Encontrados</div>
                            </div>
                            @endif

                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                                <div class="text-lg font-bold text-gray-600">{{ $detallesSincronizacion['tiempo_ejecucion'] }}</div>
                                <div class="text-sm text-gray-700 font-medium">Tiempo de Ejecución</div>
                            </div>
                        </div>

                        <!-- Detalles de acciones realizadas -->
                        @if(isset($detallesSincronizacion['productos_creados']) || isset($detallesSincronizacion['productos_actualizados']))
                        <div class="row mb-4">
                            @if($detallesSincronizacion['productos_creados'] > 0)
                            <div class="col-md-6">
                                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-center">
                                    <div class="text-2xl font-bold text-emerald-600">{{ $detallesSincronizacion['productos_creados'] }}</div>
                                    <div class="text-sm text-emerald-700 font-medium">🆕 Productos Nuevos</div>
                                </div>
                            </div>
                            @endif

                            @if($detallesSincronizacion['productos_actualizados'] > 0)
                            <div class="col-md-6">
                                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-center">
                                    <div class="text-2xl font-bold text-amber-600">{{ $detallesSincronizacion['productos_actualizados'] }}</div>
                                    <div class="text-sm text-amber-700 font-medium">🔄 Productos Actualizados</div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        <!-- Barra de progreso completa -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="font-medium text-gray-700">Progreso de Sincronización</span>
                                <span class="text-green-600 font-bold">100% Completado</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-green-400 to-green-600 h-3 rounded-full w-full transition-all duration-1000"></div>
                            </div>
                        </div>

                        <!-- Mensaje de éxito -->
                        <div class="text-center p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-green-800 font-medium">
                                🎉 La sincronización se ha completado exitosamente.
                                @if(isset($detallesSincronizacion['productos_actualizados']) && $detallesSincronizacion['productos_actualizados'] > 0)
                                    Se actualizaron {{ $detallesSincronizacion['productos_actualizados'] }} productos existentes con los últimos cambios de Valencia.
                                @endif
                                @if(isset($detallesSincronizacion['productos_creados']) && $detallesSincronizacion['productos_creados'] > 0)
                                    Se agregaron {{ $detallesSincronizacion['productos_creados'] }} productos nuevos de Valencia.
                                @endif
                                Los productos están ahora disponibles en el sistema.
                            </p>
                        </div>
                    @endif
                </div>

                <div class="modal-footer bg-gray-50">
                    <small class="text-gray-500 mr-auto">Este modal se cerrará automáticamente en 5 segundos</small>
                    <button type="button"
                            class="btn btn-secondary"
                            wire:click="cerrarDetallesSincronizacion">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div> {{-- FIN ELEMENTO RAÍZ --}}
