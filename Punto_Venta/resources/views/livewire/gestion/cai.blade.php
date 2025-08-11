<div>
    <style>
        /* Estilos para tabla CAI */
        .cai-row-active {
            background-color: rgba(34, 197, 94, 0.05) !important;
        }
        .cai-row-inactive {
            background-color: rgba(239, 68, 68, 0.05) !important;
            opacity: 0.7;
        }
        .badge-active {
            background-color: #22c55e !important;
            color: white;
            font-weight: 600;
        }
        .badge-inactive {
            background-color: #ef4444 !important;
            color: white;
            font-weight: 600;
        }
    </style>

    @if (session()->has('mensaje'))
    <div x-data="{ show: true }" x-init="$nextTick(() => show = true)"
        x-show="show"
        x-transition
        style="display: none;"  {{-- Para que no aparezca en flash antes de Alpine --}}
    >
        <div class="modal fade show d-block" tabindex="-1" role="dialog" @click.away="show = false">
            <div class="modal-dialog modal-dialog-centered" @click.stop>
                <div class="shadow modal-content border-success">
                    <div class="text-white modal-header bg-success">
                        <h5 class="modal-title">Éxito</h5>
                        <button type="button" class="btn-close" @click="show = false"></button>
                    </div>
                    <div class="modal-body">
                        <p>{{ session('mensaje') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" @click="show = false">Aceptar</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Backdrop manual -->
        <div class="modal-backdrop fade show" x-show="show" x-transition></div>
    </div>
    @endif



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
                <table id="tbl_cai" class="table mb-0 align-middle table-sm table-hover table-bordered">
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
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($cai as $item)
                            <tr class="text-center align-middle hover:bg-gray-50 {{ $item->estado_id == 1 ? 'cai-row-active' : 'cai-row-inactive' }}">
                                <td class="fw-semibold">{{ $item->id }}</td>
                                <td class="text-start">{{ $item->tipo_documento_fiscal }}</td>
                                <td class="text-start">{{ $item->denominacion_social }}</td>
                                <td class="text-start">{{ $item->cai }}</td>
                                <td class="text-start">{{ $item->rango_inicio }}</td>
                                <td class="text-start">{{ $item->rango_final }}</td>
                                <td class="text-start">{{ $item->fecha_limite_emision }}</td>
                                <td class="text-start">{{ $item->fecha_solicitud }}</td>
                                <td class="text-start">{{ $item->punto_emision }}</td>
                                <td class="text-start">{{ $item->cantidad_solicitada }}</td>
                                <td class="text-start">{{ $item->cantidad_otorgada }}</td>
                                <td class="text-start">{{ $item->users_registro }}</td>
                                <td class="text-start">
                                    <span class="badge {{ $item->estado_id == 1 ? 'badge-active' : 'badge-inactive' }}">
                                        {{ $item->estado_id == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>

                                <td class="text-start">{{ $item->created_at }}</td>
                                <td class="text-start">{{ $item->updated_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                 <td colspan="14" class="text-center">No hay registros disponibles.</td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>

    </div>


    {{--   Modal Agregar cai   --}}
    <div wire:key="modal-nuevp-cai">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($modalCrearAbierto) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cerrarModalCrear()"
        >
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header"
                         :class="{
                            'bg-emerald-700 text-white': theme === 'verde',
                            'bg-blue-700 text-white': theme === 'azul',
                            'bg-gray-900 text-white': theme === 'oscuro',
                            'bg-slate-700 text-white': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                         }"
                    >
                        <h5 class="modal-title">Ingreso de CAI</h5>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="crearCai">
                            <div class="row">
                                <div class="mb-2 col-md-4">
                                    <label for="nuevoCai" class="form-label">CAI</label>
                                    <input type="text" id="nuevoCai" class="form-control" wire:model.defer="nuevoCai"  title="El CAI debe tener el formato ####-####-####-####-####-####" maxlength="39">
                                    @error('nuevoCai')
                                        <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-2 col-md-4">
                                    <label for="nuevoFechaLimite" class="form-label">Fecha límite</label>
                                    <input type="date" id="nuevoFechaLimite" class="form-control" wire:model.defer="nuevoFechaLimite" title="Debe seleccionar una fecha límite de vigencia de este CAI.">
                                    @error('nuevoFechaLimite')
                                        <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                    @enderror
                                </div>


                                <div class="mb-2 col-md-4">
                                    <label for="nuevoFechaSolicitud" class="form-label">Fecha de Solicitud</label>
                                    <input type="date" id="nuevoFechaSolicitud" class="form-control" wire:model.defer="nuevoFechaSolicitud" title="Debe seleccionar una fecha límite de vigencia de este CAI.">
                                    @error('nuevoFechaSolicitud')
                                        <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                    @enderror
                                </div>


                                <div class="mb-2 col-md-4">
                                    <label for="nuevoPuntoEmision" class="form-label">Punto de Emisión</label>
                                    <input type="text" id="nuevoPuntoEmision" class="form-control" wire:model.defer="nuevoPuntoEmision" title="Debe seleccionar una fecha límite de vigencia de este CAI.">
                                    @error('nuevoPuntoEmision')
                                        <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-2 col-md-4">
                                    <label for="tipoDocumento" class="form-label">Tipo de documento</label>
                                    <select id="tipoDocumento" class="form-select" wire:model.defer="tipoDocumentoSeleccionado">
                                        <option value="">Seleccione un tipo...</option>
                                        @foreach($tiposDocumento as $tipo)
                                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('tipoDocumentoSeleccionado')
                                        <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                    @enderror
                                </div>


                                <div class="mb-2 col-md-4"
                                    x-data="{
                                        search: '',
                                        open: false,
                                        selected: @entangle('tiendaSeleccionado'),
                                        options: {{ $tiendas->toJson() }},
                                        filteredOptions() {
                                            if (this.search === '') return this.options;
                                            const term = this.search.toLowerCase();
                                            return this.options.filter(o =>
                                                String(o.id).toLowerCase().includes(term) ||
                                                String(o.identificador_legal).toLowerCase().includes(term) ||
                                                String(o.numero_sucursal).toLowerCase().includes(term) ||
                                                String(o.denominacion_social).toLowerCase().includes(term)
                                            );
                                        },
                                        selectOption(option) {
                                            this.selected = option.id;
                                            this.search = `${option.id} (${option.denominacion_social} - ${option.numero_sucursal})`;
                                            this.open = false;
                                        },
                                        clearSearch() {
                                            this.search = '';
                                            this.open = true;
                                        },
                                        init() {
                                            this.$watch('selected', value => {
                                                const obj = this.options.find(o => o.id == value);
                                                if (obj) {
                                                    this.search = `${obj.id} (${obj.denominacion_social} - ${obj.numero_sucursal})`;
                                                }
                                            });
                                        }
                                    }"
                                    @click.outside="open = false"
                                >
                                    <label for="tiendaId" class="form-label">Seleccionar Tienda</label>

                                    <input type="text"
                                        placeholder="Buscar..."
                                        class="mb-1 form-control"
                                        x-model="search"
                                        @focus="open = true; clearSearch()"
                                        @input="open = true"
                                    >

                                    <ul class="list-group position-absolute w-100" x-show="open" style="z-index: 10; max-height: 150px; overflow-y: auto;">
                                        <template x-for="item in filteredOptions()" :key="item.id">
                                            <li class="list-group-item list-group-item-action"
                                                @click="selectOption(item)"
                                                x-text="`${item.id} - ${item.denominacion_social} - ${item.numero_sucursal}`">
                                            </li>
                                        </template>
                                    </ul>

                                    @error('tiendaSeleccionado')
                                        <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-2 col-md-4">
                                    <label for="nuevoCantidadSolicitada" class="form-label">Cantidad Solicitada</label>
                                    <input type="number" id="nuevoCantidadSolicitada" step="1" min="0" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" wire:model.defer="nuevoCantidadSolicitada" title="Debe ingresar un numero entero.">
                                    @error('nuevoCantidadSolicitada')
                                        <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-2 col-md-4">
                                    <label for="nuevoCantidadOtorgada" class="form-label">Cantidad Otorgada</label>
                                    <input type="number" id="nuevoCantidadOtorgada" step="1" min="0" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" wire:model.defer="nuevoCantidadOtorgada" title="Debe ingresar un numero entero.">
                                    @error('nuevoCantidadOtorgada')
                                        <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-2 col-md-4">
                                    <label for="nuevoRangoInicial" class="form-label">Rango Inicial</label>
                                    <input type="text" id="nuevoRangoInicial" step="1" class="form-control" wire:model.defer="nuevoRangoInicial" title="Debe contener el formato correcto.">
                                    @error('nuevoRangoInicial')
                                        <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-2 col-md-4">
                                    <label for="nuevoRangoFinal" class="form-label">Rando Final</label>
                                    <input type="text" id="nuevoRangoFinal" class="form-control" wire:model.defer="nuevoRangoFinal" title="Debe contener el formato correcto.">
                                    @error('nuevoRangoFinal')
                                        <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                    @enderror
                                </div>

                            </div>
                            <div class="flex justify-end mt-4">
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
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


    {{-- Modal Editar cai --}}
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
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
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
                    <div class="text-white modal-header bg-danger">
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

</div> {{-- FIN ELEMENTO RAÍZ --}}
