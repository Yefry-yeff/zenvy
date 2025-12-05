<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CierreDeCaja extends Component
{
    public $cajaActual = null;
    public $resumenTransacciones = [];
    public $desglose_entradas = [];
    public $desgloseTarjetas = [];
    public $desgloseTransferencias = [];
    public $desgloseCheques = [];

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

    // Otros métodos de pago - Conteo manual
    public $totalTarjetaContado = 0;
    public $totalTransferenciaContado = 0;
    public $totalChequeContado = 0;

    // Cálculos
    public $totalContado = 0;
    public $diferenciaEfectivo = 0;
    public $diferenciaTarjeta = 0;
    public $diferenciaTransferencia = 0;
    public $diferenciaCheque = 0;
    public $totalSistema = 0;
    public $observaciones = '';

    // Mensajes
    public $mensajeExito = '';
    public $mensajeError = '';
    public $cierreProcesado = false;

    const SALDO_INICIAL = 2000.00;

    public function mount()
    {
        $this->cargarDatosCaja();
        $this->calcularResumen();
    }

    public function cargarDatosCaja()
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->tienda_id) {
            $this->cajaActual = null;
            return;
        }

        $this->cajaActual = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->first();
    }

    public function calcularResumen()
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->tienda_id) {
            return;
        }

        // Obtener último cierre
        $ultimoCierre = DB::table('cierre_caja_historico')
            ->where('user_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->orderBy('fecha_cierre', 'desc')
            ->first();

        $fechaInicio = $ultimoCierre ? $ultimoCierre->fecha_cierre : null;

        // Resumen de transacciones desde el último cierre usando factura_has_pago
        $queryBase = DB::table('factura as f')
            ->join('factura_has_pago as fhp', 'f.id', '=', 'fhp.factura_id')
            ->join('tipo_pago as tp', 'fhp.tipo_pago_id', '=', 'tp.id')
            ->where('f.users_id', $usuario->id);

        if ($fechaInicio) {
            $queryBase->where('f.created_at', '>', $fechaInicio);
        }

        $this->resumenTransacciones = $queryBase
            ->select(
                'tp.nombre as forma_pago',
                DB::raw('COUNT(DISTINCT f.id) as cantidad'),
                DB::raw('SUM(fhp.pago_recibido) as total')
            )
            ->groupBy('tp.id', 'tp.nombre')
            ->get();

        // Calcular total del sistema
        $this->totalSistema = $this->resumenTransacciones->sum('total');

        // Desgloses específicos
        $this->cargarDesgloseEntradas($fechaInicio);
        $this->cargarDesgloseTarjetas($fechaInicio);
        $this->cargarDesgloseTransferencias($fechaInicio);
        $this->cargarDesgloseCheques($fechaInicio);
    }

    private function cargarDesgloseEntradas($fechaInicio)
    {
        // Tabla entradas_caja no existe, dejar vacío
        $this->desglose_entradas = collect();
    }

    private function cargarDesgloseTarjetas($fechaInicio)
    {
        // Tabla pago_con_tarjeta no existe, dejar vacío
        $this->desgloseTarjetas = collect();
    }

    private function cargarDesgloseTransferencias($fechaInicio)
    {
        // Tabla pago_con_transferencia no existe, dejar vacío
        $this->desgloseTransferencias = collect();
    }

    private function cargarDesgloseCheques($fechaInicio)
    {
        // Tabla pago_con_cheque no existe, dejar vacío
        $this->desgloseCheques = collect();
    }

    public function updatedBilletes500() { $this->calcularTotalContado(); }
    public function updatedBilletes200() { $this->calcularTotalContado(); }
    public function updatedBilletes100() { $this->calcularTotalContado(); }
    public function updatedBilletes50() { $this->calcularTotalContado(); }
    public function updatedBilletes20() { $this->calcularTotalContado(); }
    public function updatedBilletes10() { $this->calcularTotalContado(); }
    public function updatedBilletes5() { $this->calcularTotalContado(); }
    public function updatedBilletes2() { $this->calcularTotalContado(); }
    public function updatedBilletes1() { $this->calcularTotalContado(); }
    public function updatedMonedas050() { $this->calcularTotalContado(); }
    public function updatedMonedas020() { $this->calcularTotalContado(); }
    public function updatedMonedas010() { $this->calcularTotalContado(); }
    public function updatedMonedas005() { $this->calcularTotalContado(); }
    public function updatedMonedas002() { $this->calcularTotalContado(); }
    public function updatedMonedas001() { $this->calcularTotalContado(); }

    public function updatedTotalTarjetaContado() { $this->calcularDiferencias(); }
    public function updatedTotalTransferenciaContado() { $this->calcularDiferencias(); }
    public function updatedTotalChequeContado() { $this->calcularDiferencias(); }

    public function calcularTotalContado()
    {
        $this->totalContado = (
            ($this->billetes_500 * 500) +
            ($this->billetes_200 * 200) +
            ($this->billetes_100 * 100) +
            ($this->billetes_50 * 50) +
            ($this->billetes_20 * 20) +
            ($this->billetes_10 * 10) +
            ($this->billetes_5 * 5) +
            ($this->billetes_2 * 2) +
            ($this->billetes_1 * 1) +
            ($this->monedas_0_50 * 0.50) +
            ($this->monedas_0_20 * 0.20) +
            ($this->monedas_0_10 * 0.10) +
            ($this->monedas_0_05 * 0.05) +
            ($this->monedas_0_02 * 0.02) +
            ($this->monedas_0_01 * 0.01)
        );

        // Calcular diferencia (contado menos el efectivo que debería haber según el sistema)
        $efectivoSistema = $this->resumenTransacciones
            ->where('forma_pago', 'Efectivo')
            ->first()->total ?? 0;

        $this->diferenciaEfectivo = $this->totalContado - $efectivoSistema;

        // Calcular diferencias para otros métodos de pago
        $this->calcularDiferencias();
    }

    public function calcularDiferencias()
    {
        // Buscar tarjeta (puede ser "Tarjeta", "Tarjeta(POS)", etc.)
        $tarjetaSistema = $this->resumenTransacciones
            ->filter(function($item) {
                return stripos($item->forma_pago, 'Tarjeta') !== false;
            })
            ->sum('total');

        // Buscar cheque
        $chequeSistema = $this->resumenTransacciones
            ->filter(function($item) {
                return stripos($item->forma_pago, 'Cheque') !== false;
            })
            ->sum('total');

        // Buscar transferencia
        $transferenciaSistema = $this->resumenTransacciones
            ->filter(function($item) {
                return stripos($item->forma_pago, 'Transferencia') !== false;
            })
            ->sum('total');

        $this->diferenciaTarjeta = $this->totalTarjetaContado - $tarjetaSistema;
        $this->diferenciaTransferencia = $this->totalTransferenciaContado - $transferenciaSistema;
        $this->diferenciaCheque = $this->totalChequeContado - $chequeSistema;
    }

    public function procesarCierre()
    {
        try {
            DB::beginTransaction();

            $usuario = Auth::user();

            // Obtener período
            $ultimoCierre = DB::table('cierre_caja_historico')
                ->where('user_id', $usuario->id)
                ->where('tienda_id', $usuario->tienda_id)
                ->orderBy('fecha_cierre', 'desc')
                ->first();

            $periodoInicio = $ultimoCierre ? $ultimoCierre->fecha_cierre : null;
            $periodoFin = now();

            // Contar facturas del período
            $queryFacturas = DB::table('factura')
                ->where('users_id', $usuario->id);

            if ($periodoInicio) {
                $queryFacturas->where('created_at', '>', $periodoInicio);
            }

            $cantidadFacturas = $queryFacturas->count();

            // Calcular totales por tipo de pago
            $efectivoSistema = $this->resumenTransacciones
                ->filter(fn($item) => stripos($item->forma_pago, 'Efectivo') !== false)
                ->sum('total');

            $tarjetaSistema = $this->resumenTransacciones
                ->filter(fn($item) => stripos($item->forma_pago, 'Tarjeta') !== false)
                ->sum('total');

            $transferenciaSistema = $this->resumenTransacciones
                ->filter(fn($item) => stripos($item->forma_pago, 'Transferencia') !== false)
                ->sum('total');

            $chequeSistema = $this->resumenTransacciones
                ->filter(fn($item) => stripos($item->forma_pago, 'Cheque') !== false)
                ->sum('total');

            // Guardar cierre en histórico
            DB::table('cierre_caja_historico')->insert([
                'user_id' => $usuario->id,
                'tienda_id' => $usuario->tienda_id,
                'fecha_cierre' => $periodoFin,
                'periodo_inicio' => $periodoInicio,
                'periodo_fin' => $periodoFin,
                'total_efectivo_sistema' => $efectivoSistema,
                'total_efectivo_contado' => $this->totalContado,
                'diferencia' => $this->diferenciaEfectivo,
                'total_tarjeta' => $tarjetaSistema,
                'total_tarjeta_contado' => $this->totalTarjetaContado,
                'diferencia_tarjeta' => $this->diferenciaTarjeta,
                'total_transferencia' => $transferenciaSistema,
                'total_transferencia_contado' => $this->totalTransferenciaContado,
                'diferencia_transferencia' => $this->diferenciaTransferencia,
                'total_cheque' => $chequeSistema,
                'total_cheque_contado' => $this->totalChequeContado,
                'diferencia_cheque' => $this->diferenciaCheque,
                'total_general' => $this->totalSistema,
                'cantidad_facturas' => $cantidadFacturas,
                'observaciones' => $this->observaciones,
                'desglose_billetes' => json_encode([
                    'billetes' => [
                        '500' => $this->billetes_500,
                        '200' => $this->billetes_200,
                        '100' => $this->billetes_100,
                        '50' => $this->billetes_50,
                        '20' => $this->billetes_20,
                        '10' => $this->billetes_10,
                        '5' => $this->billetes_5,
                        '2' => $this->billetes_2,
                        '1' => $this->billetes_1,
                    ],
                    'monedas' => [
                        '0.50' => $this->monedas_0_50,
                        '0.20' => $this->monedas_0_20,
                        '0.10' => $this->monedas_0_10,
                        '0.05' => $this->monedas_0_05,
                        '0.02' => $this->monedas_0_02,
                        '0.01' => $this->monedas_0_01,
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Resetear caja al saldo inicial
            DB::table('caja')
                ->where('users_id', $usuario->id)
                ->where('tienda_id', $usuario->tienda_id)
                ->update([
                    'balance' => self::SALDO_INICIAL,
                    'updated_at' => now()
                ]);

            DB::commit();

            $this->cierreProcesado = true;
            $this->mensajeExito = '✅ Cierre de caja procesado exitosamente. La caja se ha restablecido a L. ' . number_format(self::SALDO_INICIAL, 2);

            Log::info("Cierre de caja procesado - Usuario: {$usuario->id}, Tienda: {$usuario->tienda_id}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = '❌ Error al procesar cierre: ' . $e->getMessage();
            Log::error("Error en cierre de caja: " . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.caja.cierre-de-caja');
    }
}
