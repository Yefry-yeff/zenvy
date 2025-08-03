@php
    $componentClass = 'App\\Livewire\\' . str_replace('.', '\\', $vista);
@endphp

<div>
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
