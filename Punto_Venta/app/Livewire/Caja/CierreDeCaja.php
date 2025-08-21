<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Log;

class CierreDeCaja extends Component
{
    public $cajaActual = null;
    public $transaccionesDia = [];
    public $resumenTransacciones = [];
    
    // Billetes
    public $billetes_500 = 0;
    public $billetes_200 = 0;
    public $billetes_100 = 0;
    public $billetes_50 = 0;
    public $billetes_20 = 0;
    public $billetes_10 = 0;
    public $billetes_5 = 0;
    public $billetes_2 = 0;
    public $billetes_1 = 0;
    
    // Monedas
    public $monedas_0_50 = 0;
    public $monedas_0_20 = 0;
    public $monedas_0_10 = 0;
    public $monedas_0_05 = 0;
    public $monedas_0_02 = 0;
    public $monedas_0_01 = 0;
    
    // Cálculos
    public $totalContado = 0;
    public $diferenciaEfectivo = 0;
    public $totalSistema = 0;
    
    // Mensajes
    public $mensajeExito = '';
    public $mensajeError = '';
    public $cierreProcesado = false;

    public function mount()
    {
        $this->validarJornadaAbierta();
        $this->cargarDatosCaja();
        $this->cargarTransaccionesDia();
        $this->calcularResumen();
    }

    public function validarJornadaAbierta()
    {
        $usuario = Auth::user();
        
        if (!$usuario->tienda_id) {
            return false;
        }

        $fechaActual = date('Y-m-d');
        
        // Verificar si existe una jornada aperturada para hoy
        $jornadaAbierta = DB::table('jornada')
            ->where('fecha', $fechaActual)
            ->where('tienda_id', $usuario->tienda_id)
            ->where('apertura', 1)
            ->where('cierre', 0)
            ->first();

        if (!$jornadaAbierta) {
            return false;
        }

        return true;
    }

