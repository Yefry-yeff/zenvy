<?php

namespace App\Livewire\GestionDeSucursales;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AperturaDeJornada extends Component
{
    public $fechaApertura;
    public $comentario = '';
    public $mensaje = '';
    public $tipoMensaje = '';
    public $procesoEnCurso = false;
    public $tiendaUsuario;
    public $nombreTienda;

    public function mount()
    {
        // Solo permitir apertura de la fecha actual
        $this->fechaApertura = Carbon::now()->format('Y-m-d');
        $this->cargarTiendaUsuario();
    }

    public function cargarTiendaUsuario()
    {
        $usuario = Auth::user();
        
        if ($usuario && $usuario->tienda_id) {
            $this->tiendaUsuario = $usuario->tienda_id;
            
            // Obtener nombre de la tienda
            $tienda = DB::table('tienda')
                ->where('id', $this->tiendaUsuario)
                ->first();
            
            $this->nombreTienda = $tienda ? $tienda->denominacion_social : 'Tienda no encontrada';
        }
    }

    public function validarYProcesarApertura()
    {
        $this->resetear();
        
        if (!$this->tiendaUsuario) {
            $this->mensaje = 'Usuario sin tienda asignada. No se puede procesar la apertura.';
            $this->tipoMensaje = 'error';
            return;
        }

        // Validar que solo se pueda aperturar la fecha actual
        $fechaActual = Carbon::now()->format('Y-m-d');
        if ($this->fechaApertura !== $fechaActual) {
            $this->mensaje = 'Solo se puede aperturar la jornada de la fecha actual (' . $fechaActual . '). No se pueden aperturar fechas anteriores.';
            $this->tipoMensaje = 'error';
            return;
        }

        try {
            // 1. Verificar si ya existe una jornada aperturada para hoy
            $jornadaHoy = DB::table('jornada')
                ->where('fecha', $this->fechaApertura)
                ->where('tienda_id', $this->tiendaUsuario)
                ->first();

            if ($jornadaHoy && $jornadaHoy->apertura == 1) {
                $this->mensaje = "Ya existe una jornada aperturada para la fecha {$this->fechaApertura} en {$this->nombreTienda}.";
                $this->tipoMensaje = 'error';
                return;
            }

            // 2. Validar cierre del día anterior (solo si no es la primera vez)
            $fechaAnterior = Carbon::parse($this->fechaApertura)->subDay()->format('Y-m-d');
            
            $jornadaAnterior = DB::table('jornada')
                ->where('tienda_id', $this->tiendaUsuario)
                ->where('fecha', $fechaAnterior)
                ->first();

            // Si existe registro del día anterior, debe estar cerrado
            if ($jornadaAnterior && $jornadaAnterior->cierre != 1) {
                $this->mensaje = "No se puede aperturar la jornada porque la jornada del día anterior ({$fechaAnterior}) no está cerrada. Debe cerrar la jornada anterior primero.";
                $this->tipoMensaje = 'error';
                return;
            }

            // 3. Si no hay registros anteriores, es la primera vez (permitir)
            $primerRegistro = DB::table('jornada')
                ->where('tienda_id', $this->tiendaUsuario)
                ->exists();

            if (!$primerRegistro) {
                $this->mensaje = "Esta será la primera jornada aperturada para {$this->nombreTienda}. ¡Bienvenido al sistema!";
                $this->tipoMensaje = 'info';
            }

            // 4. Proceder con la apertura
            $this->procesarAperturaJornada();

        } catch (\Exception $e) {
            $this->mensaje = 'Error al validar condiciones: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        }
    }

    public function procesarAperturaJornada()
    {
        if ($this->procesoEnCurso) return;
        
        $this->procesoEnCurso = true;

        try {
            DB::beginTransaction();

            // Siempre crear un NUEVO registro de jornada
            $jornadaId = DB::table('jornada')->insertGetId([
                'fecha' => $this->fechaApertura,
                'tienda_id' => $this->tiendaUsuario,
                'apertura' => 1,
                'cierre' => 0,
                'user_id_apertura' => Auth::id(),
                'comentario' => $this->comentario,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            $this->mensaje = 'Jornada aperturada exitosamente para la fecha ' . $this->fechaApertura . ' en ' . $this->nombreTienda . ' (ID: ' . $jornadaId . ')';
            $this->tipoMensaje = 'success';

        } catch (\Exception $e) {
            DB::rollback();
            $this->mensaje = 'Error al aperturar la jornada: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        } finally {
            $this->procesoEnCurso = false;
        }
    }

    public function resetear()
    {
        $this->mensaje = '';
        $this->tipoMensaje = '';
    }

    public function render()
    {
        return view('livewire.gestion-de-sucursales.apertura-de-jornada');
    }
}
