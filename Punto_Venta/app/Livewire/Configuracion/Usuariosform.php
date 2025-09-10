<?php
namespace App\Livewire\Configuracion;

use Livewire\Component;
use App\Models\User;
use App\Models\UserDetalle;
use App\Models\Rol;
use App\Models\Tienda;
use App\Models\Menu;
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
        'form.txt_identificacion' => 'required|unique:user_detalle,identidad',
        'form.primer_nombre' => 'required|string|max:50',
        'form.segundo_nombre' => 'nullable|string|max:50',
        'form.primer_apellido' => 'required|string|max:50',
        'form.segundo_apellido' => 'nullable|string|max:50',
        'form.telefono' => 'nullable|string|max:20',
        'form.fecha_nacimiento' => 'nullable|date',
        'form.genero' => 'nullable|in:M,F',
        'form.direccion' => 'nullable|string|max:255',
    ];

    public function mount($usuarioId = null)
    {
        // Verificar si hay un usuario a editar desde sesión
        $usuarioEnSesion = session('usuario_editar_id');
        
        if ($usuarioId) {
            $this->modo = 'editar';
            $this->usuarioId = $usuarioId;
            $this->cargarUsuario();
        } elseif ($usuarioEnSesion) {
            $this->modo = 'editar';
            $this->usuarioId = $usuarioEnSesion;
            $this->cargarUsuario();
            // Limpiar la sesión después de usar el valor
            session()->forget('usuario_editar_id');
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
        $usuario = User::with('detalle')->find($this->usuarioId);

        if ($usuario) {
            $this->form['email'] = $usuario->email;
            $this->form['rol_id'] = $usuario->roles_id;
            $this->form['tienda_id'] = $usuario->tienda_id;
            $this->form['estado_id'] = $usuario->estado_id ?? 1;

            if ($usuario->detalle) {
                $this->form['txt_identificacion'] = $usuario->detalle->identidad;
                $this->form['primer_nombre'] = $usuario->detalle->primer_nombre;
                $this->form['segundo_nombre'] = $usuario->detalle->segundo_nombre;
                $this->form['primer_apellido'] = $usuario->detalle->primer_apellido;
                $this->form['segundo_apellido'] = $usuario->detalle->segundo_apellido;
                $this->form['telefono'] = $usuario->detalle->telefono;
                $this->form['fecha_nacimiento'] = $usuario->detalle->fecha_nacimiento;
                $this->form['genero'] = $usuario->detalle->genero;
                $this->form['direccion'] = $usuario->detalle->direccion;
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
            // Obtener los menús asignados al rol desde la tabla rol_permiso
            $this->permisos = DB::table('menu')
                ->join('rol_permiso', 'menu.id', '=', 'rol_permiso.menu_id')
                ->where('rol_permiso.rol_id', $this->form['rol_id'])
                ->where('rol_permiso.estado', 1)
                ->select('menu.*')
                ->get();
        } else {
            $this->permisos = collect();
        }
    }

    protected function getRulesForValidation()
    {
        $rules = [
            'form.email' => 'required|email|unique:users,email' . ($this->usuarioId ? ',' . $this->usuarioId : ''),
            'form.rol_id' => 'required|exists:roles,id',
            'form.tienda_id' => 'required|exists:tienda,id',
            'form.estado_id' => 'required|in:1,2',
            'form.txt_identificacion' => 'required|unique:user_detalle,identidad' . ($this->usuarioId ? ',' . $this->usuarioId . ',users_id' : ''),
            'form.primer_nombre' => 'required|string|max:50',
            'form.segundo_nombre' => 'nullable|string|max:50',
            'form.primer_apellido' => 'required|string|max:50',
            'form.segundo_apellido' => 'nullable|string|max:50',
            'form.telefono' => 'nullable|string|max:20',
            'form.fecha_nacimiento' => 'nullable|date',
            'form.genero' => 'nullable|in:M,F',
            'form.direccion' => 'nullable|string|max:255',
        ];

        // En modo editar, el password es opcional
        if ($this->modo === 'editar') {
            $rules['form.password'] = 'nullable|min:6';
        } else {
            $rules['form.password'] = 'required|min:6';
        }

        return $rules;
    }

    public function guardar()
    {
        $this->validate($this->getRulesForValidation());

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
        // Crear usuario primero
        $usuario = User::create([
            'name' => $this->form['primer_nombre'] . ' ' . $this->form['primer_apellido'],
            'email' => $this->form['email'],
            'password' => Hash::make($this->form['password']),
            'roles_id' => $this->form['rol_id'],
            'tienda_id' => $this->form['tienda_id'],
            'estado_id' => $this->form['estado_id'],
        ]);

        // Crear detalle del usuario
        UserDetalle::create([
            'users_id' => $usuario->id,
            'identidad' => $this->form['txt_identificacion'],
            'primer_nombre' => $this->form['primer_nombre'],
            'segundo_nombre' => !empty($this->form['segundo_nombre']) ? $this->form['segundo_nombre'] : null,
            'primer_apellido' => $this->form['primer_apellido'],
            'segundo_apellido' => !empty($this->form['segundo_apellido']) ? $this->form['segundo_apellido'] : null,
            'telefono' => !empty($this->form['telefono']) ? $this->form['telefono'] : null,
            'fecha_nacimiento' => !empty($this->form['fecha_nacimiento']) ? $this->form['fecha_nacimiento'] : null,
            'genero' => !empty($this->form['genero']) ? $this->form['genero'] : null,
            'direccion' => !empty($this->form['direccion']) ? $this->form['direccion'] : null,
            'estado_id' => 1,
        ]);

        return $usuario;
    }

    private function actualizarUsuario()
    {
        $usuario = User::find($this->usuarioId);

        // Actualizar usuario
        $usuario->update([
            'name' => trim($this->form['primer_nombre'] . ' ' . $this->form['primer_apellido']),
            'roles_id' => $this->form['rol_id'],
            'tienda_id' => $this->form['tienda_id'],
            'estado_id' => $this->form['estado_id'],
        ]);

        // Actualizar contraseña solo si se proporciona
        if (!empty($this->form['password'])) {
            $usuario->update(['password' => Hash::make($this->form['password'])]);
        }

        // Actualizar detalle del usuario
        if ($usuario->detalle) {
            $usuario->detalle->update([
                'identidad' => $this->form['txt_identificacion'],
                'primer_nombre' => $this->form['primer_nombre'],
                'segundo_nombre' => !empty($this->form['segundo_nombre']) ? $this->form['segundo_nombre'] : null,
                'primer_apellido' => $this->form['primer_apellido'],
                'segundo_apellido' => !empty($this->form['segundo_apellido']) ? $this->form['segundo_apellido'] : null,
                'telefono' => !empty($this->form['telefono']) ? $this->form['telefono'] : null,
                'fecha_nacimiento' => !empty($this->form['fecha_nacimiento']) ? $this->form['fecha_nacimiento'] : null,
                'genero' => !empty($this->form['genero']) ? $this->form['genero'] : null,
                'direccion' => !empty($this->form['direccion']) ? $this->form['direccion'] : null,
            ]);
        }
    }

    public function volver()
    {
        $this->dispatch('cambiarVista', ruta: 'Configuracion.Usuarios');
    }

    public function render()
    {
        return view('livewire.configuracion.usuariosform');
    }
}
