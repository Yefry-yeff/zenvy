<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

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
            <h5 class="mb-0 text-lg">
                @if($isEditing)
                    Editar Producto
                    @if($esProductoValencia)
                        <span class="ml-2 px-2 py-1 text-xs bg-orange-100 text-orange-700 rounded-full">🏢 Valencia</span>
                    @endif
                @else
                    Nuevo Producto
                @endif
            </h5>
            
            <!-- Indicador de sincronización automática -->
            @if($sincronizandoValencia)
            <div class="flex items-center gap-2 px-3 py-2 text-sm text-blue-700 bg-blue-50 rounded-md border border-blue-200">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                <span>🔄 Sincronizando con Valencia...</span>
            </div>
            @endif
            
            <button wire:click="volverALista"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>←</span> Volver
            </button>
        </div>

        {{-- PRODUCTOS DE VALENCIA DESHABILITADOS - Solo para productos Zenvy
        @if(!$isEditing && isset($productosValencia['valencia']) && count($productosValencia['valencia']) > 0)
            <div class="px-5">
                <div class="p-4 bg-white border shadow rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-orange-700">🏢 Productos disponibles desde Valencia</h2>
                        <button
                            wire:click="sincronizarProductosValencia"
                            class="px-4 py-2 text-white bg-orange-500 rounded hover:bg-orange-600"
                        >
                            🔄 Sincronizar Todos
                        </button>
                    </div>
                    
                    <!-- Lista de productos de Valencia -->
                    @if(count($productosValencia['valencia']) > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border border-gray-300">
                                <thead class="bg-orange-100">
                                    <tr>
                                        <th class="px-3 py-2 border">ID</th>
                                        <th class="px-3 py-2 border">Nombre</th>
                                        <th class="px-3 py-2 border">Descripción</th>
                                        <th class="px-3 py-2 border">Precio Base</th>
                                        <th class="px-3 py-2 border">Estado</th>
                                        <th class="px-3 py-2 border">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($productosValencia['valencia'] as $producto)
                                        <tr class="hover:bg-orange-50">
                                            <td class="px-3 py-2 border">{{ $producto->id }}</td>
                                            <td class="px-3 py-2 border">{{ $producto->nombre }}</td>
                                            <td class="px-3 py-2 border">{{ $producto->descripcion ?? 'N/A' }}</td>
                                            <td class="px-3 py-2 border">L. {{ number_format($producto->precio_base, 2) }}</td>
                                            <td class="px-3 py-2 border">
                                                @if($producto->sincronizado)
                                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Sincronizado</span>
                                                @else
                                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">No sincronizado</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 border">
                                                @if(!$producto->sincronizado)
                                                    <button
                                                        wire:click="sincronizarProductoValencia({{ $producto->id }})"
                                                        class="bg-orange-500 text-white px-3 py-1 rounded hover:bg-orange-600 text-xs"
                                                    >
                                                        Sincronizar
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 italic">No hay productos disponibles desde Valencia para sincronizar.</p>
                    @endif
                </div>
            </div>
        @endif
        --}}

        <!-- FORMULARIO -->
        <div class="px-5 py-4">
            <!-- Alerta de validación backend -->
            @if($mostrarAlerta)
                <div class="alert-campo-obligatorio">
                    <strong>⚠️ Campo Obligatorio</strong>
                    <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
                    <br><small>{{ $mensajeAlerta }}</small>
                </div>
            @endif

            <!-- Notificaciones Flash (no intrusivas) -->
            @if(session('info'))
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-800 text-sm"
                     x-data="{ show: true }" 
                     x-show="show" 
                     x-init="setTimeout(() => show = false, 4000)"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="flex items-center justify-between">
                        <span>{{ session('info') }}</span>
                        <button @click="show = false" class="ml-2 text-blue-600 hover:text-blue-800">×</button>
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="guardar">

                <!-- Información Básica -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📝 Información Básica</h2>
                        
                        @if($esProductoValencia)
                            <div class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded">
                                <p class="text-sm text-orange-700">
                                    🏢 <strong>Producto sincronizado desde Valencia</strong> - Los campos principales son de solo lectura. 
                                    Solo puedes modificar precios, descuentos e imagen.
                                </p>
                            </div>
                        @endif
                        
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="codigo_barra" class="form-label">Código de Barras</label>
                                <input type="text"
                                       id="codigo_barra"
                                       class="form-control {{ $this->getClaseCampo('codigo_barra') }}"
                                       wire:model="form.codigo_barra"
                                       onkeydown="if(event.key==='Enter'){event.preventDefault(); return false;}"
                                       autofocus>
                                @error('form.codigo_barra')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="codigo_estatal" class="form-label">Código Estatal</label>
                                @if($esProductoValencia)
                                    <input type="text" 
                                           id="codigo_estatal" 
                                           class="form-control bg-gray-100 text-gray-600" 
                                           value="{{ $form['codigo_estatal'] }}" 
                                           readonly 
                                           style="cursor: not-allowed;" 
                                           title="Campo solo de lectura">
                                @else
                                    <input type="text" id="codigo_estatal" class="form-control" wire:model.defer="form.codigo_estatal">
                                @endif
                                @error('form.codigo_estatal')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="nombre" class="form-label">Nombre <span class="text-red-600">*</span></label>
                                @if($esProductoValencia)
                                    <input type="text" 
                                           id="nombre" 
                                           class="form-control bg-gray-100 text-gray-600" 
                                           value="{{ $form['nombre'] }}" 
                                           readonly 
                                           style="cursor: not-allowed;" 
                                           title="Campo solo de lectura">
                                @else
                                    <input type="text" id="nombre" class="form-control {{ $this->getClaseCampo('nombre') }}" wire:model.live="form.nombre">
                                @endif
                                @error('form.nombre')
                                    <div class="mt-1 text-sm text-danger">❌ El nombre del producto es obligatorio y no puede estar vacío</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="descripcion" class="form-label">Descripción</label>
                                @if($esProductoValencia)
                                    <input type="text" 
                                           id="descripcion" 
                                           class="form-control bg-gray-100 text-gray-600" 
                                           value="{{ $form['descripcion'] }}" 
                                           readonly 
                                           style="cursor: not-allowed;" 
                                           title="Campo solo de lectura">
                                @else
                                    <input type="text" id="descripcion" class="form-control" wire:model.defer="form.descripcion">
                                @endif
                                @error('form.descripcion')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Imagen del Producto -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📷 Imagen del Producto</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="imagen" class="form-label">Seleccionar imagen</label>
                                <input type="file"
                                       id="imagen"
                                       class="form-control"
                                       wire:model="imagen"
                                       accept="image/*">
                                <div wire:loading wire:target="imagen" class="mt-1 text-sm text-info">
                                    📤 Subiendo imagen...
                                </div>
                                @error('imagen')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB</small>
                            </div>
                            <div class="mb-3 col-md-6">
                                <label class="form-label">Vista previa</label>
                                <div class="p-3 text-center border rounded" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                                    @if($this->getImagenMiniatura())
                                        <div class="position-relative">
                                            <img src="{{ $this->getImagenMiniatura() }}"
                                                 alt="Vista previa"
                                                 class="rounded img-fluid"
                                                 style="max-height: 120px; max-width: 100%; object-fit: cover;">
                                            <button type="button"
                                                    wire:click="removerImagen"
                                                    class="top-0 btn btn-danger btn-sm position-absolute end-0 rounded-circle"
                                                    style="width: 25px; height: 25px; font-size: 12px; line-height: 1;"
                                                    title="Remover imagen">×</button>
                                        </div>
                                    @else
                                        <div class="text-muted">
                                            <i class="mb-2 fas fa-image fa-2x"></i><br>
                                            <small>No hay imagen seleccionada</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categorización -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">🏷️ Categorización</h2>
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="marca" class="form-label">Marca <span class="text-red-600">*</span></label>
                                @if($esProductoValencia)
                                    <select id="marca" 
                                            class="form-select bg-gray-100 text-gray-600" 
                                            disabled 
                                            style="cursor: not-allowed;" 
                                            title="Campo solo de lectura">
                                        <option value="{{ $form['marca_id'] }}">{{ collect($marcas)->where('id', $form['marca_id'])->first()['nombre'] ?? 'N/A' }}</option>
                                    </select>
                                @else
                                    <select id="marca" class="form-select {{ $this->getClaseCampo('marca') }}" wire:model.live="form.marca_id">
                                        <option value="">Seleccionar marca</option>
                                        @foreach($marcas as $marca)
                                            <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('form.marca_id')
                                    <div class="mt-1 text-sm text-danger">❌ Debe seleccionar una marca válida</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="categoria" class="form-label">Categoría <span class="text-red-600">*</span></label>
                                @if($esProductoValencia)
                                    <select id="categoria" 
                                            class="form-select bg-gray-100 text-gray-600" 
                                            disabled 
                                            style="cursor: not-allowed;" 
                                            title="Campo solo de lectura">
                                        <option value="{{ $categoriaSeleccionada }}">{{ collect($categorias)->where('id', $categoriaSeleccionada)->first()['nombre'] ?? 'N/A' }}</option>
                                    </select>
                                @else
                                    <select id="categoria" class="form-select {{ $this->getClaseCampo('categoria') }}" wire:model.live="categoriaSeleccionada">
                                        <option value="">Seleccionar categoría</option>
                                        @foreach($categorias as $categoria)
                                            <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('categoriaSeleccionada')
                                    <div class="mt-1 text-sm text-danger">❌ Debe seleccionar una categoría válida</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="subcategoria" class="form-label">Subcategoría <span class="text-red-600">*</span></label>
                                @if($esProductoValencia)
                                    <select id="subcategoria" 
                                            class="form-select bg-gray-100 text-gray-600" 
                                            disabled 
                                            style="cursor: not-allowed;" 
                                            title="Campo solo de lectura">
                                        <option value="{{ $form['subcategoria_id'] }}">{{ collect($subcategorias)->where('id', $form['subcategoria_id'])->first()['nombre'] ?? 'N/A' }}</option>
                                    </select>
                                @else
                                    <select id="subcategoria" class="form-select {{ $this->getClaseCampo('subcategoria') }}" wire:model.live="form.subcategoria_id">
                                        <option value="">Seleccionar subcategoría</option>
                                        @foreach($subcategorias as $subcategoria)
                                            <option value="{{ $subcategoria->id }}">{{ $subcategoria->nombre }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('form.subcategoria_id')
                                    <div class="mt-1 text-sm text-danger">❌ Debe seleccionar una subcategoría válida</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Datos de Venta -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">💰 Datos de Venta</h2>

                        <div class="row">
                            <div class="mb-3 col-md-3">
                                <label for="unidad_medida_venta" class="form-label">Unidad de Medida <span class="text-red-600">*</span></label>
                                @if($esProductoValencia)
                                    <select id="unidad_medida_venta" 
                                            class="form-select bg-gray-100 text-gray-600" 
                                            disabled 
                                            style="cursor: not-allowed;" 
                                            title="Campo solo de lectura">
                                        <option value="{{ $form['unidad_medida_venta_id'] }}">
                                            {{ collect($unidadesMedida)->where('id', $form['unidad_medida_venta_id'])->first()['nombre'] ?? 'N/A' }} 
                                            ({{ collect($unidadesMedida)->where('id', $form['unidad_medida_venta_id'])->first()['simbolo'] ?? '' }})
                                        </option>
                                    </select>
                                @else
                                    <select id="unidad_medida_venta" class="form-select {{ $this->getClaseCampo('unidad_medida') }}" wire:model.defer="form.unidad_medida_venta_id">
                                        <option value="">Seleccionar unidad</option>
                                        @foreach($unidadesMedida as $unidad)
                                            <option value="{{ $unidad->id }}">{{ $unidad->nombre }} ({{ $unidad->simbolo }})</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('form.unidad_medida_venta_id')
                                    <div class="mt-1 text-sm text-danger">❌ Debe seleccionar una unidad de medida</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="precio_base" class="form-label">Precio Base <span class="text-red-600">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">L.</span>
                                    <input type="number" id="precio_base" class="form-control {{ $this->getClaseCampo('precio_base') }}"
                                           wire:model.live="form.precio_base" step="0.01" 
                                           min="{{ $esProductoValencia ? ($form['precio4'] ?? 0) : 0 }}" placeholder="0.00">
                                </div>
                                @error('form.precio_base')
                                    <div class="mt-1 text-sm text-danger">❌ El precio base es obligatorio</div>
                                @enderror
                                @if($esProductoValencia && isset($form['precio4']) && $form['precio4'] > 0)
                                    <small class="text-orange-600">
                                        ⚠️ Para productos de Valencia, el precio base no puede ser menor que el precio4 (L. {{ number_format($form['precio4'], 2) }})
                                    </small>
                                @endif
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="isv_id" class="form-label">Tipo de ISV <span class="text-red-600">*</span></label>
                                @if($esProductoValencia)
                                    <select id="isv_id" 
                                            class="form-select bg-gray-100 text-gray-600" 
                                            disabled 
                                            style="cursor: not-allowed;" 
                                            title="Campo solo de lectura">
                                        <option value="{{ $form['isv_id'] }}">{{ collect($isvs)->where('id', $form['isv_id'])->first()['cantidad'] ?? 'N/A' }}%</option>
                                    </select>
                                @else
                                    <select id="isv_id" class="form-select {{ $this->getClaseCampo('isv_id') }}" wire:model.defer="form.isv_id">
                                        <option value="">Seleccionar ISV</option>
                                        @foreach($isvs as $isv)
                                            <option value="{{ $isv->id }}">{{ $isv->cantidad }}%</option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('form.isv_id')
                                    <div class="mt-1 text-sm text-danger">❌ Debe seleccionar un tipo de ISV</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="descuento_unitario" class="form-label">Descuento Unitario</label>
                                <div class="input-group">
                                    <input type="number" id="descuento_unitario" class="form-control"
                                           wire:model.defer="form.descuento_unitario" step="0.01" min="0" placeholder="0.00">
                                    <span class="input-group-text">L.</span>
                                </div>
                                @error('form.descuento_unitario')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Descuentos Especiales -->
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label class="form-label">Descuentos Especiales</label>
                                <div class="p-3 border rounded">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="descuento_tercera" wire:model.defer="form.descuento_tercera">
                                        <label class="form-check-label" for="descuento_tercera">
                                            Aplica descuento de tercera edad
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="descuento_cuarta" wire:model.defer="form.descuento_cuarta">
                                        <label class="form-check-label" for="descuento_cuarta">
                                            Aplica descuento de cuarta edad
                                        </label>
                                    </div>
                                </div>
                                @error('form.descuento_tercera')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                                @error('form.descuento_cuarta')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                                                <!-- Precios de Venta Valencia (Solo cuando producto_valencia = 1) -->
                        @if(isset($form['producto_valencia']) && $form['producto_valencia'] == 1)
                        <div class="row">
                            <div class="col-md-12">
                                <div class="p-3 mb-3 border rounded bg-light">
                                    <div class="row">
                                        <div class="mb-3 col-md-3">
                                            <label for="precio1" class="form-label">Precio A</label>
                                            <div class="input-group">
                                                <span class="input-group-text">L.</span>
                                                <input type="number"
                                                       id="precio1"
                                                       class="form-control"
                                                       wire:model.defer="form.precio1"
                                                       step="0.01"
                                                       min="0"
                                                       placeholder="0.00"
                                                       readonly>
                                            </div>
                                            @error('form.precio1')
                                                <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3 col-md-3">
                                            <label for="precio2" class="form-label">Precio B</label>
                                            <div class="input-group">
                                                <span class="input-group-text">L.</span>
                                                <input type="number"
                                                       id="precio2"
                                                       class="form-control"
                                                       wire:model.defer="form.precio2"
                                                       step="0.01"
                                                       min="0"
                                                       placeholder="0.00"
                                                       readonly>
                                            </div>
                                            @error('form.precio2')
                                                <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3 col-md-3">
                                            <label for="precio3" class="form-label">Precio C</label>
                                            <div class="input-group">
                                                <span class="input-group-text">L.</span>
                                                <input type="number"
                                                       id="precio3"
                                                       class="form-control"
                                                       wire:model.defer="form.precio3"
                                                       step="0.01"
                                                       min="0"
                                                       placeholder="0.00"
                                                       readonly>
                                            </div>
                                            @error('form.precio3')
                                                <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3 col-md-3">
                                            <label for="precio4" class="form-label">Precio D</label>
                                            <div class="input-group">
                                                <span class="input-group-text">L.</span>
                                                <input type="number"
                                                       id="precio4"
                                                       class="form-control"
                                                       wire:model.defer="form.precio4"
                                                       step="0.01"
                                                       min="0"
                                                       placeholder="0.00"
                                                       readonly>
                                            </div>
                                            @error('form.precio4')
                                                <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Estos precios son específicos para productos Valencia y son de solo lectura.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        </div>
                    </div>
                </div>

                <!-- Proyecciones -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📊 Proyecciones</h2>

                        <div class="row">
                            <div class="mb-3 col-md-3">
                                <label for="ultimo_costo_compra" class="form-label">Último Costo de Compra</label>
                                @if($esProductoValencia)
                                    <div class="input-group">
                                        <span class="input-group-text">L.</span>
                                        <input type="number" 
                                               id="ultimo_costo_compra" 
                                               class="form-control bg-gray-100 text-gray-600" 
                                               value="{{ number_format($form['ultimo_costo_compra'] ?? 0, 2) }}" 
                                               readonly 
                                               style="cursor: not-allowed;" 
                                               title="Campo solo de lectura">
                                    </div>
                                @else
                                    <div class="input-group">
                                        <span class="input-group-text">L.</span>
                                        <input type="number" id="ultimo_costo_compra" class="form-control"
                                               wire:model.live="form.ultimo_costo_compra" step="0.01" min="0" placeholder="0.00">
                                    </div>
                                @endif
                                @error('form.ultimo_costo_compra')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="costo_promedio" class="form-label">Costo Promedio</label>
                                @if($esProductoValencia)
                                    <div class="input-group">
                                        <span class="input-group-text">L.</span>
                                        <input type="number" 
                                               id="costo_promedio" 
                                               class="form-control bg-gray-100 text-gray-600" 
                                               value="{{ number_format($form['costo_promedio'] ?? 0, 2) }}" 
                                               readonly 
                                               style="cursor: not-allowed;" 
                                               title="Campo solo de lectura">
                                    </div>
                                @else
                                    <div class="input-group">
                                        <span class="input-group-text">L.</span>
                                        <input type="number" id="costo_promedio" class="form-control"
                                               wire:model.defer="form.costo_promedio" step="0.01" min="0" placeholder="0.00">
                                    </div>
                                @endif
                                @error('form.costo_promedio')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-3">
                                <label for="margen_ganancia" class="form-label">Margen de Ganancia</label>
                                <div class="input-group">
                                    <input type="text" id="margen_ganancia" class="form-control bg-light" readonly
                                           value="{{ $this->calcularMargenGanancia() }}%">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Calculado automáticamente</small>
                            </div>
                        </div>

                        <!-- Resumen de Costos y Precios -->
                        @if(isset($form['ultimo_costo_compra']) && $form['ultimo_costo_compra'] > 0 && isset($form['precio_base']) && $form['precio_base'] > 0)
                        <div class="p-3 mt-4 rounded bg-light">
                            <h6 class="mb-3 text-success"><i class="fas fa-chart-line me-2"></i>Resumen de Análisis Financiero:</h6>
                            <div class="text-center row">
                                <div class="col-md-3">
                                    <div class="p-3 text-white rounded bg-info">
                                        <strong>L. {{ number_format($form['ultimo_costo_compra'], 2) }}</strong><br>
                                        <small>Último Costo</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 text-white rounded bg-success">
                                        <strong>L. {{ number_format($form['precio_base'], 2) }}</strong><br>
                                        <small>Precio Base</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 text-white rounded bg-warning">
                                        <strong>L. {{ number_format($form['precio_base'] - $form['ultimo_costo_compra'], 2) }}</strong><br>
                                        <small>Ganancia</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="p-3 text-white rounded bg-primary">
                                        <strong>{{ $this->calcularMargenGanancia() }}%</strong><br>
                                        <small>Margen</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" wire:click="volverALista"
                        class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-white rounded"
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                            'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                            'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                            'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }">
                        @if($isEditing)
                            Actualizar Producto
                        @else
                            Crear Producto
                        @endif
                    </button>
                </div>

            </form>
        </div>

    </div>

    <!-- Alerta de validación flotante -->
    @if($mostrarAlerta)
        <div class="alert-campo-obligatorio">
            <strong>⚠️ Campo Obligatorio</strong>
            <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
            <br><small>{{ $mensajeAlerta }}</small>
        </div>
    @endif

    <!-- Estilos CSS para validación -->
    <style>
        /* Campo con error - solo rojos */
        .is-invalid, .campo-obligatorio-vacio {
            border: 2px solid #dc3545 !important;
            background-color: #fff5f5 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Mensaje de error personalizado */
        .text-danger {
            color: #dc3545 !important;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Alerta flotante personalizada */
        .alert-campo-obligatorio {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border-left: 4px solid #dc3545;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Estilo para labels de campos obligatorios */
        .text-red-600 {
            color: #dc3545 !important;
            font-weight: bold;
        }

        /* Ocultar elementos antes de que Alpine.js los maneje */
        [x-cloak] {
            display: none !important;
        }

        /* Estilos adicionales para modales */
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
        }

        .modal-header {
            border-bottom: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }

        .modal-body {
            padding: 1rem 1.5rem;
        }

        .modal-footer {
            border-top: none;
            padding: 0.5rem 1.5rem 1.5rem;
        }

        /* Animación para iconos en modales */
        .modal-body i {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>

    <!-- Modal de Éxito con Alpine.js -->
    <div x-data="{ open: @entangle('mostrarModalExito') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalExito()"
         @keydown.escape.window="$wire.cerrarModalExito()">

        <div class="w-full max-w-md mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="p-4 text-white bg-green-600">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">¡Operación Exitosa!</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-lg font-medium text-gray-900">{{ $mensajeModalExito }}</h4>
                    <p class="text-gray-600">El producto se ha procesado correctamente en el sistema.</p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 text-center bg-gray-50">
                    <button wire:click="cerrarModalExito"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-green-600 rounded-md hover:bg-green-700">
                        <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Error con Alpine.js -->
    <div x-data="{ open: @entangle('mostrarModalError') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalError()"
         @keydown.escape.window="$wire.cerrarModalError()">

        <div class="w-full max-w-md mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="p-4 text-white bg-red-600">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="text-lg font-semibold">Error en la Operación</h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h4 class="mb-2 text-lg font-medium text-gray-900">{{ $mensajeModalError }}</h4>
                    <p class="text-gray-600">Por favor, revise los datos e intente nuevamente. Si el problema persiste, contacte al administrador.</p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 text-center bg-gray-50">
                    <button wire:click="cerrarModalError"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-red-600 rounded-md hover:bg-red-700">
                        <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para manejo de modal -->
    <script>
        document.addEventListener('livewire:init', () => {
            // Solo logging para debug, la redirección se maneja al cerrar el modal
            Livewire.on('redirigirEnTresSeg', () => {
                console.log('Producto guardado exitosamente');
            });
            
            // Escuchar evento de sincronización automática
            Livewire.on('mostrarSincronizacion', () => {
                console.log('Sincronizando producto de Valencia automáticamente...');
            });
        });

        // Auto-focus en el campo de código de barras al cargar el componente
        // Optimizado para evitar múltiples setTimeout
        let focusAttempted = false;
        const focusCodigoBarra = () => {
            if (!focusAttempted) {
                focusAttempted = true;
                const codigoBarraField = document.getElementById('codigo_barra');
                if (codigoBarraField && document.activeElement !== codigoBarraField) {
                    codigoBarraField.focus();
                }
                // Reset flag después de un tiempo para permitir re-focus si es necesario
                setTimeout(() => { focusAttempted = false; }, 1000);
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Usar requestAnimationFrame en lugar de setTimeout cuando sea posible
            requestAnimationFrame(() => {
                setTimeout(focusCodigoBarra, 50); // Reducido de 100ms a 50ms
            });
        });

        // También enfocar cuando Livewire termina de cargar
        document.addEventListener('livewire:navigated', () => {
            requestAnimationFrame(() => {
                setTimeout(focusCodigoBarra, 50); // Reducido de 100ms a 50ms
            });
        });
    </script>

</div> {{-- FIN ELEMENTO RAÍZ --}}
