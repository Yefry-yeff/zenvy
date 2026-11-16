<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Cliente;
use App\Models\TipoPersona;
use App\Models\TipoCliente;
use App\Exports\ClientesExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class Clientes extends Component
{
    use WithPagination;

    // Variables para filtros y búsqueda
    public $buscar = '';
    public $filtroId = '';
    public $filtroNombre = '';
    public $filtroIdentidad = '';
    public $filtroTelefono = '';
    public $filtroCorreo = '';
    public $filtroTipo = '';
    public $filtroTipoPersona = '';
    public $filtroEstado = '';

    // Variables para paginación y ordenamiento
    public $page = 1;
    public $registrosPorPagina = 10;
    public $ordenarPor = 'created_at';
    public $direccionOrden = 'desc';

    // Propiedades para modales
    public $mostrarModalExito = false;
    public $mostrarModalError = false;
    public $mensajeModalExito = '';
    public $mensajeModalError = '';

    // Propiedades para alertas de validación
    public $mostrarAlerta = false;
    public $mensajeAlerta = '';
    public $campoConError = '';

    // Métodos de actualización de filtros
    public function updatedBuscar() { $this->resetPage(); }
    public function updatedFiltroId() { $this->resetPage(); }
    public function updatedFiltroNombre() { $this->resetPage(); }
    public function updatedFiltroIdentidad() { $this->resetPage(); }
    public function updatedFiltroTelefono() { $this->resetPage(); }
    public function updatedFiltroCorreo() { $this->resetPage(); }
    public function updatedFiltroTipo() { $this->resetPage(); }
    public function updatedFiltroTipoPersona() { $this->resetPage(); }
    public function updatedFiltroEstado() { $this->resetPage(); }
    public function updatedRegistrosPorPagina() { $this->resetPage(); }

    // Métodos de paginación manual
    public function getPage() { return $this->page; }
    public function setPage($page) { $this->page = $page; }
    public function resetPage() { $this->page = 1; }
    public function nextPage() { $this->page++; }
    public function previousPage() { if ($this->page > 1) { $this->page--; } }
    public function gotoPage($page) { $this->page = $page; }

    /**
     * Cambia el campo y dirección de ordenamiento
     */
    public function ordenar($campo)
    {
        if ($this->ordenarPor === $campo) {
            $this->direccionOrden = $this->direccionOrden === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenarPor = $campo;
            $this->direccionOrden = 'asc';
        }
        $this->resetPage();
    }

    private function obtenerFiltrosAplicados()
    {
        $filtros = [];
        if (!empty($this->buscar)) {
            $filtros[] = "Búsqueda: '{$this->buscar}'";
        }
        if (!empty($this->filtroId)) {
            $filtros[] = "ID: '{$this->filtroId}'";
        }
        if (!empty($this->filtroNombre)) {
            $filtros[] = "Nombre: '{$this->filtroNombre}'";
        }
        if (!empty($this->filtroIdentidad)) {
            $filtros[] = "Identidad: '{$this->filtroIdentidad}'";
        }
        if (!empty($this->filtroRTN)) {
            $filtros[] = "RTN: '{$this->filtroRTN}'";
        }
        if (!empty($this->filtroTelefono)) {
            $filtros[] = "Teléfono: '{$this->filtroTelefono}'";
        }
        if (!empty($this->filtroCorreo)) {
            $filtros[] = "Correo: '{$this->filtroCorreo}'";
        }
        if (!empty($this->filtroTipo)) {
            $filtros[] = "Tipo: '{$this->filtroTipo}'";
        }
        if (!empty($this->filtroEstado)) {
            $filtros[] = "Estado: '{$this->filtroEstado}'";
        }
        return empty($filtros) ? 'Ninguno' : implode(', ', $filtros);
    }

    public function render()
    {
        $query = Cliente::with(['tipoPersona', 'tipoCliente', 'direccion.municipio.departamento', 'estado']);

        // Búsqueda global
        if (!empty($this->buscar)) {
            $query->where(function($q) {
                $q->where('nombre', 'like', '%' . $this->buscar . '%')
                  ->orWhere('identidad', 'like', '%' . $this->buscar . '%')
                  ->orWhere('correo', 'like', '%' . $this->buscar . '%')
                  ->orWhere('telefono', 'like', '%' . $this->buscar . '%');
            });
        }

        // Filtros por columna
        if (!empty($this->filtroId)) {
            $query->where('id', $this->filtroId);
        }
        if (!empty($this->filtroNombre)) {
            $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
        }
        if (!empty($this->filtroIdentidad)) {
            $query->where('identidad', 'like', '%' . $this->filtroIdentidad . '%');
        }

        if (!empty($this->filtroTelefono)) {
            $query->where('telefono', 'like', '%' . $this->filtroTelefono . '%');
        }
        if (!empty($this->filtroCorreo)) {
            $query->where('correo', 'like', '%' . $this->filtroCorreo . '%');
        }
        if (!empty($this->filtroTipoPersona)) {
            $query->whereHas('tipoPersona', function($q) {
                $q->where('nombre', 'like', '%' . $this->filtroTipoPersona . '%');
            });
        }
        if (!empty($this->filtroTipo)) {
            $query->whereHas('tipoCliente', function($q) {
                $q->where('nombre', 'like', '%' . $this->filtroTipo . '%');
            });
        }
        if (!empty($this->filtroEstado)) {
            $query->whereHas('estado', function($q) {
                $q->where('nombre', 'like', '%' . $this->filtroEstado . '%');
            });
        }

        // Aplicar ordenamiento
        $query->orderBy($this->ordenarPor, $this->direccionOrden);

        // Paginar resultados
        $clientes = $query->paginate($this->registrosPorPagina, ['*'], 'page', $this->page);

        return view('livewire.sala-de-ventas.clientes', [
            'clientes' => $clientes,
            'filtrosAplicados' => $this->obtenerFiltrosAplicados()
        ]);
    }

    // ===== MÉTODOS DE NAVEGACIÓN =====

    public function crearNuevoCliente()
    {
        $this->dispatch('cambiarVista', ruta: 'SalaDeVentas.ClienteForm');
    }

    public function editarCliente($clienteId)
    {
        $this->dispatch('cambiarVista', ruta: 'SalaDeVentas.ClienteForm', parametros: [
            'clienteId' => $clienteId
        ]);
    }

    // ===== MÉTODOS DE GESTIÓN DE MODALES =====

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
    }

    public function cerrarModalError()
    {
        $this->mostrarModalError = false;
        $this->mensajeModalError = '';
    }

    // ===== MÉTODOS DE GESTIÓN DE ALERTAS =====

    public function mostrarAlerta($mensaje, $campo = '')
    {
        $this->mensajeAlerta = $mensaje;
        $this->campoConError = $campo;
        $this->mostrarAlerta = true;
    }

    public function cerrarAlerta()
    {
        $this->mostrarAlerta = false;
        $this->mensajeAlerta = '';
        $this->campoConError = '';
    }

    /**
     * Limpia todos los filtros y la búsqueda
     */
    public function limpiarFiltros()
    {
        $this->buscar = '';
        $this->filtroId = '';
        $this->filtroNombre = '';
        $this->filtroIdentidad = '';
        $this->filtroTelefono = '';
        $this->filtroCorreo = '';
        $this->filtroTipo = '';
        $this->filtroEstado = '';
        $this->resetPage();
    }

    /**
     * Exporta los datos filtrados a Excel
     */
    public function exportarExcel()
    {
        try {
            // Construir la consulta con los mismos filtros que el render
            $query = Cliente::with(['tipoPersona', 'tipoCliente', 'direccion.municipio.departamento', 'estado']);

            // Aplicar los mismos filtros que en render()
            if (!empty($this->buscar)) {
                $query->where(function($q) {
                    $q->where('nombre', 'like', '%' . $this->buscar . '%')
                      ->orWhere('identidad', 'like', '%' . $this->buscar . '%')
                      ->orWhere('rtn', 'like', '%' . $this->buscar . '%')
                      ->orWhere('correo', 'like', '%' . $this->buscar . '%')
                      ->orWhere('telefono', 'like', '%' . $this->buscar . '%');
                });
            }

            // Filtros individuales
            if (!empty($this->filtroId)) {
                $query->where('id', $this->filtroId);
            }
            if (!empty($this->filtroNombre)) {
                $query->where('nombre', 'like', '%' . $this->filtroNombre . '%');
            }
            if (!empty($this->filtroIdentidad)) {
                $query->where('identidad', 'like', '%' . $this->filtroIdentidad . '%');
            }
            if (!empty($this->filtroRTN)) {
                $query->where('rtn', 'like', '%' . $this->filtroRTN . '%');
            }
            if (!empty($this->filtroTelefono)) {
                $query->where('telefono', 'like', '%' . $this->filtroTelefono . '%');
            }
            if (!empty($this->filtroCorreo)) {
                $query->where('correo', 'like', '%' . $this->filtroCorreo . '%');
            }
            if (!empty($this->filtroTipo)) {
                $query->whereHas('tipoCliente', function($q) {
                    $q->where('nombre', 'like', '%' . $this->filtroTipo . '%');
                });
            }
            if (!empty($this->filtroEstado)) {
                $query->whereHas('estado', function($q) {
                    $q->where('nombre', 'like', '%' . $this->filtroEstado . '%');
                });
            }

            // Ordenamiento
            $query->orderBy($this->ordenarPor, $this->direccionOrden);

            // Generar el nombre del archivo
            $fecha = now()->format('d-m-Y_H-i-s');
            $nombreArchivo = "Reporte_Actores_{$fecha}.xlsx";

            // Retornar el archivo Excel
            return Excel::download(
                new ClientesExport($query, $this->obtenerFiltrosAplicados()),
                $nombreArchivo
            );

        } catch (\Exception $e) {
            Log::error('Error al exportar actores a Excel: ' . $e->getMessage());
            $this->mostrarError('Ocurrió un error al exportar los datos. Por favor, inténtelo de nuevo.');
        }
    }
}
