<table>
    <thead>
        <tr>
            <th colspan="11" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #4472C4; color: white;">
                📦 LISTADO DE PRODUCTOS
            </th>
        </tr>
        <tr>
            <th colspan="11" style="font-size: 12px; text-align: center; color: #666;">
                Sistema ZENVY - Gestión de Inventario
            </th>
        </tr>
        <tr>
            <th colspan="11"></th>
        </tr>
        <tr>
            <th colspan="11" style="font-size: 10px; text-align: center; background-color: #F8F9FA;">
                📅 Generado el: {{ $fechaGeneracion }} | 👤 Usuario: {{ $usuarioReporte ?? '-' }} | 📊 Total: {{ $totalProductos }} productos | 🔍 Filtros: {{ $filtrosAplicados }}
            </th>
        </tr>
        <tr>
            <th colspan="11"></th>
        </tr>
        <tr>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">ID</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Código de Barras</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Nombre</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Marca</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Categoría</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Subcategoría</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Precio Base</th>
            <th style="background-color: #28A745; color: white; font-weight: bold; text-align: center;">Unidad de Medida</th>
            <th style="background-color: #28A745; color: white; font-weight: bold; text-align: center;">Precio de Venta</th>
            <th style="background-color: #28A745; color: white; font-weight: bold; text-align: center;">Cantidad por Unidad</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Origen</th>
        </tr>
    </thead>
    <tbody>
        @foreach($productos as $producto)
            @php
                // Obtener precios de venta activos (ya filtrados en la consulta)
                $preciosVenta = $producto->preciosVenta;
                $totalPrecios = $preciosVenta->count();
            @endphp
            
            @if($totalPrecios > 0)
                @foreach($preciosVenta as $index => $precioVenta)
                    <tr>
                        @if($index == 0)
                            <td style="text-align: center;" rowspan="{{ $totalPrecios }}">{{ $producto->id }}</td>
                            <td rowspan="{{ $totalPrecios }}">{{ $precioVenta->codigo_barra ?? 'Sin código' }}</td>
                            <td rowspan="{{ $totalPrecios }}">{{ $producto->nombre }}</td>
                            <td rowspan="{{ $totalPrecios }}">{{ $producto->marca->nombre ?? 'Sin marca' }}</td>
                            <td rowspan="{{ $totalPrecios }}">{{ $producto->subcategoria->categoria->nombre ?? 'N/A' }}</td>
                            <td rowspan="{{ $totalPrecios }}">{{ $producto->subcategoria->nombre ?? 'N/A' }}</td>
                            <td style="text-align: right; font-weight: bold; color: #6C757D;" rowspan="{{ $totalPrecios }}">L. {{ number_format($producto->precio_base, 2) }}</td>
                        @endif
                        <td style="text-align: center; background-color: #E7F3FF; font-weight: bold;">{{ $precioVenta->unidadMedida->nombre ?? 'N/A' }}</td>
                        <td style="text-align: right; background-color: #D4EDDA; font-weight: bold; color: #28A745;">L. {{ number_format($precioVenta->precio, 2) }}</td>
                        <td style="text-align: center; background-color: #FFF3CD;">{{ $precioVenta->cantidad ?? 1 }}</td>
                        @if($index == 0)
                            <td style="text-align: center; background-color: {{ $producto->producto_valencia ? '#FFF3CD' : '#D4EDDA' }};" rowspan="{{ $totalPrecios }}">
                                {{ $producto->producto_valencia ? '🏢 Valencia' : '🏠 Paperland' }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            @else
                <tr>
                    <td style="text-align: center;">{{ $producto->id }}</td>
                    <td>Sin código</td>
                    <td>{{ $producto->nombre }}</td>
                    <td>{{ $producto->marca->nombre ?? 'Sin marca' }}</td>
                    <td>{{ $producto->subcategoria->categoria->nombre ?? 'N/A' }}</td>
                    <td>{{ $producto->subcategoria->nombre ?? 'N/A' }}</td>
                    <td style="text-align: right; font-weight: bold; color: #6C757D;">L. {{ number_format($producto->precio_base, 2) }}</td>
                    <td style="text-align: center; background-color: #F8D7DA; color: #721C24;">Sin precio de venta</td>
                    <td style="text-align: center; background-color: #F8D7DA; color: #721C24;">-</td>
                    <td style="text-align: center; background-color: #F8D7DA; color: #721C24;">-</td>
                    <td style="text-align: center; background-color: {{ $producto->producto_valencia ? '#FFF3CD' : '#D4EDDA' }};">
                        {{ $producto->producto_valencia ? '🏢 Valencia' : '🏠 Paperland' }}
                    </td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
