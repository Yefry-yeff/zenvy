<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SaldoInicial extends Component
{
    public $saldoInicial = 2000.00; // Saldo fijo del sistema
    public $cajaActual;
    public $mensaje = '';
    public $tipoMensaje = 'info';

    public function mount()
    {
        $this->cargarInformacionCaja();
    }

    public function cargarInformacionCaja()
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->tienda_id) {
            $this->mensaje = 'Usuario sin tienda asignada.';
            $this->tipoMensaje = 'error';
            return;
        }

        // Buscar o crear registro de caja
        $this->cajaActual = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->first();

        if (!$this->cajaActual) {
            // Crear registro de caja con saldo inicial
            try {
                DB::table('caja')->insert([
                    'users_id' => $usuario->id,
                    'tienda_id' => $usuario->tienda_id,
                    'balance' => $this->saldoInicial,
                    'estado_caja' => 1, // Siempre activa
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $this->cajaActual = DB::table('caja')
                    ->where('users_id', $usuario->id)
                    ->where('tienda_id', $usuario->tienda_id)
                    ->first();

                Log::info("Caja creada automáticamente - Usuario: {$usuario->id}, Tienda: {$usuario->tienda_id}");
            } catch (\Exception $e) {
                Log::error("Error al crear caja: " . $e->getMessage());
                $this->mensaje = 'Error al crear la caja: ' . $e->getMessage();
                $this->tipoMensaje = 'error';
                return;
            }
        }

        // Asegurar que la caja esté activa con el saldo correcto
        if ($this->cajaActual && $this->cajaActual->estado_caja != 1) {
            DB::table('caja')
                ->where('id', $this->cajaActual->id)
                ->update([
                    'estado_caja' => 1,
                    'balance' => $this->saldoInicial,
                    'updated_at' => now()
                ]);

            $this->cajaActual = DB::table('caja')
                ->where('users_id', $usuario->id)
                ->where('tienda_id', $usuario->tienda_id)
                ->first();
        }

        $this->mensaje = 'Su caja está siempre activa con un saldo inicial de L. ' . number_format($this->saldoInicial, 2);
        $this->tipoMensaje = 'success';
    }

    public function render()
    {
        return view('livewire.caja.saldo-inicial');
    }
}