    public function cargarDatosCaja()
    {
        $usuario = Auth::user();
        
        // Verificar que el usuario tenga tienda asignada
        if (!$usuario || !$usuario->tienda_id) {
            $this->cajaActual = null;
            return;
        }
        
        $this->cajaActual = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->where('estado_caja', 1) // 1 = abierta
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function cargarTransaccionesDia()
    {
        if (!$this->cajaActual) return;

        $fechaHoy = Carbon::today();
        
        $this->transaccionesDia = DB::table('transaccion')
            ->where('caja_id', $this->cajaActual->id)
            ->whereDate('created_at', $fechaHoy)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function calcularResumen()
    {
        if (!$this->cajaActual) return;

        $fechaHoy = Carbon::today();
        
        // Calcular totales por tipo
        $resumen = DB::table('transaccion')
            ->where('caja_id', $this->cajaActual->id)
            ->whereDate('created_at', $fechaHoy)
            ->selectRaw('
                SUM(CASE WHEN efectivo > 0 THEN efectivo ELSE 0 END) as total_efectivo_entrada,
                SUM(CASE WHEN efectivo < 0 THEN ABS(efectivo) ELSE 0 END) as total_efectivo_salida,
                SUM(efectivo) as total_efectivo_neto,
                SUM(tarjeta) as total_tarjeta,
                SUM(cheque) as total_cheque,
                COUNT(*) as total_transacciones
            ')
            ->first();

        $this->resumenTransacciones = [
            'efectivo_entrada' => $resumen->total_efectivo_entrada ?? 0,
            'efectivo_salida' => $resumen->total_efectivo_salida ?? 0,
            'efectivo_neto' => $resumen->total_efectivo_neto ?? 0,
            'tarjeta' => $resumen->total_tarjeta ?? 0,
            'cheque' => $resumen->total_cheque ?? 0,
            'transacciones' => $resumen->total_transacciones ?? 0
        ];

        $this->totalSistema = $this->cajaActual->balance ?? 0;
    }

    public function calcularTotalContado()
    {
        // Función helper para convertir valores de forma segura
        $safeFloat = function($value) {
            if (is_numeric($value)) {
                return floatval($value);
            }
            return 0.0;
        };

        $totalBilletes = 
            ($safeFloat($this->billetes_500) * 500) +
            ($safeFloat($this->billetes_200) * 200) +
            ($safeFloat($this->billetes_100) * 100) +
            ($safeFloat($this->billetes_50) * 50) +
            ($safeFloat($this->billetes_20) * 20) +
            ($safeFloat($this->billetes_10) * 10) +
            ($safeFloat($this->billetes_5) * 5) +
            ($safeFloat($this->billetes_2) * 2) +
            ($safeFloat($this->billetes_1) * 1);

        $totalMonedas = 
            ($safeFloat($this->monedas_0_50) * 0.50) +
            ($safeFloat($this->monedas_0_20) * 0.20) +
            ($safeFloat($this->monedas_0_10) * 0.10) +
            ($safeFloat($this->monedas_0_05) * 0.05) +
            ($safeFloat($this->monedas_0_02) * 0.02) +
            ($safeFloat($this->monedas_0_01) * 0.01);

        $this->totalContado = $totalBilletes + $totalMonedas;
        $this->diferenciaEfectivo = $this->totalContado - $this->totalSistema;
    }

    public function updated($propertyName)
    {
        // Validar y limpiar valores de billetes y monedas
        if (str_contains($propertyName, 'billetes_') || str_contains($propertyName, 'monedas_')) {
            // Asegurar que el valor sea numérico válido
            $value = $this->$propertyName;
            if (!is_numeric($value) || $value < 0) {
                $this->$propertyName = 0;
            } else {
                $this->$propertyName = intval($value); // Para billetes/monedas debe ser entero
            }
            
            $this->calcularTotalContado();
        }
    }

    public function procesarCierre()
    {
        // Validar que la jornada esté abierta antes de proceder
        if (!$this->validarJornadaAbierta()) {
            $this->mensajeError = 'No se pueden realizar operaciones de caja porque la jornada no está aperturada para hoy. Debe aperturar la jornada primero.';
            return;
        }

        if (!$this->cajaActual) {
            $this->mensajeError = 'No hay una caja abierta para cerrar.';
            return;
        }

        try {
            DB::beginTransaction();

            // Insertar registro de cierre usando insert con nombres de columnas explícitos
            $datosInsert = [
                'caja_id' => $this->cajaActual->id,
                'total_efectivo' => $this->resumenTransacciones['efectivo_neto'],
                'total_tarjeta' => $this->resumenTransacciones['tarjeta'],
                'total_cheque' => $this->resumenTransacciones['cheque'],
                'conteo_efectivo' => $this->totalContado,
                'conteo_tarjeta' => $this->resumenTransacciones['tarjeta'], // Mismo valor
                'conteo_cheque' => $this->resumenTransacciones['cheque'], // Mismo valor
                'diferencia_efectivo' => $this->diferenciaEfectivo,
                'diferencia_tarjeta' => 0, // No hay diferencia en tarjetas
                'diferencia_cheque' => 0, // No hay diferencia en cheques
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Agregar billetes
            $datosInsert['500'] = floatval($this->billetes_500);
            $datosInsert['200'] = floatval($this->billetes_200);
            $datosInsert['100'] = floatval($this->billetes_100);
            $datosInsert['50'] = floatval($this->billetes_50);
            $datosInsert['20'] = floatval($this->billetes_20);
            $datosInsert['10'] = floatval($this->billetes_10);
            $datosInsert['5'] = floatval($this->billetes_5);
            $datosInsert['2'] = floatval($this->billetes_2);
            $datosInsert['1'] = floatval($this->billetes_1);

            // Agregar monedas usando los nombres exactos de las columnas en la BD
            $datosInsert['050'] = floatval($this->monedas_0_50);
            $datosInsert['020'] = floatval($this->monedas_0_20);
            $datosInsert['010'] = floatval($this->monedas_0_10);
            $datosInsert['005'] = floatval($this->monedas_0_05);
            $datosInsert['002'] = floatval($this->monedas_0_02);
            $datosInsert['001'] = floatval($this->monedas_0_01);

            DB::table('cierre_de_caja')->insert($datosInsert);

            // Cerrar la caja y resetear balance
            DB::table('caja')
                ->where('id', $this->cajaActual->id)
                ->update([
                    'estado_caja' => 2, // 2 = cerrada
                    'balance' => 0.00, // Resetear balance a 0
                    'updated_at' => now()
                ]);

            DB::commit();

            // Recargar datos de caja para mostrar el nuevo estado
            $this->cargarDatosCaja();

            $this->cierreProcesado = true;
            $this->mensajeExito = 'Cierre de caja procesado correctamente. Caja cerrada y balance resetado a L.0.00. ' . 
                                 'Total contado: L.' . number_format($this->totalContado, 2) . 
                                 '. Diferencia: L.' . number_format($this->diferenciaEfectivo, 2);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = 'Error al procesar el cierre de caja: ' . $e->getMessage();
        }
    }

    public function limpiarMensajes()
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
    }

    public function volverDashboard()
    {
        // Emitir evento al componente padre
        $this->dispatch('cambiarVista', ['vista' => 'dashboard']);
    }

    public function render()
    {
        return view('livewire.caja.cierre-de-caja');
    }
}
