<?php

namespace App\Livewire\GestionDeSucursales;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CierreDeJornada extends Component
{
    public $fechaCierre;
    public $comentario = '';
    public $mostrarAlerta = false;
    public $cajasAbiertas = [];
    public $cajasConDiferencia = [];
    public $mensaje = '';
    public $tipoMensaje = '';
    public $procesoEnCurso = false;
    public $tiendaUsuario;
    public $nombreTienda;

    protected $listeners = ['limpiarMensaje' => 'limpiarMensaje'];

    public function mount()
    {
        $this->fechaCierre = Carbon::now()->format('Y-m-d');
        $this->cargarTiendaUsuario();
    }

    public function cargarTiendaUsuario()
    {
        // Obtener la tienda del usuario autenticado con su información
        $usuario = DB::table('users as u')
            ->leftJoin('tienda as t', 'u.tienda_id', '=', 't.id')
            ->where('u.id', Auth::id())
            ->select('u.tienda_id', 't.denominacion_social')
            ->first();
        
        if ($usuario) {
            $this->tiendaUsuario = $usuario->tienda_id;
            // Usar denominacion_social si existe, sino mostrar ID
            $this->nombreTienda = $usuario->denominacion_social 
                                  ?? 'Tienda #' . $usuario->tienda_id;
        } else {
            $this->tiendaUsuario = null;
            $this->nombreTienda = null;
        }
    }

