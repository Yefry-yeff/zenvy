<x-guest-layout>
    <div class="flex flex-col items-center justify-center min-h-screen px-4 py-12 bg-gradient-to-br from-blue-50 via-white to-blue-100 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800">

        <!-- CARD -->
        <div class="relative z-10 w-full max-w-md p-6 border rounded-lg shadow-lg bg-white/70 dark:bg-gray-800/70 backdrop-blur-md border-white/30">

            <!-- TÍTULO -->
            <h2 class="mb-4 text-xl font-bold text-center text-blue-900 dark:text-white">
                🔐 ¿Olvidaste tu contraseña?
            </h2>

            <!-- MENSAJE -->
            <p class="mb-6 text-sm text-center text-gray-700 dark:text-gray-300">
                No te preocupes, puedes <strong>contactarte con los administradores del sistema</strong> para restablecer tu acceso.
            </p>

            <!-- BOTÓN REGRESAR -->
            <div class="flex justify-center mt-6">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white transition duration-150 ease-in-out rounded-md
                          bg-[#0A1F44] hover:bg-[#081a39] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600">
                    ← Regresar al inicio de sesión
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
