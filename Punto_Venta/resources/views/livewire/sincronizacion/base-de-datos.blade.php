<div>
    <!-- Configuración de Sincronización de Base de Datos -->
    <div class="container-fluid px-4 py-4">
        
        <!-- Encabezado -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h3 mb-1">
                            <i class="fas fa-database text-primary me-2"></i>
                            Configuración de Sincronización
                        </h2>
                        <p class="text-muted mb-0">Gestionar las configuraciones de sincronización entre bases de datos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensajes de estado -->
        @if($mensajeExito)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ $mensajeExito }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($mensajeError)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ $mensajeError }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Configuración de Bases de Datos -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-server me-2"></i>Configuración de Bases de Datos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="border rounded p-3 bg-light">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-upload me-2"></i>Base de Datos Origen (Valencia)
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">{{ $nombreBaseDatosOrigen }}</span>
                                        <button wire:click="abrirEdicionBaseDatos" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Cambiar
                                        </button>
                                    </div>
                                    <small class="text-muted">Conexión de base de datos configurada en config/database.php</small>
                                </div>
                            </div>
                            <div class="col-md-2 text-center d-flex align-items-center justify-content-center">
                                <i class="fas fa-arrow-right text-success fa-2x"></i>
                            </div>
                            <div class="col-md-5">
                                <div class="border rounded p-3 bg-light">
                                    <h6 class="text-success mb-3">
                                        <i class="fas fa-download me-2"></i>Base de Datos Destino (Zenvy)
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">{{ $nombreBaseDatosDestino }}</span>
                                        <button wire:click="abrirEdicionBaseDatos" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-edit"></i> Cambiar
                                        </button>
                                    </div>
                                    <small class="text-muted">Conexión de base de datos configurada en config/database.php</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button wire:click="abrirConfiguracionTablas" class="btn btn-primary">
                                        <i class="fas fa-table me-2"></i>Ver Configuraciones de Sincronización
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista de Configuraciones de Sincronización -->
        @if($mostrarConfiguracionTablas)
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-sync me-2"></i>Configuraciones de Sincronización Activas
                            </h5>
                            <button wire:click="cerrarConfiguracionTablas" class="btn btn-sm btn-light">
                                <i class="fas fa-times"></i> Cerrar
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($configuraciones as $key => $config)
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 {{ $config['activo'] ? 'border-success' : 'border-warning' }}">
                                            <div class="card-header {{ $config['activo'] ? 'bg-success' : 'bg-warning' }} text-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="card-title mb-0">{{ $config['nombre'] }}</h6>
                                                    <button wire:click="toggleSincronizacion('{{ $key }}')" 
                                                            class="btn btn-sm {{ $config['activo'] ? 'btn-light' : 'btn-dark' }}">
                                                        <i class="fas {{ $config['activo'] ? 'fa-pause' : 'fa-play' }}"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text small">{{ $config['descripcion'] }}</p>
                                                
                                                <div class="mb-3">
                                                    <strong>Servicio:</strong><br>
                                                    <code class="text-primary">{{ $config['servicio'] }}</code>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Comando:</strong><br>
                                                    <code class="text-success">php artisan {{ $config['comando'] }}</code>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-6">
                                                        <strong>Tabla Origen:</strong><br>
                                                        <small class="text-muted">{{ $config['tabla_origen'] }}</small>
                                                    </div>
                                                    <div class="col-6">
                                                        <strong>Tabla Destino:</strong><br>
                                                        <small class="text-muted">{{ $config['tabla_destino'] }}</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-3">
                                                    <button wire:click="probarComando('{{ $config['comando'] }}')" 
                                                            class="btn btn-sm btn-outline-primary w-100"
                                                            {{ !$config['activo'] ? 'disabled' : '' }}>
                                                        <i class="fas fa-play me-2"></i>Probar Comando
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-footer text-center">
                                                <span class="badge {{ $config['activo'] ? 'bg-success' : 'bg-warning' }}">
                                                    {{ $config['activo'] ? 'ACTIVO' : 'INACTIVO' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <div class="alert alert-info mt-4">
                                <h6><i class="fas fa-info-circle me-2"></i>Información Importante</h6>
                                <ul class="mb-0">
                                    <li>Al cambiar las configuraciones de base de datos, se actualizarán automáticamente todos los servicios de sincronización.</li>
                                    <li>Los cambios se aplican directamente en el código de los servicios (archivos PHP).</li>
                                    <li>No se modifican las tablas de la base de datos, solo las configuraciones de conexión.</li>
                                    <li>Asegúrate de que las conexiones de base de datos estén configuradas correctamente en <code>config/database.php</code>.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal de Edición de Base de Datos -->
    @if($mostrarModalEdicion)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-database me-2"></i>Configurar Conexiones de Base de Datos
                        </h5>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atención:</strong> Este cambio modificará las configuraciones en todos los servicios de sincronización.
                        </div>
                        
                        <form wire:submit.prevent="guardarConfiguracionBaseDatos">
                            <div class="mb-3">
                                <label for="nombreBaseDatosOrigen" class="form-label">
                                    <i class="fas fa-upload me-2"></i>Base de Datos Origen (Valencia)
                                </label>
                                <input type="text" 
                                       class="form-control @error('nombreBaseDatosOrigen') is-invalid @enderror" 
                                       id="nombreBaseDatosOrigen"
                                       wire:model="nombreBaseDatosOrigen" 
                                       placeholder="profac_app">
                                @error('nombreBaseDatosOrigen')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Nombre de la conexión configurada en config/database.php</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nombreBaseDatosDestino" class="form-label">
                                    <i class="fas fa-download me-2"></i>Base de Datos Destino (Zenvy)
                                </label>
                                <input type="text" 
                                       class="form-control @error('nombreBaseDatosDestino') is-invalid @enderror" 
                                       id="nombreBaseDatosDestino"
                                       wire:model="nombreBaseDatosDestino" 
                                       placeholder="mysql">
                                @error('nombreBaseDatosDestino')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Nombre de la conexión configurada en config/database.php</small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" wire:click="cancelarEdicion" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button" wire:click="guardarConfiguracionBaseDatos" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Actualizar Configuración
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
