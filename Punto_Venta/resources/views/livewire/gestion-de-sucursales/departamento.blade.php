<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Tabla de Departamentos --}}
    <div class="overflow-hidden border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

        <!-- ENCABEZADO -->
        <div class="flex items-center justify-between px-5 py-3 mb-4 font-semibold text-white rounded-t"
            :class="{
                'bg-emerald-600': theme === 'verde',
                'bg-blue-600': theme === 'azul',
                'bg-gray-900': theme === 'oscuro',
                'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
            }"
        >
            <h5 class="mb-0 text-lg">Gestión de Departamentos</h5>
            <button wire:click="abrirModalCrear"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Agregar Departamento
            </button>
        </div>

        <!-- TABLA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="departamentosTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Municipios</th>
                            <th>Registrado por</th>
                            <th style="width: 150px;">Fecha Registro</th>
                            <th style="width: 60px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departamentos as $departamento)
                            <tr class="text-center align-middle hover:bg-gray-50">
                                <td class="cursor-pointer" wire:click="editarDepartamento({{ $departamento->id }})">{{ $departamento->id }}</td>
                                <td class="cursor-pointer text-start" wire:click="editarDepartamento({{ $departamento->id }})">{{ $departamento->nombre }}</td>
                                <td class="cursor-pointer" wire:click="editarDepartamento({{ $departamento->id }})">
                                    <span class="badge bg-info">
                                        {{ $departamento->municipios->count() }} municipios
                                    </span>
                                </td>
                                <td class="cursor-pointer" wire:click="editarDepartamento({{ $departamento->id }})">{{ $departamento->userRegistro->name ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editarDepartamento({{ $departamento->id }})">{{ $departamento->created_at ? $departamento->created_at->format('d/m/Y') : 'N/A' }}</td>
                                <td>
                                    <button type="button" class="p-0 btn btn-link" wire:click="confirmarEliminar({{ $departamento->id }})" title="Eliminar" onclick="event.stopPropagation();">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7v12a2 2 0 002 2h8a2 2 0 002-2V7M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m-7 0h10" style="color:#e3342f;" />
                                            <line x1="10" y1="11" x2="10" y2="17" stroke="#e3342f" stroke-width="2"/>
                                            <line x1="14" y1="11" x2="14" y2="17" stroke="#e3342f" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-muted">No hay departamentos disponibles.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modal Confirmar Eliminación -->
    <div wire:key="modal-confirmar-eliminar">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($modalEliminarAbierto ?? false) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.cerrarModalEliminar()"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="text-white modal-header bg-danger">
                        <h5 class="modal-title">¿Eliminar departamento?</h5>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro que deseas eliminar este departamento? Esta acción no se puede deshacer.</p>
                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModalEliminar">No</button>
                            <button type="button" class="btn btn-danger" wire:click="eliminarDepartamento">Sí, eliminar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para Departamento --}}
    <div wire:key="modal-departamento">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($modalDepartamentoAbierto ?? false) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.resetDepartamento()"
        >
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalDepartamentoLabel">
                            <i class="fas fa-{{ $isEdit ? 'edit' : 'plus' }} me-2"></i>
                            {{ $isEdit ? 'Editar' : 'Nuevo' }} Departamento
                        </h5>
                        <button type="button" class="btn-close" wire:click="resetDepartamento"></button>
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
                            <div class="mt-4 row">
                                <div class="col-12">
                                    <div class="mb-3 d-flex justify-content-between align-items-center">
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
                                        <table id="municipiosTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                                            <thead class="table-light">
                                                <tr class="text-center align-middle">
                                                    <th>ID</th>
                                                    <th>Nombre</th>
                                                    <th style="width: 60px;">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($municipios as $municipio)
                                                    <tr class="text-center align-middle hover:bg-gray-50">
                                                        <td>{{ $municipio['id'] }}</td>
                                                        <td class="text-start">{{ $municipio['nombre'] }}</td>
                                                        <td>
                                                            <button type="button" class="p-0 btn btn-link"
                                                                    onclick="confirmarEliminacionMunicipio({{ $municipio['id'] }})"
                                                                    title="Eliminar">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7v12a2 2 0 002 2h8a2 2 0 002-2V7M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2m-7 0h10" style="color:#e3342f;" />
                                                                    <line x1="10" y1="11" x2="10" y2="17" stroke="#e3342f" stroke-width="2"/>
                                                                    <line x1="14" y1="11" x2="14" y2="17" stroke="#e3342f" stroke-width="2"/>
                                                                </svg>
                                                            </button>
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
                            <div class="mt-4 row">
                                <div class="col-12">
                                    <div class="mb-3 d-flex justify-content-between align-items-center">
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
                            <button type="button" class="btn btn-secondary" wire:click="resetDepartamento">
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
    <div wire:key="modal-municipio">
        <div class="modal fade show"
             tabindex="-1"
             style="display: @if($showMunicipioModal ?? false) block @else none @endif; background: rgba(0,0,0,0.5); z-index: 1001;"
             aria-modal="true"
             role="dialog"
             @click.self="@this.resetMunicipio()"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalMunicipioLabel">
                            <i class="fas fa-plus me-2"></i>
                            Nuevo Municipio
                        </h5>
                        <button type="button" class="btn-close" wire:click="resetMunicipio"></button>
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
                            <button type="button" class="btn btn-secondary" wire:click="resetMunicipio">
                                <i class="fas fa-times me-1"></i>
                                Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('mensaje'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="mt-3 mb-0 transition-opacity duration-300 alert alert-success">
            {{ session('mensaje') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="mt-3 mb-0 transition-opacity duration-300 alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

        @if($alertMessage)
        <div x-data="{ show: true }" x-show="show"
             @click.window="show = false"
             @keydown.window="show = false"
             @mousemove.window="show = false"
             class="alert alert-{{ $alertType == 'success' ? 'success' : ($alertType == 'error' ? 'danger' : 'warning') }} mt-3 mb-0 transition-opacity duration-300">
            <i class="fas fa-{{ $alertType == 'success' ? 'check-circle' : ($alertType == 'error' ? 'exclamation-triangle' : 'exclamation-circle') }}"></i>
            {{ $alertMessage }}
        </div>
    @endif

    <script>
        // Función para confirmar eliminación de municipio
        function confirmarEliminacionMunicipio(id) {
            if (confirm('¿Está seguro de que desea eliminar este municipio?')) {
                @this.eliminarMunicipio(id);
            }
        }
    </script>

</div> {{-- FIN ELEMENTO RAÍZ --}}
