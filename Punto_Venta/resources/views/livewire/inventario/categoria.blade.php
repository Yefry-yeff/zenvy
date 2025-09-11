<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Sección de Categorías Propias de Zenvy --}}
    <div class="overflow-hidden border border-gray-300 rounded shadow mb-6" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO CATEGORÍAS ZENVY -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t"
            :class="{
                'bg-emerald-600': theme === 'verde',
                'bg-blue-600': theme === 'azul',
                'bg-gray-900': theme === 'oscuro',
                'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
            }"
        >
            <h5 class="mb-0 text-lg">📂 Categorías Propias de Zenvy</h5>
            <button wire:click="abrirModalCrear"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Agregar Categoría
            </button>
        </div>

        <!-- TABLA CATEGORÍAS ZENVY -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="categoriasZenvyTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th style="width: 80px;">ID</th>
                            <th>Nombre</th>
                            <th style="width: 60px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoriasZenvy as $categoria)
                            <tr class="text-center align-middle hover:bg-gray-50">
                                <td class="cursor-pointer fw-semibold" wire:click="editar({{ $categoria->id }})">{{ $categoria->id }}</td>
                                <td class="cursor-pointer text-start" wire:click="editar({{ $categoria->id }})">{{ $categoria->nombre }}</td>
                                <td>
                                    <button type="button" class="p-0 btn btn-link" wire:click="confirmarEliminar({{ $categoria->id }})" title="Eliminar" onclick="event.stopPropagation();">
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
                                <td colspan="3" class="py-4 text-center text-muted">No hay categorías propias de Zenvy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Sección de Categorías de Valencia --}}
    <div class="overflow-hidden border border-orange-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO CATEGORÍAS VALENCIA -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t bg-orange-600">
            <h5 class="mb-0 text-lg">🏢 Categorías de Valencia (Solo Lectura)</h5>
            <button wire:click="sincronizarCategoriasValencia"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>🔄</span> Sincronizar
            </button>
        </div>

        <!-- TABLA CATEGORÍAS VALENCIA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="categoriasValenciaTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th style="width: 80px;">ID</th>
                            <th>Nombre</th>
                            <th style="width: 120px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categoriasValencia as $categoria)
                            <tr class="text-center align-middle bg-orange-50">
                                <td class="fw-semibold">{{ $categoria->id }}</td>
                                <td class="text-start">{{ $categoria->nombre }}</td>
                                <td>
                                    <span class="badge bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs">
                                        🔒 Sincronizada
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-muted">No hay categorías sincronizadas desde Valencia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Agregar Categoría -->
    <div wire:key="modal-nueva-categoria">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($modalCrearAbierto) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cerrarModalCrear()"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"
                         :class="{
                            'bg-emerald-700 text-white': theme === 'verde',
                            'bg-blue-700 text-white': theme === 'azul',
                            'bg-gray-900 text-white': theme === 'oscuro',
                            'bg-slate-700 text-white': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                         }"
                    >
                        <h5 class="modal-title">Nueva Categoría</h5>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="crearCategoria">
                            <div class="mb-3">
                                <label for="nuevaCategoriaNombre" class="form-label">Nombre</label>
                                <input type="text" id="nuevaCategoriaNombre" class="form-control" wire:model.defer="nuevaCategoriaNombre" placeholder="Ingrese el nombre de la categoría">
                                @error('nuevaCategoriaNombre')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="flex justify-end mt-4">
                                <button type="button" class="px-4 py-2 mr-2 text-gray-600 bg-gray-100 rounded hover:bg-gray-200" wire:click="cerrarModalCrear">
                                    Cancelar
                                </button>
                                <button type="submit" class="px-4 py-2 text-white rounded"
                                    :class="{
                                        'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                        'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                        'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                        'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                                    }"
                                >
                                    Crear Categoría
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
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
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirmar Eliminación</h5>
                    </div>
                    <div class="modal-body">
                        @if(count($productosVinculados) > 0)
                            <div class="alert alert-warning">
                                <h6><strong>⚠️ No se puede eliminar esta categoría</strong></h6>
                                <p>Esta categoría tiene <strong>{{ count($productosVinculados) }} producto(s)</strong> vinculado(s) a través de sus subcategorías. 
                                   Primero debe eliminar o cambiar la subcategoría de estos productos:</p>
                            </div>
                            
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-striped">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 150px;">Código de Barras</th>
                                            <th>Nombre del Producto</th>
                                            <th style="width: 120px;">Subcategoría</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($productosVinculados as $producto)
                                            <tr>
                                                <td class="text-center">
                                                    <code class="bg-light px-2 py-1 rounded">{{ $producto['codigo_barra'] }}</code>
                                                </td>
                                                <td>{{ $producto['nombre'] }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">{{ $producto['subcategoria'] }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <small>
                                    <strong>💡 Sugerencia:</strong> 
                                    Vaya al módulo de <strong>Productos</strong> y edite cada producto para cambiar su subcategoría o elimínelos.
                                </small>
                            </div>
                            
                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" class="btn btn-secondary" wire:click="cerrarModalEliminar">
                                    <i class="fas fa-times me-1"></i> Cerrar
                                </button>
                            </div>
                        @else
                            <div class="alert alert-success">
                                <h6><strong>✅ Esta categoría se puede eliminar</strong></h6>
                                <p>No hay productos vinculados a las subcategorías de esta categoría.</p>
                            </div>
                            
                            <p><strong>¿Estás seguro que deseas eliminar esta categoría?</strong></p>
                            <p class="text-muted">Esta acción eliminará también todas sus subcategorías y no se puede deshacer.</p>
                            
                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" class="btn btn-secondary" wire:click="cerrarModalEliminar">
                                    <i class="fas fa-times me-1"></i> No, cancelar
                                </button>
                                <button type="button" class="btn btn-danger" wire:click="eliminarCategoria">
                                    <i class="fas fa-trash me-1"></i> Sí, eliminar
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas --}}
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
