<div>
    
    <div class="container mt-4">
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 fw-bold text-primary">Gestión de Marcas</h5>
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalMarca">
                    ➕ Agregar Marca
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="marcasTable" class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr class="align-middle text-center">
                                <th style="width: 80px;">ID</th>
                                <th>Nombre</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($marcas as $marca)
                                <tr class="align-middle text-center">
                                    <td class="fw-semibold">{{ $marca->id }}</td>
                                    <td class="text-start">{{ $marca->nombre }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">No hay marcas disponibles.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Bootstrap -->
    <div class="modal fade" id="modalMarca" tabindex="-1" aria-labelledby="modalMarcaLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalMarcaLabel">Modal de Marca</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Aquí puedes colocar el contenido que desees para el modal.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

</div>
