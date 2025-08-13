<?php
namespace App\Livewire\Configuracion;

use Livewire\Component;
use App\Models\User;
use App\Models\Actor;
use App\Models\Rol;
use App\Models\Tienda;
use App\Models\Permiso;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsuariosForm extends Component
{
    public $modo = 'crear'; // crear o editar
    public $usuarioId;
    public $mostrarMensaje = false;

    // Propiedades del formulario
    public $form = [
        // Datos de usuario
        'email' => '',
        'password' => '',
        'rol_id' => '',
        'tienda_id' => '',
        'estado_id' => 1,

        // Datos de actor
        'txt_identificacion' => '',
        'primer_nombre' => '',
        'segundo_nombre' => '',
        'primer_apellido' => '',
        'segundo_apellido' => '',
        'telefono' => '',
        'fecha_nacimiento' => '',
        'genero' => '',
        'direccion' => '',
    ];

    // Datos para dropdowns
    public $roles;
    public $tiendas;
    public $permisos;
    public $empresas = [];

    protected $rules = [
        'form.email' => 'required|email|unique:users,email',
        'form.password' => 'required|min:6',
        'form.rol_id' => 'required|exists:roles,id',
        'form.tienda_id' => 'required|exists:tienda,id',
        'form.estado_id' => 'required|in:1,2',
        'form.txt_identificacion' => 'required|unique:actor,txt_identificacion',
        'form.primer_nombre' => 'required|string|max:50',
        'form.primer_apellido' => 'required|string|max:50',
    ];

    public function mount($usuarioId = null)
    {
        if ($usuarioId) {
            $this->modo = 'editar';
            $this->usuarioId = $usuarioId;
            $this->cargarUsuario();
        }

        $this->cargarDatos();
    }

    public function cargarDatos()
    {
        // Cargar roles activos
        $this->roles = Rol::where('estado', 1)->get();

        // Cargar tiendas activas
        $this->tiendas = Tienda::where('estado_id', 1)->get();

        // Cargar permisos del rol seleccionado
        $this->cargarPermisos();

        Log::info('Datos cargados:', [
            'roles_count' => $this->roles->count(),
            'tiendas_count' => $this->tiendas->count(),
            'roles' => $this->roles->toArray()
        ]);
    }

    public function cargarUsuario()
    {
        $usuario = User::with('actor')->find($this->usuarioId);

        if ($usuario) {
            $this->form['email'] = $usuario->email;
            $this->form['rol_id'] = $usuario->rol_id;
            $this->form['tienda_id'] = $usuario->tienda_id;
            $this->form['estado_id'] = $usuario->estado_id ?? 1;

            if ($usuario->actor) {
                $this->form['txt_identificacion'] = $usuario->actor->txt_identificacion;
                $this->form['primer_nombre'] = $usuario->actor->primer_nombre;
                $this->form['segundo_nombre'] = $usuario->actor->segundo_nombre;
                $this->form['primer_apellido'] = $usuario->actor->primer_apellido;
                $this->form['segundo_apellido'] = $usuario->actor->segundo_apellido;
                $this->form['telefono'] = $usuario->actor->telefono;
                $this->form['fecha_nacimiento'] = $usuario->actor->fecha_nacimiento;
                $this->form['genero'] = $usuario->actor->genero;
                $this->form['direccion'] = $usuario->actor->direccion;
            }
        }
    }

    public function updatedFormRolId()
    {
        $this->cargarPermisos();
    }

    public function cargarPermisos()
    {
        if (!empty($this->form['rol_id'])) {
            $this->permisos = Permiso::whereHas('roles', function($query) {
                $query->where('roles.id', $this->form['rol_id']);
            })->get();
        } else {
            $this->permisos = collect();
        }
    }

    public function guardar()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            if ($this->modo === 'crear') {
                $this->crearUsuario();
            } else {
                $this->actualizarUsuario();
            }

            DB::commit();
            $this->mostrarMensaje = true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar usuario: ' . $e->getMessage());
            session()->flash('error', 'Error al guardar el usuario: ' . $e->getMessage());
        }
    }

    private function crearUsuario()
    {
        // Crear actor
        $actor = Actor::create([
            'txt_identificacion' => $this->form['txt_identificacion'],
            'primer_nombre' => $this->form['primer_nombre'],
            'segundo_nombre' => $this->form['segundo_nombre'],
            'primer_apellido' => $this->form['primer_apellido'],
            'segundo_apellido' => $this->form['segundo_apellido'],
            'telefono' => $this->form['telefono'],
            'fecha_nacimiento' => $this->form['fecha_nacimiento'],
            'genero' => $this->form['genero'],
            'direccion' => $this->form['direccion'],
            'estado_id' => 1,
        ]);

        // Crear usuario
        $usuario = User::create([
            'name' => trim($this->form['primer_nombre'] . ' ' . $this->form['primer_apellido']),
            'email' => $this->form['email'],
            'password' => Hash::make($this->form['password']),
            'rol_id' => $this->form['rol_id'],
            'tienda_id' => $this->form['tienda_id'],
            'actor_id' => $actor->id,
            'estado_id' => $this->form['estado_id'],
        ]);
    }

    private function actualizarUsuario()
    {
        $usuario = User::find($this->usuarioId);

        // Actualizar usuario
        $usuario->update([
            'name' => trim($this->form['primer_nombre'] . ' ' . $this->form['primer_apellido']),
            'rol_id' => $this->form['rol_id'],
            'tienda_id' => $this->form['tienda_id'],
            'estado_id' => $this->form['estado_id'],
        ]);

        // Actualizar contraseña solo si se proporciona
        if (!empty($this->form['password'])) {
            $usuario->update(['password' => Hash::make($this->form['password'])]);
        }

        // Actualizar actor
        if ($usuario->actor) {
            $usuario->actor->update([
                'primer_nombre' => $this->form['primer_nombre'],
                'segundo_nombre' => $this->form['segundo_nombre'],
                'primer_apellido' => $this->form['primer_apellido'],
                'segundo_apellido' => $this->form['segundo_apellido'],
                'telefono' => $this->form['telefono'],
                'fecha_nacimiento' => $this->form['fecha_nacimiento'],
                'genero' => $this->form['genero'],
                'direccion' => $this->form['direccion'],
            ]);
        }
    }

    public function volver()
    {
        $this->dispatch('cambiarVista', ruta: 'configuracion.usuarios');
    }

    public function render()
    {
        return view('livewire.configuracion.usuariosform');
    }
}
