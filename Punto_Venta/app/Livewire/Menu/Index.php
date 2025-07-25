<?php
namespace App\Livewire\Menu;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Componente Livewire para la gestión de menús y submenús.
 * Permite crear, editar, listar y generar automáticamente componentes Livewire.
 */
class Index extends Component
{
    public $menus = [];              // Lista de menús renderizados
    public $menuGrupos = [];         // Lista de nombres de grupos de menú
    public $submitted = false;       // Control de envío para validación visual

    public $form = [                 // Formulario principal para crear/editar menús
        'id' => null,
        'menu_grupo' => '',
        'txt_comentario' => '',
        'icon' => '',
        'orden' => 1,
        'estado' => 1,
    ];

    public $modo = 'crear';         // Modo actual: 'crear' o 'editar'
    public $modalOpen = false;      // Estado del modal

    public $filtro = [              // Filtros aplicados a la tabla de menús
        'menu' => '',
        'submenu' => '',
        'icon' => '',
        'orden' => '',
        'estado' => '',
    ];

    /** Validaciones para el formulario */
    protected $rules = [
        'form.menu_grupo'     => 'required|string',
        'form.txt_comentario' => 'required|string',
        'form.icon'           => 'required|string',
        'form.orden'          => 'required|numeric|min:1',
        'form.estado'         => 'required|boolean',
    ];

    /** Mensajes personalizados para validaciones */
    public function messages()
    {
        return [
            'form.menu_grupo.required'     => 'Campo obligatorio',
            'form.txt_comentario.required' => 'Campo obligatorio',
            'form.orden.required'          => 'Campo obligatorio',
            'form.orden.numeric'           => 'Debe ser un número',
            'form.orden.min'               => 'Debe ser al menos 1',
            'form.estado.required'         => 'Campo obligatorio',
            'form.estado.boolean'          => 'Valor inválido',
        ];
    }

    /** Inicializa el componente y carga datos iniciales */
    public function mount()
    {
        logger()->info('[MenuIndex] mount ejecutado');
        $this->cargarDatos();
    }

    /** Renderiza la vista asociada */
    public function render()
    {
        logger()->info('[MenuIndex] render ejecutado');
        return view('livewire.menu.index', [
            'menus' => $this->menus,
            'menuGrupos' => $this->menuGrupos,
        ]);
    }

    /** Carga menús y grupos desde la base de datos */
    public function cargarDatos()
    {
        $this->menuGrupos = DB::table('menu_grupo')->orderBy('nombre')->pluck('nombre')->toArray();

        $query = DB::table('menu_grupo as mg')
            ->join('menu as m', 'm.parent_id', '=', 'mg.id')
            ->select('m.id', 'mg.nombre as menu', 'm.txt_comentario', 'm.icon', 'm.orden', 'm.estado');

        if (!empty($this->filtro['menu'])) {
            $query->where('mg.nombre', 'like', '%' . $this->filtro['menu'] . '%');
        }
        if (!empty($this->filtro['submenu'])) {
            $query->where('m.txt_comentario', 'like', '%' . $this->filtro['submenu'] . '%');
        }
        if (!empty($this->filtro['icon'])) {
            $query->where('m.icon', 'like', '%' . $this->filtro['icon'] . '%');
        }
        if (!empty($this->filtro['orden'])) {
            $query->where('m.orden', $this->filtro['orden']);
        }
        if ($this->filtro['estado'] !== '') {
            $query->where('m.estado', $this->filtro['estado']);
        }

        $this->menus = $query->get();
    }

    /** Actualiza los datos cuando se cambia algún filtro */
    public function updatedFiltro()
    {
        $this->cargarDatos();
    }

    /** Cierra y reinicia el formulario/modal */
    public function cerrarModal()
    {
        $this->submitted = false;
        $this->modalOpen = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->reset('form');
    }

    /** Abre el modal para crear o editar un menú */
    public function abrirModal($id = null)
    {
        $this->modalOpen = true;
        $this->modo = $id ? 'editar' : 'crear';

        if ($id) {
            $menu = DB::table('menu as m')
                ->join('menu_grupo as mg', 'm.parent_id', '=', 'mg.id')
                ->where('m.id', $id)
                ->select('m.*', 'mg.nombre as menu_grupo')
                ->first();

            $this->form = (array) $menu;
        } else {
            $this->form = [
                'id' => null,
                'menu_grupo' => '',
                'txt_comentario' => '',
                'icon' => '',
                'orden' => 1,
                'estado' => 1,
            ];
        }
    }

    /** Guarda o actualiza el menú y crea el componente Livewire si es nuevo */
    public function guardar()
{
    $this->submitted = true;
    $this->validate();

    DB::beginTransaction(); // ⬅️ Iniciar transacción

    try {
        // Verificar grupo
        $grupoId = DB::table('menu_grupo')->where('nombre', $this->form['menu_grupo'])->value('id');

        if (!$grupoId) {
            $grupoId = DB::table('menu_grupo')->insertGetId([
                'nombre' => $this->form['menu_grupo']
            ]);
        }

        // Construcción de ruta
        $route = strtolower(str_replace(' ', '_', $this->form['menu_grupo'])) . '.' . strtolower(str_replace(' ', '_', $this->form['txt_comentario']));

        $data = [
            'txt_comentario' => $this->form['txt_comentario'],
            'icon' => $this->form['icon'],
            'parent_id' => $grupoId,
            'route' => $route,
            'orden' => $this->form['orden'],
            'estado' => $this->form['estado'],
            'updated_at' => now(),
        ];

        if ($this->modo === 'editar' && $this->form['id']) {
            DB::table('menu')->where('id', $this->form['id'])->update($data);
             DB::table('permisos')
        ->where('id_menu', $this->form['id'])
        ->update([
            'estado' => $this->form['estado'],
            'updated_at' => now(),
            'updated_user' => auth()->id()
        ]);
        } else {
            $data['created_at'] = now();
            $menuId = DB::table('menu')->insertGetId($data);

            // Insertar permiso asociado
            DB::table('permisos')->insert([
                'id_menu' => $menuId,
                'nombre' => $this->form['txt_comentario'],
                'estado' => 1,
                'created_at' => now(),
                'created_user' => auth()->id(),
            ]);
        }

        DB::commit(); // ✅ Confirmar si todo fue bien
        $this->modalOpen = false;
        $this->cargarDatos();
        session()->flash('mensaje', 'Menú guardado correctamente.');
    } catch (\Throwable $e) {
        DB::rollBack(); // ❌ Deshacer todo si hay error
        Log::error('Error al guardar menú o permiso: ' . $e->getMessage());
        session()->flash('mensaje', 'Error al guardar: ' . $e->getMessage());
    }
}
}
