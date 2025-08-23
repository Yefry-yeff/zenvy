<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FlujoDeCaja extends Component
{
    public $transacciones = [];
    public $fechaFiltro;
    public $tipoTransaccionFiltro = '';
    public $totalEfectivo = 0;
    public $totalTarjeta = 0;
    public $totalCheque = 0;
    public $totalTransferencia = 0;
    public $totalGeneral = 0;

    public function mount()
    {
        $this->fechaFiltro = Carbon::now()->format('Y-m-d');
        $this->cargarTransacciones();
    }

    public function cargarTransacciones()
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->tienda_id) {
            $this->transacciones = [];
            return;
        }

        // Obtener las cajas del usuario
        $cajas = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->pluck('id');

        if ($cajas->isEmpty()) {
            $this->transacciones = [];
            return;
        }

        // Construir la consulta de transacciones
        $query = DB::table('transaccion')
            ->whereIn('caja_id', $cajas);

        // Aplicar filtro de fecha si está seleccionado
        if ($this->fechaFiltro) {
            $query->whereDate('created_at', $this->fechaFiltro);
        }

        // Aplicar filtro de tipo de transacción si está seleccionado
        if ($this->tipoTransaccionFiltro) {
            $query->where('transaccion', $this->tipoTransaccionFiltro);
        }

        // Obtener transacciones ordenadas por fecha más reciente
        $this->transacciones = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaccion) {
                // No mostrar valores para apertura de caja y cierre
                $esAperturaOCierre = in_array(strtolower($transaccion->transaccion), ['apertura_caja', 'cierre']);

                return [
                    'id' => $transaccion->id,
                    'caja_id' => $transaccion->caja_id,
                    'transaccion' => $transaccion->transaccion,
                    'efectivo' => $esAperturaOCierre ? 0 : ($transaccion->efectivo ?? 0),
                    'tarjeta' => $esAperturaOCierre ? 0 : ($transaccion->tarjeta ?? 0),
                    'cheque' => $esAperturaOCierre ? 0 : ($transaccion->cheque ?? 0),
                    'transferencia' => $esAperturaOCierre ? 0 : ($transaccion->transferencia ?? 0),
                    'descripcion' => $transaccion->descripcion,
                    'created_at' => $transaccion->created_at,
                    'total' => $esAperturaOCierre ? 0 : (($transaccion->efectivo ?? 0) + ($transaccion->tarjeta ?? 0) + ($transaccion->cheque ?? 0) + ($transaccion->transferencia ?? 0))
                ];
            })
            ->toArray();

        $this->calcularTotales();
    }

    public function calcularTotales()
    {
        $this->totalEfectivo = collect($this->transacciones)->sum('efectivo');
        $this->totalTarjeta = collect($this->transacciones)->sum('tarjeta');
        $this->totalCheque = collect($this->transacciones)->sum('cheque');
        $this->totalTransferencia = collect($this->transacciones)->sum('transferencia');
        $this->totalGeneral = $this->totalEfectivo + $this->totalTarjeta + $this->totalCheque + $this->totalTransferencia;
    }

    public function filtrarTransacciones()
    {
        $this->cargarTransacciones();
    }

    public function render()
    {
        return view('livewire.caja.flujo-de-caja');
    }
}
