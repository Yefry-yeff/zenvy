<table>
    <thead>
        <tr>
            <th colspan="8" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #4472C4; color: white;">
                📦 LISTADO DE PRODUCTOS
            </th>
        </tr>
        <tr>
            <th colspan="8" style="font-size: 12px; text-align: center; color: #666;">
                Sistema ZENVY - Gestión de Inventario
            </th>
        </tr>
        <tr>
            <th colspan="8"></th>
        </tr>
        <tr>
            <th colspan="8" style="font-size: 10px; text-align: center; background-color: #F8F9FA;">
                📅 Generado el: {{ $fechaGeneracion }} | 📊 Total: {{ $totalProductos }} productos | 🔍 Filtros: {{ $filtrosAplicados }}
            </th>
        </tr>
        <tr>
            <th colspan="8"></th>
        </tr>
        <tr>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">ID</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Código de Barras</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Nombre</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Marca</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Categoría</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Subcategoría</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Precio Base</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Origen</th>
        </tr>
    </thead>
    <tbody>
        @foreach($productos as $producto)
            <tr>
                <td style="text-align: center;">{{ $producto->id }}</td>
                <td>{{ $producto->codigo_barra ?: 'Sin código' }}</td>
                <td>{{ $producto->nombre }}</td>
                <td>{{ $producto->marca->nombre ?? 'Sin marca' }}</td>
                <td>{{ $producto->subcategoria->categoria->nombre ?? 'N/A' }}</td>
                <td>{{ $producto->subcategoria->nombre ?? 'N/A' }}</td>
                <td style="text-align: right; font-weight: bold; color: #28A745;">L. {{ number_format($producto->precio_base, 2) }}</td>
                <td style="text-align: center; background-color: {{ $producto->producto_valencia ? '#FFF3CD' : '#D4EDDA' }};">
                    {{ $producto->producto_valencia ? '🏢 Valencia' : '🏠 Paperland' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>