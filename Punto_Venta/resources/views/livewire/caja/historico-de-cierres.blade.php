<div>
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .avatar {
            font-weight: bold;
            font-size: 14px;
        }
    </style>

    <!-- MENSAJES DE SESIÓN -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>✅ Éxito:</strong> {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>❌ Error:</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="overflow-hidden border border-gray-300 rounded shadow">
        
        <!-- ENCABEZADO -->
        <div class="flex items-center justify-between px-5 py-3 font-semibold text-white bg-gradient-primary rounded-t">
            <h5 class="mb-0 text-lg">
                <i class="fas fa-history me-2"></i>
                Histórico de Cierres de Caja
            </h5>
            <div class="flex items-center gap-2">
                @if($esAdmin)
                    <span class="px-2 py-1 text-xs font-semibold text-gray-800 bg-yellow-400 rounded">Admin</span>
                @endif
                <span class="text-sm opacity-90">Total: {{ $cierres->total() }}</span>
            </div>
        </div>

        <!-- FILTROS Y BÚSQUEDA -->
        <div class="px-4 py-3 border-b bg-gray-50">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-{{ $esAdmin ? '3' : '2' }}">
                <!-- Fecha Inicio -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Fecha Inicio</label>
                    <input type="date" wire:model="fechaInicio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Fecha Fin -->
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Fecha Fin</label>
                    <input type="date" wire:model="fechaFin" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Usuario (solo para admin) -->
                @if($esAdmin)
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Usuario</label>
                        <select wire:model="usuarioId" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los usuarios</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
            
            <!-- Botones de acción -->
            <div class="flex gap-2 mt-3">
                <button wire:click="filtrar" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    <i class="fas fa-search me-1"></i> Filtrar
                </button>
                <button wire:click="limpiarFiltros" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    <i class="fas fa-eraser me-1"></i> Limpiar Filtros
                </button>
            </div>
        </div>

        <!-- INFORMACIÓN DE RESULTADOS -->
        <div class="px-4 py-2 bg-gray-100 border-b">
            <div class="text-sm text-gray-600">
                Mostrando {{ $cierres->firstItem() ?? 0 }} a {{ $cierres->lastItem() ?? 0 }}
                de {{ $cierres->total() }} registros
            </div>
        </div>

        <!-- TABLA DE CIERRES -->
        <div class="overflow-x-auto">
            @if($cierres->count() > 0)
                <table class="w-full text-sm text-left text-gray-700">
                    <thead class="text-xs text-white uppercase bg-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-center">#</th>
                            @if($esAdmin)
                                <th scope="col" class="px-6 py-3">Usuario</th>
                            @endif
                            <th scope="col" class="px-6 py-3">Fecha Cierre</th>
                            <th scope="col" class="px-6 py-3">Periodo</th>
                            <th scope="col" class="px-6 py-3 text-right">Efectivo</th>
                            <th scope="col" class="px-6 py-3 text-right">Tarjeta</th>
                            <th scope="col" class="px-6 py-3 text-right">Transferencia</th>
                            <th scope="col" class="px-6 py-3 text-right">Cheque</th>
                            <th scope="col" class="px-6 py-3 text-right">Total General</th>
                            <th scope="col" class="px-6 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($cierres as $cierre)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-center font-medium text-gray-900">{{ $cierre->id }}</td>
                                @if($esAdmin)
                                    <td class="px-6 py-4">
                                        @php
                                            $nombreUsuario = $cierre->nombre_usuario ?? null;
                                        @endphp
                                        @if($nombreUsuario)
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center justify-center w-10 h-10 text-sm font-bold text-white rounded-full bg-gradient-to-r from-blue-500 to-purple-600 shadow-md">
                                                    {{ strtoupper(substr($nombreUsuario, 0, 1)) }}
                                                </div>
                                                <span class="font-medium text-gray-900">{{ $nombreUsuario }}</span>
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">Sin usuario</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-calendar-alt text-blue-500"></i>
                                        <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($cierre->fecha_cierre)->format('d/m/Y H:i') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    @if($cierre->periodo_inicio)
                                        <div class="space-y-1">
                                            <div>{{ \Carbon\Carbon::parse($cierre->periodo_inicio)->format('d/m/Y H:i') }}</div>
                                            <div class="text-center"><i class="fas fa-arrow-down text-gray-400"></i></div>
                                            <div>{{ \Carbon\Carbon::parse($cierre->fecha_cierre)->format('d/m/Y H:i') }}</div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 italic">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">
                                        L. {{ number_format($cierre->total_efectivo_contado ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">
                                        L. {{ number_format($cierre->total_tarjeta ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full">
                                        L. {{ number_format($cierre->total_transferencia ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold text-gray-800 bg-gray-100 rounded-full">
                                        L. {{ number_format($cierre->total_cheque ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-base font-bold text-purple-600">
                                        L. {{ number_format(
                                            ($cierre->total_efectivo_contado ?? 0) + 
                                            ($cierre->total_tarjeta ?? 0) + 
                                            ($cierre->total_transferencia ?? 0) + 
                                            ($cierre->total_cheque ?? 0), 
                                            2
                                        ) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button type="button"
                                           onclick="event.preventDefault(); event.stopPropagation(); window.open('{{ route('cierre-caja.pdf.preview', $cierre->id) }}', '_blank');" 
                                           class="inline-flex items-center px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           title="Ver PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                        <button type="button"
                                           onclick="event.preventDefault(); event.stopPropagation(); window.location.href='{{ route('cierre-caja.reporte-transacciones', $cierre->id) }}';" 
                                           class="inline-flex items-center px-3 py-2 text-xs font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
                                           title="Descargar Excel">
                                            <i class="fas fa-file-excel"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="py-12 text-center">
                    <i class="mb-3 text-gray-400 fas fa-inbox fa-3x"></i>
                    <p class="text-gray-500">No se encontraron registros de cierres de caja</p>
                    <small class="text-gray-400">Intenta ajustar los filtros de búsqueda</small>
                </div>
            @endif
        </div>

        <!-- PAGINACIÓN -->
        @if($cierres->hasPages())
            <div class="px-4 py-3 border-t bg-gray-50">
                {{ $cierres->links() }}
            </div>
        @endif
    </div>
</div>
