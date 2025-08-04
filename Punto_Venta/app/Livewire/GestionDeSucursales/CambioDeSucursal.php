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

    public function mount()
    {
        $this->cargarTiendas();
    }

    public function updatedBuscarUsuario()
    {
        if (strlen($this->buscarUsuario) >= 2) {
            $this->buscarUsuarios();
            $this->mostrarSugerencias = true;
        } else {
            $this->usuariosSugeridos = [];
            $this->mostrarSugerencias = false;
            $this->limpiarSeleccion();
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
                ->limit(5)
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
            $usuario = User::with(['tienda', 'rol'])->findOrFail($usuarioId);
            
            $this->usuarioSeleccionado = $usuario;
            $this->buscarUsuario = $usuario->name;
            $this->nombreUsuario = $usuario->name;
            $this->emailUsuario = $usuario->email;
            $this->sucursalActual = $usuario->tienda ? $usuario->tienda->denominacion_social : 'Sin asignar';
            $this->sucursalActualId = $usuario->tienda_id;
            $this->rolUsuario = $usuario->rol ? $usuario->rol->txt_nombre : 'Sin rol';
            $this->nuevaSucursalId = $usuario->tienda_id; // Inicializar con la sucursal actual
            
            $this->mostrarSugerencias = false;
            $this->usuariosSugeridos = [];
            
        } catch (\Exception $e) {
            Log::error('Error al seleccionar usuario', [
                'usuario_id' => $usuarioId,
                'mensaje' => $e->getMessage()
            ]);
            $this->mostrarError('Error al cargar los datos del usuario');
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
        if (!$this->usuarioSeleccionado) {
            $this->mostrarError('Debe seleccionar un usuario');
            return;
        }

        if (!$this->nuevaSucursalId) {
            $this->mostrarError('Debe seleccionar una nueva sucursal');
            return;
        }

        if ($this->nuevaSucursalId == $this->sucursalActualId) {
            $this->mostrarError('La nueva sucursal debe ser diferente a la actual');
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
        $this->nuevaSucursalId = $this->sucursalActualId; // Restaurar selección original
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

    public function render()
    {
        return view('livewire.gestion-de-sucursales.cambio-de-sucursal');
    }
}
