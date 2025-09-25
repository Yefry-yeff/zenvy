<?php

namespace App\Livewire;

use Livewire\Component;

class DynamicContent extends Component
{
    public $vista;
    public $componenteId = null;
    public $parametros = [];

    public function mount()
    {
        $this->vista = 'DashboardDinamico';
        $this->componenteId = uniqid();
        
        // Ya no hay parámetros URL a limpiar - se maneja todo con sesión
    }

    protected $listeners = ['cambiarVista'];

    public function hydrate()
    {
        // Ya no hay parámetros URL a verificar - todo se maneja con sesión
    }

    public function cambiarVista($ruta, $parametros = [])
    {
        logger()->info('[Livewire] cambiarVista recibió:', ['ruta' => $ruta, 'parametros' => $parametros]);

        // Limpiar sesiones de filtros al cambiar de vista para resetear filtros
        $this->limpiarSesionesFiltros();

        // Si la ruta es 'dashboard', usar el componente DashboardDinamico
        if ($ruta === 'dashboard') {
            $this->vista = 'DashboardDinamico';
        } else {
            $this->vista = $ruta;
        }

        $this->parametros = $parametros;
        $this->componenteId = uniqid();
        
        // Guardar la vista actual en sesión para referencia
        session(['vista_actual' => $ruta]);
    }
    
    /**
     * Limpiar sesiones de filtros al cambiar de vista
     */
    private function limpiarSesionesFiltros()
    {
        session()->forget([
            'compras_filtros',
            'productos_filtros', 
            'categorias_filtros',
            'compras_ordenamiento',
            'lista_productos_ordenamiento'
        ]);
    }
    
    public function render()
    {
        return view('livewire.dynamic-content');
    }
}

