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
            // 1. VALIDACIÓN PRINCIPAL: Verificar si ya existe una jornada abierta para hoy
            $jornadaHoy = DB::table('jornada')
                ->where('fecha', $this->fechaApertura)
                ->where('tienda_id', $this->tiendaUsuario)
                ->where('apertura', 1)
                ->where('cierre', 0)
                ->first();

            if ($jornadaHoy) {
                $this->mensaje = "❌ Ya existe una jornada abierta para la fecha {$this->fechaApertura} en {$this->nombreTienda}. No se puede aperturar nuevamente.";
                $this->tipoMensaje = 'error';
                return;
            }

            // COMENTADO: Permitir múltiples aperturas/cierres en el mismo día
            /*
            // 2. Verificar si existe algún registro para esta fecha (independientemente del estado)
            $jornadaExistente = DB::table('jornada')
                ->where('fecha', $this->fechaApertura)
                ->where('tienda_id', $this->tiendaUsuario)
                ->first();

            if ($jornadaExistente && $jornadaExistente->cierre == 1) {
                $this->mensaje = "❌ Ya existe una jornada cerrada para la fecha {$this->fechaApertura} en {$this->nombreTienda}. No se puede aperturar nuevamente.";
                $this->tipoMensaje = 'error';
                return;
            }
            */

            // 3. VALIDACIÓN ADICIONAL: Verificar jornadas no cerradas de días anteriores
            $jornadasAbiertas = DB::table('jornada')
                ->where('tienda_id', $this->tiendaUsuario)
                ->where('fecha', '<', $this->fechaApertura)
                ->where('apertura', 1)
                ->where('cierre', 0)
                ->orderBy('fecha', 'desc')
                ->get();

            if ($jornadasAbiertas->count() > 0) {
                $fechasTexto = $jornadasAbiertas->pluck('fecha')->map(function($fecha) {
                    return date('d/m/Y', strtotime($fecha));
                })->implode(', ');
                
                $this->mensaje = "❌ NO se puede aperturar la jornada porque existen jornadas sin cerrar de días anteriores: " . $fechasTexto . 
                    ". Debe cerrar todas las jornadas pendientes antes de aperturar una nueva.";
                $this->tipoMensaje = 'error';
                return;
            }

            // 4. Todo está bien, proceder con la apertura
            $esFirstTime = !DB::table('jornada')
                ->where('tienda_id', $this->tiendaUsuario)
                ->exists();

            if ($esFirstTime) {
                $this->mensaje = "✅ Esta será la primera jornada para {$this->nombreTienda}. Creando registro...";
            } else {
                $this->mensaje = "✅ Validaciones completadas. Creando nueva jornada para {$this->nombreTienda}...";
            }
            
            $this->tipoMensaje = 'info';
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
