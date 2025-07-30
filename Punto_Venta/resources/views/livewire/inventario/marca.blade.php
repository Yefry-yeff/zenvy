<div>
    
    <div class="container mt-4">
        <div class="card">
            <div class="text-white card-header bg-primary d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Lista de Marcas</h4>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalMarca">
                    Abrir Modal
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="marcasTable" class="table align-middle table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($marcas as $marca)
                                <tr>
                                    <td>{{ $marca->id }}</td>
                                    <td>{{ $marca->nombre }}</td>
                                    <td>
                                        <!-- acciones -->
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Sin datos</td>
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
