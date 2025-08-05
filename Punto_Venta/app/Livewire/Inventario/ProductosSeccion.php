<?php

/**
 * ============================================================================
 * COMPONENTE LIVEWIRE: PRODUCTOS POR SECCIÓN
 * ============================================================================
 * 
 * PROPÓSITO:
 * Gestiona la visualización de productos almacenados en una sección específica
 * de bodega, mostrando información jerárquica completa y datos detallados.
 * 
 * FUNCIONALIDADES PRINCIPALES:
 * 1. Carga y muestra productos de una sección específica
 * 2. Navegación jerárquica (Tienda→Bodega→Segmento→Sección)
 * 3. Filtrado y búsqueda de productos
 * 4. Manejo de modales para retroalimentación
 * 5. Formateo de datos para presentación
 * 
 * FLUJO DE DATOS:
 * URL($seccionId) → mount() → cargarDatos() → render() → obtenerProductos() → Vista
 * 
 * RELACIONES UTILIZADAS:
 * - Seccion → Segmento → Bodega → Tienda
 * - RecibidoBodega → Producto → Marca/Subcategoria/UnidadMedida
 * 
 * @package App\Livewire\Inventario
 * @author Johann Ruiz
 * @version 1.0
 * ============================================================================
 */

namespace App\Livewire\Inventario;

// ===== IMPORTACIONES =====
use Livewire\Component;          // Clase base para componentes Livewire
use App\Models\Seccion;          // Modelo para gestión de secciones de bodega
use App\Models\RecibidoBodega;   // Modelo principal - productos recibidos en bodega
use App\Models\Producto;         // Modelo de productos (usado para referencia)
use Illuminate\Support\Facades\Log;   // Sistema de logging de Laravel
use Illuminate\Support\Facades\Auth;  // Sistema de autenticación

/**
 * ============================================================================
 * CLASE PRINCIPAL: ProductosSeccion
 * ============================================================================
 * 
 * Extiende Livewire\Component para funcionalidad reactiva
 * Maneja toda la lógica de negocio para mostrar productos por sección
 */

class ProductosSeccion extends Component
{
    // =========================================================================
    // PROPIEDADES DEL COMPONENTE
    // =========================================================================
    
    /**
     * ID de la sección actual - Recibido desde la URL
     * @var int
     */
    public $seccionId;
    
    /**
     * Objeto Seccion con relaciones cargadas
     * Contiene la jerarquía completa: Seccion→Segmento→Bodega→Tienda
     * @var \App\Models\Seccion
     */
    public $seccion;

    // ===== PROPIEDADES DE FILTRADO Y BÚSQUEDA =====
    
    /**
     * Término de búsqueda para filtrar productos
     * Busca en: nombre, descripción, código_barra, código_estatal
     * @var string
     */
    public $buscar = '';
    
    /**
     * Filtro por estado de producto (activo/inactivo)
     * @var string
     */
    public $filtroEstado = '';

    // ===== PROPIEDADES PARA MODALES DE RETROALIMENTACIÓN =====
    
    /**
     * Controla la visibilidad del modal de éxito
     * @var bool
     */
    public $mostrarModalExito = false;
    
    /**
     * Controla la visibilidad del modal de error
     * @var bool
     */
    public $mostrarModalError = false;
    
    /**
     * Mensaje a mostrar en el modal de éxito
     * @var string
     */
    public $mensajeModalExito = '';
    
    /**
     * Mensaje a mostrar en el modal de error
     * @var string
     */
    public $mensajeModalError = '';

    /**
     * Configuración para persistir filtros en la URL
     * Permite que los filtros se mantengan al recargar la página
     * @var array
     */
    protected $queryString = [
        'buscar' => ['except' => ''],         // Persiste búsqueda si no está vacía
        'filtroEstado' => ['except' => '']    // Persiste filtro de estado si no está vacío
    ];

    // =========================================================================
    // MÉTODOS DEL CICLO DE VIDA DEL COMPONENTE
    // =========================================================================

    /**
     * MÉTODO DE INICIALIZACIÓN
     * 
     * Se ejecuta automáticamente cuando se instancia el componente
     * Recibe el ID de la sección desde la URL y carga los datos iniciales
     * 
     * @param int $seccionId ID de la sección a mostrar
     * @return void
     */
    public function mount($seccionId)
    {
        $this->seccionId = $seccionId;    // Almacena el ID recibido
        $this->cargarDatos();             // Carga la información de la sección
    }

