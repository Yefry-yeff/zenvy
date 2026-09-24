<?php

namespace App\Livewire\Inventario;

use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\IdZenvyValencia;
use App\Services\SincronizacionSubcategoriasService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CategoriaForm extends Component
{
    public $categoriaId;
    public $form = [
        'nombre' => '',
    ];
    public $subcategorias;
    public $nuevaSubcategoria = '';
    public $mostrarMensaje = false;

    // Propiedades para categorías de Valencia
    public $esCategoriaValencia = false;
    
    // Servicio como propiedad privada (no pública)
    private $sincronizacionService;

    // Propiedades para el flujo de creación
    public $mostrarSeccionSubcategorias = false;
    public $subcategoriasTemporales = [];

    // Propiedades para modal de eliminar subcategoría
    public $modalEliminarSubcategoriaAbierto = false;
    public $subcategoriaAEliminar = null;

    // Propiedades para modal de editar subcategoría
    public $modalEditarSubcategoriaAbierto = false;
    public $subcategoriaEditando = null;
    public $formSubcategoria = [
        'id' => null,
        'nombre' => '',
    ];

    // Propiedades para efectos de carga en sincronización de subcategorías
    public $sincronizandoSubcategorias = false;
    public $progreso = null;
    public $detallesSincronizacion = null;

    public function mount($id = null)
    {
        $this->subcategorias = collect(); // Inicializar como colección vacía

        if ($id) {
            $this->categoriaId = $id;
            $categoria = Categoria::findOrFail($id);
            $this->form['nombre'] = $categoria->nombre;
            
            // Verificar si es una categoría de Valencia
            $this->esCategoriaValencia = $this->verificarSiEsCategoriaValencia($id);
            
            $this->cargarSubcategorias();
        } else {
            $this->categoriaId = null;
            $this->esCategoriaValencia = false;
        }
    }

    private function getSincronizacionService()
    {
        if (!$this->sincronizacionService) {
            $this->sincronizacionService = app(SincronizacionSubcategoriasService::class);
        }
        return $this->sincronizacionService;
    }

    public function cargarSubcategorias()
    {
        if ($this->categoriaId) {
            if ($this->esCategoriaValencia) {
                // Para categorías de Valencia, separar subcategorías por origen
                $this->subcategorias = [
                    'zenvy' => Subcategoria::where('categoria_id', $this->categoriaId)
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                  ->from('id_zenvy_valencia')
                                  ->whereRaw('id_zenvy_valencia.id_zenvy = subcategoria.id')
                                  ->where('id_zenvy_valencia.tipo_dato_migrado_id', 4);
                        })->orderBy('nombre')->get(['id', 'nombre'])->toArray(),
                    
                    'valencia' => Subcategoria::where('categoria_id', $this->categoriaId)
                        ->whereExists(function ($query) {
                            $query->select(DB::raw(1))
                                  ->from('id_zenvy_valencia')
                                  ->whereRaw('id_zenvy_valencia.id_zenvy = subcategoria.id')
                                  ->where('id_zenvy_valencia.tipo_dato_migrado_id', 4);
                        })->orderBy('nombre')->get(['id', 'nombre'])->toArray(),
                ];
            } else {
                // Para categorías propias, cargar normalmente
                $this->subcategorias = Subcategoria::where('categoria_id', $this->categoriaId)
                    ->get(['id', 'nombre'])->toArray();
            }
        }
    }

    private function verificarSiEsCategoriaValencia($categoriaId)
    {
        return IdZenvyValencia::where('id_zenvy', $categoriaId)
                             ->where('tipo_dato_migrado_id', 3)
                             ->exists();
    }

    public function sincronizarSubcategoriasValencia()
    {
        if (!$this->esCategoriaValencia) {
            session()->flash('error', 'Esta función solo está disponible para categorías de Valencia.');
            return;
        }

        try {
            // Iniciar el proceso de sincronización
            $this->sincronizandoSubcategorias = true;
            $this->progreso = 0;
            $this->detallesSincronizacion = null;

            // Simular progreso de sincronización
            for ($i = 0; $i <= 100; $i += 25) {
                $this->progreso = $i;
                $this->dispatch('actualizarProgreso', $this->progreso);
                usleep(200000); // 0.2 segundos
            }

            $resultado = $this->getSincronizacionService()->forzarSincronizacion();
            
            // Finalizar progreso
            $this->progreso = 100;
            $this->dispatch('actualizarProgreso', $this->progreso);
            
            // Preparar detalles de sincronización
            $this->detallesSincronizacion = [
                'subcategorias_sincronizadas' => ($resultado['estadisticas']['nuevas'] ?? 0) + ($resultado['estadisticas']['actualizadas'] ?? 0),
                'subcategorias_nuevas' => $resultado['estadisticas']['nuevas'] ?? 0,
                'subcategorias_actualizadas' => $resultado['estadisticas']['actualizadas'] ?? 0,
                'sin_cambios' => $resultado['estadisticas']['sin_cambios'] ?? 0,
                'total_procesadas' => $resultado['estadisticas']['total_procesadas'] ?? 0,
                'tiempo_ejecucion' => '~2 segundos'
            ];
            
            $this->cargarSubcategorias(); // Recargar después de sincronizar
            
            // Mensajes de estado
            if (($resultado['estadisticas']['nuevas'] ?? 0) > 0 || ($resultado['estadisticas']['actualizadas'] ?? 0) > 0) {
                session()->flash('mensaje', '✅ Sincronización completada: ' . $this->detallesSincronizacion['subcategorias_sincronizadas'] . ' subcategorías procesadas exitosamente.');
            } else {
                session()->flash('mensaje', '✅ Sincronización completada: Todas las subcategorías están actualizadas.');
            }
            
            // Finalizar estado de carga
            $this->sincronizandoSubcategorias = false;
            
        } catch (\Exception $e) {
            $this->sincronizandoSubcategorias = false;
            $this->progreso = 0;
            
            session()->flash('error', 'Error al sincronizar subcategorías: ' . $e->getMessage());
        }
    }

    public function cerrarDetallesSincronizacion()
    {
        $this->detallesSincronizacion = null;
    }

    public function volver()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.Categoria');
    }

    public function procederASubcategorias()
    {
        $this->validate([
            'form.nombre' => 'required|string|max:255|unique:categoria,nombre,' . $this->categoriaId,
        ], [
            'form.nombre.required' => 'El nombre de la categoría es obligatorio.',
            'form.nombre.unique' => 'Ya existe una categoría con ese nombre.'
        ]);

        $this->mostrarSeccionSubcategorias = true;
        session()->flash('mensaje', 'Nombre válido. Ahora puedes agregar subcategorías o guardar directamente.');
    }

    public function agregarSubcategoriaTemporal()
    {
        $this->validate([
            'nuevaSubcategoria' => 'required|string|max:255',
        ], [
            'nuevaSubcategoria.required' => 'El nombre de la subcategoría es obligatorio.',
        ]);

        // Verificar que no esté duplicada
        if (in_array($this->nuevaSubcategoria, $this->subcategoriasTemporales)) {
            session()->flash('error', 'Ya agregaste una subcategoría con ese nombre.');
            return;
        }

        $this->subcategoriasTemporales[] = $this->nuevaSubcategoria;
        $this->nuevaSubcategoria = '';
        session()->flash('mensaje', 'Subcategoría agregada a la lista.');
    }

    public function eliminarSubcategoriaTemporal($index)
    {
        unset($this->subcategoriasTemporales[$index]);
        $this->subcategoriasTemporales = array_values($this->subcategoriasTemporales); // Reindexar
    }

    public function guardar()
    {
        $this->validate([
            'form.nombre' => 'required|string|max:255|unique:categoria,nombre,' . $this->categoriaId,
        ], [
            'form.nombre.required' => 'El nombre de la categoría es obligatorio.',
            'form.nombre.unique' => 'Ya existe una categoría con ese nombre.'
        ]);

        if ($this->categoriaId) {
            // Actualizar categoría existente
            $categoria = Categoria::findOrFail($this->categoriaId);
            $categoria->update($this->form);
        } else {
            // Crear nueva categoría
            $categoria = Categoria::create($this->form);
            $this->categoriaId = $categoria->id;
            
            // Crear subcategorías temporales si las hay
            foreach ($this->subcategoriasTemporales as $nombreSubcategoria) {
                Subcategoria::create([
                    'nombre' => $nombreSubcategoria,
                    'categoria_id' => $categoria->id,
                ]);
            }
            
            $this->cargarSubcategorias();
        }

        $this->mostrarMensaje = true;
        session()->flash('mensaje', 'Categoría guardada correctamente.');
        
        // Redirigir a la lista de categorías
        $this->dispatch('cambiarVista', ruta: 'Inventario.Categoria');
    }

    public function agregarSubcategoria()
    {
        $this->validate([
            'nuevaSubcategoria' => 'required|string|max:255',
        ], [
            'nuevaSubcategoria.required' => 'El nombre de la subcategoría es obligatorio.',
        ]);

        if (!$this->categoriaId) {
            session()->flash('error', 'Primero debe guardar la categoría.');
            return;
        }

        Subcategoria::create([
            'nombre' => $this->nuevaSubcategoria,
            'categoria_id' => $this->categoriaId,
        ]);

        $this->nuevaSubcategoria = '';
        $this->cargarSubcategorias();
        session()->flash('mensaje', 'Subcategoría agregada correctamente.');
    }

    public function eliminarSubcategoria($subcategoriaId)
    {
        $this->subcategoriaAEliminar = $subcategoriaId;
        $this->modalEliminarSubcategoriaAbierto = true;
    }

    public function confirmarEliminarSubcategoria()
    {
        $subcategoria = Subcategoria::find($this->subcategoriaAEliminar);
        if ($subcategoria) {
            $subcategoria->delete();
            $this->cargarSubcategorias();
            session()->flash('mensaje', 'Subcategoría eliminada correctamente.');
        }
        $this->cerrarModalEliminarSubcategoria();
    }

    public function cerrarModalEliminarSubcategoria()
    {
        $this->modalEliminarSubcategoriaAbierto = false;
        $this->subcategoriaAEliminar = null;
    }

    public function editarSubcategoria($subcategoriaId)
    {
        $subcategoria = Subcategoria::findOrFail($subcategoriaId);
        $this->formSubcategoria['id'] = $subcategoria->id;
        $this->formSubcategoria['nombre'] = $subcategoria->nombre;
        $this->subcategoriaEditando = $subcategoriaId;
        $this->modalEditarSubcategoriaAbierto = true;
    }

    public function guardarSubcategoria()
    {
        $this->validate([
            'formSubcategoria.nombre' => 'required|string|max:255|unique:subcategoria,nombre,' . $this->formSubcategoria['id'],
        ], [
            'formSubcategoria.nombre.required' => 'El nombre de la subcategoría es obligatorio.',
            'formSubcategoria.nombre.unique' => 'Ya existe una subcategoría con ese nombre.',
        ]);

        $subcategoria = Subcategoria::findOrFail($this->formSubcategoria['id']);
        $subcategoria->update(['nombre' => $this->formSubcategoria['nombre']]);

        $this->cargarSubcategorias();
        $this->cerrarModalEditarSubcategoria();
        session()->flash('mensaje', 'Subcategoría actualizada correctamente.');
    }

    public function cerrarModalEditarSubcategoria()
    {
        $this->modalEditarSubcategoriaAbierto = false;
        $this->subcategoriaEditando = null;
        $this->formSubcategoria = ['id' => null, 'nombre' => ''];
    }

    public function render()
    {
        return view('livewire.inventario.categoria-form');
    }
}
