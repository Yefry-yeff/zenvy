<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Tabla de Marcas --}}
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
            <h5 class="mb-0 text-lg">Gestión de Marcas</h5>
            <button wire:click="abrirModalCrear"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Agregar Marca
            </button>
        </div>

        <!-- TABLA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="marcasTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th style="width: 80px;">ID</th>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($marcas as $marca)
                            <tr class="text-center align-middle cursor-pointer hover:bg-gray-50"
                                wire:click="editar({{ $marca->id }})">
                                <td class="fw-semibold">{{ $marca->id }}</td>
                                <td class="text-start">{{ $marca->nombre }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-4 text-center text-muted">No hay marcas disponibles.</td>
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
