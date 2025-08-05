<div> {{-- ELEMENTO RAÍZ ÚNICO OBLIGATORIO --}}

    {{-- Tabla de Sucursales --}}
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
            <h5 class="mb-0 text-lg">Gestión de Sucursales</h5>
            <button wire:click="abrirModalCrear"
                class="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
                <span>➕</span> Agregar Sucursal
            </button>
        </div>

        <!-- TABLA -->
        <div class="px-4 py-3 pt-0 card-body">
            <div class="table-responsive">
                <table id="sucursalesTable" class="table mb-0 align-middle table-sm table-hover table-bordered">
                    <thead class="table-light">
                        <tr class="text-center align-middle">
                            <th>Denominación Social</th>
                            <th>Número Sucursal</th>
                            <th>Tipo Tienda</th>
                            <th>Teléfono</th>
                            <th>Municipio</th>
                            <th>Estado</th>
                            <th style="width: 150px;">Fecha Creación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sucursales as $sucursal)
                            <tr class="text-center align-middle hover:bg-gray-50">
                                <td class="text-start cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->denominacion_social }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->numero_sucursal ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->tipoTienda->nombre ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->telefono ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->direccion->municipio->nombre ?? 'N/A' }}</td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">
                                    <span class="badge {{ $sucursal->estado_id == 1 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $sucursal->estado->descripcion ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="cursor-pointer" wire:click="editar({{ $sucursal->id }})">{{ $sucursal->created_at ? $sucursal->created_at->format('d/m/Y') : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-muted">No hay sucursales disponibles.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

</div> {{-- FIN ELEMENTO RAÍZ --}}
