<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Factura;
use App\Excel\FacturasExport;
use Maatwebsite\Excel\Facades\Excel;


class ListaDeFacturas extends Component
{
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
    // Métodos para resetear página al cambiar filtros
    public function updatedBuscar() { $this->resetPage(); }
    public function updatedFiltroId() { $this->resetPage(); }
    public function updatedFiltroNumero() { $this->resetPage(); }
    public function updatedFiltroCliente() { $this->resetPage(); }
    public function updatedFiltroRTN() { $this->resetPage(); }
    public function updatedFiltroFecha() { $this->resetPage(); }
    public function updatedFiltroSubtotal() { $this->resetPage(); }
    public function updatedFiltroISV() { $this->resetPage(); }
    public function updatedFiltroTotal() { $this->resetPage(); }
    public function updatedRegistrosPorPagina() { $this->resetPage(); }

    // Métodos de paginación manual (opcional, igual que Producto)
    public function getPage() { return $this->page; }
    public function setPage($page) { $this->page = $page; }
    public function resetPage() { $this->page = 1; }
    public function nextPage() { $this->page++; }
    public function previousPage() { if ($this->page > 1) { $this->page--; } }
    public function gotoPage($page) { $this->page = $page; }
    // Ordenamiento de columnas
    public $ordenarPor = 'id';
    public $direccionOrden = 'desc';
    // Búsqueda global
    public $buscar = '';

    // Filtros por columna
    public $filtroId = '';
    public $filtroNumero = '';
    public $filtroCliente = '';
    public $filtroRTN = '';
    public $filtroFecha = '';
    public $filtroSubtotal = '';
    public $filtroISV = '';
    public $filtroTotal = '';

    // Paginación
    public $registrosPorPagina = 10;
    public $page = 1;

    public function descargarExcel()
    {
        // Replicar la lógica de filtros y paginación actual
        $user = Auth::user();
        $esAdmin = false;
        if ($user && $user->roles_id) {
            $esAdmin = DB::table('roles')
                ->where('id', $user->roles_id)
                ->whereIn('txt_nombre', ['Admin', 'Administrador', 'admin', 'administrador'])
                ->exists();
        }

        $query = Factura::query();
        if (!$esAdmin) {
            if ($user) {
                $query->where('users_id', $user->id);
            } else {
                $query->where('id', 0);
            }
        }
        if (!empty($this->buscar)) {
            $query->where(function($q) {
                $q->where('nombre_cliente', 'like', '%' . $this->buscar . '%')
                  ->orWhere('numero_factura', 'like', '%' . $this->buscar . '%')
                  ->orWhere('rtn', 'like', '%' . $this->buscar . '%');
            });
        }
        if (!empty($this->filtroId)) {
            $query->where('id', $this->filtroId);
        }
        if (!empty($this->filtroNumero)) {
            $query->where('numero_factura', 'like', '%' . $this->filtroNumero . '%');
        }
        if (!empty($this->filtroCliente)) {
            $query->where('nombre_cliente', 'like', '%' . $this->filtroCliente . '%');
        }
        if (!empty($this->filtroRTN)) {
            $query->where('rtn', 'like', '%' . $this->filtroRTN . '%');
        }
        if (!empty($this->filtroFecha)) {
            $query->whereDate('fecha_emision', $this->filtroFecha);
        }
        if (!empty($this->filtroSubtotal)) {
            $query->where('sub_total', 'like', '%' . $this->filtroSubtotal . '%');
        }
        if (!empty($this->filtroISV)) {
            $query->where('isv', 'like', '%' . $this->filtroISV . '%');
        }
        if (!empty($this->filtroTotal)) {
            $query->where('total', 'like', '%' . $this->filtroTotal . '%');
        }
        $query->orderBy($this->ordenarPor, $this->direccionOrden);

        // Obtener solo la página actual
        $facturas = $query->paginate($this->registrosPorPagina, ['*'], 'page', $this->page);
        $facturasArray = $facturas->items();

        // Metadatos
        $fechaGeneracion = now()->format('d/m/Y H:i:s');
        $totalFacturas = $facturas->total();
        $usuarioReporte = $user ? $user->name : 'Invitado';
        $filtrosAplicados = $this->obtenerFiltrosAplicados();

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "facturas_{$timestamp}.xlsx";

        return Excel::download(
            new \App\Excel\FacturasExport($facturasArray, $fechaGeneracion, $totalFacturas, $filtrosAplicados, $usuarioReporte),
            $filename
        );
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
        if (!empty($this->filtroNumero)) {
            $filtros[] = "No. Factura: '{$this->filtroNumero}'";
        }
        if (!empty($this->filtroCliente)) {
            $filtros[] = "Cliente: '{$this->filtroCliente}'";
        }
        if (!empty($this->filtroRTN)) {
            $filtros[] = "RTN: '{$this->filtroRTN}'";
        }
        if (!empty($this->filtroFecha)) {
            $filtros[] = "Fecha: '{$this->filtroFecha}'";
        }
        if (!empty($this->filtroSubtotal)) {
            $filtros[] = "Subtotal: '{$this->filtroSubtotal}'";
        }
        if (!empty($this->filtroISV)) {
            $filtros[] = "ISV: '{$this->filtroISV}'";
        }
        if (!empty($this->filtroTotal)) {
            $filtros[] = "Total: '{$this->filtroTotal}'";
        }
        return empty($filtros) ? 'Ninguno' : implode(', ', $filtros);
    }
    public $facturaParaImprimir = null;
    public $productosFacturaImpresa = [];
    public $pagosFacturaImpresa = [];
    public $caiFacturaImpresa = null;
    public $facturaDetalle = null;

