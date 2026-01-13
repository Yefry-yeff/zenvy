<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                <!-- Bandeja de Pedidos Web -->
                <div class="relative">
                    <button onclick="togglePedidos()" class="relative p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                        <!-- Icono de campana -->
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <!-- Badge de notificaciones -->
                        <span id="pedidos-badge" style="display: none;" class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full min-w-[20px]">
                            0
                        </span>
                    </button>
                    
                    <!-- Dropdown de pedidos -->
                    <div id="pedidos-dropdown" style="display: none;" class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
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
                            <a href="{{ route('pedidos-web.index') }}" class="block text-center text-sm text-blue-600 hover:text-blue-800 font-medium">
                                Ver todos los pedidos →
                            </a>
                        </div>
                    </div>
                </div>

                <script>
                    let pedidosDropdownOpen = false;
                    
                    function togglePedidos() {
                        const dropdown = document.getElementById('pedidos-dropdown');
                        pedidosDropdownOpen = !pedidosDropdownOpen;
                        
                        if (pedidosDropdownOpen) {
                            dropdown.style.display = 'block';
                            loadPedidos();
                        } else {
                            dropdown.style.display = 'none';
                        }
                    }
                    
                    function loadPedidos() {
                        fetch('{{ route("pedidos-web.api.pendientes") }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                renderPedidos(data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            document.getElementById('pedidos-container').innerHTML = `
                                <div class="p-4 text-center text-red-500">
                                    <p>Error al cargar pedidos</p>
                                </div>
                            `;
                        });
                    }
                    
                    function renderPedidos(pedidos) {
                        const container = document.getElementById('pedidos-container');
                        
                        if (!pedidos || pedidos.length === 0) {
                            container.innerHTML = `
                                <div class="p-8 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="mt-2">No hay pedidos pendientes</p>
                                </div>
                            `;
                            return;
                        }
                        
                        container.innerHTML = pedidos.map(pedido => `
                            <a href="${pedido.url}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            ${!pedido.leido ? '<span class="text-lg">🔔</span>' : ''}
                                            <span class="font-medium text-gray-900">${pedido.numero_pedido}</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">${pedido.cliente_nombre}</p>
                                        <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                            <span>📦 ${pedido.items_count} items</span>
                                            <span>🕒 ${pedido.created_at}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900">L ${pedido.total}</p>
                                    </div>
                                </div>
                            </a>
                        `).join('');
                    }
                    
                    function updatePedidosBadge() {
                        fetch('/api/v1/orders/unread/count', {
                            headers: {
                                'Authorization': 'Bearer {{ session("api_token") }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && data.data.unread_count > 0) {
                                const badge = document.getElementById('pedidos-badge');
                                badge.textContent = data.data.unread_count;
                                badge.style.display = 'inline-flex';
                            }
                        })
                        .catch(e => console.log('Badge update skipped'));
                    }
                    
                    // Cerrar dropdown al hacer click fuera
                    document.addEventListener('click', function(event) {
                        const dropdown = document.getElementById('pedidos-dropdown');
                        const button = event.target.closest('button[onclick="togglePedidos()"]');
                        
                        if (!button && !dropdown.contains(event.target) && pedidosDropdownOpen) {
                            togglePedidos();
                        }
                    });
                    
                    // Actualizar badge al cargar
                    document.addEventListener('DOMContentLoaded', function() {
                        updatePedidosBadge();
                        setInterval(updatePedidosBadge, 30000); // Cada 30 segundos
                    });
                </script>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
