<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SaldoInicial extends Component
{
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

        // Si no existe registro de caja, crear uno
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

        // Buscar caja del usuario
        $this->cajaActual = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->first();
        
        // Verificar estado de la caja
        if (!$this->cajaActual) {
            // No existe registro de caja - Se puede aperturar
            $this->mensaje = 'No existe registro de caja. Se creará uno nuevo al aperturar.';
            $this->tipoMensaje = 'info';
        } elseif ($this->cajaActual->estado_caja == 1) {
            // Caja ya está abierta - No se puede aperturar
            $this->cajaActual = null;
            $this->mensaje = 'La caja ya está abierta. No es necesario aperturarla nuevamente.';
            $this->tipoMensaje = 'info';
        } elseif ($this->cajaActual->estado_caja == 2) {
            // Caja está cerrada - Se puede aperturar
            $this->mensaje = '';
            $this->tipoMensaje = '';
        } else {
            // Estado no reconocido
            $this->cajaActual = null;
            $this->mensaje = 'Estado de caja no reconocido. Contacte al administrador.';
            $this->tipoMensaje = 'error';
        }
    }

    public function aperturarCaja()
    {
        // Validar que la jornada esté abierta antes de proceder
        if (!$this->validarJornadaAbierta()) {
            return;
        }

        $this->validate([
            'descripcion' => 'nullable|string|max:255'
        ], [
            'descripcion.max' => 'La descripción no puede exceder 255 caracteres'
        ]);

        $usuario = Auth::user();
        $fechaActual = date('Y-m-d');

        try {
            DB::beginTransaction();

            $balanceExistente = 0;
            $cajaId = null;

            // Caso 1: No existe registro de caja - Crear nuevo
            if (!$this->cajaActual) {
                $cajaId = DB::table('caja')->insertGetId([
                    'users_id' => $usuario->id,
                    'tienda_id' => $usuario->tienda_id,
                    'balance' => 0.00,
                    'estado_caja' => 1, // Abierto
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $balanceExistente = 0;
                $tipoOperacion = 'Nueva caja creada y aperturada';
            }
            // Caso 2: Existe caja cerrada - Aperturar
            elseif ($this->cajaActual->estado_caja == 2) {
                $cajaId = $this->cajaActual->id;
                $balanceExistente = floatval($this->cajaActual->balance ?? 0);
                
                // Cambiar estado de la caja a abierto
                DB::table('caja')
                    ->where('id', $cajaId)
                    ->update([
                        'estado_caja' => 1, // Abierto
                        'updated_at' => now()
                    ]);
                
                $tipoOperacion = 'Caja aperturada';
            }
            else {
                throw new \Exception('La caja debe estar cerrada (estado 2) para poder aperturarla.');
            }

            // Verificar si ya hubo cierre hoy (existe registro en apertura_caja para hoy)
            $aperturaHoy = DB::table('apertura_caja')
                ->where('caja_id', $cajaId)
                ->whereDate('fecha_apertura', $fechaActual)
                ->exists();

            // SIEMPRE crear un nuevo registro en apertura_caja para cada apertura
            DB::table('apertura_caja')->insert([
                'caja_id' => $cajaId,
                'balance_apertura' => $balanceExistente,
                'fecha_apertura' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $tipoOperacion .= $aperturaHoy ? ' (Nueva sesión)' : ' (Primera sesión del día)';

            // Registrar la transacción de apertura de caja
            DB::table('transaccion')->insert([
                'caja_id' => $cajaId,
                'transaccion' => 'apertura_caja',
                'efectivo' => $balanceExistente,
                'tarjeta' => 0,
                'cheque' => 0,
                'descripcion' => $this->descripcion ?: 'Apertura de caja',
                'created_at' => now(),
                'update_at' => now()
            ]);

            DB::commit();

            $this->mensaje = $tipoOperacion . ' correctamente con L. ' . number_format($balanceExistente, 2) . '. La caja está ahora disponible para operar.';
            $this->tipoMensaje = 'success';
            
            // Limpiar formulario
            $this->reset(['descripcion']);
            
            // Recargar información de la caja
            $this->cargarCajaActual();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensaje = 'Error al aperturar la caja: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        }
    }

    public function render()
    {
        return view('livewire.caja.saldo-inicial');
    }
}
