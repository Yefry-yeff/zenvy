<div>

    {{-- Tabla de CAI --}}
    <div class="overflow-hidden border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t" :class="{'bg-emerald-600': theme === 'verde','bg-blue-600': theme === 'azul','bg-gray-900': theme === 'oscuro','bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'}">
            <h5 class="mb-0 text-lg">Gestión de CAI</h5>
            <button wire:click="abrirModalCrear" class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Ingresar Cai
            </button>
        </div>

        <!-- TABLA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="marcasTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>Cod</th>
                            <th>Doc. Fiscal</th>
                            <th>Nombre Comercial</th>
                            <th>Cai</th>
                            <th>Rango Inicial</th>
                            <th>Rango Final</th>
                            <th>Limite Emisión</th>
                            <th>Solicitud</th>
                            <th>Punto Emisión</th>
                            <th>Cant. Solicitada</th>
                            <th>Cant. Ortogada</th>
                            <th>Registrado por</th>
                            <th>Estado</th>
                            <th>Registro</th>
                            <th>Ult. Modificación</th>
                            <th >Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($cai as $item)
                            <tr class="text-center align-middle hover:bg-gray-50">
                                <td class="fw-semibold cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->id }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->tipo_documento_fiscal }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->denominacion_social }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->cai }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->rango_inicio }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->rango_final }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->fecha_limite_emision }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->fecha_solicitud }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->punto_emision }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->cantidad_solicitada }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->cantidad_otorgada }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->users_registro }}</td>
                                @if($item->estado_id = 1)
                                    <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})"><span class="badge bg-success">Activo</span></td>
                                @elseif($item->estado_id = 2)
                                    <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})"><span class="badge bg-success">Activo</span></td>
                                @endif
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->created_at }}</td>
                                <td class="text-start cursor-pointer" wire:click="editar({{ $item->id }})">{{ $item->updated_at }}</td>
                                <td>
                                    <button type="button" class="btn btn-link p-0" wire:click="confirmarEliminar({{ $item->id }})" title="Eliminar" onclick="event.stopPropagation();">
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
                                <td colspan="3" class="py-4 text-center text-muted">No hay registros disponibles.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Modal Editar Marca --}}
    <div wire:key="modal-{{ $form['id'] ?? 'nuevo' }}">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($modalAbierto) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cerrarModal()"
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
                        <h5 class="modal-title">Editar Marca</h5>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="guardar">
                            <div class="mb-3">
                                <label for="marcaId" class="form-label">ID</label>
                                <input type="text" id="marcaId" class="form-control" wire:model="form.id" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="marcaNombre" class="form-label">Nombre</label>
                                <input type="text" id="marcaNombre" class="form-control"
                                       wire:model.defer="form.nombre">
                                @error('form.nombre')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="flex justify-end mt-4">
                                <button
                                    type="submit"
                                    class="px-4 py-2 text-white rounded"
                                    :class="{
                                        'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                        'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                        'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                        'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                                    }"
                                >
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Marca -->
    <div wire:key="modal-nueva-marca">
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
                        <h5 class="modal-title">Agregar Marca</h5>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="crearMarca">
                            <div class="mb-3">
                                <label for="nuevaMarcaNombre" class="form-label">Nombre</label>
                                <input type="text" id="nuevaMarcaNombre" class="form-control" wire:model.defer="nuevaMarcaNombre">
                                @error('nuevaMarcaNombre')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="flex justify-end mt-4">
                                <button
                                    type="submit"
                                    class="px-4 py-2 text-white rounded"
                                    :class="{
                                        'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                        'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                        'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                        'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                                    }"
                                >
                                    Guardar
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
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">¿Eliminar marca?</h5>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro que deseas eliminar esta marca? Esta acción no se puede deshacer.</p>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModalEliminar">No</button>
                            <button type="button" class="btn btn-danger" wire:click="eliminarMarca">Sí, eliminar</button>
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
             class="alert alert-success mt-3 mb-0 transition-opacity duration-300">
            {{ session('mensaje') }}
        </div>
    @endif

</div> {{-- FIN ELEMENTO RAÍZ --}}
