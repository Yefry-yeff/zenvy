<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Sección de Subcategorías Propias de Zenvy --}}
    <div class="overflow-hidden border border-gray-300 rounded shadow mb-6" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO SUBCATEGORÍAS ZENVY -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t"
            :class="{
                'bg-emerald-600': theme === 'verde',
                'bg-blue-600': theme === 'azul',
                'bg-gray-900': theme === 'oscuro',
                'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
            }"
        >
            <h5 class="mb-0 text-lg">📋 Subcategorías Propias de Zenvy</h5>
            <button wire:click="abrirModalCrear"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Agregar Subcategoría
            </button>
        </div>

        <!-- TABLA SUBCATEGORÍAS ZENVY -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="subcategoriasZenvyTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th style="width: 80px;">ID</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th style="width: 60px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subcategoriasZenvy as $subcategoria)
                            <tr class="text-center align-middle hover:bg-gray-50">
                                <td class="cursor-pointer fw-semibold" wire:click="editar({{ $subcategoria->id }})">{{ $subcategoria->id }}</td>
                                <td class="cursor-pointer text-start" wire:click="editar({{ $subcategoria->id }})">{{ $subcategoria->nombre }}</td>
                                <td class="text-start">{{ $subcategoria->categoria->nombre ?? 'Sin categoría' }}</td>
                                <td>
                                    <button type="button" class="p-0 btn btn-link" wire:click="confirmarEliminar({{ $subcategoria->id }})" title="Eliminar" onclick="event.stopPropagation();">
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
                                <td colspan="4" class="py-4 text-center text-muted">No hay subcategorías propias de Zenvy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Sección de Subcategorías de Valencia --}}
    <div class="overflow-hidden border border-orange-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO SUBCATEGORÍAS VALENCIA -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t bg-orange-600">
            <h5 class="mb-0 text-lg">🏢 Subcategorías de Valencia (Solo Lectura)</h5>
            <button wire:click="sincronizarSubcategoriasValencia"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>🔄</span> Sincronizar
            </button>
        </div>

        <!-- TABLA SUBCATEGORÍAS VALENCIA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="subcategoriasValenciaTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th style="width: 80px;">ID</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th style="width: 120px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subcategoriasValencia as $subcategoria)
                            <tr class="text-center align-middle bg-orange-50">
                                <td class="fw-semibold">{{ $subcategoria->id }}</td>
                                <td class="text-start">{{ $subcategoria->nombre }}</td>
                                <td class="text-start">{{ $subcategoria->categoria->nombre ?? 'Sin categoría' }}</td>
                                <td>
                                    <span class="badge bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs">
                                        🔒 Sincronizada
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-muted">No hay subcategorías sincronizadas desde Valencia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Modal Crear Subcategoría --}}
    @if($modalCrearAbierto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" wire:click="cerrarModalCrear"></div>
                
                <div class="inline-block w-full max-w-lg px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">
                                Crear Nueva Subcategoría
                            </h3>
                            
                            <div class="mt-4">
                                <label for="nuevaSubcategoriaNombre" class="block text-sm font-medium text-gray-700">Nombre de la Subcategoría</label>
                                <input type="text" 
                                       wire:model.defer="nuevaSubcategoriaNombre" 
                                       id="nuevaSubcategoriaNombre"
                                       class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('nuevaSubcategoriaNombre')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-4">
                                <label for="categoriaSeleccionada" class="block text-sm font-medium text-gray-700">Categoría</label>
                                <select wire:model.defer="categoriaSeleccionada" 
                                        id="categoriaSeleccionada"
                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Seleccionar categoría...</option>
                                    @foreach($categorias as $categoria)
                                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('categoriaSeleccionada')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" 
                                wire:click="crearSubcategoria"
                                class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Crear
                        </button>
                        <button type="button" 
                                wire:click="cerrarModalCrear"
                                class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Eliminar Subcategoría --}}
    @if($modalEliminarAbierto)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" wire:click="cerrarModalEliminar"></div>
                
                <div class="inline-block w-full max-w-lg px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="w-full mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">
                                Eliminar Subcategoría
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    ¿Está seguro de que desea eliminar esta subcategoría? Esta acción no se puede deshacer.
                                </p>
                                @if(count($productosVinculados) > 0)
                                    <div class="p-4 mt-4 bg-yellow-50 border-l-4 border-yellow-400">
                                        <p class="text-sm text-yellow-700">
                                            <strong>Advertencia:</strong> Esta subcategoría tiene productos vinculados:
                                        </p>
                                        <ul class="mt-2 text-sm text-yellow-600">
                                            @foreach(array_slice($productosVinculados, 0, 5) as $producto)
                                                <li>• {{ $producto }}</li>
                                            @endforeach
                                            @if(count($productosVinculados) > 5)
                                                <li>• ... y {{ count($productosVinculados) - 5 }} más</li>
                                            @endif
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" 
                                wire:click="eliminarSubcategoria"
                                class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-red-600 border border-transparent rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Eliminar
                        </button>
                        <button type="button" 
                                wire:click="cerrarModalEliminar"
                                class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Mensajes Flash --}}
    @if (session()->has('mensaje'))
        <div class="fixed top-4 right-4 z-50 p-4 bg-green-500 text-white rounded shadow-lg">
            {{ session('mensaje') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed top-4 right-4 z-50 p-4 bg-red-500 text-white rounded shadow-lg">
            {{ session('error') }}
        </div>
    @endif

</div>