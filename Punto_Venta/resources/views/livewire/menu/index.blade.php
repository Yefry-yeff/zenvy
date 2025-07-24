<div>
    {{-- A good traveler has no fixed plans and is not intent upon arriving. --}}
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
        <h5 class="mb-0">Gestión de Menús</h5>
        <button wire:click="abrirModal" class="px-3 py-1 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
            ➕ Agregar Menú
        </button>
    </div>

    {{-- Tabla de menús --}}
   <div class="overflow-x-auto bg-white">
<table class="min-w-full text-sm text-left border border-gray-300">
       <thead class="bg-gray-100 border-b border-gray-300">
            <!-- ENCABEZADOS -->
             <tr class="text-xs font-semibold text-gray-700 uppercase">
                <th class="px-4 py-2 border">MENU</th>
                <th class="px-4 py-2 border">SUB_MENU</th>
                <th class="px-4 py-2 border">ICONO</th>
                <th class="px-4 py-2 border">SECUENCIA</th>
                <th class="px-4 py-2 border">ESTADO</th>
            </tr>
        </thead>

       <tbody class="text-sm text-gray-800 bg-white divide-y divide-gray-200">
    @forelse ($menus as $menu)
        <tr
            wire:click="abrirModal({{ $menu->id }})"
            class="transition cursor-pointer hover:bg-gray-100"
        >
            <td class="px-4 py-2 border-t">{{ $menu->menu }}</td>
            <td class="px-4 py-2 border-t">{{ $menu->txt_comentario }}</td>
            <td class="px-4 py-2 text-center border-t">{{ $menu->icon }}</td>
            <td class="px-4 py-2 text-center border-t">{{ $menu->orden }}</td>
            <td class="px-4 py-2 text-center border-t">
                @if($menu->estado)
                    <span class="inline-block px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded">Activo</span>
                @else
                    <span class="inline-block px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded">Inactivo</span>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="5" class="px-4 py-4 italic text-center text-gray-500">
                No hay menús disponibles.
            </td>
        </tr>
    @endforelse
</tbody>
    </table>

    {{-- Mensaje de éxito --}}
    @if (session()->has('mensaje'))
        <div class="p-3 mt-4 text-green-700 bg-green-100 border border-green-400 rounded">
            {{ session('mensaje') }}
        </div>
    @endif

    {{-- Modal --}}
    @if($modalOpen)
         <div
        x-data="{
            open: true,
            theme: localStorage.getItem('theme') || 'verde',
            modo: @entangle('modo'),
            submitted: @entangle('submitted'),
            touched: false
        }"
        x-init="$watch('submitted', val => { if (!val) touched = false })"
        x-show="open"
        @click.outside="$wire.cerrarModal()"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 overflow-y-auto bg-black bg-opacity-50"
    >
           <div
    class="relative w-full max-w-xl p-6 bg-white rounded shadow-lg"
    @click.outside="$wire.set('modalOpen', false)"
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
                    {{ $modo === 'crear' ? 'Agregar Menú' : 'Modificar Menú' }}
                </h2>

                <div x-data="{ modo: @entangle('modo'), submitted: @entangle('submitted') }">
                    {{-- Grupo de menú --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Grupo de menú <span class="text-red-600" x-show="modo === 'crear'">*</span>
                    </label>

                    <template x-if="modo === 'crear'">
                        <input
                            list="grupos"
                            wire:model.defer="form.menu_grupo"
                            class="w-full px-3 py-2 border rounded"
                            placeholder="Ej: Configuración"
                        >
                    </template>

                    <template x-if="modo === 'editar'">
                        <input
                            type="text"
                            wire:model.defer="form.menu_grupo"
                            disabled
                            class="w-full px-3 py-2 bg-gray-100 border rounded cursor-not-allowed"
                        >
                    </template>

                    <datalist id="grupos">
                        @foreach ($menuGrupos as $grupo)
                            <option value="{{ $grupo }}"></option>
                        @endforeach
                    </datalist>

                    @if ($submitted && $errors->has('form.menu_grupo'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->first('form.menu_grupo') }}</p>
                    @endif
                </div>

                    {{-- Submenú --}}
                    <div class="mb-4" x-data="{ touched: false }">
                        <label class="block text-sm font-medium text-gray-700">
                            Submenú <span class="text-red-600" x-show="modo === 'crear'">*</span>
                        </label>

                        <input
                            type="text"
                            wire:model.defer="form.txt_comentario"
                            :disabled="modo === 'editar'"
                            @focus="if (modo === 'editar') touched = true"
                            @click="if (modo === 'editar') touched = true"
                            class="w-full px-3 py-2 border rounded disabled:bg-gray-100 disabled:cursor-not-allowed"
                            placeholder="Ej: Usuarios"
                        >

                        <p x-show="touched && modo === 'editar'" x-transition class="mt-1 text-sm text-red-500">Campo bloqueado</p>

                        @if ($submitted && $errors->has('form.txt_comentario'))
                            <p class="mt-1 text-sm text-red-600">{{ $errors->first('form.txt_comentario') }}</p>
                        @endif
                    </div>

                    {{-- Ícono --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">
                            Ícono (emoji)
                        </label>
                        <input
                            type="text"
                            wire:model.defer="form.icon"
                            class="w-full px-3 py-2 border rounded"
                            placeholder="Ej: 👤"
                        >
                    </div>

                    {{-- Orden --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">
                            Orden <span class="text-red-600" x-show="modo === 'crear'">*</span>
                        </label>
                        <input
                            type="number"
                            wire:model.defer="form.orden"
                            class="w-full px-3 py-2 border rounded"
                            min="1"
                        >
                        @if ($submitted && $errors->has('form.orden'))
                            <p class="mt-1 text-sm text-red-600">{{ $errors->first('form.orden') }}</p>
                        @endif
                    </div>

                    {{-- Estado --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">
                            Estado <span class="text-red-600" x-show="modo === 'crear'">*</span>
                        </label>
                        <select wire:model.defer="form.estado" class="w-full px-3 py-2 border rounded">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                        @if ($submitted && $errors->has('form.estado'))
                            <p class="mt-1 text-sm text-red-600">{{ $errors->first('form.estado') }}</p>
                        @endif
                    </div>

                {{-- Botones --}}
                <div class="flex justify-end mt-6 space-x-2">

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
                    <span wire:loading.remove>{{ $modo === 'crear' ? 'Agregar' : 'Modificar' }}</span>
                    <span wire:loading>Guardando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
