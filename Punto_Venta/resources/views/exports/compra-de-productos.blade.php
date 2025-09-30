<table>
    <thead>
        <tr>
            <th colspan="7" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #4472C4; color: white;">
                🛒 LISTADO DE COMPRAS DE PRODUCTOS
            </th>
        </tr>
        <tr>
            <th colspan="7" style="font-size: 12px; text-align: center; color: #666;">
                Sistema ZENVY - Gestión de Compras
            </th>
        </tr>
        <tr><th colspan="7"></th></tr>
        <tr>
            <th colspan="7" style="font-size: 10px; text-align: center; background-color: #F8F9FA;">
                📅 Generado el: {{ $fechaGeneracion }} | 👤 Usuario: {{ $usuarioReporte ?? '-' }} | 📊 Total: {{ $totalCompras }} compras | 🔍 Filtros: {{ $filtrosAplicados }}
            </th>
        </tr>
        <tr><th colspan="7"></th></tr>
        <tr>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;"># Factura</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Proveedor</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Fecha Emisión</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Fecha Recepción</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Estado</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Cantidad Productos</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($compras as $compra)
            <tr>
                <td>{{ $compra['numero_factura'] ?? '-' }}</td>
                <td>{{ $compra['proveedor_nombre'] ?? 'N/A' }}</td>
                <td>{{ !empty($compra['fecha_emision']) ? \Carbon\Carbon::parse($compra['fecha_emision'])->format('d/m/Y') : '-' }}</td>
                <td>{{ !empty($compra['fecha_recepcion']) ? \Carbon\Carbon::parse($compra['fecha_recepcion'])->format('d/m/Y') : '-' }}</td>
                <td>{{ $compra['estado'] ?? 'N/A' }}</td>
                <td style="text-align: right;">{{ $compra['cantidad_productos'] ?? 0 }}</td>
                <td style="text-align: right;">L. {{ number_format($compra['total'] ?? 0, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
