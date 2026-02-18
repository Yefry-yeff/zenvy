<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\IdZenvyValencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\SincronizacionProductosService;

class SincronizacionComprasService
{
    private $conexionValencia;
    private $conexionZenvy;
    private $sincronizacionProductos;

    public function __construct()
    {
        $this->conexionValencia = DB::connection('profac_app');
        $this->conexionZenvy = DB::connection(); // Usar conexión por defecto (db_zenvy)
        $this->sincronizacionProductos = new SincronizacionProductosService();
    }

    /**
     * Sincronizar compras desde Valencia - solo nuevas compras
     */
    public function sincronizarComprasValencia()
    {
        try {
            // Obtener compras desde Valencia usando el script proporcionado
            $comprasValencia = $this->obtenerComprasDesdeValencia();

            if (empty($comprasValencia)) {
                return [
                    'success' => true,
                    'estadisticas' => [
                        'compras_nuevas' => 0,
                        'productos_sincronizados' => 0,
                        'total_procesadas' => 0,
                        'errores' => 0,
                        'productos_migrados' => 0,
                        'productos_fallidos' => []
                    ],
                    'mensaje' => 'No hay nuevas compras para sincronizar'
                ];
            }

            $estadisticas = [
                'compras_nuevas' => 0,
                'productos_sincronizados' => 0,
                'total_procesadas' => 0,
                'errores' => 0,
                'productos_migrados' => 0,
                'productos_fallidos' => []
            ];

            // Agrupar por número de factura para crear las compras
            $comprasAgrupadas = collect($comprasValencia)->groupBy('numero_factura');

            foreach ($comprasAgrupadas as $numeroFactura => $productosCompra) {
                try {
                    // Validar número de factura (viene de Valencia: puede ser traslado_id o compra_id)
                    if (empty($numeroFactura) || $numeroFactura === '') {
                        $primerProductoTmp = $productosCompra->first();
                        $fallback = $primerProductoTmp->compra_id ?? null;
                        
                        if ($fallback) {
                            $numeroFactura = $fallback;
                        } else {
                            // Omitir silenciosamente este grupo de productos
                            $estadisticas['errores']++;
                            continue;
                        }
                    }
                    // Verificar si la compra ya existe
                    $compraExistente = Compra::where('numero_factura', $numeroFactura)->first();

                    if ($compraExistente) {
                        // Si la compra existe y el campo user está vacío, actualizarlo con 'Valencia'
                        if (empty($compraExistente->user)) {
                            $compraExistente->user = 'Valencia';
                            $compraExistente->save();
                        }
                        continue;
                    }

                    // Primero validar que todos los productos existan o puedan ser migrados
                    $productosFallidos = [];
                    $productosMigrados = [];
                    
                    foreach ($productosCompra as $productoData) {
                        $idProductoZenvy = $this->obtenerIdProductoZenvy($productoData->producto_id_valencia);
                        
                        if (!$idProductoZenvy) {
                            // Intentar migrar el producto desde Valencia
                            try {
                                $resultadoMigracion = $this->sincronizacionProductos->sincronizarProducto($productoData->producto_id_valencia);
                                
                                if ($resultadoMigracion['success']) {
                                    $productosMigrados[] = [
                                        'id_valencia' => $productoData->producto_id_valencia,
                                        'id_zenvy' => $resultadoMigracion['id_zenvy'],
                                        'accion' => $resultadoMigracion['accion']
                                    ];
                                } else {
                                    // Obtener nombre del producto desde Valencia
                                    $nombreProducto = $this->obtenerNombreProductoValencia($productoData->producto_id_valencia);
                                    $productosFallidos[] = [
                                        'id_valencia' => $productoData->producto_id_valencia,
                                        'nombre' => $nombreProducto,
                                        'error' => $resultadoMigracion['mensaje']
                                    ];
                                }
                            } catch (\Exception $e) {
                                $nombreProducto = $this->obtenerNombreProductoValencia($productoData->producto_id_valencia);
                                $productosFallidos[] = [
                                    'id_valencia' => $productoData->producto_id_valencia,
                                    'nombre' => $nombreProducto,
                                    'error' => $e->getMessage()
                                ];
                            }
                        }
                    }
                    
                    // Si hay productos que no se pudieron migrar, no crear la compra
                    if (!empty($productosFallidos)) {
                        $estadisticas['errores']++;
                        
                        // Agregar información del número de traslado/compra para cada producto fallido
                        foreach ($productosFallidos as &$productoFallido) {
                            $productoFallido['numero_factura'] = $numeroFactura;
                            $productoFallido['tipo_origen'] = $primerProducto->tipo_origen ?? 'COMPRA';
                        }
                        
                        $estadisticas['productos_fallidos'] = array_merge($estadisticas['productos_fallidos'], $productosFallidos);
                        
                        $nombresProductos = collect($productosFallidos)->pluck('nombre')->join(', ');
                        
                        // Registrar en bitácora
                        $this->registrarErrorEnBitacora('ERROR_MIGRACION_PRODUCTOS', [
                            'id_referencia' => $numeroFactura,
                            'tipo_compra' => $primerProducto->tipo_origen ?? 'COMPRA',
                            'productos_fallidos' => $productosFallidos,
                            'mensaje' => "No se puede crear compra {$numeroFactura} debido a productos faltantes: {$nombresProductos}"
                        ]);
                        
                        Log::error("No se puede crear compra {$numeroFactura} debido a productos faltantes: {$nombresProductos}");
                        continue; // Saltar esta compra
                    }
                    
                    // Actualizar estadísticas de productos migrados
                    $estadisticas['productos_migrados'] += count($productosMigrados);
                    
                    // Crear nueva compra
                    $primerProducto = $productosCompra->first();
                    $compra = $this->crearCompra($primerProducto);

                    if ($compra) {
                        $estadisticas['compras_nuevas']++;

                        // Registrar la compra en el mapeo de sincronización según el tipo
                        $tipoOrigen = $primerProducto->tipo_origen === 'TRASLADO' ?
                            IdZenvyValencia::TIPO_TRASLADO : IdZenvyValencia::TIPO_COMPRA;

                        $this->registrarCompraSincronizada($compra->id, $numeroFactura, $tipoOrigen, $primerProducto);

                        // Agregar productos a la compra
                        foreach ($productosCompra as $productoData) {
                            $resultado = $this->agregarProductoACompra($compra->id, $productoData);
                            if ($resultado) {
                                $estadisticas['productos_sincronizados']++;
                            } else {
                                $estadisticas['errores']++;
                            }
                        }
                    }

                    $estadisticas['total_procesadas']++;

                } catch (\Exception $e) {
                    $this->registrarErrorEnBitacora('ERROR_PROCESAR_COMPRA', [
                        'id_referencia' => $numeroFactura,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    Log::error("Error al procesar compra {$numeroFactura}: " . $e->getMessage());
                    $estadisticas['errores']++;
                }
            }
            
            // Preparar mensaje según resultados
            $mensaje = 'Sincronización de compras completada';
            $success = true;
            
            if (!empty($estadisticas['productos_fallidos'])) {
                $cantidadFallidos = count($estadisticas['productos_fallidos']);
                
                // Agrupar por número de factura
                $fallidosPorFactura = collect($estadisticas['productos_fallidos'])->groupBy('numero_factura');
                
                $mensajeDetallado = [];
                foreach ($fallidosPorFactura as $numFactura => $productos) {
                    $tipoOrigen = $productos->first()['tipo_origen'] ?? 'COMPRA';
                    $tipoTexto = $tipoOrigen === 'TRASLADO' ? 'Traslado' : 'Compra';
                    $nombresProductos = $productos->pluck('nombre')->join(', ');
                    $mensajeDetallado[] = "{$tipoTexto} #{$numFactura}: {$nombresProductos}";
                }
                
                $mensaje = "No se puede ingresar la(s) compra(s) debido a que faltan {$cantidadFallidos} producto(s): ";
                $mensaje .= implode('; ', $mensajeDetallado);
                $success = false;
            }

            return [
                'success' => $success,
                'estadisticas' => $estadisticas,
                'mensaje' => $mensaje
            ];

        } catch (\Exception $e) {
            Log::error('Error en sincronización de compras: ' . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error en la sincronización: ' . $e->getMessage(),
                'estadisticas' => [
                    'compras_nuevas' => 0,
                    'productos_sincronizados' => 0,
                    'total_procesadas' => 0,
                    'errores' => 1
                ]
            ];
        }
    }

    /**
     * Obtener compras desde Valencia usando el script SQL proporcionado
     */
    private function obtenerComprasDesdeValencia()
    {
        $sql = "
            SELECT
                -- IDs de origen en Valencia para traceabilidad
                A.id AS recibido_bodega_id,
                A.compra_id AS compra_id_valencia,
                C.translado_id AS translado_id_valencia,

                -- llenado de Tabla Compra
                COALESCE(C.translado_id, A.compra_id) AS numero_factura,
                NULL AS fec_vecimiento,
                A.created_at AS fecha_emision,
                'fecha que se sincroniza' AS fecha_recepcion,
                NOW() AS created_at,
                NULL AS updated_at,
                1 AS estado_id,
                1 AS cliente_id,
                -- llenado de la tabla compra_has_producto
                -- precio: si es traslado usa último de compra_has_producto, si no usa el de la compra
                COALESCE(chp.precio_unidad, B.precio_unidad) AS precio,
                A.cantidad_inicial_seccion AS cantidad_ingresada,
                A.cantidad_disponible AS cantidad_sin_asignar,
                A.fecha_expiracion AS fecha_expiracion,

                -- subtotal, isv y total calculados en base al precio real
                (COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) AS sub_total_producto,
                ((COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) * (P.isv / 100.0)) AS isv,
                ((COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) +
                 ((COALESCE(chp.precio_unidad, B.precio_unidad) * A.cantidad_inicial_seccion) * (P.isv / 100.0))) AS precio_total,

                A.compra_id AS compra_id,
                A.producto_id AS producto_id_valencia,
                IFNULL(A.unidad_compra_id, 1) AS unidad_medida_id,

                -- indicador de origen
                CASE WHEN C.translado_id IS NOT NULL THEN 'TRASLADO' ELSE 'COMPRA' END AS tipo_origen

            FROM recibido_bodega A
            LEFT JOIN log_translado C ON C.destino = A.id   -- si existe traslado, lo detectamos
            LEFT JOIN producto P ON P.id = A.producto_id
            LEFT JOIN compra_has_producto B ON B.compra_id = A.compra_id AND B.producto_id = A.producto_id
            LEFT JOIN (
                SELECT producto_id, precio_unidad
                FROM compra_has_producto chp1
                WHERE created_at = (
                    SELECT MAX(created_at)
                    FROM compra_has_producto chp2
                    WHERE chp2.producto_id = chp1.producto_id
                )
            ) chp ON chp.producto_id = A.producto_id
            WHERE A.seccion_id = 433
              AND A.created_at > '2025-09-10'
        ";

        return $this->conexionValencia->select($sql);
    }

    /**
     * Crear una nueva compra en Zenvy
     */
    private function crearCompra($datosCompra)
    {
        try {
            $compra = Compra::create([
                'numero_factura' => $datosCompra->numero_factura,
                'fecha_vencimiento' => $datosCompra->fec_vecimiento,
                'fecha_emision' => $datosCompra->fecha_emision,
                'fecha_recepcion' => now()->format('Y-m-d'), // Fecha actual de sincronización
                'estado_id' => $datosCompra->estado_id,
                'cliente_id' => $datosCompra->cliente_id,
                'user' => 'Valencia', // Usuario que creó la compra desde Valencia
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return $compra;

        } catch (\Exception $e) {
            Log::error("Error al crear compra {$datosCompra->numero_factura}: " . $e->getMessage(), [
                'recibido_bodega_id' => $datosCompra->recibido_bodega_id ?? null,
                'compra_id_valencia' => $datosCompra->compra_id_valencia ?? null,
                'translado_id_valencia' => $datosCompra->translado_id_valencia ?? null
            ]);
            return null;
        }
    }

    /**
     * Agregar producto a una compra
     */
    private function agregarProductoACompra($compraId, $datosProducto)
    {
        try {
            // Buscar el ID del producto en Zenvy usando el mapeo
            $idProductoZenvy = $this->obtenerIdProductoZenvy($datosProducto->producto_id_valencia);

            if (!$idProductoZenvy) {
                Log::warning("Producto Valencia ID {$datosProducto->producto_id_valencia} no encontrado en Zenvy, omitiendo...", [
                    'recibido_bodega_id' => $datosProducto->recibido_bodega_id ?? null,
                    'compra_id_valencia' => $datosProducto->compra_id_valencia ?? null,
                    'translado_id_valencia' => $datosProducto->translado_id_valencia ?? null
                ]);
                return false;
            }

            // Buscar el ID de la unidad de medida en Zenvy usando el mapeo
            $idUnidadMedidaZenvy = $this->obtenerIdUnidadMedidaZenvy($datosProducto->unidad_medida_id);

            if (!$idUnidadMedidaZenvy) {
                Log::warning("Unidad medida Valencia ID {$datosProducto->unidad_medida_id} no encontrada en Zenvy, usando unidad por defecto (1)");
                $idUnidadMedidaZenvy = 1; // Usar unidad por defecto
            }

            // Verificar si el producto ya existe en esta compra
            $productoExistente = CompraHasProducto::where('compra_id', $compraId)
                ->where('producto_id', $idProductoZenvy)
                ->first();

            if ($productoExistente) {
                return false;
            }

            // Validar precio y valores críticos
            if (!isset($datosProducto->precio) || $datosProducto->precio === null || $datosProducto->precio === '') {
                Log::error("Precio nulo o vacío para producto Valencia ID {$datosProducto->producto_id_valencia} al agregar a compra {$compraId}", [
                    'recibido_bodega_id' => $datosProducto->recibido_bodega_id ?? null,
                    'compra_id_valencia' => $datosProducto->compra_id_valencia ?? null,
                    'translado_id_valencia' => $datosProducto->translado_id_valencia ?? null,
                    'datosProducto' => (array)$datosProducto
                ]);
                return false;
            }

            $compraProducto = CompraHasProducto::create([
                'compra_id' => $compraId,
                'producto_id' => $idProductoZenvy,
                'precio' => $datosProducto->precio,
                'cantidad_ingresada' => $datosProducto->cantidad_ingresada,
                'cantidad_sin_asignar' => $datosProducto->cantidad_sin_asignar,
                'fecha_expiracion' => $datosProducto->fecha_expiracion,
                'sub_total_producto' => $datosProducto->sub_total_producto,
                'isv' => $datosProducto->isv,
                'precio_total' => $datosProducto->precio_total,
                'unidad_medida_id' => $idUnidadMedidaZenvy
            ]);

            return true;

        } catch (\Exception $e) {
            $this->registrarErrorEnBitacora('ERROR_AGREGAR_PRODUCTO_COMPRA', [
                'id_referencia' => $compraId,
                'recibido_bodega_id' => $datosProducto->recibido_bodega_id ?? null,
                'compra_id_valencia' => $datosProducto->compra_id_valencia ?? null,
                'translado_id_valencia' => $datosProducto->translado_id_valencia ?? null,
                'producto_id_valencia' => $datosProducto->producto_id_valencia ?? null,
                'error' => $e->getMessage()
            ]);
            
            Log::error("Error al agregar producto a compra {$compraId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener el ID del producto en Zenvy basado en el ID de Valencia
     */
    private function obtenerIdProductoZenvy($idProductoValencia)
    {
        $mapeo = IdZenvyValencia::where('id_valencia', $idProductoValencia)
            ->where('tipo_dato_migrado_id', IdZenvyValencia::TIPO_PRODUCTO) // 1 para productos
            ->first();

        return $mapeo ? $mapeo->id_zenvy : null;
    }

    /**
     * Obtener el ID de la unidad de medida en Zenvy basado en el ID de Valencia
     */
    private function obtenerIdUnidadMedidaZenvy($idUnidadValencia)
    {
        $mapeo = IdZenvyValencia::where('id_valencia', $idUnidadValencia)
            ->where('tipo_dato_migrado_id', IdZenvyValencia::TIPO_UNIDAD_MEDIDA) // 5 para unidades de medida
            ->first();

        return $mapeo ? $mapeo->id_zenvy : null;
    }

    /**
     * Registrar la compra sincronizada en la tabla de mapeo
     */
    private function registrarCompraSincronizada($idCompraZenvy, $numeroFacturaValencia, $tipoCompra = IdZenvyValencia::TIPO_COMPRA, $primerProducto = null)
    {
        try {
            // Validar numero de factura: debe ser un entero válido (proviene de Valencia)
            if (empty($numeroFacturaValencia) || !is_numeric($numeroFacturaValencia)) {
                Log::warning('No se registrará mapeo: id_valencia inválido para la compra Zenvy', [
                    'id_zenvy' => $idCompraZenvy,
                    'id_valencia_original' => $numeroFacturaValencia,
                    'primer_producto' => $primerProducto ? (array)$primerProducto : null
                ]);
                return false;
            }

            // Asegurar entero
            $numeroFacturaInt = (int)$numeroFacturaValencia;

            // Registrar en id_zenvy_valencia para tracking de sincronización
            $mapeo = IdZenvyValencia::crearMapeo(
                $idCompraZenvy,
                $numeroFacturaInt,
                $tipoCompra // 8 para COMPRA, 9 para TRASLADO
            );

            $tipoTexto = $tipoCompra === IdZenvyValencia::TIPO_TRASLADO ? 'TRASLADO' : 'COMPRA';
            return true;

        } catch (\Exception $e) {
            $this->registrarErrorEnBitacora('ERROR_REGISTRAR_COMPRA_MAPEO', [
                'id_referencia' => $idCompraZenvy,
                'numero_factura' => $numeroFacturaValencia,
                'tipo' => $tipoCompra,
                'error' => $e->getMessage()
            ]);
            Log::error("Error al registrar compra en mapeo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar error en bitácora
     */
    private function registrarErrorEnBitacora($accion, $datos)
    {
        try {
            DB::table('bitacora')->insert([
                'tablaReferencia' => 'sincronizacion_compras',
                'accion' => $accion,
                'idReferencia' => $datos['id_referencia'] ?? 0,
                'datosAnteriores' => null,
                'datosNuevos' => json_encode($datos),
                'users_id' => Auth::id() ?? 1,
                'created_at' => now(),
                'updated_at' => null
            ]);
        } catch (\Exception $e) {
            // Silenciar error de bitácora para no interrumpir proceso
        }
    }
    
    /**
     * Obtener nombre de producto desde Valencia
     */
    private function obtenerNombreProductoValencia($idProductoValencia)
    {
        try {
            $producto = $this->conexionValencia
                ->table('producto')
                ->where('id', $idProductoValencia)
                ->first();
            
            return $producto ? $producto->nombre : "Producto ID {$idProductoValencia}";
        } catch (\Exception $e) {
            return "Producto ID {$idProductoValencia}";
        }
    }
    
    /**
     * Forzar sincronización manual
     */
    public function forzarSincronizacion()
    {
        return $this->sincronizarComprasValencia();
    }
}
