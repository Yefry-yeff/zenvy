<?php

namespace App\Livewire\GestionDeSucursales;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CierreDeJornada extends Component
{
    public $fechaCierre;
    public $comentario = '';
    public $mostrarAlerta = false;
    public $cajasAbiertas = [];
    public $cajasConDiferencia = [];
    public $transaccionesPorCaja = [];
    public $mensaje = '';
    public $tipoMensaje = '';
    public $procesoEnCurso = false;
    public $tiendaUsuario;
    public $nombreTienda;

    protected $listeners = [
        'limpiarMensaje' => 'limpiarMensaje',
        'sucursalCambiada' => 'refrescarDatos'
    ];

    public function mount()
    {
        $this->fechaCierre = Carbon::now()->format('Y-m-d');
        $this->cargarTiendaUsuario();
    }

    public function refrescarDatos()
    {
        // Recargar datos cuando cambie la sucursal
        $this->cargarTiendaUsuario();
        $this->limpiarMensaje();
        $this->render(); // Forzar re-renderizado
    }

    public function cargarTiendaUsuario()
    {
        // Obtener la tienda del usuario autenticado con su información
        $usuario = DB::table('users as u')
            ->leftJoin('tienda as t', 'u.tienda_id', '=', 't.id')
            ->where('u.id', Auth::id())
            ->select('u.tienda_id', 't.denominacion_social')
            ->first();

        if ($usuario) {
            $this->tiendaUsuario = $usuario->tienda_id;
            // Usar denominacion_social si existe, sino mostrar ID
            $this->nombreTienda = $usuario->denominacion_social
                                  ?? 'Tienda #' . $usuario->tienda_id;
        } else {
            $this->tiendaUsuario = null;
            $this->nombreTienda = null;
        }
    }

