<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Tabla de Sucursales --}}
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
            <h5 class="mb-0 text-lg">Gestión de Sucursales</h5>
            <button wire:click="abrirModalCrear"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Agregar Sucursal
            </button>
        </div>

        <!-- TABLA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="sucursalesTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>Denominación Social</th>
                            <th>Número Sucursal</th>
                            <th>Tipo Tienda</th>
                            <th>Teléfono</th>
                            <th>Municipio</th>
                            <th>Estado</th>
                            <th style="width: 150px;">Fecha Creación</th>
                            <th style="width: 60px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sucursales as $sucursal)
                            <tr class="text-center align-middle hover:bg-gray-50">
                                <td class="text-start cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->denominacion_social }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->numero_sucursal ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->tipoTienda->nombre ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->telefono ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->direccion->municipio->nombre ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">
                                    <span class="badge {{ $sucursal->estado_id == 1 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $sucursal->estado->descripcion ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->created_at ? $sucursal->created_at->format('d/m/Y') : 'N/A' }}</td>
                                <td>
                                    <button type="button" class="btn btn-link p-0" wire:click="confirmarEliminar({{ $sucursal->id }})" title="Eliminar" onclick="event.stopPropagation();">
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
                                <td colspan="8" class="py-4 text-center text-muted">No hay sucursales disponibles.</td>
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
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="text-white modal-header bg-danger">
                        <h5 class="modal-title">¿Eliminar sucursal?</h5>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro que deseas eliminar esta sucursal? Esta acción no se puede deshacer.</p>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModalEliminar">No</button>
                            <button type="button" class="btn btn-danger" wire:click="eliminarSucursal">Sí, eliminar</button>
                        </div>
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

    @if($alertMessage)
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="alert alert-{{ $alertType == 'success' ? 'success' : ($alertType == 'error' ? 'danger' : 'warning') }} mt-3 mb-0 transition-opacity duration-300">
            <i class="fas fa-{{ $alertType == 'success' ? 'check-circle' : ($alertType == 'error' ? 'exclamation-triangle' : 'exclamation-circle') }}"></i>
            {{ $alertMessage }}
        </div>
    @endif

</div> {{-- FIN ELEMENTO RAÍZ --}}
