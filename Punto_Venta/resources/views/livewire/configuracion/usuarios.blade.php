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
    <h5 class="mb-0">Gestión de Usuarios</h5>
 <button
    x-on:click="$dispatch('cambiarVista', { ruta: 'Configuracion.usuariosform' })"
    class="px-3 py-1 text-sm text-gray-800 no-underline bg-white rounded hover:bg-gray-100"
>
    ➕ Agregar Usuario
</button>
</div>

    {{-- Tabla de usuarios --}}
    <div class="overflow-x-auto bg-white">
        <table class="min-w-full text-sm text-left border border-gray-300">
            <thead class="bg-gray-100 border-b border-gray-300">
                <tr class="text-xs font-semibold text-gray-700 uppercase">
                    <th class="px-4 py-2 border">NOMBRE</th>
                    <th class="px-4 py-2 border">CORREO</th>
                    <th class="px-4 py-2 border">DIRECCIÓN</th>
                    <th class="px-4 py-2 border">TELÉFONO</th>
                    <th class="px-4 py-2 border">ROL</th>
                    <th class="px-4 py-2 border">ESTADO</th>
                </tr>
            </thead>
            <tbody class="text-sm text-gray-800 bg-white divide-y divide-gray-200">
                @forelse ($usuarios as $usuario)
                    <tr
                        wire:click="editar({{ $usuario->id }})"
                        class="transition cursor-pointer hover:bg-gray-100"
                    >
                        <td class="px-4 py-2 border-t">{{ $usuario->nombre }}</td>
                        <td class="px-4 py-2 border-t">{{ $usuario->correo }}</td>
                        <td class="px-4 py-2 border-t">{{ $usuario->direccion }}</td>
                        <td class="px-4 py-2 border-t">{{ $usuario->telefono }}</td>
                        <td class="px-4 py-2 border-t">{{ $usuario->rol }}</td>
                        <td class="px-4 py-2 text-center border-t">
                            @if ($usuario->estado)
                                <span class="inline-block px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded">Activo</span>
                            @else
                                <span class="inline-block px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-4 italic text-center text-gray-500">
                            No hay usuarios registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mensaje de éxito --}}
    @if (session()->has('mensaje'))
        <div class="p-3 mt-4 text-green-700 bg-green-100 border border-green-400 rounded">
            {{ session('mensaje') }}
        </div>
    @endif

</div>