    public function verificarCondicionesParaCierre()
    {
        $this->resetear();
        
        if (!$this->tiendaUsuario) {
            $this->mensaje = 'Usuario sin tienda asignada. No se puede procesar el cierre.';
            $this->tipoMensaje = 'error';
            return;
        }
        
        try {
            // 1. PRIMERO: Verificar si existe una jornada aperturada para esta fecha y tienda
            $jornadaAperturada = DB::table('jornada')
                ->where('fecha', $this->fechaCierre)
                ->where('tienda_id', $this->tiendaUsuario)
                ->where('apertura', 1)
                ->first();

            if (!$jornadaAperturada) {
                $this->mensaje = "No se puede cerrar la jornada porque no se ha aperturado la jornada para la fecha {$this->fechaCierre} de {$this->nombreTienda}. Debe aperturar la jornada primero.";
                $this->tipoMensaje = 'error';
                return;
            }

            // 2. Verificar si ya está cerrada
            if ($jornadaAperturada->cierre == 1) {
                $this->mensaje = "La jornada para la fecha {$this->fechaCierre} de {$this->nombreTienda} ya está cerrada.";
                $this->tipoMensaje = 'error';
                return;
            }

            // 3. Verificar si hay cajas abiertas (estado = 1) en la tienda del usuario
            $this->cajasAbiertas = DB::table('caja as c')
                ->join('users as u', 'c.users_id', '=', 'u.id')
                ->where('c.estado_caja', 1)
                ->where('u.tienda_id', $this->tiendaUsuario)
                ->whereDate('c.created_at', $this->fechaCierre)
                ->select('c.*')
                ->get()
                ->toArray();

            // 4. Verificar cajas con diferencias en cierres del día en la tienda
            $this->cajasConDiferencia = DB::table('cierre_de_caja as cc')
                ->join('caja as c', 'cc.caja_id', '=', 'c.id')
                ->join('users as u', 'c.users_id', '=', 'u.id')
                ->where('cc.diferencia_efectivo', '!=', 0)
                ->where('u.tienda_id', $this->tiendaUsuario)
                ->whereDate('cc.created_at', $this->fechaCierre)
                ->select('c.id', 'c.users_id', 'cc.diferencia_efectivo', 'cc.created_at')
                ->get()
                ->toArray();

            // 5. Mostrar alertas si hay problemas o proceder directamente
            if (count($this->cajasAbiertas) > 0 || count($this->cajasConDiferencia) > 0) {
                $this->mostrarAlerta = true;
            } else {
                // No hay problemas, proceder directamente
                $this->procesarCierreJornada();
            }

        } catch (\Exception $e) {
            $this->mensaje = 'Error al verificar condiciones: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        }
    }

    public function procesarCierreJornada()
    {
        if ($this->procesoEnCurso) return;
        
        $this->procesoEnCurso = true;

        try {
            DB::beginTransaction();

            // 1. Buscar la jornada aperturada para esta fecha y tienda
            $jornada = DB::table('jornada')
                ->where('fecha', $this->fechaCierre)
                ->where('tienda_id', $this->tiendaUsuario)
                ->where('apertura', 1)
                ->where('cierre', 0)
                ->first();

            if (!$jornada) {
                throw new \Exception('No se encontró una jornada aperturada para cerrar');
            }

            // 2. Actualizar la jornada: apertura = 0 y cierre = 1
            DB::table('jornada')
                ->where('id', $jornada->id)
                ->update([
                    'apertura' => 0,
                    'cierre' => 1,
                    'user_id_cierre' => Auth::id(),
                    'comentario' => $this->comentario,
                    'updated_at' => now()
                ]);

            // 2. Procesar cajas abiertas de la tienda
            foreach ($this->cajasAbiertas as $cajaData) {
                // Convertir array a objeto si es necesario
                $caja = is_array($cajaData) ? (object) $cajaData : $cajaData;
                
                // Cambiar estado a cerrada (2)
                DB::table('caja')
                    ->where('id', $caja->id)
                    ->update([
                        'estado_caja' => 2,
                        'updated_at' => now()
                    ]);

                // Si tiene balance, registrar como cierre con diferencia
                if (isset($caja->balance) && $caja->balance > 0) {
                    DB::table('cierre_de_caja')->insert([
                        'caja_id' => $caja->id,
                        'total_efectivo' => $caja->balance,
                        'conteo_efectivo' => 0,
                        'diferencia_efectivo' => $caja->balance, // El balance como diferencia
                        'total_tarjeta' => 0,
                        'conteo_tarjeta' => 0,
                        'diferencia_tarjeta' => 0,
                        'total_cheque' => 0,
                        'conteo_cheque' => 0,
                        'diferencia_cheque' => 0,
                        // Inicializar todas las denominaciones en 0
                        '1' => 0, '2' => 0, '5' => 0, '10' => 0, '20' => 0, '50' => 0,
                        '100' => 0, '200' => 0, '500' => 0,
                        '001' => 0, '002' => 0, '005' => 0, '010' => 0, '020' => 0, '050' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Poner balance de la caja en 0
                    DB::table('caja')
                        ->where('id', $caja->id)
                        ->update(['balance' => 0]);
                }
            }

            // 3. También cambiar estado de cajas de la tienda que ya tenían cierres pero estaban abiertas
            $cajasAbiertasAdicionales = DB::table('caja as c')
                ->join('users as u', 'c.users_id', '=', 'u.id')
                ->where('c.estado_caja', 1)
                ->where('u.tienda_id', $this->tiendaUsuario)
                ->whereDate('c.created_at', $this->fechaCierre)
                ->pluck('c.id');

            DB::table('caja')
                ->whereIn('id', $cajasAbiertasAdicionales)
                ->update([
                    'estado_caja' => 2,
                    'updated_at' => now()
                ]);

            DB::commit();

            $this->mensaje = 'Jornada cerrada exitosamente para la fecha ' . $this->fechaCierre . 
                            ' en ' . $this->nombreTienda . '. ' . count($this->cajasAbiertas) . ' cajas procesadas.';
            $this->tipoMensaje = 'success';
            
            $this->resetear();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensaje = 'Error al procesar cierre de jornada: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        } finally {
            $this->procesoEnCurso = false;
        }
    }

    public function cancelarCierre()
    {
        $this->resetear();
        $this->mensaje = 'Cierre de jornada cancelado';
        $this->tipoMensaje = 'info';
    }

    public function limpiarMensaje()
    {
        $this->mensaje = '';
        $this->tipoMensaje = '';
    }

    private function resetear()
    {
        $this->mostrarAlerta = false;
        $this->cajasAbiertas = [];
        $this->cajasConDiferencia = [];
    }

    public function render()
    {
        return view('livewire.gestion-de-sucursales.cierre-de-jornada');
    }
}
