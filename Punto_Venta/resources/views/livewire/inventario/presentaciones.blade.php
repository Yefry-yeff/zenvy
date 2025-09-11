<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Sección de Unidades Propias de Zenvy --}}
    <div class="overflow-hidden border border-gray-300 rounded shadow mb-6" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO UNIDADES ZENVY -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t"
            :class="{
                'bg-emerald-600': theme === 'verde',
                'bg-blue-600': theme === 'azul',
                'bg-gray-900': theme === 'oscuro',
                'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
            }"
        >
            <h5 class="mb-0 text-lg">📏 Unidades de Medida Propias de Zenvy</h5>
            <button wire:click="abrirModalCrear"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Agregar Unidad
            </button>
        </div>

        <!-- TABLA UNIDADES ZENVY -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="unidadesZenvyTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>Nombre</th>
                            <th style="width: 100px;">Símbolo</th>
                            <th style="width: 150px;">Fecha Creación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unidadesZenvy as $unidad)
                            <tr class="text-center align-middle hover:bg-gray-50">
                                <td class="text-start cursor-pointer" wire:click="editar({{ $unidad->id }})">{{ $unidad->nombre }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $unidad->id }})">{{ $unidad->simbolo }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $unidad->id }})">{{ $unidad->created_at ? $unidad->created_at->format('d/m/Y') : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-muted">No hay unidades propias de Zenvy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Sección de Unidades de Valencia --}}
    <div class="overflow-hidden border border-orange-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO UNIDADES VALENCIA -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t bg-orange-600">
            <h5 class="mb-0 text-lg">🏢 Unidades de Valencia (Solo Lectura)</h5>
            <button wire:click="sincronizarUnidadesValencia"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>🔄</span> Sincronizar
            </button>
        </div>

        <!-- TABLA UNIDADES VALENCIA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="unidadesValenciaTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>Nombre</th>
                            <th style="width: 100px;">Símbolo</th>
                            <th style="width: 120px;">Estado</th>
                            <th style="width: 150px;">Fecha Creación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unidadesValencia as $unidad)
                            <tr class="text-center align-middle bg-orange-50">
                                <td class="text-start">{{ $unidad->nombre }}</td>
                                <td>{{ $unidad->simbolo }}</td>
                                <td>
                                    <span class="badge bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs">
                                        🔒 Sincronizada
                                    </span>
                                </td>
                                <td>{{ $unidad->created_at ? $unidad->created_at->format('d/m/Y') : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-muted">No hay unidades sincronizadas desde Valencia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Modal Editar Unidad de Medida --}}
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
                        <h5 class="modal-title">Editar Unidad de Medida</h5>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="guardar">
                            <div class="mb-3" style="display: none;">
                                <label for="unidadId" class="form-label">ID</label>
                                <input type="text" id="unidadId" class="form-control" wire:model="form.id" readonly>
                            </div>
                            <div class="mb-3" style="display: none;">
                                <label for="unidadCantidad" class="form-label">Unidad</label>
                                <input type="number" id="unidadCantidad" class="form-control" wire:model.defer="form.unidad">
                                @error('form.unidad')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="unidadNombre" class="form-label">Nombre</label>
                                <input type="text" id="unidadNombre" class="form-control bg-gray-100" wire:model.defer="form.nombre" readonly>
                                <small class="text-muted">El nombre no se puede modificar</small>
                                @error('form.nombre')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="unidadSimbolo" class="form-label">Símbolo</label>
                                <input type="text" id="unidadSimbolo" class="form-control" wire:model.defer="form.simbolo">
                                @error('form.simbolo')
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

    <!-- Modal Agregar Unidad de Medida -->
    <div wire:key="modal-nueva-unidad">
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
                        <h5 class="modal-title">Agregar Unidad de Medida</h5>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="crearUnidad">
                            <div class="mb-3" style="display: none;">
                                <label for="nuevaUnidadCantidad" class="form-label">Unidad</label>
                                <input type="number" id="nuevaUnidadCantidad" class="form-control" wire:model.defer="nuevaUnidad">
                                @error('nuevaUnidad')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="nuevoNombreUnidad" class="form-label">Nombre</label>
                                <input type="text" id="nuevoNombreUnidad" class="form-control" wire:model.defer="nuevoNombre">
                                @error('nuevoNombre')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="nuevoSimboloUnidad" class="form-label">Símbolo</label>
                                <input type="text" id="nuevoSimboloUnidad" class="form-control" wire:model.defer="nuevoSimbolo">
                                @error('nuevoSimbolo')
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

</div> {{-- FIN ELEMENTO RAÍZ --}}
