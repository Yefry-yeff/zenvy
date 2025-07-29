<?php

namespace App\Livewire\Menu;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;

/**
 * Componente Livewire para la gestión de menús y submenús.
 */
class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $menuGrupos = [];
    public $submitted = false;

    public $form = [
        'id' => null,
        'menu_grupo' => '',
        'txt_comentario' => '',
        'icon' => '',
        'orden' => 1,
        'estado_id' => 1,
    ];

    public $modo = 'crear';
    public $modalOpen = false;

    public $filtro = [
        'menu' => '',
        'submenu' => '',
        'icon' => '',
        'orden' => '',
        'estado_id' => '',
    ];

    protected $rules = [
        'form.menu_grupo' => 'required|string',
        'form.txt_comentario' => 'required|string',
        'form.icon' => 'required|string',
        'form.orden' => 'required|numeric|min:1',
        'form.estado_id' => 'required|integer',
    ];

    public function messages()
    {
        return [
            'form.menu_grupo.required' => 'Campo obligatorio',
            'form.txt_comentario.required' => 'Campo obligatorio',
            'form.orden.required' => 'Campo obligatorio',
            'form.orden.numeric' => 'Debe ser un número',
            'form.orden.min' => 'Debe ser al menos 1',
            'form.estado_id.required' => 'Campo obligatorio',
        ];
    }

    public function updatedFormMenuGrupo($value)
    {
        if ($this->modo === 'crear' && $value !== '') {
            $icono = DB::table('menu_grupo')->where('nombre', $value)->value('icon');
            if ($icono) {
                $this->form['icon'] = $icono;
            }
        }
    }

    public function mount()
    {
        $this->cargarDatos();
    }

public function render()
{
    $menus = DB::table('menu as m')
        ->join('menu_grupo as mg', 'mg.id', '=', 'm.parent_id')
        ->select(
            'm.id',
            'mg.nombre as menu',
            'm.txt_comentario',
            'mg.icon',
            'm.orden',
            'm.estado_id'
        )
        ->orderBy('mg.id')
        ->orderBy('m.orden')
        ->paginate(10); // 👈 Paginación de 10 filas

    return view('livewire.menu.index', compact('menus'));
}


    public function cargarDatos()
    {
        $this->menuGrupos = DB::table('menu_grupo')->orderBy('nombre')->pluck('nombre')->toArray();
    }

    public function updatedFiltro()
    {
        $this->cargarDatos();
    }

    public function cerrarModal()
    {
        $this->submitted = false;
        $this->modalOpen = false;
        $this->resetErrorBag();
        $this->resetValidation();
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
                ->select('m.*', 'mg.nombre as menu_grupo', 'mg.icon')
                ->first();

            $this->form = (array) $menu;
        } else {
            $this->form = [
                'id' => null,
                'menu_grupo' => '',
                'txt_comentario' => '',
                'icon' => '',
                'orden' => 1,
                'estado_id' => 1,
            ];
        }
    }

    public function guardar()
    {
        $this->submitted = true;
        $this->validate();

        try {
            if ($this->modo === 'editar' && $this->form['id']) {
                DB::statement("CALL sp_gestion_menu_sidebar(?, ?, ?, ?, ?, ?, ?)", [
                    2,
                    $this->form['id'],
                    $this->form['menu_grupo'],
                    $this->form['icon'],
                    $this->form['txt_comentario'],
                    $this->form['orden'],
                    $this->form['estado_id']
                ]);
            } else {
                DB::statement("CALL sp_gestion_menu_sidebar(?, ?, ?, ?, ?, ?, ?)", [
                    1,
                    null,
                    $this->form['menu_grupo'],
                    $this->form['icon'],
                    $this->form['txt_comentario'],
                    $this->form['orden'],
                    $this->form['estado_id']
                ]);

                // Generar ruta como sala_de_ventas.lista_de_transacciones
                $rutaBase = $this->form['menu_grupo'] . '.' . $this->form['txt_comentario'];
                $rutaStudly = collect(explode('.', $rutaBase))->map(fn($segmento) => Str::studly($segmento));
                $rutaComponente = implode('.', collect(explode('.', $rutaBase))->map(fn($s) => Str::slug($s, '-')));

                $pathClase = app_path('Livewire/' . $rutaStudly->implode('/')) . '.php';
                $pathVista = resource_path('views/livewire/' . $rutaComponente) . '.blade.php';

                if (!file_exists($pathClase)) {
                    $process = new Process(['php', 'artisan', 'livewire:make', $rutaComponente]);
                    $process->setWorkingDirectory(base_path());
                    $process->run();

                    if (!$process->isSuccessful()) {
                        throw new ProcessFailedException($process);
                    }

                    if (!file_exists($pathVista)) {
                        file_put_contents($pathVista, <<<BLADE
<div>
    <!-- Vista generada automáticamente -->
    <h1 class="text-xl font-bold">{$this->form['txt_comentario']}</h1>
</div>
BLADE
                        );
                    }
                }
            }

            $this->modalOpen = false;
            $this->cargarDatos();
            session()->flash('mensaje', 'Menú guardado correctamente.');
        } catch (\Throwable $e) {
            Log::error('❌ Error al guardar menú o generar componente: ' . $e->getMessage());
            session()->flash('mensaje', 'Error al guardar: ' . $e->getMessage());
        }
    }
}
