<?php
namespace App\Livewire\Configuracion;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\MiActor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Usuariosform extends Component
{
    public $modo = 'crear';
    public $formOriginal = [];
    public $mostrarSinCambios = false;
    public $roles = [];
    public $usuarioId;
public $mostrarMensaje = false;
    public $empresas = [];
    public $permisos = [];

    public $form = [
        'primer_nombre' => '',
        'segundo_nombre' => '',
        'primer_apellido' => '',
        'segundo_apellido' => '',
        'direccion' => '',
        'telefono' => '',
        'genero' => '',
        'fecha_nacimiento' => '',
        'estado' => 1,
        'email' => '',
        'rol_id' => '',
        'password' => '',
        'txt_identificacion' => '',
    ];

public function updated($property)
{
    logger()->info('[Livewire] Campo actualizado: ' . $property);
    if ($property === 'form.rol_id') {
        $this->updatedFormRolId($this->form['rol_id']);
    }
}

public function updatedFormRolId($value)
{
    logger()->info('[Livewire] Rol cambiado a: ' . $value);

    if ($value) {
        $this->permisos = DB::table('rol_permiso as rp')
            ->join('permisos as p', 'p.id', '=', 'rp.permiso_id')
            ->where('rp.rol_id', $value)
            ->where('rp.estado', 1)
            ->select('p.id', 'p.nombre')
            ->get();
    } else {
        $this->permisos = collect();
    }
}
    public function rules()
{
    $rules = [
        'form.rol_id' => 'required',
        'form.estado' => 'required',
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
        // Siempre cargar los roles
        $this->roles = DB::table('roles')->select('id', 'txt_nombre')->get();

    if (session()->has('usuario_editar_id')) {
            $this->usuarioId = session('usuario_editar_id');
            $this->modo = 'editar';
            $this->cargarDatos();

            // Limpiar para que no quede persistente
            session()->forget('usuario_editar_id');
        } else {
            $this->modo = 'crear';
        }
        $this->updatedFormRolId($this->form['rol_id']);
    }

    public function cargarDatos()
    {
        $usuario = User::with(['actor', 'roles'])->findOrFail(id: $this->usuarioId);

        $this->form = [
            'primer_nombre' => $usuario->actor->TXT_PRIMER_NOMBRE,
            'segundo_nombre' => $usuario->actor->TXT_SEGUNDO_NOMBRE,
            'primer_apellido' => $usuario->actor->TXT_PRIMER_APELLIDO,
            'segundo_apellido' => $usuario->actor->TXT_SEGUNDO_APELLIDO,
            'direccion' => $usuario->actor->TXT_DIRECCION,
            'telefono' => $usuario->actor->telefono,
            'genero' => $usuario->actor->genero,
            'fecha_nacimiento' => $usuario->actor->fecha_nacimiento,
            'estado' => $usuario->estado,
            'email' => $usuario->email,
            'rol_id' => $usuario->roles->first()?->id,
            'password' => '',
            'txt_identificacion' => $usuario->actor->txt_identificacion,
        ];
// Clonar el formulario para comparar después
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
logger()->info('[Usuariosform] validación pasada exitosamente');

    DB::beginTransaction();

    try {
        if ($this->modo === 'crear') {
            $actor = MiActor::create([
               'txt_identificacion'   => $this->form['txt_identificacion'],
                'TXT_PRIMER_NOMBRE'    => $this->form['primer_nombre'],
                'TXT_SEGUNDO_NOMBRE'   => $this->form['segundo_nombre'] ?: null,
                'TXT_PRIMER_APELLIDO'  => $this->form['primer_apellido'],
                'TXT_SEGUNDO_APELLIDO' => $this->form['segundo_apellido'] ?: null,
                'TXT_DIRECCION'        => $this->form['direccion'] ?: null,
                'telefono'             => $this->form['telefono'] ?: null,
                'genero'               => $this->form['genero'] ?: null,
                'fecha_nacimiento'     => $this->form['fecha_nacimiento'] ?: null,
            ]);

            $nombreCompleto = trim(
                $this->form['primer_nombre'] . ' ' .
                $this->form['segundo_nombre'] . ' ' .
                $this->form['primer_apellido'] . ' ' .
                $this->form['segundo_apellido']
            );

            $usuario = User::create([
                    'name' => $nombreCompleto,
                    'email' => $this->form['email'],
                    'password' => Hash::make($this->form['password']),
                    'estado' => $this->form['estado'],
                    'id_actor' => $actor->id,
            ]);

            DB::table('user_rol')->insert([
                'user_id' => $usuario->id,
                'rol_id' => $this->form['rol_id'],
            ]);
        } else {
            $usuario = User::findOrFail($this->usuarioId);
            $usuario->estado = $this->form['estado'];

            if ($this->form['password']) {
                $usuario->password = Hash::make($this->form['password']);
            }

            $usuario->save();

            $usuario->actor->update([
                'TXT_PRIMER_NOMBRE' => $this->form['primer_nombre'],
                'TXT_SEGUNDO_NOMBRE' => $this->form['segundo_nombre'],
                'TXT_PRIMER_APELLIDO' => $this->form['primer_apellido'],
                'TXT_SEGUNDO_APELLIDO' => $this->form['segundo_apellido'],
                'TXT_DIRECCION' => $this->form['direccion'],
                'telefono' => $this->form['telefono'],
                'genero' => $this->form['genero'],
                'fecha_nacimiento' => $this->form['fecha_nacimiento'],
            ]);

            DB::table('user_rol')->updateOrInsert(
                ['user_id' => $usuario->id],
                ['rol_id' => $this->form['rol_id']]
            );
                        // 🔁 Eliminar permisos que fueron eliminados en la UI
            if (!empty($this->permisosEliminados)) {
                foreach ($this->permisosEliminados as $permisoId) {
                    DB::table('user_permiso')
                        ->where('user_id', $usuario->id)
                        ->where('permiso_id', $permisoId)
                        ->delete();
                }
}
        }
logger()->info('[Usuariosform] commit ejecutado');
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
