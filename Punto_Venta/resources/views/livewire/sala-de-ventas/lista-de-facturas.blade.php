<div class="container-fluid px-4">
    <!-- Encabezado con filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0 text-gray-800">Lista de Facturas</h1>
                <div class="d-flex gap-2">
                    <input type="date" class="form-control" style="width: auto;" 
                           wire:model="fechaDesde" placeholder="Fecha desde">
                    <input type="date" class="form-control" style="width: auto;" 
                           wire:model="fechaHasta" placeholder="Fecha hasta">
                    <button class="btn btn-primary" wire:click="filtrar">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Facturas -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Facturas Registradas</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th class="text-center">No. Factura</th>
                                    <th class="text-center">Cliente</th>
                                    <th class="text-center">RTN</th>
                                    <th class="text-center">Fecha</th>
                                    <th class="text-center">Subtotal</th>
                                    <th class="text-center">ISV</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($facturas as $index => $factura)
                                    <tr>
                                        <td class="text-center">{{ $facturas->firstItem() + $index }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-secondary">{{ $factura->numero_factura ?? 'N/A' }}</span>
                                        </td>
                                        <td>{{ $factura->cliente_nombre ?? 'Cliente General' }}</td>
                                        <td class="text-center">{{ $factura->cliente_rtn ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y H:i A') }}
                                        </td>
                                        <td class="text-right">L. {{ number_format($factura->subtotal, 2) }}</td>
                                        <td class="text-right">L. {{ number_format($factura->isv_total, 2) }}</td>
                                        <td class="text-right font-weight-bold">L. {{ number_format($factura->total, 2) }}</td>
                                        <td class="text-center">
                                            @if($factura->estado_id == 1)
                                                <span class="badge badge-success">Pagada</span>
                                            @elseif($factura->estado_id == 2)
                                                <span class="badge badge-warning">Pendiente</span>
                                            @elseif($factura->estado_id == 3)
                                                <span class="badge badge-danger">Anulada</span>
                                            @else
                                                <span class="badge badge-secondary">Desconocido</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if($factura->estado_id != 3)
                                                <button class="btn btn-sm btn-success" 
                                                        wire:click="imprimirFactura({{ $factura->id }})"
                                                        title="Reimprimir factura">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-secondary" 
                                                        title="Factura anulada" disabled>
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-file-invoice fa-3x mb-3"></i>
                                                <p class="mb-0">No se encontraron facturas en el rango de fechas seleccionado</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    @if($facturas->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span class="text-muted">
                                    Mostrando {{ $facturas->firstItem() }} a {{ $facturas->lastItem() }} 
                                    de {{ $facturas->total() }} registros
                                </span>
                            </div>
                            <div>
                                {{ $facturas->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Mensajes de éxito -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
</div>
