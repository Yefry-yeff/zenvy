<div>
    {{-- Alertas --}}
    @if($alertMessage)
        <div class="alert alert-{{ $alertType == 'success' ? 'success' : ($alertType == 'error' ? 'danger' : 'warning') }} alert-dismissible fade show" role="alert">
            <i class="fas fa-{{ $alertType == 'success' ? 'check-circle' : ($alertType == 'error' ? 'exclamation-triangle' : 'exclamation-circle') }}"></i>
            {{ $alertMessage }}
            <button type="button" class="btn-close" wire:click="cerrarAlerta" aria-label="Close"></button>
        </div>
    @endif

    {{-- Encabezado y botón para agregar --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-map-marked-alt me-2"></i>
                    Gestión de Departamentos
                </h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDepartamento" wire:click="resetDepartamento">
                    <i class="fas fa-plus me-1"></i>
                    Nuevo Departamento
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla de departamentos --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table me-2"></i>
                        Lista de Departamentos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaDepartamentos">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Municipios</th>
                                    <th>Registrado por</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($departamentos as $departamento)
                                    <tr>
                                        <td>{{ $departamento->id }}</td>
                                        <td>{{ $departamento->nombre }}</td>
                                        <td>
                                            <span class="badge bg-info">
                                                {{ $departamento->municipios->count() }} municipios
                                            </span>
                                        </td>
                                        <td>{{ $departamento->userRegistro->name ?? 'N/A' }}</td>
                                        <td>{{ $departamento->created_at ? $departamento->created_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalDepartamento"
                                                        wire:click="editarDepartamento({{ $departamento->id }})"
                                                        title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger" 
                                                        onclick="confirmarEliminacion({{ $departamento->id }})"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para Departamento --}}
    <div class="modal fade" id="modalDepartamento" tabindex="-1" aria-labelledby="modalDepartamentoLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDepartamentoLabel">
                        <i class="fas fa-{{ $isEdit ? 'edit' : 'plus' }} me-2"></i>
                        {{ $isEdit ? 'Editar' : 'Nuevo' }} Departamento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="resetDepartamento"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="guardarDepartamento">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="nombre_departamento" class="form-label">
                                        <i class="fas fa-map-marked-alt me-1"></i>
                                        Nombre del Departamento <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control {{ $this->getClaseCampo('nombre_departamento') }}" 
                                           id="nombre_departamento" 
                                           wire:model="nombre_departamento" 
                                           placeholder="Ingrese el nombre del departamento">
                                    @error('nombre_departamento')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Sección de municipios cuando se está editando --}}
                        @if($isEdit && count($municipios) > 0)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">
                                            <i class="fas fa-city me-2"></i>
                                            Municipios del Departamento
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-success" wire:click="mostrarModalMunicipio">
                                            <i class="fas fa-plus me-1"></i>
                                            Agregar Municipio
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nombre</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($municipios as $municipio)
                                                    <tr>
                                                        <td>{{ $municipio['id'] }}</td>
                                                        <td>{{ $municipio['nombre'] }}</td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-warning btn-sm" 
                                                                        wire:click="editarMunicipio({{ $municipio['id'] }})"
                                                                        title="Editar">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-danger btn-sm" 
                                                                        onclick="confirmarEliminacionMunicipio({{ $municipio['id'] }})"
                                                                        title="Eliminar">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($isEdit && count($municipios) == 0)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">
                                            <i class="fas fa-city me-2"></i>
                                            Municipios del Departamento
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-success" wire:click="mostrarModalMunicipio">
                                            <i class="fas fa-plus me-1"></i>
                                            Agregar Municipio
                                        </button>
                                    </div>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Este departamento no tiene municipios registrados.
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="resetDepartamento">
                                <i class="fas fa-times me-1"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                {{ $isEdit ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para Municipio --}}
    <div class="modal fade" id="modalMunicipio" tabindex="-1" aria-labelledby="modalMunicipioLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMunicipioLabel">
                        <i class="fas fa-{{ $editingMunicipio ? 'edit' : 'plus' }} me-2"></i>
                        {{ $editingMunicipio ? 'Editar' : 'Nuevo' }} Municipio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="resetMunicipio"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="guardarMunicipio">
                        <div class="mb-3">
                            <label for="nombre_municipio" class="form-label">
                                <i class="fas fa-city me-1"></i>
                                Nombre del Municipio <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control {{ $this->getClaseCampo('nombre_municipio') }}" 
                                   id="nombre_municipio" 
                                   wire:model="nombre_municipio" 
                                   placeholder="Ingrese el nombre del municipio">
                            @error('nombre_municipio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="resetMunicipio">
                                <i class="fas fa-times me-1"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                {{ $editingMunicipio ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts para confirmaciones y DataTable --}}
    <script>
        // Función para confirmar eliminación de departamento
        function confirmarEliminacion(id) {
            if (confirm('¿Está seguro de que desea eliminar este departamento?')) {
                @this.eliminarDepartamento(id);
            }
        }

        // Función para confirmar eliminación de municipio
        function confirmarEliminacionMunicipio(id) {
            if (confirm('¿Está seguro de que desea eliminar este municipio?')) {
                @this.eliminarMunicipio(id);
            }
        }

        // Event listeners para manejar modales
        document.addEventListener('livewire:init', function () {
            // Cerrar modal departamento
            Livewire.on('cerrarModal', () => {
                var modal = bootstrap.Modal.getInstance(document.getElementById('modalDepartamento'));
                if (modal) {
                    modal.hide();
                }
            });

            // Cerrar modal municipio
            Livewire.on('cerrarModalMunicipio', () => {
                var modal = bootstrap.Modal.getInstance(document.getElementById('modalMunicipio'));
                if (modal) {
                    modal.hide();
                }
            });
        });

        // Mostrar modal municipio cuando se active la propiedad
        document.addEventListener('livewire:updated', function () {
            if (@js($showMunicipioModal)) {
                var modal = new bootstrap.Modal(document.getElementById('modalMunicipio'));
                modal.show();
            }
        });

        // Inicializar DataTable después de que la vista se haya cargado
        document.addEventListener('DOMContentLoaded', function() {
            // Aquí puedes agregar la inicialización de DataTable si la necesitas
            // $('#tablaDepartamentos').DataTable({
            //     language: {
            //         url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            //     },
            //     responsive: true,
            //     order: [[0, 'desc']]
            // });
        });
    </script>
</div>