    /**
     * CARGA DE DATOS INICIALES
     * 
     * Carga la sección con todas sus relaciones jerárquicas usando Eager Loading
     * Maneja errores y muestra mensaje en caso de problemas
     * 
     * RELACIONES CARGADAS:
     * - segmento: Segmento al que pertenece la sección
     * - segmento.bodega: Bodega que contiene el segmento
     * - segmento.bodega.tienda: Tienda que posee la bodega
     * 
     * @return void
     */
    private function cargarDatos()
    {
        try {
            // EAGER LOADING: Carga sección con toda la jerarquía de una sola consulta
            $this->seccion = Seccion::with(['segmento.bodega.tienda'])->findOrFail($this->seccionId);
            
        } catch (\Exception $e) {
            // MANEJO DE ERRORES: Log del error y notificación al usuario
            Log::error('Error al cargar datos de la sección', [
                'seccion_id' => $this->seccionId,
                'mensaje' => $e->getMessage(),
                'usuario_id' => Auth::id(),      // ID del usuario actual
                'timestamp' => now()             // Timestamp del error
            ]);
            
            // Muestra modal de error al usuario
            $this->mostrarError('Error al cargar los datos de la sección');
        }
    }

    /**
     * MÉTODO DE RENDERIZADO
     * 
     * Se ejecuta automáticamente en cada actualización del componente
     * Obtiene los productos y pasa los datos a la vista Blade
     * 
     * @return \Illuminate\View\View Vista con datos de productos
     */
    public function render()
    {
        // Obtiene productos filtrados según criterios actuales
        $productos = $this->obtenerProductos();

        // Retorna vista con datos para el frontend
        return view('livewire.inventario.productos-seccion', [
            'productos' => $productos    // Colección de RecibidoBodega
        ]);
    }

    // =========================================================================
    // MÉTODOS DE CONSULTA Y OBTENCIÓN DE DATOS
    // =========================================================================

    /**
     * OBTENCIÓN DE PRODUCTOS CON FILTROS
     * 
     * Método principal que construye la consulta de productos con:
     * - Eager Loading de todas las relaciones necesarias
     * - Filtros de búsqueda por texto
     * - Filtros por estado
     * - Ordenación por fecha
     * - Solo productos con stock disponible
     * 
     * MODELO BASE: RecibidoBodega
     * ¿Por qué RecibidoBodega y no Producto?
     * - RecibidoBodega contiene la relación específica con la sección
     * - Incluye datos de stock, fechas de recepción y expiración
     * - Un mismo producto puede estar en múltiples secciones
     * 
     * @return \Illuminate\Support\Collection Colección de RecibidoBodega
     */
    private function obtenerProductos()
    {
        try {
            // CONSTRUCCIÓN DE LA CONSULTA BASE
            $query = RecibidoBodega::with([
                // EAGER LOADING: Carga todas las relaciones necesarias de una vez
                // Evita el problema N+1 (múltiples consultas por cada producto)
                
                'producto.marca',                    // Producto → Marca
                'producto.subcategoria.categoria',   // Producto → Subcategoría → Categoría
                'producto.subcategoria',            // Producto → Subcategoría
                'producto.unidadMedidaCompra',      // Producto → Unidad de Medida Compra
                'producto.unidadMedidaVenta'        // Producto → Unidad de Medida Venta
            ])
            ->where('seccion_id', $this->seccionId)           // Solo de esta sección
            ->where('cantidad_inicial_seccion', '>', 0);      // Solo con stock disponible

            // ===== APLICACIÓN DE FILTROS =====

            // FILTRO DE BÚSQUEDA POR TEXTO
            if (!empty($this->buscar)) {
                $query->whereHas('producto', function($q) {
                    // whereHas: Filtra RecibidoBodega basado en condiciones del Producto relacionado
                    $q->where('nombre', 'like', '%' . $this->buscar . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->buscar . '%')
                      ->orWhere('codigo_barra', 'like', '%' . $this->buscar . '%')
                      ->orWhere('codigo_estatal', 'like', '%' . $this->buscar . '%');
                });
            }

            // FILTRO POR ESTADO DEL PRODUCTO
            if ($this->filtroEstado !== '') {
                $query->whereHas('producto', function($q) {
                    $q->where('estado_id', $this->filtroEstado);
                });
            }

            // ORDENACIÓN DE RESULTADOS
            return $query->orderBy('fecha_recibido', 'desc')    // Más recientes primero
                        ->orderBy('id', 'desc')                 // Desempate por ID
                        ->get();                                // Ejecuta consulta y obtiene Collection

        } catch (\Exception $e) {
            // MANEJO DE ERRORES EN CONSULTAS
            Log::error('Error al obtener productos de la sección', [
                'seccion_id' => $this->seccionId,
                'mensaje' => $e->getMessage(),
                'filtros' => [
                    'buscar' => $this->buscar,
                    'filtroEstado' => $this->filtroEstado
                ],
                'usuario_id' => Auth::id(),
                'timestamp' => now()
            ]);

            // Retorna colección vacía en caso de error (evita crasheo)
            return collect();
        }
    }

