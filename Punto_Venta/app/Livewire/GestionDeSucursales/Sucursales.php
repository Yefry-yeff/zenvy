<?php

namespace App\Livewire\GestionDeSucursales;

use Livewire\Component;
use App\Models\Tienda;
class Sucursales extends Component
{
    // Propiedades de control
    public $modalEliminarAbierto = false;
    public $sucursalAEliminar = null;
    
    // Propiedades para alertas
    public $alertMessage = '';
    public $alertType = '';

    public function mount()
    {
        // Inicialización del componente
    }

    public function render()
    {
        $sucursales = Tienda::with(['userCreador', 'tipoTienda', 'estado', 'direccion.municipio.departamento'])
                           ->orderBy('id', 'desc')
                           ->get();
        
        return view('livewire.gestion-de-sucursales.sucursales', compact('sucursales'));
    }

    // Función para crear nueva sucursal
    public function abrirModalCrear()
    {
        $this->dispatch('cambiarVista', ruta: 'GestionDeSucursales.sucursalform');
    }

    // Función para editar sucursal
    public function editar($id)
    {
        $this->dispatch('cambiarVista', ruta: 'GestionDeSucursales.sucursalform', parametros: ['id' => $id]);
    }

    // Función para confirmar eliminación
    public function confirmarEliminar($id)
    {
        $this->sucursalAEliminar = $id;
        $this->modalEliminarAbierto = true;
    }

    // Función para cerrar modal de eliminación
    public function cerrarModalEliminar()
    {
        $this->modalEliminarAbierto = false;
        $this->sucursalAEliminar = null;
    }

    // Función para eliminar sucursal
    public function eliminarSucursal()
    {
        try {
            if ($this->sucursalAEliminar) {
                $sucursal = Tienda::find($this->sucursalAEliminar);
                if ($sucursal) {
                    $sucursal->delete();
                    session()->flash('mensaje', 'Sucursal eliminada correctamente.');
                }
            }
            $this->cerrarModalEliminar();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar la sucursal: ' . $e->getMessage());
            $this->cerrarModalEliminar();
        }
    }

    // Función para cerrar alerta
    public function cerrarAlerta()
    {
        $this->alertMessage = '';
        $this->alertType = '';
    }
}
