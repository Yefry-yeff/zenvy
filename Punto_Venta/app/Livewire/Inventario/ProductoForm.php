<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Producto as ProductoModel;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Marca;
use App\Models\UnidadMedida;
use Illuminate\Support\Facades\Auth;

class ProductoForm extends Component
{
    public $productoId;
    public $isEditing = false;

    // Formulario principal
    public $form = [
        'nombre' => '',
        'descripcion' => '',
        'isv' => 0.15,
        'precio_base' => 0,
        'ultimo_costo_compra' => 0,
        'costo_promedio' => 0,
        'codigo_barra' => '',
        'codigo_estatal' => '',
        'estado_id' => 1,
        'subcategoria_id' => null,
        'marca_id' => null,
        'unidad_compra' => 1,
        'unidad_medida_compra_id' => null,
        'precio1' => 0,
        'precio2' => 0,
        'precio3' => 0,
        'precio4' => 0,
    ];

    // Datos para los selectores
    public $categorias = [];
    public $subcategorias = [];
    public $marcas = [];
    public $unidadesMedida = [];
    public $categoriaSeleccionada = null;

    protected $rules = [
        'form.nombre' => 'required|string|max:80',
        'form.descripcion' => 'nullable|string|max:45',
        'form.isv' => 'required|numeric|min:0|max:1',
        'form.precio_base' => 'required|numeric|min:0',
        'form.ultimo_costo_compra' => 'required|numeric|min:0',
        'form.costo_promedio' => 'required|numeric|min:0',
        'form.codigo_barra' => 'nullable|string|max:100',
        'form.codigo_estatal' => 'nullable|string|max:45',
        'form.estado_id' => 'required|integer',
        'form.subcategoria_id' => 'required|integer|exists:subcategoria,id',
        'form.marca_id' => 'required|integer|exists:marca,id',
        'form.unidad_compra' => 'required|numeric|min:0.01',
        'form.unidad_medida_compra_id' => 'required|integer|exists:unidad_medida,id',
        'form.precio1' => 'required|numeric|min:0',
        'form.precio2' => 'nullable|numeric|min:0',
        'form.precio3' => 'nullable|numeric|min:0',
        'form.precio4' => 'nullable|numeric|min:0',
    ];

    protected $messages = [
        'form.nombre.required' => 'El nombre es obligatorio',
        'form.nombre.max' => 'El nombre no puede exceder 80 caracteres',
        'form.descripcion.max' => 'La descripción no puede exceder 45 caracteres',
        'form.isv.required' => 'El ISV es obligatorio',
        'form.isv.numeric' => 'El ISV debe ser un número',
        'form.isv.min' => 'El ISV no puede ser menor a 0',
        'form.isv.max' => 'El ISV no puede ser mayor a 1',
        'form.precio_base.required' => 'El precio base es obligatorio',
        'form.precio_base.numeric' => 'El precio base debe ser un número',
        'form.precio_base.min' => 'El precio base no puede ser negativo',
        'form.ultimo_costo_compra.required' => 'El último costo de compra es obligatorio',
        'form.ultimo_costo_compra.numeric' => 'El último costo de compra debe ser un número',
        'form.ultimo_costo_compra.min' => 'El último costo de compra no puede ser negativo',
        'form.costo_promedio.required' => 'El costo promedio es obligatorio',
        'form.costo_promedio.numeric' => 'El costo promedio debe ser un número',
        'form.costo_promedio.min' => 'El costo promedio no puede ser negativo',
        'form.subcategoria_id.required' => 'La subcategoría es obligatoria',
        'form.subcategoria_id.exists' => 'La subcategoría seleccionada no existe',
        'form.marca_id.required' => 'La marca es obligatoria',
        'form.marca_id.exists' => 'La marca seleccionada no existe',
        'form.unidad_compra.required' => 'La unidad de compra es obligatoria',
        'form.unidad_compra.numeric' => 'La unidad de compra debe ser un número',
        'form.unidad_compra.min' => 'La unidad de compra debe ser mayor a 0',
        'form.unidad_medida_compra_id.required' => 'La unidad de medida es obligatoria',
        'form.unidad_medida_compra_id.exists' => 'La unidad de medida seleccionada no existe',
        'form.precio1.required' => 'El precio 1 es obligatorio',
        'form.precio1.numeric' => 'El precio 1 debe ser un número',
        'form.precio1.min' => 'El precio 1 no puede ser negativo',
    ];

    public function mount($id = null)
    {
        $this->cargarDatosIniciales();
        
        if ($id) {
            $this->productoId = $id;
            $this->isEditing = true;
            $this->cargarProducto();
        }
    }

    public function cargarDatosIniciales()
    {
        $this->categorias = Categoria::orderBy('nombre')->get();
        $this->marcas = Marca::orderBy('nombre')->get();
        $this->unidadesMedida = UnidadMedida::orderBy('nombre')->get();
    }

    public function cargarProducto()
    {
        $producto = ProductoModel::find($this->productoId);
        
        if ($producto) {
            $this->form = [
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'isv' => $producto->isv,
                'precio_base' => $producto->precio_base,
                'ultimo_costo_compra' => $producto->ultimo_costo_compra,
                'costo_promedio' => $producto->costo_promedio,
                'codigo_barra' => $producto->codigo_barra,
                'codigo_estatal' => $producto->codigo_estatal,
                'estado_id' => $producto->estado_id,
                'subcategoria_id' => $producto->subcategoria_id,
                'marca_id' => $producto->marca_id,
                'unidad_compra' => $producto->unidad_compra,
                'unidad_medida_compra_id' => $producto->unidad_medida_compra_id,
                'precio1' => $producto->precio1,
                'precio2' => $producto->precio2,
                'precio3' => $producto->precio3,
                'precio4' => $producto->precio4,
            ];

            // Cargar categoría y subcategorías correspondientes
            if ($producto->subcategoria_id) {
                $subcategoria = Subcategoria::find($producto->subcategoria_id);
                if ($subcategoria) {
                    $this->categoriaSeleccionada = $subcategoria->categoria_id;
                    $this->cargarSubcategorias();
                }
            }
        }
    }

    public function updatedCategoriaSeleccionada($value)
    {
        $this->form['subcategoria_id'] = null;
        $this->cargarSubcategorias();
    }

    public function cargarSubcategorias()
    {
        if ($this->categoriaSeleccionada) {
            $this->subcategorias = Subcategoria::where('categoria_id', $this->categoriaSeleccionada)
                ->orderBy('nombre')
                ->get();
        } else {
            $this->subcategorias = [];
        }
    }

    public function guardar()
    {
        $this->validate();

        try {
            $datos = $this->form;
            $datos['users_id'] = Auth::id();

            if ($this->isEditing) {
                ProductoModel::actualizarProducto($this->productoId, $datos);
                session()->flash('mensaje', 'Producto actualizado exitosamente.');
            } else {
                ProductoModel::crearProducto($datos);
                session()->flash('mensaje', 'Producto creado exitosamente.');
            }

            $this->volverALista();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar el producto: ' . $e->getMessage());
        }
    }

    public function volverALista()
    {
        $this->dispatch('cambiarVista', ruta: 'Inventario.producto');
    }

    public function render()
    {
        return view('livewire.inventario.producto-form');
    }
}
