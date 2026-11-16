<?php

namespace App\Livewire\Inventario;

use Livewire\Component;
use App\Models\Isv as IsvModel;
use Exception;
use Illuminate\Support\Facades\Log;

class Isv extends Component
{
    // Propiedades principales
    public $isvs = [];
    public $mostrarModal = false;
    
    // Alertas
    public $mostrarAlertaExito = false;
    public $mostrarAlertaError = false;
    public $mensajeExito = '';
    public $mensajeError = '';
    
    // Formulario con validación
    public $form = [
        'cantidad' => ''
    ];

    // Reglas de validación
    protected function rules()
    {
        return [
            'form.cantidad' => 'required|numeric|min:0|max:100',
        ];
    }

    // Mensajes de validación personalizados
    protected function messages()
    {
        return [
            'form.cantidad.required' => 'El porcentaje de ISV es obligatorio.',
            'form.cantidad.numeric' => 'El porcentaje debe ser un número válido.',
            'form.cantidad.min' => 'El porcentaje no puede ser menor a 0.',
            'form.cantidad.max' => 'El porcentaje no puede ser mayor a 100.',
        ];
    }

    // Montaje del componente
    public function mount()
    {
        $this->cargarIsvs();
    }

    // Cargar lista de ISVs
    public function cargarIsvs()
    {
        try {
            $this->isvs = IsvModel::orderBy('id', 'asc')->get();
        } catch (Exception $e) {
            $this->mostrarError('Error al cargar los ISVs: ' . $e->getMessage());
        }
    }

    // Abrir modal para crear nuevo ISV
    public function abrirModal()
    {
        $this->resetModal();
        $this->mostrarModal = true;
    }

    // Cambiar estado del ISV (activar/inactivar)
    public function cambiarEstado($id)
    {
        try {
            $isv = IsvModel::findOrFail($id);
            $nuevoEstado = $isv->estado_id == 1 ? 2 : 1;
            $accion = $nuevoEstado == 1 ? 'activado' : 'inactivado';
            
            $isv->update(['estado_id' => $nuevoEstado]);
            
            $this->mostrarExito("ISV {$accion} correctamente");
            $this->cargarIsvs();
            
        } catch (Exception $e) {
            $this->mostrarError('Error al cambiar estado: ' . $e->getMessage());
        }
    }

    // Cerrar modal
    public function cerrarModal()
    {
        $this->resetModal();
    }

    // Resetear modal
    private function resetModal()
    {
        $this->mostrarModal = false;
        $this->form = ['cantidad' => ''];
        $this->resetErrorBag();
    }

    // Guardar ISV (solo crear)
    public function guardar()
    {
        // Verificar que hay datos
        if (empty($this->form['cantidad']) && $this->form['cantidad'] !== '0') {
            $this->mostrarError('El campo cantidad es obligatorio');
            return;
        }

        // Verificar que es numérico
        if (!is_numeric($this->form['cantidad'])) {
            $this->mostrarError('El campo cantidad debe ser un número válido');
            return;
        }

        // Verificar rango
        $cantidad = floatval($this->form['cantidad']);
        if ($cantidad < 0 || $cantidad > 100) {
            $this->mostrarError('El porcentaje debe estar entre 0 y 100');
            return;
        }

        try {
            // Crear nuevo ISV (por defecto activo)
            $nuevoIsv = IsvModel::create([
                'cantidad' => $cantidad,
                'estado_id' => 1
            ]);
            
            $this->mostrarExito('ISV creado correctamente con ID: ' . $nuevoIsv->id);
            $this->cargarIsvs();
            $this->cerrarModal();

        } catch (Exception $e) {
            $this->mostrarError('Error al guardar: ' . $e->getMessage());
        }
    }

    // Mostrar mensaje de éxito
    private function mostrarExito($mensaje)
    {
        $this->mensajeExito = $mensaje;
        $this->mostrarAlertaExito = true;
        $this->mostrarAlertaError = false;
    }

    // Mostrar mensaje de error
    private function mostrarError($mensaje)
    {
        $this->mensajeError = $mensaje;
        $this->mostrarAlertaError = true;
        $this->mostrarAlertaExito = false;
    }

    // Cerrar alerta de éxito
    public function cerrarAlertaExito()
    {
        $this->mostrarAlertaExito = false;
        $this->mensajeExito = '';
    }

    // Cerrar alerta de error
    public function cerrarAlertaError()
    {
        $this->mostrarAlertaError = false;
        $this->mensajeError = '';
    }

    // Obtener clase CSS para campos del formulario
    public function getClaseCampo($campo)
    {
        $errores = $this->getErrorBag();
        if ($errores->has("form.{$campo}")) {
            return 'is-invalid';
        }
        return '';
    }

    // Renderizar vista

    // Renderizar vista
    public function render()
    {
        return view('livewire.inventario.isv');
    }
}
