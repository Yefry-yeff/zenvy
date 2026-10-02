@php
    $componentClass = 'App\\Livewire\\' . str_replace('.', '\\', $vista);
@endphp

<div
    class="relative min-h-[calc(100vh-6rem)]"
    x-data="{
        cargando: false,
        temporizador: null,
        desregistrarHook: null,
        init() {
            this.desregistrarHook = Livewire.hook('commit', ({ component, succeed, fail }) => {
                if (component.name !== 'dynamic-content') {
                    return;
                }

                succeed(() => queueMicrotask(() => this.finalizarCarga()));
                fail(() => this.finalizarCarga());
            });
        },
        destroy() {
            this.desregistrarHook?.();
        },
        iniciarCarga() {
            this.cargando = true;
            clearTimeout(this.temporizador);
            this.temporizador = setTimeout(() => this.cargando = false, 15000);
        },
        finalizarCarga() {
            this.cargando = false;
            clearTimeout(this.temporizador);
        }
    }"
    x-on:cargando-vista.window="iniciarCarga()"
    x-on:vista-cargada.window="finalizarCarga()"
>
    <div
        x-show="cargando"
        x-transition.opacity.duration.75ms
        class="absolute inset-0 z-50 flex items-center justify-center bg-white/80"
        style="display: none;"
        role="status"
        aria-live="polite"
    >
        <div class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded shadow-sm">
            <span class="w-5 h-5 border-2 border-emerald-600 rounded-full border-t-transparent animate-spin" aria-hidden="true"></span>
            <span>Cargando pantalla...</span>
        </div>
    </div>

    {{--  <div class="p-2 mb-2 text-yellow-800 bg-yellow-100">
        Vista solicitada: <strong>{{ $vista }}</strong><br>
        Clase esperada: <strong>{{ $componentClass }}</strong>
        @if(!empty($parametros))
            <br>Parámetros: <strong>{{ json_encode($parametros) }}</strong>
        @endif
    </div>  --}}

    @if (class_exists($componentClass))
        @livewire($vista, $parametros, key($componenteId))
    @else
        <p class="font-semibold text-red-600">❌ Componente Livewire no encontrado para: {{ $vista }}</p>
    @endif
</div>
