<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class CAIService
{
    /**
     * Obtener el siguiente número de factura usando el sistema CAI
     * @param int $tiendaId ID de la tienda para validar CAI específico
     */
    public function obtenerSiguienteNumeroFactura($tiendaId = null)
    {
        try {
            DB::beginTransaction();

            // Si no se proporciona tienda_id, usar la del usuario autenticado
            if (!$tiendaId) {
                $user = Auth::user();
                $tiendaId = $user->tienda_id ?? null;
                
                if (!$tiendaId) {
                    throw new Exception('No se puede determinar la tienda del usuario para generar la factura');
                }
            }

            // Buscar el CAI activo para la tienda específica
            $gestionCai = DB::table('gestion_cai as gc')
                ->join('cai as c', 'gc.cai_id', '=', 'c.id')
                ->where('gc.estado_id', 1) // Estado activo en gestión
                ->where('c.estado_id', 1)  // Estado activo en CAI
                ->where('c.tienda_id', $tiendaId) // CAI específico de la tienda
                ->where('gc.cantidad_no_utilizada', '>', 0)
                ->select(
                    'gc.*', 
                    'c.cai', 
                    'c.fecha_limite_emision',
                    'c.tienda_id',
                    'c.estado_id as cai_estado_id'
                )
                ->orderBy('gc.id', 'asc') // FIFO: primer CAI registrado primero
                ->first();

            if (!$gestionCai) {
                throw new Exception("No hay CAI activos disponibles para facturar en esta tienda (ID: {$tiendaId})");
            }

            // Verificar que el CAI no haya vencido
            $fechaActual = now()->format('Y-m-d');
            if ($gestionCai->fecha_limite_emision && $gestionCai->fecha_limite_emision < $fechaActual) {
                // Desactivar CAI vencido
                DB::table('cai')
                    ->where('id', $gestionCai->cai_id)
                    ->update(['estado_id' => 2]); // Inactivo
                    
                DB::table('gestion_cai')
                    ->where('id', $gestionCai->id)
                    ->update(['estado_id' => 2]); // Inactivo

                throw new Exception('El CAI disponible ha vencido (Fecha límite: ' . $gestionCai->fecha_limite_emision . '). Se ha desactivado automáticamente.');
            }

            // Verificar si está próximo a vencer (menos de 7 días)
            $fechaLimite = Carbon::parse($gestionCai->fecha_limite_emision);
            $diasRestantes = now()->diffInDays($fechaLimite, false);
            
            $alertaVencimiento = null;
            if ($diasRestantes <= 7 && $diasRestantes >= 0) {
                $alertaVencimiento = "¡ATENCIÓN! El CAI vence en {$diasRestantes} día(s) (Fecha límite: {$gestionCai->fecha_limite_emision})";
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

            // Si se agotó también desactivar el CAI principal
            if ($nuevoEstado == 2) {
                DB::table('cai')
                    ->where('id', $gestionCai->cai_id)
                    ->update(['estado_id' => 2]);
            }

            DB::commit();

            return [
                'numero_factura' => $numeroFactura,
                'cai_id' => $gestionCai->cai_id,
                'gestion_cai_id' => $gestionCai->id,
                'cai' => $gestionCai->cai,
                'cantidad_restante' => $nuevaCantidadNoUtilizada,
                'numero_secuencia' => $gestionCai->numero_actual, // El número antes de incrementar
                'cai_agotado' => $nuevoEstado == 2,
                'fecha_limite_emision' => $gestionCai->fecha_limite_emision,
                'dias_restantes_vencimiento' => $diasRestantes,
                'alerta_vencimiento' => $alertaVencimiento,
                'tienda_id' => $gestionCai->tienda_id
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
     * Verificar disponibilidad de CAI
     */
    public function verificarDisponibilidadCAI()
    {
        $caisActivos = DB::table('gestion_cai as gc')
            ->join('cai as c', 'gc.cai_id', '=', 'c.id')
            ->where('gc.estado_id', 1)
            ->where('gc.cantidad_no_utilizada', '>', 0)
            ->where('c.fecha_limite_emision', '>=', now()->format('Y-m-d'))
            ->count();

        return $caisActivos > 0;
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
