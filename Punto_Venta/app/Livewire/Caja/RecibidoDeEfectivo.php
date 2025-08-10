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
        $this->cargarCajaActual();
    }

    public function cargarCajaActual()
    {
        $usuario = Auth::user();
        
        $this->cajaActual = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('estado_caja', 1) // 1 = abierta
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function recibirEfectivo()
    {
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
