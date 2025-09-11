<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\IdZenvyValencia;

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
                    'codigo_barra',
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
                return 1;
            case 15:
                return 2;
            case 18:
                return 3;
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
    public function sincronizarProducto($idProductoValencia)
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
            $yaExiste = IdZenvyValencia::where('id_valencia', $idProductoValencia)
                ->where('tipo_dato_migrado_id', 1) // 1 para productos
                ->exists();

            if ($yaExiste) {
                throw new \Exception("El producto ya está sincronizado");
            }

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
                throw new \Exception("Subcategoría no sincronizada. Sincroniza primero la subcategoría con ID: {$productoValencia->sub_categoria_id}");
            }

            // Preparar datos para insertar en Zenvy
            $datosProductoZenvy = [
                'nombre' => $productoValencia->nombre,
                'descripcion' => $productoValencia->descripcion,
                'isv_id' => $this->convertirIsvAId($productoValencia->isv),
                'precio_base' => $productoValencia->precio_base,
                'ultimo_costo_compra' => $productoValencia->ultimo_costo_compra,
                'costo_promedio' => $productoValencia->costo_promedio,
                'codigo_barra' => $productoValencia->codigo_barra,
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
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Insertar en Zenvy
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

            Log::info("Producto sincronizado exitosamente. Valencia ID: $idProductoValencia, Zenvy ID: $idProductoZenvy");

            return [
                'success' => true,
                'mensaje' => 'Producto sincronizado exitosamente',
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
            'errores' => 0,
            'mensajes' => []
        ];

        foreach ($productosValencia as $producto) {
            $resultado = $this->sincronizarProducto($producto->id);
            
            if ($resultado['success']) {
                $resultados['sincronizados']++;
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
}