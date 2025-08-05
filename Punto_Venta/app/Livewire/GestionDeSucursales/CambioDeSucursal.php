<?php

namespace App\Livewire\GestionDeSucursales;

use Livewire\Component;
use App\Models\User;
use App\Models\Tiendas;
use App\Models\Rol;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CambioDeSucursal extends Component
{
    public $buscarUsuario = '';
    public $usuarioSeleccionado = null;
    public $usuariosSugeridos = [];
    public $mostrarSugerencias = false;

    // Datos del usuario seleccionado
    public $nombreUsuario = '';
    public $emailUsuario = '';
    public $sucursalActual = '';
    public $rolUsuario = '';
    public $sucursalActualId = null;

    // Nueva sucursal
    public $nuevaSucursalId = null;
    public $tiendas = [];

    // Modales y mensajes
    public $mostrarModalConfirmacion = false;
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    // Validación de campos obligatorios
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $camposConError = [];

    public function mount()
    {
        $this->cargarTiendas();
    }

    public function updatedNuevaSucursalId()
    {
        // Esta función se ejecuta automáticamente cuando cambia nuevaSucursalId
        // Fuerza la actualización del estado del botón

        // Limpiar errores de validación para el campo sucursal
        $this->camposConError = array_filter($this->camposConError, fn($campo) => $campo !== 'nuevaSucursalId');
        if (empty($this->camposConError)) {
            $this->mostrarAlerta = false;
            $this->mensajeAlerta = '';
        }
    }

    public function getBotonHabilitadoProperty()
    {
        return $this->usuarioSeleccionado &&
               $this->nuevaSucursalId &&
               $this->nuevaSucursalId != $this->sucursalActualId;
    }

    public function updatedBuscarUsuario()
    {
        if (strlen($this->buscarUsuario) >= 1) {
            $this->buscarUsuarios();
        } else {
            $this->mostrarTodosUsuarios();
        }
        $this->mostrarSugerencias = true;
    }

    public function enfocarUsuario()
    {
        $this->mostrarTodosUsuarios();
        $this->mostrarSugerencias = true;
    }

    public function mostrarTodosUsuarios()
    {
        try {
            $this->usuariosSugeridos = User::with(['tienda', 'rol'])
                ->where('estado_id', 1)
                ->orderBy('name')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar todos los usuarios', [
                'mensaje' => $e->getMessage()
            ]);
            $this->usuariosSugeridos = [];
        }
    }

    public function buscarUsuarios()
    {
        try {
            $this->usuariosSugeridos = User::with(['tienda', 'rol'])
                ->where(function($query) {
                    $query->where('name', 'like', '%' . $this->buscarUsuario . '%')
                          ->orWhere('email', 'like', '%' . $this->buscarUsuario . '%');
                })
                ->where('estado_id', 1) // Solo usuarios activos
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al buscar usuarios', [
                'mensaje' => $e->getMessage(),
                'busqueda' => $this->buscarUsuario
            ]);
            $this->usuariosSugeridos = [];
        }
    }
