<x-guest-layout>
    <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 px-4 py-8">

        <!-- LOGO animado con entrada -->
        <div class="mb-6 text-center">
            <img id="logo" src="{{ asset('img/LOGO_VALENCIA.jpg') }}"
                 class="opacity-0 translate-x-[-100px] transition-all duration-700 ease-out w-48 sm:w-56 md:w-64 h-auto rounded-full object-contain"
                 alt="Logo" />
        </div>

        <!-- Login Card -->
        <div class="w-full max-w-md bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md animate__animated animate__fadeIn">

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
                    <x-text-input id="email" class="block mt-1 w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                  type="email" name="email" :value="old('email')" required autofocus />
                </div>

                <!-- Password -->
                <div>
                    <x-input-label for="password" :value="__('Contraseña')" class="dark:text-white"/>
                    <x-text-input id="password" class="block mt-1 w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                  type="password" name="password" required autocomplete="current-password" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input id="remember_me" type="checkbox" name="remember"
                           class="rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500 dark:bg-gray-700 dark:border-gray-600">
                    <label for="remember_me" class="ml-2 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('Recuérdame') }}
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mt-4 space-y-2 sm:space-y-0">
                    @if (Route::has('password.request'))
                        <a class="text-sm text-orange-600 hover:underline dark:text-orange-400" href="{{ route('password.request') }}">
                            {{ __('He olvidado mi contraseña') }}
                        </a>
                    @endif

                    <button id="submitBtn" type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 bg-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600 transition ease-in-out duration-150">
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
            btn.innerHTML = `<svg class="animate-spin h-4 w-4 mr-2 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                             </svg> Entrando...`;
            return true;
        }
    </script>
</x-guest-layout>
