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

            // Aperturar automáticamente todas las cajas de la sucursal
            $resultadoCajas = $this->aperturarCajasSucursal();

            DB::commit();

            $this->mensaje = 'Jornada aperturada exitosamente para la fecha ' . $this->fechaApertura . ' en ' . $this->nombreTienda . ' (ID: ' . $jornadaId . ')';

            // Agregar información sobre las cajas aperturadas
            if (!empty($resultadoCajas['mensaje'])) {
                $this->mensaje .= '\n\n' . $resultadoCajas['mensaje'];
            }

            $this->tipoMensaje = 'success';

        } catch (\Exception $e) {
            DB::rollback();
            $this->mensaje = 'Error al aperturar la jornada: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        } finally {
            $this->procesoEnCurso = false;
        }
    }

    /**
     * Apertura automática de todas las cajas de la sucursal
     */
    private function aperturarCajasSucursal()
    {
        $fechaHoy = $this->fechaApertura;
        $contadores = [
            'aperturadas' => 0,
            'yaAbiertas' => 0,
            'nuevas' => 0,
            'errores' => 0
        ];
        $mensajes = [];

        try {
            // 1. Obtener todos los usuarios de la tienda actual
            $usuariosTienda = DB::table('users')
                ->where('tienda_id', $this->tiendaUsuario)
                ->where('estado_id', 1) // Solo usuarios activos
                ->get();

            foreach ($usuariosTienda as $usuario) {
                try {
                    // 2. Verificar si ya existe una caja para este usuario hoy
                    $cajaHoy = DB::table('caja')
                        ->where('users_id', $usuario->id)
                        ->where('tienda_id', $this->tiendaUsuario)
                        ->whereDate('created_at', $fechaHoy)
                        ->first();

                    // Si ya tiene caja hoy y está abierta, no hacer nada
                    if ($cajaHoy && $cajaHoy->estado_caja == 1) {
                        $contadores['yaAbiertas']++;
                        continue;
                    }

                    // 3. Obtener el último balance del usuario en esta tienda
                    $ultimaCaja = DB::table('caja')
                        ->where('users_id', $usuario->id)
                        ->where('tienda_id', $this->tiendaUsuario)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    // 4. Determinar el balance inicial
                    $balanceInicial = 0;
                    if ($ultimaCaja) {
                        // Si hay una caja anterior, usar su último balance
                        $balanceInicial = $ultimaCaja->balance ?? 0;
                    }
                    // Si es nuevo cajero, balance = 0 (ya establecido arriba)

                    // 5. Crear nueva caja para hoy o actualizar existente
                    $cajaIdParaTransaccion = null;

                    if ($cajaHoy) {
                        // Verificar si la caja existe y está cerrada
                        if ($cajaHoy->estado_caja == 0) {
                            // Si el cajero tiene un registro para ese día pero ya se cerró esa caja,
                            // crear un nuevo registro con el último balance de ese cajero
                            $nuevaCajaId = DB::table('caja')->insertGetId([
                                'users_id' => $usuario->id,
                                'tienda_id' => $this->tiendaUsuario,
                                'balance' => $balanceInicial,
                                'estado_caja' => 1, // Abierta
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);

                            $cajaIdParaTransaccion = $nuevaCajaId;
                            $contadores['nuevas']++;
                        } else {
                            // Si existe caja para hoy y está abierta, actualizarla
                            DB::table('caja')
                                ->where('id', $cajaHoy->id)
                                ->update([
                                    'balance' => $balanceInicial,
                                    'updated_at' => now()
                                ]);

                            $cajaIdParaTransaccion = $cajaHoy->id;
                            $contadores['aperturadas']++;
                        }
                    } else {
                        // Crear nueva caja para hoy
                        $nuevaCajaId = DB::table('caja')->insertGetId([
                            'users_id' => $usuario->id,
                            'tienda_id' => $this->tiendaUsuario,
                            'balance' => $balanceInicial,
                            'estado_caja' => 1, // Abierta
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $cajaIdParaTransaccion = $nuevaCajaId;
                        $contadores['nuevas']++;
                    }

                    // 6. Registrar transacción de apertura automática
                    DB::table('transaccion')->insert([
                        'caja_id' => $cajaIdParaTransaccion,
                        'transaccion' => 'apertura_automatica',
                        'efectivo' => $balanceInicial,
                        'tarjeta' => 0,
                        'cheque' => 0,
                        'descripcion' => 'Apertura automática al iniciar jornada - Balance anterior: L.' . number_format($balanceInicial, 2),
                        'created_at' => now(),
                        'update_at' => now()
                    ]);

                } catch (\Exception $e) {
                    $contadores['errores']++;
                    $mensajes[] = "Error con usuario {$usuario->name}: " . $e->getMessage();
                }
            }

            // Preparar mensaje de resultado
            $mensajeResultado = "🏦 CAJAS APERTURADAS:\n";
            $mensajeResultado .= "✅ Cajas aperturadas: {$contadores['aperturadas']}\n";
            $mensajeResultado .= "🆕 Cajas nuevas creadas: {$contadores['nuevas']}\n";
            $mensajeResultado .= "📝 Cajas ya abiertas: {$contadores['yaAbiertas']}\n";

            if ($contadores['errores'] > 0) {
                $mensajeResultado .= "❌ Errores: {$contadores['errores']}\n";
            }

            return [
                'exito' => true,
                'contadores' => $contadores,
                'mensaje' => $mensajeResultado,
                'errores' => $mensajes
            ];

        } catch (\Exception $e) {
            return [
                'exito' => false,
                'mensaje' => "Error general al aperturar cajas: " . $e->getMessage()
            ];
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