public function seleccionarUsuario($usuarioId)
{
    try {
        $usuario = User::with('tienda', 'rol')->findOrFail($usuarioId);

        // Asignar solo lo necesario para mostrar al usuario
        $this->usuarioSeleccionado = true;
        $this->buscarUsuario = $usuario->name;
        $this->nombreUsuario = $usuario->name;
        $this->emailUsuario = $usuario->email;
        $this->sucursalActual = $usuario->tienda->denominacion_social ?? 'Sin asignar';
        $this->sucursalActualId = $usuario->tienda_id ?? null;
        $this->rolUsuario = $usuario->rol->txt_nombre ?? 'Sin rol';

        // No tocar aquí lógica de validaciones o sucursales para evitar parpadeo
        $this->usuariosSugeridos = [];
        $this->mostrarSugerencias = false;

    } catch (\Exception $e) {
        $this->mostrarModalError = true;
        $this->mensajeModalError = 'Error al seleccionar el usuario.';
    }
}


    public function limpiarSeleccion()
    {
        $this->usuarioSeleccionado = null;
        $this->nombreUsuario = '';
        $this->emailUsuario = '';
        $this->sucursalActual = '';
        $this->rolUsuario = '';
        $this->sucursalActualId = null;
        $this->nuevaSucursalId = null;
    }

    public function confirmarCambio()
    {
        // Validar campos obligatorios
        if (!$this->validarCamposObligatorios()) {
            return;
        }

        if ($this->nuevaSucursalId == $this->sucursalActualId) {
            $this->camposConError[] = 'nuevaSucursalId';
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'La nueva sucursal debe ser diferente a la actual';
            return;
        }

        $this->mostrarModalConfirmacion = true;
    }

    public function ejecutarCambio()
    {
        try {
            DB::beginTransaction();

            $nuevaSucursal = Tiendas::findOrFail($this->nuevaSucursalId);

            // Actualizar la sucursal del usuario
            User::where('id', $this->usuarioSeleccionado->id)
                ->update([
                    'tienda_id' => $this->nuevaSucursalId,
                    'update_user' => Auth::id(),
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('Cambio de sucursal exitoso', [
                'usuario_id' => $this->usuarioSeleccionado->id,
                'usuario_nombre' => $this->usuarioSeleccionado->name,
                'sucursal_anterior' => $this->sucursalActual,
                'sucursal_nueva' => $nuevaSucursal->denominacion_social,
                'realizado_por' => Auth::id()
            ]);

            $this->mostrarModalConfirmacion = false;
            $this->mostrarExito("Cambio de sucursal realizado exitosamente. {$this->nombreUsuario} ha sido transferido a {$nuevaSucursal->denominacion_social}");

            // Actualizar la información mostrada
            $this->sucursalActual = $nuevaSucursal->denominacion_social;
            $this->sucursalActualId = $this->nuevaSucursalId;

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Error al cambiar sucursal', [
                'usuario_id' => $this->usuarioSeleccionado->id ?? 'N/A',
                'nueva_sucursal_id' => $this->nuevaSucursalId,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]);

            $this->mostrarModalConfirmacion = false;
            $this->mostrarError('Error al realizar el cambio de sucursal: ' . $e->getMessage());
        }
    }

    public function cancelarCambio()
    {
        $this->mostrarModalConfirmacion = false;
        $this->nuevaSucursalId = null; // Limpiar selección para forzar nueva elección
    }

    public function limpiarFormulario()
    {
        $this->buscarUsuario = '';
        $this->limpiarSeleccion();
        $this->usuariosSugeridos = [];
        $this->mostrarSugerencias = false;
    }

    private function cargarTiendas()
    {
        try {
            $this->tiendas = Tiendas::where('estado_id', 1)
                ->orderBy('denominacion_social')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error al cargar tiendas', [
                'mensaje' => $e->getMessage()
            ]);
            $this->tiendas = [];
        }
    }

    private function mostrarExito($mensaje)
    {
        $this->mensajeModalExito = $mensaje;
        $this->mostrarModalExito = true;
    }

    private function mostrarError($mensaje)
    {
        $this->mensajeModalError = $mensaje;
        $this->mostrarModalError = true;
    }

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
        $this->mensajeModalExito = '';
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->camposConError = [];
    }

    // Método para obtener clases CSS dinámicas
    public function getClaseCampo($campo)
    {
        if (in_array($campo, $this->camposConError)) {
            return 'is-invalid campo-obligatorio-vacio';
        }

        return '';
    }

    private function validarCamposObligatorios()
    {
        $this->camposConError = [];

        if (empty($this->buscarUsuario) || !$this->usuarioSeleccionado) {
            $this->camposConError[] = 'buscarUsuario';
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Debe seleccionar un usuario válido';
            return false;
        }

        if (empty($this->nuevaSucursalId)) {
            $this->camposConError[] = 'nuevaSucursalId';
            $this->mostrarAlerta = true;
            $this->mensajeAlerta = 'Debe seleccionar una nueva sucursal';
            return false;
        }

        return true;
    }

    public function render()
    {
        return view('livewire.gestion-de-sucursales.cambio-de-sucursal');
    }
}
