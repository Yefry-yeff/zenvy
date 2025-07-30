<div>
    <div class="container mt-4">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold text-primary">Gestión de Productos</h5>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarProducto">
                    ➕ Agregar Producto
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="productoTable" class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr class="align-middle text-center">
                                <th style="width: 80px;">ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productos as $producto)
                                <tr class="align-middle text-center cursor-pointer" wire:click="selectProducto({{ $producto->id }})">
                                    <td class="fw-semibold">{{ $producto->id }}</td>
                                    <td class="text-start">{{ $producto->nombre }}</td>
                                    <td class="text-start">{{ $producto->categoria }}</td>
                                    <td class="text-end">${{ number_format($producto->precio, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No hay productos disponibles.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Producto -->
    <div class="modal fade" id="modalAgregarProducto" tabindex="-1" aria-labelledby="modalAgregarProductoLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalAgregarProductoLabel">Agregar Producto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form wire:submit.prevent="crearProducto">
              <div class="mb-3">
                <label for="nuevoNombreProducto" class="form-label">Nombre</label>
                <input type="text" id="nuevoNombreProducto" class="form-control" wire:model.lazy="nuevoNombreProducto">
              </div>
              <div class="mb-3">
                <label for="nuevaCategoria" class="form-label">Categoría</label>
                <input type="text" id="nuevaCategoria" class="form-control" wire:model.lazy="nuevaCategoria">
              </div>
              <div class="mb-3">
                <label for="nuevoPrecio" class="form-label">Precio</label>
                <input type="number" id="nuevoPrecio" class="form-control" wire:model.lazy="nuevoPrecio" step="0.01">
              </div>
              <button type="submit" class="btn btn-success">Agregar</button>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Editar Producto -->
    <div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-labelledby="modalEditarProductoLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEditarProductoLabel">Editar Producto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form wire:submit.prevent="updateProducto">
              <div class="mb-3">
                <label for="productoId" class="form-label">ID</label>
                <input type="text" id="productoId" class="form-control" value="{{ $selectedProducto['id'] ?? '' }}" readonly>
              </div>
              <div class="mb-3">
                <label for="productoNombre" class="form-label">Nombre</label>
                <input type="text" id="productoNombre" class="form-control" wire:model.lazy="selectedProducto.nombre">
              </div>
              <div class="mb-3">
                <label for="productoCategoria" class="form-label">Categoría</label>
                <input type="text" id="productoCategoria" class="form-control" wire:model.lazy="selectedProducto.categoria">
              </div>
              <div class="mb-3">
                <label for="productoPrecio" class="form-label">Precio</label>
                <input type="number" id="productoPrecio" class="form-control" wire:model.lazy="selectedProducto.precio" step="0.01">
              </div>
              <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
</div>
