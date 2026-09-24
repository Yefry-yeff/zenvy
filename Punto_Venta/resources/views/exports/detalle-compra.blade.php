<table>
    <thead>
        <tr>
            <th colspan="5" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #4472C4; color: white;">
                🧾 DETALLE DE COMPRA
            </th>
        </tr>
        <tr>
            <th colspan="5" style="font-size: 12px; text-align: center; color: #666;">
                Sistema ZENVY - Gestión de Compras
            </th>
        </tr>
        <tr><th colspan="5"></th></tr>
        <tr>
            <th colspan="5" style="font-size: 10px; text-align: center; background-color: #F8F9FA;">
                📅 Generado el: {{ $fechaGeneracion }} | 👤 Usuario: {{ $usuarioReporte ?? '-' }}
            </th>
        </tr>
        <tr><th colspan="5"></th></tr>
        <tr>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">N° Factura</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Proveedor</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Estado</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Emisión</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Recepción</th>
        </tr>
        <tr>
            <td>{{ $detalle['numero_factura'] ?? '-' }}</td>
            <td>{{ $detalle['proveedor_nombre'] ?? 'N/A' }}</td>
            <td>{{ $detalle['estado'] ?? 'N/A' }}</td>
            <td>{{ !empty($detalle['fecha_emision']) ? \Carbon\Carbon::parse($detalle['fecha_emision'])->format('d/m/Y') : '-' }}</td>
            <td>{{ !empty($detalle['fecha_recepcion']) ? \Carbon\Carbon::parse($detalle['fecha_recepcion'])->format('d/m/Y') : '-' }}</td>
        </tr>
        <tr><th colspan="5"></th></tr>
        <tr>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Producto</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Cantidad</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Unidad</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">P. Unitario</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($detalle['productos'] as $producto)
            <tr>
                <td>{{ $producto['nombre'] }}</td>
                <td style="text-align: right;">{{ $producto['cantidad'] }}</td>
                <td>{{ $producto['unidad'] }}</td>
                <td style="text-align: right;">L. {{ number_format($producto['precio_unitario'], 2) }}</td>
                <td style="text-align: right;">L. {{ number_format($producto['precio_total'], 2) }}</td>
            </tr>
        @endforeach
        <tr><td colspan="5"></td></tr>
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold;">Subtotal</td>
            <td style="text-align: right;">L. {{ number_format($detalle['subtotal_general'], 2) }}</td>
        </tr>
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold;">ISV</td>
            <td style="text-align: right;">L. {{ number_format($detalle['isv_general'], 2) }}</td>
        </tr>
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold;">Total</td>
            <td style="text-align: right; font-weight: bold; color: #4472C4;">L. {{ number_format($detalle['total_general'], 2) }}</td>
        </tr>
    </tbody>
</table>
