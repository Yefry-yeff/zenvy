<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SaldoInicial extends Component
{
    public $monto = '';
    public $descripcion = '';
    public $cajaActual;
    public $mensaje = '';
    public $tipoMensaje = '';

    public function mount()
    {
        $this->validarJornadaAbierta();
        $this->verificarOCrearRegistroCaja();
        $this->cargarCajaActual();
    }

    public function validarJornadaAbierta()
    {
        $usuario = Auth::user();
        
        if (!$usuario->tienda_id) {
            $this->mensaje = 'Usuario sin tienda asignada. No se pueden realizar operaciones de caja.';
            $this->tipoMensaje = 'error';
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
            $this->mensaje = 'No se pueden realizar operaciones de caja porque la jornada no está aperturada para hoy. Debe aperturar la jornada primero.';
            $this->tipoMensaje = 'error';
            return false;
        }

        return true;
    }

    public function verificarOCrearRegistroCaja()
    {
        $usuario = Auth::user();
        
        // Verificar que el usuario tenga tienda asignada
        if (!$usuario || !$usuario->tienda_id) {
            return;
        }

        // Verificar si existe un registro de caja para este usuario y tienda
        $cajaExistente = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->first();

        // Si no existe registro de caja, crear uno con estado 2 (cerrado)
        if (!$cajaExistente) {
            try {
                DB::table('caja')->insert([
                    'users_id' => $usuario->id,
                    'tienda_id' => $usuario->tienda_id,
                    'balance' => 0.00,
                    'estado_caja' => 2, // Cerrado
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                Log::info("Registro de caja creado automáticamente para usuario {$usuario->id} en tienda {$usuario->tienda_id}");
            } catch (\Exception $e) {
                Log::error("Error al crear registro de caja: " . $e->getMessage());
            }
        }
    }

    public function cargarCajaActual()
    {
        $usuario = Auth::user();
        
        // Verificar que el usuario tenga tienda asignada
        if (!$usuario || !$usuario->tienda_id) {
            $this->cajaActual = null;
            $this->mensaje = 'Usuario sin tienda asignada. No se puede cargar información de caja.';
            $this->tipoMensaje = 'error';
            return;
        }

        // Buscar la caja del usuario actual en su tienda actual que esté cerrada (estado_caja = 2)
        $this->cajaActual = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->where('estado_caja', 2) // Cajas cerradas (listas para abrir)
            ->orderBy('created_at', 'desc')
            ->first();
        
        // Si no se encuentra caja, establecer mensaje informativo
        if (!$this->cajaActual) {
            $this->mensaje = 'No se encontró caja en estado cerrado (estado 2) para el usuario en la sucursal actual.';
            $this->tipoMensaje = 'info';
        } else {
            // Limpiar mensaje si se encuentra caja
            $this->mensaje = '';
            $this->tipoMensaje = '';
        }
    }

    public function establecerSaldoInicial()
    {
        // Validar que la jornada esté abierta antes de proceder
        if (!$this->validarJornadaAbierta()) {
            return;
        }

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
                    'fecha_apertura' => now(), // Fecha y hora de apertura
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
