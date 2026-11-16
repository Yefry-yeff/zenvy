<x-guest-layout>
    <div class="relative flex flex-col items-center justify-center min-h-screen px-4 py-8 overflow-hidden bg-gradient-to-br from-blue-50 via-white to-blue-100 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800">

        <!-- FONDO DIFUMINADO CON ANIMACIÓN -->
<div class="absolute inset-0 z-0 overflow-hidden">
    <div class="absolute w-96 h-96 bg-blue-300 opacity-30 rounded-full filter blur-3xl animate-pulse-slow top-[-20%] left-[-10%]"></div>
    <div class="absolute w-96 h-96 bg-blue-200 opacity-30 rounded-full filter blur-2xl animate-float-slow bottom-[-10%] right-[-10%]"></div>
</div>

        <!-- LOGO animado con entrada -->
        <div class="mb-6 text-center">
            <img id="logo" src="{{ asset('img/zenvy.png') }}"
                 class="opacity-0 translate-x-[-100px] transition-all duration-700 ease-out w-48 sm:w-56 md:w-64 h-auto rounded-full object-contain"
                 alt="Logo" />
        </div>

        <!-- Login Card con Glassmorphism -->
<div class="relative z-10 w-full max-w-md p-6 border shadow-xl rounded-xl backdrop-blur-md bg-white/40 dark:bg-gray-800/40 border-white/30">
            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <!-- Validation Errors -->
            <x-auth-validation-errors class="mb-4" :errors="$errors" />

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4" onsubmit="return animateSubmit(this)">
                @csrf

                <!-- Email -->
                <div>
                    <x-input-label for="email" :value="__('Correo Electrónico')" class="dark:text-white"/>
                    <x-text-input id="email" class="block w-full mt-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                  type="email" name="email" :value="old('email')" required autofocus />
                </div>

                <!-- Password -->
                <div>
                    <x-input-label for="password" :value="__('Contraseña')" class="dark:text-white"/>
                    <x-text-input id="password" class="block w-full mt-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                  type="password" name="password" required autocomplete="current-password" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input id="remember_me" type="checkbox" name="remember"
                           class="text-orange-600 border-gray-300 rounded shadow-sm focus:ring-orange-500 dark:bg-gray-700 dark:border-gray-600">
                    <label for="remember_me" class="ml-2 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('Recuérdame') }}
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex flex-col mt-4 space-y-2 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                    @if (Route::has('password.request'))
                    <a class="text-sm text-[#0A1F44] hover:underline dark:text-[#0A1F44]" href="{{ route('password.request') }}">
                        {{ __('He olvidado mi contraseña') }}
                    </a>
                    @endif

                    <button id="submitBtn" type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-[#0A1F44] border border-transparent rounded-md hover:bg-[#081a39]">
                        {{ __('Entrar') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Animación inicial del logo
        window.addEventListener('DOMContentLoaded', () => {
            const logo = document.getElementById('logo');
            setTimeout(() => {
                logo.classList.remove('opacity-0', 'translate-x-[-100px]');
                logo.classList.add('opacity-100', 'translate-x-0');
            }, 100);
        });

        // Animación de envío del formulario
        function animateSubmit(form) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = `<svg class="inline-block w-4 h-4 mr-2 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                             </svg> Entrando...`;
            return true;
        }
    </script>
</x-guest-layout>