    // =========================================================================
    // MÉTODOS DE NAVEGACIÓN JERÁRQUICA
    // =========================================================================

    /**
     * NAVEGACIÓN: VOLVER A SECCIONES
     * 
     * Navega de vuelta a la vista de secciones del segmento actual
     * Utiliza el sistema de eventos de Livewire para comunicación entre componentes
     * 
     * PARÁMETROS ENVIADOS:
     * - bodegaId: Para mantener contexto de la bodega
     * - segmentoId: Para mostrar secciones del segmento actual
     * 
     * @return void
     */
    public function volverASecciones()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Secciones', parametros: [
            'bodegaId' => $this->seccion->segmento->bodega->id,     // ID de la bodega
            'segmentoId' => $this->seccion->segmento->id            // ID del segmento
        ]);
    }

    /**
     * NAVEGACIÓN: VOLVER A SEGMENTOS
     * 
     * Navega a la vista de segmentos de la bodega actual
     * Útil para navegación rápida saltando un nivel
     * 
     * @return void
     */
    public function volverASegmentos()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Segmentos', parametros: [
            'bodegaId' => $this->seccion->segmento->bodega->id      // ID de la bodega
        ]);
    }

    /**
     * NAVEGACIÓN: VOLVER A BODEGAS
     * 
     * Navega a la vista principal de bodegas
     * Regresa al nivel más alto de la jerarquía
     * 
     * @return void
     */
    public function volverABodegas()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Bodegas');
    }

    // =========================================================================
    // MÉTODOS DE FILTRADO Y BÚSQUEDA
    // =========================================================================

    /**
     * LIMPIAR TODOS LOS FILTROS
     * 
     * Resetea todos los filtros a sus valores por defecto
     * Útil para mostrar todos los productos sin restricciones
     * 
     * @return void
     */
    public function limpiarFiltros()
    {
        $this->buscar = '';          // Limpia término de búsqueda
        $this->filtroEstado = '';    // Limpia filtro de estado
        // Al cambiar estas propiedades, Livewire re-renderiza automáticamente
    }

    /**
     * LISTENER: ACTUALIZACIÓN DE BÚSQUEDA
     * 
     * Se ejecuta automáticamente cuando cambia la propiedad $buscar
     * Livewire detecta el cambio y re-ejecuta render() automáticamente
     * 
     * @return void
     */
    public function updatedBuscar()
    {
        // Livewire maneja la actualización automáticamente
        // Este método existe para futuras extensiones o validaciones
    }

    /**
     * LISTENER: ACTUALIZACIÓN DE FILTRO DE ESTADO
     * 
     * Se ejecuta automáticamente cuando cambia la propiedad $filtroEstado
     * Livewire detecta el cambio y re-ejecuta render() automáticamente
     * 
     * @return void
     */
    public function updatedFiltroEstado()
    {
        // Livewire maneja la actualización automáticamente
        // Este método existe para futuras extensiones o validaciones
    }

    // =========================================================================
    // MÉTODOS DE GESTIÓN DE MODALES
    // =========================================================================

    /**
     * MOSTRAR MODAL DE ÉXITO
     * 
     * Configura y muestra un modal de éxito con mensaje personalizado
     * Utilizado para notificar operaciones exitosas al usuario
     * 
     * @param string $mensaje Mensaje a mostrar en el modal
     * @return void
     */
    public function mostrarExito($mensaje)
    {
        $this->mensajeModalExito = $mensaje;    // Establece el mensaje
        $this->mostrarModalExito = true;        // Hace visible el modal
        // Livewire re-renderiza automáticamente y muestra el modal
    }

    /**
     * MOSTRAR MODAL DE ERROR
     * 
     * Configura y muestra un modal de error con mensaje personalizado
     * Utilizado para notificar errores o problemas al usuario
     * 
     * @param string $mensaje Mensaje a mostrar en el modal
     * @return void
     */
    public function mostrarError($mensaje)
    {
        $this->mensajeModalError = $mensaje;    // Establece el mensaje
        $this->mostrarModalError = true;        // Hace visible el modal
        // Livewire re-renderiza automáticamente y muestra el modal
    }

    /**
     * CERRAR MODAL DE ÉXITO
     * 
     * Oculta el modal de éxito
     * Llamado desde el frontend cuando el usuario hace clic en "Entendido"
     * 
     * @return void
     */
    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;       // Oculta el modal
        // Opcionalmente se podría limpiar el mensaje
        // $this->mensajeModalExito = '';
    }

    /**
     * CERRAR MODAL DE ERROR
     * 
     * Oculta el modal de error
     * Llamado desde el frontend cuando el usuario hace clic en "Cerrar"
     * 
     * @return void
     */
    public function cerrarModalError()
    {
        $this->mostrarModalError = false;       // Oculta el modal
        // Opcionalmente se podría limpiar el mensaje
        // $this->mensajeModalError = '';
    }

    // =========================================================================
    // MÉTODOS DE UTILIDAD Y FORMATEO
    // =========================================================================

    /**
     * OBTENER TEXTO DEL ESTADO
     * 
     * Convierte el ID numérico del estado a texto legible para el usuario
     * Centraliza la lógica de mapeo de estados
     * 
     * MAPEO DE ESTADOS:
     * - 1 = 'Activo'    (producto disponible)
     * - 0 = 'Inactivo'  (producto no disponible)
     * 
     * @param int $estado ID del estado (0 o 1)
     * @return string Texto del estado
     */
    public function obtenerEstadoTexto($estado)
    {
        return $estado == 1 ? 'Activo' : 'Inactivo';
    }

    /**
     * OBTENER CLASE CSS DEL ESTADO
     * 
     * Convierte el ID numérico del estado a clases CSS apropiadas
     * Proporciona feedback visual consistente en toda la aplicación
     * 
     * MAPEO DE CLASES:
     * - 1 = 'badge bg-success'   (verde para activo)
     * - 0 = 'badge bg-secondary' (gris para inactivo)
     * 
     * @param int $estado ID del estado (0 o 1)
     * @return string Clases CSS para el badge
     */
    public function obtenerEstadoClase($estado)
    {
        return $estado == 1 ? 'badge bg-success' : 'badge bg-secondary';
    }

    /**
     * FORMATEAR FECHA PARA DISPLAY
     * 
     * Convierte fechas de base de datos a formato legible en español
     * Maneja valores nulos de forma segura
     * 
     * FORMATO DE ENTRADA: Y-m-d (2024-12-31)
     * FORMATO DE SALIDA:  d/m/Y (31/12/2024)
     * 
     * @param string|null $fecha Fecha en formato Y-m-d
     * @return string Fecha formateada o 'N/A' si es null
     */
    public function formatearFecha($fecha)
    {
        return $fecha ? \Carbon\Carbon::parse($fecha)->format('d/m/Y') : 'N/A';
    }

    /**
     * FORMATEAR MONEDA PARA DISPLAY
     * 
     * Convierte valores numéricos a formato de moneda hondureña
     * Proporciona formato consistente en toda la aplicación
     * 
     * FORMATO: L. 1,234.56
     * - Prefijo: L. (Lempiras)
     * - Separador de miles: coma (,)
     * - Separador decimal: punto (.)
     * - Decimales: 2 dígitos
     * 
     * @param float $monto Monto numérico
     * @return string Monto formateado como moneda
     */
    public function formatearMoneda($monto)
    {
        return 'L. ' . number_format($monto, 2, '.', ',');
    }
}

