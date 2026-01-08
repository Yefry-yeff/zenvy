<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HistoricoDeCierres extends Component
{
    use WithPagination;

    public $fechaInicio;
    public $fechaFin;
    public $usuarioId;
    public $esAdmin = false;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        // Verificar si el usuario es Admin (roles_id = 2 según la BD)
        $usuario = Auth::user();
        
        // Verificar por roles_id o por nombre de rol
        $this->esAdmin = false;
        
        if ($usuario->roles_id == 2) {
            $this->esAdmin = true;
        } else {
            // Intentar verificar por nombre de rol si existe la relación
            try {
                $rolNombre = DB::table('roles')->where('id', $usuario->roles_id)->value('txt_nombre');
                $this->esAdmin = in_array(strtolower($rolNombre ?? ''), ['admin', 'administrador']);
            } catch (\Exception $e) {
                $this->esAdmin = false;
            }
        }
        
        // Si no es admin, solo mostrar sus propios cierres
        if (!$this->esAdmin) {
            $this->usuarioId = $usuario->id;
        }

        // Establecer fechas por defecto (últimos 30 días)
        $this->fechaFin = now()->format('Y-m-d');
        $this->fechaInicio = now()->subDays(30)->format('Y-m-d');
    }

    public function filtrar()
    {
        $this->resetPage();
    }

    public function limpiarFiltros()
    {
        $this->fechaInicio = now()->subDays(30)->format('Y-m-d');
        $this->fechaFin = now()->format('Y-m-d');
        
        if ($this->esAdmin) {
            $this->usuarioId = null;
        }
        
        $this->resetPage();
    }

    public function descargarPDF($cierreId)
    {
        return redirect()->route('cierre-caja.pdf.preview', $cierreId);
    }

    public function descargarExcel($cierreId)
    {
        return redirect()->route('cierre-caja.reporte-transacciones', $cierreId);
    }

    public function generarReporteConsolidado()
    {
        if (!$this->esAdmin) {
            session()->flash('error', 'No tienes permisos para generar el reporte consolidado');
            return;
        }

        // Validar que se haya seleccionado una fecha
        if (!$this->fechaInicio || !$this->fechaFin) {
            session()->flash('error', 'Debes seleccionar un rango de fechas para generar el reporte');
            return;
        }

        // Redirigir al controlador que generará el PDF
        return redirect()->route('cierre-caja.reporte-consolidado', [
            'fechaInicio' => $this->fechaInicio,
            'fechaFin' => $this->fechaFin,
            'usuarioId' => $this->usuarioId
        ]);
    }

    public function render()
    {
        $query = DB::table('cierre_caja_historico as cch')
            ->leftJoin('users as u', 'cch.user_id', '=', 'u.id')
            ->select(
                'cch.id',
                'cch.user_id',
                'cch.fecha_cierre',
                'cch.periodo_inicio',
                'cch.total_efectivo_sistema',
                'cch.total_tarjeta',
                'cch.total_transferencia',
                'cch.total_cheque',
                'u.name as nombre_usuario'
            );

        // Filtrar por usuario si no es admin o si seleccionó un usuario específico
        if ($this->usuarioId) {
            $query->where('cch.user_id', $this->usuarioId);
        }

        // Filtrar por rango de fechas
        if ($this->fechaInicio && $this->fechaFin) {
            $query->whereBetween('cch.fecha_cierre', [$this->fechaInicio . ' 00:00:00', $this->fechaFin . ' 23:59:59']);
        }

        $cierres = $query->orderBy('cch.fecha_cierre', 'desc')
            ->paginate(15);

        // Obtener lista de usuarios para el filtro (solo si es admin)
        $usuarios = [];
        if ($this->esAdmin) {
            $usuarios = DB::table('users')
                ->select('id', 'name')
                ->where('estado_id', 1)
                ->orderBy('name')
                ->get();
        }

        return view('livewire.caja.historico-de-cierres', [
            'cierres' => $cierres,
            'usuarios' => $usuarios
        ]);
    }
}
