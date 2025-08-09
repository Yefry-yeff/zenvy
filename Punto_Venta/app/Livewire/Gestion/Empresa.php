<?php

namespace App\Livewire\Gestion;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Empresa as EmpresaModel;
use Illuminate\Support\Facades\Log;

class Empresa extends Component
{
    use WithFileUploads;
    
    // Propiedades del formulario
    public $nombre;
    public $rtn;
    public $correo;
    public $telefono;
    public $logo; // Para el archivo subido
    
    // Propiedades de control
    public $empresa;
    public $editando = false;
    public $logoPreview = null;
    
    protected $rules = [
        'nombre' => 'required|string|max:60',
        'rtn' => 'nullable|string|max:45',
        'correo' => 'nullable|email|max:45',
        'telefono' => 'nullable|integer',
        'logo' => 'nullable|image|max:2048' // máximo 2MB
    ];
    
    protected $messages = [
        'nombre.required' => 'El nombre de la empresa es obligatorio.',
        'nombre.max' => 'El nombre no puede exceder 60 caracteres.',
        'correo.email' => 'El correo debe tener un formato válido.',
        'correo.max' => 'El correo no puede exceder 45 caracteres.',
        'rtn.max' => 'El RTN no puede exceder 45 caracteres.',
        'telefono.integer' => 'El teléfono debe ser un número.',
        'logo.image' => 'El logo debe ser una imagen.',
        'logo.max' => 'El logo no puede exceder 2MB.'
    ];
    
    public function mount()
    {
        $this->cargarEmpresa();
    }
    
    public function cargarEmpresa()
    {
        // Cargar la primera empresa (asumiendo que solo hay una)
        $this->empresa = EmpresaModel::first();
        
        if ($this->empresa) {
            $this->nombre = $this->empresa->nombre;
            $this->rtn = $this->empresa->rtn;
            $this->correo = $this->empresa->correo;
            $this->telefono = $this->empresa->telefono;
            $this->editando = true;
        } else {
            $this->editando = false;
        }
    }
    
    public function updatedLogo()
    {
        $this->validate(['logo' => 'image|max:2048']);
        
        if ($this->logo) {
            $this->logoPreview = $this->logo->temporaryUrl();
        }
    }
    
    public function guardarEmpresa()
    {
        $this->validate();
        
        try {
            $logoBlob = null;
            
            // Procesar logo si se subió uno nuevo
            if ($this->logo) {
                $logoBlob = file_get_contents($this->logo->getRealPath());
                Log::info('Logo procesado', ['tamaño' => strlen($logoBlob)]);
            }
            
            if ($this->editando && $this->empresa) {
                // Actualizar empresa existente
                $this->empresa->update([
                    'nombre' => $this->nombre,
                    'rtn' => $this->rtn,
                    'correo' => $this->correo,
                    'telefono' => $this->telefono,
                ]);
                
                // Actualizar logo solo si se subió uno nuevo
                if ($logoBlob) {
                    $this->empresa->update(['logo' => $logoBlob]);
                }
                
                session()->flash('success', 'Información de la empresa actualizada exitosamente.');
                
            } else {
                // Crear nueva empresa
                $this->empresa = EmpresaModel::create([
                    'nombre' => $this->nombre,
                    'rtn' => $this->rtn,
                    'correo' => $this->correo,
                    'telefono' => $this->telefono,
                    'logo' => $logoBlob,
                ]);
                
                $this->editando = true;
                session()->flash('success', 'Empresa creada exitosamente.');
            }
            
            // Limpiar el archivo temporal
            $this->logo = null;
            $this->logoPreview = null;
            
        } catch (\Exception $e) {
            Log::error('Error al guardar empresa', ['error' => $e->getMessage()]);
            session()->flash('error', 'Error al guardar la empresa: ' . $e->getMessage());
        }
    }
    
    public function limpiarFormulario()
    {
        $this->reset(['nombre', 'rtn', 'correo', 'telefono', 'logo']);
        $this->editando = false;
        $this->logoPreview = null;
    }

    public function eliminarLogo()
    {
        try {
            if ($this->empresa && $this->empresa->logo) {
                $this->empresa->update(['logo' => null]);
                $this->empresa = $this->empresa->fresh();
                session()->flash('success', 'Logo eliminado correctamente.');
            } else {
                session()->flash('error', 'No hay logo para eliminar.');
            }
        } catch (\Exception $e) {
            Log::error('Error al eliminar logo: ' . $e->getMessage());
            session()->flash('error', 'Error al eliminar el logo.');
        }
    }
    
    public function render()
    {
        return view('livewire.gestion.empresa');
    }
}
