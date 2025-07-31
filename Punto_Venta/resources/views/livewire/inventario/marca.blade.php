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
                                <tr class="align-middle text-center cursor-pointer" wire:click="editar({{ $marca->id }})">
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

    <!-- Modal Editar Marca -->
    <div wire:key="modal-{{ $form['id'] ?? 'nuevo' }}">
        <div class="modal fade show" tabindex="-1" style="display: @if($modalAbierto) block @else none @endif; background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Marca</h5>
                        <button type="button" class="btn-close" wire:click="cerrarModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="guardar">
                            <div class="mb-3">
                                <label for="marcaId" class="form-label">ID</label>
                                <input type="text" id="marcaId" class="form-control" wire:model="form.id" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="marcaNombre" class="form-label">Nombre</label>
                                <input type="text" id="marcaNombre" class="form-control" wire:model.defer="form.nombre">
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
