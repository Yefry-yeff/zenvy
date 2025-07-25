<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Fuentes --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Estilos Vite --}}
    <link rel="stylesheet" href="{{ asset('build/assets/app-B5wKzLt-.css') }}">

    {{-- Solución para evitar múltiples Alpine (Livewire defer) --}}
    <script>
        window.deferLoadingAlpine = function (callback) {
            window.addEventListener('alpine:initialized', callback);
        };
    </script>

    {{-- Estilos Livewire --}}
    @livewireStyles
</head>

<body
    x-data="{ theme: localStorage.getItem('theme') || 'verde', sidebarOpen: true }"
    x-init="document.documentElement.className = theme"
    x-effect="localStorage.setItem('theme', theme); document.documentElement.className = theme"
    class="flex flex-col h-screen font-sans antialiased"
>

    {{-- BARRA SUPERIOR CON LOGO / TÍTULO --}}
    <div class="flex items-center justify-center gap-3 px-6 py-2 text-base font-semibold text-black bg-white shadow">
        <img src="{{ asset('img/logo-zenvy.png') }}" alt="Logo Zenvy" class="object-contain h-8">
        ZENVY POS v1.0
    </div>

    {{-- ENCABEZADO PRINCIPAL --}}
    <header class="bg-[var(--tema)] text-white flex items-center justify-between px-6 py-2 shadow text-sm">
        <div class="flex items-center gap-3">
            {{-- Botón para retraer menú --}}
            <button @click="sidebarOpen = !sidebarOpen" class="text-white focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-2 px-3 py-1 text-sm text-white transition rounded bg-white/10 hover:bg-white/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l9-9 9 9M4 10v10a1 1 0 001 1h5m6-11v10a1 1 0 001 1h5V10" />
                </svg>
                <span>Inicio</span>
            </a>
        </div>

        {{-- Perfil con dropdown --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="flex items-center gap-2 text-white focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="font-medium">{{ Auth::user()->name }}</span>
            </button>

            <div x-show="open" @click.outside="open = false" x-transition
                 class="absolute right-0 z-50 w-48 mt-2 text-gray-800 bg-white rounded shadow-md">
                <div class="px-4 py-2 border-b">
                    <p class="text-sm font-semibold text-gray-600">Tema</p>
                    <button @click="theme = 'verde'" class="w-full px-2 py-1 text-sm text-left rounded hover:bg-green-100">Verde</button>
                    <button @click="theme = 'azul'" class="w-full px-2 py-1 text-sm text-left rounded hover:bg-blue-100">Azul</button>
                    <button @click="theme = 'oscuro'" class="w-full px-2 py-1 text-sm text-left rounded hover:bg-gray-100">Oscuro</button>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center w-full gap-2 px-4 py-2 text-sm text-gray-700 rounded-b hover:bg-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5" />
                        </svg>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        {{-- MENÚ LATERAL --}}
        <x-sidebar :menu="$sidebarMenu" />

        {{-- CONTENIDO PRINCIPAL --}}
        <main
            :class="sidebarOpen ? 'ml-0' : 'ml-0 w-full'"
            class="flex-1 p-6 overflow-y-auto transition-all duration-200 bg-white"
        >
            {{-- Componente dinámico Livewire --}}
            @livewire('dynamic-content')
        </main>
    </div>

    {{-- Scripts Livewire --}}
    @livewireScripts

    {{-- Scripts Vite que incluye Alpine (solo uno, al final) --}}
    <script type="module" src="{{ asset('build/assets/app-DNxiirP_.js') }}"></script>
</body>
</html>
