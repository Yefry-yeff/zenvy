<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SaldoInicial extends Component
{
    public $monto = '';
    public $descripcion = '';
    public $cajaActual;
    public $mensaje = '';
    public $tipoMensaje = '';

    public function mount()
    {
        $this->cargarCajaActual();
    }

    public function cargarCajaActual()
    {
        // Buscar la caja del usuario actual que esté cerrada (estado_caja = 2)
        $this->cajaActual = DB::table('caja')
            ->where('users_id', Auth::id())
            ->where('estado_caja', 2) // Cajas cerradas
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function establecerSaldoInicial()
    {
        $this->validate([
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:255'
        ], [
            'monto.required' => 'El monto es obligatorio',
            'monto.numeric' => 'El monto debe ser un número válido',
            'monto.min' => 'El monto debe ser mayor a 0',
            'descripcion.max' => 'La descripción no puede exceder 255 caracteres'
        ]);

        if (!$this->cajaActual) {
            $this->mensaje = 'No se encontró una caja cerrada (estado 2) para abrir.';
            $this->tipoMensaje = 'error';
            return;
        }

        try {
            DB::beginTransaction();

            // Actualizar la caja: establecer saldo inicial y cambiar estado a abierto (1)
            DB::table('caja')
                ->where('id', $this->cajaActual->id)
                ->update([
                    'balance' => floatval($this->monto),
                    'estado_caja' => 1, // Abierto
                    'updated_at' => now()
                ]);

            // Registrar la transacción de saldo inicial
            DB::table('transaccion')->insert([
                'caja_id' => $this->cajaActual->id,
                'transaccion' => 'saldo_inicial',
                'efectivo' => floatval($this->monto),
                'tarjeta' => 0,
                'cheque' => 0,
                'descripcion' => $this->descripcion ?: 'Establecimiento de saldo inicial',
                'created_at' => now(),
                'update_at' => now()
            ]);

            DB::commit();

            $this->mensaje = 'Saldo inicial establecido correctamente. La caja está ahora abierta con L. ' . number_format($this->monto, 2);
            $this->tipoMensaje = 'success';
            
            // Limpiar formulario
            $this->reset(['monto', 'descripcion']);
            
            // Recargar información de la caja
            $this->cargarCajaActual();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensaje = 'Error al establecer el saldo inicial: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        }
    }

    public function render()
    {
        return view('livewire.caja.saldo-inicial');
    }
}
