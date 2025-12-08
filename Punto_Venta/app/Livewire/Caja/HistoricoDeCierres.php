<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
        // Verificar si el usuario es Admin o Administrador
        $usuario = Auth::user();
        $rolNombre = $usuario->rol->nombre ?? '';
        
        $this->esAdmin = in_array(strtolower($rolNombre), ['admin', 'administrador']);
        
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

    public function render()
    {
        $query = DB::table('cierre_caja_historico as cch')
            ->join('users as u', 'cch.user_id', '=', 'u.id')
            ->select(
                'cch.*',
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
