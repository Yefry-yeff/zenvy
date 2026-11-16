<table>
    <thead>
        <tr>
            <th colspan="9" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #4472C4; color: white;">
                🧾 LISTADO DE FACTURAS
            </th>
        </tr>
        <tr>
            <th colspan="9" style="font-size: 12px; text-align: center; color: #666;">
                Sistema ZENVY - Gestión de Ventas
            </th>
        </tr>
        <tr><th colspan="9"></th></tr>
        <tr>
            <th colspan="9" style="font-size: 10px; text-align: center; background-color: #F8F9FA;">
                📅 Generado el: {{ $fechaGeneracion }} | 👤 Usuario: {{ $usuarioReporte ?? '-' }} | 📊 Total: {{ $totalFacturas }} facturas | 🔍 Filtros: {{ $filtrosAplicados }}
            </th>
        </tr>
        <tr><th colspan="9"></th></tr>
        <tr>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">ID</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">No. Factura</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Cliente</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">RTN</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Fecha</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Subtotal</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">ISV</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Total</th>
            <th style="background-color: #4472C4; color: white; font-weight: bold; text-align: center;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($facturas as $factura)
            <tr>
                <td>{{ $factura['id'] }}</td>
                <td>{{ $factura['numero_factura'] }}</td>
                <td>{{ $factura['nombre_cliente'] ?? 'Cliente General' }}</td>
                <td>{{ $factura['rtn'] }}</td>
                <td>{{ !empty($factura['fecha_emision']) ? \Carbon\Carbon::parse($factura['fecha_emision'])->format('d/m/Y') : '-' }}</td>
                <td style="text-align: right;">L. {{ number_format($factura['sub_total'] ?? 0, 2) }}</td>
                <td style="text-align: right;">L. {{ number_format($factura['isv'] ?? 0, 2) }}</td>
                <td style="text-align: right; font-weight: bold;">L. {{ number_format($factura['total'] ?? 0, 2) }}</td>
                <td style="text-align: center;">
                    @if($factura['estado_factura_id'] == 1)
                        Pagada
                    @elseif($factura['estado_factura_id'] == 2)
                        Pendiente
                    @elseif($factura['estado_factura_id'] == 3)
                        Anulada
                    @else
                        Desconocido
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