    public function verificarCondicionesParaCierre()
    {
        $this->resetear();

        if (!$this->tiendaUsuario) {
            $this->mensaje = 'Usuario sin tienda asignada. No se puede procesar el cierre.';
            $this->tipoMensaje = 'error';
            return;
        }

        try {
            // 1. PRIMERO: Verificar si existe una jornada aperturada para esta fecha y tienda
            $jornadaAperturada = DB::table('jornada')
                ->where('fecha', $this->fechaCierre)
                ->where('tienda_id', $this->tiendaUsuario)
                ->where('apertura', 1)
                ->first();

            if (!$jornadaAperturada) {
                $this->mensaje = "No se puede cerrar la jornada porque no se ha aperturado la jornada para la fecha {$this->fechaCierre} de {$this->nombreTienda}. Debe aperturar la jornada primero.";
                $this->tipoMensaje = 'error';
                return;
            }

            // 2. Verificar si ya está cerrada
            if ($jornadaAperturada->cierre == 1) {
                $this->mensaje = "La jornada para la fecha {$this->fechaCierre} de {$this->nombreTienda} ya está cerrada.";
                $this->tipoMensaje = 'error';
                return;
            }

            // 3. Verificar si hay cajas abiertas (estado = 1) en la tienda del usuario
            $this->cajasAbiertas = DB::table('caja as c')
                ->join('users as u', 'c.users_id', '=', 'u.id')
                ->where('c.estado_caja', 1)
                ->where('c.tienda_id', $this->tiendaUsuario)
                ->select('c.*', 'u.name as nombre_usuario')
                ->get()
                ->toArray();

            // 4. Verificar cajas con diferencias en cierres del día en la tienda
            $this->cajasConDiferencia = DB::table('cierre_de_caja as cc')
                ->join('caja as c', 'cc.caja_id', '=', 'c.id')
                ->join('users as u', 'c.users_id', '=', 'u.id')
                ->where('c.tienda_id', $this->tiendaUsuario)
                ->whereDate('cc.fecha_cierre', $this->fechaCierre)
                ->where(function($query) {
                    $query->where('cc.diferencia_efectivo', '!=', 0)
                          ->orWhere('cc.diferencia_tarjeta', '!=', 0)
                          ->orWhere('cc.diferencia_cheque', '!=', 0);
                })
                ->select(
                    'c.id',
                    'c.users_id',
                    'u.name as nombre_usuario',
                    'cc.diferencia_efectivo',
                    'cc.diferencia_tarjeta',
                    'cc.diferencia_cheque',
                    'cc.fecha_cierre'
                )
                ->get()
                ->toArray();

            // 5. Obtener transacciones por caja (excluyendo las que ya tienen diferencias registradas)
            $cajasConDiferenciaIds = collect($this->cajasConDiferencia)->pluck('id')->toArray();

            $this->transaccionesPorCaja = DB::table('transaccion as t')
                ->join('caja as c', 't.caja_id', '=', 'c.id')
                ->join('users as u', 'c.users_id', '=', 'u.id')
                ->where('c.tienda_id', $this->tiendaUsuario)
                ->whereDate('t.created_at', $this->fechaCierre)
                ->whereNotIn('c.id', $cajasConDiferenciaIds)
                ->groupBy('c.id', 'c.users_id', 'u.name')
                ->selectRaw('
                    c.id as caja_id,
                    c.users_id,
                    u.name as nombre_usuario,
                    c.balance as balance_actual,
                    SUM(CASE WHEN t.efectivo > 0 THEN t.efectivo ELSE 0 END) as total_efectivo_ingreso,
                    SUM(CASE WHEN t.efectivo < 0 THEN ABS(t.efectivo) ELSE 0 END) as total_efectivo_egreso,
                    SUM(CASE WHEN t.tarjeta > 0 THEN t.tarjeta ELSE 0 END) as total_tarjeta_ingreso,
                    SUM(CASE WHEN t.tarjeta < 0 THEN ABS(t.tarjeta) ELSE 0 END) as total_tarjeta_egreso,
                    SUM(CASE WHEN t.cheque > 0 THEN t.cheque ELSE 0 END) as total_cheque_ingreso,
                    SUM(CASE WHEN t.cheque < 0 THEN ABS(t.cheque) ELSE 0 END) as total_cheque_egreso,
                    SUM(IFNULL(t.efectivo, 0) + IFNULL(t.tarjeta, 0) + IFNULL(t.cheque, 0)) as total_neto
                ')
                ->having('total_neto', '!=', 0)
                ->get()
                ->toArray();

            // 6. Mostrar alertas si hay problemas o proceder directamente
            if (count($this->cajasAbiertas) > 0 || count($this->cajasConDiferencia) > 0) {
                $this->mostrarAlerta = true;
            } else {
                // No hay problemas, proceder directamente
                $this->procesarCierreJornada();
            }

        } catch (\Exception $e) {
            $this->mensaje = 'Error al verificar condiciones: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        }
    }

    public function procesarCierreJornada()
    {
        if ($this->procesoEnCurso) return;

        $this->procesoEnCurso = true;

        try {
            DB::beginTransaction();

            // 1. Buscar la jornada aperturada para esta fecha y tienda
            $jornada = DB::table('jornada')
                ->where('fecha', $this->fechaCierre)
                ->where('tienda_id', $this->tiendaUsuario)
                ->where('apertura', 1)
                ->where('cierre', 0)
                ->first();

            if (!$jornada) {
                throw new \Exception('No se encontró una jornada aperturada para cerrar');
            }

            // 2. Actualizar la jornada: apertura = 0 y cierre = 1
            DB::table('jornada')
                ->where('id', $jornada->id)
                ->update([
                    'apertura' => 0,
                    'cierre' => 1,
                    'user_id_cierre' => Auth::id(),
                    'comentario' => $this->comentario,
                    'updated_at' => now()
                ]);

            // 2. Procesar cajas abiertas de la tienda
            foreach ($this->cajasAbiertas as $cajaData) {
                // Convertir array a objeto si es necesario
                $caja = is_array($cajaData) ? (object) $cajaData : $cajaData;

                // Obtener totales de transacciones para esta caja en la fecha de cierre
                $totalesTransacciones = DB::table('transaccion')
                    ->where('caja_id', $caja->id)
                    ->whereDate('created_at', $this->fechaCierre)
                    ->selectRaw('
                        IFNULL(SUM(efectivo), 0) as total_efectivo_transacciones,
                        IFNULL(SUM(tarjeta), 0) as total_tarjeta_transacciones,
                        IFNULL(SUM(cheque), 0) as total_cheque_transacciones,
                        IFNULL(SUM(transferencia), 0) as total_transferencia_transacciones
                    ')
                    ->first();

                // Valores por defecto si no hay transacciones
                $totalEfectivo = $totalesTransacciones ? ($totalesTransacciones->total_efectivo_transacciones ?? 0) : 0;
                $totalTarjeta = $totalesTransacciones ? ($totalesTransacciones->total_tarjeta_transacciones ?? 0) : 0;
                $totalCheque = $totalesTransacciones ? ($totalesTransacciones->total_cheque_transacciones ?? 0) : 0;
                $totalTransferencia = $totalesTransacciones ? ($totalesTransacciones->total_transferencia_transacciones ?? 0) : 0;

                // El balance actual de la caja
                $balanceCaja = $caja->balance ?? 0;

                // Calcular diferencias (balance actual vs totales de transacciones)
                $diferenciaEfectivo = $balanceCaja - $totalEfectivo;
                $diferenciaTarjeta = 0 - $totalTarjeta; // Para tarjeta y cheque, la diferencia es negativa de los totales
                $diferenciaCheque = 0 - $totalCheque;

                // Cambiar estado a cerrada (2)
                DB::table('caja')
                    ->where('id', $caja->id)
                    ->update([
                        'estado_caja' => 2,
                        'updated_at' => now()
                    ]);

                // PRIMERO: Registrar transacción de cierre de caja y obtener su ID
                $idTransaccion = DB::table('transaccion')->insertGetId([
                    'caja_id' => $caja->id,
                    'transaccion' => 'cierre_caja',
                    'efectivo' => $totalEfectivo, // Total efectivo sin diferencias
                    'tarjeta' => $totalTarjeta,   // Total tarjeta sin diferencias
                    'cheque' => $totalCheque,     // Total cheque sin diferencias
                    'transferencia' => $totalTransferencia, // Total transferencia sin diferencias
                    'descripcion' => 'Cierre automático por cierre de jornada - Totales de la jornada',
                    'created_at' => now(),
                    'update_at' => now()
                ]);

                // SEGUNDO: Registrar cierre de caja con todos los datos incluyendo id_transaccion
                $cierreId = DB::table('cierre_de_caja')->insertGetId([
                    'caja_id' => $caja->id,
                    'balance_cierre' => $balanceCaja,
                    'total_efectivo' => $totalEfectivo,
                    'total_tarjeta' => $totalTarjeta,
                    'total_cheque' => $totalCheque,
                    'total_transferencia' => $totalTransferencia, // Total transferencia calculado
                    'conteo_efectivo' => 0,
                    'conteo_tarjeta' => 0,
                    'conteo_cheque' => 0,
                    'diferencia_efectivo' => $diferenciaEfectivo,
                    'diferencia_tarjeta' => $diferenciaTarjeta,
                    'diferencia_cheque' => $diferenciaCheque,
                    // Inicializar todas las denominaciones en 0
                    '1' => 0, '2' => 0, '5' => 0, '10' => 0, '20' => 0, '50' => 0,
                    '100' => 0, '200' => 0, '500' => 0,
                    '001' => 0, '002' => 0, '005' => 0, '010' => 0, '020' => 0, '050' => 0,
                    'fecha_cierre' => $this->fechaCierre, // Usar la fecha de cierre seleccionada
                    'transaccion_id' => $idTransaccion, // Agregar el ID de la transacción
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // 3. Procesar cajas adicionales que estén abiertas pero no fueron capturadas en la primera consulta
            $cajasAbiertasAdicionales = DB::table('caja as c')
                ->join('users as u', 'c.users_id', '=', 'u.id')
                ->where('c.estado_caja', 1)
                ->where('c.tienda_id', $this->tiendaUsuario)
                ->whereNotIn('c.id', collect($this->cajasAbiertas)->pluck('id')->toArray())
                ->select('c.*', 'u.name as nombre_usuario')
                ->get();

                foreach ($cajasAbiertasAdicionales as $caja) {
                // Obtener totales de transacciones para esta caja en la fecha de cierre
                $totalesTransacciones = DB::table('transaccion')
                    ->where('caja_id', $caja->id)
                    ->whereDate('created_at', $this->fechaCierre)
                    ->selectRaw('
                        IFNULL(SUM(efectivo), 0) as total_efectivo_transacciones,
                        IFNULL(SUM(tarjeta), 0) as total_tarjeta_transacciones,
                        IFNULL(SUM(cheque), 0) as total_cheque_transacciones,
                        IFNULL(SUM(transferencia), 0) as total_transferencia_transacciones
                    ')
                    ->first();

                // Valores por defecto si no hay transacciones
                $totalEfectivo = $totalesTransacciones ? ($totalesTransacciones->total_efectivo_transacciones ?? 0) : 0;
                $totalTarjeta = $totalesTransacciones ? ($totalesTransacciones->total_tarjeta_transacciones ?? 0) : 0;
                $totalCheque = $totalesTransacciones ? ($totalesTransacciones->total_cheque_transacciones ?? 0) : 0;
                $totalTransferencia = $totalesTransacciones ? ($totalesTransacciones->total_transferencia_transacciones ?? 0) : 0;

                // El balance actual de la caja
                $balanceCaja = $caja->balance ?? 0;

                // Calcular diferencias (balance actual vs totales de transacciones)
                $diferenciaEfectivo = $balanceCaja - $totalEfectivo;
                $diferenciaTarjeta = 0 - $totalTarjeta;
                $diferenciaCheque = 0 - $totalCheque;

                // Cambiar estado a cerrada (2)
                DB::table('caja')
                    ->where('id', $caja->id)
                    ->update([
                        'estado_caja' => 2,
                        'updated_at' => now()
                    ]);

                // PRIMERO: Registrar transacción de cierre de caja y obtener su ID
                $idTransaccion = DB::table('transaccion')->insertGetId([
                    'caja_id' => $caja->id,
                    'transaccion' => 'cierre_caja',
                    'efectivo' => $totalEfectivo, // Total efectivo sin diferencias
                    'tarjeta' => $totalTarjeta,   // Total tarjeta sin diferencias
                    'cheque' => $totalCheque,     // Total cheque sin diferencias
                    'transferencia' => $totalTransferencia, // Total transferencia sin diferencias
                    'descripcion' => 'Cierre automático por cierre de jornada - Totales de la jornada',
                    'created_at' => now(),
                    'update_at' => now()
                ]);                // SEGUNDO: Registrar cierre de caja con todos los datos incluyendo id_transaccion
                DB::table('cierre_de_caja')->insert([
                    'caja_id' => $caja->id,
                    'balance_cierre' => $balanceCaja,
                    'total_efectivo' => $totalEfectivo,
                    'total_tarjeta' => $totalTarjeta,
                    'total_cheque' => $totalCheque,
                    'total_transferencia' => $totalTransferencia, // Total transferencia calculado
                    'conteo_efectivo' => 0,
                    'conteo_tarjeta' => 0,
                    'conteo_cheque' => 0,
                    'diferencia_efectivo' => $diferenciaEfectivo,
                    'diferencia_tarjeta' => $diferenciaTarjeta,
                    'diferencia_cheque' => $diferenciaCheque,
                    // Inicializar todas las denominaciones en 0
                    '1' => 0, '2' => 0, '5' => 0, '10' => 0, '20' => 0, '50' => 0,
                    '100' => 0, '200' => 0, '500' => 0,
                    '001' => 0, '002' => 0, '005' => 0, '010' => 0, '020' => 0, '050' => 0,
                    'fecha_cierre' => $this->fechaCierre, // Usar la fecha de cierre seleccionada
                    'transaccion_id' => $idTransaccion, // Agregar el ID de la transacción
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $totalCajasProcesadas = count($this->cajasAbiertas) + count($cajasAbiertasAdicionales);

            DB::commit();

            $this->mensaje = 'Jornada cerrada exitosamente para la fecha ' . $this->fechaCierre .
                            ' en ' . $this->nombreTienda . '. ' . $totalCajasProcesadas . ' cajas procesadas con cierres automáticos registrados.';
            $this->tipoMensaje = 'success';

            $this->resetear();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensaje = 'Error al procesar cierre de jornada: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        } finally {
            $this->procesoEnCurso = false;
        }
    }

    public function cancelarCierre()
    {
        $this->resetear();
        $this->mensaje = 'Cierre de jornada cancelado';
        $this->tipoMensaje = 'info';
    }

    public function limpiarMensaje()
    {
        $this->mensaje = '';
        $this->tipoMensaje = '';
    }

    private function resetear()
    {
        $this->mostrarAlerta = false;
        $this->cajasAbiertas = [];
        $this->cajasConDiferencia = [];
        $this->transaccionesPorCaja = [];
    }

    public function render()
    {
        return view('livewire.gestion-de-sucursales.cierre-de-jornada');
    }
}
