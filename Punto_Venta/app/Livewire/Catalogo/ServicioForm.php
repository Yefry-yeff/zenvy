<?php

namespace App\Livewire\Catalogo;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Servicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServicioForm extends Component
{
    use WithFileUploads;

    public $servicioId;
    public $isEditing = false;

    // Formulario principal
    public $form = [
        'nombre' => '',
        'descripcion' => '',
        'precio_base' => '',
        'descuento_unitario' => '',
        'descuento_tercera' => false,
        'descuento_cuarta' => false,
        'isv_id' => null,
        'estado_id' => 1,
        'users_id' => null,
    ];

    // Propiedades para imagen
    public $imagen;
    public $tieneImagenAnterior = false; // En lugar de almacenar la imagen directamente

    // Datos para selectores
    public $isvs = [];

    // Propiedades para validación
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';

    // Propiedades para modales
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    protected $rules = [
        'form.nombre' => 'required|string|max:80',
        'form.descripcion' => 'nullable|string|max:100',
        'form.precio_base' => 'required|numeric|min:0.01',
        'form.descuento_unitario' => 'nullable|numeric|min:0',
        'form.descuento_tercera' => 'boolean',
        'form.descuento_cuarta' => 'boolean',
        'form.isv_id' => 'required|integer|exists:isv,id',
        'form.estado_id' => 'required|integer',
        'imagen' => 'nullable|image|max:5120', // 5MB máximo
    ];

    protected $messages = [
        'form.nombre.required' => 'El nombre es obligatorio',
        'form.nombre.max' => 'El nombre no puede exceder 80 caracteres',
        'form.descripcion.max' => 'La descripción no puede exceder 100 caracteres',
        'form.precio_base.required' => 'El precio base es obligatorio',
        'form.precio_base.min' => 'El precio base debe ser mayor a 0',
        'form.descuento_unitario.min' => 'El descuento unitario no puede ser negativo',
        'form.isv_id.required' => 'El tipo de ISV es obligatorio',
        'form.isv_id.exists' => 'El tipo de ISV seleccionado no existe',
        'imagen.image' => 'El archivo debe ser una imagen válida',
        'imagen.max' => 'La imagen no puede ser mayor a 5MB',
    ];

    public function mount($id = null)
    {
        $this->cargarDatosIniciales();

        if ($id) {
            $this->servicioId = $id;
            $this->isEditing = true;
            $this->cargarServicio();
        }
    }

    public function cargarDatosIniciales()
    {
        $this->isvs = DB::table('isv')->orderBy('cantidad')->get();
    }

    public function cargarServicio()
    {
        $servicio = Servicio::find($this->servicioId);

        if ($servicio) {
            $this->form = [
                'nombre' => $servicio->nombre ?? '',
                'descripcion' => $servicio->descripcion ?? '',
                'precio_base' => $servicio->precio_base !== null ? strval($servicio->precio_base) : '',
                'descuento_unitario' => $servicio->descuento_unitario !== null ? strval($servicio->descuento_unitario) : '',
                'descuento_tercera' => $servicio->descuento_tercera ? true : false,
                'descuento_cuarta' => $servicio->descuento_cuarta ? true : false,
                'isv_id' => $servicio->isv_id,
                'estado_id' => $servicio->estado_id,
                'users_id' => $servicio->users_id,
            ];

            // Verificar si tiene imagen anterior (sin almacenar el BLOB directamente)
            $this->tieneImagenAnterior = !empty($servicio->imagen);
        }
    }

    public function guardar()
    {
        try {
            $this->validate();

            $datos = $this->form;
            $datos['users_id'] = Auth::id();

            // Sanitizar campos numéricos - convertir strings vacíos a NULL o valores por defecto
            $datos['precio_base'] = !empty($datos['precio_base']) ? floatval($datos['precio_base']) : 0;
            $datos['descuento_unitario'] = !empty($datos['descuento_unitario']) ? floatval($datos['descuento_unitario']) : 0;

            // Convertir checkboxes boolean a enteros
            $datos['descuento_tercera'] = $datos['descuento_tercera'] ? 1 : 0;
            $datos['descuento_cuarta'] = $datos['descuento_cuarta'] ? 1 : 0;

            // Procesar imagen
            if ($this->imagen) {
                // Nueva imagen subida
                $datos['imagen'] = file_get_contents($this->imagen->getRealPath());
            } elseif ($this->isEditing && $this->tieneImagenAnterior) {
                // Mantener imagen anterior (recuperarla de la BD)
                $servicioActual = Servicio::find($this->servicioId);
                $datos['imagen'] = $servicioActual ? $servicioActual->imagen : null;
            } else {
                // No hay imagen
                $datos['imagen'] = null;
            }

            if ($this->isEditing) {
                Servicio::actualizarServicio($this->servicioId, $datos);
                session()->flash('success', 'Servicio actualizado exitosamente.');
            } else {
                Servicio::crearServicio($datos);
                session()->flash('success', 'Servicio creado exitosamente.');
            }

            // Redirigir inmediatamente a la vista de servicios
            $this->dispatch('cambiarVista', ruta: 'Catalogo.Servicios');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Error de validación al guardar servicio', [
                'errores' => $e->errors(),
                'datos' => $this->form
            ]);
            
            $primerError = collect($e->errors())->flatten()->first();
            $this->mostrarError('Error de validación: ' . $primerError);

        } catch (\Exception $e) {
            Log::error('Error al guardar servicio', [
                'mensaje' => $e->getMessage(),
                'datos' => $datos, // Usar $datos en lugar de $this->form para ver los valores finales
                'isEditing' => $this->isEditing
            ]);
            $this->mostrarError('Hubo un error inesperado al guardar el servicio');
        }
    }

    public function volverALista()
    {
        $this->dispatch('cambiarVista', ruta: 'Catalogo.Servicios');
    }

    public function removerImagen()
    {
        $this->imagen = null;
        $this->tieneImagenAnterior = false;
    }

    public function getImagenMiniatura()
    {
        if ($this->imagen) {
            return 'data:image/*;base64,' . base64_encode(file_get_contents($this->imagen->getRealPath()));
        } elseif ($this->isEditing && $this->tieneImagenAnterior && $this->servicioId) {
            // Recuperar imagen de la base de datos solo cuando se necesite mostrar
            $servicio = Servicio::find($this->servicioId);
            if ($servicio && $servicio->imagen) {
                return 'data:image/*;base64,' . base64_encode($servicio->imagen);
            }
        }
        return null;
    }

    // Métodos para mostrar alertas y modales
    public function mostrarError($mensaje)
    {
        $this->mostrarModalError = true;
        $this->mensajeModalError = $mensaje;
    }

    public function mostrarExito($mensaje)
    {
        $this->mostrarModalExito = true;
        $this->mensajeModalExito = $mensaje;
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
        $this->mensajeModalExito = '';
    }

    public function render()
    {
        return view('livewire.catalogo.servicio-form');
    }
}
