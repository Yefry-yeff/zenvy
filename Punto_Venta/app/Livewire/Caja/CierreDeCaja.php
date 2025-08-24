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
    public $jornadaAbierta = null;
    public $jornada = null;
    public $resumenTransacciones = [];
    public $desglose_entradas = [];
    public $desgloseTarjetas = [];
    public $desgloseTransferencias = [];

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
        $this->calcularResumen();
    }

    public function validarJornadaAbierta()
    {
        $usuario = Auth::user();

        if (!$usuario->tienda_id) {
            return false;
        }

        // Buscar jornada aperturada (puede ser de cualquier fecha)
        $this->jornadaAbierta = DB::table('jornada')
            ->where('tienda_id', $usuario->tienda_id)
            ->where('apertura', 1)
            ->where('cierre', 0)
            ->first();

        // Asignar también a la propiedad $jornada para los métodos de desglose
        $this->jornada = $this->jornadaAbierta;

        if (!$this->jornadaAbierta) {
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

    public function calcularResumen()
    {
        if (!$this->cajaActual || !$this->jornadaAbierta) return;

        // Usar la fecha de la jornada aperturada
        $fechaJornada = Carbon::parse($this->jornadaAbierta->fecha);

        // Obtener el último saldo inicial del día desde apertura_caja
        $ultimaApertura = DB::table('apertura_caja')
            ->where('caja_id', $this->cajaActual->id)
            ->whereDate('fecha_apertura', $fechaJornada)
            ->orderBy('fecha_apertura', 'desc')
            ->first();

        $saldoInicial = $ultimaApertura ? $ultimaApertura->balance_apertura : 0;

        // Calcular totales por tipo - EXCLUIR transacciones de apertura_caja usando la fecha de la jornada
        $resumen = DB::table('transaccion')
            ->where('caja_id', $this->cajaActual->id)
            ->whereDate('created_at', $fechaJornada)
            ->where('transaccion', '!=', 'apertura_caja') // Excluir apertura_caja
            ->selectRaw('
                SUM(CASE WHEN efectivo > 0 THEN efectivo ELSE 0 END) as total_efectivo_entrada,
                SUM(CASE WHEN efectivo < 0 THEN ABS(efectivo) ELSE 0 END) as total_efectivo_salida,
                SUM(efectivo) as total_efectivo_neto,
                SUM(tarjeta) as total_tarjeta,
                SUM(cheque) as total_cheque,
                SUM(transferencia) as total_transferencia,
                COUNT(*) as total_transacciones
            ')
            ->first();

        $this->resumenTransacciones = [
            'saldo_inicial' => $saldoInicial,
            'efectivo_entrada' => $resumen->total_efectivo_entrada ?? 0,
            'efectivo_salida' => $resumen->total_efectivo_salida ?? 0,
            'efectivo_neto' => $resumen->total_efectivo_neto ?? 0,
            'tarjeta' => $resumen->total_tarjeta ?? 0,
            'cheque' => $resumen->total_cheque ?? 0,
            'transferencia' => $resumen->total_transferencia ?? 0,
            'transacciones' => $resumen->total_transacciones ?? 0,
            'fecha_jornada' => $fechaJornada->format('d/m/Y') // Agregar fecha de la jornada
        ];

        $this->totalSistema = $this->cajaActual->balance ?? 0;

        // Calcular todos los desgloses
        $this->calcularDesgloseEntradas();
        $this->calcularDesgloseTarjetas();
        $this->calcularDesgloseTransferencias();
    }

    public function calcularDesgloseEntradas()
    {
        if (!$this->cajaActual || !$this->jornada) return;

        $fecha = $this->jornada->fecha;

        // Obtener desglose por tipo de transacción con efectivo positivo
        $desglose = DB::table('transaccion')
            ->where('caja_id', $this->cajaActual->id)
            ->whereDate('created_at', $fecha)
            ->where('transaccion', '!=', 'apertura_caja')
            ->where('efectivo', '>', 0)
            ->selectRaw('
                transaccion as tipo,
                SUM(efectivo) as total,
                COUNT(*) as cantidad
            ')
            ->groupBy('transaccion')
            ->orderBy('total', 'desc')
            ->get();

        $this->desglose_entradas = $desglose->map(function($item) {
            return [
                'tipo' => $this->formatearTipoTransaccion($item->tipo),
                'total' => $item->total,
                'cantidad' => $item->cantidad,
                'tipo_original' => $item->tipo
            ];
        })->toArray();
    }

    private function formatearTipoTransaccion($tipo)
    {
        $formatos = [
            'venta' => '🛒 Ventas',
            'deposito' => '💰 Depósitos',
            'ingreso_otro' => '📈 Otros Ingresos',
            'devolucion' => '↩️ Devoluciones',
            'ajuste_positivo' => '➕ Ajustes (+)',
            'entrada_efectivo' => '💵 Entrada Efectivo',
            'pago_recibido' => '💳 Pagos Recibidos',
            'default' => '📋 ' . ucfirst(str_replace('_', ' ', $tipo))
        ];

        return $formatos[$tipo] ?? $formatos['default'];
    }

    /**
     * Calcula el desglose de pagos con tarjeta
     */
    private function calcularDesgloseTarjetas()
    {
        try {
            if (!$this->jornada) {
                $this->desgloseTarjetas = collect();
                return;
            }

            $fecha = $this->jornada->fecha;
            
            $this->desgloseTarjetas = DB::select("
                SELECT 
                    pt.nombre as metodo_pago,
                    COUNT(fp.id) as cantidad_transacciones,
                    SUM(fp.cantidad) as total_pagado
                FROM factura f
                INNER JOIN factura_has_pago fp ON f.id = fp.factura_id
                INNER JOIN pago_tipo pt ON fp.pago_tipo_id = pt.id
                WHERE DATE(f.created_at) = ?
                AND pt.nombre LIKE '%tarjeta%'
                GROUP BY pt.id, pt.nombre
                ORDER BY total_pagado DESC
            ", [$fecha]);

            $this->desgloseTarjetas = collect($this->desgloseTarjetas);
            
        } catch (\Exception $e) {
            Log::error('Error al calcular desglose de tarjetas: ' . $e->getMessage());
            $this->desgloseTarjetas = collect();
        }
    }

    /**
     * Calcula el desglose de transferencias
     */
    private function calcularDesgloseTransferencias()
    {
        try {
            if (!$this->jornada) {
                $this->desgloseTransferencias = collect();
                return;
            }

            $fecha = $this->jornada->fecha;
            
            $this->desgloseTransferencias = DB::select("
                SELECT 
                    pt.nombre as metodo_pago,
                    COUNT(fp.id) as cantidad_transacciones,
                    SUM(fp.cantidad) as total_pagado
                FROM factura f
                INNER JOIN factura_has_pago fp ON f.id = fp.factura_id
                INNER JOIN pago_tipo pt ON fp.pago_tipo_id = pt.id
                WHERE DATE(f.created_at) = ?
                AND pt.nombre LIKE '%transferencia%'
                GROUP BY pt.id, pt.nombre
                ORDER BY total_pagado DESC
            ", [$fecha]);

            $this->desgloseTransferencias = collect($this->desgloseTransferencias);
            
        } catch (\Exception $e) {
            Log::error('Error al calcular desglose de transferencias: ' . $e->getMessage());
            $this->desgloseTransferencias = collect();
        }
    }

    public function calcularTotalContado()
    {
        $totalBilletes =
            ($this->safeFloat($this->billetes_500) * 500) +
            ($this->safeFloat($this->billetes_200) * 200) +
            ($this->safeFloat($this->billetes_100) * 100) +
            ($this->safeFloat($this->billetes_50) * 50) +
            ($this->safeFloat($this->billetes_20) * 20) +
            ($this->safeFloat($this->billetes_10) * 10) +
            ($this->safeFloat($this->billetes_5) * 5) +
            ($this->safeFloat($this->billetes_2) * 2) +
            ($this->safeFloat($this->billetes_1) * 1);

        $totalMonedas =
            ($this->safeFloat($this->monedas_0_50) * 0.50) +
            ($this->safeFloat($this->monedas_0_20) * 0.20) +
            ($this->safeFloat($this->monedas_0_10) * 0.10) +
            ($this->safeFloat($this->monedas_0_05) * 0.05) +
            ($this->safeFloat($this->monedas_0_02) * 0.02) +
            ($this->safeFloat($this->monedas_0_01) * 0.01);

        $this->totalContado = $totalBilletes + $totalMonedas;
        $this->diferenciaEfectivo = $this->totalContado - $this->totalSistema;
    }

    /**
     * Helper method para convertir valores de forma segura a float
     */
    private function safeFloat($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }
        return 0.0;
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
                'balance_cierre' => $this->safeFloat($this->cajaActual->balance), // Balance al momento del cierre
                'total_efectivo' => $this->safeFloat($this->resumenTransacciones['efectivo_neto']),
                'total_tarjeta' => $this->safeFloat($this->resumenTransacciones['tarjeta']),
                'total_cheque' => $this->safeFloat($this->resumenTransacciones['cheque']),
                'conteo_efectivo' => $this->safeFloat($this->totalContado),
                'conteo_tarjeta' => $this->safeFloat($this->resumenTransacciones['tarjeta']), // Mismo valor
                'conteo_cheque' => $this->safeFloat($this->resumenTransacciones['cheque']), // Mismo valor
                'diferencia_efectivo' => $this->safeFloat($this->diferenciaEfectivo),
                'diferencia_tarjeta' => 0, // No hay diferencia en tarjetas
                'diferencia_cheque' => 0, // No hay diferencia en cheques
                'fecha_cierre' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Agregar billetes
            $datosInsert['500'] = $this->safeFloat($this->billetes_500);
            $datosInsert['200'] = $this->safeFloat($this->billetes_200);
            $datosInsert['100'] = $this->safeFloat($this->billetes_100);
            $datosInsert['50'] = $this->safeFloat($this->billetes_50);
            $datosInsert['20'] = $this->safeFloat($this->billetes_20);
            $datosInsert['10'] = $this->safeFloat($this->billetes_10);
            $datosInsert['5'] = $this->safeFloat($this->billetes_5);
            $datosInsert['2'] = $this->safeFloat($this->billetes_2);
            $datosInsert['1'] = $this->safeFloat($this->billetes_1);

            // Agregar monedas usando los nombres exactos de las columnas en la BD
            $datosInsert['050'] = $this->safeFloat($this->monedas_0_50);
            $datosInsert['020'] = $this->safeFloat($this->monedas_0_20);
            $datosInsert['010'] = $this->safeFloat($this->monedas_0_10);
            $datosInsert['005'] = $this->safeFloat($this->monedas_0_05);
            $datosInsert['002'] = $this->safeFloat($this->monedas_0_02);
            $datosInsert['001'] = $this->safeFloat($this->monedas_0_01);

            DB::table('cierre_de_caja')->insert($datosInsert);

            // Cerrar la caja - Solo cambiar estado a cerrado (2)
            DB::table('caja')
                ->where('id', $this->cajaActual->id)
                ->update([
                    'estado_caja' => 2, // 2 = cerrada
                    'updated_at' => now()
                ]);

            // Registrar transacción de cierre de caja con los montos recepcionados
            $fechaAhora = now();

            // Crear una sola transacción de cierre con todos los montos en 0
            $transaccionCierre = [
                'caja_id' => $this->cajaActual->id,
                'transaccion' => 'cierre',
                'efectivo' => 0.00,
                'tarjeta' => 0.00,
                'cheque' => 0.00,
                'transferencia' => 0.00,
                'descripcion' => 'Cierre de caja - Montos recepcionados',
                'created_at' => $fechaAhora,
                'update_at' => $fechaAhora
            ];

            // Insertar la transacción de cierre
            DB::table('transaccion')->insert($transaccionCierre);

            DB::commit();

            // Recargar datos de caja para mostrar el nuevo estado
            $this->cargarDatosCaja();

            $this->cierreProcesado = true;
            $this->mensajeExito = 'Cierre de caja procesado correctamente. Caja cerrada exitosamente. ' .
                                 'Total contado: L.' . number_format($this->totalContado, 2) .
                                 '. Diferencia: L.' . number_format($this->diferenciaEfectivo, 2) .
                                 '. Balance preserved: L.' . number_format($this->cajaActual->balance, 2);

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
