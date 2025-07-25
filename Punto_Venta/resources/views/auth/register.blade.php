<x-guest-layout>
    <div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-gradient-to-br from-blue-50 via-white to-blue-100 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800">

        <div class="w-full max-w-md p-6 text-center border rounded-lg shadow-lg bg-white/70 dark:bg-gray-800/70 backdrop-blur-md border-white/30">
            <h2 class="mb-4 text-xl font-bold text-blue-900 dark:text-white">🚫 Registro deshabilitado</h2>
            <p class="text-sm text-gray-700 dark:text-gray-300">
                El registro de nuevos usuarios está deshabilitado. Si necesitas acceso, por favor contacta al administrador del sistema.
            </p>

            <a href="{{ route('login') }}"
               class="inline-block px-4 py-2 mt-6 text-sm font-semibold text-white transition duration-150 ease-in-out rounded-md
                      bg-[#0A1F44] hover:bg-[#081a39]">
                ← Volver al inicio de sesión
            </a>
        </div>

    </div>
</x-guest-layout>