    public function verDetalle($facturaId)
    {
        $this->facturaDetalle = Factura::find($facturaId);
    }

    public function cerrarDetalle()
    {
        $this->facturaDetalle = null;
    }

    public function generarPDF($facturaId)
    {
        return redirect()->route('factura.pdf', $facturaId);
    }

    private function cargarDatosParaImpresion($facturaId)
    {
        // Cargar la factura
        $this->facturaParaImprimir = Factura::find($facturaId);

        // Cargar información del CAI asociado a la factura
        $this->caiFacturaImpresa = DB::table('cai')
            ->where('id', $this->facturaParaImprimir->cai_id)
            ->first();

        // Cargar productos
        $this->productosFacturaImpresa = DB::table('factura_has_producto as fp')
            ->join('producto as p', 'fp.producto_id', '=', 'p.id')
            ->where('fp.factura_id', $facturaId)
            ->select(
                'p.nombre',
                'p.codigo_barra',
                'fp.cantidad',
                'fp.precio_unidad',
                'fp.subtotal',
                'fp.descuento',
                'fp.isv_aplicado',
                'fp.isv',
                'fp.total'
            )
            ->get();

        // Cargar métodos de pago
        $this->pagosFacturaImpresa = DB::table('factura_has_pago as fp')
            ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
            ->where('fp.factura_id', $facturaId)
            ->select('tp.nombre as metodo', 'fp.pago_recibido')
            ->get();
    }

    public function cerrarImpresion()
    {
        $this->facturaParaImprimir = null;
        $this->productosFacturaImpresa = [];
        $this->pagosFacturaImpresa = [];
        $this->caiFacturaImpresa = null;
    }

    public function render()
    {
        $user = Auth::user();
        $esAdmin = false;
        if ($user && $user->roles_id) {
            $esAdmin = DB::table('roles')
                ->where('id', $user->roles_id)
                ->whereIn('txt_nombre', ['Admin', 'Administrador', 'admin', 'administrador'])
                ->exists();
        }

        $query = Factura::query();

        // Filtro por usuario (no admin)
        if (!$esAdmin) {
            if ($user) {
                $query->where('users_id', $user->id);
            } else {
                $query->where('id', 0); // No mostrar nada
            }
        }

        // Filtro búsqueda global
        if (!empty($this->buscar)) {
            $query->where(function($q) {
                $q->where('nombre_cliente', 'like', '%' . $this->buscar . '%')
                  ->orWhere('numero_factura', 'like', '%' . $this->buscar . '%')
                  ->orWhere('rtn', 'like', '%' . $this->buscar . '%');
            });
        }

        // Filtros por columna
        if (!empty($this->filtroId)) {
            $query->where('id', $this->filtroId);
        }
        if (!empty($this->filtroNumero)) {
            $query->where('numero_factura', 'like', '%' . $this->filtroNumero . '%');
        }
        if (!empty($this->filtroCliente)) {
            $query->where('nombre_cliente', 'like', '%' . $this->filtroCliente . '%');
        }
        if (!empty($this->filtroRTN)) {
            $query->where('rtn', 'like', '%' . $this->filtroRTN . '%');
        }
        if (!empty($this->filtroFecha)) {
            $query->whereDate('fecha_emision', $this->filtroFecha);
        }
        if (!empty($this->filtroSubtotal)) {
            $query->where('sub_total', 'like', '%' . $this->filtroSubtotal . '%');
        }
        if (!empty($this->filtroISV)) {
            $query->where('isv', 'like', '%' . $this->filtroISV . '%');
        }
        if (!empty($this->filtroTotal)) {
            $query->where('total', 'like', '%' . $this->filtroTotal . '%');
        }

        // Ordenamiento
        $query->orderBy($this->ordenarPor, $this->direccionOrden);

        // Paginación
        $facturas = $query->paginate($this->registrosPorPagina, ['*'], 'page', $this->page);

        return view('livewire.sala-de-ventas.lista-de-facturas', [
            'facturas' => $facturas,
            'esAdmin' => $esAdmin
        ]);
    }
}
