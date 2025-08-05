<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Productos de la Sección --}}
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
            <div>
                <h5 class="mb-0 text-lg">
                    <i class="fas fa-boxes me-2"></i>Productos en Sección
                </h5>
                @if($seccion)
                    <small class="opacity-90">
                        <i class="fas fa-warehouse me-1"></i>{{ $seccion->segmento->bodega->nombre }}
                        <i class="fas fa-layer-group me-1 ms-2"></i>{{ $seccion->segmento->descripcion }}
                        <i class="fas fa-cube me-1 ms-2"></i>{{ $seccion->descripcion }}
                    </small>
                @endif
            </div>

            <div class="flex gap-2">
                <button wire:click="volverASecciones"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <i class="fas fa-arrow-left"></i> Volver a Secciones
                </button>
            </div>
        </div>

        <!-- CONTENIDO -->
        <div class="px-4 py-3 pt-0 card-body">
            <!-- Información de la Sección -->
            @if($seccion)
                <div class="p-3 mb-4 rounded bg-light">
                    <div class="row">
                        <div class="col-md-3">
                            <strong><i class="fas fa-warehouse text-primary me-2"></i>Bodega:</strong><br>
                            {{ $seccion->segmento->bodega->nombre }}
                        </div>
                        <div class="col-md-3">
                            <strong><i class="fas fa-layer-group text-success me-2"></i>Segmento:</strong><br>
                            {{ $seccion->segmento->descripcion }}
                        </div>
                        <div class="col-md-3">
                            <strong><i class="fas fa-cube text-info me-2"></i>Sección:</strong><br>
                            {{ $seccion->descripcion }} ({{ $seccion->numeracion }})
                        </div>
                        <div class="col-md-3">
                            <strong><i class="fas fa-store text-warning me-2"></i>Tienda:</strong><br>
                            {{ $seccion->segmento->bodega->tienda->denominacion_social ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            @endif

           <!-- Tabla de Productos -->
            <div class="table-responsive">
                <table id="productosSeccionTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Código</th>
                            <th>Marca</th>
                            <th>Categoría</th>
                            <th>U. Medida</th>
                            <th>Stock</th>
                            <th>F. Recibido</th>
                            <th>F. Expiración</th>
                            <th>Precio Base</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $recibido)
                            <tr class="text-center align-middle hover:bg-gray-50">
                                <td>{{ $recibido->producto->id }}</td>
                                <td class="text-start">
                                    <div>
                                        <strong>{{ $recibido->producto->nombre }}</strong><br>
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($recibido->producto->descripcion, 50) }}</small>
                                    </div>
                                </td>
                                <td>
                                    @if($recibido->producto->codigo_barra)
                                        <span class="badge bg-primary">{{ $recibido->producto->codigo_barra }}</span><br>
                                    @endif
                                    @if($recibido->producto->codigo_estatal)
                                        <span class="badge bg-secondary">{{ $recibido->producto->codigo_estatal }}</span>
                                    @endif
                                </td>
                                <td>{{ $recibido->producto->marca->nombre ?? 'Sin marca' }}</td>
                                <td>
                                    {{ $recibido->producto->subcategoria->categoria->nombre ?? 'Sin categoría' }}<br>
                                    <small class="text-muted">{{ $recibido->producto->subcategoria->txt_nombre ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $recibido->producto->unidadMedidaCompra->nombre ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $recibido->cantidad_inicial_seccion > 10 ? 'bg-success' : ($recibido->cantidad_inicial_seccion > 0 ? 'bg-warning' : 'bg-danger') }}">
                                        {{ $recibido->cantidad_inicial_seccion }}
                                    </span>
                                </td>
                                <td>{{ $this->formatearFecha($recibido->fecha_recibido) }}</td>
                                <td>
                                    @if($recibido->fecha_expiracion)
                                        <span class="badge {{ \Carbon\Carbon::parse($recibido->fecha_expiracion)->isPast() ? 'bg-danger' : 'bg-success' }}">
                                            {{ $this->formatearFecha($recibido->fecha_expiracion) }}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td class="text-end">L. {{ number_format($recibido->producto->precio_base ?? 0, 2) }}</td>
                                <td>
                                    <span class="{{ $this->obtenerEstadoClase($recibido->producto->estado_id) }}">
                                        {{ $this->obtenerEstadoTexto($recibido->producto->estado_id) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-4 text-center text-muted">No hay productos disponibles en esta sección.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Modal de Éxito -->
        @if($mostrarModalExito)
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="text-white modal-header bg-success">
                            <h5 class="modal-title">
                                <i class="fas fa-check-circle me-2"></i>¡Éxito!
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="text-center">
                                <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">{{ $mensajeModalExito }}</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" wire:click="cerrarModalExito" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Entendido
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Modal de Error -->
        @if($mostrarModalError)
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="text-white modal-header bg-danger">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle me-2"></i>Error
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">{{ $mensajeModalError }}</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" wire:click="cerrarModalError" class="btn btn-danger">
                                <i class="fas fa-times me-2"></i>Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Estilos CSS adicionales -->
    <style>
        .modal.show {
            display: block !important;
        }

        /* Mejorar la visualización de badges en la tabla */
        .table td .badge {
            font-size: 0.75rem;
        }

        /* Responsive para móviles */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>

    @if (session()->has('mensaje'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="alert alert-success mt-3 mb-0 transition-opacity duration-300">
            {{ session('mensaje') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="alert alert-danger mt-3 mb-0 transition-opacity duration-300">
            {{ session('error') }}
        </div>
    @endif

</div>
