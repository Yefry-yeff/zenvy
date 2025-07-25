<?php

namespace App\Livewire\Configuracion;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Rol;
use App\Models\Permiso;

class Roles extends Component
{
    public $roles = [], $modalAbierto = false, $modoEdicion = false, $submitted = false;
    public $permisosDisponibles = [], $permisos = [];

    public $form = [
        'id' => null,
        'txt_nombre' => '',
        'estado' => 1,
    ];

    protected $rules = [
        'form.txt_nombre' => 'required|string',
        'form.estado' => 'required|boolean',
    ];

    public function messages()
    {
        return [
            'form.txt_nombre.required' => 'Campo obligatorio',
            'form.estado.required' => 'Campo obligatorio',
            'form.estado.boolean' => 'Valor inválido',
        ];
    }

    public function mount()
    {
        $this->cargarDatos();
    }

    public function render()
    {
        return view('livewire.configuracion.roles', [
            'roles' => $this->roles,
            'permisosDisponibles' => $this->permisosDisponibles,
        ]);
    }

    public function cargarDatos()
    {
        $this->roles = Rol::orderBy('id')->get();
        $this->permisosDisponibles = Permiso::orderBy('id')->get();
    }

    public function abrirModalCrear()
    {
        $this->resetFormulario();
        $this->modalAbierto = true;
        $this->modoEdicion = false;
        $this->permisosDisponibles = Permiso::where('estado', 1)->orderBy('id')->get();
    }

    public function editar($id)
    {
        $rol = Rol::findOrFail($id);
        $this->form['id'] = $rol->id;
        $this->form['txt_nombre'] = $rol->txt_nombre;
        $this->form['estado'] = $rol->estado;
        $this->permisos = $rol->permisos()->pluck('permisos.id')->toArray();
        $this->modalAbierto = true;
        $this->modoEdicion = true;
          // Solo permisos activos
        $this->permisosDisponibles = Permiso::where('estado', 1)->orderBy('id')->get();
        $this->permisos = $rol->permisos()->pluck('permisos.id')->toArray();
    }

    public function cerrarModal()
    {
        $this->submitted = false;
        $this->modalAbierto = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->resetFormulario();
    }

    public function guardar()
    {
        $this->submitted = true;
        $this->validate();

        if ($this->modoEdicion && $this->form['id']) {
            $rol = Rol::find($this->form['id']);
            $rol->estado = $this->form['estado'];
            $rol->updated_at = Carbon::now();
            $rol->save();
        } else {
            $rol = Rol::create([
                'txt_nombre' => $this->form['txt_nombre'],
                'estado' => $this->form['estado'],
                'created_at' => Carbon::now(),
                'created_user' => auth()->id(),
            ]);
        }

        $rol->permisos()->sync($this->permisos);

        $this->modalAbierto = false;
        $this->cargarDatos();
        session()->flash('mensaje', 'Rol guardado correctamente.');
    }

    private function resetFormulario()
    {
        $this->form = [
            'id' => null,
            'txt_nombre' => '',
            'estado' => 1,
        ];
        $this->permisos = [];
    }
}
