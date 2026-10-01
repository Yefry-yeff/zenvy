@php
    $componentClass = 'App\\Livewire\\' . str_replace('.', '\\', $vista);
@endphp

<div class="relative min-h-48">
    <div
        wire:loading.delay.shortest.flex
        wire:target="cambiarVista"
        class="absolute inset-0 z-50 items-center justify-center bg-white/85 backdrop-blur-sm"
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
