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
            <h5 class="mb-0 text-lg">📏 Unidades de Medida de Paperland</h5>
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

            <!-- Botón de sincronización con estado de carga -->
            <div class="relative">
                <button wire:click="sincronizarUnidadesValencia"
                    wire:loading.attr="disabled"
                    wire:target="sincronizarUnidadesValencia"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100 disabled:opacity-75 disabled:cursor-not-allowed">

                    <!-- Spinner de carga -->
                    <div wire:loading wire:target="sincronizarUnidadesValencia" class="inline-block w-4 h-4 border-2 border-gray-300 border-t-orange-600 rounded-full animate-spin"></div>

                    <!-- Icono normal -->
                    <span wire:loading.remove wire:target="sincronizarUnidadesValencia">🔄</span>

                    <!-- Texto del botón -->
                    <span wire:loading.remove wire:target="sincronizarUnidadesValencia">Sincronizar</span>
                    <span wire:loading wire:target="sincronizarUnidadesValencia">Sincronizando...</span>
                </button>
            </div>
        </div>

        <!-- Barra de progreso para sincronización -->
        @if($sincronizandoUnidades && $progreso !== null)
            <div class="px-5 pb-3">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-orange-600 h-2 rounded-full transition-all duration-300"
                         style="width: {{ $progreso }}%"></div>
                </div>
                <p class="text-sm text-gray-600 mt-1">Sincronizando unidades... {{ $progreso }}%</p>
            </div>
        @endif

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

    {{-- Modal de Detalles de Sincronización de Unidades --}}
    @if($detallesSincronizacion)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            {{-- Overlay --}}
            <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="cerrarDetallesSincronizacion"></div>

            {{-- Modal --}}
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">📊 Sincronización de Unidades Completada</h3>
                    <button wire:click="cerrarDetallesSincronizacion" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded">
                        <span class="font-medium text-green-800">✅ Unidades procesadas:</span>
                        <span class="font-bold text-green-600">{{ $detallesSincronizacion['unidades_sincronizadas'] }}</span>
                    </div>

                    @if($detallesSincronizacion['unidades_nuevas'] > 0)
                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded">
                            <span class="font-medium text-blue-800">🆕 Unidades nuevas:</span>
                            <span class="font-bold text-blue-600">{{ $detallesSincronizacion['unidades_nuevas'] }}</span>
                        </div>
                    @endif

                    @if($detallesSincronizacion['unidades_actualizadas'] > 0)
                        <div class="flex justify-between items-center p-3 bg-yellow-50 rounded">
                            <span class="font-medium text-yellow-800">🔄 Unidades actualizadas:</span>
                            <span class="font-bold text-yellow-600">{{ $detallesSincronizacion['unidades_actualizadas'] }}</span>
                        </div>
                    @endif

                    @if($detallesSincronizacion['sin_cambios'] > 0)
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="font-medium text-gray-800">⚪ Sin cambios:</span>
                            <span class="font-bold text-gray-600">{{ $detallesSincronizacion['sin_cambios'] }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-center p-3 bg-orange-50 rounded">
                        <span class="font-medium text-orange-800">📈 Total procesadas:</span>
                        <span class="font-bold text-orange-600">{{ $detallesSincronizacion['total_procesadas'] }}</span>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-purple-50 rounded">
                        <span class="font-medium text-purple-800">⏱️ Tiempo:</span>
                        <span class="font-bold text-purple-600">{{ $detallesSincronizacion['tiempo_ejecucion'] }}</span>
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
