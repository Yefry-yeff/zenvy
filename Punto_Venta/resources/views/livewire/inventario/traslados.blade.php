<div>
    <style>
        .transfer-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, .62);
            backdrop-filter: blur(2px);
            animation: transfer-backdrop-in .22s ease-out both;
        }

        .transfer-modal {
            width: min(940px, 100%);
            max-height: calc(100vh - 48px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
            animation: transfer-modal-in .32s cubic-bezier(.22, 1, .36, 1) both;
        }

        .transfer-modal-body {
            min-height: 0;
            flex: 1;
            overflow-y: auto;
        }

        .product-search-backdrop {
            position: fixed;
            inset: 0;
            z-index: 10050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, .72);
            animation: transfer-backdrop-in .22s ease-out both;
        }

        .transfer-alert {
            animation: transfer-slide-down .3s cubic-bezier(.22, 1, .36, 1) both;
        }

        .transfer-product-card {
            opacity: 0;
            animation: transfer-card-in .32s cubic-bezier(.22, 1, .36, 1) forwards;
            animation-delay: var(--transfer-delay, 0ms);
        }

        .transfer-summary-row {
            animation: transfer-row-in .25s ease-out both;
        }

        .transfer-action {
            transition: transform .16s ease, box-shadow .16s ease, background-color .16s ease, border-color .16s ease;
        }

        .transfer-action:hover:not(:disabled) {
            transform: translateY(-1px);
        }

        .transfer-action:active:not(:disabled) {
            transform: translateY(0) scale(.98);
        }

        @keyframes transfer-backdrop-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes transfer-modal-in {
            from { opacity: 0; transform: translateY(18px) scale(.975); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes transfer-slide-down {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes transfer-card-in {
            from { opacity: 0; transform: translateY(12px) scale(.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes transfer-row-in {
            from { opacity: 0; transform: translateX(8px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .transfer-modal-backdrop,
            .product-search-backdrop,
            .transfer-modal,
            .transfer-alert,
            .transfer-product-card,
            .transfer-summary-row {
                animation: none;
                opacity: 1;
            }

            .transfer-action {
                transition: none;
            }
        }

        @media (max-width: 767px) {
            .transfer-modal-backdrop {
                padding: 0;
            }

            .transfer-modal {
                width: 100%;
                min-height: 100vh;
                max-height: 100vh;
                border-radius: 0;
            }
        }
    </style>

    @if(session()->has('success') && !$mostrarDetalle)
        <div class="transfer-alert px-4 py-3 mb-4 text-sm text-green-800 bg-green-50 border border-green-200 rounded-lg">
            <strong>Listo.</strong> {{ session('success') }}
        </div>
    @endif
    @if(session()->has('error'))
        <div class="transfer-alert px-4 py-3 mb-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg">
            <strong>No se pudo completar.</strong> {{ session('error') }}
        </div>
    @endif

    <div class="overflow-hidden bg-white border border-gray-200 rounded-xl shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-5 border-b border-gray-200">
            <div>
                <div class="flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 text-xl text-blue-700 bg-blue-100 rounded-lg">🚚</span>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Envíos a Valencia</h2>
                        <p class="text-sm text-gray-500">Prepare, despache y consulte los productos enviados desde Paperland.</p>
                    </div>
                </div>
            </div>
            <button wire:click="abrirCrear"
                    class="transfer-action inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 hover:shadow-md">
                <span class="text-lg leading-none">+</span>
                Preparar envío
            </button>
        </div>

        <div class="grid grid-cols-1 gap-3 p-5 bg-gray-50 border-b sm:grid-cols-3">
            <div class="p-4 bg-white border border-gray-200 rounded-lg">
                <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Por revisar</p>
                <p class="mt-1 text-2xl font-bold text-gray-900">{{ $estadisticas['borradores'] }}</p>
                <p class="text-xs text-gray-500">Todavía no descuentan inventario</p>
            </div>
            <div class="p-4 bg-white border border-blue-200 rounded-lg">
                <p class="text-xs font-semibold tracking-wide text-blue-600 uppercase">En camino</p>
                <p class="mt-1 text-2xl font-bold text-blue-700">{{ $estadisticas['en_transito'] }}</p>
                <p class="text-xs text-gray-500">Ya descontados de Paperland</p>
            </div>
            <div class="p-4 bg-white border border-green-200 rounded-lg">
                <p class="text-xs font-semibold tracking-wide text-green-600 uppercase">Recibidos</p>
                <p class="mt-1 text-2xl font-bold text-green-700">{{ $estadisticas['completados'] }}</p>
                <p class="text-xs text-gray-500">Confirmados por Valencia</p>
            </div>
        </div>

        <div class="flex flex-col gap-3 p-5 border-b md:flex-row">
            <div class="relative flex-1">
                <span class="absolute text-gray-400 -translate-y-1/2 left-3 top-1/2">⌕</span>
                <input wire:model.live.debounce.300ms="busqueda"
                       class="w-full py-2 pl-9 pr-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="Buscar por número de envío">
            </div>
            <select wire:model.live="filtroEstado" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg md:w-56">
                <option value="">Todos los estados</option>
                <option value="borrador">Por revisar</option>
                <option value="en_transito">En camino</option>
                <option value="recibido_parcial">Recepción parcial</option>
                <option value="recibido">Recibido</option>
                <option value="cancelado">Cancelado</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs tracking-wide text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left">Envío</th>
                        <th class="px-5 py-3 text-left">Sale de</th>
                        <th class="px-5 py-3 text-center">Productos</th>
                        <th class="px-5 py-3 text-center">Unidades</th>
                        <th class="px-5 py-3 text-center">Estado</th>
                        <th class="px-5 py-3 text-center">Fecha</th>
                        <th class="px-5 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($traslados as $traslado)
                        @php
                            $estado = match($traslado->estado) {
                                'borrador' => ['Por revisar', 'bg-gray-100 text-gray-700'],
                                'en_transito' => ['En camino', 'bg-blue-100 text-blue-700'],
                                'recibido_parcial' => ['Recepción parcial', 'bg-amber-100 text-amber-800'],
                                'recibido' => ['Recibido', 'bg-green-100 text-green-700'],
                                default => ['Cancelado', 'bg-red-100 text-red-700'],
                            };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ $traslado->codigo }}</p>
                                <p class="text-xs text-gray-500">Destino: Valencia</p>
                            </td>
                            <td class="px-5 py-4 text-gray-700">{{ trim($traslado->bodegaOrigen->nombre ?? 'Paperland') }}</td>
                            <td class="px-5 py-4 text-center">{{ $traslado->detalles->count() }}</td>
                            <td class="px-5 py-4 font-semibold text-center text-gray-900">
                                {{ number_format($traslado->detalles->sum('cantidad_solicitada')) }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full {{ $estado[1] }}">{{ $estado[0] }}</span>
                            </td>
                            <td class="px-5 py-4 text-center text-gray-500">{{ $traslado->fecha_solicitud?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4 text-right">
                                <button wire:click="verDetalle({{ $traslado->id }})"
                                        class="px-3 py-1.5 text-sm font-medium text-blue-700 rounded-lg hover:bg-blue-50">
                                    Ver envío →
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl bg-gray-100 rounded-full">📦</div>
                                <p class="font-medium text-gray-800">Todavía no hay envíos a Valencia</p>
                                <p class="mt-1 text-sm text-gray-500">Prepare el primero usando el botón superior.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($traslados->hasPages())
            <div class="px-5 py-4 border-t">{{ $traslados->links() }}</div>
        @endif
    </div>

    @if($mostrarCrear)
        <div class="transfer-modal-backdrop">
            <div class="transfer-modal">
                <div class="flex items-start justify-between px-6 py-5 border-b border-gray-200">
                    <div>
                        <p class="mb-1 text-xs font-semibold tracking-wide text-blue-600 uppercase">Nuevo envío</p>
                        <h3 class="text-xl font-bold text-gray-900">Preparar productos para Valencia</h3>
                        <p class="mt-1 text-sm text-gray-500">Revise todo antes de despachar. Guardar este formulario no descuenta inventario.</p>
                    </div>
                    <button wire:click="cerrarCrear" class="p-1 text-2xl leading-none text-gray-400 rounded hover:text-gray-700 hover:bg-gray-100">&times;</button>
                </div>

                <div class="flex items-center gap-2 px-6 py-3 text-xs font-medium border-b bg-gray-50">
                    <span class="inline-flex items-center justify-center w-6 h-6 text-white bg-blue-600 rounded-full">1</span>
                    <span class="text-gray-700">Origen</span>
                    <span class="w-8 h-px bg-gray-300"></span>
                    <span class="inline-flex items-center justify-center w-6 h-6 text-white bg-blue-600 rounded-full">2</span>
                    <span class="text-gray-700">Productos</span>
                    <span class="w-8 h-px bg-gray-300"></span>
                    <span class="inline-flex items-center justify-center w-6 h-6 text-gray-600 bg-gray-200 rounded-full">3</span>
                    <span class="text-gray-500">Revisión</span>
                </div>

                <div class="transfer-modal-body">
                    <div class="grid grid-cols-1 lg:grid-cols-[1.25fr_.75fr]">
                        <div class="p-6 space-y-5 lg:border-r">
                            <section>
                                <label class="block mb-2 text-sm font-semibold text-gray-800">¿Desde dónde sale?</label>
                                <select wire:model.live="bodegaOrigenId" class="w-full px-3 py-2.5 text-sm bg-white border border-gray-300 rounded-lg">
                                    <option value="">Seleccione una bodega</option>
                                    @foreach($bodegas as $bodega)
                                        <option value="{{ $bodega->id }}">{{ trim($bodega->nombre) }}</option>
                                    @endforeach
                                </select>
                                @error('bodegaOrigenId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </section>

                            <section class="p-4 border border-gray-200 rounded-xl bg-gray-50">
                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-900">Agregar productos</h4>
                                        <p class="text-xs text-gray-500">Solo aparecen productos vinculados con Valencia.</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full">{{ count($productosSalida) }} agregados</span>
                                </div>

                                <div class="space-y-3">
                                    <button wire:click="abrirBusquedaProductos"
                                            @disabled(!$bodegaOrigenId)
                                                class="transfer-action flex items-center justify-between w-full px-4 py-3 text-left bg-white border border-gray-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-50">
                                        <span class="flex items-center gap-3">
                                            <span class="text-lg text-gray-400">⌕</span>
                                            <span>
                                                <span class="block text-sm font-semibold text-gray-800">Buscar en el inventario</span>
                                                <span class="block text-xs text-gray-500">Nombre, código de barras, marca o categoría</span>
                                            </span>
                                        </span>
                                        <span class="text-sm font-semibold text-blue-600">Abrir buscador →</span>
                                    </button>

                                    @if($productoBusquedaSeleccionado)
                                        <div class="p-3 bg-white border border-blue-200 rounded-lg">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900">{{ $productoBusquedaSeleccionado['nombre'] }}</p>
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        {{ $productoBusquedaSeleccionado['unidad_nombre'] }}
                                                        @if($productoBusquedaSeleccionado['presentacion_descripcion'])
                                                            · {{ $productoBusquedaSeleccionado['presentacion_descripcion'] }}
                                                        @endif
                                                    </p>
                                                    <p class="mt-1 text-xs font-medium text-green-700">
                                                        {{ $productoBusquedaSeleccionado['stock_disponible'] }} disponibles
                                                    </p>
                                                    <p class="mt-1 text-xs font-medium text-indigo-700">
                                                        Valencia: producto #{{ $productoBusquedaSeleccionado['producto_id_valencia'] }}
                                                        · unidad #{{ $productoBusquedaSeleccionado['unidad_medida_id_valencia'] }}
                                                    </p>
                                                    <p class="mt-0.5 text-xs text-gray-400">
                                                        Unidad autorizada por traslado Valencia #{{ $productoBusquedaSeleccionado['ultimo_traslado_valencia'] }}
                                                    </p>
                                                </div>
                                                <button wire:click="abrirBusquedaProductos" class="text-xs font-semibold text-blue-600">Cambiar</button>
                                            </div>

                                            <div class="mt-3">
                                                <label class="block mb-1 text-xs font-semibold text-gray-700">
                                                    ¿Cuántas {{ trim($productoBusquedaSeleccionado['unidad_nombre']) }} enviará?
                                                </label>
                                                <div class="flex gap-2">
                                                    <input type="number" min="1"
                                                           max="{{ $productoBusquedaSeleccionado['stock_disponible'] }}"
                                                           wire:model="cantidadSeleccionada"
                                                           class="flex-1 px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg"
                                                           placeholder="Máximo {{ $productoBusquedaSeleccionado['stock_disponible'] }}">
                                                    <button wire:click="agregarProducto"
                                                            class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                                        Agregar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @error('productoSeleccionadoId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    @error('presentacionSeleccionadaId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    @error('cantidadSeleccionada') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </section>

                            <section>
                                <label class="block mb-2 text-sm font-semibold text-gray-800">Nota para este envío <span class="font-normal text-gray-400">(opcional)</span></label>
                                <textarea wire:model="comentario" rows="2" maxlength="500"
                                          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg resize-none"
                                          placeholder="Ejemplo: entregar al encargado de bodega"></textarea>
                            </section>
                        </div>

                        <aside class="p-6 bg-slate-50">
                            <div class="sticky top-0">
                                <h4 class="font-semibold text-gray-900">Resumen del envío</h4>
                                <p class="mt-1 mb-4 text-xs text-gray-500">Confirme productos y cantidades antes de continuar.</p>

                                @if(count($productosSalida) > 0)
                                    <div class="overflow-hidden bg-white border border-gray-200 rounded-lg">
                                        <table class="w-full text-xs">
                                            <thead class="text-gray-500 bg-gray-100">
                                                <tr wire:key="resumen-{{ $item['precio_venta_id'] }}" class="transfer-summary-row">
                                                    <th class="px-3 py-2 text-left">Producto en Valencia</th>
                                                    <th class="px-2 py-2 text-left">Unidad</th>
                                                    <th class="px-2 py-2 text-right">Cant.</th>
                                                    <th class="w-8"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y">
                                                @foreach($productosSalida as $indice => $item)
                                                    <tr>
                                                        <td class="px-3 py-3">
                                                            <p class="font-semibold text-gray-900">{{ $item['producto'] }}</p>
                                                            <p class="mt-0.5 text-gray-500">ID Valencia: {{ $item['producto_id_valencia'] }}</p>
                                                        </td>
                                                        <td class="px-2 py-3">
                                                            <p class="font-medium text-gray-800">{{ trim($item['unidad_nombre']) }}</p>
                                                            <p class="text-gray-500">ID Valencia: {{ $item['unidad_medida_id_valencia'] }}</p>
                                                        </td>
                                                        <td class="px-2 py-3 text-base font-bold text-right text-blue-700">{{ $item['cantidad'] }}</td>
                                                        <td class="pr-2 text-right">
                                                            <button wire:click="quitarProducto({{ $indice }})" title="Quitar" class="text-lg text-red-500">&times;</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                        <div class="px-4 py-8 text-center bg-white border-2 border-dashed border-gray-200 rounded-lg">
                                            <div class="mb-2 text-2xl">📦</div>
                                            <p class="text-sm font-medium text-gray-600">El envío está vacío</p>
                                            <p class="mt-1 text-xs text-gray-400">Agregue un producto para continuar.</p>
                                        </div>
                                @endif

                                @if(count($productosSalida) > 0)
                                    <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-200">
                                        <span class="text-sm text-gray-600">Total de unidades</span>
                                        <span class="text-lg font-bold text-gray-900">{{ number_format(collect($productosSalida)->sum('cantidad')) }}</span>
                                    </div>
                                @endif
                            </div>
                        </aside>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-2 px-6 py-4 bg-white border-t sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-gray-500">El inventario se descontará al confirmar el despacho en el siguiente paso.</p>
                    <div class="flex gap-2">
                        <button wire:click="cerrarCrear" class="transfer-action px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                        <button wire:click="guardarBorrador"
                                wire:loading.attr="disabled"
                                @disabled(count($productosSalida) === 0)
                                class="transfer-action px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 hover:shadow-md disabled:opacity-40 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="guardarBorrador">Continuar a revisión →</span>
                            <span wire:loading wire:target="guardarBorrador">Preparando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($mostrarBusquedaProducto)
        <div class="product-search-backdrop" wire:keydown.escape="cerrarBusquedaProductos">
            <div class="transfer-modal" style="width: min(1120px, 100%);">
                <div class="flex items-center justify-between px-6 py-4 text-white bg-slate-800">
                    <div>
                        <h3 class="text-lg font-semibold">🔍 Buscar producto para enviar</h3>
                        <p class="mt-1 text-xs text-slate-300">Solo se muestran presentaciones con existencia en {{ trim(collect($bodegas)->firstWhere('id', (int) $bodegaOrigenId)?->nombre ?? 'la bodega seleccionada') }}.</p>
                    </div>
                    <button wire:click="cerrarBusquedaProductos" class="text-2xl leading-none text-slate-200 hover:text-white">&times;</button>
                </div>

                <div class="transfer-modal-body p-6">
                    <div class="relative mb-4">
                        <span class="absolute text-gray-400 -translate-y-1/2 left-3 top-1/2">⌕</span>
                        <input type="text"
                               wire:model.live.debounce.300ms="busquedaProducto"
                               class="w-full py-3 pl-10 pr-10 text-sm border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                               placeholder="Buscar por nombre, código de barras o descripción..."
                               autofocus>
                        <div wire:loading wire:target="busquedaProducto,marcaBusqueda,categoriaBusqueda,subcategoriaBusqueda"
                             class="absolute -translate-y-1/2 right-3 top-1/2">
                            <span class="block w-5 h-5 border-2 border-blue-500 rounded-full border-t-transparent animate-spin"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 mb-5 md:grid-cols-4">
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-gray-600">Marca</label>
                            <select wire:model.live="marcaBusqueda" class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg">
                                <option value="">Todas las marcas</option>
                                @foreach($marcasBusqueda as $marca)
                                    <option value="{{ $marca['id'] }}">{{ $marca['nombre'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-gray-600">Categoría</label>
                            <select wire:model.live="categoriaBusqueda" class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg">
                                <option value="">Todas las categorías</option>
                                @foreach($categoriasBusqueda as $categoria)
                                    <option value="{{ $categoria['id'] }}">{{ $categoria['nombre'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-gray-600">Subcategoría</label>
                            <select wire:model.live="subcategoriaBusqueda" class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg">
                                <option value="">Todas las subcategorías</option>
                                @foreach($subcategoriasBusqueda as $subcategoria)
                                    <option value="{{ $subcategoria['id'] }}">{{ $subcategoria['nombre'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-semibold text-gray-600">Disponibilidad</label>
                            <div class="flex items-center h-[38px] px-3 text-sm font-medium text-green-700 border border-green-200 rounded-lg bg-green-50">
                                ● Solo con existencia
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @forelse($resultadosBusqueda as $item)
                            @php
                                $stockBajo = $item['stock_disponible'] <= 5;
                            @endphp
                            <button wire:click="seleccionarProductoBusqueda({{ $item['producto_id'] }}, {{ $item['precio_venta_id'] }})"
                                    wire:key="producto-busqueda-{{ $item['precio_venta_id'] }}"
                                    style="--transfer-delay: {{ min($loop->index * 35, 245) }}ms"
                                    class="transfer-product-card transfer-action relative overflow-hidden text-left bg-white border-2 border-gray-200 rounded-xl hover:border-blue-400 hover:shadow-lg">
                                <div class="relative flex items-center justify-center h-32 overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100">
                                    @if($item['tiene_imagen'])
                                        <img src="{{ route('producto.imagen', ['producto' => $item['producto_id'], 'v' => \Carbon\Carbon::parse($item['updated_at'])->timestamp]) }}"
                                             alt="{{ $item['nombre'] }}"
                                             class="object-cover w-full h-full">
                                    @else
                                        <span class="text-4xl text-gray-300">📦</span>
                                    @endif
                                    <span class="absolute px-2 py-1 text-xs font-bold text-white rounded top-2 right-2 {{ $stockBajo ? 'bg-amber-500' : 'bg-green-600' }}">
                                        Stock: {{ $item['stock_disponible'] }}
                                    </span>
                                </div>

                                <div class="p-4">
                                    <h4 class="min-h-[40px] text-sm font-bold text-gray-900 line-clamp-2">{{ $item['nombre'] }}</h4>
                                    <div class="mt-2">
                                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">
                                            {{ $item['unidad_nombre'] }}
                                            @if($item['cantidad_presentacion'] > 1)
                                                · {{ $item['cantidad_presentacion'] }} unids.
                                            @endif
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-indigo-700">
                                        Coincide con Valencia: producto #{{ $item['producto_id_valencia'] }}
                                        · unidad #{{ $item['unidad_medida_id_valencia'] }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-gray-400">
                                        Unidad recibida en traslado #{{ $item['ultimo_traslado_valencia'] }}
                                    </p>
                                    @if($item['presentacion_descripcion'])
                                        <p class="mt-2 text-xs text-gray-500">{{ $item['presentacion_descripcion'] }}</p>
                                    @endif
                                    <p class="mt-2 text-xs font-mono text-gray-500">{{ $item['codigo_barra'] ?: 'Sin código de barras' }}</p>

                                    <div class="flex flex-wrap gap-1.5 mt-3">
                                        @if($item['subcategoria_nombre'])
                                            <span class="px-2 py-1 text-xs text-blue-700 rounded bg-blue-50">{{ $item['subcategoria_nombre'] }}</span>
                                        @endif
                                        @if($item['marca_nombre'])
                                            <span class="px-2 py-1 text-xs text-gray-700 bg-gray-100 rounded">{{ $item['marca_nombre'] }}</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-between pt-3 mt-3 border-t border-gray-100">
                                        <div>
                                            <p class="text-xs text-gray-400">Precio de referencia</p>
                                            <p class="text-lg font-bold text-green-600">L. {{ number_format($item['precio'], 2) }}</p>
                                        </div>
                                        <span class="flex items-center justify-center w-9 h-9 text-lg font-bold text-white bg-blue-600 rounded-full">+</span>
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div class="py-12 text-center md:col-span-2 lg:col-span-3">
                                <div class="mb-3 text-5xl text-gray-300">⌕</div>
                                <p class="font-medium text-gray-600">No se encontraron productos con existencia</p>
                                <p class="mt-1 text-sm text-gray-400">Pruebe con otro nombre, código o filtro.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t bg-gray-50">
                    <p class="text-sm text-gray-500">
                        {{ $totalBusqueda }} {{ $totalBusqueda === 1 ? 'presentación disponible' : 'presentaciones disponibles' }}
                    </p>
                    <div class="flex items-center gap-2">
                        <button wire:click="paginaAnteriorBusqueda"
                                @disabled($paginaBusqueda <= 1)
                                class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg disabled:opacity-40">Anterior</button>
                        <span class="px-2 text-sm text-gray-600">
                            {{ $paginaBusqueda }} / {{ max(1, (int) ceil($totalBusqueda / $resultadosPorPagina)) }}
                        </span>
                        <button wire:click="paginaSiguienteBusqueda"
                                @disabled($paginaBusqueda >= max(1, (int) ceil($totalBusqueda / $resultadosPorPagina)))
                                class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg disabled:opacity-40">Siguiente</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($mostrarDetalle && $trasladoSeleccionado)
        <div class="transfer-modal-backdrop">
            <div class="transfer-modal" style="width: min(760px, 100%);">
                <div class="flex items-start justify-between px-6 py-5 border-b">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-blue-600 uppercase">Revisión del envío</p>
                        <h3 class="mt-1 text-xl font-bold text-gray-900">{{ $trasladoSeleccionado->codigo }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ trim($trasladoSeleccionado->bodegaOrigen->nombre ?? 'Paperland') }} → Valencia</p>
                    </div>
                    <button wire:click="cerrarDetalle" class="p-1 text-2xl leading-none text-gray-400 rounded hover:bg-gray-100">&times;</button>
                </div>

                <div class="transfer-modal-body p-6">
                    @if(session()->has('success'))
                        <div class="transfer-alert p-3 mb-4 text-sm text-blue-800 border border-blue-200 rounded-lg bg-blue-50">{{ session('success') }}</div>
                    @endif

                    @if($trasladoSeleccionado->estado === 'borrador')
                        <div class="flex gap-3 p-4 mb-5 border border-amber-200 rounded-lg bg-amber-50">
                            <span class="text-xl">⚠️</span>
                            <div>
                                <p class="text-sm font-semibold text-amber-900">El inventario todavía no se ha descontado</p>
                                <p class="mt-1 text-xs text-amber-800">Confirme el despacho solamente cuando los productos hayan salido físicamente de Paperland.</p>
                            </div>
                        </div>
                    @endif

                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full text-sm">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Producto Valencia</th>
                                    <th class="px-4 py-3 text-left">Producto</th>
                                    <th class="px-4 py-3 text-left">Unidad autorizada</th>
                                    <th class="px-4 py-3 text-right">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($trasladoSeleccionado->detalles as $detalle)
                                    <tr class="transfer-summary-row">
                                        <td class="px-4 py-3 font-mono text-gray-700">#{{ $detalle->producto_externo_id }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $detalle->producto->nombre }}</td>
                                        <td class="px-4 py-3 text-gray-500">
                                            {{ $detalle->precioVenta?->unidadMedida?->nombre }}
                                            {{ $detalle->precioVenta?->descripcion }}
                                        </td>
                                        <td class="px-4 py-3 font-bold text-right">{{ $detalle->cantidad_solicitada }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 font-semibold text-right text-gray-700">Total de unidades enviadas</td>
                                    <td class="px-4 py-3 text-lg font-bold text-right text-gray-900">{{ number_format($trasladoSeleccionado->detalles->sum('cantidad_solicitada')) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($trasladoSeleccionado->comentario)
                        <div class="p-3 mt-4 text-sm text-gray-700 border border-gray-200 rounded-lg">
                            <span class="font-semibold">Nota:</span> {{ $trasladoSeleccionado->comentario }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 border-t bg-gray-50">
                    @if($trasladoSeleccionado->estado === 'borrador')
                        <button wire:click="cancelar({{ $trasladoSeleccionado->id }})"
                                wire:confirm="¿Desea cancelar este envío?"
                                class="transfer-action px-3 py-2 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50">
                            Cancelar envío
                        </button>
                        <div class="flex gap-2">
                            <button wire:click="cerrarDetalle" class="transfer-action px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Despachar después</button>
                            <button wire:click="despachar({{ $trasladoSeleccionado->id }})"
                                    wire:confirm="¿Los productos ya salieron físicamente de Paperland? Al confirmar se descontarán del inventario."
                                    wire:loading.attr="disabled"
                                    wire:target="despachar({{ $trasladoSeleccionado->id }})"
                                    class="transfer-action px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700 hover:shadow-md disabled:opacity-50">
                                <span wire:loading.remove wire:target="despachar({{ $trasladoSeleccionado->id }})">Confirmar envío y descontar stock</span>
                                <span wire:loading wire:target="despachar({{ $trasladoSeleccionado->id }})" class="inline-flex items-center gap-2">
                                    <span class="block w-4 h-4 border-2 border-white rounded-full border-t-transparent animate-spin"></span>
                                    Descontando inventario...
                                </span>
                            </button>
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <span class="inline-flex w-2 h-2 bg-blue-500 rounded-full"></span>
                            Estado: <strong>{{ str_replace('_', ' ', ucfirst($trasladoSeleccionado->estado)) }}</strong>
                        </div>
                        <button wire:click="cerrarDetalle" class="transfer-action px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cerrar</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
