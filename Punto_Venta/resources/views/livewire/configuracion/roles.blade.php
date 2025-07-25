<div class="overflow-hidden border border-gray-300 rounded shadow" x-data x-init="$watch('theme', t => localStorage.setItem('theme', t))">

    <!-- ENCABEZADO CON TEMA -->
    <div
        class="flex items-center justify-between px-4 py-2 font-semibold text-white"
        :class="{
            'bg-emerald-600': theme === 'verde',
            'bg-blue-600': theme === 'azul',
            'bg-gray-900': theme === 'oscuro',
            'bg-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
        }"
    >
        <h5 class="mb-0">Gestión de Roles</h5>
        <button wire:click="abrirModalCrear" class="px-3 py-1 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
            ➕ Agregar Rol
        </button>
    </div>

    <!-- TABLA DE ROLES -->
    <div class="overflow-x-auto bg-white">
        <table class="min-w-full text-sm text-left border border-gray-300">
            <thead class="bg-gray-100 border-b border-gray-300">
                <tr class="text-xs font-semibold text-gray-700 uppercase">
                    <th class="px-4 py-2 border">ID</th>
                    <th class="px-4 py-2 border">Nombre</th>
                    <th class="px-4 py-2 border">Estado</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-800 bg-white divide-y divide-gray-200">
                @forelse ($roles as $rol)
                    <tr
                        wire:click="editar({{ $rol->id }})"
                        class="transition cursor-pointer hover:bg-gray-100"
                    >
                        <td class="px-4 py-2 border-t">{{ $rol->id }}</td>
                        <td class="px-4 py-2 border-t">{{ $rol->txt_nombre }}</td>
                        <td class="px-4 py-2 border-t">
                            @if($rol->estado)
                                <span class="inline-block px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded">Activo</span>
                            @else
                                <span class="inline-block px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-4 italic text-center text-gray-500">No hay roles registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- MENSAJE DE ÉXITO -->
    @if (session()->has('mensaje'))
        <div class="p-3 mt-4 text-green-700 bg-green-100 border border-green-400 rounded">
            {{ session('mensaje') }}
        </div>
    @endif

    <!-- MODAL DE CREACIÓN / EDICIÓN -->
    @if($modalAbierto)
        <div
            x-data="{
                open: true,
                theme: localStorage.getItem('theme') || 'verde',
                modo: @entangle('modoEdicion'),
                submitted: @entangle('submitted'),
                touched: false
            }"
            x-show="open"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 overflow-y-auto bg-black bg-opacity-50"
        >
            <div
                class="relative w-full max-w-xl p-6 bg-white rounded shadow-lg"
                @click.outside="$wire.set('modalAbierto', false)"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="scale-95 opacity-0"
                x-transition:enter-end="scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="scale-100 opacity-100"
                x-transition:leave-end="scale-95 opacity-0"
                :class="{
                    'border-t-4 border-emerald-600': theme === 'verde',
                    'border-t-4 border-blue-600': theme === 'azul',
                    'border-t-4 border-gray-900': theme === 'oscuro',
                    'border-t-4 border-slate-700': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }"
            >
                <h2 class="mb-4 text-lg font-semibold">
                    {{ $modoEdicion ? 'Editar Rol' : 'Crear Rol' }}
                </h2>

                <!-- Campo Nombre -->
               <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Nombre del Rol <span class="text-red-600" x-show="modo === false">*</span>
                    </label>

                    <input
                        type="text"
                        wire:model.defer="form.txt_nombre"
                        :disabled="modo"
                        class="w-full px-3 py-2 border rounded"
                        :class="{ 'bg-gray-100 cursor-not-allowed': modo }"
                        placeholder="Ej: Administrador"
                    >

                    @if ($submitted && $errors->has('form.txt_nombre'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->first('form.txt_nombre') }}</p>
                    @endif
                </div>


                <!-- Estado -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <select wire:model.defer="estado" class="w-full px-3 py-2 border rounded">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <!-- Lista de permisos -->
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Permisos</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($permisosDisponibles as $permiso)
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" wire:model="permisos" value="{{ $permiso->id }}">
                                <span>{{ $permiso->nombre }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Botón Guardar -->
                <div class="flex justify-end mt-6">
                    <button
                        wire:click="guardar"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-white rounded"
                        :class="{
                            'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                            'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                            'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                            'bg-slate-700 hover:bg-slate-800': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                        }"
                    >
                        <span wire:loading.remove>{{ $modoEdicion ? 'Actualizar' : 'Crear' }}</span>
                        <span wire:loading>Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
