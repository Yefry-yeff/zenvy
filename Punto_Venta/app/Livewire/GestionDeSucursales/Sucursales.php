<?php

namespace App\Livewire\GestionDeSucursales;

use Livewire\Component;
use App\Models\Tienda;
class Sucursales extends Component
{
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

    // Función para cerrar alerta
    public function cerrarAlerta()
    {
        $this->alertMessage = '';
        $this->alertType = '';
    }
}
