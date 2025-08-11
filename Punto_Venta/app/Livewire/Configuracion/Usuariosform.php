<?php
namespace App\Livewire\Configuracion;

use Livewire\Component;
use App\Models\User;
use App\Models\UserDetalle;
use App\Models\Caja;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Usuariosform extends Component
{
    public $modo = 'crear';
    public $formOriginal = [];
    public $mostrarSinCambios = false;
    public $roles = [];
    public $tiendas = [];
    public $usuarioId;
    public $mostrarMensaje = false;
    public $empresas = [];
    public $permisos = [];
    public $permisosEliminados = [];

    public $form = [
        'primer_nombre' => '',
        'segundo_nombre' => '',
        'primer_apellido' => '',
        'segundo_apellido' => '',
        'direccion' => '',
        'telefono' => '',
        'genero' => '',
        'fecha_nacimiento' => '',
        'estado_id' => 1,
        'email' => '',
        'rol_id' => '',
        'tienda_id' => '',
        'password' => '',
        'txt_identificacion' => '',
    ];

    public function updated($property)
    {
        if ($property === 'form.rol_id') {
            $this->updatedFormRolId($this->form['rol_id']);
        }
    }

    public function updatedFormRolId($value)
    {
        if ($value) {
            $this->permisos = DB::table('rol_permiso as rp')
                ->join('menu as m', 'm.id', '=', 'rp.menu_id')
                ->where('rp.rol_id', $value)
                ->select('m.id', 'm.txt_comentario as nombre')
                ->get();
        } else {
            $this->permisos = collect();
        }
    }

    public function rules()
    {
        $rules = [
            'form.rol_id' => 'required',
            'form.tienda_id' => 'required|exists:tienda,id',
            'form.estado_id' => 'required',
            'form.primer_nombre' => 'required|string|max:100',
            'form.primer_apellido' => 'required|string|max:100',
            'form.txt_identificacion' => 'required|string|max:50',
        ];

        if ($this->modo === 'crear') {
            $rules['form.email'] = 'required|email|unique:users,email';
            $rules['form.password'] = 'required|min:6';
        } else {
            $rules['form.email'] = [
                'required', 'email',
                Rule::unique('users', 'email')->ignore($this->usuarioId),
            ];
            $rules['form.password'] = 'nullable|min:6';
        }

        return $rules;
    }

    public function mount($id = null)
    {
        $this->roles = DB::table('roles')->select('id', 'txt_nombre')->get();
        $this->tiendas = DB::table('tienda')
            ->where('estado_id', 1) // Solo tiendas activas
            ->select('id', 'denominacion_social')
            ->orderBy('denominacion_social')
            ->get();

        if (session()->has('usuario_editar_id')) {
            $this->usuarioId = session('usuario_editar_id');
            $this->modo = 'editar';
            $this->cargarDatos();
            session()->forget('usuario_editar_id');
        }

        $this->updatedFormRolId($this->form['rol_id']);
    }

    public function cargarDatos()
    {
        $usuario = User::with('detalle')->findOrFail($this->usuarioId);

        $this->form = [
            'primer_nombre' => $usuario->detalle->primer_nombre,
            'segundo_nombre' => $usuario->detalle->segundo_nombre,
            'primer_apellido' => $usuario->detalle->primer_apellido,
            'segundo_apellido' => $usuario->detalle->segundo_apellido,
            'direccion' => $usuario->detalle->direccion,
            'telefono' => $usuario->detalle->telefono,
            'genero' => $usuario->detalle->genero,
            'fecha_nacimiento' => $usuario->detalle->fecha_nacimiento,
            'estado_id' => $usuario->estado_id,
            'email' => $usuario->email,
            'rol_id' => $usuario->roles_id,
            'tienda_id' => $usuario->tienda_id,
            'password' => '',
            'txt_identificacion' => $usuario->detalle->identidad,
        ];

        $this->formOriginal = $this->form;
        $this->updatedFormRolId($this->form['rol_id']);
    }

    public function guardar()
    {
        $this->validate();

        if ($this->modo === 'editar' && $this->form === $this->formOriginal) {
            $this->mostrarSinCambios = true;
            return;
        }

        DB::beginTransaction();

        try {
            if ($this->modo === 'crear') {
                $usuario = User::create([
                    'name' => $this->form['primer_nombre'] . ' ' . $this->form['primer_apellido'],
                    'email' => $this->form['email'],
                    'password' => Hash::make($this->form['password']),
                    'estado_id' => $this->form['estado_id'],
                    'roles_id' => $this->form['rol_id'],
                    'tienda_id' => $this->form['tienda_id'],
                ]);

                UserDetalle::create([
                    'users_id' => $usuario->id,
                    'primer_nombre' => $this->form['primer_nombre'],
                    'segundo_nombre' => $this->form['segundo_nombre'],
                    'primer_apellido' => $this->form['primer_apellido'],
                    'segundo_apellido' => $this->form['segundo_apellido'],
                    'direccion' => $this->form['direccion']?: null,
                    'telefono' => $this->form['telefono']?: null,
                    'genero' => $this->form['genero']?: null,
                    'fecha_nacimiento' => $this->form['fecha_nacimiento']?: null,
                    'identidad' => $this->form['txt_identificacion'],
                    'estado_id' => $this->form['estado_id'],
                ]);

                // Si el rol es cajero (ID 8), crear registro de caja
                if ($this->form['rol_id'] == 8) {
                    Caja::create([
                        'tienda_id' => $this->form['tienda_id'],
                        'users_id' => $usuario->id,
                        'balance' => 0.00,
                        'fecha_apertura' => null,
                        'fecha_cierre' => null,
                        'estado_caja' => 2, // Cerrada por defecto
                    ]);
                }

            } else {
                $usuario = User::findOrFail($this->usuarioId);
                $rolAnterior = $usuario->roles_id;

                $usuario->estado_id = $this->form['estado_id'];
                $usuario->email = $this->form['email'];
                $usuario->roles_id = $this->form['rol_id'];
                $usuario->tienda_id = $this->form['tienda_id'];

                if ($this->form['password']) {
                    $usuario->password = Hash::make($this->form['password']);
                }

                $usuario->save();

                $usuario->detalle->update([
                    'primer_nombre' => $this->form['primer_nombre'],
                    'segundo_nombre' => $this->form['segundo_nombre'],
                    'primer_apellido' => $this->form['primer_apellido'],
                    'segundo_apellido' => $this->form['segundo_apellido'],
                    'direccion' => $this->form['direccion']?: null,
                    'telefono' => $this->form['telefono']?: null,
                    'genero' => $this->form['genero']?: null,
                    'fecha_nacimiento' => $this->form['fecha_nacimiento']?: null,
                    'identidad' => $this->form['txt_identificacion'],
                    'estado_id' => $this->form['estado_id'],
                ]);

                // Si cambió a rol cajero (ID 8) y no tenía registro de caja, crearlo
                if ($this->form['rol_id'] == 8 && $rolAnterior != 8) {
                    $cajaExistente = Caja::where('users_id', $usuario->id)->first();
                    if (!$cajaExistente) {
                        Caja::create([
                            'tienda_id' => $this->form['tienda_id'],
                            'users_id' => $usuario->id,
                            'balance' => 0.00,
                            'fecha_apertura' => null,
                            'fecha_cierre' => null,
                            'estado_caja' => 2, // Cerrada por defecto
                        ]);
                    }
                }

                if (!empty($this->permisosEliminados)) {
                    foreach ($this->permisosEliminados as $permisoId) {
                        DB::table('user_permiso')
                            ->where('user_id', $usuario->id)
                            ->where('permiso_id', $permisoId)
                            ->delete();
                    }
                }
            }

            DB::commit();
            session()->flash('mensaje', '✅ Usuario guardado exitosamente.');
            $this->mostrarMensaje = true;
            $this->dispatch('usuario-guardado');

        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('[Usuariosform] Error al guardar: ' . $e->getMessage());
            session()->flash('mensaje', '❌ Error al guardar: ' . $e->getMessage());
        }
    }

    public function volver()
    {
        $this->dispatch('cambiarVista', 'Configuracion.usuarios');
    }

    public function render()
    {
        return view('livewire.Configuracion.usuariosform');
    }
}
