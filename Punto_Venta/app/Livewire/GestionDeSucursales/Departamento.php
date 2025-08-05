<?php

namespace App\Livewire\GestionDeSucursales;

use Livewire\Component;
use App\Models\Departamento as ModelDepartamento;
use App\Models\Municipio;
use Illuminate\Support\Facades\Auth;

class Departamento extends Component
{
    // Propiedades para el departamento
    public $nombre_departamento = '';
    public $departamento_id = null;
    
    // Propiedades para los municipios
    public $nombre_municipio = '';
    public $municipio_id = null;
    public $municipios = [];
    
    // Propiedades de control
    public $isEdit = false;
    public $showMunicipioModal = false;
    public $editingMunicipio = false;
    public $modalEliminarAbierto = false;
    public $modalDepartamentoAbierto = false;
    public $departamentoAEliminar = null;
    
    // Propiedades para alertas
    public $alertMessage = '';
    public $alertType = '';
    
    protected $rules = [
        'nombre_departamento' => 'required|min:2|max:45',
        'nombre_municipio' => 'required|min:2|max:45',
    ];

    protected $messages = [
        'nombre_departamento.required' => 'El nombre del departamento es obligatorio.',
        'nombre_departamento.min' => 'El nombre del departamento debe tener al menos 2 caracteres.',
        'nombre_departamento.max' => 'El nombre del departamento no puede exceder 45 caracteres.',
        'nombre_municipio.required' => 'El nombre del municipio es obligatorio.',
        'nombre_municipio.min' => 'El nombre del municipio debe tener al menos 2 caracteres.',
        'nombre_municipio.max' => 'El nombre del municipio no puede exceder 45 caracteres.',
    ];

    public function render()
    {
        $departamentos = ModelDepartamento::with(['municipios', 'userRegistro'])->orderBy('id', 'desc')->get();
        return view('livewire.gestion-de-sucursales.departamento', compact('departamentos'));
    }

    // Función para obtener la clase CSS de los campos con errores
    public function getClaseCampo($campo)
    {
        return $this->getErrorBag()->has($campo) ? 'is-invalid' : '';
    }

    // Función para guardar departamento
    public function guardarDepartamento()
    {
        $this->validate([
            'nombre_departamento' => 'required|min:2|max:45'
        ]);

        try {
            if ($this->isEdit) {
                $departamento = ModelDepartamento::find($this->departamento_id);
                $departamento->update([
                    'nombre' => $this->nombre_departamento,
                ]);
                session()->flash('mensaje', 'Departamento actualizado correctamente.');
            } else {
                ModelDepartamento::create([
                    'nombre' => $this->nombre_departamento,
                    'user_registro_id' => Auth::id(),
                ]);
                session()->flash('mensaje', 'Departamento creado correctamente.');
            }
            
            $this->resetDepartamento();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar el departamento: ' . $e->getMessage());
        }
    }

    // Función para abrir modal de crear departamento
    public function abrirModalCrear()
    {
        $this->resetDepartamento();
        $this->modalDepartamentoAbierto = true;
    }

    // Función para editar departamento
    public function editarDepartamento($id)
    {
        $departamento = ModelDepartamento::with('municipios')->find($id);
        if ($departamento) {
            $this->departamento_id = $departamento->id;
            $this->nombre_departamento = $departamento->nombre;
            $this->municipios = $departamento->municipios->toArray();
            $this->isEdit = true;
            $this->modalDepartamentoAbierto = true;
        }
    }

    // Función para confirmar eliminación
    public function confirmarEliminar($id)
    {
        $this->departamentoAEliminar = $id;
        $this->modalEliminarAbierto = true;
    }

    // Función para cerrar modal de eliminación
    public function cerrarModalEliminar()
    {
        $this->modalEliminarAbierto = false;
        $this->departamentoAEliminar = null;
    }

