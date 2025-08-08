<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice"></i> Facturación
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Alertas -->
                    @if (session()->has('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Información de bodega -->
                    @if($bodegaPrincipal)
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-warehouse"></i> 
                            Bodega Principal: <strong>{{ $bodegaPrincipal->nombre }}</strong>
                        </div>
                    @else
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle"></i> 
                            No se encontró bodega principal para su tienda
                        </div>
                    @endif

                    <div class="row">
                        <!-- Panel de productos -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Buscar Productos</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Búsqueda de productos -->
                                    <div class="mb-3">
                                        <label class="form-label">Buscar por nombre o código</label>
                                        <input type="text" 
                                               class="form-control" 
                                               wire:model.live="busquedaProducto"
                                               placeholder="Escriba para buscar productos...">
                                    </div>

                                    <!-- Lista de productos encontrados -->
                                    @if(!empty($productos))
                                        <div class="list-group">
                                            @foreach($productos as $producto)
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">{{ $producto->nombre }}</h6>
                                                        <small class="text-muted">{{ $producto->codigo_barra }}</small>
                                                        <br>
                                                        <span class="badge bg-success">L. {{ number_format($producto->precio_base, 2) }}</span>
                                                    </div>
                                                    <button class="btn btn-sm btn-primary" 
                                                            wire:click="agregarProducto({{ $producto->id }})">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Panel de factura -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Factura</h6>
                                    @if(!empty($productosFactura))
                                        <button class="btn btn-sm btn-outline-secondary" 
                                                wire:click="limpiarFormulario">
                                            <i class="fas fa-trash"></i> Limpiar
                                        </button>
                                    @endif
                                </div>
                                <div class="card-body">
                                    <!-- Datos del cliente -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nombre del Cliente</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   wire:model="nombreCliente"
                                                   placeholder="Nombre completo">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">RTN/Identidad</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   wire:model="rtnCliente"
                                                   placeholder="RTN o número de identidad">
                                        </div>
                                    </div>

                                    <!-- Productos de la factura -->
                                    @if(!empty($productosFactura))
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th>Cant.</th>
                                                        <th>Precio</th>
                                                        <th>Total</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($productosFactura as $item)
                                                        <tr class="{{ isset($alertasStock[$item['id']]) ? 'table-warning' : '' }}">
                                                            <td>
                                                                <div>
                                                                    <small class="fw-bold">{{ $item['nombre'] }}</small>
                                                                    <br>
                                                                    <small class="text-muted">{{ $item['codigo_barra'] }}</small>
                                                                    @if(isset($alertasStock[$item['id']]))
                                                                        <br>
                                                                        <small class="text-danger">
                                                                            <i class="fas fa-exclamation-triangle"></i>
                                                                            {{ $alertasStock[$item['id']] }}
                                                                        </small>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <input type="number" 
                                                                       class="form-control form-control-sm" 
                                                                       style="width: 70px;"
                                                                       min="1"
                                                                       value="{{ $item['cantidad'] }}"
                                                                       wire:change="actualizarCantidad({{ $item['id'] }}, $event.target.value)">
                                                            </td>
                                                            <td>L. {{ number_format($item['precio_base'], 2) }}</td>
                                                            <td>L. {{ number_format($item['total'], 2) }}</td>
                                                            <td>
                                                                <button class="btn btn-sm btn-outline-danger" 
                                                                        wire:click="eliminarProducto({{ $item['id'] }})">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Totales -->
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <strong>Subtotal:</strong>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        L. {{ number_format($subTotal, 2) }}
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <strong>ISV (15%):</strong>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        L. {{ number_format($isv, 2) }}
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <h5 class="text-primary">Total:</h5>
                                                    </div>
                                                    <div class="col-6 text-end">
                                                        <h5 class="text-primary">L. {{ number_format($total, 2) }}</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Botón de procesar -->
                                        <div class="mt-3 d-grid">
                                            <button class="btn btn-success btn-lg" 
                                                    wire:click="procesarFactura"
                                                    {{ $hayErroresStock() ? 'disabled' : '' }}>
                                                <i class="fas fa-check"></i> 
                                                {{ $hayErroresStock() ? 'Corregir errores de stock' : 'Procesar Factura' }}
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                            <p>No hay productos en la factura</p>
                                            <small>Busque y agregue productos para comenzar</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
