<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Tabla de Productos --}}
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
            <h5 class="mb-0 text-lg">Gestión de Productos</h5>
            <button wire:click="abrirModalCrear"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Agregar Producto
            </button>
        </div>

        <!-- TABLA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="productosTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
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
                        @forelse($productos as $producto)
                            <tr class="text-center align-middle hover:bg-gray-50">
                                <td class="cursor-pointer text-start" wire:click="editar({{ $producto->id }})">{{ $producto->nombre }}</td>
                                <td class="cursor-pointer text-start" wire:click="editar({{ $producto->id }})">{{ $producto->descripcion ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $producto->id }})">{{ $producto->subcategoria->categoria->nombre ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $producto->id }})">{{ $producto->subcategoria->nombre ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $producto->id }})">{{ $producto->marca->nombre ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $producto->id }})">{{ $producto->created_at ? $producto->created_at->format('d/m/Y') : 'N/A' }}</td>
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
                                <td colspan="7" class="py-4 text-center text-muted">No hay productos disponibles.</td>
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
                                        <span>{{ $productoSeleccionado['nombre'] }}</span>
                                    </div>
                                    <div class="col-md-6">
                                        <small><strong>Código de Barras:</strong></small><br>
                                        <span>{{ $productoSeleccionado['codigo_barra'] ?: 'Sin código' }}</span>
                                    </div>
                                    <div class="mt-2 col-md-4">
                                        <small><strong>Marca:</strong></small><br>
                                        <span>{{ $productoSeleccionado['marca'] }}</span>
                                    </div>
                                    <div class="mt-2 col-md-4">
                                        <small><strong>Categoría:</strong></small><br>
                                        <span>{{ $productoSeleccionado['categoria'] }}</span>
                                    </div>
                                    <div class="mt-2 col-md-4">
                                        <small><strong>Subcategoría:</strong></small><br>
                                        <span>{{ $productoSeleccionado['subcategoria'] }}</span>
                                    </div>
                                </div>
                            </div>

                            @if(!$puedeEliminar)
                                <div class="alert alert-warning">
                                    <h6><strong>⚠️ No se puede eliminar este producto</strong></h6>
                                    <p>El producto no cumple con los requisitos para ser eliminado:</p>
                                    <ul class="mb-2">
                                        @if($tieneCodigoBarras)
                                            <li><strong>Código de barras asignado:</strong> El producto tiene el código "{{ $productoSeleccionado['codigo_barra'] }}" asignado.</li>
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

</div> {{-- FIN ELEMENTO RAÍZ --}}
