<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('img/favicon/favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/favicon/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('img/favicon/favicon-96x96.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('img/favicon/site.webmanifest') }}">

    {{-- Fuentes --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Tom Select --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

    {{-- Estilos compilados con Vite --}}
    <link rel="stylesheet" href="{{ asset('build/assets/app-B6dSsrj3.css') }}">

    {{-- Bootstrap 5 CSS (sin integrity para evitar error) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- DataTables CSS --}}
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

    {{-- FontAwesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
    class="flex items-center justify-between px-6 py-2 text-white transition-all duration-300 shadow-md backdrop-blur-md z-[99999] relative"
>
    {{-- IZQUIERDA: botón menú y logo --}}
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="text-white focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <div class="flex items-center gap-2 text-base font-semibold">
            <div class="flex flex-col items-start gap-0">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('img/Logo_Paperland2.png') }}" alt="Logo Paperland" class="object-contain w-8 h-8">
                    <span class="text-xl font-semibold text-white">Paperland</span>
                </div>
                <span class="text-[0.65rem] text-white/80 -mt-1 ml-10">imagina · crea · diviértete</span>
            </div>
        </div>
    </div>

    {{-- DERECHA: Bandeja de Pedidos y Perfil --}}
    <div class="flex items-center gap-4">
        {{-- Bandeja de Pedidos Web --}}
        <div class="relative">
            <button onclick="togglePedidos(event)" class="relative p-2 text-white hover:text-white/80 focus:outline-none">
                {{-- Icono de campana --}}
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                {{-- Badge de notificaciones --}}
                <span id="pedidos-badge" style="display: none;" class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full min-w-[20px]">
                    0
                </span>
            </button>
            
            {{-- Dropdown de pedidos --}}
            <div id="pedidos-dropdown" style="display: none; position: fixed;" class="w-96 bg-white rounded-lg shadow-2xl border border-gray-200 z-[99999]">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Pedidos Web Pendientes</h3>
                    <p class="text-sm text-gray-500">Nuevas solicitudes de e-commerce</p>
                </div>
                <div class="max-h-96 overflow-y-auto" id="pedidos-container">
                    <div class="p-4 text-center text-gray-500">
                        <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2">Cargando pedidos...</p>
                    </div>
                </div>
                <div class="p-3 border-t border-gray-200 bg-gray-50">
                    <button onclick="window.Livewire.dispatch('cambiarVista', ['BandejaPedidos']); togglePedidos(event);" class="w-full text-sm text-blue-600 hover:text-blue-800 font-medium text-left">
                        Ver todos los pedidos →
                    </button>
                </div>
            </div>
        </div>

        {{-- Perfil con dropdown de opciones --}}
        <div x-data="{ 
            open: false,
            toggle(event) {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => {
                        const button = event.currentTarget;
                        const dropdown = this.$refs.dropdown;
                        const rect = button.getBoundingClientRect();
                        dropdown.style.top = (rect.bottom + 8) + 'px';
                        dropdown.style.right = (window.innerWidth - rect.right) + 'px';
                    });
                }
            }
        }" class="relative">
            <button @click="toggle($event)" class="flex items-center gap-2 text-white focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="font-medium">{{ Auth::user()->name }}</span>
            </button>

        <div x-show="open" x-ref="dropdown" @click.outside="open = false" x-transition style="position: fixed;" class="z-[99999] w-48 text-gray-800 bg-white rounded shadow-2xl">
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

    {{-- Dashboard Events para gráficos (inline para asegurar que se ejecute) --}}
    <script>
        document.addEventListener('livewire:init', () => {
            // Escuchar eventos de Livewire
            Livewire.on('dashboardRenderizado', () => {
                if (typeof window.chartsInitialized !== 'undefined' && window.chartsInitialized) {
                    if (typeof destroyCharts === 'function') {
                        destroyCharts();
                    }
                }
                setTimeout(() => {
                    if (typeof initCharts === 'function') {
                        initCharts();
                    }
                }, 150);
            });

            Livewire.on('datosActualizados', () => {
                if (typeof window.chartsInitialized !== 'undefined' && window.chartsInitialized) {
                    if (typeof destroyCharts === 'function') {
                        destroyCharts();
                    }
                }
                setTimeout(() => {
                    if (typeof initCharts === 'function') {
                        initCharts();
                    }
                }, 150);
            });
        });
    </script>

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
            lastActivity: Date.now(),
            sessionTimeout: 8 * 60 * 60 * 60 * 60 * 60 * 60 * 1000, // 2 horas
            resetTimer() {
                if (this.timeout) {
                    clearTimeout(this.timeout);
                    this.timeout = null;
                }
                this.lastActivity = Date.now();
                this.timeout = setTimeout(() => {
                    // Verificar si realmente pasó el tiempo sin optimizar
                    if (Date.now() - this.lastActivity >= this.sessionTimeout) {
                        this.showModal = true;
                    }
                }, this.sessionTimeout);
            },
            cerrarSesion() {
                window.location.href = '{{ route('logout') }}';
            },
            init() {
                this.resetTimer();
                // Usar throttling para evitar resetear el timer muy frecuentemente
                let throttle = false;
                const throttledReset = () => {
                    if (!throttle) {
                        throttle = true;
                        this.resetTimer();
                        setTimeout(() => { throttle = false; }, 1000); // Throttle de 1 segundo
                    }
                };
                ['mousemove', 'keydown', 'click', 'scroll'].forEach(evt =>
                    window.addEventListener(evt, throttledReset, { passive: true })
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

    {{-- Script de Bandeja de Pedidos --}}
    <script>
        let pedidosDropdownOpen = false;
        let dropdownMovedToBody = false;

        function togglePedidos(event) {
            const dropdown = document.getElementById('pedidos-dropdown');
            const button = event ? event.currentTarget : document.querySelector('button[onclick*="togglePedidos"]');
            pedidosDropdownOpen = !pedidosDropdownOpen;
            
            if (pedidosDropdownOpen) {
                // Mover el dropdown al body si aún no se ha movido para asegurar z-index correcto
                if (!dropdownMovedToBody) {
                    document.body.appendChild(dropdown);
                    dropdownMovedToBody = true;
                }
                
                // Calcular posición del botón
                const rect = button.getBoundingClientRect();
                dropdown.style.top = (rect.bottom + 8) + 'px';
                dropdown.style.right = (window.innerWidth - rect.right) + 'px';
                dropdown.style.display = 'block';
                loadPedidos();
            } else {
                dropdown.style.display = 'none';
            }
        }

        function loadPedidos() {
            const container = document.getElementById('pedidos-container');
            
            fetch('/pedidos-web/api/pendientes', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderPedidos(data.data);
                } else {
                    container.innerHTML = `
                        <div class="p-4 text-center text-red-500">
                            <p>Error al cargar pedidos</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                container.innerHTML = `
                    <div class="p-4 text-center text-red-500">
                        <p>Error de conexión</p>
                    </div>
                `;
            });
        }

        function renderPedidos(pedidos) {
            const container = document.getElementById('pedidos-container');
            
            if (!pedidos || pedidos.length === 0) {
                container.innerHTML = `
                    <div class="p-8 text-center text-gray-500">
                        <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="mt-2 text-sm">No hay pedidos pendientes</p>
                    </div>
                `;
                return;
            }
            
            const pedidosHtml = pedidos.map(pedido => `
                <button onclick="verDetallePedido(${pedido.id}); return false;" class="w-full text-left block p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                ${!pedido.leido ? '<span class="text-blue-600">🔔</span>' : ''}
                                <span class="font-semibold text-gray-800">${pedido.numero_pedido}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">${pedido.cliente_nombre}</p>
                            <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                <span>📦 ${pedido.items_count} items</span>
                                <span>⏰ ${pedido.fecha_pedido}</span>
                            </div>
                        </div>
                        <div class="text-right ml-2">
                            <p class="text-lg font-bold text-gray-800">L ${pedido.total}</p>
                        </div>
                    </div>
                </button>
            `).join('');
            
            container.innerHTML = pedidosHtml;
        }

        function verDetallePedido(pedidoId) {
            // Cerrar el dropdown
            pedidosDropdownOpen = false;
            document.getElementById('pedidos-dropdown').style.display = 'none';
            
            // Abrir el detalle en una nueva pestaña
            window.open('/pedidos-web/' + pedidoId, '_blank');
        }

        function updatePedidosBadge() {
            fetch('/api/v1/orders/unread/count', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.unread_count > 0) {
                    const badge = document.getElementById('pedidos-badge');
                    badge.textContent = data.data.unread_count;
                    badge.style.display = 'inline-flex';
                } else {
                    const badge = document.getElementById('pedidos-badge');
                    badge.style.display = 'none';
                }
            })
            .catch(error => {
                console.log('Badge update skipped:', error);
            });
        }

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('pedidos-dropdown');
            const button = event.target.closest('button[onclick*="togglePedidos"]');
            
            if (!button && dropdown && !dropdown.contains(event.target) && pedidosDropdownOpen) {
                pedidosDropdownOpen = false;
                dropdown.style.display = 'none';
            }
        });

        // Cargar el contador de badges al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            updatePedidosBadge();
            // Actualizar cada 30 segundos
            setInterval(updatePedidosBadge, 30000);
        });
    </script>

    @stack('scripts')

</body>
</html>
