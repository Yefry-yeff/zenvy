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

    // Propiedades para validación backend
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';
    public $camposConError = [];
    public $camposValidos = [];
    public $erroresValidacion = [];

    protected $rules = [
        'form.nombre' => 'required|string|max:80',
        'form.descripcion' => 'nullable|string|max:45',
        'form.isv' => 'nullable|numeric|min:0|max:1',
        'form.precio_base' => 'required|numeric|min:0',
        'form.ultimo_costo_compra' => 'nullable|numeric|min:0',
        'form.costo_promedio' => 'nullable|numeric|min:0',
        'form.codigo_barra' => 'nullable|string|max:100',
        'form.codigo_estatal' => 'nullable|string|max:45',
        'form.estado_id' => 'required|integer',
        'form.subcategoria_id' => 'required|integer|exists:subcategoria,id',
        'form.marca_id' => 'required|integer|exists:marca,id',
        'form.unidad_compra' => 'required|integer|min:1',
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
        'form.isv.numeric' => 'El ISV debe ser un número',
        'form.isv.min' => 'El ISV no puede ser menor a 0',
        'form.isv.max' => 'El ISV no puede ser mayor a 1',
        'form.precio_base.required' => 'El precio base es obligatorio',
        'form.precio_base.numeric' => 'El precio base debe ser un número',
        'form.precio_base.min' => 'El precio base no puede ser negativo',
        'form.ultimo_costo_compra.numeric' => 'El último costo de compra debe ser un número',
        'form.ultimo_costo_compra.min' => 'El último costo de compra no puede ser negativo',
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
        
        // Limpiar subcategoría si se cambia la categoría
        $this->removerErrorCampo('subcategoria');
        
        // Validar categoría
        if ($this->categoriaSeleccionada) {
            $this->removerErrorCampo('categoria');
            $this->marcarCampoValido('categoria');
        } else {
            $this->mostrarErrorCampo('categoria', 'Debe seleccionar una categoría');
        }
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
        // Verificar campos críticos antes de la validación completa
        $camposVacios = $this->verificarCamposCriticos();
        
        if (!empty($camposVacios)) {
            $primerCampoVacio = $camposVacios[0];
            $mensajes = [
                'nombre' => 'El nombre del producto es obligatorio',
                'marca' => 'Debe seleccionar una marca',
                'categoria' => 'Debe seleccionar una categoría',
                'subcategoria' => 'Debe seleccionar una subcategoría', 
                'precio_base' => 'El precio base es obligatorio',
                'precio1' => 'El precio 1 es obligatorio',
                'unidad_compra' => 'La unidad de compra es obligatoria',
                'unidad_medida' => 'Debe seleccionar una unidad de medida'
            ];
            
            $this->mostrarErrorCampo($primerCampoVacio, $mensajes[$primerCampoVacio]);
            return;
        }

        // Limpiar alertas antes de validar
        $this->cerrarAlerta();
        
        try {
            $this->validate();
            
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

    // ===== MÉTODOS DE VALIDACIÓN EN TIEMPO REAL =====
    
    public function updatedFormNombre()
    {
        try {
            $this->validateOnly('form.nombre');
            $this->removerErrorCampo('nombre');
            $this->marcarCampoValido('nombre');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('nombre', 'El nombre es obligatorio y no puede estar vacío');
        }
    }

    public function updatedFormMarcaId()
    {
        try {
            $this->validateOnly('form.marca_id');
            $this->removerErrorCampo('marca');
            $this->marcarCampoValido('marca');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('marca', 'Debe seleccionar una marca');
        }
    }

    public function updatedFormSubcategoriaId()
    {
        try {
            $this->validateOnly('form.subcategoria_id');
            $this->removerErrorCampo('subcategoria');
            $this->marcarCampoValido('subcategoria');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('subcategoria', 'Debe seleccionar una subcategoría');
        }
    }

    public function updatedFormPrecioBase()
    {
        try {
            $this->validateOnly('form.precio_base');
            $this->removerErrorCampo('precio_base');
            $this->marcarCampoValido('precio_base');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('precio_base', 'El precio base debe ser mayor a 0');
        }
    }

    public function updatedFormPrecio1()
    {
        try {
            $this->validateOnly('form.precio1');
            $this->removerErrorCampo('precio1');
            $this->marcarCampoValido('precio1');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('precio1', 'El precio 1 debe ser mayor a 0');
        }
    }

    public function updatedFormUnidadCompra()
    {
        try {
            // Asegurar que sea un entero
            if ($this->form['unidad_compra'] !== null && $this->form['unidad_compra'] !== '') {
                $this->form['unidad_compra'] = (int) $this->form['unidad_compra'];
            }
            
            $this->validateOnly('form.unidad_compra');
            $this->removerErrorCampo('unidad_compra');
            $this->marcarCampoValido('unidad_compra');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('unidad_compra', 'La unidad de compra debe ser un número entero mayor a 0');
        }
    }

    public function updatedFormUnidadMedidaCompraId()
    {
        try {
            $this->validateOnly('form.unidad_medida_compra_id');
            $this->removerErrorCampo('unidad_medida');
            $this->marcarCampoValido('unidad_medida');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('unidad_medida', 'Debe seleccionar una unidad de medida');
        }
    }

    // ===== MÉTODOS PARA MANEJO DE ERRORES Y ESTILOS =====
    
    private function mostrarErrorCampo($campo, $mensaje)
    {
        $this->camposConError[] = $campo;
        $this->camposConError = array_unique($this->camposConError);
        
        // Remover de campos válidos si está ahí
        $this->camposValidos = array_filter($this->camposValidos, function($c) use ($campo) {
            return $c !== $campo;
        });
        
        $this->mostrarAlerta = true;
        $this->mensajeAlerta = $mensaje;
        $this->campoConError = $campo;
        
        // Guardar error en array de errores
        $this->erroresValidacion[$campo] = $mensaje;
    }

    private function removerErrorCampo($campo)
    {
        $this->camposConError = array_filter($this->camposConError, function($c) use ($campo) {
            return $c !== $campo;
        });
        
        // Remover de errores
        unset($this->erroresValidacion[$campo]);
        
        if ($this->campoConError === $campo) {
            $this->cerrarAlerta();
        }
    }

    private function marcarCampoValido($campo)
    {
        $this->camposValidos[] = $campo;
        $this->camposValidos = array_unique($this->camposValidos);
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->campoConError = '';
    }

    // Método para obtener clases CSS dinámicas
    public function getClaseCampo($campo)
    {
        if (in_array($campo, $this->camposConError)) {
            return 'is-invalid campo-obligatorio-vacio';
        }
        
        if (in_array($campo, $this->camposValidos)) {
            return 'campo-valido';
        }
        
        return '';
    }

    // Método para verificar si todos los campos críticos están completos
    public function verificarCamposCriticos()
    {
        $camposCriticos = ['nombre', 'marca', 'categoria', 'subcategoria', 'precio_base', 'precio1', 'unidad_compra', 'unidad_medida'];
        $camposVacios = [];

        foreach ($camposCriticos as $campo) {
            $valor = '';
            switch ($campo) {
                case 'nombre':
                    $valor = $this->form['nombre'];
                    break;
                case 'marca':
                    $valor = $this->form['marca_id'];
                    break;
                case 'categoria':
                    $valor = $this->categoriaSeleccionada;
                    break;
                case 'subcategoria':
                    $valor = $this->form['subcategoria_id'];
                    break;
                case 'precio_base':
                    $valor = $this->form['precio_base'];
                    break;
                case 'precio1':
                    $valor = $this->form['precio1'];
                    break;
                case 'unidad_compra':
                    $valor = $this->form['unidad_compra'];
                    break;
                case 'unidad_medida':
                    $valor = $this->form['unidad_medida_compra_id'];
                    break;
            }

            if (empty($valor)) {
                $camposVacios[] = $campo;
            }
        }

        return $camposVacios;
    }

    public function render()
    {
        return view('livewire.inventario.producto-form');
    }
}
