<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\CompraHasProducto;
use App\Models\IdZenvyValencia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SincronizacionComprasService
{
    private $conexionValencia;
    private $conexionZenvy;

    public function __construct()
    {
        $this->conexionValencia = DB::connection('profac_app');
        $this->conexionZenvy = DB::connection(); // Usar conexión por defecto (db_zenvy)
    }

    /**
     * Sincronizar compras desde Valencia - solo nuevas compras
     */
    public function sincronizarComprasValencia()
    {
        try {
            Log::info('Iniciando sincronización de compras desde Valencia');

            // Obtener compras desde Valencia usando el script proporcionado
            $comprasValencia = $this->obtenerComprasDesdeValencia();

            if (empty($comprasValencia)) {
                Log::info('No se encontraron nuevas compras para sincronizar');
                return [
                    'success' => true,
                    'estadisticas' => [
                        'compras_nuevas' => 0,
                        'productos_sincronizados' => 0,
                        'total_procesadas' => 0,
                        'errores' => 0
                    ],
                    'mensaje' => 'No hay nuevas compras para sincronizar'
                ];
            }

            $estadisticas = [
                'compras_nuevas' => 0,
                'productos_sincronizados' => 0,
                'total_procesadas' => 0,
                'errores' => 0
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
                            Log::info("Compra {$numeroFactura} actualizada con user='Valencia'");
                        } else {
                            Log::info("Compra con número de factura {$numeroFactura} ya existe, omitiendo...");
                        }
                        continue;
                    }

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
                    Log::error("Error al procesar compra {$numeroFactura}: " . $e->getMessage());
                    $estadisticas['errores']++;
                }
            }

            Log::info('Sincronización de compras completada', $estadisticas);

            return [
                'success' => true,
                'estadisticas' => $estadisticas,
                'mensaje' => 'Sincronización de compras completada exitosamente'
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

            Log::info("Compra creada exitosamente: {$compra->numero_factura} (ID: {$compra->id})", [
                'recibido_bodega_id' => $datosCompra->recibido_bodega_id ?? null,
                'compra_id_valencia' => $datosCompra->compra_id_valencia ?? null,
                'translado_id_valencia' => $datosCompra->translado_id_valencia ?? null
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
                Log::info("Producto {$idProductoZenvy} ya existe en compra {$compraId}, omitiendo...");
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

            Log::info("Producto agregado a compra: Compra {$compraId}, Producto {$idProductoZenvy}, Unidad {$idUnidadMedidaZenvy}");
            return true;

        } catch (\Exception $e) {
            Log::error("Error al agregar producto a compra {$compraId}: " . $e->getMessage(), [
                'recibido_bodega_id' => $datosProducto->recibido_bodega_id ?? null,
                'compra_id_valencia' => $datosProducto->compra_id_valencia ?? null,
                'translado_id_valencia' => $datosProducto->translado_id_valencia ?? null,
                'producto_id_valencia' => $datosProducto->producto_id_valencia ?? null
            ]);
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
            Log::info("Compra registrada en mapeo: Zenvy ID {$idCompraZenvy}, Valencia Factura {$numeroFacturaValencia}, Tipo: {$tipoTexto}", ['mapeo' => $mapeo ? $mapeo->toArray() : null]);
            return true;

        } catch (\Exception $e) {
            Log::error("Error al registrar compra en mapeo: " . $e->getMessage());
            return false;
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
