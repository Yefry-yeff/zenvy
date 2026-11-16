<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IdZenvyValencia extends Model
{
    protected $table = 'id_zenvy_valencia';
    
    protected $fillable = [
        'tipo_dato_migrado_id',
        'id_zenvy',
        'id_valencia',
        'created_at',
        'updated_at'
    ];

    public $timestamps = true;

    // Constantes para tipos de datos
    const TIPO_MARCA = 2;
    const TIPO_PRODUCTO = 1;
    const TIPO_CATEGORIA = 3;
    const TIPO_SUBCATEGORIA = 4;
    const TIPO_UNIDAD_MEDIDA = 5;
    const TIPO_COMPRA = 8;
    const TIPO_TRASLADO = 9;

    /**
     * Busca mapeo por ID de Valencia y tipo
     */
    public static function buscarPorValencia($idValencia, $tipoDato)
    {
        return self::where('id_valencia', $idValencia)
                  ->where('tipo_dato_migrado_id', $tipoDato)
                  ->first();
    }

    /**
     * Busca mapeo por ID de Zenvy y tipo
     */
    public static function buscarPorZenvy($idZenvy, $tipoDato)
    {
        return self::where('id_zenvy', $idZenvy)
                  ->where('tipo_dato_migrado_id', $tipoDato)
                  ->first();
    }

    /**
     * Crea o actualiza mapeo (deshabilitando foreign key checks temporalmente)
     */
    public static function crearMapeo($idZenvy, $idValencia, $tipoDato)
    {
        try {
            // Deshabilitar foreign key checks temporalmente
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Usar insert directo para evitar problemas con foreign keys múltiples
            $existente = self::where('id_valencia', $idValencia)
                            ->where('tipo_dato_migrado_id', $tipoDato)
                            ->first();
            
            if ($existente) {
                // Actualizar existente
                $existente->update([
                    'id_zenvy' => $idZenvy,
                    'updated_at' => now()
                ]);
                $resultado = $existente;
            } else {
                // Crear nuevo usando insert directo
                $id = DB::table('id_zenvy_valencia')->insertGetId([
                    'tipo_dato_migrado_id' => $tipoDato,
                    'id_zenvy' => $idZenvy,
                    'id_valencia' => $idValencia,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $resultado = self::find($id);
            }
            
            // Rehabilitar foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            return $resultado;
            
        } catch (\Exception $e) {
            // Asegurar que se rehabiliten los foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            Log::error("Error creando mapeo: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Obtiene todos los mapeos de marcas
     */
    public static function mapeosMarcas()
    {
        return self::where('tipo_dato_migrado_id', self::TIPO_MARCA)->get();
    }

    // Relaciones
    public function marca()
    {
        return $this->belongsTo(Marca::class, 'id_zenvy');
    }
}
