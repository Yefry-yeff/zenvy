<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;

class EntregaDeEfectivo extends Component
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
        
        $this->cajaActual = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->where('estado_caja', 1) // 1 = abierta
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function entregarEfectivo()
    {
        // Validar que la jornada esté abierta antes de proceder
        if (!$this->validarJornadaAbierta()) {
            return;
        }

        $this->validate();

        if (!$this->cajaActual) {
            $this->mensajeError = 'No hay una caja abierta para entregar efectivo.';
            return;
        }

        $montoNumerico = floatval($this->monto);

        // Verificar que hay suficiente saldo en caja
        if ($this->cajaActual->balance < $montoNumerico) {
            $this->mensajeError = 'No hay suficiente saldo en caja. Saldo actual: L.' . number_format($this->cajaActual->balance, 2);
            return;
        }

        try {
            DB::beginTransaction();

            $usuario = Auth::user();

            // Crear registro en transacciones (como salida de efectivo)
            $transaccionId = DB::table('transaccion')->insertGetId([
                'caja_id' => $this->cajaActual->id,
                'transaccion' => 'Entrega de Efectivo',
                'efectivo' => -$montoNumerico, // Negativo porque es salida
                'tarjeta' => 0.00,
                'cheque' => 0.00,
                'descripcion' => $this->comentarios ?: 'Entrega de efectivo',
                'created_at' => now(),
                'update_at' => now()
            ]);

            // Actualizar saldo de la caja (restar el monto)
            DB::table('caja')
                ->where('id', $this->cajaActual->id)
                ->update([
                    'balance' => DB::raw('balance - ' . $montoNumerico),
                    'updated_at' => now()
                ]);

            DB::commit();

            // Recargar información de caja
            $this->cargarCajaActual();

            // Mensaje de éxito
            $this->mensajeExito = "Se han entregado L." . number_format($montoNumerico, 2) . " correctamente. Nuevo saldo: L." . number_format($this->cajaActual->balance, 2);
            
            // Limpiar formulario
            $this->reset(['monto', 'comentarios', 'mensajeError']);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = 'Error al procesar la entrega de efectivo: ' . $e->getMessage();
        }
    }

    public function limpiarMensajes()
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
    }

    public function render()
    {
        return view('livewire.caja.entrega-de-efectivo');
    }
}
