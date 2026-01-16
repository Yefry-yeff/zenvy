<?php

namespace App\Livewire\SalaDeVentas;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Factura;
use App\Models\FacturaAnulada;
use App\Excel\FacturasExport;
use Maatwebsite\Excel\Facades\Excel;


class ListaDeFacturas extends Component
{
    // Propiedades para modal de anulación
    public $mostrarModalAnular = false;
    public $facturaAAnular = null;
    public $motivoAnulacion = '';
    public $metodoDevolucion = 'efectivo';
    public $observacionesAnulacion = '';
    public $afectarInventario = true;
    public $afectarFlujoCaja = true;

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
    public function updatedFiltroOrigen() { $this->resetPage(); }
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
    public $filtroOrigen = ''; // Filtro para origen web o POS

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
        
        // Filtro por origen en descarga Excel
        if (!empty($this->filtroOrigen)) {
            if ($this->filtroOrigen === 'web') {
                $query->where('origen_web', true);
            } elseif ($this->filtroOrigen === 'pos') {
                $query->where('origen_web', false);
            }
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

    /**
     * Métodos de anulación de facturas
     */
    public function abrirModalAnular($facturaId)
    {
        $this->facturaAAnular = Factura::find($facturaId);
        
        // Validar que la factura no esté ya anulada (estado_factura_id = 2)
        if ($this->facturaAAnular && $this->facturaAAnular->estado_factura_id == 2) {
            session()->flash('error', 'Esta factura ya está anulada.');
            return;
        }

        $this->mostrarModalAnular = true;
        $this->motivoAnulacion = '';
        $this->metodoDevolucion = 'efectivo';
        $this->observacionesAnulacion = '';
        $this->afectarInventario = true;
        $this->afectarFlujoCaja = true;
    }

    public function cerrarModalAnular()
    {
        $this->mostrarModalAnular = false;
        $this->facturaAAnular = null;
        $this->motivoAnulacion = '';
        $this->metodoDevolucion = 'efectivo';
        $this->observacionesAnulacion = '';
        $this->afectarInventario = true;
        $this->afectarFlujoCaja = true;
    }

    public function anularFactura()
    {
        Log::info('Iniciando anulación de factura', [
            'factura_id' => $this->facturaAAnular ? $this->facturaAAnular->id : null,
            'motivo' => $this->motivoAnulacion,
            'metodo' => $this->metodoDevolucion
        ]);

        // Validaciones
        $this->validate([
            'motivoAnulacion' => 'required|min:10|max:500',
            'metodoDevolucion' => 'required|in:efectivo,transferencia,nota_credito,no_aplica'
        ], [
            'motivoAnulacion.required' => 'Debe ingresar el motivo de anulación',
            'motivoAnulacion.min' => 'El motivo debe tener al menos 10 caracteres',
            'motivoAnulacion.max' => 'El motivo no puede exceder 500 caracteres',
            'metodoDevolucion.required' => 'Debe seleccionar el método de devolución'
        ]);

        if (!$this->facturaAAnular) {
            session()->flash('error', 'No se encontró la factura a anular.');
            return;
        }

        DB::beginTransaction();
        try {
            $user = Auth::user();
            
            Log::info('Usuario autenticado', ['user_id' => $user->id, 'user_name' => $user->name]);
            
            // 1. Obtener productos de la factura
            Log::info('Obteniendo productos de la factura', ['factura_id' => $this->facturaAAnular->id]);
            $productosFactura = DB::table('factura_has_producto')
                ->where('factura_id', $this->facturaAAnular->id)
                ->get();

            Log::info('Productos encontrados', ['cantidad' => $productosFactura->count()]);
            $productosDevueltos = [];

            // 2. Devolver al inventario (si aplica)
            if ($this->afectarInventario && $productosFactura->count() > 0) {
                Log::info('Iniciando devolución al inventario');
                foreach ($productosFactura as $producto) {
                    // Verificar si factura_has_producto tiene recibido_bodega_id o precio_venta_id
                    $recibidoBodegaId = null;
                    
                    // Opción 1: Si factura_has_producto tiene recibido_bodega_id directo
                    if (isset($producto->recibido_bodega_id) && $producto->recibido_bodega_id) {
                        $recibidoBodegaId = $producto->recibido_bodega_id;
                        Log::info('Recibido bodega ID encontrado directo en factura_has_producto', [
                            'recibido_bodega_id' => $recibidoBodegaId
                        ]);
                    }
                    // Opción 2: Si tiene precio_venta_id, obtener recibido_bodega_id desde precio_venta
                    elseif (isset($producto->precio_venta_id) && $producto->precio_venta_id) {
                        $precioVenta = DB::table('precio_venta')
                            ->where('id', $producto->precio_venta_id)
                            ->first();
                        
                        if ($precioVenta && isset($precioVenta->recibido_bodega_id)) {
                            $recibidoBodegaId = $precioVenta->recibido_bodega_id;
                            Log::info('Recibido bodega ID encontrado desde precio_venta', [
                                'precio_venta_id' => $producto->precio_venta_id,
                                'recibido_bodega_id' => $recibidoBodegaId
                            ]);
                        }
                    }
                    // Opción 3: Buscar en recibido_bodega el más reciente del producto
                    else {
                        $recibidoBodega = DB::table('recibido_bodega')
                            ->where('producto_id', $producto->producto_id)
                            ->orderBy('id', 'desc')
                            ->first();
                        
                        if ($recibidoBodega) {
                            $recibidoBodegaId = $recibidoBodega->id;
                            Log::info('Recibido bodega ID encontrado por búsqueda directa', [
                                'producto_id' => $producto->producto_id,
                                'recibido_bodega_id' => $recibidoBodegaId
                            ]);
                        }
                    }

                    if ($recibidoBodegaId) {
                        // Incrementar la cantidad_disponible en recibido_bodega
                        DB::table('recibido_bodega')
                            ->where('id', $recibidoBodegaId)
                            ->increment('cantidad_disponible', $producto->cantidad);

                        Log::info('Inventario actualizado', [
                            'recibido_bodega_id' => $recibidoBodegaId,
                            'cantidad_incrementada' => $producto->cantidad
                        ]);

                        // Registrar producto devuelto
                        $productosDevueltos[] = [
                            'producto_id' => $producto->producto_id,
                            'cantidad' => $producto->cantidad,
                            'precio_unidad' => $producto->precio_unidad,
                            'subtotal' => $producto->subtotal,
                            'recibido_bodega_id' => $recibidoBodegaId
                        ];
                    } else {
                        Log::warning('No se pudo encontrar recibido_bodega_id para el producto', [
                            'producto_id' => $producto->producto_id,
                            'producto' => $producto
                        ]);
                    }
                }
            }

            // 3. Afectar flujo de caja (si aplica)
            $impactoFlujoCaja = null;
            if ($this->afectarFlujoCaja && $this->metodoDevolucion !== 'no_aplica') {
                Log::info('Registrando en flujo de caja');
                $impactoFlujoCaja = $this->facturaAAnular->total;
                
                try {
                    // Registrar en flujo de caja como salida (devolución)
                    DB::table('flujo_caja')->insert([
                        'tipo_movimiento' => 'salida',
                        'monto' => $this->facturaAAnular->total,
                        'descripcion' => 'Devolución por anulación de factura ' . $this->facturaAAnular->numero_factura,
                        'metodo_pago' => $this->metodoDevolucion,
                        'factura_id' => $this->facturaAAnular->id,
                        'users_id' => $user->id,
                        'fecha_movimiento' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    Log::info('Flujo de caja actualizado correctamente');
                } catch (\Exception $e) {
                    Log::warning('Error al registrar en flujo_caja (tabla opcional)', [
                        'error' => $e->getMessage()
                    ]);
                    // No detenemos el proceso si flujo_caja falla (es opcional)
                    $impactoFlujoCaja = null;
                }
            }

            // 4. Actualizar estado de la factura a "Anulada" (estado_factura_id = 2)
            Log::info('Actualizando estado de factura a Anulada');
            DB::table('factura')
                ->where('id', $this->facturaAAnular->id)
                ->update([
                    'estado_factura_id' => 2,
                    'updated_at' => now()
                ]);

            // 5. Registrar en tabla facturas_anuladas
            Log::info('Insertando en tabla facturas_anuladas');
            $facturaAnulada = FacturaAnulada::create([
                'factura_id' => $this->facturaAAnular->id,
                'numero_factura' => $this->facturaAAnular->numero_factura,
                'nombre_cliente' => $this->facturaAAnular->nombre_cliente,
                'rtn' => $this->facturaAAnular->rtn,
                'sub_total' => $this->facturaAAnular->sub_total,
                'isv' => $this->facturaAAnular->isv,
                'total' => $this->facturaAAnular->total,
                'fecha_emision_factura' => $this->facturaAAnular->fecha_emision,
                'fecha_anulacion' => now(),
                'motivo_anulacion' => $this->motivoAnulacion,
                'users_id_anulo' => $user->id,
                'users_id_vendedor' => $this->facturaAAnular->users_id,
                'productos_devueltos' => json_encode($productosDevueltos),
                'impacto_flujo_caja' => $impactoFlujoCaja,
                'metodo_devolucion' => $this->metodoDevolucion,
                'observaciones' => $this->observacionesAnulacion
            ]);

            Log::info('Factura anulada registrada', ['factura_anulada_id' => $facturaAnulada->id]);

            // 6. Registrar en bitácora
            Log::info('Registrando en bitácora');
            DB::table('bitacora')->insert([
                'users_id' => $user->id,
                'accion' => 'Anulación de Factura',
                'tablaReferencia' => 'factura',
                'idReferencia' => $this->facturaAAnular->id,
                'datosAnteriores' => json_encode([
                    'estado_factura_id' => 1,
                    'numero_factura' => $this->facturaAAnular->numero_factura,
                    'total' => $this->facturaAAnular->total
                ]),
                'datosNuevos' => json_encode([
                    'estado_factura_id' => 3,
                    'motivo_anulacion' => $this->motivoAnulacion,
                    'factura_anulada_id' => $facturaAnulada->id,
                    'afecto_inventario' => $this->afectarInventario,
                    'afecto_flujo_caja' => $this->afectarFlujoCaja
                ]),
                'created_at' => now()
            ]);

            DB::commit();
            Log::info('Transacción completada exitosamente');

            session()->flash('success', 'Factura anulada exitosamente. ' . 
                ($this->afectarInventario ? 'Inventario actualizado. ' : '') .
                ($this->afectarFlujoCaja && $impactoFlujoCaja ? 'Flujo de caja actualizado.' : ''));
            
            $this->cerrarModalAnular();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERROR AL ANULAR FACTURA', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
                'factura_id' => $this->facturaAAnular ? $this->facturaAAnular->id : null,
                'motivo' => $this->motivoAnulacion,
                'metodo_devolucion' => $this->metodoDevolucion,
                'afectar_inventario' => $this->afectarInventario,
                'afectar_flujo_caja' => $this->afectarFlujoCaja
            ]);
            session()->flash('error', 'Error al anular la factura: ' . $e->getMessage());
        }
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
        
        // Filtro por origen (web o POS)
        if (!empty($this->filtroOrigen)) {
            if ($this->filtroOrigen === 'web') {
                $query->where('origen_web', true);
            } elseif ($this->filtroOrigen === 'pos') {
                $query->where('origen_web', false);
            }
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
