<div>
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0">
                            <i class="fas fa-building me-2 text-primary"></i>
                            Información de la Empresa
                        </h2>
                        <p class="text-muted mb-0">Gestiona la información y logo de tu empresa</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

    <div class="row">
        <!-- Formulario de Empresa -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>
                        {{ $editando ? 'Editar' : 'Registrar' }} Empresa
                    </h5>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="guardarEmpresa">
                        <!-- Nombre de la Empresa -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-building me-1"></i>
                                Nombre de la Empresa <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('nombre') is-invalid @enderror" 
                                   id="nombre" 
                                   wire:model="nombre" 
                                   placeholder="Ingrese el nombre de la empresa">
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- RTN -->
                        <div class="mb-3">
                            <label for="rtn" class="form-label">
                                <i class="fas fa-id-card me-1"></i>
                                RTN (Registro Tributario Nacional)
                            </label>
                            <input type="text" 
                                   class="form-control @error('rtn') is-invalid @enderror" 
                                   id="rtn" 
                                   wire:model="rtn" 
                                   placeholder="Ej: 08019999999999">
                            @error('rtn')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Correo Electrónico -->
                        <div class="mb-3">
                            <label for="correo" class="form-label">
                                <i class="fas fa-envelope me-1"></i>
                                Correo Electrónico
                            </label>
                            <input type="email" 
                                   class="form-control @error('correo') is-invalid @enderror" 
                                   id="correo" 
                                   wire:model="correo" 
                                   placeholder="empresa@ejemplo.com">
                            @error('correo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Teléfono -->
                        <div class="mb-3">
                            <label for="telefono" class="form-label">
                                <i class="fas fa-phone me-1"></i>
                                Teléfono
                            </label>
                            <input type="number" 
                                   class="form-control @error('telefono') is-invalid @enderror" 
                                   id="telefono" 
                                   wire:model="telefono" 
                                   placeholder="Ej: 22345678">
                            @error('telefono')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Logo -->
                        <div class="mb-3">
                            <label for="logo" class="form-label">
                                <i class="fas fa-image me-1"></i>
                                Logo de la Empresa
                            </label>
                            <input type="file" 
                                   class="form-control @error('logo') is-invalid @enderror" 
                                   id="logo" 
                                   wire:model="logo" 
                                   accept="image/*">
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
                            </div>
                        </div>

                        <!-- Preview del logo -->
                        @if ($logoPreview)
                            <div class="mb-3">
                                <label class="form-label">Vista Previa del Logo:</label>
                                <div class="text-center">
                                    <img src="{{ $logoPreview }}" 
                                         alt="Preview" 
                                         class="img-thumbnail" 
                                         style="max-width: 200px; max-height: 200px;">
                                </div>
                            </div>
                        @endif

                        <!-- Botones -->
                        <div class="d-flex justify-content-between">
                            <button type="button" 
                                    class="btn btn-secondary" 
                                    wire:click="limpiarFormulario">
                                <i class="fas fa-undo me-1"></i>
                                Limpiar
                            </button>
                            <button type="submit" 
                                    class="btn btn-primary" 
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove>
                                    <i class="fas fa-save me-1"></i>
                                    {{ $editando ? 'Actualizar' : 'Guardar' }}
                                </span>
                                <span wire:loading>
                                    <i class="fas fa-spinner fa-spin me-1"></i>
                                    Guardando...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Información Actual -->
        <div class="col-lg-4">
            <!-- Card de Información -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información Actual
                    </h5>
                </div>
                <div class="card-body">
                    @if($empresa)
                        <!-- Logo Actual -->
                        @if($empresa->logo)
                            <div class="text-center mb-3">
                                <img src="data:image/png;base64,{{ base64_encode($empresa->logo) }}" 
                                     alt="Logo de {{ $empresa->nombre }}" 
                                     class="img-fluid rounded shadow-sm"
                                     style="max-width: 150px; max-height: 150px;">
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-danger" 
                                            wire:click="eliminarLogo"
                                            onclick="return confirm('¿Está seguro de eliminar el logo?')">
                                        <i class="fas fa-trash me-1"></i>
                                        Eliminar Logo
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="text-center mb-3">
                                <div class="bg-light p-4 rounded">
                                    <i class="fas fa-image text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2 mb-0">Sin logo</p>
                                </div>
                            </div>
                        @endif

                        <!-- Información de la Empresa -->
                        <div class="info-item mb-2">
                            <strong><i class="fas fa-building me-1 text-primary"></i> Nombre:</strong>
                            <div class="ms-3">{{ $empresa->nombre ?: 'No especificado' }}</div>
                        </div>

                        <div class="info-item mb-2">
                            <strong><i class="fas fa-id-card me-1 text-primary"></i> RTN:</strong>
                            <div class="ms-3">{{ $empresa->rtn ?: 'No especificado' }}</div>
                        </div>

                        <div class="info-item mb-2">
                            <strong><i class="fas fa-envelope me-1 text-primary"></i> Correo:</strong>
                            <div class="ms-3">{{ $empresa->correo ?: 'No especificado' }}</div>
                        </div>

                        <div class="info-item mb-2">
                            <strong><i class="fas fa-phone me-1 text-primary"></i> Teléfono:</strong>
                            <div class="ms-3">{{ $empresa->telefono ?: 'No especificado' }}</div>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="fas fa-exclamation-circle mb-2" style="font-size: 2rem;"></i>
                            <p>No hay información de empresa registrada</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Card de Ayuda -->
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Consejos
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            El logo aparecerá en las facturas impresas
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Use imágenes de alta calidad para mejor resultado
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success me-2"></i>
                            El RTN es importante para documentos fiscales
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
        .info-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .card {
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
        }
    </style>
</div>
