<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Producto as ProductoModel;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Marca;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductoForm extends Component
{
    public $productoId;
    public $isEditing = false;

    // Formulario principal
    public $form = [
        'nombre' => '',
        'descripcion' => '',
        'codigo_barra' => '',
        'codigo_estatal' => '',
        'estado_id' => 1,
        'subcategoria_id' => null,
        'marca_id' => null,
        'isv_id' => null,
        'precio_base' => 0,
        'descuento_unitario' => 0,
        'descuento_tercera' => false,
        'descuento_cuarta' => false,
        'ultimo_costo_compra' => 0,
        'costo_promedio' => 0,
        'unidad_medida_venta_id' => null,
        'users_id' => null,
    ];

    // Datos para los selectores
    public $categorias = [];
    public $subcategorias = [];
    public $marcas = [];
    public $unidadesMedida = [];
    public $isvs = [];
    public $categoriaSeleccionada = null;

    // Propiedades para validación backend
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';
    public $camposConError = [];
    public $erroresValidacion = [];

    // Propiedades para modales
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    protected $rules = [
        'form.nombre' => 'required|string|max:80',
        'form.descripcion' => 'nullable|string|max:45',
        'form.codigo_barra' => 'nullable|string|max:100',
        'form.codigo_estatal' => 'nullable|string|max:45',
        'form.estado_id' => 'required|integer',
        'form.subcategoria_id' => 'required|integer|exists:subcategoria,id',
        'form.marca_id' => 'required|integer|exists:marca,id',
        'form.isv_id' => 'required|integer|exists:isv,id',
        'form.precio_base' => 'required|numeric|min:0.01',
        'form.descuento_unitario' => 'nullable|numeric|min:0',
        'form.descuento_tercera' => 'boolean',
        'form.descuento_cuarta' => 'boolean',
        'form.ultimo_costo_compra' => 'nullable|numeric|min:0',
        'form.costo_promedio' => 'nullable|numeric|min:0',
        'form.unidad_medida_venta_id' => 'required|integer|exists:unidad_medida,id',
    ];

    protected $messages = [
        'form.nombre.required' => 'El nombre es obligatorio',
        'form.nombre.max' => 'El nombre no puede exceder 80 caracteres',
        'form.descripcion.max' => 'La descripción no puede exceder 45 caracteres',
        'form.subcategoria_id.required' => 'La subcategoría es obligatoria',
        'form.subcategoria_id.exists' => 'La subcategoría seleccionada no existe',
        'form.marca_id.required' => 'La marca es obligatoria',
        'form.marca_id.exists' => 'La marca seleccionada no existe',
        'form.isv_id.required' => 'El tipo de ISV es obligatorio',
        'form.isv_id.exists' => 'El tipo de ISV seleccionado no existe',
        'form.precio_base.required' => 'El precio base es obligatorio',
        'form.precio_base.min' => 'El precio base debe ser mayor a 0',
        'form.descuento_unitario.min' => 'El descuento unitario no puede ser negativo',
        'form.unidad_medida_venta_id.required' => 'La unidad de medida es obligatoria',
        'form.unidad_medida_venta_id.exists' => 'La unidad de medida seleccionada no existe',
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
        $this->unidadesMedida = DB::table('unidad_medida')->orderBy('nombre')->get();
        $this->isvs = DB::table('isv')->orderBy('cantidad')->get();
    }

    public function cargarProducto()
    {
        $producto = ProductoModel::find($this->productoId);

        if ($producto) {
            $this->form = [
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'codigo_barra' => $producto->codigo_barra,
                'codigo_estatal' => $producto->codigo_estatal,
                'estado_id' => $producto->estado_id,
                'subcategoria_id' => $producto->subcategoria_id,
                'marca_id' => $producto->marca_id,
                'isv_id' => $producto->isv_id ?? null,
                'precio_base' => $producto->precio_base ?? 0,
                'descuento_unitario' => $producto->descuento_unitario ?? 0,
                'descuento_tercera' => $producto->descuento_tercera ? true : false,
                'descuento_cuarta' => $producto->descuento_cuarta ? true : false,
                'ultimo_costo_compra' => $producto->ultimo_costo_compra ?? 0,
                'costo_promedio' => $producto->costo_promedio ?? 0,
                'unidad_medida_venta_id' => $producto->unidad_medida_venta_id,
                'users_id' => $producto->users_id,
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
        $this->limpiarErrorCampo('subcategoria');

        // Validar categoría
        if ($this->categoriaSeleccionada) {
            $this->limpiarErrorCampo('categoria');
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
                'unidad_medida' => 'Debe seleccionar una unidad de medida',
                'isv_id' => 'Debe seleccionar un tipo de ISV'
            ];

            $this->mostrarErrorCampo($primerCampoVacio, $mensajes[$primerCampoVacio]);
            return;
        }

        // Verificación específica para precio_base = 0
        if ($this->form['precio_base'] == 0) {
            $this->mostrarErrorCampo('precio_base', 'El precio base no puede ser 0, debe ser mayor a 0');
            return;
        }

        // Limpiar alertas antes de validar
        $this->cerrarAlerta();

        try {
            // Validar los datos del formulario
            $this->validate();


            $datos = $this->form;
            $datos['users_id'] = Auth::id();

            // Convertir checkboxes boolean a enteros para el SP
            $datos['descuento_tercera'] = $datos['descuento_tercera'] ? 1 : 0;
            $datos['descuento_cuarta'] = $datos['descuento_cuarta'] ? 1 : 0;

            // Log para debugging
            Log::info('Intentando guardar producto', [
                'datos' => $datos,
                'isEditing' => $this->isEditing,
                'productoId' => $this->productoId
            ]);

            if ($this->isEditing) {
                ProductoModel::actualizarProducto($this->productoId, $datos);
                Log::info('Producto actualizado exitosamente', ['id' => $this->productoId]);
                $this->mostrarExito('Producto actualizado exitosamente.');
            } else {
                $resultado = ProductoModel::crearProducto($datos);
                Log::info('Producto creado exitosamente', ['resultado' => $resultado]);
                $this->mostrarExito('Producto creado exitosamente.');
            }

            // Redirigir después de mostrar el modal
            $this->dispatch('redirigirEnTresSeg');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error de validación - mostrar errores específicos
            Log::warning('Error de validación al guardar producto', [
                'errores' => $e->errors(),
                'datos' => $this->form
            ]);
            $this->mostrarError('Error de validación: Revise los campos marcados en rojo');

        } catch (\Exception $e) {
            // Error general - log completo y mensaje simple al usuario
            Log::error('Error al guardar producto', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'datos' => $this->form,
                'isEditing' => $this->isEditing
            ]);
            $this->mostrarError('Hubo un error inesperado al guardar el producto');
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
            $this->limpiarErrorCampo('nombre');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('nombre', 'El nombre es obligatorio y no puede estar vacío');
        }
    }

    public function updatedFormMarcaId()
    {
        try {
            $this->validateOnly('form.marca_id');
            $this->limpiarErrorCampo('marca');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('marca', 'Debe seleccionar una marca');
        }
    }

    public function updatedFormSubcategoriaId()
    {
        try {
            $this->validateOnly('form.subcategoria_id');
            $this->limpiarErrorCampo('subcategoria');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->mostrarErrorCampo('subcategoria', 'Debe seleccionar una subcategoría');
        }
    }

    // ===== MÉTODOS PARA MANEJO DE ERRORES Y ESTILOS =====

    private function mostrarErrorCampo($campo, $mensaje)
    {
        $this->camposConError[] = $campo;
        $this->camposConError = array_unique($this->camposConError);

        $this->mostrarAlerta = true;
        $this->mensajeAlerta = $mensaje;
        $this->campoConError = $campo;

        // Guardar error en array de errores
        $this->erroresValidacion[$campo] = $mensaje;
    }

    private function limpiarErrorCampo($campo)
    {
        // Remover de errores
        $this->camposConError = array_filter($this->camposConError, function($c) use ($campo) {
            return $c !== $campo;
        });

        // Remover de errores
        unset($this->erroresValidacion[$campo]);

        if ($this->campoConError === $campo) {
            $this->cerrarAlerta();
        }
    }

    public function calcularMargenGanancia()
    {
        $ultimoCosto = floatval($this->form['ultimo_costo_compra'] ?? 0);
        $precioBase = floatval($this->form['precio_base'] ?? 0);

        if ($ultimoCosto > 0 && $precioBase > 0) {
            $ganancia = $precioBase - $ultimoCosto;
            $margen = ($ganancia / $ultimoCosto) * 100;
            return round($margen, 2);
        }

        return 0;
    }

    public function updatedFormUltimoCostoCompra()
    {
        $this->dispatch('actualizarMargen', $this->calcularMargenGanancia());
    }

    public function updatedFormPrecioBase()
    {
        $this->dispatch('actualizarMargen', $this->calcularMargenGanancia());
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->campoConError = '';
    }

    // ===== MÉTODOS PARA MANEJO DE MODALES =====

    public function mostrarExito($mensaje)
    {
        $this->mensajeModalExito = $mensaje;
        $this->mostrarModalExito = true;
    }

    public function mostrarError($mensaje)
    {
        $this->mensajeModalError = $mensaje;
        $this->mostrarModalError = true;
    }

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
        $this->mensajeModalExito = '';

        // Redirigir a la tabla de productos después de cerrar el modal
        $this->dispatch('cambiarVista', ruta: 'Inventario.producto');
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }

    // Método para obtener clases CSS dinámicas
    public function getClaseCampo($campo)
    {
        if (in_array($campo, $this->camposConError)) {
            return 'is-invalid campo-obligatorio-vacio';
        }

        return '';
    }

    // Método para verificar si todos los campos críticos están completos
    public function verificarCamposCriticos()
    {
        $camposCriticos = ['nombre', 'marca', 'categoria', 'subcategoria', 'precio_base', 'unidad_medida', 'isv_id'];
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
                case 'unidad_medida':
                    $valor = $this->form['unidad_medida_venta_id'];
                    break;
                case 'isv_id':
                    $valor = $this->form['isv_id'];
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
