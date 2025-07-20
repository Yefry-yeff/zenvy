<div x-data="{ open: false, theme: localStorage.getItem('theme') || 'verde' }"
     x-init="document.documentElement.className = theme"
     x-effect="localStorage.setItem('theme', theme); document.documentElement.className = theme"
     class="relative">

    <button @click="open = !open"
            class="flex items-center gap-2 text-sm text-white focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24"
             stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span class="font-medium">{{ Auth::user()->name }}</span>
    </button>

    <!-- Dropdown -->
    <div x-show="open" @click.away="open = false" x-transition
         class="absolute right-0 mt-2 w-48 bg-white shadow-md rounded z-50 text-gray-800 border-t-4"
         :class="{
            'border-[#047857]': theme === 'verde',
            'border-[#1D4ED8]': theme === 'azul',
            'border-[#1F2937]': theme === 'oscuro'
         }">

        <!-- Cambiar tema -->
        <div class="px-4 py-2 border-b">
            <p class="text-sm font-semibold text-gray-600">Tema</p>
            <template x-for="(label, value) in { verde: 'Verde', azul: 'Azul', oscuro: 'Oscuro' }" :key="value">
                <button
                    @click="theme = value"
                    class="w-full text-left text-sm px-2 py-1 rounded"
                    :class="theme === value
                        ? 'bg-[var(--tema)] text-white font-semibold'
                        : 'hover:bg-gray-100 text-gray-700'"
                    x-text="label"
                ></button>
            </template>
        </div>

        <!-- Cerrar sesión -->
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
