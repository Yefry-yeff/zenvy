<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class GestionDeDiferencias extends Component
{
    use WithPagination;

    public $tiendaUsuario;
    public $nombreTienda;
    
    // Filtros
    public $filtroCaja = '';
    public $filtroUsuario = '';
    public $filtroTipoCierre = '';
    public $filtroEstado = '';
    public $filtroFechaDesde = '';
    public $filtroFechaHasta = '';
    
    // Paginación
    public $registrosPorPagina = 10;
    
    // Modal de gestión
    public $mostrarModal = false;
    public $diferenciaSeleccionada = null;
    public $historialGestiones = [];
    public $monto = '';
    public $descripcion = '';
    public $mensaje = '';
    public $tipoMensaje = '';
    public $procesoEnCurso = false;
    
    // Modal de éxito
    public $mostrarModalExito = false;
    public $tituloExito = '';
    public $mensajeExito = '';
    public $diferenciaTotalmenteResuelta = false;

    protected $listeners = ['limpiarMensaje' => 'limpiarMensaje'];

    protected $rules = [
        'monto' => 'required|numeric|not_in:0',
        'descripcion' => 'required|string|max:400|min:3',
    ];

    protected $messages = [
        'monto.required' => 'El monto es obligatorio',
        'monto.numeric' => 'El monto debe ser un número válido',
        'monto.not_in' => 'El monto no puede ser cero',
        'descripcion.required' => 'La descripción es obligatoria',
        'descripcion.min' => 'La descripción debe tener al menos 3 caracteres',
        'descripcion.max' => 'La descripción no puede exceder 400 caracteres',
    ];

    public function mount()
    {
        if (!$this->validarJornadaAbierta()) {
            return;
        }
        
        $this->cargarTiendaUsuario();
        $this->cargarDiferencias();
    }

    public function validarJornadaAbierta()
    {
        $usuario = Auth::user();
        
        if (!$usuario->tienda_id) {
            $this->mensaje = 'Usuario sin tienda asignada. No se pueden gestionar diferencias de caja.';
            $this->tipoMensaje = 'error';
            return false;
        }

        $fechaActual = date('Y-m-d');
        
        $jornadaAbierta = DB::table('jornada')
            ->where('fecha', $fechaActual)
            ->where('tienda_id', $usuario->tienda_id)
            ->where('apertura', 1)
            ->where('cierre', 0)
            ->first();

        if (!$jornadaAbierta) {
            $this->mensaje = 'No se pueden gestionar diferencias de caja porque la jornada no está aperturada para hoy. Debe aperturar la jornada primero.';
            $this->tipoMensaje = 'error';
            return false;
        }

        return true;
    }

    public function cargarTiendaUsuario()
    {
        $usuario = DB::table('users as u')
            ->leftJoin('tienda as t', 'u.tienda_id', '=', 't.id')
            ->where('u.id', Auth::id())
            ->select('u.tienda_id', 't.denominacion_social')
            ->first();
        
        if ($usuario) {
            $this->tiendaUsuario = $usuario->tienda_id;
            $this->nombreTienda = $usuario->denominacion_social ?? 'Tienda #' . $usuario->tienda_id;
        }
    }

    public function cargarDiferencias()
    {
        if (!$this->tiendaUsuario) return collect();

        // Obtener todas las cajas con diferencias de la tienda
        $query = DB::table('cierre_de_caja as cc')
            ->join('caja as c', 'cc.caja_id', '=', 'c.id')
            ->join('users as u', 'c.users_id', '=', 'u.id')
            ->leftJoin('gestion_diferencia as gd', 'cc.id', '=', 'gd.cierre_de_caja_id')
            ->where('u.tienda_id', $this->tiendaUsuario)
            ->whereRaw('ABS(cc.diferencia_efectivo) >= 0.01'); // Solo mostrar diferencias que aún tienen saldo pendiente

        // Aplicar filtros
        if (!empty($this->filtroCaja)) {
            $query->where('c.id', 'like', '%' . $this->filtroCaja . '%');
        }

        if (!empty($this->filtroUsuario)) {
            $query->where('u.name', 'like', '%' . $this->filtroUsuario . '%');
        }

        if (!empty($this->filtroTipoCierre)) {
            if ($this->filtroTipoCierre === 'caja') {
                $query->where('cc.tipo_cierre', 1);
            } elseif ($this->filtroTipoCierre === 'jornada') {
                $query->where('cc.tipo_cierre', 2);
            }
        }

        if (!empty($this->filtroEstado)) {
            if ($this->filtroEstado === 'pendiente') {
                $query->whereRaw('ABS(cc.diferencia_efectivo) >= 0.01');
            } elseif ($this->filtroEstado === 'resuelto') {
                $query->whereRaw('ABS(cc.diferencia_efectivo) < 0.01');
            }
        }

        if (!empty($this->filtroFechaDesde)) {
            $query->whereDate('cc.created_at', '>=', $this->filtroFechaDesde);
        }

        if (!empty($this->filtroFechaHasta)) {
            $query->whereDate('cc.created_at', '<=', $this->filtroFechaHasta);
        }

        return $query->select(
                'cc.id as cierre_id',
                'c.id as caja_id',
                'c.users_id',
                'u.name as nombre_usuario',
                'cc.diferencia_efectivo',
                'cc.total_efectivo',
                'cc.conteo_efectivo',
                'cc.created_at',
                'cc.tipo_cierre',
                DB::raw('COALESCE(SUM(gd.monto), 0) as total_gestionado'),
                'cc.diferencia_efectivo as diferencia_pendiente',
                DB::raw('COUNT(gd.id) as gestiones_realizadas')
            )
            ->groupBy('cc.id', 'c.id', 'c.users_id', 'u.name', 'cc.diferencia_efectivo', 'cc.total_efectivo', 'cc.conteo_efectivo', 'cc.created_at', 'cc.tipo_cierre')
            ->orderBy('cc.created_at', 'desc')
            ->paginate($this->registrosPorPagina);
    }

    // Métodos para actualizar filtros
    public function updatedFiltroCaja()
    {
        $this->resetPage();
    }

    public function updatedFiltroUsuario()
    {
        $this->resetPage();
    }

    public function updatedFiltroTipoCierre()
    {
        $this->resetPage();
    }

    public function updatedFiltroEstado()
    {
        $this->resetPage();
    }

    public function updatedFiltroFechaDesde()
    {
        $this->resetPage();
    }

    public function updatedFiltroFechaHasta()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->filtroCaja = '';
        $this->filtroUsuario = '';
        $this->filtroTipoCierre = '';
        $this->filtroEstado = '';
        $this->filtroFechaDesde = '';
        $this->filtroFechaHasta = '';
        $this->resetPage();
    }

    public function abrirModal($cierreId)
    {
        // Buscar la diferencia directamente de la base de datos
        $this->diferenciaSeleccionada = DB::table('cierre_de_caja as cc')
            ->join('caja as c', 'cc.caja_id', '=', 'c.id')
            ->join('users as u', 'c.users_id', '=', 'u.id')
            ->leftJoin('gestion_diferencia as gd', 'cc.id', '=', 'gd.cierre_de_caja_id')
            ->where('cc.id', $cierreId)
            ->select(
                'cc.id as cierre_id',
                'c.id as caja_id',
                'c.users_id',
                'u.name as nombre_usuario',
                'cc.diferencia_efectivo',
                'cc.total_efectivo',
                'cc.conteo_efectivo',
                'cc.created_at',
                'cc.tipo_cierre',
                DB::raw('COALESCE(SUM(gd.monto), 0) as total_gestionado'),
                'cc.diferencia_efectivo as diferencia_pendiente',
                DB::raw('COUNT(gd.id) as gestiones_realizadas')
            )
            ->groupBy('cc.id', 'c.id', 'c.users_id', 'u.name', 'cc.diferencia_efectivo', 'cc.total_efectivo', 'cc.conteo_efectivo', 'cc.created_at', 'cc.tipo_cierre')
            ->first();

        if ($this->diferenciaSeleccionada) {
            // Cargar historial de gestiones para esta diferencia
            $this->cargarHistorialGestiones($cierreId);
            $this->mostrarModal = true;
            $this->resetForm();
        }
    }

    public function cargarHistorialGestiones($cierreId)
    {
        $this->historialGestiones = DB::table('gestion_diferencia as gd')
            ->join('users as u', 'gd.users_id', '=', 'u.id')
            ->where('gd.cierre_de_caja_id', $cierreId)
            ->select(
                'gd.id',
                'gd.monto',
                'gd.descripcion',
                'gd.created_at',
                'u.name as gestor_nombre'
            )
            ->orderBy('gd.created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function cerrarModal()
    {
        $this->mostrarModal = false;
        $this->diferenciaSeleccionada = null;
        $this->historialGestiones = [];
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->monto = '';
        $this->descripcion = '';
        $this->resetValidation();
    }

    public function gestionarDiferencia()
    {
        if ($this->procesoEnCurso) return;

        $this->validate();

        if (!$this->diferenciaSeleccionada) {
            $this->mensaje = 'No se ha seleccionado una diferencia válida';
            $this->tipoMensaje = 'error';
            return;
        }

        $this->procesoEnCurso = true;

        try {
            DB::beginTransaction();

            // 1. Insertar en gestion_diferencia (permitir montos positivos y negativos)
            $gestionId = DB::table('gestion_diferencia')->insertGetId([
                'monto' => $this->monto,
                'descripcion' => $this->descripcion,
                'cierre_de_caja_id' => $this->diferenciaSeleccionada->cierre_id,
                'users_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 2. Simplemente sumar el monto actual a la diferencia actual
            // (La diferencia actual ya incluye gestiones anteriores)
            $nuevaDiferencia = $this->diferenciaSeleccionada->diferencia_efectivo + $this->monto;

            // 3. ACTUALIZAR diferencia_efectivo en cierre_de_caja con la nueva diferencia
            DB::table('cierre_de_caja')
                ->where('id', $this->diferenciaSeleccionada->cierre_id)
                ->update([
                    'diferencia_efectivo' => $nuevaDiferencia,
                    'updated_at' => now()
                ]);

            DB::commit();

            // Verificar si la diferencia quedó completamente resuelta
            $diferenciaPendienteFinal = abs($nuevaDiferencia);
            $this->diferenciaTotalmenteResuelta = $diferenciaPendienteFinal < 0.01;

            // Preparar modal de éxito
            $tipoMovimiento = $this->monto > 0 ? 'ajuste positivo' : 'ajuste negativo';
            $impactoTexto = '';
            
            if ($this->monto > 0) {
                $impactoTexto = $this->diferenciaSeleccionada->diferencia_efectivo > 0 ? 'reduciendo el sobrante' : 'reduciendo el faltante';
            } else {
                $impactoTexto = $this->diferenciaSeleccionada->diferencia_efectivo > 0 ? 'aumentando el sobrante' : 'aumentando el faltante';
            }

            if ($this->diferenciaTotalmenteResuelta) {
                $this->tituloExito = '¡Diferencia Completamente Resuelta!';
                $this->mensajeExito = "La diferencia de la Caja #{$this->diferenciaSeleccionada->caja_id} ha sido completamente gestionada con un {$tipoMovimiento} de L. " . number_format(abs($this->monto), 2) . ". La transacción se ha cerrado exitosamente.";
            } else {
                $this->tituloExito = '¡Gestión Registrada Exitosamente!';
                $this->mensajeExito = "Se registró un {$tipoMovimiento} de L. " . number_format(abs($this->monto), 2) . " en la Caja #{$this->diferenciaSeleccionada->caja_id}, {$impactoTexto}. Nueva diferencia: L. " . number_format(abs($nuevaDiferencia), 2) . ". La transacción permanece abierta.";
            }

            // Si la diferencia no se cerró completamente, actualizar historial en el modal
            if (!$this->diferenciaTotalmenteResuelta) {
                $this->cargarHistorialGestiones($this->diferenciaSeleccionada->cierre_id);
                // Recargar la diferencia seleccionada con los nuevos datos
                $this->abrirModal($this->diferenciaSeleccionada->cierre_id);
            }
            
            $this->cerrarModal();
            $this->mostrarModalExito = true;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensaje = 'Error al gestionar la diferencia: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        } finally {
            $this->procesoEnCurso = false;
        }
    }

    public function cerrarModalExito()
    {
        $this->mostrarModalExito = false;
        $this->tituloExito = '';
        $this->mensajeExito = '';
        $this->diferenciaTotalmenteResuelta = false;
    }

    public function limpiarMensaje()
    {
        $this->mensaje = '';
        $this->tipoMensaje = '';
    }

    public function descargarExcel()
    {
        try {
            // Obtener todos los datos sin paginación para el reporte
            $query = DB::table('cierre_de_caja as cc')
                ->join('caja as c', 'cc.caja_id', '=', 'c.id')
                ->join('users as u', 'c.users_id', '=', 'u.id')
                ->leftJoin('gestion_diferencia as gd', 'cc.id', '=', 'gd.cierre_de_caja_id')
                ->where('u.tienda_id', $this->tiendaUsuario)
                ->whereRaw('ABS(cc.diferencia_efectivo) >= 0.01');

            // Aplicar los mismos filtros
            if (!empty($this->filtroCaja)) {
                $query->where('c.id', 'like', '%' . $this->filtroCaja . '%');
            }
            if (!empty($this->filtroUsuario)) {
                $query->where('u.name', 'like', '%' . $this->filtroUsuario . '%');
            }
            if (!empty($this->filtroTipoCierre)) {
                if ($this->filtroTipoCierre === 'caja') {
                    $query->where('cc.tipo_cierre', 1);
                } elseif ($this->filtroTipoCierre === 'jornada') {
                    $query->where('cc.tipo_cierre', 2);
                }
            }
            if (!empty($this->filtroEstado)) {
                if ($this->filtroEstado === 'pendiente') {
                    $query->whereRaw('ABS(cc.diferencia_efectivo) >= 0.01');
                } elseif ($this->filtroEstado === 'resuelto') {
                    $query->whereRaw('ABS(cc.diferencia_efectivo) < 0.01');
                }
            }
            if (!empty($this->filtroFechaDesde)) {
                $query->whereDate('cc.created_at', '>=', $this->filtroFechaDesde);
            }
            if (!empty($this->filtroFechaHasta)) {
                $query->whereDate('cc.created_at', '<=', $this->filtroFechaHasta);
            }

            $diferencias = $query->select(
                    'cc.id as cierre_id',
                    'c.id as caja_id',
                    'u.name as nombre_usuario',
                    'cc.diferencia_efectivo',
                    'cc.total_efectivo',
                    'cc.conteo_efectivo',
                    'cc.created_at',
                    'cc.tipo_cierre',
                    DB::raw('COALESCE(SUM(gd.monto), 0) as total_gestionado'),
                    DB::raw('COUNT(gd.id) as gestiones_realizadas')
                )
                ->groupBy('cc.id', 'c.id', 'u.name', 'cc.diferencia_efectivo', 'cc.total_efectivo', 'cc.conteo_efectivo', 'cc.created_at', 'cc.tipo_cierre')
                ->orderBy('cc.created_at', 'desc')
                ->get();

            $fechaGeneracion = now()->format('d/m/Y H:i:s');
            $usuarioReporte = Auth::user()->name;
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "gestion_diferencias_{$timestamp}.xlsx";

            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            \Maatwebsite\Excel\Facades\Excel::store(
                new \App\Excel\GestionDiferenciasExport($diferencias, $fechaGeneracion, $usuarioReporte, $this->nombreTienda),
                $filename,
                'temp'
            );

            return $this->redirectRoute('download.file', ['file' => $filename]);
        } catch (\Exception $e) {
            $this->mensaje = 'Error al generar el archivo Excel: ' . $e->getMessage();
            $this->tipoMensaje = 'error';
        }
    }

    public function render()
    {
        $diferencias = $this->cargarDiferencias();
        
        return view('livewire.caja.gestion-de-diferencias', [
            'diferencias' => $diferencias
        ]);
    }
}
