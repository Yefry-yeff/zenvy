<table>
    <thead>
        <tr>
            <th colspan="10" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #4472C4; color: white;">
                📊 REPORTE DE VENTAS
            </th>
        </tr>
        <tr>
            <th colspan="10" style="font-size: 12px; text-align: center; color: #666;">
                Sistema ZENVY - Gestión de Ventas
            </th>
        </tr>
        <tr><th colspan="10"></th></tr>
        <tr>
            <th colspan="10" style="font-size: 10px; text-align: center; background-color: #F8F9FA;">
                📅 Generado el: {{ $fechaGeneracion }} | 🔍 Filtros: {{ $filtrosAplicados }}
            </th>
        </tr>
        <tr><th colspan="10"></th></tr>
        <tr>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">ID</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">No. Factura</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Cliente</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">RTN</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Fecha</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Gravado</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Exento</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Descuento</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">ISV</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($facturas as $factura)
        <tr>
            <td>{{ is_array($factura) ? $factura['id'] : $factura->id }}</td>
            <td>{{ is_array($factura) ? $factura['numero_factura'] : $factura->numero_factura }}</td>
            <td>{{ is_array($factura) ? ($factura['nombre_cliente'] ?? 'Cliente General') : ($factura->nombre_cliente ?? 'Cliente General') }}</td>
            <td>{{ is_array($factura) ? ($factura['rtn'] ?? 'N/A') : ($factura->rtn ?? 'N/A') }}</td>
            <td>{{ !empty(is_array($factura) ? $factura['fecha_emision'] : $factura->fecha_emision) ? \Carbon\Carbon::parse(is_array($factura) ? $factura['fecha_emision'] : $factura->fecha_emision)->format('d/m/Y') : '-' }}</td>
            <td style="text-align: right;">{{ number_format(is_array($factura) ? ($factura['sub_total_grabado'] ?? 0) : ($factura->sub_total_grabado ?? 0), 2) }}</td>
            <td style="text-align: right;">{{ number_format(is_array($factura) ? ($factura['sub_total_exento'] ?? 0) : ($factura->sub_total_exento ?? 0), 2) }}</td>
            <td style="text-align: right;">{{ number_format(is_array($factura) ? ($factura['monto_descuento'] ?? 0) : ($factura->monto_descuento ?? 0), 2) }}</td>
            <td style="text-align: right;">{{ number_format(is_array($factura) ? ($factura['isv'] ?? 0) : ($factura->isv ?? 0), 2) }}</td>
            <td style="text-align: right; font-weight: bold;">{{ number_format(is_array($factura) ? ($factura['total'] ?? 0) : ($factura->total ?? 0), 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="font-weight: bold; text-align: right; background-color: #E8F0FE;">TOTALES</td>
            <td style="font-weight: bold; text-align: right; background-color: #E8F0FE;">{{ number_format($totales->total_gravado ?? 0, 2) }}</td>
            <td style="font-weight: bold; text-align: right; background-color: #E8F0FE;">{{ number_format($totales->total_exento ?? 0, 2) }}</td>
            <td style="font-weight: bold; text-align: right; background-color: #E8F0FE;">{{ number_format($totales->total_descuento ?? 0, 2) }}</td>
            <td style="font-weight: bold; text-align: right; background-color: #E8F0FE;">{{ number_format($totales->total_isv ?? 0, 2) }}</td>
            <td style="font-weight: bold; text-align: right; background-color: #E8F0FE;">{{ number_format($totales->total_total ?? 0, 2) }}</td>
        </tr>
    </tfoot>
</table>
