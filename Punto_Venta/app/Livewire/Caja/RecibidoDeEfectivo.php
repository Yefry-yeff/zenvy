<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;

class RecibidoDeEfectivo extends Component
{
    #[Validate('required|numeric|min:0.01')]
    public $monto = '';

    #[Validate('nullable|string|max:255')]
    public $comentarios = '';

    public $cajaActual = null;
    public $mensajeExito = '';
    public $mensajeError = '';

    public function mount()
    {
        $this->validarJornadaAbierta();
        $this->cargarCajaActual();
    }

    public function validarJornadaAbierta()
    {
        $usuario = Auth::user();
        
        if (!$usuario->tienda_id) {
            $this->mensajeError = 'Usuario sin tienda asignada. No se pueden realizar operaciones de caja.';
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
            $this->mensajeError = 'No se pueden realizar operaciones de caja porque la jornada no está aperturada para hoy. Debe aperturar la jornada primero.';
            return false;
        }

        return true;
    }

    public function cargarCajaActual()
    {
        $usuario = Auth::user();
        
        // Verificar que el usuario tenga tienda asignada
        if (!$usuario || !$usuario->tienda_id) {
            $this->cajaActual = null;
            return;
        }
        
        // Obtener caja actual con la fecha de apertura más reciente
        $this->cajaActual = DB::table('caja as c')
            ->leftJoin('apertura_caja as ac', function($join) {
                $join->on('c.id', '=', 'ac.caja_id')
                     ->whereDate('ac.fecha_apertura', today());
            })
            ->where('c.users_id', $usuario->id)
            ->where('c.tienda_id', $usuario->tienda_id)
            ->where('c.estado_caja', 1) // 1 = abierta
            ->select('c.*', 'ac.fecha_apertura')
            ->orderBy('c.created_at', 'desc')
            ->first();
    }

    public function recibirEfectivo()
    {
        // Validar que la jornada esté abierta antes de proceder
        if (!$this->validarJornadaAbierta()) {
            return;
        }

        $this->validate();

        if (!$this->cajaActual) {
            $this->mensajeError = 'No hay una caja abierta para recibir efectivo.';
            return;
        }

        try {
            DB::beginTransaction();

            $usuario = Auth::user();
            $montoNumerico = floatval($this->monto);

            // Crear registro en transacciones
            $transaccionId = DB::table('transaccion')->insertGetId([
                'caja_id' => $this->cajaActual->id,
                'transaccion' => 'Recibo de Efectivo',
                'efectivo' => $montoNumerico,
                'tarjeta' => 0.00,
                'cheque' => 0.00,
                'descripcion' => $this->comentarios ?: 'Recepción de efectivo',
                'created_at' => now(),
                'update_at' => now()
            ]);

            // Actualizar saldo de la caja
            DB::table('caja')
                ->where('id', $this->cajaActual->id)
                ->update([
                    'balance' => DB::raw('balance + ' . $montoNumerico),
                    'updated_at' => now()
                ]);

            DB::commit();

            // Recargar información de caja
            $this->cargarCajaActual();

            // Mensaje de éxito
            $this->mensajeExito = "Se han recibido L." . number_format($montoNumerico, 2) . " correctamente. Nuevo saldo: L." . number_format($this->cajaActual->balance, 2);
            
            // Limpiar formulario
            $this->reset(['monto', 'comentarios', 'mensajeError']);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = 'Error al procesar la recepción de efectivo: ' . $e->getMessage();
        }
    }

    public function limpiarMensajes()
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
    }

    public function render()
    {
        return view('livewire.caja.recibido-de-efectivo');
    }
}
