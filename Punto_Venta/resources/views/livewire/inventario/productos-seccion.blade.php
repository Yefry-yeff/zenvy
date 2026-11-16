{{--
    ============================================================================
    VISTA: PRODUCTOS POR SECCIÓN
    ============================================================================

    PROPÓSITO: Mostrar todos los productos que están almacenados en una sección específica

    FLUJO DE DATOS:
    1. Recibe $seccionId desde la URL
    2. ProductosSeccion.php carga la sección y sus productos
    3. Esta vista muestra la información jerárquica y la tabla de productos

    ESTRUCTURA JERÁRQUICA:
    Tienda → Bodega → Segmento → Sección → Productos

    DATOS PRINCIPALES:
    - $seccion: Información de la sección actual con relaciones cargadas
    - $productos: Colección de RecibidoBodega con productos y sus relaciones
    ============================================================================
--}}

<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO PARA LIVEWIRE --}}

    {{--
        ========================================================================
        SECCIÓN 1: ENCABEZADO DINÁMICO CON TEMA
        ========================================================================

        FUNCIONALIDAD:
        - Título principal con ícono
        - Información contextual de la sección actual
        - Botón de navegación para volver
        - Tema dinámico basado en preferencias del usuario

        DATOS MOSTRADOS:
        - Nombre de la bodega, segmento y sección
        - Navegación jerárquica visual
        ========================================================================
    --}}
    {{-- Productos de la Sección --}}
    <div class="overflow-hidden border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO PRINCIPAL -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t"
            {{-- SISTEMA DE TEMAS DINÁMICO - Los colores cambian según la preferencia del usuario --}}
            :class="{
                'bg-emerald-600': theme === 'verde',
                'bg-blue-600': theme === 'azul',
                'bg-gray-900': theme === 'oscuro',
                'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
            }"
        >
            <div>
                {{-- TÍTULO PRINCIPAL CON ÍCONO DESCRIPTIVO --}}
                <h5 class="mb-0 text-lg">
                    <i class="fas fa-boxes me-2"></i>Productos en Sección
                </h5>
            </div>

            {{-- NAVEGACIÓN - Botón para regresar a la vista anterior --}}
            <div class="flex gap-2">
                <button wire:click="volverASecciones"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <i class="fas fa-arrow-left"></i> Volver a Secciones
                </button>
            </div>
        </div>

        {{--
            ================================================================
            SECCIÓN 2: CONTENIDO PRINCIPAL
            ================================================================

            ESTRUCTURA:
            1. Información jerárquica de la sección
            2. Tabla de productos con todas sus relaciones

            PADDING: px-4 py-3 pt-0 card-body (estilo consistente con otras vistas)
            ================================================================
        --}}
        <!-- CONTENIDO PRINCIPAL -->
        <div class="px-4 py-3 pt-0 card-body">
            {{--
                ============================================================
                SUBSECCIÓN 2.1: INFORMACIÓN JERÁRQUICA DE LA SECCIÓN
                ============================================================

                PROPÓSITO: Mostrar la ubicación completa del contexto actual

                JERARQUÍA MOSTRADA:
                Tienda → Bodega → Segmento → Sección

                ORIGEN DE DATOS:
                $seccion viene de ProductosSeccion.php, cargado con:
                Seccion::with(['segmento.bodega.tienda'])

                RELACIONES NAVEGADAS:
                $seccion->segmento->bodega->tienda->denominacion_social
                $seccion->segmento->bodega->nombre
                $seccion->segmento->descripcion
                $seccion->descripcion
                ============================================================
            --}}
            <!-- Información de la Sección -->
            @if($seccion)
                <div class="p-3 mb-4 rounded bg-light">
                    <div class="row">
                        {{-- TIENDA - Nivel más alto de la jerarquía --}}
                        <div class="col-md-3">
                            <strong><i class="fas fa-store text-warning me-2"></i>Tienda:</strong><br>
                            {{-- ORIGEN: $seccion->segmento->bodega->tienda->denominacion_social --}}
                            {{ $seccion->segmento->bodega->tienda->denominacion_social ?? 'N/A' }}
                        </div>

                        {{-- BODEGA - Segundo nivel de la jerarquía --}}
                        <div class="col-md-3">
                            <strong><i class="fas fa-warehouse text-primary me-2"></i>Bodega:</strong><br>
                            {{-- ORIGEN: $seccion->segmento->bodega->nombre --}}
                            {{ $seccion->segmento->bodega->nombre }}
                        </div>

                        {{-- SEGMENTO - Tercer nivel de la jerarquía --}}
                        <div class="col-md-3">
                            <strong><i class="fas fa-layer-group text-success me-2"></i>Segmento:</strong><br>
                            {{-- ORIGEN: $seccion->segmento->descripcion --}}
                            {{ $seccion->segmento->descripcion }}
                        </div>

                        {{-- SECCIÓN - Nivel actual donde están los productos --}}
                        <div class="col-md-3">
                            <strong><i class="fas fa-cube text-info me-2"></i>Sección:</strong><br>
                            {{-- ORIGEN: $seccion->descripcion y $seccion->numeracion --}}
                            {{ $seccion->descripcion }} ({{ $seccion->numeracion }})
                        </div>
                    </div>
                </div>
            @endif

           {{--
                ============================================================
                SUBSECCIÓN 2.2: TABLA DE PRODUCTOS
                ============================================================

                PROPÓSITO: Mostrar todos los productos almacenados en esta sección

                ORIGEN DE DATOS:
                $productos viene de ProductosSeccion->obtenerProductos()
                Cada elemento es un RecibidoBodega con eager loading de:
                - producto.marca
                - producto.subcategoria.categoria
                - producto.subcategoria
                - producto.unidadMedidaCompra
                - producto.unidadMedidaVenta

                ESTRUCTURA:
                - Header con 11 columnas
                - Datos con @forelse para manejar casos vacíos
                - Responsive design con Bootstrap
                - Filas clickeables para editar stock
                ============================================================
            --}}

           <!-- Información sobre funcionalidad -->
           <div class="p-3 mb-3 border rounded bg-info bg-opacity-10 border-info">
               <div class="d-flex align-items-center">
                   <i class="fas fa-info-circle text-info me-2"></i>
                   <div>
                       <strong>Edición de Stock:</strong>
                       <small>Haga clic en cualquier fila para editar las cantidades de stock del producto. El stock en sección no puede exceder la cantidad del lote de compra.</small>
                   </div>
               </div>
           </div>

           <!-- Tabla de Productos -->
            <div class="table-responsive">
                {{-- ID: Para posible integración con JavaScript/DataTables en el futuro --}}
                <table id="productosSeccionTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    {{-- ENCABEZADO DE LA TABLA --}}
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>ID</th>                 {{-- producto.id --}}
                            <th>Producto</th>           {{-- producto.nombre + descripcion --}}
                            <th>Código</th>             {{-- producto.codigo_barra + codigo_estatal --}}
                            <th>Marca</th>              {{-- producto.marca.nombre --}}
                            <th>Categoría</th>          {{-- producto.subcategoria.categoria.nombre --}}
                            <th>U. Medida</th>          {{-- producto.unidadMedidaCompra.nombre --}}
                            <th class="bg-opacity-25 bg-warning"><i class="fas fa-cubes me-1"></i>Stock</th>   {{-- recibido.cantidad_inicial_seccion --}}
                            <th>F. Recibido</th>        {{-- recibido.fecha_recibido --}}
                            <th>F. Expiración</th>      {{-- recibido.fecha_expiracion --}}
                            <th>Precio Base</th>        {{-- producto.precio_base --}}
                            <th>Estado</th>             {{-- producto.estado_id --}}
                        </tr>
                    </thead>
                    <tbody>
                        {{--
                            ================================================
                            ITERACIÓN DE PRODUCTOS
                            ================================================

                            @forelse: Itera sobre $productos con manejo de casos vacíos
                            $recibido: Cada elemento es un modelo RecibidoBodega

                            EAGER LOADING DISPONIBLE:
                            - $recibido->producto (Modelo Producto)
                            - $recibido->producto->marca (Modelo Marca)
                            - $recibido->producto->subcategoria (Modelo Subcategoria)
                            - $recibido->producto->subcategoria->categoria (Modelo Categoria)
                            - $recibido->producto->unidadMedidaCompra (Modelo UnidadMedida)
                            ================================================
                        --}}
                        @forelse($productos as $recibido)
                            <tr class="text-center align-middle transition-colors duration-200 cursor-pointer hover:bg-blue-50 hover:shadow-sm"
                                wire:click="editarProducto({{ $recibido->id }})"
                                title="🖱️ Haga clic para editar el stock de este producto"
                                style="user-select: none;">
                                {{-- COLUMNA 1: ID DEL PRODUCTO --}}
                                <td>{{ $recibido->producto->id }}</td>
                                {{-- ORIGEN: RecibidoBodega->producto->id --}}

                                {{-- COLUMNA 2: INFORMACIÓN DEL PRODUCTO --}}
                                <td class="text-start">
                                    <div>
                                        {{-- NOMBRE PRINCIPAL --}}
                                        <strong>{{ $recibido->producto->nombre }}</strong><br>
                                        {{-- ORIGEN: RecibidoBodega->producto->nombre --}}

                                        {{-- DESCRIPCIÓN TRUNCADA --}}
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($recibido->producto->descripcion, 50) }}</small>
                                        {{-- ORIGEN: RecibidoBodega->producto->descripcion (limitada a 50 caracteres) --}}
                                    </div>
                                </td>

                                {{-- COLUMNA 3: CÓDIGOS DEL PRODUCTO --}}
                                <td>
                                    {{-- CÓDIGO DE BARRA (si existe) --}}
                                    @if($recibido->producto->codigo_barra)
                                        <span class="badge bg-primary">{{ $recibido->producto->codigo_barra }}</span><br>
                                        {{-- ORIGEN: RecibidoBodega->producto->codigo_barra --}}
                                    @endif

                                    {{-- CÓDIGO ESTATAL (si existe) --}}
                                    @if($recibido->producto->codigo_estatal)
                                        <span class="badge bg-secondary">{{ $recibido->producto->codigo_estatal }}</span>
                                        {{-- ORIGEN: RecibidoBodega->producto->codigo_estatal --}}
                                    @endif
                                </td>

                                {{-- COLUMNA 4: MARCA --}}
                                <td>{{ $recibido->producto->marca->nombre ?? 'Sin marca' }}</td>
                                {{-- ORIGEN: RecibidoBodega->producto->marca->nombre --}}
                                {{-- RELACIÓN: producto belongsTo marca --}}

                                {{-- COLUMNA 5: CATEGORÍA Y SUBCATEGORÍA --}}
                                <td>
                                    {{-- CATEGORÍA PRINCIPAL --}}
                                    {{ $recibido->producto->subcategoria->categoria->nombre ?? 'Sin categoría' }}<br>
                                    {{-- ORIGEN: RecibidoBodega->producto->subcategoria->categoria->nombre --}}
                                    {{-- RELACIÓN: producto->subcategoria->categoria (relación anidada) --}}

                                    {{-- SUBCATEGORÍA --}}
                                    <small class="text-muted">{{ $recibido->producto->subcategoria->txt_nombre ?? 'N/A' }}</small>
                                    {{-- ORIGEN: RecibidoBodega->producto->subcategoria->txt_nombre --}}
                                </td>

                                {{-- COLUMNA 6: UNIDAD DE MEDIDA --}}
                                <td>
                                    <span class="badge bg-info">
                                        {{ $recibido->producto->unidadMedidaCompra->nombre ?? 'N/A' }}
                                        {{-- ORIGEN: RecibidoBodega->producto->unidadMedidaCompra->nombre --}}
                                        {{-- RELACIÓN: producto belongsTo unidadMedidaCompra --}}
                                    </span>
                                </td>

                                {{-- COLUMNA 7: STOCK CON CÓDIGO DE COLORES --}}
                                <td>
                                    {{-- LÓGICA DE COLORES: Verde >10, Amarillo >0, Rojo =0 --}}
                                    <span class="badge {{ $recibido->cantidad_disponible > 10 ? 'bg-success' : ($recibido->cantidad_disponible > 0 ? 'bg-warning' : 'bg-danger') }}">
                                        {{ $recibido->cantidad_disponible }}
                                        {{-- ORIGEN: RecibidoBodega->cantidad_inicial_seccion --}}
                                    </span>
                                </td>
                                {{-- COLUMNA 8: FECHA DE RECIBIDO --}}
                                <td>{{ $this->formatearFecha($recibido->fecha_recibido) }}</td>
                                {{-- ORIGEN: RecibidoBodega->fecha_recibido --}}
                                {{-- PROCESAMIENTO: Método formatearFecha() convierte a d/m/Y --}}

                                {{-- COLUMNA 9: FECHA DE EXPIRACIÓN CON VALIDACIÓN --}}
                                <td>
                                    @if($recibido->fecha_expiracion)
                                        {{-- LÓGICA DE COLORES: Rojo si ya expiró, Verde si no --}}
                                        <span class="badge {{ \Carbon\Carbon::parse($recibido->fecha_expiracion)->isPast() ? 'bg-danger' : 'bg-success' }}">
                                            {{ $this->formatearFecha($recibido->fecha_expiracion) }}
                                            {{-- ORIGEN: RecibidoBodega->fecha_expiracion --}}
                                            {{-- VALIDACIÓN: Carbon verifica si la fecha ya pasó --}}
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                        {{-- CASO: Producto sin fecha de expiración --}}
                                    @endif
                                </td>

                                {{-- COLUMNA 10: PRECIO BASE FORMATEADO --}}
                                <td class="text-end">L. {{ number_format($recibido->producto->precio_base ?? 0, 2) }}</td>
                                {{-- ORIGEN: RecibidoBodega->producto->precio_base --}}
                                {{-- PROCESAMIENTO: number_format() con 2 decimales --}}
                                {{-- FORMATO: L. 1,234.56 --}}

                                {{-- COLUMNA 11: ESTADO CON MÉTODOS DE UTILIDAD --}}
                                <td>
                                    <span class="{{ $this->obtenerEstadoClase($recibido->producto->estado_id) }}">
                                        {{ $this->obtenerEstadoTexto($recibido->producto->estado_id) }}
                                        {{-- ORIGEN: RecibidoBodega->producto->estado_id --}}
                                        {{-- PROCESAMIENTO: Métodos del componente Livewire --}}
                                        {{-- obtenerEstadoTexto(): 1='Activo', 0='Inactivo' --}}
                                        {{-- obtenerEstadoClase(): 1='badge bg-success', 0='badge bg-secondary' --}}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            {{-- CASO VACÍO: Cuando no hay productos en la sección --}}
                            <tr>
                                <td colspan="11" class="py-4 text-center text-muted">No hay productos disponibles en esta sección.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- FIN DE LA TABLA DE PRODUCTOS --}}

        </div>
        {{-- FIN DEL CONTENIDO PRINCIPAL --}}

        {{--
            ================================================================
            SECCIÓN 3: MODALES DE RETROALIMENTACIÓN
            ================================================================

            PROPÓSITO: Mostrar mensajes de éxito o error al usuario

            FUNCIONAMIENTO:
            - Se muestran condicionalmente basado en variables Livewire
            - $mostrarModalExito y $mostrarModalError controlan la visibilidad
            - Los mensajes vienen de $mensajeModalExito y $mensajeModalError

            EVENTOS:
            - wire:click llama métodos Livewire para cerrar los modales
            ================================================================
        --}}

        {{-- MODAL DE ÉXITO --}}
        @if($mostrarModalExito)
            {{-- ORIGEN: Variable Livewire $mostrarModalExito (boolean) --}}
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="text-white modal-header bg-success">
                            <h5 class="modal-title">
                                <i class="fas fa-check-circle me-2"></i>¡Éxito!
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="text-center">
                                <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">{{ $mensajeModalExito }}</p>
                                {{-- ORIGEN: Variable Livewire $mensajeModalExito (string) --}}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" wire:click="cerrarModalExito" class="btn btn-success">
                                {{-- ACCIÓN: Llama método cerrarModalExito() en ProductosSeccion.php --}}
                                <i class="fas fa-check me-2"></i>Entendido
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- MODAL DE ERROR --}}
        @if($mostrarModalError)
            {{-- ORIGEN: Variable Livewire $mostrarModalError (boolean) --}}
            <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="text-white modal-header bg-danger">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle me-2"></i>Error
                            </h5>
                        </div>
                        <div class="modal-body">
                            <div class="text-center">
                                <i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0">{{ $mensajeModalError }}</p>
                                {{-- ORIGEN: Variable Livewire $mensajeModalError (string) --}}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" wire:click="cerrarModalError" class="btn btn-danger">
                                {{-- ACCIÓN: Llama método cerrarModalError() en ProductosSeccion.php --}}
                                <i class="fas fa-times me-2"></i>Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    {{-- FIN DEL CONTENEDOR PRINCIPAL --}}

    {{--
        ====================================================================
        SECCIÓN 4: ESTILOS CSS Y MENSAJES DE SESIÓN
        ====================================================================

        PROPÓSITO:
        1. Estilos CSS personalizados para la vista
        2. Manejo de mensajes de sesión de Laravel

        ESTILOS INCLUIDOS:
        - Estilos para modales
        - Mejoras visuales para badges en tablas
        - Diseño responsive para móviles

        MENSAJES DE SESIÓN:
        - session('mensaje'): Mensajes de éxito
        - session('error'): Mensajes de error
        - Alpine.js maneja la auto-ocultación
        ====================================================================
    --}}
    <!-- Estilos CSS adicionales -->
    <style>
        {{-- ESTILOS PARA MODALES --}}
        .modal.show {
            display: block !important;
        }

        {{-- MEJORAS VISUALES PARA BADGES EN TABLAS --}}
        .table td .badge {
            font-size: 0.75rem;
        }

        {{-- DISEÑO RESPONSIVE PARA MÓVILES --}}
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>

    {{-- MENSAJE DE ÉXITO DE SESIÓN --}}
    @if (session()->has('mensaje'))
        {{-- ORIGEN: session('mensaje') - Flash message de Laravel --}}
        <div x-data="{ show: true }" x-show="show"
             {{-- EVENTOS ALPINE.JS: Se oculta al hacer clic, teclear o mover el mouse --}}
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="mt-3 mb-0 transition-opacity duration-300 alert alert-success">
            {{ session('mensaje') }}
        </div>
    @endif

    {{-- MENSAJE DE ERROR DE SESIÓN --}}
    @if (session()->has('error'))
        {{-- ORIGEN: session('error') - Flash message de Laravel --}}
        <div x-data="{ show: true }" x-show="show"
             {{-- EVENTOS ALPINE.JS: Se oculta al hacer clic, teclear o mover el mouse --}}
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="mt-3 mb-0 transition-opacity duration-300 alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

</div>
{{--
    ============================================================================
    FIN DE LA VISTA PRODUCTOS-SECCION
    ============================================================================

    RESUMEN DEL FLUJO COMPLETO:

    1. ENTRADA: URL con $seccionId
    2. LIVEWIRE: ProductosSeccion.php procesa y carga datos
    3. VISTA: Esta vista recibe $seccion y $productos
    4. RENDERIZADO: Se muestra la jerarquía y tabla de productos
    5. INTERACCIÓN: Usuario puede navegar y ver modales

    DATOS PRINCIPALES UTILIZADOS:
    - $seccion: Información jerárquica (Tienda→Bodega→Segmento→Sección)
    - $productos: Colección de RecibidoBodega con eager loading completo

    TECNOLOGÍAS INTEGRADAS:
    - Laravel Livewire: Componente reactivo
    - Alpine.js: Interactividad del frontend
    - Bootstrap: Estilos y componentes UI
    - Blade: Motor de plantillas
    - Font Awesome: Iconografía
    ============================================================================
--}}