/**
 * ============================================================================
 * DOCUMENTACIÓN TÉCNICA ADICIONAL
 * ============================================================================
 * 
 * PATRONES UTILIZADOS:
 * - Repository Pattern: A través de modelos Eloquent
 * - Observer Pattern: Listeners de Livewire (updated* methods)
 * - Strategy Pattern: Diferentes métodos de formateo según el tipo de dato
 * 
 * PERFORMANCE OPTIMIZATIONS:
 * - Eager Loading: Previene consultas N+1
 * - Query Filtering: Filtros aplicados a nivel de base de datos
 * - Collection Caching: Uso de collect() para manejo eficiente de resultados
 * 
 * SEGURIDAD:
 * - Validación de entrada: findOrFail para IDs
 * - Escape de SQL: Uso de parámetros bound en consultas like
 * - Error Handling: Try-catch con logging detallado
 * 
 * MANTENIBILIDAD:
 * - Separación de responsabilidades: Métodos específicos para cada función
 * - Documentación inline: Comentarios explicativos detallados
 * - Naming Conventions: Nombres descriptivos y consistentes
 * 
 * EXTENSIBILIDAD:
 * - Métodos modulares: Fácil agregar nuevos filtros o formatos
 * - Event System: Comunicación desacoplada entre componentes
 * - Interface Consistency: Patrones reutilizables en otros componentes
 * ============================================================================
 */