    // Función para eliminar departamento
    public function eliminarDepartamento()
    {
        try {
            if ($this->departamentoAEliminar) {
                $departamento = ModelDepartamento::find($this->departamentoAEliminar);
                if ($departamento) {
                    // Verificar si tiene municipios asociados
                    if ($departamento->municipios()->count() > 0) {
                        session()->flash('error', 'No se puede eliminar el departamento porque tiene municipios asociados.');
                    } else {
                        $departamento->delete();
                        session()->flash('mensaje', 'Departamento eliminado correctamente.');
                    }
                }
            }
            $this->cerrarModalEliminar();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar el departamento: ' . $e->getMessage());
            $this->cerrarModalEliminar();
        }
    }

    // Función para guardar municipio
    public function guardarMunicipio()
    {
        $this->validate([
            'nombre_municipio' => 'required|min:2|max:45'
        ]);

        if (!$this->departamento_id) {
            $this->alertMessage = 'Debe seleccionar un departamento primero.';
            $this->alertType = 'warning';
            return;
        }

        try {
            if ($this->editingMunicipio) {
                $municipio = Municipio::find($this->municipio_id);
                $municipio->update([
                    'nombre' => $this->nombre_municipio,
                ]);
                $this->alertMessage = 'Municipio actualizado correctamente.';
            } else {
                Municipio::create([
                    'nombre' => $this->nombre_municipio,
                    'departamento_id' => $this->departamento_id,
                    'users_registro_id' => Auth::id(),
                ]);
                $this->alertMessage = 'Municipio creado correctamente.';
            }
            
            $this->alertType = 'success';
            $this->resetMunicipio();
            $this->cargarMunicipios();
            $this->dispatch('cerrarModalMunicipio');
            
        } catch (\Exception $e) {
            $this->alertMessage = 'Error al procesar el municipio: ' . $e->getMessage();
            $this->alertType = 'error';
        }
    }

    // Función para editar municipio
    public function editarMunicipio($id)
    {
        $municipio = Municipio::find($id);
        if ($municipio) {
            $this->municipio_id = $municipio->id;
            $this->nombre_municipio = $municipio->nombre;
            $this->editingMunicipio = true;
            $this->showMunicipioModal = true;
        }
    }

    // Función para eliminar municipio
    public function eliminarMunicipio($id)
    {
        try {
            $municipio = Municipio::find($id);
            if ($municipio) {
                $municipio->delete();
                $this->alertMessage = 'Municipio eliminado correctamente.';
                $this->alertType = 'success';
                $this->cargarMunicipios();
            }
        } catch (\Exception $e) {
            $this->alertMessage = 'Error al eliminar el municipio: ' . $e->getMessage();
            $this->alertType = 'error';
        }
    }

    // Función para cargar municipios del departamento actual
    public function cargarMunicipios()
    {
        if ($this->departamento_id) {
            $this->municipios = Municipio::where('departamento_id', $this->departamento_id)->get()->toArray();
        }
    }

    // Función para mostrar modal de municipio
    public function mostrarModalMunicipio()
    {
        if (!$this->departamento_id) {
            $this->alertMessage = 'Debe seleccionar un departamento primero.';
            $this->alertType = 'warning';
            return;
        }
        $this->resetMunicipio();
        $this->showMunicipioModal = true;
    }

    // Función para resetear datos de departamento
    public function resetDepartamento()
    {
        $this->nombre_departamento = '';
        $this->departamento_id = null;
        $this->isEdit = false;
        $this->municipios = [];
        $this->modalDepartamentoAbierto = false;
        $this->resetErrorBag();
    }

    // Función para resetear datos de municipio
    public function resetMunicipio()
    {
        $this->nombre_municipio = '';
        $this->municipio_id = null;
        $this->editingMunicipio = false;
        $this->showMunicipioModal = false;
        $this->resetErrorBag(['nombre_municipio']);
    }

    // Función para cerrar alerta
    public function cerrarAlerta()
    {
        $this->alertMessage = '';
        $this->alertType = '';
    }
}
