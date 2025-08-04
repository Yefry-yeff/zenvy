<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Cambio de Sucursal --}}
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
                <i class="fas fa-exchange-alt me-2"></i>Cambio de Sucursal
            </h5>
            <button wire:click="limpiarFormulario"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <i class="fas fa-broom"></i> Limpiar
            </button>
        </div>

        <!-- CONTENIDO -->
        <div class="px-5 py-4">
            
            <!-- Búsqueda de Usuario -->
            <div class="mb-4">
                <div class="p-4 bg-white border shadow rounded-xl">
                    <h3 class="mb-3 text-lg font-semibold text-gray-700">
                        <i class="fas fa-search me-2"></i>Buscar Usuario
                    </h3>
                    
                    <div class="position-relative">
                        <label for="buscarUsuario" class="form-label">Usuario <span class="text-red-600">*</span></label>
                        <input type="text" 
                               id="buscarUsuario"
                               class="form-control" 
                               wire:model.live="buscarUsuario"
                               placeholder="Escriba el nombre o email del usuario..."
                               autocomplete="off">
                        
                        <!-- Lista desplegable de sugerencias -->
                        @if($mostrarSugerencias && count($usuariosSugeridos) > 0)
                            <div class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                @foreach($usuariosSugeridos as $usuario)
                                    <div wire:click="seleccionarUsuario({{ $usuario->id }})" 
                                         class="px-3 py-2 cursor-pointer hover-bg-light border-bottom">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $usuario->name }}</strong><br>
                                                <small class="text-muted">{{ $usuario->email }}</small>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-primary">
                                                    {{ $usuario->tienda ? $usuario->tienda->denominacion_social : 'Sin sucursal' }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        @if($mostrarSugerencias && count($usuariosSugeridos) == 0 && strlen($buscarUsuario) >= 2)
                            <div class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-lg" style="z-index: 1000;">
                                <div class="px-3 py-2 text-muted text-center">
                                    No se encontraron usuarios que coincidan con "{{ $buscarUsuario }}"
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Información del Usuario Seleccionado -->
            @if($usuarioSeleccionado)
                <div class="mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h3 class="mb-3 text-lg font-semibold text-gray-700">
                            <i class="fas fa-user me-2"></i>Información del Usuario
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nombre Completo</label>
                                    <div class="p-2 bg-light rounded">{{ $nombreUsuario }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email</label>
                                    <div class="p-2 bg-light rounded">{{ $emailUsuario }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Sucursal Actual</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-store me-2 text-primary"></i>{{ $sucursalActual }}
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Rol Asignado</label>
                                    <div class="p-2 bg-light rounded">
                                        <i class="fas fa-user-tag me-2 text-success"></i>{{ $rolUsuario }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selección de Nueva Sucursal -->
                <div class="mb-4">
                    <div class="p-4 bg-white border shadow rounded-xl">
                        <h3 class="mb-3 text-lg font-semibold text-gray-700">
                            <i class="fas fa-map-marker-alt me-2"></i>Nueva Sucursal
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <label for="nuevaSucursal" class="form-label">Seleccionar Nueva Sucursal <span class="text-red-600">*</span></label>
                                <select wire:model="nuevaSucursalId" wire:change="$refresh" id="nuevaSucursal" class="form-select">
                                    <option value="">Seleccione una sucursal...</option>
                                    @foreach($tiendas as $tienda)
                                        <option value="{{ $tienda->id }}" {{ $tienda->id == $sucursalActualId ? 'disabled' : '' }}>
                                            {{ $tienda->denominacion_social }}
                                            {{ $tienda->id == $sucursalActualId ? '(Sucursal actual)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                
                                {{-- Información de depuración (quitar después de resolver) --}}
                                <small class="text-muted mt-1 d-block">
                                    Debug: Nueva: {{ $nuevaSucursalId ?? 'null' }} | Actual: {{ $sucursalActualId ?? 'null' }} | 
                                    Habilitado: {{ $this->botonHabilitado ? 'Sí' : 'No' }}
                                </small>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button wire:click="confirmarCambio" 
                                        class="btn w-100 {{ $this->botonHabilitado ? 'btn-primary' : 'btn-secondary' }}"
                                        {{ !$this->botonHabilitado ? 'disabled' : '' }}>
                                    <i class="fas fa-exchange-alt me-2"></i>Cambiar Sucursal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <!-- Modal de Confirmación -->
    @if($mostrarModalConfirmacion)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Cambio de Sucursal
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p>¿Está seguro de que desea cambiar la sucursal del usuario?</p>
                        <div class="alert alert-info">
                            <strong>Usuario:</strong> {{ $nombreUsuario }}<br>
                            <strong>De:</strong> {{ $sucursalActual }}<br>
                            <strong>A:</strong> {{ collect($tiendas)->firstWhere('id', $nuevaSucursalId)->denominacion_social ?? 'N/A' }}
                        </div>
                        <p class="text-muted small">Esta acción se registrará en el sistema y no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cancelarCambio" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button" wire:click="ejecutarCambio" class="btn btn-warning">
                            <i class="fas fa-check me-2"></i>Confirmar Cambio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Éxito -->
    @if($mostrarModalExito)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
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
                        <button type="button" wire:click="cerrarModalExito" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Entendido
                        </button>
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
                    <div class="modal-header bg-danger text-white">
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
                        <button type="button" wire:click="cerrarModalError" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Estilos CSS adicionales -->
    <style>
        .hover-bg-light:hover {
            background-color: #f8f9fa !important;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .modal.show {
            display: block !important;
        }
    </style>

</div>
