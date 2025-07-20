<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Estilos + Scripts con Vite -->
      <link rel="stylesheet" href="{{ asset('build/assets/app-D14tntRX.css') }}">
     <script type="module" src="{{ asset('build/assets/app-DaBYqt0m.js') }}"></script>
</head>

<body
    x-data="{ theme: localStorage.getItem('theme') || 'verde', sidebarOpen: true }"
    x-init="document.documentElement.className = theme"
    x-effect="localStorage.setItem('theme', theme); document.documentElement.className = theme"
    class="font-sans antialiased h-screen flex flex-col"
>

  {{-- BARRA SUPERIOR CON LOGO / TÍTULO --}}
<div class="bg-white text-black px-6 py-2 text-base font-semibold shadow flex items-center justify-center gap-3">
    <img src="{{ asset('img/logo-zenvy.png') }}" alt="Logo Zenvy" class="h-8 object-contain">
    ZENVY POS v1.0
</div>

{{-- ENCABEZADO PRINCIPAL (Botón menú y Dashboard, y Perfil) --}}
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
   class="flex items-center gap-2 px-3 py-1 rounded bg-white/10 hover:bg-white/20 transition text-white text-sm">
   <!-- Icono SVG de casa -->
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

        {{-- Dropdown --}}
        <div x-show="open" @click.away="open = false" x-transition
             class="absolute right-0 mt-2 w-48 bg-white shadow-md rounded z-50 text-gray-800">
            <div class="px-4 py-2 border-b">
                <p class="text-sm font-semibold text-gray-600">Tema</p>
                <button @click="theme = 'verde'" class="w-full text-left text-sm hover:bg-green-100 px-2 py-1 rounded">Verde</button>
                <button @click="theme = 'azul'" class="w-full text-left text-sm hover:bg-blue-100 px-2 py-1 rounded">Azul</button>
                <button @click="theme = 'oscuro'" class="w-full text-left text-sm hover:bg-gray-100 px-2 py-1 rounded">Oscuro</button>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b">
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
        <!-- MENÚ LATERAL -->
        <x-sidebar />

        <!-- CONTENIDO PRINCIPAL -->
        <main
            :class="sidebarOpen ? 'ml-0' : 'ml-0 w-full'"
            class="flex-1 overflow-y-auto p-6 bg-white transition-all duration-200"
        >
            @isset($header)
                <div class="text-xl font-semibold text-gray-700 mb-4">
                    {{ $header }}
                </div>
            @endisset

            {{ $slot }}
        </main>
    </div>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
