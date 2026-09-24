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
    public $cajaSeleccionada = ''; // Nueva propiedad para filtro de caja
    public $cajasDisponibles = []; // Lista de cajas disponibles
    public $esAdministrador = false; // Verificar si el usuario es admin
    public $totalEfectivo = 0;
    public $totalTarjeta = 0;
    public $totalCheque = 0;
    public $totalTransferencia = 0;
    public $totalGeneral = 0;

    public function mount()
    {
        $this->fechaFiltro = Carbon::now()->format('Y-m-d');

        // Verificar si el usuario es administrador
        $usuario = Auth::user();
        $this->esAdministrador = $usuario && $usuario->rol && $usuario->rol->txt_nombre === 'Admin';

        // Si es administrador, cargar todas las cajas de la tienda
        if ($this->esAdministrador && $usuario->tienda_id) {
            $this->cajasDisponibles = DB::table('caja')
                ->join('users', 'caja.users_id', '=', 'users.id')
                ->where('caja.tienda_id', $usuario->tienda_id)
                ->select(
                    'caja.id',
                    'users.name as usuario_nombre'
                )
                ->orderBy('caja.id')
                ->get()
                ->toArray();
        }

        $this->cargarTransacciones();
    }

    public function cargarTransacciones()
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->tienda_id) {
            $this->transacciones = [];
            return;
        }

        // Obtener las cajas según el rol del usuario
        if ($this->esAdministrador) {
            // Si es admin y seleccionó una caja específica
            if ($this->cajaSeleccionada) {
                $cajas = collect([$this->cajaSeleccionada]);
            } else {
                // Si es admin pero no seleccionó caja, mostrar todas las cajas de la tienda
                $cajas = DB::table('caja')
                    ->where('tienda_id', $usuario->tienda_id)
                    ->pluck('id');
            }
        } else {
            // Si no es admin, solo sus propias cajas
            $cajas = DB::table('caja')
                ->where('users_id', $usuario->id)
                ->where('tienda_id', $usuario->tienda_id)
                ->pluck('id');
        }

        if ($cajas->isEmpty()) {
            $this->transacciones = [];
            return;
        }

        // Construir la consulta de transacciones
        $query = DB::table('transaccion')
            ->leftJoin('caja', 'transaccion.caja_id', '=', 'caja.id')
            ->leftJoin('users', 'caja.users_id', '=', 'users.id')
            ->whereIn('transaccion.caja_id', $cajas)
            ->select(
                'transaccion.*',
                'users.name as usuario_caja'
            );

        // Aplicar filtro de fecha si está seleccionado
        if ($this->fechaFiltro) {
            $query->whereDate('transaccion.created_at', $this->fechaFiltro);
        }

        // Aplicar filtro de tipo de transacción si está seleccionado
        if ($this->tipoTransaccionFiltro) {
            $query->where('transaccion.transaccion', $this->tipoTransaccionFiltro);
        }

        // Obtener transacciones ordenadas por fecha más reciente
        $this->transacciones = $query->orderBy('transaccion.created_at', 'desc')
            ->get()
            ->map(function ($transaccion) {
                // Solo ocultar valores para cierre, pero mostrar efectivo para apertura_caja
                $esCierre = strtolower($transaccion->transaccion) === 'cierre';
                $esApertura = strtolower($transaccion->transaccion) === 'apertura_caja';

                return [
                    'id' => $transaccion->id,
                    'caja_id' => $transaccion->caja_id,
                    'nombre_caja' => $transaccion->nombre_caja ?? 'N/A',
                    'usuario_caja' => $transaccion->usuario_caja ?? 'N/A',
                    'transaccion' => $transaccion->transaccion,
                    'efectivo' => $esCierre ? 0 : ($transaccion->efectivo ?? 0),
                    'tarjeta' => ($esCierre || $esApertura) ? 0 : ($transaccion->tarjeta ?? 0),
                    'cheque' => ($esCierre || $esApertura) ? 0 : ($transaccion->cheque ?? 0),
                    'transferencia' => ($esCierre || $esApertura) ? 0 : ($transaccion->transferencia ?? 0),
                    'descripcion' => $transaccion->descripcion,
                    'created_at' => $transaccion->created_at,
                    'total' => $esCierre ? 0 : (($transaccion->efectivo ?? 0) + (($esCierre || $esApertura) ? 0 : ($transaccion->tarjeta ?? 0)) + (($esCierre || $esApertura) ? 0 : ($transaccion->cheque ?? 0)) + (($esCierre || $esApertura) ? 0 : ($transaccion->transferencia ?? 0)))
                ];
            })
            ->toArray();

        $this->calcularTotales();
    }

    public function updatedCajaSeleccionada()
    {
        $this->cargarTransacciones();
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
