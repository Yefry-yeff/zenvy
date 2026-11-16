<?php

namespace App\Livewire\Configuracion;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Rol;

class Roles extends Component
{
    public $roles = [], $modalAbierto = false, $modoEdicion = false, $submitted = false;
    public $menusDisponibles = [], $permisos = [];

    public $form = [
        'id' => null,
        'txt_nombre' => '',
        'estado' => 1,
    ];

    protected $rules = [
        'form.txt_nombre' => 'required|string',
        'form.estado' => 'required|integer|in:0,1',
    ];

    public function messages()
    {
        return [
            'form.txt_nombre.required' => 'Campo obligatorio',
            'form.estado.required' => 'Campo obligatorio',
            'form.estado.integer' => 'Valor inválido',
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
            'menusDisponibles' => $this->menusDisponibles,
        ]);
    }

    public function cargarDatos()
    {
        $this->roles = Rol::orderBy('id')->get();

        // Agrupar menús por grupo
        $menuRaw = DB::table('menu as m')
            ->join('menu_grupo as g', 'g.id', '=', 'm.parent_id')
            ->where('m.estado_id', 1)
            ->select('m.id', 'm.txt_comentario', 'g.nombre as grupo')
            ->orderBy('g.nombre')
            ->orderBy('m.orden')
            ->get();

        // Estructura: [grupo => [menus]]
        $this->menusDisponibles = $menuRaw->groupBy('grupo')->map(function ($items) {
            return $items->map(fn($i) => ['id' => $i->id, 'nombre' => $i->txt_comentario]);
        })->toArray();
    }

    public function abrirModalCrear()
    {
        $this->resetFormulario();
        $this->modalAbierto = true;
        $this->modoEdicion = false;
    }

    public function editar($id)
    {
        $rol = Rol::findOrFail($id);

        $this->form['id'] = $rol->id;
        $this->form['txt_nombre'] = $rol->txt_nombre;
        $this->form['estado'] = $rol->estado;

        $this->permisos = DB::table('rol_permiso')
            ->where('rol_id', $id)
            ->pluck('menu_id')
            ->toArray();

        $this->modalAbierto = true;
        $this->modoEdicion = true;
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
            $rol->txt_nombre = $this->form['txt_nombre'];
            $rol->estado = $this->form['estado'];
            $rol->updated_at = now();
            $rol->save();
        } else {
            $rol = Rol::create([
                'txt_nombre' => $this->form['txt_nombre'],
                'estado' => $this->form['estado'],
                'created_at' => now(),
                'created_user' => auth()->id(),
            ]);
        }

        // Sincronizar accesos en rol_permiso
        DB::table('rol_permiso')->where('rol_id', $rol->id)->delete();

        $datos = collect($this->permisos)->map(function ($menuId) use ($rol) {
            return [
                'rol_id' => $rol->id,
                'menu_id' => $menuId,
                'estado' => 1,
                'created_at' => now(),
                'created_user' => auth()->id()
            ];
        })->toArray();

        if (!empty($datos)) {
            DB::table('rol_permiso')->insert($datos);
        }

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
