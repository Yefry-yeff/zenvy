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
                <i class="fas fa-users me-2"></i>Gestión de Actores
            </h5>
            <div class="flex gap-2">
                <button wire:click="crearNuevoCliente"
                    class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                    <i class="fas fa-plus"></i> Nuevo Actor
                </button>
            </div>
        </div>

        <!-- CONTENIDO -->
        <div class="px-4 py-3 pt-0 card-body">

            <!-- Tabla de clientes -->
            <div class="table-responsive">
                <table class="table mb-0 align-middle table-sm table-hover table-bordered" id="clientesTable">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>ID</th>
                            <th>Actor</th>
                            <th>Identidad/RTN</th>
                            <th>Correo</th>
                            <th>Tipo Persona</th>
                            <th>Tipo Actor</th>
                            <th>Dirección</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $cliente)
                            <tr class="text-center align-middle cursor-pointer"
                                wire:click="editarCliente({{ $cliente->id }})"
                                style="cursor: pointer;"
                                title="Clic para editar cliente">
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
                                        <div>
                                            <small>
                                                @if($cliente->direccion)
                                                    {{ $cliente->direccion }}<br>
                                                @else
                                                    <span class="text-muted">Sin dirección</span><br>
                                                @endif
                                            </small>
                                        </div>
                                    @else
                                        <span class="text-muted">Sin dirección</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="badge {{ $cliente->estado_id == 1 ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $cliente->estado_id == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-4 text-center text-muted">
                                    <i class="mb-3 fas fa-users fa-3x text-light"></i><br>
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

    <!-- Alerta de validación flotante -->
    @if($mostrarAlerta)
        <div class="alert-campo-obligatorio">
            <strong>⚠️ Campo Obligatorio</strong>
            <button wire:click="cerrarAlerta" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">×</button>
            <br><small>{{ $mensajeAlerta }}</small>
        </div>
    @endif

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

    <!-- Estilos CSS para validación y alertas -->
    <style>
        /* Campo con error - solo rojos */
        .is-invalid, .campo-obligatorio-vacio {
            border: 2px solid #dc3545 !important;
            background-color: #fff5f5 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Mensaje de error personalizado */
        .text-danger {
            color: #dc3545 !important;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Alerta flotante personalizada */
        .alert-campo-obligatorio {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 14px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            border-left: 4px solid #dc3545;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Estilo para labels de campos obligatorios */
        .text-red-600 {
            color: #dc3545 !important;
            font-weight: bold;
        }

        /* Cursor pointer para filas clickeables */
        .cursor-pointer:hover {
            background-color: #f8f9fa !important;
        }
    </style>

</div>
