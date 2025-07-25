<div
    class="overflow-hidden border border-gray-300 rounded shadow"
    x-data="{
        tab: 'datos',
        sinCambiosModal: false
    }"
    x-init="$watch('theme', t => localStorage.setItem('theme', t))"
    x-on:mostrar-sin-cambios.window="sinCambiosModal = true"
>

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
        <button wire:click="volver" class="px-3 py-1 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
            ← Volver
        </button>
        <h5>{{ $modo === 'crear' ? 'Crear Usuario' : 'Editar Usuario' }}</h5>
        <button wire:click="guardar" class="px-3 py-1 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
            💾 Guardar
        </button>
    </div>

<!-- DATOS DE INICIO DE SESIÓN -->
<div class="p-4">
    <div class="p-4 bg-white border shadow rounded-xl">
        <h2 class="mb-4 text-lg font-semibold text-gray-700">🗝️ Datos de inicio de sesión</h2>

        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">Correo<span class="text-red-600">*</span></label>
                <input type="email" wire:model.defer="form.email"
                    @if($modo === 'editar') disabled class="w-full px-3 py-2 bg-gray-100 border rounded cursor-not-allowed"
                    @else class="w-full px-3 py-2 border rounded" @endif
                />
                        @error('form.email')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Contraseña<span class="text-red-600">*</span></label>
                <input type="password" wire:model.defer="form.password" class="w-full px-3 py-2 border rounded" />
                         @error('form.password')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">Rol<span class="text-red-600">*</span></label>
                <select wire:model.lazy="form.rol_id" class="w-full px-3 py-2 border rounded">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->id }}">{{ $rol->txt_nombre }}</option>
                    @endforeach
                </select>
                @error('form.rol_id')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Estado<span class="text-red-600">*</span></label>
                <select wire:model.defer="form.estado" class="w-full px-3 py-2 border rounded">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
                        @error('form.estado')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
            </div>
        </div>
    </div>
</div>

    <!-- DATOS DE ACTOR -->
<div class="p-4">
    <div class="p-4 bg-white border shadow rounded-xl">
        <h2 class="mb-4 text-lg font-semibold text-gray-700">🧍 Datos de Actor</h2>

        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Identificación<span class="text-red-600">*</span></label>
                <input type="text" wire:model.defer="form.txt_identificacion"
                    @if($modo === 'editar') disabled class="w-full px-3 py-2 bg-gray-100 border rounded cursor-not-allowed"
                    @else class="w-full px-3 py-2 border rounded" @endif
                />
                     @error('form.txt_identificacion')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                     @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Primer Nombre<span class="text-red-600">*</span></label>
                <input type="text" wire:model.defer="form.primer_nombre" class="w-full px-3 py-2 border rounded" />
                 @error('form.primer_nombre')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Segundo Nombre</label>
                <input type="text" wire:model.defer="form.segundo_nombre" class="w-full px-3 py-2 border rounded" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Primer Apellido<span class="text-red-600">*</span></label>
                <input type="text" wire:model.defer="form.primer_apellido" class="w-full px-3 py-2 border rounded" />
                @error('form.primer_apellido')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Segundo Apellido</label>
                <input type="text" wire:model.defer="form.segundo_apellido" class="w-full px-3 py-2 border rounded" />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                <input type="text" wire:model.defer="form.telefono" class="w-full px-3 py-2 border rounded" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha de Nacimiento</label>
                <input type="date" wire:model.defer="form.fecha_nacimiento" class="w-full px-3 py-2 border rounded" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Género</label>
                <select wire:model.defer="form.genero" class="w-full px-3 py-2 border rounded">
                    <option value="">-- Seleccionar --</option>
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Dirección</label>
            <input type="text" wire:model.defer="form.direccion" class="w-full px-3 py-2 border rounded" />
        </div>
    </div>
</div>

<!-- ACCESOS -->
<div class="p-4">
    <div class="p-4 bg-white border shadow rounded-xl">
        <h2 class="mb-4 text-lg font-semibold text-gray-700">🔐 Accesos</h2>

        <nav class="flex gap-2 mb-4">
            <button @click="tab = 'empresas'" :class="tab === 'empresas' ? 'border-b-2 border-emerald-600 font-bold' : ''">🏢 Empresa</button>
            <button @click="tab = 'permisos'" :class="tab === 'permisos' ? 'border-b-2 border-emerald-600 font-bold' : ''">🔐 Permisos</button>
        </nav>

        <!-- Empresa -->
        <div x-show="tab === 'empresas'">
            @if (empty($empresas))
                <p class="italic text-red-600">No se encuentra una empresa asignada al usuario.</p>
            @else
                <!-- Aquí iría tu tabla o listado de empresas -->
            @endif
        </div>

        <!-- Permisos -->
        <div x-show="tab === 'permisos'">
<h3 class="mb-2 text-sm font-semibold text-gray-700">Permisos del Rol Seleccionado</h3>

@if (!empty($form['rol_id']))
    @if ($permisos->isEmpty())
        <p class="italic text-gray-600">Este rol no tiene permisos asignados.</p>
    @else
        <table class="w-full text-sm text-left border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2 border">Permiso</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($permisos as $permiso)
                    <tr>
                        <td class="px-3 py-2 border">{{ $permiso->nombre }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endif
</div>
    </div>
</div>


@if ($mostrarMensaje)
<div
    x-data="{ show: true }"
    x-show="show"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click.outside="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Configuracion.usuarios' })"
    @keydown.window.escape="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Configuracion.usuarios' })"
>
    <div class="w-full max-w-sm p-6 text-center bg-white rounded-lg shadow-lg">
        <h2 class="mb-2 text-lg font-semibold text-green-700">✅ Usuario actualizado correctamente</h2>
        <p class="text-sm text-gray-600">Puedes continuar usando el sistema.</p>
        <button
            class="px-4 py-2 mt-4 text-sm text-white rounded bg-emerald-600 hover:bg-emerald-700"
            @click="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Configuracion.usuarios' })"
        >
            Cerrar
        </button>
    </div>
</div>
@endif



