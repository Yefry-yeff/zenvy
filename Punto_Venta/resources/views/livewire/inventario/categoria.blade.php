<div>
    <div class="container mt-4">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold text-primary">Gestión de Categorías</h5>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarCategoria">
                    ➕ Agregar Categoría
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="categoriaTable" class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr class="align-middle text-center">
                                <th style="width: 80px;">ID</th>
                                <th>Nombre</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categorias as $categoria)
                                <tr class="align-middle text-center cursor-pointer" wire:click="selectCategoria({{ $categoria->id }})">
                                    <td class="fw-semibold">{{ $categoria->id }}</td>
                                    <td class="text-start">{{ $categoria->nombre }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">No hay categorías disponibles.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Categoría -->
    <div class="modal fade" id="modalAgregarCategoria" tabindex="-1" aria-labelledby="modalAgregarCategoriaLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalAgregarCategoriaLabel">Agregar Categoría</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form wire:submit.prevent="crearCategoria">
              <div class="mb-3">
                <label for="nuevoNombreCategoria" class="form-label">Nombre</label>
                <input type="text" id="nuevoNombreCategoria" class="form-control" wire:model.lazy="nuevoNombreCategoria">
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

    <!-- Modal Editar Categoría -->
    <div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-labelledby="modalEditarCategoriaLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEditarCategoriaLabel">Editar Categoría</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form wire:submit.prevent="updateCategoria">
              <div class="mb-3">
                <label for="categoriaId" class="form-label">ID</label>
                <input type="text" id="categoriaId" class="form-control" value="{{ $selectedCategoria['id'] ?? '' }}" readonly>
              </div>
              <div class="mb-3">
                <label for="categoriaNombre" class="form-label">Nombre</label>
                <input type="text" id="categoriaNombre" class="form-control" wire:model.lazy="selectedCategoria.nombre">
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
