<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PedidoWeb;
use App\Models\Factura;
use Illuminate\Support\Facades\DB;

class DetallePedido extends Component
{
    public $pedidoId;
    public $pedido;
    public $mostrarModalProcesar = false;
    public $mostrarModalRechazar = false;
    public $modalImpresion = false;
    public $facturaParaImprimir;
    public $caiFacturaImpresa;
    public $productosFacturaImpresa;
    public $pagosFacturaImpresa;
    public $empresaFacturaImpresa;
    public $tiendaFacturaImpresa;
    
    // Propiedades para paginación y filtros de productos
    public $registrosPorPaginaProductos = 10;
    public $paginaProductos = 1;
    public $buscarProducto = '';
    public $filtroNombreProducto = '';

    public function mount($pedidoId = null)
    {
        if (!$pedidoId) {
            session()->flash('error', 'ID de pedido no proporcionado');
            $this->dispatch('cambiarVista', ['BandejaPedidos']);
            return;
        }

        $this->pedidoId = $pedidoId;
        $this->cargarPedido();
    }

    public function cargarPedido()
    {
        $this->pedido = PedidoWeb::with(['items.producto', 'procesadoPor'])
            ->find($this->pedidoId);

        if (!$this->pedido) {
            session()->flash('error', 'Pedido no encontrado');
            $this->dispatch('cambiarVista', ['BandejaPedidos']);
            return;
        }

        // Marcar como leído
        if (!$this->pedido->leido) {
            $this->pedido->marcarComoLeido();
        }
    }

    public function procesarPedido()
    {
        try {
            $this->mostrarModalProcesar = false;
            
            $orderService = app(\App\Services\Api\OrderService::class);
            $factura = $orderService->convertirPedidoAFactura(
                $this->pedidoId,
                auth()->id()
            );

            session()->flash('success', "Pedido procesado exitosamente. Factura ID: {$factura->id}");
            $this->cargarPedido(); // Recargar datos
            
            // Emitir evento para mostrar la impresión de la factura
            $this->dispatch('mostrarImpresionFactura', facturaId: $factura->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Error al procesar pedido: ' . $e->getMessage());
        }
    }

    public function rechazarPedido()
    {
        try {
            $this->mostrarModalRechazar = false;
            
            $this->pedido->rechazar();
            session()->flash('success', 'Pedido rechazado');
            $this->cargarPedido();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al rechazar pedido: ' . $e->getMessage());
        }
    }

    public function imprimirFactura()
    {
        try {
            if ($this->pedido && $this->pedido->factura_id) {
                $this->cargarDatosParaImpresion($this->pedido->factura_id);
                $this->modalImpresion = true;
            } else {
                session()->flash('error', 'No se encontró la factura asociada al pedido.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error al cargar la factura: ' . $e->getMessage());
        }
    }

    private function cargarDatosParaImpresion($facturaId)
    {
        // Cargar la factura
        $this->facturaParaImprimir = Factura::find($facturaId);

        // Cargar información de la empresa (mantener como objeto)
        $this->empresaFacturaImpresa = DB::table('empresa')->first();

        // Cargar información de la tienda (mantener como objeto)
        $this->tiendaFacturaImpresa = DB::table('tienda as t')
            ->leftJoin('direccion as d', 't.direccion_sucursal_id', '=', 'd.id')
            ->select('t.*', 'd.domicilio_tributario')
            ->where('t.id', 1)
            ->first();

        // Cargar información del CAI y CONVERTIR A ARRAY (el template PDF lo usa como array)
        $caiObj = DB::table('cai')
            ->where('id', $this->facturaParaImprimir->cai_id)
            ->first();
        $this->caiFacturaImpresa = $caiObj ? (array) $caiObj : null;

        // Cargar productos y convertir a arrays
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
            ->get()
            ->map(function($producto) {
                return (array) $producto;
            })
            ->toArray();

        // Cargar métodos de pago
        $this->pagosFacturaImpresa = DB::table('factura_has_pago as fp')
            ->join('tipo_pago as tp', 'fp.tipo_pago_id', '=', 'tp.id')
            ->where('fp.factura_id', $facturaId)
            ->select('tp.nombre as metodo', 'fp.pago_recibido')
            ->get()
            ->map(function($pago) {
                return [
                    'metodo' => $pago->metodo,
                    'pago_recibido' => $pago->pago_recibido
                ];
            })
            ->toArray();
    }

    public function cerrarImpresion()
    {
        $this->modalImpresion = false;
        $this->facturaParaImprimir = null;
        $this->caiFacturaImpresa = null;
        $this->productosFacturaImpresa = null;
        $this->pagosFacturaImpresa = null;
        $this->empresaFacturaImpresa = null;
        $this->tiendaFacturaImpresa = null;
    }

    // Métodos de paginación para productos
    public function nextPageProductos()
    {
        $this->paginaProductos++;
    }

    public function previousPageProductos()
    {
        if ($this->paginaProductos > 1) {
            $this->paginaProductos--;
        }
    }

    public function updatedBuscarProducto()
    {
        $this->paginaProductos = 1;
    }

    public function updatedFiltroNombreProducto()
    {
        $this->paginaProductos = 1;
    }

    public function getProductosPaginados()
    {
        if (!$this->pedido || !$this->pedido->items) {
            return [
                'datos' => collect([]),
                'total' => 0,
                'desde' => 0,
                'hasta' => 0,
                'paginaActual' => 1,
                'ultimaPagina' => 1
            ];
        }

        $items = $this->pedido->items;

        // Aplicar filtros
        if ($this->buscarProducto) {
            $busqueda = strtolower($this->buscarProducto);
            $items = $items->filter(function($item) use ($busqueda) {
                $nombreProducto = $item->producto ? strtolower($item->producto->nombre) : '';
                return str_contains($nombreProducto, $busqueda) || 
                       str_contains((string)$item->producto_id, $busqueda);
            });
        }

        if ($this->filtroNombreProducto) {
            $filtro = strtolower($this->filtroNombreProducto);
            $items = $items->filter(function($item) use ($filtro) {
                $nombreProducto = $item->producto ? strtolower($item->producto->nombre) : '';
                return str_contains($nombreProducto, $filtro);
            });
        }

        $total = $items->count();
        $desde = ($this->paginaProductos - 1) * $this->registrosPorPaginaProductos;
        $datos = $items->slice($desde, $this->registrosPorPaginaProductos)->values();
        
        return [
            'datos' => $datos,
            'total' => $total,
            'desde' => $desde + 1,
            'hasta' => min($desde + $this->registrosPorPaginaProductos, $total),
            'paginaActual' => $this->paginaProductos,
            'ultimaPagina' => ceil($total / $this->registrosPorPaginaProductos)
        ];
    }

    public function volverABandeja()
    {
        $this->dispatch('cambiarVista', ['BandejaPedidos']);
    }

    public function render()
    {
        return view('livewire.detalle-pedido');
    }
}
