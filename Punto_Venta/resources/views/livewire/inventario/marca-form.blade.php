<div
    class="overflow-hidden border border-gray-300 rounded shadow"
    x-data="{ sinCambiosModal: false }"
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
        <h5>{{ $marcaId ? 'Editar Marca' : 'Crear Marca' }}</h5>
        <button wire:click="guardar" class="px-3 py-1 text-sm text-gray-800 bg-white rounded hover:bg-gray-100">
            💾 Guardar
        </button>
    </div>

    <!-- DATOS DE LA MARCA -->
    <div class="p-4">
        <div class="p-4 bg-white border shadow rounded-xl">
            <h2 class="mb-4 text-lg font-semibold text-gray-700">🏷️ Datos de la Marca</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Nombre de la Marca<span class="text-red-600">*</span></label>
                <input type="text" wire:model.defer="form.nombre" class="w-full px-3 py-2 border rounded" />
                @error('form.nombre')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <!-- MENSAJE DE ÉXITO -->
    @if ($mostrarMensaje)
    <div
        x-data="{ show: true }"
        x-show="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        @click.outside="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Inventario.marca' })"
        @keydown.window.escape="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Inventario.marca' })"
    >
        <div class="w-full max-w-sm p-6 text-center bg-white rounded-lg shadow-lg">
            <h2 class="mb-2 text-lg font-semibold text-green-700">✅ Marca guardada correctamente</h2>
            <p class="text-sm text-gray-600">Los cambios se han guardado exitosamente.</p>
            <button
                class="px-4 py-2 mt-4 text-sm text-white rounded bg-emerald-600 hover:bg-emerald-700"
                @click="show = false; Livewire.dispatch('cambiarVista', { ruta: 'Inventario.marca' })"
            >
                Cerrar
            </button>
        </div>
    </div>
    @endif

    <!-- MENSAJES DE SESIÓN -->
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

</div>
