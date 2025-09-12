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
        <div></div> <!-- Spacer para mantener el centrado -->
    </div>

    <!-- CATEGORÍA VALENCIA/NORMAL -->
    <div class="p-4">
        <div class="p-4 bg-white border shadow rounded-xl">
            @if($esCategoriaValencia)
                <h2 class="mb-4 text-lg font-semibold text-orange-700">🏢 Categoría de Valencia</h2>
                <p class="mb-4 text-sm text-orange-600">Esta es una categoría sincronizada desde Valencia. El nombre no se puede modificar, pero puedes agregar subcategorías propias de Zenvy.</p>
            @else
                <h2 class="mb-4 text-lg font-semibold text-gray-700">✏️ Editar Categoría</h2>
            @endif

    <!-- DATOS DE LA CATEGORÍA -->
    <div class="p-4">
        <div class="p-4 bg-white border shadow rounded-xl">
            <h2 class="mb-4 text-lg font-semibold text-gray-700">📁 Datos de la Categoría</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Nombre de la Categoría<span class="text-red-600">*</span></label>
                @if($esCategoriaValencia)
                    <div class="w-full px-3 py-2 bg-orange-50 border border-orange-200 rounded text-gray-700">
                        {{ $form['nombre'] }}
                        <span class="text-xs text-orange-600 ml-2">(Solo lectura - Sincronizada desde Valencia)</span>
                    </div>
                @else
                    <input type="text" wire:model.defer="form.nombre" class="w-full px-3 py-2 border rounded" />
                    @error('form.nombre')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                @endif
            </div>

            @if(!$categoriaId)
                <!-- Botón para proceder a subcategorías (solo en modo agregar) -->
                <div class="flex justify-end">
                    <button
                        wire:click="procederASubcategorias"
                        class="px-4 py-2 text-white rounded"
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                            'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                            'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                            'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }"
                    >
                        📂 Ingresar Subcategoría
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- SUBCATEGORÍAS -->
    <div class="p-4" x-data="{ mostrarSubcategorias: @entangle('mostrarSeccionSubcategorias') }">
        <div class="p-4 bg-white border shadow rounded-xl">
            @if($esCategoriaValencia)
                <!-- Vista especial para categorías de Valencia -->
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-orange-700">🔗 Gestión de Subcategorías</h2>
                    
                    <!-- Botón de sincronización con estado de carga -->
                    <div class="relative">
                        <button wire:click="sincronizarSubcategoriasValencia"
                            wire:loading.attr="disabled"
                            wire:target="sincronizarSubcategoriasValencia"
                            class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-orange-200 rounded hover:bg-orange-300 disabled:opacity-75 disabled:cursor-not-allowed">
                            
                            <!-- Spinner de carga -->
                            <div wire:loading wire:target="sincronizarSubcategoriasValencia" class="inline-block w-4 h-4 border-2 border-gray-300 border-t-orange-600 rounded-full animate-spin"></div>
                            
                            <!-- Icono normal -->
                            <span wire:loading.remove wire:target="sincronizarSubcategoriasValencia">🔄</span>
                            
                            <!-- Texto del botón -->
                            <span wire:loading.remove wire:target="sincronizarSubcategoriasValencia">Sincronizar Valencia</span>
                            <span wire:loading wire:target="sincronizarSubcategoriasValencia">Sincronizando...</span>
                        </button>
                    </div>
                </div>

                <!-- Barra de progreso para sincronización -->
                @if($sincronizandoSubcategorias && $progreso !== null)
                    <div class="mb-4">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-orange-600 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $progreso }}%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Sincronizando subcategorías... {{ $progreso }}%</p>
                    </div>
                @endif

                <!-- Subcategorías Propias de Zenvy -->
                <div class="mb-6">
                    <h3 class="mb-3 text-md font-semibold text-green-700">📦 Subcategorías Propias de Zenvy</h3>
                    
                    <!-- Agregar nueva subcategoría propia -->
                    <div class="flex gap-2 mb-4">
                        <input
                            type="text"
                            wire:model.defer="nuevaSubcategoria"
                            placeholder="Nombre de la nueva subcategoría propia"
                            class="flex-1 px-3 py-2 border rounded"
                        />
                        <button
                            wire:click="agregarSubcategoria"
                            class="px-4 py-2 text-white bg-green-600 rounded hover:bg-green-700"
                        >
                            ➕ Agregar
                        </button>
                    </div>
                    @error('nuevaSubcategoria')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror

                    <!-- Lista de subcategorías propias -->
                    @if(isset($subcategorias['zenvy']) && count($subcategorias['zenvy']) > 0)
                        <table class="w-full text-sm text-left border border-gray-300">
                            <thead class="bg-green-100">
                                <tr>
                                    <th class="px-3 py-2 border">ID</th>
                                    <th class="px-3 py-2 border">Nombre</th>
                                    <th class="px-3 py-2 border">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subcategorias['zenvy'] as $subcategoria)
                                    <tr class="hover:bg-green-50">
                                        <td class="px-3 py-2 border cursor-pointer" wire:click="editarSubcategoria({{ $subcategoria['id'] }})">{{ $subcategoria['id'] }}</td>
                                        <td class="px-3 py-2 border cursor-pointer" wire:click="editarSubcategoria({{ $subcategoria['id'] }})">{{ $subcategoria['nombre'] }}</td>
                                        <td class="px-3 py-2 border">
                                            <button
                                                wire:click="eliminarSubcategoria({{ $subcategoria['id'] }})"
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
                        <p class="text-gray-500 italic">No hay subcategorías propias de Zenvy para esta categoría.</p>
                    @endif
                </div>

                <!-- Subcategorías de Valencia -->
                <div>
                    <h3 class="mb-3 text-md font-semibold text-orange-700">🏢 Subcategorías de Valencia (Solo Lectura)</h3>
                    
                    @if(isset($subcategorias['valencia']) && count($subcategorias['valencia']) > 0)
                        <table class="w-full text-sm text-left border border-gray-300">
                            <thead class="bg-orange-100">
                                <tr>
                                    <th class="px-3 py-2 border">ID</th>
                                    <th class="px-3 py-2 border">Nombre</th>
                                    <th class="px-3 py-2 border">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subcategorias['valencia'] as $subcategoria)
                                    <tr class="bg-orange-50">
                                        <td class="px-3 py-2 border">{{ $subcategoria['id'] }}</td>
                                        <td class="px-3 py-2 border">{{ $subcategoria['nombre'] }}</td>
                                        <td class="px-3 py-2 border">
                                            <span class="badge bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs">
                                                🔒 Sincronizada
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-gray-500 italic">No hay subcategorías sincronizadas desde Valencia para esta categoría.</p>
                    @endif
                </div>

            @else
                <!-- Vista normal para categorías propias de Zenvy -->
                <h2 class="mb-4 text-lg font-semibold text-gray-700">📂 Subcategorías</h2>

            @if($categoriaId)
                <!-- Agregar nueva subcategoría (solo en modo editar) -->
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

                <!-- Lista de subcategorías (solo en modo editar) -->
                @if (isset($subcategorias['zenvy']) && count($subcategorias['zenvy']) > 0)
                    <table id="subcategoriaTable" class="w-full text-sm text-left border border-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 border">ID</th>
                                <th class="px-3 py-2 border">Nombre</th>
                                <th class="px-3 py-2 border">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subcategorias['zenvy'] as $subcategoria)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 border cursor-pointer" wire:click="editarSubcategoria({{ $subcategoria['id'] }})">{{ $subcategoria['id'] }}</td>
                                    <td class="px-3 py-2 border cursor-pointer" wire:click="editarSubcategoria({{ $subcategoria['id'] }})">{{ $subcategoria['nombre'] }}</td>
                                    <td class="px-3 py-2 border">
                                        <button
                                            wire:click="eliminarSubcategoria({{ $subcategoria['id'] }})"
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

                <!-- Botón guardar para modo editar -->
                <div class="flex justify-end mt-4">
                    <button
                        wire:click="guardar"
                        class="px-6 py-2 font-medium text-white rounded"
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                            'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                            'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                            'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }"
                    >
                        💾 Guardar Categoría
                    </button>
                </div>
            @else
                <!-- Modo agregar -->
                <div x-show="!mostrarSubcategorias">
                    <!-- Mensaje informativo inicial -->
                    <div class="p-4 text-center border-2 border-gray-300 border-dashed rounded-lg bg-gray-50">
                        <div class="text-gray-500">
                            <i class="mb-2 text-2xl fas fa-info-circle"></i>
                            <p class="text-sm font-medium">Las subcategorías se podrán gestionar después de crear la categoría</p>
                            <p class="mt-1 text-xs text-gray-400">Guarda primero la categoría principal para agregar subcategorías</p>
                        </div>
                    </div>
                </div>

                <div x-show="mostrarSubcategorias" x-transition>
                    <!-- Sección habilitada para agregar subcategorías -->
                    <div class="p-4 mb-4 border border-green-200 rounded-lg bg-green-50">
                        <div class="text-center text-green-700">
                            <i class="mb-2 text-xl fas fa-check-circle"></i>
                            <p class="text-sm font-medium">¡Perfecto! Ahora puedes agregar subcategorías (opcional)</p>
                            <p class="mt-1 text-xs">Puedes agregar subcategorías o guardar directamente la categoría</p>
                        </div>
                    </div>

                    <!-- Campo para nueva subcategoría -->
                    <div class="flex gap-2 mb-4">
                        <input
                            type="text"
                            wire:model.defer="nuevaSubcategoria"
                            placeholder="Nombre de la nueva subcategoría (opcional)"
                            class="flex-1 px-3 py-2 border rounded"
                        />
                        <button
                            wire:click="agregarSubcategoriaTemporal"
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

                    <!-- Lista temporal de subcategorías -->
                    @if(!empty($subcategoriasTemporales))
                        <div class="mb-4">
                            <h4 class="mb-2 text-sm font-medium text-gray-700">Subcategorías a crear:</h4>
                            <table id="subcategoriaTable" class="w-full text-sm text-left border border-gray-300">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 border">#</th>
                                        <th class="px-3 py-2 border">Nombre</th>
                                        <th class="px-3 py-2 border">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subcategoriasTemporales as $index => $subcategoria)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 border">{{ $index + 1 }}</td>
                                            <td class="px-3 py-2 border">{{ $subcategoria }}</td>
                                            <td class="px-3 py-2 border">
                                                <button
                                                    wire:click="eliminarSubcategoriaTemporal({{ $index }})"
                                                    class="p-0 btn btn-link"
                                                    title="Eliminar"
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
                        </div>
                    @else
                        <p class="mb-4 italic text-gray-600">No hay subcategorías agregadas aún.</p>
                    @endif

                    <!-- Botón guardar para modo agregar -->
                    <div class="flex justify-end">
                        <button
                            wire:click="guardar"
                            class="px-6 py-2 font-medium text-white rounded"
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                            }"
                        >
                            💾 Guardar Categoría
                        </button>
                    </div>
                </div>
            @endif
            @endif
        </div>
    </div>

    <!-- MENSAJE DE ÉXITO -->
    @if ($mostrarMensaje)
    <div
        x-data="{ show: true }"
        x-show="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click.outside="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Inventario.Categoria' })"
        @keydown.window.escape="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Inventario.Categoria' })"
    >
        <div class="w-full max-w-sm p-6 text-center bg-white rounded-lg shadow-lg">
            <h2 class="mb-2 text-lg font-semibold text-green-700">✅ Categoría guardada correctamente</h2>
            <p class="text-sm text-gray-600">Los cambios se han guardado exitosamente.</p>
            <button
                class="px-4 py-2 mt-4 text-sm text-white rounded bg-emerald-600 hover:bg-emerald-700"
                @click="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Inventario.Categoria' })"
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

    {{-- Modal de Detalles de Sincronización de Subcategorías --}}
    @if($detallesSincronizacion)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            {{-- Overlay --}}
            <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="cerrarDetallesSincronizacion"></div>
            
            {{-- Modal --}}
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">📊 Sincronización de Subcategorías Completada</h3>
                    <button wire:click="cerrarDetallesSincronizacion" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-3">
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded">
                        <span class="font-medium text-green-800">✅ Subcategorías procesadas:</span>
                        <span class="font-bold text-green-600">{{ $detallesSincronizacion['subcategorias_sincronizadas'] }}</span>
                    </div>
                    
                    @if($detallesSincronizacion['subcategorias_nuevas'] > 0)
                        <div class="flex justify-between items-center p-3 bg-blue-50 rounded">
                            <span class="font-medium text-blue-800">🆕 Subcategorías nuevas:</span>
                            <span class="font-bold text-blue-600">{{ $detallesSincronizacion['subcategorias_nuevas'] }}</span>
                        </div>
                    @endif
                    
                    @if($detallesSincronizacion['subcategorias_actualizadas'] > 0)
                        <div class="flex justify-between items-center p-3 bg-yellow-50 rounded">
                            <span class="font-medium text-yellow-800">🔄 Subcategorías actualizadas:</span>
                            <span class="font-bold text-yellow-600">{{ $detallesSincronizacion['subcategorias_actualizadas'] }}</span>
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

</div>
