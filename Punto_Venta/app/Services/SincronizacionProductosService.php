<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\IdZenvyValencia;
use App\Services\SincronizacionSubcategoriasService;

class SincronizacionProductosService
{
    private $conexionProfac;
    private $conexionZenvy;
    private static $instancia;

    public function __construct()
    {
        $this->conexionProfac = DB::connection('profac_app');
        $this->conexionZenvy = DB::connection('mysql');
    }

    public static function obtenerInstancia()
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene todos los productos de Valencia (profac_app)
     */
    public function obtenerProductosValencia()
    {
        try {
            return $this->conexionProfac
                ->table('producto')
                ->select([
                    'id',
                    'nombre',
                    'descripcion',
                    'isv',
                    'precio_base',
                    'ultimo_costo_compra',
                    'costo_promedio',
                    'codigo_estatal',
                    'marca_id',
                    'unidad_medida_compra_id',
                    'estado_producto_id',
                    'sub_categoria_id',
                    'precio1',
                    'precio2',
                    'precio3',
                    'precio4'
                ])
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al obtener productos de Valencia: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Convierte el valor ISV de Valencia al ID correspondiente en Zenvy
     */
    private function convertirIsvAId($isvValencia)
    {
        switch ($isvValencia) {
            case 0:
                return 5;
            case 15:
                return 1;
            case 18:
                return 2;
            default:
                return 1; // Por defecto
        }
    }

    /**
     * Obtiene el ID de Zenvy basado en el ID de Valencia usando la tabla de mapeo
     */
    private function obtenerIdZenvy($idValencia, $tipoEntidad)
    {
        $mapeo = IdZenvyValencia::where('id_valencia', $idValencia)
            ->where('tipo_dato_migrado_id', $tipoEntidad)
            ->first();

        return $mapeo ? $mapeo->id_zenvy : null;
    }

    /**
     * Sincroniza un producto específico desde Valencia a Zenvy
     */
    public function sincronizarProducto($idProductoValencia, $esAutoSincronizacion = false)
    {
        try {
            // Obtener producto de Valencia
            $productoValencia = $this->conexionProfac
                ->table('producto')
                ->where('id', $idProductoValencia)
                ->first();

            if (!$productoValencia) {
                throw new \Exception("Producto no encontrado en Valencia con ID: $idProductoValencia");
            }

            // Verificar si ya está sincronizado
            $mapeoExistente = IdZenvyValencia::where('id_valencia', $idProductoValencia)
                ->where('tipo_dato_migrado_id', 1) // 1 para productos
                ->first();

            // Obtener IDs mapeados para relaciones
            $marcaIdZenvy = $this->obtenerIdZenvy($productoValencia->marca_id, 2); // 2 para marcas
            $unidadIdZenvy = $this->obtenerIdZenvy($productoValencia->unidad_medida_compra_id, 5); // 5 para unidades
            $subcategoriaIdZenvy = $this->obtenerIdZenvy($productoValencia->sub_categoria_id, 4); // 4 para subcategorías

            // Validar que existan las relaciones necesarias
            if (!$marcaIdZenvy) {
                throw new \Exception("Marca no sincronizada. Sincroniza primero la marca con ID: {$productoValencia->marca_id}");
            }
            if (!$unidadIdZenvy) {
                throw new \Exception("Unidad de medida no sincronizada. Sincroniza primero la unidad con ID: {$productoValencia->unidad_medida_compra_id}");
            }
            if (!$subcategoriaIdZenvy) {
                // Sincronizar subcategoría usando el servicio especializado
                $subcatSyncService = new SincronizacionSubcategoriasService();
                $syncResult = $subcatSyncService->sincronizarSubcategoria($productoValencia->sub_categoria_id);
                if (!$syncResult['success']) {
                    throw new \Exception("No se pudo sincronizar la subcategoría: " . $syncResult['mensaje']);
                }
                $subcategoriaIdZenvy = $syncResult['id_zenvy'];
            }

            // Preparar datos para insertar/actualizar en Zenvy (sin codigo_barra)
            $datosProductoZenvy = [
                'nombre' => $productoValencia->nombre,
                'descripcion' => $productoValencia->descripcion,
                'isv_id' => $this->convertirIsvAId($productoValencia->isv),
                'precio_base' => $productoValencia->precio_base,
                'ultimo_costo_compra' => $productoValencia->ultimo_costo_compra,
                'costo_promedio' => $productoValencia->costo_promedio,
                'codigo_estatal' => $productoValencia->codigo_estatal,
                'marca_id' => $marcaIdZenvy,
                'unidad_medida_venta_id' => $unidadIdZenvy,
                'users_id' => 4, // Por defecto
                'estado_id' => $productoValencia->estado_producto_id,
                'subcategoria_id' => $subcategoriaIdZenvy,
                'precio1' => $productoValencia->precio1,
                'precio2' => $productoValencia->precio2,
                'precio3' => $productoValencia->precio3,
                'precio4' => $productoValencia->precio4,
                'descuento_unitario' => 0, // Por defecto
                'descuento_tercera' => 1, // Por defecto
                'descuento_cuarta' => 1, // Por defecto
                'producto_valencia' => 1, // Marcado como producto de Valencia
                'updated_at' => now()
            ];

            // Obtener codigo_barra de Valencia para actualizar en precio_has_venta
            $codigoBarraValencia = $this->conexionProfac
                ->table('producto')
                ->where('id', $idProductoValencia)
                ->value('codigo_barra');

            $accion = '';
            $idProductoZenvy = null;

            if ($mapeoExistente) {
                // ACTUALIZAR producto existente
                $idProductoZenvy = $mapeoExistente->id_zenvy;

                // Obtener el producto actual de Zenvy para usar en validaciones
                $productoZenvyActual = $this->conexionZenvy
                    ->table('producto')
                    ->where('id', $idProductoZenvy)
                    ->first();

                if ($productoZenvyActual) {
                    // Para actualizaciones, NO sincronizar: codigo_barra, imagen, descuento_unitario, isv_id, unidad_medida_venta_id
                    // Para precio_base: solo sincronizar si es necesario ajustarlo al precio4
                    $datosActualizacion = [
                        'nombre' => $productoValencia->nombre,
                        'descripcion' => $productoValencia->descripcion,
                        // NO sincronizar isv_id en actualizaciones (mantener valor local)
                        'ultimo_costo_compra' => $productoValencia->ultimo_costo_compra,
                        'costo_promedio' => $productoValencia->costo_promedio,
                        'codigo_estatal' => $productoValencia->codigo_estatal,
                        'marca_id' => $marcaIdZenvy,
                        // NO sincronizar unidad_medida_venta_id en actualizaciones (mantener valor local)
                        'estado_id' => $productoValencia->estado_producto_id,
                        'subcategoria_id' => $subcategoriaIdZenvy,
                        // Sincronizar precios 1-4 normalmente
                        'precio1' => $productoValencia->precio1,
                        'precio2' => $productoValencia->precio2,
                        'precio3' => $productoValencia->precio3,
                        'precio4' => $productoValencia->precio4,
                        'updated_at' => now()
                    ];

                    // Lógica especial para precio_base:
                    // Solo actualizar si precio_base actual es menor que precio4
                    $precio4Valencia = $productoValencia->precio4 ?? 0;
                    $precioBaseActual = $productoZenvyActual->precio_base ?? 0;

                    if ($precioBaseActual < $precio4Valencia) {
                        // Si precio base actual es menor que precio4, actualizarlo al precio4
                        $datosActualizacion['precio_base'] = $precio4Valencia;
                        Log::info("Precio base ajustado automáticamente de {$precioBaseActual} a {$precio4Valencia} (precio4) para producto Valencia ID: $idProductoValencia");
                    }
                    // Si precio_base >= precio4, mantener el valor actual (no sincronizar)

                    // Campos que NO se sincronizan en actualizaciones:
                    // - codigo_barra (se maneja en precio_has_venta, no en producto)
                    // - imagen (mantener valor actual)
                    // - descuento_unitario (mantener valor actual)
                    // - isv_id (mantener valor local - puede ser diferente según configuración de la tienda)
                    // - unidad_medida_venta_id (mantener valor local - puede ser diferente según preferencias)

                    $this->conexionZenvy
                        ->table('producto')
                        ->where('id', $idProductoZenvy)
                        ->update($datosActualizacion);

                    // Actualizar codigo_barra en precio_has_venta para la unidad de medida correspondiente
                    if ($codigoBarraValencia && $unidadIdZenvy) {
                        $this->conexionZenvy
                            ->table('precio_has_venta')
                            ->where('producto_id', $idProductoZenvy)
                            ->where('unidad_medida_id', $unidadIdZenvy)
                            ->where('estado_id', 1)
                            ->update([
                                'codigo_barra' => $codigoBarraValencia,
                                'updated_at' => now()
                            ]);
                        Log::info("Código de barras actualizado en precio_has_venta para producto Zenvy ID: $idProductoZenvy, Unidad: $unidadIdZenvy");
                    }
                } else {
                    // Si no existe el producto en Zenvy (caso raro), usar datos completos
                    $this->conexionZenvy
                        ->table('producto')
                        ->where('id', $idProductoZenvy)
                        ->update($datosProductoZenvy);

                    // Actualizar codigo_barra en precio_has_venta para la unidad de medida correspondiente
                    if ($codigoBarraValencia && $unidadIdZenvy) {
                        $this->conexionZenvy
                            ->table('precio_has_venta')
                            ->where('producto_id', $idProductoZenvy)
                            ->where('unidad_medida_id', $unidadIdZenvy)
                            ->where('estado_id', 1)
                            ->update([
                                'codigo_barra' => $codigoBarraValencia,
                                'updated_at' => now()
                            ]);
                        Log::info("Código de barras actualizado en precio_has_venta para producto Zenvy ID: $idProductoZenvy, Unidad: $unidadIdZenvy");
                    }
                }

                $accion = 'actualizado';
                Log::info("Producto actualizado exitosamente. Valencia ID: $idProductoValencia, Zenvy ID: $idProductoZenvy");
            } else {
                // CREAR nuevo producto
                $datosProductoZenvy['created_at'] = now();

                $idProductoZenvy = $this->conexionZenvy
                    ->table('producto')
                    ->insertGetId($datosProductoZenvy);

                // Crear mapeo en tabla de relaciones
                IdZenvyValencia::create([
                    'id_zenvy' => $idProductoZenvy,
                    'id_valencia' => $idProductoValencia,
                    'tipo_dato_migrado_id' => 1, // 1 para productos
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Actualizar codigo_barra en precio_has_venta para la unidad de medida correspondiente
                if ($codigoBarraValencia && $unidadIdZenvy) {
                    $this->conexionZenvy
                        ->table('precio_has_venta')
                        ->where('producto_id', $idProductoZenvy)
                        ->where('unidad_medida_id', $unidadIdZenvy)
                        ->where('estado_id', 1)
                        ->update([
                            'codigo_barra' => $codigoBarraValencia,
                            'updated_at' => now()
                        ]);
                    Log::info("Código de barras establecido en precio_has_venta para nuevo producto Zenvy ID: $idProductoZenvy, Unidad: $unidadIdZenvy");
                }

                $accion = 'creado';
                Log::info("Producto creado exitosamente. Valencia ID: $idProductoValencia, Zenvy ID: $idProductoZenvy");
            }

            return [
                'success' => true,
                'mensaje' => "Producto $accion exitosamente",
                'accion' => $accion,
                'id_zenvy' => $idProductoZenvy,
                'datos' => $datosProductoZenvy
            ];

        } catch (\Exception $e) {
            Log::error('Error al sincronizar producto: ' . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Sincroniza múltiples productos desde Valencia
     */
    public function sincronizarTodosLosProductos()
    {
        $productosValencia = $this->obtenerProductosValencia();
        $resultados = [
            'sincronizados' => 0,
            'creados' => 0,
            'actualizados' => 0,
            'errores' => 0,
            'mensajes' => []
        ];

        foreach ($productosValencia as $producto) {
            $resultado = $this->sincronizarProducto($producto->id);

            if ($resultado['success']) {
                $resultados['sincronizados']++;
                if (isset($resultado['accion'])) {
                    if ($resultado['accion'] === 'creado') {
                        $resultados['creados']++;
                    } elseif ($resultado['accion'] === 'actualizado') {
                        $resultados['actualizados']++;
                    }
                }
            } else {
                $resultados['errores']++;
                $resultados['mensajes'][] = "ID {$producto->id}: {$resultado['mensaje']}";
            }
        }

        return $resultados;
    }

    /**
     * Obtiene productos de Valencia con información de sincronización
     */
    public function obtenerProductosValenciaConEstado()
    {
        try {
            $productos = $this->obtenerProductosValencia();

            // Obtener IDs ya sincronizados
            $idsSincronizados = IdZenvyValencia::where('tipo_dato_migrado_id', 1)
                ->pluck('id_valencia')
                ->toArray();

            return $productos->map(function ($producto) use ($idsSincronizados) {
                $producto->sincronizado = in_array($producto->id, $idsSincronizados);
                $producto->id_zenvy = null;

                if ($producto->sincronizado) {
                    $mapeo = IdZenvyValencia::where('id_valencia', $producto->id)
                        ->where('tipo_dato_migrado_id', 1)
                        ->first();
                    $producto->id_zenvy = $mapeo ? $mapeo->id_zenvy : null;
                }

                return $producto;
            });

        } catch (\Exception $e) {
            Log::error('Error al obtener productos con estado: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Verifica si un producto está sincronizado
     */
    public function estaProductoSincronizado($idProductoValencia)
    {
        return IdZenvyValencia::where('id_valencia', $idProductoValencia)
            ->where('tipo_dato_migrado_id', 1)
            ->exists();
    }

    /**
     * Obtiene estadísticas de sincronización
     */
    public function obtenerEstadisticasSincronizacion()
    {
        try {
            $totalValencia = $this->conexionProfac->table('producto')->count();
            $totalSincronizados = IdZenvyValencia::where('tipo_dato_migrado_id', 1)->count();

            return [
                'total_valencia' => $totalValencia,
                'total_sincronizados' => $totalSincronizados,
                'pendientes' => $totalValencia - $totalSincronizados,
                'porcentaje_sincronizado' => $totalValencia > 0 ? round(($totalSincronizados / $totalValencia) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas: ' . $e->getMessage());
            return [
                'total_valencia' => 0,
                'total_sincronizados' => 0,
                'pendientes' => 0,
                'porcentaje_sincronizado' => 0
            ];
        }
    }

    /**
     * Actualiza un producto de Valencia ya sincronizado, preservando campos editables
     * y aplicando reglas especiales para precio_base vs precio4
     */
    public function actualizarProductoValencia($idProductoZenvy)
    {
        try {
            // Obtener el mapeo para encontrar el ID de Valencia
            $mapeo = IdZenvyValencia::where('id_zenvy', $idProductoZenvy)
                ->where('tipo_dato_migrado_id', 1) // 1 para productos
                ->first();

            if (!$mapeo) {
                throw new \Exception("El producto no está sincronizado con Valencia");
            }

            // Obtener datos actuales del producto en Zenvy
            $productoZenvy = $this->conexionZenvy
                ->table('producto')
                ->where('id', $idProductoZenvy)
                ->first();

            if (!$productoZenvy) {
                throw new \Exception("Producto no encontrado en Zenvy");
            }

            // Obtener datos actualizados de Valencia
            $productoValencia = $this->conexionProfac
                ->table('producto')
                ->where('id', $mapeo->id_valencia)
                ->first();

            if (!$productoValencia) {
                throw new \Exception("Producto no encontrado en Valencia");
            }

            // Campos de SOLO LECTURA (solo se actualizan desde Valencia)
            $camposSoloLectura = [
                'nombre' => $productoValencia->nombre,
                'codigo_estatal' => $productoValencia->codigo_estatal,
                'descripcion' => $productoValencia->descripcion,
                'isv_id' => $this->convertirIsvAId($productoValencia->isv),
                'ultimo_costo_compra' => $productoValencia->ultimo_costo_compra,
                'costo_promedio' => $productoValencia->costo_promedio,
                'updated_at' => now()
            ];

            // Obtener IDs mapeados para relaciones de solo lectura
            $marcaIdZenvy = $this->obtenerIdZenvy($productoValencia->marca_id, 2);
            $unidadIdZenvy = $this->obtenerIdZenvy($productoValencia->unidad_medida_compra_id, 5);
            $subcategoriaIdZenvy = $this->obtenerIdZenvy($productoValencia->sub_categoria_id, 4);

            if ($marcaIdZenvy) {
                $camposSoloLectura['marca_id'] = $marcaIdZenvy;
            }
            if ($unidadIdZenvy) {
                $camposSoloLectura['unidad_medida_venta_id'] = $unidadIdZenvy;
            }
            if ($subcategoriaIdZenvy) {
                $camposSoloLectura['subcategoria_id'] = $subcategoriaIdZenvy;
            }

            // REGLA ESPECIAL: Si precio4 > precio_base, actualizar precio_base automáticamente
            $precio4Valencia = $productoValencia->precio4;
            $precioBaseActual = $productoZenvy->precio_base;

            if ($precio4Valencia > $precioBaseActual) {
                $camposSoloLectura['precio_base'] = $precio4Valencia;
                Log::info("Auto-actualizando precio_base de $precioBaseActual a $precio4Valencia para producto ID: $idProductoZenvy (precio4 mayor)");
            }

            // Actualizar solo los campos de solo lectura
            $this->conexionZenvy
                ->table('producto')
                ->where('id', $idProductoZenvy)
                ->update($camposSoloLectura);

            // Obtener codigo_barra de Valencia y actualizar en precio_has_venta
            $codigoBarraValencia = $this->conexionProfac
                ->table('producto')
                ->where('id', $mapeo->id_valencia)
                ->value('codigo_barra');

            // Actualizar codigo_barra en precio_has_venta para la unidad de medida correspondiente
            if ($codigoBarraValencia && $unidadIdZenvy) {
                $this->conexionZenvy
                    ->table('precio_has_venta')
                    ->where('producto_id', $idProductoZenvy)
                    ->where('unidad_medida_id', $unidadIdZenvy)
                    ->where('estado_id', 1)
                    ->update([
                        'codigo_barra' => $codigoBarraValencia,
                        'updated_at' => now()
                    ]);
                Log::info("Código de barras actualizado en precio_has_venta para producto Zenvy ID: $idProductoZenvy, Unidad: $unidadIdZenvy");
            }

            Log::info("Producto de Valencia actualizado exitosamente. Zenvy ID: $idProductoZenvy");

            return [
                'success' => true,
                'mensaje' => 'Producto actualizado exitosamente desde Valencia',
                'campos_actualizados' => array_keys($camposSoloLectura),
                'precio_base_actualizado' => isset($camposSoloLectura['precio_base'])
            ];

        } catch (\Exception $e) {
            Log::error("Error al actualizar producto de Valencia: " . $e->getMessage());
            throw $e;
        }
    }
}
