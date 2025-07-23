<?php
namespace App\Livewire\Menu;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Index extends Component
{
    public $menus = [];
    public $menuGrupos = [];
    public $submitted = false;

    protected $rules = [
    'form.menu_grupo'     => 'required|string',
    'form.txt_comentario' => 'required|string',
    'form.icon'           => 'required|string',
    'form.orden'          => 'required|numeric|min:1',
    'form.estado'         => 'required|boolean',
];

    public $form = [
        'id' => null,
        'menu_grupo' => '',
        'txt_comentario' => '',
        'icon' => '',
        'orden' => 1,
        'estado' => 1,
    ];

    public $modo = 'crear'; // o 'editar'
    public $modalOpen = false;

    public function mount()
    {
        logger()->info('[MenuIndex] mount ejecutado');
        $this->cargarDatos();
    }
public function render()
{
    logger()->info('[MenuIndex] render ejecutado');
    return view('livewire.menu.index', [
        'menus' => $this->menus,
        'menuGrupos' => $this->menuGrupos,
    ]);
}
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

public function updatedFiltro()
{
    $this->cargarDatos();
}
public function cerrarModal()
{
    $this->modalOpen = false;
    $this->reset('form');
}

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
  public function messages()
    {
        return [
            'form.menu_grupo.required'     => 'Campo obligatorio',
            'form.txt_comentario.required' => 'Campo obligatorio',
            'form.icon.required'           => 'Campo obligatorio',
            'form.orden.required'          => 'Campo obligatorio',
            'form.orden.numeric'           => 'Debe ser un número',
            'form.orden.min'               => 'Debe ser al menos 1',
            'form.estado.required'         => 'Campo obligatorio',
            'form.estado.boolean'          => 'Valor inválido',
        ];
    }

    public function guardar()
    {

         $this->submitted = true; // <- Esto es importante

        $this->validate();

        // Buscar o insertar grupo
        $grupoId = DB::table('menu_grupo')->where('nombre', $this->form['menu_grupo'])->value('id');

        if (!$grupoId) {
            $grupoId = DB::table('menu_grupo')->insertGetId([
                'nombre' => $this->form['menu_grupo']
            ]);
        }

        $data = [
            'txt_comentario' => $this->form['txt_comentario'],
            'icon' => $this->form['icon'],
            'parent_id' => $grupoId,
            'route' => strtolower(str_replace(' ', '_', $this->form['menu_grupo'])) . '.' . strtolower(str_replace(' ', '_', $this->form['txt_comentario'])),
            'orden' => $this->form['orden'],
            'estado' => $this->form['estado'],
            'updated_at' => Carbon::now(),
        ];

        if ($this->modo === 'editar' && $this->form['id']) {
            DB::table('menu')->where('id', $this->form['id'])->update($data);
        } else {
            $data['created_at'] = Carbon::now();
            DB::table('menu')->insert($data);
        }

        $this->modalOpen = false;
        $this->cargarDatos();
    }
public $filtro = [
    'menu' => '',
    'submenu' => '',
    'icon' => '',
    'orden' => '',
    'estado' => '',
];


}
