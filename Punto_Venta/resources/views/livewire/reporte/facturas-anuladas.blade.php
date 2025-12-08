<div class="container-fluid px-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-ban text-danger"></i> Reporte de Facturas Anuladas
            </h1>
        </div>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Facturas Anuladas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalRegistros }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Monto Total Anulado
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">L. {{ number_format($totalMonto, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-gradient-danger">
            <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-filter"></i> Filtros de Búsqueda
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Número de Factura -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">N° Factura</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="filtroNumeroFactura"
                           class="form-control form-control-sm"
                           placeholder="Buscar...">
                </div>

                <!-- Cliente -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Cliente</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="filtroCliente"
                           class="form-control form-control-sm"
                           placeholder="Buscar...">
                </div>

                <!-- Usuario que Anuló -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Usuario que Anuló</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="filtroUsuarioAnulo"
                           class="form-control form-control-sm"
                           placeholder="Buscar...">
                </div>

                <!-- Vendedor -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Vendedor Original</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="filtroVendedor"
                           class="form-control form-control-sm"
                           placeholder="Buscar...">
                </div>
            </div>

            <div class="row">
                <!-- Fecha Anulación Inicio -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Fecha Anulación (Desde)</label>
                    <input type="date" 
                           wire:model.live="filtroFechaAnulacionInicio"
                           class="form-control form-control-sm">
                </div>

                <!-- Fecha Anulación Fin -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Fecha Anulación (Hasta)</label>
                    <input type="date" 
                           wire:model.live="filtroFechaAnulacionFin"
                           class="form-control form-control-sm">
                </div>

                <!-- Fecha Emisión Inicio -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Fecha Emisión (Desde)</label>
                    <input type="date" 
                           wire:model.live="filtroFechaEmisionInicio"
                           class="form-control form-control-sm">
                </div>

                <!-- Fecha Emisión Fin -->
                <div class="col-md-3 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Fecha Emisión (Hasta)</label>
                    <input type="date" 
                           wire:model.live="filtroFechaEmisionFin"
                           class="form-control form-control-sm">
                </div>
            </div>

            <div class="row">
                <!-- Motivo -->
                <div class="col-md-12 mb-3">
                    <label class="text-xs font-weight-bold text-gray-700">Motivo de Anulación</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="filtroMotivo"
                           class="form-control form-control-sm"
                           placeholder="Buscar por motivo...">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-gradient-danger">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-white">
                    <i class="fas fa-table"></i> Listado de Facturas Anuladas
                </h6>
                <button wire:click="exportarExcel" 
                        class="btn btn-success btn-sm"
                        wire:loading.attr="disabled"
                        wire:target="exportarExcel">
                    <span wire:loading.remove wire:target="exportarExcel">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </span>
                    <span wire:loading wire:target="exportarExcel">
                        <i class="fas fa-spinner fa-spin"></i> Generando...
                    </span>
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($facturasAnuladas->count() > 0)
                <div class="table-responsive" style="max-height: calc(100vh - 500px); overflow-y: auto;">
                    <table class="table table-bordered table-hover table-sm text-xs">
                        <thead class="bg-danger text-white sticky-top">
                            <tr>
                                <th class="cursor-pointer" wire:click="ordenar('id')">
                                    ID
                                    @if($ordenarPor === 'id')
                                        <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th class="cursor-pointer" wire:click="ordenar('numero_factura')">
                                    N° Factura
                                    @if($ordenarPor === 'numero_factura')
                                        <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th>Cliente</th>
                                <th class="cursor-pointer" wire:click="ordenar('fecha_emision_factura')">
                                    F. Emisión
                                    @if($ordenarPor === 'fecha_emision_factura')
                                        <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th class="cursor-pointer" wire:click="ordenar('fecha_anulacion')">
                                    F. Anulación
                                    @if($ordenarPor === 'fecha_anulacion')
                                        <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th class="text-right cursor-pointer" wire:click="ordenar('total')">
                                    Total
                                    @if($ordenarPor === 'total')
                                        <i class="fas fa-sort-{{ $direccionOrden === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </th>
                                <th>Vendedor</th>
                                <th>Anuló</th>
                                <th>Motivo</th>
                                <th class="text-center">Detalles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($facturasAnuladas as $anulacion)
                                <tr>
                                    <td class="font-weight-bold">{{ $anulacion->id }}</td>
                                    <td>
                                        <span class="badge badge-danger">{{ $anulacion->numero_factura }}</span>
                                    </td>
                                    <td>
                                        <div>{{ $anulacion->nombre_cliente ?? 'N/A' }}</div>
                                        @if($anulacion->rtn)
                                            <small class="text-muted">RTN: {{ $anulacion->rtn }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ \Carbon\Carbon::parse($anulacion->fecha_emision_factura)->format('d/m/Y') }}
                                    </td>
                                    <td class="text-center">
                                        <div class="font-weight-bold text-danger">
                                            {{ \Carbon\Carbon::parse($anulacion->fecha_anulacion)->format('d/m/Y') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($anulacion->fecha_anulacion)->format('H:i:s') }}
                                        </small>
                                    </td>
                                    <td class="text-right font-weight-bold text-danger">
                                        L. {{ number_format($anulacion->total, 2) }}
                                    </td>
                                    <td>
                                        <i class="fas fa-user-tie"></i> {{ $anulacion->vendedor_nombre }}
                                    </td>
                                    <td>
                                        <i class="fas fa-user-shield"></i> {{ $anulacion->usuario_anulo_nombre }}
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $anulacion->motivo_anulacion }}">
                                            {{ Str::limit($anulacion->motivo_anulacion, 50) }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-info"
                                                data-toggle="modal" 
                                                data-target="#modalDetalle{{ $anulacion->id }}">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal de Detalles -->
                                <div class="modal fade" id="modalDetalle{{ $anulacion->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-info-circle"></i> Detalles de Anulación - Factura {{ $anulacion->numero_factura }}
                                                </h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6 class="border-bottom pb-2"><i class="fas fa-file-invoice"></i> Información de Factura</h6>
                                                        <p><strong>N° Factura:</strong> {{ $anulacion->numero_factura }}</p>
                                                        <p><strong>Cliente:</strong> {{ $anulacion->nombre_cliente ?? 'N/A' }}</p>
                                                        <p><strong>RTN:</strong> {{ $anulacion->rtn ?? 'N/A' }}</p>
                                                        <p><strong>Subtotal:</strong> L. {{ number_format($anulacion->sub_total, 2) }}</p>
                                                        <p><strong>ISV:</strong> L. {{ number_format($anulacion->isv, 2) }}</p>
                                                        <p><strong>Total:</strong> <span class="text-danger font-weight-bold">L. {{ number_format($anulacion->total, 2) }}</span></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6 class="border-bottom pb-2"><i class="fas fa-ban"></i> Información de Anulación</h6>
                                                        <p><strong>Fecha Emisión:</strong> {{ \Carbon\Carbon::parse($anulacion->fecha_emision_factura)->format('d/m/Y') }}</p>
                                                        <p><strong>Fecha Anulación:</strong> {{ \Carbon\Carbon::parse($anulacion->fecha_anulacion)->format('d/m/Y H:i:s') }}</p>
                                                        <p><strong>Vendedor:</strong> {{ $anulacion->vendedor_nombre }}</p>
                                                        <p><strong>Anuló:</strong> {{ $anulacion->usuario_anulo_nombre }}</p>
                                                        <p><strong>Método Devolución:</strong> 
                                                            @switch($anulacion->metodo_devolucion)
                                                                @case('efectivo')
                                                                    <span class="badge badge-success">💵 Efectivo</span>
                                                                    @break
                                                                @case('transferencia')
                                                                    <span class="badge badge-info">🏦 Transferencia</span>
                                                                    @break
                                                                @case('nota_credito')
                                                                    <span class="badge badge-warning">📝 Nota Crédito</span>
                                                                    @break
                                                                @default
                                                                    <span class="badge badge-secondary">❌ No Aplica</span>
                                                            @endswitch
                                                        </p>
                                                        @if($anulacion->impacto_flujo_caja)
                                                            <p><strong>Impacto Flujo:</strong> <span class="text-danger">L. {{ number_format($anulacion->impacto_flujo_caja, 2) }}</span></p>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <h6 class="border-bottom pb-2"><i class="fas fa-comment-alt"></i> Motivo de Anulación</h6>
                                                        <p class="bg-light p-3 rounded">{{ $anulacion->motivo_anulacion }}</p>
                                                    </div>
                                                </div>
                                                @if($anulacion->observaciones)
                                                    <div class="row mt-2">
                                                        <div class="col-12">
                                                            <h6 class="border-bottom pb-2"><i class="fas fa-sticky-note"></i> Observaciones</h6>
                                                            <p class="bg-light p-3 rounded">{{ $anulacion->observaciones }}</p>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-3">
                    {{ $facturasAnuladas->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5>No hay facturas anuladas</h5>
                    <p class="text-muted">No se encontraron registros con los filtros aplicados.</p>
                </div>
            @endif
        </div>
    </div>
</div>
