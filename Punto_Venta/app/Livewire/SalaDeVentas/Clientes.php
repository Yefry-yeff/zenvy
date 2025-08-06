<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Cliente;
use App\Models\TipoPersona;
use App\Models\TipoCliente;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Clientes extends Component
{
    use WithPagination;

    public $registrosPorPagina = 10;

    // Propiedades para modales
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    // Propiedades para alertas de validación
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';

    public function render()
    {
        $clientes = Cliente::with(['tipoPersona', 'tipoCliente', 'direccion.municipio.departamento'])
                          ->orderBy('created_at', 'desc')
                          ->paginate($this->registrosPorPagina);
        
        return view('livewire.sala-de-ventas.clientes', [
            'clientes' => $clientes
        ]);
    }

    // ===== MÉTODOS DE NAVEGACIÓN =====

    public function crearNuevoCliente()
    {
        $this->dispatch('cambiarVista', ruta: 'SalaDeVentas.ClienteForm');
    }

    public function editarCliente($clienteId)
    {
        $this->dispatch('cambiarVista', ruta: 'SalaDeVentas.ClienteForm', parametros: [
            'clienteId' => $clienteId
        ]);
    }

    // ===== MÉTODOS DE GESTIÓN DE MODALES =====

    public function mostrarExito($mensaje)
    {
        $this->mensajeModalExito = $mensaje;
        $this->mostrarModalExito = true;
    }

    public function mostrarError($mensaje)
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

    // ===== MÉTODOS DE GESTIÓN DE ALERTAS =====

    public function mostrarAlerta($mensaje, $campo = '')
    {
        $this->mensajeAlerta = $mensaje;
        $this->campoConError = $campo;
        $this->mostrarAlerta = true;
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->campoConError = '';
    }
}
