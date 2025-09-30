<table>
    <thead>
        <tr>
            <th colspan="10" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #4472C4; color: white;">
                📦 LISTADO DE PRODUCTOS RECIBIDOS EN BODEGA
            </th>
        </tr>
        <tr>
            <th colspan="10" style="font-size: 12px; text-align: center; color: #666;">
                Sistema ZENVY - Gestión de Inventario
            </th>
        </tr>
        <tr><th colspan="10"></th></tr>
        <tr>
            <th colspan="10" style="font-size: 10px; text-align: center; background-color: #F8F9FA;">
                📅 Generado el: {{ $fechaGeneracion }} | 👤 Usuario: {{ $usuarioReporte ?? '-' }} | 📊 Total: {{ $totalProductos }} productos | 🔍 Filtros: {{ $filtrosAplicados }}
            </th>
        </tr>
        <tr>
            <th style="background:#4472C4;color:#fff;">#</th>
            <th style="background:#4472C4;color:#fff;">ID Producto</th>
            <th style="background:#4472C4;color:#fff;">Producto</th>
            <th style="background:#4472C4;color:#fff;">Código de Barras</th>
            <th style="background:#4472C4;color:#fff;">Marca</th>
            <th style="background:#4472C4;color:#fff;">Bodega</th>
            <th style="background:#4472C4;color:#fff;">Estado</th>
            <th style="background:#4472C4;color:#fff;">Cantidad</th>
            <th style="background:#4472C4;color:#fff;">Unidad</th>
            <th style="background:#4472C4;color:#fff;">Fecha recibido</th>
        </tr>
    </thead>
    <tbody>
        @foreach($productos as $i => $producto)
        <tr>
            <td>{{ $i+1 }}</td>
            <td>{{ $producto->producto_id ?? '-' }}</td>
            <td>{{ $producto->producto_nombre ?? '-' }}</td>
            <td>{{ isset($producto->codigo_barra) && $producto->codigo_barra !== '' ? "'" . $producto->codigo_barra : '-' }}</td>
            <td>{{ $producto->marca_nombre ?? '-' }}</td>
            <td>{{ $producto->bodega_nombre ?? '-' }}</td>
            <td>
                @if(isset($producto->cantidad_disponible))
                    @if($producto->cantidad_disponible > 10)
                        Disponible
                    @elseif($producto->cantidad_disponible > 0)
                        Poco stock
                    @else
                        Agotado
                    @endif
                @else
                    -
                @endif
            </td>
            <td>{{ $producto->cantidad_disponible ?? 0 }}</td>
            <td>{{ $producto->unidad_medida ?? '-' }}</td>
            <td>{{ $producto->fecha_recibido ? \Carbon\Carbon::parse($producto->fecha_recibido)->format('d/m/Y') : '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
