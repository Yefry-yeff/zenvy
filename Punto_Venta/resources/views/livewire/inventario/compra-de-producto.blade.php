<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    <!-- Mensajes de error -->
    @if (session()->has('error'))
        <div class="mb-4 px-4 py-3 rounded relative bg-red-100 border border-red-400 text-red-700" role="alert">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

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
                🛒 Nueva Compra de Productos
            </h5>
            <div class="flex gap-2">
                <button type="button" 
                        wire:click="volver"
                        class="inline-flex items-center gap-1 px-3 py-2 text-sm bg-white text-gray-800 rounded hover:bg-gray-100">
                    <span>←</span> Volver
                </button>
            </div>
        </div>

        <!-- FORMULARIO -->
        <div class="px-5 py-4">
            <!-- Alerta de validación backend -->
            @if($mostrarAlerta)
                <div class="alert-campo-obligatorio">
                    <strong>⚠️ Error</strong>
                    <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
                    <br><small>{{ $mensajeAlerta }}</small>
                </div>
            @endif

            <form wire:submit.prevent="guardarCompra" novalidate>

                <!-- Información de la Compra -->
                <div class="p-4 mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📋 Información de la Compra</h2>
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="numero_factura" class="form-label">Número de Factura <span class="text-red-600">*</span></label>
                                <input type="text" id="numero_factura" class="form-control" wire:model.defer="compra.numero_factura">
                                @error('compra.numero_factura')
                                    <div class="mt-1 text-sm text-danger">❌ {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="proveedor" class="form-label">Proveedor <span class="text-red-600">*</span></label>
                                <select id="proveedor" class="form-select" wire:model.defer="proveedorSeleccionado">
                                    <option value="">Seleccionar proveedor</option>
                                    @forelse($proveedores as $proveedor)
                                        <option value="{{ $proveedor['id'] }}">
                                            {{ $proveedor['nombre'] }}
                                            @if($proveedor['rtn'])
                                                (RTN: {{ $proveedor['rtn'] }})
                                            @endif
                                            - {{ $proveedor['tipo_cliente'] }}
                                        </option>
                                    @empty
                                        <option value="" disabled>No hay proveedores disponibles</option>
                                    @endforelse
                                </select>
                                @error('proveedorSeleccionado')
                                    <div class="mt-1 text-sm text-danger">❌ {{ $message }}</div>
                                @enderror
                                @if(count($proveedores) == 0)
                                    <div class="mt-1 text-sm text-warning">
                                        ⚠️ No se encontraron proveedores. Asegúrese de tener clientes con tipo "Proveedor".
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-3 col-md-4">
                                <label for="fecha_emision" class="form-label">Fecha de Emisión <span class="text-red-600">*</span></label>
                                <input type="date" id="fecha_emision" class="form-control" wire:model.defer="compra.fecha_emision">
                                @error('compra.fecha_emision')
                                    <div class="mt-1 text-sm text-danger">❌ {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="fecha_recepcion" class="form-label">Fecha de Recepción <span class="text-red-600">*</span></label>
                                <input type="date" id="fecha_recepcion" class="form-control" wire:model.defer="compra.fecha_recepcion">
                                @error('compra.fecha_recepcion')
                                    <div class="mt-1 text-sm text-danger">❌ {{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3 col-md-4">
                                <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                                <input type="date" id="fecha_vencimiento" class="form-control" wire:model.defer="compra.fecha_vencimiento">
                                @error('compra.fecha_vencimiento')
                                    <div class="mt-1 text-sm text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Botón Ingresar Productos (siempre visible) -->
                        <div class="row mt-4">
                            <div class="col-12">
                                @if($this->mostrarSeccionProductos && !$mostrarSeccionProductosActiva)
                                    <div class="alert alert-success d-flex align-items-center justify-content-between">
                                        <div>
                                            <strong>✅ Información completa!</strong>
                                            <span class="ms-2">Ya puede proceder a agregar productos.</span>
                                        </div>
                                        <button type="button" 
                                                class="btn btn-sm px-4 py-2 d-flex align-items-center"
                                                wire:click="activarSeccionProductos"
                                                :class="{
                                                    'btn-success': theme === 'verde' || !theme,
                                                    'btn-primary': theme === 'azul',
                                                    'btn-dark': theme === 'oscuro',
                                                    'btn-secondary': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro' && theme
                                                }">
                                            <span style="font-size: 16px;" class="me-2">📦</span>
                                            <span class="fw-bold">Ingresar Productos</span>
                                        </button>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-center">
                                        <button type="button" 
                                                class="btn btn-lg px-5 py-3 d-flex align-items-center"
                                                wire:click="validarYActivarSeccionProductos"
                                                :class="{
                                                    'btn-success': theme === 'verde' || !theme,
                                                    'btn-primary': theme === 'azul',
                                                    'btn-dark': theme === 'oscuro',
                                                    'btn-secondary': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro' && theme
                                                }">
                                            <span style="font-size: 18px;" class="me-2">📦</span>
                                            <span class="fw-bold">Ingresar Productos</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Agregar Productos -->
                @if($this->mostrarSeccionProductos && $mostrarSeccionProductosActiva)
                        <!-- Sección completa de agregar productos -->
                <div class="p-4 mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📦 Agregar Productos</h2>
                        
                        <!-- Fila única de facturación -->
                        <div class="p-3 border rounded bg-gray-50">
                            <!-- Vista Desktop Grande (lg y arriba) - Una sola fila -->
                            <div class="d-none d-lg-block">
                                <div class="row g-2 align-items-end">
                                    <!-- Búsqueda/Selección de Producto -->
                                    <div class="col-lg-2">
                                        <label for="busqueda_producto" class="form-label">
                                            <strong>Producto / Código</strong> <span class="text-red-600">*</span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                   id="busqueda_producto" 
                                                   class="form-control" 
                                                   wire:model.live="busquedaProducto" 
                                                   placeholder="Escanear o buscar...">
                                            @if($busquedaProducto && $productoTemporal['producto_id'])
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-secondary position-absolute"
                                                        style="right: 5px; top: 5px; padding: 2px 6px;"
                                                        wire:click="limpiarBusqueda">
                                                    ✕
                                                </button>
                                            @endif
                                        </div>
                                        
                                        <!-- Lista de productos filtrados -->
                                        @if($mostrarListaProductos && count($productosFiltrados) > 0)
                                            <div class="position-absolute w-100 bg-white border rounded shadow-lg" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                                @foreach($productosFiltrados as $producto)
                                                    <div class="p-2 cursor-pointer hover:bg-gray-100" 
                                                         wire:click="seleccionarProducto({{ $producto['id'] }})">
                                                        <strong>{{ $producto['nombre'] }}</strong>
                                                        @if($producto['codigo_barra'])
                                                            <br><small class="text-muted">{{ $producto['codigo_barra'] }} - {{ $producto['marca'] }}</small>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Precio -->
                                    <div class="col-lg-2">
                                        <label for="precio" class="form-label"><strong>Precio</strong> <span class="text-red-600">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">L.</span>
                                            <input type="number" 
                                                   id="precio" 
                                                   name="precio_lg"
                                                   class="form-control" 
                                                   step="0.01" 
                                                   wire:model.live="productoTemporal.precio"
                                                   placeholder="0.00">
                                        </div>
                                    </div>

                                    <!-- Cantidad con botones +/- -->
                                    <div class="col-lg-1">
                                        <label for="cantidad" class="form-label"><strong>Cant.</strong> <span class="text-red-600">*</span></label>
                                        <div class="d-flex align-items-center justify-content-center">
                                            <!-- Botones + y - verticales a la izquierda -->
                                            <div class="d-flex flex-column me-2">
                                                <button type="button" 
                                                        class="btn btn-outline-secondary p-1 mb-1" 
                                                        wire:click="incrementarCantidad"
                                                        style="width: 20px; height: 20px; font-size: 10px; line-height: 1;">
                                                    +
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-secondary p-1" 
                                                        wire:click="decrementarCantidad"
                                                        style="width: 20px; height: 20px; font-size: 10px; line-height: 1;">
                                                    -
                                                </button>
                                            </div>
                                            <!-- Número a la derecha -->
                                            <span class="badge bg-secondary px-2 py-1" style="min-width: 30px; font-size: 0.8rem;">
                                                {{ $productoTemporal['cantidad_ingresada'] }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Unidad -->
                                    <div class="col-lg-2">
                                        <label for="unidad_compra" class="form-label"><strong>Unidad</strong> <span class="text-red-600">*</span></label>
                                        <select id="unidad_compra" name="unidad_compra_lg" class="form-select" wire:model.live="productoTemporal.unidad_compra_id">
                                            <option value="">Seleccionar</option>
                                            @foreach($unidadesCompra as $unidad)
                                                <option value="{{ $unidad['id'] }}">{{ $unidad['nombre'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- ISV -->
                                    <div class="col-lg-1">
                                        <label for="isv" class="form-label"><strong>ISV</strong></label>
                                        <div class="input-group">
                                            <input type="number" 
                                                   id="isv" 
                                                   name="isv_lg"
                                                   class="form-control text-center" 
                                                   step="0.01" 
                                                   min="0" 
                                                   max="100"
                                                   wire:model.live="productoTemporal.isv"
                                                   placeholder="0">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>

                                    <!-- Fecha de Expiración -->
                                    <div class="col-lg-2">
                                        <label for="fecha_expiracion" class="form-label"><strong>Exp.</strong></label>
                                        <input type="date" 
                                               id="fecha_expiracion" 
                                               class="form-control" 
                                               wire:model.live="productoTemporal.fecha_expiracion">
                                    </div>

                                    <!-- Botón Agregar -->
                                    <div class="col-lg-2">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="button" 
                                                class="btn w-100 d-flex align-items-center justify-content-center" 
                                                wire:click="agregarProducto"
                                                @disabled(!$this->botonHabilitado)
                                                :class="{
                                                    'btn-success': theme === 'verde' || !theme,
                                                    'btn-primary': theme === 'azul',
                                                    'btn-dark': theme === 'oscuro',
                                                    'btn-secondary': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro' && theme
                                                }">
                                            <span style="font-size: 16px;">➕</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Vista Desktop Mediana (md-lg) - 3 elementos por fila + botón al final -->
                            <div class="d-none d-md-block d-lg-none">
                                <!-- Primera fila: Producto, Precio, Cantidad -->
                                <div class="row g-3 mb-3">
                                    <!-- Búsqueda/Selección de Producto -->
                                    <div class="col-md-4">
                                        <label for="busqueda_producto_md" class="form-label">
                                            <strong>Producto / Código Barras</strong> <span class="text-red-600">*</span>
                                        </label>
                                        <div class="position-relative">
                                            <input type="text" 
                                                   id="busqueda_producto_md" 
                                                   class="form-control" 
                                                   wire:model.live="busquedaProducto" 
                                                   placeholder="Escanear o buscar...">
                                            @if($busquedaProducto && $productoTemporal['producto_id'])
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-secondary position-absolute"
                                                        style="right: 5px; top: 5px; padding: 2px 6px;"
                                                        wire:click="limpiarBusqueda">
                                                    ✕
                                                </button>
                                            @endif
                                        </div>
                                        
                                        <!-- Lista de productos filtrados -->
                                        @if($mostrarListaProductos && count($productosFiltrados) > 0)
                                            <div class="position-absolute w-100 bg-white border rounded shadow-lg" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                                @foreach($productosFiltrados as $producto)
                                                    <div class="p-2 cursor-pointer hover:bg-gray-100" 
                                                         wire:click="seleccionarProducto({{ $producto['id'] }})">
                                                        <strong>{{ $producto['nombre'] }}</strong>
                                                        @if($producto['codigo_barra'])
                                                            <br><small class="text-muted">{{ $producto['codigo_barra'] }} - {{ $producto['marca'] }}</small>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Precio -->
                                    <div class="col-md-4">
                                        <label for="precio_md" class="form-label"><strong>Precio</strong> <span class="text-red-600">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">L.</span>
                                            <input type="number" 
                                                   id="precio_md" 
                                                   name="precio_md"
                                                   class="form-control" 
                                                   step="0.01" 
                                                   wire:model.live="productoTemporal.precio"
                                                   placeholder="0.00">
                                        </div>
                                    </div>

                                    <!-- Cantidad con botones +/- -->
                                    <div class="col-md-4">
                                        <label for="cantidad_md" class="form-label"><strong>Cantidad</strong> <span class="text-red-600">*</span></label>
                                        <div class="d-flex align-items-center">
                                            <!-- Botones + y - verticales a la izquierda -->
                                            <div class="d-flex flex-column me-2">
                                                <button type="button" 
                                                        class="btn btn-outline-secondary p-1 mb-1" 
                                                        wire:click="incrementarCantidad"
                                                        style="width: 20px; height: 20px; font-size: 10px; line-height: 1;">
                                                    +
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-secondary p-1" 
                                                        wire:click="decrementarCantidad"
                                                        style="width: 20px; height: 20px; font-size: 10px; line-height: 1;">
                                                    -
                                                </button>
                                            </div>
                                            <!-- Número a la derecha -->
                                            <span class="badge bg-secondary px-2 py-1" style="min-width: 30px; font-size: 0.8rem;">
                                                {{ $productoTemporal['cantidad_ingresada'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Segunda fila: Unidad, ISV, Fecha -->
                                <div class="row g-3 mb-3">
                                    <!-- Unidad -->
                                    <div class="col-md-4">
                                        <label for="unidad_compra_md" class="form-label"><strong>Unidad</strong> <span class="text-red-600">*</span></label>
                                        <select id="unidad_compra_md" name="unidad_compra_md" class="form-select" wire:model.live="productoTemporal.unidad_compra_id">
                                            <option value="">Seleccionar</option>
                                            @foreach($unidadesCompra as $unidad)
                                                <option value="{{ $unidad['id'] }}">{{ $unidad['nombre'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- ISV -->
                                    <div class="col-md-4">
                                        <label for="isv_md" class="form-label"><strong>ISV</strong></label>
                                        <div class="input-group">
                                            <input type="number" 
                                                   id="isv_md" 
                                                   name="isv_md"
                                                   class="form-control text-center" 
                                                   step="0.01" 
                                                   min="0" 
                                                   max="100"
                                                   wire:model.live="productoTemporal.isv"
                                                   placeholder="0">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>

                                    <!-- Fecha de Expiración -->
                                    <div class="col-md-4">
                                        <label for="fecha_expiracion_md" class="form-label"><strong>Fecha de Expiración</strong></label>
                                        <input type="date" 
                                               id="fecha_expiracion_md" 
                                               class="form-control" 
                                               wire:model.live="productoTemporal.fecha_expiracion">
                                    </div>
                                </div>

                                <!-- Tercera fila: Botón Agregar centrado -->
                                <div class="row">
                                    <div class="col-12 d-flex justify-content-center">
                                        <button type="button" 
                                                class="btn px-4 py-2 d-flex align-items-center" 
                                                wire:click="agregarProducto"
                                                @disabled(!$this->botonHabilitado)
                                                :class="{
                                                    'btn-success': theme === 'verde' || !theme,
                                                    'btn-primary': theme === 'azul',
                                                    'btn-dark': theme === 'oscuro',
                                                    'btn-secondary': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro' && theme
                                                }">
                                            <span style="font-size: 16px;" class="me-2">➕</span>
                                            <span>Agregar Producto</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Vista Mobile (≤412px) - Lista vertical -->
                            <div class="d-block d-md-none" style="display: none !important;">
                                <!-- Solo se muestra en móviles muy pequeños mediante CSS -->
                            </div>
                            
                            <!-- Vista Mobile Real (≤412px) -->
                            <div class="mobile-only-view">
                                <!-- Búsqueda/Selección de Producto -->
                                <div class="mb-3">
                                    <label for="busqueda_producto_mobile" class="form-label">
                                        <strong>Producto / Código Barras</strong> <span class="text-red-600">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <input type="text" 
                                               id="busqueda_producto_mobile" 
                                               class="form-control" 
                                               wire:model.live="busquedaProducto" 
                                               placeholder="Escanear o buscar...">
                                        @if($busquedaProducto && $productoTemporal['producto_id'])
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary position-absolute"
                                                    style="right: 5px; top: 5px; padding: 2px 6px;"
                                                    wire:click="limpiarBusqueda">
                                                ✕
                                            </button>
                                        @endif
                                    </div>
                                    
                                    <!-- Lista de productos filtrados -->
                                    @if($mostrarListaProductos && count($productosFiltrados) > 0)
                                        <div class="position-absolute w-100 bg-white border rounded shadow-lg" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                            @foreach($productosFiltrados as $producto)
                                                <div class="p-2 cursor-pointer hover:bg-gray-100" 
                                                     wire:click="seleccionarProducto({{ $producto['id'] }})">
                                                    <strong>{{ $producto['nombre'] }}</strong>
                                                    @if($producto['codigo_barra'])
                                                        <br><small class="text-muted">{{ $producto['codigo_barra'] }} - {{ $producto['marca'] }}</small>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <!-- Precio -->
                                <div class="mb-3">
                                    <label for="precio_mobile" class="form-label"><strong>Precio</strong> <span class="text-red-600">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">L.</span>
                                        <input type="number" 
                                               id="precio_mobile" 
                                               name="precio_mobile"
                                               class="form-control" 
                                               step="0.01" 
                                               wire:model.live="productoTemporal.precio"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <!-- Cantidad y Unidad en la misma fila -->
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label"><strong>Cantidad</strong> <span class="text-red-600">*</span></label>
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex flex-column me-2">
                                                <button type="button" 
                                                        class="btn btn-outline-secondary p-1 mb-1" 
                                                        wire:click="incrementarCantidad"
                                                        style="width: 20px; height: 20px; font-size: 10px; line-height: 1;">
                                                    +
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-secondary p-1" 
                                                        wire:click="decrementarCantidad"
                                                        style="width: 20px; height: 20px; font-size: 10px; line-height: 1;">
                                                    -
                                                </button>
                                            </div>
                                            <span class="badge bg-secondary px-2 py-1" style="min-width: 30px; font-size: 0.8rem;">
                                                {{ $productoTemporal['cantidad_ingresada'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label for="unidad_compra_mobile" class="form-label"><strong>Unidad</strong> <span class="text-red-600">*</span></label>
                                        <select id="unidad_compra_mobile" name="unidad_compra_mobile" class="form-select" wire:model.live="productoTemporal.unidad_compra_id">
                                            <option value="">Seleccionar</option>
                                            @foreach($unidadesCompra as $unidad)
                                                <option value="{{ $unidad['id'] }}">{{ $unidad['nombre'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- ISV y Fecha en la misma fila -->
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label for="isv_mobile" class="form-label"><strong>ISV</strong></label>
                                        <div class="input-group">
                                            <input type="number" 
                                                   id="isv_mobile" 
                                                   name="isv_mobile"
                                                   class="form-control text-center" 
                                                   step="0.01" 
                                                   min="0" 
                                                   max="100"
                                                   wire:model.live="productoTemporal.isv"
                                                   placeholder="0">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label for="fecha_expiracion_mobile" class="form-label"><strong>Expiración</strong></label>
                                        <input type="date" 
                                               id="fecha_expiracion_mobile" 
                                               class="form-control" 
                                               wire:model.live="productoTemporal.fecha_expiracion">
                                    </div>
                                </div>

                                <!-- Botón Agregar -->
                                <div class="mb-3">
                                    <button type="button" 
                                            class="btn w-100 d-flex align-items-center justify-content-center" 
                                            wire:click="agregarProducto"
                                            @disabled(!$this->botonHabilitado)
                                            :class="{
                                                'btn-success': theme === 'verde' || !theme,
                                                'btn-primary': theme === 'azul',
                                                'btn-dark': theme === 'oscuro',
                                                'btn-secondary': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro' && theme
                                            }">
                                        <span style="font-size: 16px;">➕</span>
                                        <span class="ms-2">Agregar Producto</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Información del producto seleccionado -->
                            @if($productoTemporal['producto_id'])
                                @php
                                    $productoSeleccionado = collect($productos)->firstWhere('id', $productoTemporal['producto_id']);
                                @endphp
                                @if($productoSeleccionado)
                                    <div class="mt-2 p-2 bg-info bg-opacity-10 border border-info rounded">
                                        <small class="text-info">
                                            <strong>Producto:</strong> {{ $productoSeleccionado['nombre'] }} | 
                                            <strong>Marca:</strong> {{ $productoSeleccionado['marca'] }} | 
                                            <strong>Categoría:</strong> {{ $productoSeleccionado['subcategoria'] }}
                                        </small>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Mensaje para completar información de compra cuando no está completa -->
                @if(!$this->mostrarSeccionProductos)
                <!-- Mensaje para completar información de compra -->
                <div class="p-4 mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="text-muted" style="font-size: 3rem;">📋</i>
                            </div>
                            <h4 class="text-muted mb-3">Complete la Información de la Compra</h4>
                            <p class="text-muted mb-0">
                                Para agregar productos, primero complete todos los campos obligatorios 
                                en la sección "Información de la Compra" arriba.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Lista de Productos Agregados -->
                @if(count($productosCompra) > 0)
                <div class="p-4 mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h2 class="mb-4 text-lg font-semibold text-gray-700">📝 Productos en la Compra</h2>
                        
                        <!-- Tabla para desktop -->
                        <div class="d-none d-md-block">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 25%;">Producto</th>
                                            <th style="width: 10%;" class="text-center">Cant.</th>
                                            <th style="width: 10%;" class="text-center">Unidad</th>
                                            <th style="width: 12%;" class="text-end">Precio</th>
                                            <th style="width: 12%;" class="text-end">Subtotal</th>
                                            <th style="width: 8%;" class="text-center">ISV</th>
                                            <th style="width: 13%;" class="text-end">Total</th>
                                            <th style="width: 10%;" class="text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($productosCompra as $index => $producto)
                                            <tr>
                                                <td class="text-start">
                                                    <strong>{{ $producto['producto_nombre'] }}</strong>
                                                    @if($producto['producto_codigo'])
                                                        <br><small class="text-muted">{{ $producto['producto_codigo'] }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <!-- Botones + y - verticales -->
                                                        <div class="d-flex flex-column me-2">
                                                            <button type="button" 
                                                                    class="btn btn-outline-secondary p-1 mb-1" 
                                                                    wire:click="actualizarCantidad({{ $index }}, {{ $producto['cantidad_ingresada'] + 1 }})"
                                                                    style="width: 20px; height: 20px; font-size: 10px; line-height: 1;">
                                                                +
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-outline-secondary p-1" 
                                                                    wire:click="actualizarCantidad({{ $index }}, {{ max(1, $producto['cantidad_ingresada'] - 1) }})"
                                                                    style="width: 20px; height: 20px; font-size: 10px; line-height: 1;">
                                                                -
                                                            </button>
                                                        </div>
                                                        <!-- Input de cantidad -->
                                                        <input type="number" 
                                                               class="form-control form-control-sm text-center" 
                                                               value="{{ $producto['cantidad_ingresada'] }}"
                                                               wire:change="actualizarCantidad({{ $index }}, $event.target.value)"
                                                               min="1"
                                                               style="width: 60px;">
                                                    </div>
                                                </td>
                                                <td class="text-center">{{ $producto['unidad_compra_nombre'] }}</td>
                                                <td class="text-end">L. {{ number_format($producto['precio'], 2) }}</td>
                                                <td class="text-end">L. {{ number_format($producto['sub_total_producto'], 2) }}</td>
                                                <td class="text-center">{{ $producto['isv'] }}%</td>
                                                <td class="text-end"><strong>L. {{ number_format($producto['precio_total'], 2) }}</strong></td>
                                                <td class="text-center">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            wire:click="eliminarProducto({{ $index }})">
                                                        🗑️
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Vista Mobile con tarjetas -->
                        <div class="d-md-none">
                            @foreach($productosCompra as $index => $producto)
                                <div class="card mb-3 border">
                                    <div class="card-body p-3">
                                        <!-- Encabezado del producto -->
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="mb-1 fw-bold">{{ $producto['producto_nombre'] }}</h6>
                                                @if($producto['producto_codigo'])
                                                    <small class="text-muted">Código: {{ $producto['producto_codigo'] }}</small>
                                                @endif
                                            </div>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger" 
                                                    wire:click="eliminarProducto({{ $index }})">
                                                🗑️
                                            </button>
                                        </div>
                                        
                                        <!-- Información del producto -->
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center">
                                                    <small class="text-muted d-block">Cantidad</small>
                                                    <div class="d-flex align-items-center justify-content-center mt-1">
                                                        <!-- Botones + y - verticales -->
                                                        <div class="d-flex flex-column me-2">
                                                            <button type="button" 
                                                                    class="btn btn-outline-secondary p-1 mb-1" 
                                                                    wire:click="actualizarCantidad({{ $index }}, {{ $producto['cantidad_ingresada'] + 1 }})"
                                                                    style="width: 20px; height: 20px; font-size: 10px; line-height: 1;">
                                                                +
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-outline-secondary p-1" 
                                                                    wire:click="actualizarCantidad({{ $index }}, {{ max(1, $producto['cantidad_ingresada'] - 1) }})"
                                                                    style="width: 20px; height: 20px; font-size: 10px; line-height: 1;">
                                                                -
                                                            </button>
                                                        </div>
                                                        <!-- Input de cantidad -->
                                                        <input type="number" 
                                                               class="form-control form-control-sm text-center" 
                                                               value="{{ $producto['cantidad_ingresada'] }}"
                                                               wire:change="actualizarCantidad({{ $index }}, $event.target.value)"
                                                               min="1"
                                                               style="width: 50px;">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center">
                                                    <small class="text-muted d-block">Unidad</small>
                                                    <span class="fw-semibold">{{ $producto['unidad_compra_nombre'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Precios -->
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center">
                                                    <small class="text-muted d-block">Precio Unit.</small>
                                                    <span class="fw-semibold">L.{{ number_format($producto['precio'], 2) }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center">
                                                    <small class="text-muted d-block">ISV</small>
                                                    <span class="fw-semibold">{{ $producto['isv'] }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Totales -->
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center bg-light">
                                                    <small class="text-muted d-block">Subtotal</small>
                                                    <span class="fw-bold text-primary">L.{{ number_format($producto['sub_total_producto'], 2) }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border rounded p-2 text-center bg-success bg-opacity-10">
                                                    <small class="text-muted d-block">Total</small>
                                                    <span class="fw-bold text-success">L.{{ number_format($producto['precio_total'], 2) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Totales de factura profesional -->
                        <div class="row justify-content-end mt-4">
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="border rounded p-3 bg-light">
                                    <!-- Subtotal -->
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                        <span class="fw-semibold">Subtotal:</span>
                                        <span class="fw-bold">L.{{ number_format($subtotal, 2) }}</span>
                                    </div>
                                    
                                    <!-- ISV -->
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                        <span class="fw-semibold">ISV (15%):</span>
                                        <span class="fw-bold">L.{{ number_format($totalIsv, 2) }}</span>
                                    </div>
                                    
                                    <!-- Total Final -->
                                    <div class="d-flex justify-content-between align-items-center py-2 mt-2">
                                        <span class="fw-bold text-success fs-5">TOTAL:</span>
                                        <span class="fw-bold text-success fs-4">L.{{ number_format($total, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Botones -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex flex-column flex-md-row justify-content-end gap-2">
                            <button type="button" 
                                    wire:click="resetFormulario"
                                    class="btn btn-outline-secondary order-2 order-md-1">
                                🔄 Limpiar Todo
                            </button>
                            
                            <button type="submit"
                                    class="btn order-1 order-md-2"
                                    @disabled(!$this->botonGuardarHabilitado)
                                    :class="{
                                        'btn-success': theme === 'verde' || !theme,
                                        'btn-primary': theme === 'azul',
                                        'btn-dark': theme === 'oscuro',
                                        'btn-secondary': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro' && theme
                                    }">
                                💾 Guardar Compra
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>

    </div>

    <!-- Alerta de validación flotante -->
    @if($mostrarAlerta)
        <div class="alert-campo-obligatorio">
            <strong>⚠️ Error</strong>
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

        /* Lista de productos filtrados */
        .cursor-pointer:hover {
            background-color: #f8f9fa;
        }

        /* Tabla responsive */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            overflow-x: auto;
            min-height: 200px;
        }

        /* Asegurar que la tabla no se comprima demasiado */
        .table {
            min-width: 700px;
            margin-bottom: 0;
        }

        /* Botón deshabilitado */
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Estilos para la fila de facturación */
        .bg-gray-50 {
            background-color: #f8f9fa !important;
        }

        /* Botones de cantidad */
        .input-group .btn {
            border-color: #dee2e6;
        }

        .input-group .btn:hover {
            background-color: #e9ecef;
        }

        /* Campo de cantidad centrado */
        .text-center {
            text-align: center !important;
        }

        /* Información del producto seleccionado */
        .bg-info.bg-opacity-10 {
            background-color: rgba(13, 202, 240, 0.1) !important;
        }

        .border-info {
            border-color: #0dcaf0 !important;
        }

        .text-info {
            color: #0dcaf0 !important;
        }

        /* Mejorar la lista de productos filtrados */
        .position-absolute {
            position: absolute !important;
        }

        /* Botón de limpiar búsqueda */
        .position-absolute .btn {
            z-index: 5;
        }

        /* Campos obligatorios destacados */
        .form-label strong {
            font-weight: 600;
        }

        /* Input group con moneda */
        .input-group-text {
            background-color: #e9ecef;
            border-color: #ced4da;
        }

        /* Botones pequeños de cantidad */
        .btn-sm {
            font-size: 0.75rem;
        }

        /* Badge para cantidad */
        .badge {
            font-size: 0.9rem !important;
        }

        /* Input group pequeño */
        .input-group-sm .form-control,
        .input-group-sm .input-group-text {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Botones de cantidad hover */
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        /* Botones de cantidad pequeños */
        .btn-outline-secondary.p-1 {
            border-radius: 3px;
            font-weight: bold;
        }

        /* Input de cantidad en tabla */
        .form-control-sm.text-center {
            padding: 0.25rem 0.5rem;
            text-align: center !important;
        }

        /* Botón agregar pequeño y blanco */
        .btn-agregar-pequeno {
            background-color: white !important;
            border: 1px solid #dee2e6 !important;
            color: #6c757d !important;
            transition: all 0.2s ease;
        }

        .btn-agregar-pequeno:hover {
            background-color: #f8f9fa !important;
            border-color: #adb5bd !important;
            color: #495057 !important;
        }

        .btn-agregar-pequeno:disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
        }

        /* Campo de fecha pequeño */
        .form-control-sm {
            font-size: 0.75rem !important;
            padding: 0.25rem !important;
        }

        /* Estilos para tabla de factura */
        .table-sm th,
        .table-sm td {
            padding: 0.5rem 0.25rem;
            vertical-align: middle;
            border: 1px solid #dee2e6;
        }

        .table-light th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
            font-size: 0.875rem;
            padding: 0.5rem 0.25rem;
            font-weight: 600;
        }

        /* Encabezados de tabla */
        .table thead th {
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fa;
        }

        /* Layout fijo de tabla */
        .table {
            table-layout: fixed;
            width: 100%;
        }





        /* Totales de factura profesional */
        .border-bottom {
            border-bottom: 1px solid #dee2e6 !important;
        }

        /* Mejoras responsive */
        @media (max-width: 767px) {
            .col-6 {
                margin-bottom: 10px;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .table-sm th,
            .table-sm td {
                padding: 0.5rem 0.25rem;
            }
            
            /* Padding reducido para móvil */
            .p-4 {
                padding: 1rem !important;
            }
            
            /* Tarjetas de productos en móvil */
            .card {
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .card-body {
                padding: 1rem !important;
            }
            
            /* Cajas de información en móvil */
            .border.rounded.p-2 {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6 !important;
            }
            
            /* Totales sin espacios extra */
            .fw-bold {
                white-space: nowrap;
            }
            
            /* Asegurar que L. esté junto al número */
            .fw-bold:contains("L.") {
                display: inline-block;
            }
        }

        /* Estilos específicos para móvil 412x915 */
        @media (max-width: 414px) {
            .px-5 {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            
            .p-4.mb-4 {
                padding: 0.75rem !important;
                margin-bottom: 1rem !important;
            }
            
            /* Form mobile optimizado */
            .form-control, .form-select {
                font-size: 16px; /* Evita zoom en iOS */
                padding: 0.5rem;
            }
            
            /* Labels más pequeños */
            .form-label {
                font-size: 0.875rem;
                margin-bottom: 0.25rem;
            }
            
            /* Botones de cantidad más accesibles */
            .d-flex.flex-column .btn {
                width: 28px !important;
                height: 28px !important;
                font-size: 12px !important;
            }
            
            /* Mostrar solo vista móvil en 412px o menos */
            .mobile-only-view {
                display: block !important;
            }
        }

        /* Ocultar vista móvil por defecto */
        .mobile-only-view {
            display: none !important;
        }

        /* Mostrar vista móvil solo en pantallas muy pequeñas */
        @media (max-width: 412px) {
            .mobile-only-view {
                display: block !important;
            }
        }

        /* Estilos para vistas Desktop */
        @media (min-width: 413px) and (max-width: 991px) {
            /* Vista mediana: 3 elementos por fila */
            .d-md-block.d-lg-none {
                display: block !important;
            }
        }

        @media (min-width: 992px) {
            /* Vista grande: una sola fila */
            .d-lg-block {
                display: block !important;
            }
        }

        /* Asegurar que los precios no se rompan */
        .fw-semibold, .fw-bold {
            white-space: nowrap !important;
            display: inline-block !important;
        }

        /* Contenedores de precios más espaciosos */
        .border.rounded.p-2 {
            min-height: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Estilos para distribución flexible */
        .row.g-3 {
            margin-bottom: 1rem;
        }

        /* Botón centrado mejorado */
        .btn.px-4.py-2 {
            min-width: 200px;
            font-weight: 600;
        }

        /* Botones verticales de cantidad */
        .d-flex.flex-column .btn {
            border-radius: 2px;
        }

        .d-flex.flex-column .btn:first-child {
            border-bottom: none;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .d-flex.flex-column .btn:last-child {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        /* Estilos para campos pequeños */
        .form-control-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .form-select-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Alineación de elementos en fila de facturación */
        .d-flex.align-items-center {
            height: 38px; /* Altura estándar de form-control */
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
                        <h3 class="text-lg font-semibold">¡Compra Registrada!</h3>
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
                    <p class="text-gray-600">La compra se ha registrado correctamente en el sistema.</p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 text-center bg-gray-50">
                    <button wire:click="cerrarModalExito"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-green-600 rounded-md hover:bg-green-700">
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
                        <h3 class="text-lg font-semibold">Error en la Compra</h3>
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
                    <p class="text-gray-600">Por favor, revise los datos e intente nuevamente.</p>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3 text-center bg-gray-50">
                    <button wire:click="cerrarModalError"
                            class="px-4 py-2 text-white transition-colors duration-200 bg-red-600 rounded-md hover:bg-red-700">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script para auto-focus y manejo de eventos -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus inicial en el campo de búsqueda
            setTimeout(() => {
                const busquedaField = document.getElementById('busqueda_producto');
                if (busquedaField) {
                    busquedaField.focus();
                }
            }, 100);
        });

        document.addEventListener('livewire:init', () => {
            // Escuchar eventos de Livewire para enfocar campos
            Livewire.on('enfocar-busqueda', () => {
                setTimeout(() => {
                    const busquedaField = document.getElementById('busqueda_producto');
                    if (busquedaField) {
                        busquedaField.focus();
                        busquedaField.select();
                    }
                }, 100);
            });

            Livewire.on('enfocar-precio', () => {
                setTimeout(() => {
                    const precioField = document.getElementById('precio');
                    if (precioField) {
                        precioField.focus();
                        precioField.select();
                    }
                }, 100);
            });

            // Actualizar estado del botón cuando cambien los datos
            Livewire.hook('morph.updated', () => {
                actualizarBotonAgregar();
            });
        });

        // Función para actualizar el estado del botón agregar
        function actualizarBotonAgregar() {
            const botonAgregar = document.querySelector('button[wire\\:click="agregarProducto"]');
            if (botonAgregar) {
                const productoId = @this.productoTemporal?.producto_id;
                const precio = parseFloat(@this.productoTemporal?.precio || 0);
                const unidadId = @this.productoTemporal?.unidad_compra_id;
                
                const habilitado = productoId && precio > 0 && unidadId;
                botonAgregar.disabled = !habilitado;
                
                // Cambiar opacidad visualmente
                botonAgregar.style.opacity = habilitado ? '1' : '0.6';
            }
        }

        // Manejar Enter en campos para navegación rápida
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                
                // Si está en búsqueda y hay producto seleccionado, ir a precio
                if (activeElement.id === 'busqueda_producto') {
                    const productoId = @this.productoTemporal?.producto_id;
                    if (productoId) {
                        e.preventDefault();
                        setTimeout(() => {
                            const precioField = document.getElementById('precio');
                            if (precioField) {
                                precioField.focus();
                                precioField.select();
                            }
                        }, 100);
                    }
                }
                
                // Si está en precio, ir a unidad
                else if (activeElement.id === 'precio') {
                    e.preventDefault();
                    setTimeout(() => {
                        const unidadField = document.getElementById('unidad_compra');
                        if (unidadField) {
                            unidadField.focus();
                        }
                    }, 100);
                }
                
                // Si está en ISV, agregar producto automáticamente
                else if (activeElement.id === 'isv') {
                    e.preventDefault();
                    const botonAgregar = document.querySelector('button[wire\\:click="agregarProducto"]');
                    if (botonAgregar && !botonAgregar.disabled) {
                        @this.agregarProducto();
                    }
                }
            }
            
            // Atajos de teclado
            if (e.ctrlKey && e.key === '+') {
                e.preventDefault();
                @this.incrementarCantidad();
            }
            if (e.ctrlKey && e.key === '-') {
                e.preventDefault();
                @this.decrementarCantidad();
            }
        });

        // Verificar estado del botón periódicamente
        setInterval(actualizarBotonAgregar, 500);
    </script>

</div> {{-- FIN ELEMENTO RAÍZ --}}
