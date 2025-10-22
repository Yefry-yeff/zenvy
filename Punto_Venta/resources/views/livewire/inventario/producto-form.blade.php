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
                                    Puedes modificar: precios, descuentos, imagen, unidad de medida y tipo de ISV.
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
                                        <option value="{{ $form['marca_id'] }}">{{ collect($marcas)->where('id', $form['marca_id'])->first()->nombre ?? 'N/A' }}</option>
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
                                        <option value="{{ $categoriaSeleccionada }}">{{ collect($categorias)->where('id', $categoriaSeleccionada)->first()->nombre ?? 'N/A' }}</option>
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
                                        <option value="{{ $form['subcategoria_id'] }}">{{ collect($subcategorias)->where('id', $form['subcategoria_id'])->first()->nombre ?? 'N/A' }}</option>
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

                        <!-- Primera fila: ISV, Descuento Unitario y Descuentos Especiales -->
                        <div class="row">
                            <div class="mb-3 col-md-3">
                                <label for="isv_id" class="form-label">Tipo de ISV <span class="text-red-600">*</span></label>
                                <select id="isv_id" class="form-select {{ $this->getClaseCampo('isv_id') }}" wire:model.defer="form.isv_id">
                                    <option value="">Seleccionar ISV</option>
                                    @foreach($isvs as $isv)
                                        <option value="{{ $isv->id }}">{{ $isv->cantidad }}%</option>
                                    @endforeach
                                </select>
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
                            <div class="mb-3 col-md-6">
                                <label class="form-label">Descuentos Especiales</label>
                                <div class="p-2 border rounded">
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
                        </div>

                        <!-- Precios de Venta por Unidad de Medida -->
                        <div class="mt-4">
                            <h6 class="mb-3 text-gray-700 border-bottom pb-2">
                                <i class="fas fa-tags me-2"></i>Precios de Venta por Unidad
                            </h6>

                            <!-- Formulario para agregar nuevo precio -->
                            <div class="p-3 mb-3 border rounded bg-light">
                                <h6 class="mb-3 text-gray-700"><i class="fas fa-plus-circle me-2"></i>Agregar Precio</h6>
                                <div class="row align-items-end">
                                    <div class="mb-3 col-md-3">
                                        <label for="nueva_unidad_medida" class="form-label">Unidad de Medida <span class="text-red-600">*</span></label>
                                        <select id="nueva_unidad_medida" 
                                                class="form-select" 
                                                wire:model="nuevoPrecioVenta.unidad_medida_id">
                                            <option value="">Seleccionar unidad</option>
                                            @foreach($unidadesMedida as $unidad)
                                                <option value="{{ $unidad->id }}">{{ $unidad->nombre }} ({{ $unidad->simbolo }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3 col-md-2">
                                        <label for="nueva_cantidad" class="form-label">Cantidad <span class="text-red-600">*</span></label>
                                        <input type="number" 
                                               id="nueva_cantidad" 
                                               class="form-control" 
                                               wire:model="nuevoPrecioVenta.cantidad"
                                               min="1" 
                                               step="1"
                                               placeholder="1">
                                    </div>
                                    <div class="mb-3 col-md-3">
                                        <label for="nuevo_precio" class="form-label">Precio <span class="text-red-600">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">L.</span>
                                            <input type="number" 
                                                   id="nuevo_precio" 
                                                   class="form-control" 
                                                   wire:model="nuevoPrecioVenta.precio"
                                                   step="0.01" 
                                                   min="0" 
                                                   placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <button type="button" 
                                                wire:click="agregarPrecioVenta" 
                                                class="w-100 btn"
                                                :class="{
                                                    'btn-success': theme === 'verde',
                                                    'btn-primary': theme === 'azul',
                                                    'btn-dark': theme === 'oscuro',
                                                    'btn-secondary': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                                                }">
                                            <i class="fas fa-plus me-1"></i> Agregar Precio
                                        </button>
                                    </div>
                                </div>
                                @if(session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        {{ session('error') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif
                                @if(session('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session('success') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif
                            </div>

                            <!-- Tabla de precios existentes -->
                            @if(count($preciosVenta) > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th>Unidad de Medida</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">Precio Total</th>
                                            <th class="text-end">Precio Unitario</th>
                                            <th class="text-center" style="width: 80px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($preciosVenta as $index => $precio)
                                            @php
                                                $unidad = collect($unidadesMedida)->firstWhere('id', $precio['unidad_medida_id']);
                                                $precioUnitario = $precio['cantidad'] > 0 ? $precio['precio'] / $precio['cantidad'] : 0;
                                            @endphp
                                            <tr style="cursor: pointer;">
                                                <td class="text-center" wire:click="abrirModalEditarPrecio({{ $index }})">{{ $index + 1 }}</td>
                                                <td wire:click="abrirModalEditarPrecio({{ $index }})">
                                                    <span class="badge bg-info">
                                                        {{ $unidad->nombre ?? 'N/A' }} ({{ $unidad->simbolo ?? '' }})
                                                    </span>
                                                </td>
                                                <td class="text-center" wire:click="abrirModalEditarPrecio({{ $index }})">
                                                    <strong>{{ $precio['cantidad'] }}</strong> 
                                                    {{ $precio['cantidad'] > 1 ? 'unidades' : 'unidad' }}
                                                </td>
                                                <td class="text-end" wire:click="abrirModalEditarPrecio({{ $index }})">
                                                    <span class="text-success fw-bold">L. {{ number_format($precio['precio'], 2) }}</span>
                                                </td>
                                                <td class="text-end" wire:click="abrirModalEditarPrecio({{ $index }})">
                                                    <small class="text-muted">L. {{ number_format($precioUnitario, 2) }} c/u</small>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" 
                                                            wire:click="abrirModalEliminarPrecio({{ $index }})" 
                                                            class="btn btn-danger btn-sm"
                                                            title="Eliminar precio">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="p-4 text-center text-gray-500 border rounded bg-light">
                                <i class="mb-2 fas fa-box-open fa-2x"></i>
                                <p class="mb-0">No hay precios de venta configurados. Agrega el primer precio usando el formulario anterior.</p>
                            </div>
                            @endif
                        </div>

                        <!-- Precios de Venta Valencia (Solo cuando producto_valencia = 1) -->
                        @if(isset($form['producto_valencia']) && $form['producto_valencia'] == 1)
                        <div class="mt-4">
                            <h3 class="mb-3 text-md font-semibold text-gray-700">💰 Precios Valencia</h3>
                            <div class="p-3 border rounded bg-light">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Precio A</label>
                                        <div class="input-group">
                                            <span class="input-group-text">L.</span>
                                            <input type="number" class="form-control bg-gray-100" wire:model.defer="form.precio1" step="0.01" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Precio B</label>
                                        <div class="input-group">
                                            <span class="input-group-text">L.</span>
                                            <input type="number" class="form-control bg-gray-100" wire:model.defer="form.precio2" step="0.01" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Precio C</label>
                                        <div class="input-group">
                                            <span class="input-group-text">L.</span>
                                            <input type="number" class="form-control bg-gray-100" wire:model.defer="form.precio3" step="0.01" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Precio D</label>
                                        <div class="input-group">
                                            <span class="input-group-text">L.</span>
                                            <input type="number" class="form-control bg-gray-100" wire:model.defer="form.precio4" step="0.01" readonly>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Precios específicos de Valencia (solo lectura)
                                </small>
                            </div>
                        </div>
                        @endif
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

    <!-- Modal para editar precio -->
    <div x-data="{ open: @entangle('mostrarModalEditarPrecio') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
         @click.self="$wire.cerrarModalEditarPrecio()"
         @keydown.escape.window="$wire.cerrarModalEditarPrecio()">

        <div class="w-full max-w-lg mx-4">
            <div class="overflow-hidden bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="p-4 text-white"
                     :class="{
                         'bg-emerald-600': theme === 'verde',
                         'bg-blue-600': theme === 'azul',
                         'bg-gray-900': theme === 'oscuro',
                         'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                     }">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-edit mr-2"></i>
                            <h3 class="text-lg font-semibold">Editar Precio de Venta</h3>
                        </div>
                        <button wire:click="cerrarModalEditarPrecio" class="text-white hover:text-gray-200">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    <div class="mb-4">
                        <label for="editar_unidad_medida" class="form-label fw-bold">
                            <i class="fas fa-ruler me-1"></i>Unidad de Medida <span class="text-red-600">*</span>
                        </label>
                        <select id="editar_unidad_medida" 
                                class="form-select" 
                                wire:model="precioEditando.unidad_medida_id">
                            <option value="">Seleccionar unidad</option>
                            @foreach($unidadesMedida as $unidad)
                                <option value="{{ $unidad->id }}">{{ $unidad->nombre }} ({{ $unidad->simbolo }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="editar_cantidad" class="form-label fw-bold">
                            <i class="fas fa-sort-numeric-up me-1"></i>Cantidad <span class="text-red-600">*</span>
                        </label>
                        <input type="number" 
                               id="editar_cantidad" 
                               class="form-control form-control-lg" 
                               wire:model="precioEditando.cantidad"
                               min="1" 
                               step="1"
                               placeholder="Ej: 1, 12, 24">
                        <small class="text-muted">Número de unidades por esta presentación</small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="editar_precio" class="form-label fw-bold">
                            <i class="fas fa-dollar-sign me-1"></i>Precio Total <span class="text-red-600">*</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">L.</span>
                            <input type="number" 
                                   id="editar_precio" 
                                   class="form-control" 
                                   wire:model="precioEditando.precio"
                                   step="0.01" 
                                   min="0" 
                                   placeholder="0.00">
                        </div>
                        @if(isset($precioEditando['cantidad']) && $precioEditando['cantidad'] > 0 && isset($precioEditando['precio']) && $precioEditando['precio'] > 0)
                            <small class="text-success fw-bold">
                                <i class="fas fa-calculator me-1"></i>
                                Precio unitario: L. {{ number_format($precioEditando['precio'] / $precioEditando['cantidad'], 2) }} c/u
                            </small>
                        @endif
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 bg-gray-50 text-end">
                    <button wire:click="cerrarModalEditarPrecio"
                            class="btn btn-secondary me-2">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button wire:click="guardarEdicionPrecio"
                            class="btn"
                            :class="{
                                'btn-success': theme === 'verde',
                                'btn-primary': theme === 'azul',
                                'btn-dark': theme === 'oscuro',
                                'btn-secondary': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                            }">
                        <i class="fas fa-save me-1"></i>Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para eliminar precio -->
    <div wire:key="modal-eliminar-precio-{{ $precioAEliminar ?? 'none' }}">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($modalEliminarPrecioAbierto) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1050;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cerrarModalEliminarPrecio()"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="text-white bg-red-600 modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-trash me-2"></i>Eliminar Precio de Venta
                        </h5>
                        <button type="button" 
                                class="btn-close btn-close-white" 
                                wire:click="cerrarModalEliminarPrecio"
                                aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($precioAEliminar !== null && isset($preciosVenta[$precioAEliminar]))
                            @php
                                $precioEliminar = $preciosVenta[$precioAEliminar];
                                $unidadEliminar = collect($unidadesMedida)->firstWhere('id', $precioEliminar['unidad_medida_id']);
                                $precioUnitarioEliminar = $precioEliminar['cantidad'] > 0 ? $precioEliminar['precio'] / $precioEliminar['cantidad'] : 0;
                            @endphp
                            
                            <div class="alert alert-warning">
                                <h6><strong>⚠️ Confirmación de eliminación</strong></h6>
                                <p class="mb-0">¿Está seguro que desea eliminar este precio de venta?</p>
                            </div>

                            <div class="p-3 border rounded bg-light">
                                <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Detalles del precio:</h6>
                                <table class="table table-sm table-borderless mb-0">
                                    <tr>
                                        <td class="fw-bold" style="width: 150px;">Unidad de Medida:</td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ $unidadEliminar->nombre ?? 'N/A' }} ({{ $unidadEliminar->simbolo ?? '' }})
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Cantidad:</td>
                                        <td><strong>{{ $precioEliminar['cantidad'] }}</strong> {{ $precioEliminar['cantidad'] > 1 ? 'unidades' : 'unidad' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Precio Total:</td>
                                        <td><span class="text-success fw-bold">L. {{ number_format($precioEliminar['precio'], 2) }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Precio Unitario:</td>
                                        <td><span class="text-muted">L. {{ number_format($precioUnitarioEliminar, 2) }} c/u</span></td>
                                    </tr>
                                </table>
                            </div>

                            <div class="mt-3 alert alert-danger">
                                <small>
                                    <strong><i class="fas fa-exclamation-triangle me-1"></i>Advertencia:</strong>
                                    @if(isset($precioEliminar['id']))
                                        Este precio será marcado como <strong>inactivo</strong> en la base de datos.
                                    @else
                                        Este precio será eliminado permanentemente (aún no se ha guardado en la base de datos).
                                    @endif
                                    Esta acción no se puede deshacer.
                                </small>
                            </div>

                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" class="btn btn-secondary" wire:click="cerrarModalEliminarPrecio">
                                    <i class="fas fa-times me-1"></i> No, cancelar
                                </button>
                                <button type="button" class="btn btn-danger" wire:click="confirmarEliminarPrecio">
                                    <i class="fas fa-trash me-1"></i> Sí, eliminar
                                </button>
                            </div>
                        @else
                            <div class="alert alert-danger">
                                <p class="mb-0">No se pudo identificar el precio a eliminar.</p>
                            </div>
                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" class="btn btn-secondary" wire:click="cerrarModalEliminarPrecio">
                                    <i class="fas fa-times me-1"></i> Cerrar
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

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
        
        /* Filas clicables en tabla de precios */
        .table tbody tr[style*="cursor: pointer"] td:not(:last-child):hover {
            background-color: #f0f8ff !important;
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr[style*="cursor: pointer"]:hover td:not(:last-child) {
            background-color: #e3f2fd !important;
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
