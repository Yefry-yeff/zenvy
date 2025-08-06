<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

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
            <h5 class="mb-0 text-lg">
                <i class="fas fa-users me-2"></i>Gestión de Clientes
            </h5>
            <div class="flex gap-2">
                <button wire:click="crearNuevoCliente"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <i class="fas fa-plus"></i> Nuevo Cliente
                </button>
            </div>
        </div>

        <!-- CONTENIDO -->
        <div class="px-4 py-3 pt-0 card-body">
            
            <!-- Barra de búsqueda y filtros -->
            <div class="mb-4 row">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text"
                               class="form-control"
                               placeholder="Buscar por nombre, correo, identidad..."
                               wire:model.live="buscar">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="filtroTipoPersona">
                        <option value="">Tipo de Persona</option>
                        @foreach($tiposPersona as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="filtroTipoCliente">
                        <option value="">Tipo de Cliente</option>
                        @foreach($tiposCliente as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select class="form-select" wire:model.live="filtroEstado">
                        <option value="">Todos los estados</option>
                        <option value="1">Activos</option>
                        <option value="2">Inactivos</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <button wire:click="limpiarFiltros" class="btn btn-outline-secondary">
                        <i class="fas fa-broom"></i> Limpiar Filtros
                    </button>
                </div>
            </div>

            <!-- Tabla de clientes -->
            <div class="table-responsive">
                <table class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Identidad/RTN</th>
                            <th>Correo</th>
                            <th>Tipo Persona</th>
                            <th>Tipo Cliente</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $cliente)
                            <tr class="text-center align-middle">
                                <td>{{ $cliente->id }}</td>
                                
                                <td class="text-start">
                                    <div>
                                        <strong>{{ $cliente->nombre }}</strong><br>
                                        <small class="text-muted">
                                            Registrado: {{ $cliente->created_at ? $cliente->created_at->format('d/m/Y') : 'N/A' }}
                                        </small>
                                    </div>
                                </td>
                                
                                <td>
                                    @if($cliente->identidad)
                                        <span class="badge bg-primary">ID: {{ $cliente->identidad }}</span><br>
                                    @endif
                                    @if($cliente->rtn)
                                        <span class="badge bg-secondary">RTN: {{ $cliente->rtn }}</span>
                                    @endif
                                    @if(!$cliente->identidad && !$cliente->rtn)
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                
                                <td>
                                    {{ $cliente->correo ?? 'N/A' }}
                                </td>
                                
                                <td>
                                    <span class="badge bg-info">
                                        {{ $cliente->tipoPersona->nombre ?? 'N/A' }}
                                    </span>
                                </td>
                                
                                <td>
                                    <span class="badge bg-success">
                                        {{ $cliente->tipoCliente->nombre ?? 'N/A' }}
                                    </span>
                                </td>
                                
                                <td class="text-start">
                                    @if($cliente->direccion)
                                        <small>
                                            {{ $cliente->direccion->municipio->nombre ?? 'N/A' }}, 
                                            {{ $cliente->direccion->municipio->departamento->nombre ?? 'N/A' }}
                                        </small>
                                    @else
                                        <span class="text-muted">Sin dirección</span>
                                    @endif
                                </td>
                                
                                <td>
                                    <span class="badge {{ $cliente->estado_id == 1 ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $cliente->estado_id == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                
                                <td>
                                    <div class="btn-group" role="group">
                                        <button wire:click="editarCliente({{ $cliente->id }})"
                                                class="btn btn-outline-primary btn-sm"
                                                title="Editar cliente">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-4 text-center text-muted">
                                    <i class="fas fa-users fa-3x mb-3 text-light"></i><br>
                                    No hay clientes registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($clientes->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $clientes->links() }}
                </div>
            @endif

        </div>
    </div>

    <!-- Modal de Éxito -->
    @if($mostrarModalExito)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="text-white modal-header bg-success">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>¡Éxito!
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">{{ $mensajeModalExito }}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cerrarModalExito" class="btn btn-success">Entendido</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Error -->
    @if($mostrarModalError)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="text-white modal-header bg-danger">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Error
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-0">{{ $mensajeModalError }}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cerrarModalError" class="btn btn-danger">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
