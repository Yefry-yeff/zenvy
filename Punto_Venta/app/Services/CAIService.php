<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class CAIService
{
    /**
     * Obtener el siguiente número de factura usando el sistema CAI
     * @param int|null $tiendaId ID de la tienda para filtrar CAIs específicos
     */
    public function obtenerSiguienteNumeroFactura($tiendaId = null)
    {
        try {
            DB::beginTransaction();

            // Construir query base
            $query = DB::table('gestion_cai as gc')
                ->join('cai as c', 'gc.cai_id', '=', 'c.id')
                ->where('gc.estado_id', 1) // Estado activo
                ->where('gc.cantidad_no_utilizada', '>', 0)
                ->where('c.estado_id', 1) // CAI activo
                ->select('gc.*', 'c.cai', 'c.fecha_limite_emision', 'c.tienda_id')
                ->orderBy('gc.id', 'asc'); // FIFO: primer CAI registrado primero

            // Filtrar por tienda si se proporciona
            if ($tiendaId) {
                $query->where('c.tienda_id', $tiendaId);
            }

            $gestionCai = $query->first();

            if (!$gestionCai) {
                $mensaje = $tiendaId 
                    ? "No hay CAI activos disponibles para facturar en la tienda especificada (ID: $tiendaId)"
                    : 'No hay CAI activos disponibles para facturar';
                throw new Exception($mensaje);
            }

            // Verificar que no haya vencido
            if ($gestionCai->fecha_limite_emision && $gestionCai->fecha_limite_emision < now()->format('Y-m-d')) {
                // Desactivar CAI vencido
                DB::table('gestion_cai')
                    ->where('id', $gestionCai->id)
                    ->update(['estado_id' => 2]); // Inactivo

                throw new Exception('El CAI disponible ha vencido. Se ha desactivado automáticamente.');
            }

            // Generar el número de factura
            $numeroFormateado = $this->formatearNumero($gestionCai->numero_actual);
            $numeroFactura = $gestionCai->numero_base . $numeroFormateado;

            // Actualizar gestion_cai
            $nuevoNumeroActual = $gestionCai->numero_actual + 1;
            $nuevaCantidadNoUtilizada = $gestionCai->cantidad_no_utilizada - 1;

            // Si se agotó la cantidad, desactivar el CAI
            $nuevoEstado = $nuevaCantidadNoUtilizada > 0 ? 1 : 2; // 1=Activo, 2=Inactivo

            DB::table('gestion_cai')
                ->where('id', $gestionCai->id)
                ->update([
                    'numero_actual' => $nuevoNumeroActual,
                    'cantidad_no_utilizada' => $nuevaCantidadNoUtilizada,
                    'estado_id' => $nuevoEstado,
                    'updated_at' => now()
                ]);

            DB::commit();

            return [
                'numero_factura' => $numeroFactura,
                'cai_id' => $gestionCai->cai_id,
                'gestion_cai_id' => $gestionCai->id,
                'cai' => $gestionCai->cai,
                'cantidad_restante' => $nuevaCantidadNoUtilizada,
                'numero_secuencia' => $gestionCai->numero_actual, // El número antes de incrementar
                'cai_agotado' => $nuevoEstado == 2
            ];

        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Formatear número con ceros a la izquierda (8 dígitos)
     */
    private function formatearNumero($numero)
    {
        return str_pad($numero, 8, '0', STR_PAD_LEFT);
    }

        /**
     * Verificar si hay CAIs disponibles para facturar
     * @param int|null $tiendaId ID de la tienda para filtrar CAIs específicos
     */
    public function verificarDisponibilidadCAI($tiendaId = null)
    {
        $query = DB::table('gestion_cai as gc')
            ->join('cai as c', 'gc.cai_id', '=', 'c.id')
            ->where('gc.estado_id', 1)
            ->where('gc.cantidad_no_utilizada', '>', 0)
            ->where('c.estado_id', 1)
            ->where('c.fecha_limite_emision', '>=', now()->format('Y-m-d'));

        // Filtrar por tienda si se proporciona
        if ($tiendaId) {
            $query->where('c.tienda_id', $tiendaId);
        }

        return $query->exists();
    }

    /**
     * Validar CAI disponible para una tienda específica antes de facturar
     * @param int $tiendaId ID de la tienda
     * @return array Resultado de la validación
     */
    public function validarCAIParaTienda($tiendaId)
    {
        // Verificar CAI en tabla CAI
        $caiActivo = DB::table('cai')
            ->where('tienda_id', $tiendaId)
            ->where('estado_id', 1)
            ->where('fecha_limite_emision', '>=', now()->format('Y-m-d'))
            ->first();

        if (!$caiActivo) {
            return [
                'valido' => false,
                'mensaje' => 'No hay CAI activo válido para esta tienda en la tabla CAI',
                'detalle' => 'La tienda no tiene un CAI activo o el CAI está vencido'
            ];
        }

        // Verificar en gestion_cai que haya cantidad disponible
        $gestionCAI = DB::table('gestion_cai')
            ->where('cai_id', $caiActivo->id)
            ->where('estado_id', 1)
            ->where('cantidad_no_utilizada', '>', 0)
            ->first();

        if (!$gestionCAI) {
            return [
                'valido' => false,
                'mensaje' => 'No hay CAI con cantidad disponible para facturar en esta tienda',
                'detalle' => 'El CAI de la tienda no tiene cantidad disponible en gestion_cai'
            ];
        }

        return [
            'valido' => true,
            'mensaje' => 'CAI válido y disponible para facturar',
            'cai_info' => [
                'cai_id' => $caiActivo->id,
                'cai' => $caiActivo->cai,
                'cantidad_disponible' => $gestionCAI->cantidad_no_utilizada,
                'fecha_limite' => $caiActivo->fecha_limite_emision
            ]
        ];
    }

    /**
     * Obtener información de CAIs disponibles
     */
    public function obtenerInformacionCAIs()
    {
        return DB::table('gestion_cai as gc')
            ->join('cai as c', 'gc.cai_id', '=', 'c.id')
            ->join('estado as e', 'gc.estado_id', '=', 'e.id')
            ->select(
                'gc.id',
                'gc.numero_actual',
                'gc.numero_base',
                'gc.cantidad_no_utilizada',
                'c.cai',
                'c.fecha_limite_emision',
                'e.descripcion as estado'
            )
            ->orderBy('gc.id', 'asc')
            ->get();
    }

    /**
     * Desactivar CAIs vencidos
     */
    public function desactivarCAIsVencidos()
    {
        $caisVencidos = DB::table('gestion_cai as gc')
            ->join('cai as c', 'gc.cai_id', '=', 'c.id')
            ->where('gc.estado_id', 1)
            ->where('c.fecha_limite_emision', '<', now()->format('Y-m-d'))
            ->pluck('gc.id');

        if ($caisVencidos->count() > 0) {
            DB::table('gestion_cai')
                ->whereIn('id', $caisVencidos)
                ->update([
                    'estado_id' => 2, // Inactivo
                    'updated_at' => now()
                ]);

            return $caisVencidos->count();
        }

        return 0;
    }
}
