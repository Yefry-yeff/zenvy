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
                <i class="fas fa-concierge-bell me-2"></i>
                @if($isEditing)
                    Editar Servicio
                @else
                    Nuevo Servicio
                @endif
            </h5>
            <button wire:click="volverALista"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <i class="fas fa-arrow-left"></i> Volver
            </button>
        </div>

        <!-- FORMULARIO -->
        <div class="p-4">
            <!-- Alerta de validación backend -->
            @if($mostrarAlerta)
                <div class="alert alert-warning alert-dismissible fade show">
                    <strong>⚠️ Campo Obligatorio</strong>
                    <button wire:click="cerrarAlerta" class="btn-close"></button>
                    <br><small>{{ $mensajeAlerta }}</small>
                </div>
            @endif

            <form wire:submit.prevent="guardar">

                <!-- Información Básica -->
                <div class="mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2 text-primary"></i>
                                Información Básica
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">
                                            Nombre del Servicio <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               id="nombre" 
                                               class="form-control" 
                                               wire:model="form.nombre" 
                                               placeholder="Ej: Corte de cabello">
                                        @error('form.nombre')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="precio_base" class="form-label">
                                            Precio Base <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">L.</span>
                                            <input type="number" 
                                                   id="precio_base" 
                                                   class="form-control" 
                                                   wire:model="form.precio_base"
                                                   step="0.01" 
                                                   min="0.01"
                                                   placeholder="0.00">
                                        </div>
                                        @error('form.precio_base')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">Descripción</label>
                                        <textarea id="descripcion" 
                                                  class="form-control" 
                                                  wire:model="form.descripcion"
                                                  rows="3"
                                                  placeholder="Describe brevemente el servicio..."></textarea>
                                        @error('form.descripcion')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Imagen del Servicio -->
                <div class="mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-image me-2 text-primary"></i>
                                Imagen del Servicio
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="imagen" class="form-label">Seleccionar imagen</label>
                                        <input type="file" 
                                               id="imagen" 
                                               class="form-control" 
                                               wire:model="imagen"
                                               accept="image/*">
                                        <div wire:loading wire:target="imagen" class="mt-1 text-info small">
                                            <i class="fas fa-spinner fa-spin me-1"></i>Subiendo imagen...
                                        </div>
                                        @error('imagen') 
                                            <div class="text-danger mt-1 small">{{ $message }}</div> 
                                        @enderror
                                        <small class="text-muted">Formatos: JPG, PNG, GIF. Máximo: 5MB</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vista previa</label>
                                    <div class="border rounded p-3 text-center" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
                                        @if($this->getImagenMiniatura())
                                            <div class="position-relative">
                                                <img src="{{ $this->getImagenMiniatura() }}" 
                                                     alt="Vista previa" 
                                                     class="img-fluid rounded"
                                                     style="max-height: 120px; max-width: 100%; object-fit: cover;">
                                                <button type="button" 
                                                        wire:click="removerImagen"
                                                        class="btn btn-danger btn-sm position-absolute"
                                                        style="top: -10px; right: -10px; border-radius: 50%; width: 30px; height: 30px;"
                                                        title="Remover imagen">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @else
                                            <div class="text-muted">
                                                <i class="fas fa-image fa-3x mb-2" style="opacity: 0.3;"></i><br>
                                                <small>No hay imagen seleccionada</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Impuestos y Descuentos -->
                <div class="mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">
                                <i class="fas fa-percentage me-2 text-primary"></i>
                                Impuestos y Descuentos
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="isv_id" class="form-label">
                                            Tipo de ISV <span class="text-danger">*</span>
                                        </label>
                                        <select id="isv_id" class="form-select" wire:model="form.isv_id">
                                            <option value="">Seleccionar ISV</option>
                                            @foreach($isvs as $isv)
                                                <option value="{{ $isv->id }}">{{ $isv->cantidad }}%</option>
                                            @endforeach
                                        </select>
                                        @error('form.isv_id')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="descuento_unitario" class="form-label">
                                            Descuento Unitario
                                        </label>
                                        <input type="number" 
                                               id="descuento_unitario" 
                                               class="form-control" 
                                               wire:model="form.descuento_unitario"
                                               step="0.01" 
                                               min="0"
                                               placeholder="0.00">
                                        @error('form.descuento_unitario')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="descuento_tercera"
                                               wire:model="form.descuento_tercera">
                                        <label class="form-check-label" for="descuento_tercera">
                                            <i class="fas fa-user-clock me-1 text-info"></i>
                                            Aplica descuento tercera edad
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="descuento_cuarta"
                                               wire:model="form.descuento_cuarta">
                                        <label class="form-check-label" for="descuento_cuarta">
                                            <i class="fas fa-user-friends me-1 text-secondary"></i>
                                            Aplica descuento cuarta edad
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado del Servicio (Solo en Edición) -->
                @if($isEditing)
                    <div class="mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-toggle-on me-2 text-success"></i>
                                    Estado del Servicio
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="estado_id" class="form-label">
                                            Estado <span class="text-danger">*</span>
                                        </label>
                                        <select id="estado_id" class="form-select" wire:model="form.estado_id">
                                            @foreach($this->getOpcionesEstado() as $estado)
                                                <option value="{{ $estado->id }}">{{ $estado->descripcion }}</option>
                                            @endforeach
                                        </select>
                                        @error('form.estado_id')
                                            <div class="text-danger mt-1 small">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Puedes cambiar entre activo e inactivo según sea necesario
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- BOTONES -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" 
                            class="btn text-white" 
                            :class="{
                                'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                                'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                                'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                                'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                            }"
                            wire:loading.attr="disabled">
                        <div wire:loading wire:target="guardar">
                            <i class="fas fa-spinner fa-spin me-1"></i>
                        </div>
                        <div wire:loading.remove wire:target="guardar">
                            <i class="fas fa-save me-1"></i>
                        </div>
                        @if($isEditing) Actualizar @else Crear @endif Servicio
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- MODALES -->
    <!-- Modal de Éxito -->
    @if($mostrarModalExito)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>¡Éxito!
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p>{{ $mensajeModalExito }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" wire:click="cerrarModalExito">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Modal de Error -->
    @if($mostrarModalError)
        <div class="modal fade show" style="display: block;" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-circle me-2"></i>Error
                        </h5>
                    </div>
                    <div class="modal-body">
                        <p>{{ $mensajeModalError }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" wire:click="cerrarModalError">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

</div> {{-- FIN ELEMENTO RAÍZ --}}
