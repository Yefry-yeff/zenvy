<div x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">
    <!-- CONTENIDO PRINCIPAL -->
    <div class="overflow-hidden border border-gray-300 rounded shadow">
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
                <i class="fas fa-percentage"></i>
                Gestión de ISV (Impuestos)
            </h5>
            <button wire:click="abrirModal" 
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <i class="fas fa-plus"></i> Nuevo ISV
            </button>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="px-5 py-4">
            
            <!-- Alerta de éxito -->
            @if($mostrarAlertaExito)
                <div class="alert-exito">
                    <strong>✅ Éxito</strong>
                    <button wire:click="cerrarAlertaExito" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
                    <br><small>{{ $mensajeExito }}</small>
                </div>
            @endif

            <!-- Alerta de error -->
            @if($mostrarAlertaError)
                <div class="alert-campo-obligatorio">
                    <strong>⚠️ Error</strong>
                    <button wire:click="cerrarAlertaError" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
                    <br><small>{{ $mensajeError }}</small>
                </div>
            @endif

            <!-- TABLA DE ISV -->
            <div class="p-4 bg-white border shadow rounded-xl">
                <h6 class="mb-4 text-lg font-semibold text-gray-700">📊 Lista de Tipos de ISV</h6>
                
                @if(count($isvs) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Porcentaje (%)</th>
                                    <th>Estado</th>
                                    <th>Fecha Creación</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($isvs as $isv)
                                    <tr>
                                        <td>{{ $isv->id }}</td>
                                        <td>
                                            <span class="badge bg-primary">{{ number_format($isv->cantidad, 2) }}%</span>
                                        </td>
                                        <td>
                                            @if($isv->estado_id == 1)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> Activo
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-times-circle"></i> Inactivo
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $isv->created_at ? \Carbon\Carbon::parse($isv->created_at)->format('d/m/Y H:i') : 'N/A' }}</td>
                                        <td class="text-center">
                                            @if($isv->estado_id == 1)
                                                <button wire:click="cambiarEstado({{ $isv->id }})" 
                                                    class="btn btn-sm btn-secondary" title="Inactivar">
                                                    <i class="fas fa-eye-slash"></i> Inactivar
                                                </button>
                                            @else
                                                <button wire:click="cambiarEstado({{ $isv->id }})" 
                                                    class="btn btn-sm btn-success" title="Activar">
                                                    <i class="fas fa-eye"></i> Activar
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No hay tipos de ISV registrados</p>
                        <button wire:click="abrirModal" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Crear el primer ISV
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL PARA CREAR ISV -->
    @if($mostrarModal)
        <div class="modal fade show" 
             tabindex="-1" 
             style="display: block; background: rgba(0,0,0,0.5); z-index: 1000;"
             aria-modal="true"
             role="dialog"
             wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"
                         :class="{
                            'bg-emerald-700 text-white': theme === 'verde',
                            'bg-blue-700 text-white': theme === 'azul',
                            'bg-gray-900 text-white': theme === 'oscuro',
                            'bg-slate-700 text-white': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                         }">
                        <h5 class="modal-title">
                            <i class="fas fa-plus"></i> Nuevo ISV
                        </h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="cerrarModal"></button>
                    </div>
                    <form wire:submit.prevent="guardar">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="cantidad" class="form-label">Porcentaje de ISV <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" 
                                           id="cantidad" 
                                           class="form-control {{ $this->getClaseCampo('cantidad') }}" 
                                           wire:model.defer="form.cantidad" 
                                           step="0.01" 
                                           min="0" 
                                           max="100" 
                                           placeholder="Ej: 15.00">
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('form.cantidad')
                                    <div class="text-danger mt-1 text-sm">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Ingrese el porcentaje de impuesto (0 para productos exentos)
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="cerrarModal">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- ESTILOS PERSONALIZADOS -->
    <style>
        .alert-exito {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .alert-campo-obligatorio {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .table th {
            font-weight: 600;
            color: #495057;
        }

        .btn:focus {
            box-shadow: none;
        }

        .modal.show {
            display: block !important;
        }
    </style>
</div>
