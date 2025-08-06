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

    // Filtros y búsqueda
    public $buscar = '';
    public $filtroTipoPersona = '';
    public $filtroTipoCliente = '';
    public $filtroEstado = '';
    public $registrosPorPagina = 10;

    // Propiedades para modales
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    protected $queryString = [
        'buscar' => ['except' => ''],
        'filtroTipoPersona' => ['except' => ''],
        'filtroTipoCliente' => ['except' => ''],
        'filtroEstado' => ['except' => ''],
        'page' => ['except' => 1]
    ];

    public function render()
    {
        $clientes = $this->obtenerClientes();
        $tiposPersona = TipoPersona::activos()->orderBy('nombre')->get();
        $tiposCliente = TipoCliente::activos()->orderBy('nombre')->get();
        
        return view('livewire.sala-de-ventas.clientes', [
            'clientes' => $clientes,
            'tiposPersona' => $tiposPersona,
            'tiposCliente' => $tiposCliente
        ]);
    }

    private function obtenerClientes()
    {
        try {
            $query = Cliente::with([
                'direccion.municipio.departamento',
                'tipoPersona',
                'tipoCliente',
                'estado',
                'user'
            ]);

            // Aplicar filtro de búsqueda
            if (!empty($this->buscar)) {
                $query->where(function($q) {
                    $q->where('nombre', 'like', '%' . $this->buscar . '%')
                      ->orWhere('correo', 'like', '%' . $this->buscar . '%')
                      ->orWhere('identidad', 'like', '%' . $this->buscar . '%')
                      ->orWhere('rtn', 'like', '%' . $this->buscar . '%');
                });
            }

            // Aplicar filtro por tipo de persona
            if ($this->filtroTipoPersona !== '') {
                $query->where('tipo_persona_id', $this->filtroTipoPersona);
            }

            // Aplicar filtro por tipo de cliente
            if ($this->filtroTipoCliente !== '') {
                $query->where('tipo_cliente_id', $this->filtroTipoCliente);
            }

            // Aplicar filtro de estado
            if ($this->filtroEstado !== '') {
                $query->where('estado_id', $this->filtroEstado);
            }

            return $query->orderBy('nombre')
                        ->orderBy('created_at', 'desc')
                        ->paginate($this->registrosPorPagina);

        } catch (\Exception $e) {
            Log::error('Error al obtener clientes', [
                'mensaje' => $e->getMessage(),
                'usuario_id' => Auth::id()
            ]);
            
            return collect()->paginate($this->registrosPorPagina);
        }
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

    // ===== MÉTODOS DE FILTRADO =====

    public function limpiarFiltros()
    {
        $this->buscar = '';
        $this->filtroTipoPersona = '';
        $this->filtroTipoCliente = '';
        $this->filtroEstado = '';
        $this->resetPage();
    }

    public function updatedBuscar()
    {
        $this->resetPage();
    }

    public function updatedFiltroTipoPersona()
    {
        $this->resetPage();
    }

    public function updatedFiltroTipoCliente()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
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
}
