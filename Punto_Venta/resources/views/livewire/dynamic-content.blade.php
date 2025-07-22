<div>
    <div class="p-2 bg-yellow-100 text-yellow-800 mb-2">
        Vista cargando: <strong>{{ $vista }}</strong>
    </div>

    @if (View::exists($vista))
        @include($vista)
    @else
        <p class="text-red-600">❌ Vista no encontrada: {{ $vista }}</p>
    @endif
</div>
