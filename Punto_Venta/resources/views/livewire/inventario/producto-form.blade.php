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
                @else
                    Nuevo Producto
                @endif
            </h5>
            <button wire:click="volverALista"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>←</span> Volver
            </button>
        </div>

        <!-- FORMULARIO -->
        <div class="px-5 py-4">
            <!-- Alerta de validación backend -->
            @if($mostrarAlerta)
                <div class="mb-4 alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>⚠️ Campo requerido:</strong> {{ $mensajeAlerta }}
                    <button type="button" class="btn-close" wire:click="cerrarAlerta" aria-label="Close"></button>
                </div>
            @endif

            <form wire:submit.prevent="guardar">
                
                <!-- Información Básica -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📝 Información Básica</h2>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre <span class="text-red-600">*</span></label>
                                <input type="text" id="nombre" class="form-control {{ $this->getClaseCampo('nombre') }}" wire:model.live="form.nombre">
                                @error('form.nombre')
                                    <div class="text-danger mt-1 text-sm">❌ El nombre del producto es obligatorio y no puede estar vacío</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <input type="text" id="descripcion" class="form-control" wire:model.defer="form.descripcion">
                                @error('form.descripcion')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="codigo_barra" class="form-label">Código de Barras</label>
                                <input type="text" id="codigo_barra" class="form-control" wire:model.defer="form.codigo_barra">
                                @error('form.codigo_barra')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="codigo_estatal" class="form-label">Código Estatal</label>
                                <input type="text" id="codigo_estatal" class="form-control" wire:model.defer="form.codigo_estatal">
                                @error('form.codigo_estatal')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Categorización -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">🏷️ Categorización</h2>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="marca" class="form-label">Marca <span class="text-red-600">*</span></label>
                                <select id="marca" class="form-select {{ $this->getClaseCampo('marca') }}" wire:model.live="form.marca_id">
                                    <option value="">Seleccionar marca</option>
                                    @foreach($marcas as $marca)
                                        <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.marca_id')
                                    <div class="text-danger mt-1 text-sm">❌ Debe seleccionar una marca válida</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="categoria" class="form-label">Categoría <span class="text-red-600">*</span></label>
                                <select id="categoria" class="form-select {{ $this->getClaseCampo('categoria') }}" wire:model.live="categoriaSeleccionada">
                                    <option value="">Seleccionar categoría</option>
                                    @foreach($categorias as $categoria)
                                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('categoriaSeleccionada')
                                    <div class="text-danger mt-1 text-sm">❌ Debe seleccionar una categoría válida</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="subcategoria" class="form-label">Subcategoría <span class="text-red-600">*</span></label>
                                <select id="subcategoria" class="form-select {{ $this->getClaseCampo('subcategoria') }}" wire:model.live="form.subcategoria_id">
                                    <option value="">Seleccionar subcategoría</option>
                                    @foreach($subcategorias as $subcategoria)
                                        <option value="{{ $subcategoria->id }}">{{ $subcategoria->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('form.subcategoria_id')
                                    <div class="text-danger mt-1 text-sm">❌ Debe seleccionar una subcategoría válida</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Precios y Costos -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">💰 Precios y Costos</h2>
                        
                        <!-- Fila 1: Precios Base y Costos -->
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="precio_base" class="form-label">Precio Base <span class="text-red-600">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="precio_base" class="form-control {{ $this->getClaseCampo('precio_base') }}" wire:model.live="form.precio_base" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.precio_base')
                                    <div class="text-danger mt-1 text-sm">❌ El precio base es obligatorio y debe ser mayor a 0</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="ultimo_costo_compra" class="form-label">Último Costo Compra</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="ultimo_costo_compra" class="form-control" wire:model.defer="form.ultimo_costo_compra" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.ultimo_costo_compra')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="costo_promedio" class="form-label">Costo Promedio</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="costo_promedio" class="form-control" wire:model.defer="form.costo_promedio" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.costo_promedio')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="isv" class="form-label">ISV (Impuesto) %</label>
                                <div class="input-group">
                                    <input type="number" 
                                           id="isv" 
                                           class="form-control" 
                                           wire:model.defer="form.isv" 
                                           step="0.01" 
                                           min="0" 
                                           max="100" 
                                           placeholder="15">
                                    <span class="input-group-text bg-light">%</span>
                                </div>
                                @error('form.isv')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Fila 2: Unidades de Medida -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="unidad_compra" class="form-label">
                                    Unidad de Compra <span class="text-red-600">*</span>
                                    <small class="text-muted">(Solo números enteros)</small>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-weight-hanging"></i></span>
                                    <input type="number" 
                                           id="unidad_compra" 
                                           class="form-control {{ $this->getClaseCampo('unidad_compra') }}" 
                                           wire:model.live="form.unidad_compra" 
                                           step="1" 
                                           min="1" 
                                           placeholder="1"
                                           onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                                @error('form.unidad_compra')
                                    <div class="text-danger mt-1 text-sm">❌ La unidad de compra debe ser un número entero mayor a 0</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unidad_medida" class="form-label">Unidad de Medida <span class="text-red-600">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fas fa-ruler"></i></span>
                                    <select id="unidad_medida" class="form-select {{ $this->getClaseCampo('unidad_medida') }}" wire:model.live="form.unidad_medida_compra_id">
                                        <option value="">Seleccionar unidad de medida</option>
                                        @foreach($unidadesMedida as $unidad)
                                            <option value="{{ $unidad->id }}">{{ $unidad->nombre }} ({{ $unidad->simbolo }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('form.unidad_medida_compra_id')
                                    <div class="text-danger mt-1 text-sm">❌ Debe seleccionar una unidad de medida válida</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Precios de Venta -->
                <div class="p-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">💵 Precios de Venta</h2>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="precio1" class="form-label">Precio 1 <span class="text-red-600">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="precio1" class="form-control {{ $this->getClaseCampo('precio1') }}" wire:model.live="form.precio1" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.precio1')
                                    <div class="text-danger mt-1 text-sm">❌ El precio 1 es obligatorio y debe ser mayor a 0</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="precio2" class="form-label">Precio 2</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="precio2" class="form-control" wire:model.defer="form.precio2" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.precio2')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="precio3" class="form-label">Precio 3</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="precio3" class="form-control" wire:model.defer="form.precio3" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.precio3')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="precio4" class="form-label">Precio 4</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">L.</span>
                                    <input type="number" id="precio4" class="form-control" wire:model.defer="form.precio4" step="0.01" min="0" placeholder="0.00">
                                </div>
                                @error('form.precio4')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
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

    <!-- Mensajes de sesión -->
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
    </style>

</div> {{-- FIN ELEMENTO RAÍZ --}}
