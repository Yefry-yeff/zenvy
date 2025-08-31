<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Fuentes --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Tom Select --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

    {{-- Estilos compilados con Vite --}}
    <link rel="stylesheet" href="{{ asset('build/assets/app-FFQddNSj.css') }}">

    {{-- Bootstrap 5 CSS (sin integrity para evitar error) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- DataTables CSS --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

    {{-- Estilos personalizados para cámara escáner --}}
    <link href="{{ asset('css/camera-scanner.css') }}" rel="stylesheet" />

    {{-- Livewire --}}
    @livewireStyles
    @stack('styles')
</head>

<body
    x-data="{ theme: localStorage.getItem('theme') || 'verde', sidebarOpen: true }"
    x-init="document.documentElement.className = theme"
    x-effect="localStorage.setItem('theme', theme); document.documentElement.className = theme"
    class="flex flex-col h-screen font-sans antialiased"
>
    {{-- ENCABEZADO --}}
    <header
    :class="theme === 'verde' ? 'bg-emerald-600/80' :
            theme === 'azul' ? 'bg-blue-600/80' :
            theme === 'oscuro' ? 'bg-gray-900/80' : 'bg-slate-700/80'"
    class="flex items-center justify-between px-6 py-2 text-white transition-all duration-300 shadow-md backdrop-blur-md"
>
    {{-- IZQUIERDA: botón menú y logo --}}
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="text-white focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <div class="flex items-center gap-2 text-base font-semibold">
            <div class="p-1 bg-white rounded-xl">
                <img src="{{ asset('img/logo-zenvy.png') }}" alt="Logo Zenvy" class="object-contain w-auto h-8">
            </div>
            <span class="text-white">ZENVY POS v1.0</span>
        </div>
    </div>

    {{-- DERECHA: Perfil con dropdown de opciones --}}
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open" class="flex items-center gap-2 text-white focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="font-medium">{{ Auth::user()->name }}</span>
        </button>

        <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 z-50 w-48 mt-2 text-gray-800 bg-white rounded shadow-md">
            <div class="px-4 py-2 border-b">
                <p class="text-sm font-semibold text-gray-600">Tema</p>
                <button @click="theme = 'verde'" class="w-full px-2 py-1 text-sm text-left rounded hover:bg-green-100">Verde</button>
                <button @click="theme = 'azul'" class="w-full px-2 py-1 text-sm text-left rounded hover:bg-blue-100">Azul</button>
                <button @click="theme = 'oscuro'" class="w-full px-2 py-1 text-sm text-left rounded hover:bg-gray-100">Oscuro</button>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center w-full gap-2 px-4 py-2 text-sm text-gray-700 rounded-b hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5" />
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</header>
    {{-- CONTENIDO --}}
    <div class="flex flex-1 overflow-hidden">
        <x-sidebar :menu="$sidebarMenu" />
        <main :class="sidebarOpen ? 'ml-0' : 'ml-0 w-full'" class="flex-1 p-6 overflow-y-auto transition-all duration-200 bg-white">
            @livewire('dynamic-content')
        </main>
    </div>


    {{-- Livewire scripts --}}
    @livewireScripts

    {{-- App JS compilado con Vite --}}
    <script type="module" src="{{ asset('build/assets/app-BLl8G-P3.js') }}"></script>

    {{-- jQuery y DataTables JS CDN --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- Tom Select --}}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <script src="{{ asset('JS/Script/TablasBoostrap/listafacturas.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/compra.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/sucursales.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/clientes.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/departamento.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/municipios.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/producto-seccion.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/marca.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/cai.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/categoria.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/subcategoria.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/unidades.js') }}"></script>
    <script src="{{ asset('JS/Script/TablasBoostrap/productos.js') }}"></script>
    <!-- MODAL DE SESIÓN EXPIRADA -->
    <div
        x-show="showModal"
        x-data="{
            showModal: false,
            timeout: null,
            resetTimer() {
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => this.showModal = true, 10 * 60 * 1000); // 10 minutos
            },
            cerrarSesion() {
                window.location.href = '{{ route('logout') }}';
            },
            init() {
                this.resetTimer();
                ['mousemove', 'keydown', 'click', 'scroll'].forEach(evt =>
                    window.addEventListener(evt, () => this.resetTimer())
                );
            }
        }"
        x-init="init()"
        x-on:keydown.escape.window="if (showModal) cerrarSesion()"
        x-on:keydown.enter.window="if (showModal) cerrarSesion()"
        @click.outside="cerrarSesion()"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-50"
        style="display: none;"
    >
        <div class="w-full max-w-sm p-6 text-center bg-white rounded-lg shadow-lg">
            <h2 class="mb-2 text-lg font-semibold text-red-700">⏳ Sesión Expirada</h2>
            <p class="text-sm text-gray-600">Tu sesión ha expirado por inactividad.</p>
            <button
                class="px-4 py-2 mt-4 text-sm text-white rounded"
                :class="{
                    'bg-emerald-600 hover:bg-emerald-700': theme === 'verde',
                    'bg-blue-600 hover:bg-blue-700': theme === 'azul',
                    'bg-gray-900 hover:bg-gray-800': theme === 'oscuro',
                    'bg-slate-700 hover:bg-slate-600': theme !== 'verde' && theme !== 'azul' && theme !== 'oscuro'
                }"
                @click="cerrarSesion()"
            >
                Aceptar
            </button>
        </div>
    </div>


    {{-- Scripts adicionales --}}
    {{-- Bootstrap 5 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- ZXing Library para escáner de códigos de barras --}}
    <script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>

    {{-- Script personalizado para escáner de códigos de barras --}}
    <script src="{{ asset('js/barcode-scanner.js') }}"></script>

    @stack('scripts')

</body>
</html>
