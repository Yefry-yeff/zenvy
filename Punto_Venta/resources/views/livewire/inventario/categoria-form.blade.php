<div
    class="overflow-hidden border border-gray-300 rounded shadow"
    x-data="{ sinCambiosModal: false }"
    x-init="$watch('theme', t => localStorage.setItem('theme', t))"
    x-on:mostrar-sin-cambios.window="sinCambiosModal = true"
>

    <!-- ENCABEZADO CON TEMA -->
    <div
        class="flex items-center justify-between px-4 py-2 font-semibold text-white"
        :class="{
            'bg-emerald-600': theme === 'verde',
            'bg-blue-600': theme === 'azul',
            'bg-gray-900': theme === 'oscuro',
            'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
        }"
    >
        <button wire:click="volver" class="px-3 py-1 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
            ← Volver
        </button>
        <h5>{{ $categoriaId ? 'Editar Categoría' : 'Crear Categoría' }}</h5>
        <button wire:click="guardar" class="px-3 py-1 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
            💾 Guardar
        </button>
    </div>

    <!-- DATOS DE LA CATEGORÍA -->
    <div class="p-4">
        <div class="p-4 bg-white border shadow rounded-xl">
            <h2 class="mb-4 text-lg font-semibold text-gray-700">📁 Datos de la Categoría</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Nombre de la Categoría<span class="text-red-600">*</span></label>
                <input type="text" wire:model.defer="form.nombre" class="w-full px-3 py-2 border rounded" />
                @error('form.nombre')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <!-- SUBCATEGORÍAS -->
    @if($categoriaId)
    <div class="p-4">
        <div class="p-4 bg-white border shadow rounded-xl">
            <h2 class="mb-4 text-lg font-semibold text-gray-700">📂 Subcategorías</h2>

            <!-- Agregar nueva subcategoría -->
            <div class="flex gap-2 mb-4">
                <input
                    type="text"
                    wire:model.defer="nuevaSubcategoria"
                    placeholder="Nombre de la nueva subcategoría"
                    class="flex-1 px-3 py-2 border rounded"
                />
                <button
                    wire:click="agregarSubcategoria"
                    class="px-4 py-2 text-white rounded"
                    :class="{
                        'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                        'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                        'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                        'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                    }"
                >
                    ➕ Agregar
                </button>
            </div>
            @error('nuevaSubcategoria')
                <span class="text-sm text-red-600">{{ $message }}</span>
            @enderror

            <!-- Lista de subcategorías -->
            @if ($subcategorias->count() > 0)
                <table id="subcategoriaTable" class="w-full text-sm text-left border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 border">ID</th>
                            <th class="px-3 py-2 border">Nombre</th>
                            <th class="px-3 py-2 border">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subcategorias as $subcategoria)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 border cursor-pointer" wire:click="editarSubcategoria({{ $subcategoria->id }})">{{ $subcategoria->id }}</td>
                                <td class="px-3 py-2 border cursor-pointer" wire:click="editarSubcategoria({{ $subcategoria->id }})">{{ $subcategoria->nombre }}</td>
                                <td class="px-3 py-2 border">
                                    <button
                                        wire:click="eliminarSubcategoria({{ $subcategoria->id }})"
                                        class="p-0 btn btn-link"
                                        title="Eliminar"
                                        onclick="event.stopPropagation();"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7v12a2 2 0 002 2h8a2 2 0 002-2V7M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m-7 0h10" style="color:#e3342f;" />
                                            <line x1="10" y1="11" x2="10" y2="17" stroke="#e3342f" stroke-width="2"/>
                                            <line x1="14" y1="11" x2="14" y2="17" stroke="#e3342f" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="italic text-gray-600">No hay subcategorías asignadas a esta categoría.</p>
            @endif
        </div>
    </div>
    @endif

    <!-- MENSAJE DE ÉXITO -->
    @if ($mostrarMensaje)
    <div
        x-data="{ show: true }"
        x-show="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click.outside="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Inventario.categoria' })"
        @keydown.window.escape="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Inventario.categoria' })"
    >
        <div class="w-full max-w-sm p-6 text-center bg-white rounded-lg shadow-lg">
            <h2 class="mb-2 text-lg font-semibold text-green-700">✅ Categoría guardada correctamente</h2>
            <p class="text-sm text-gray-600">Los cambios se han guardado exitosamente.</p>
            <button
                class="px-4 py-2 mt-4 text-sm text-white rounded bg-emerald-600 hover:bg-emerald-700"
                @click="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Inventario.categoria' })"
            >
                Cerrar
            </button>
        </div>
    </div>
    @endif

    <!-- MENSAJES DE SESIÓN -->
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

    <!-- Modal Eliminar Subcategoría -->
    <div wire:key="modal-eliminar-subcategoria">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($modalEliminarSubcategoriaAbierto) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cerrarModalEliminarSubcategoria()"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="text-white modal-header bg-danger">
                        <h5 class="modal-title">¿Eliminar subcategoría?</h5>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro que deseas eliminar esta subcategoría? Esta acción no se puede deshacer.</p>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModalEliminarSubcategoria">No</button>
                            <button type="button" class="btn btn-danger" wire:click="confirmarEliminarSubcategoria">Sí, eliminar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Subcategoría -->
    <div wire:key="modal-editar-subcategoria">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($modalEditarSubcategoriaAbierto) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cerrarModalEditarSubcategoria()"
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
                        <h5 class="modal-title">Editar Subcategoría</h5>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="guardarSubcategoria">
                            <div class="mb-3">
                                <label for="subcategoriaNombre" class="form-label">Nombre de la Subcategoría</label>
                                <input type="text" id="subcategoriaNombre" class="form-control" wire:model.defer="formSubcategoria.nombre">
                                @error('formSubcategoria.nombre')
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
                                    💾 Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
