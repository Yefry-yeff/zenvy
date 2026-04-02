<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte de Ventas</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 2px solid #4472C4;
            padding-bottom: 8px;
        }

        .header h1 {
            font-size: 18px;
            color: #4472C4;
            margin: 0 0 4px 0;
            font-weight: bold;
        }

        .header .subtitle {
            font-size: 10px;
            color: #666;
            margin: 0;
        }

        .info-box {
            background-color: #f8f9fa;
            padding: 6px 10px;
            border-radius: 3px;
            margin-bottom: 10px;
            font-size: 9px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #3a5fa0;
            font-size: 9px;
        }

        tbody td {
            padding: 5px 4px;
            border: 1px solid #ddd;
            font-size: 8px;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            background-color: #f5f8ff;
        }

        tfoot td {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid #aaa;
            background-color: #dce6f1;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .total-label { text-align: right; color: #333; }
        .total-value { text-align: right; color: #1a4480; }
        .descuento   { color: #c0392b; }

        .footer {
            position: fixed;
            bottom: 8mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 REPORTE DE VENTAS</h1>
        <p class="subtitle">Sistema ZENVY &mdash; Gestión de Ventas</p>
    </div>

    <div class="info-box">
        <strong>Información del Reporte:</strong> &nbsp;
        📅 Generado el: {{ $fechaGeneracion }} &nbsp;|&nbsp;
        📊 Total de registros: {{ $totalRegistros }} &nbsp;|&nbsp;
        🔍 Filtros: {{ $filtrosAplicados }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">ID</th>
                <th style="width:13%">N° Factura</th>
                <th style="width:22%">Cliente</th>
                <th style="width:11%">RTN</th>
                <th style="width:8%">Fecha</th>
                <th style="width:9%">Gravado</th>
                <th style="width:9%">Exento</th>
                <th style="width:9%">Descuento</th>
                <th style="width:7%">ISV</th>
                <th style="width:8%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($facturas as $factura)
            <tr>
                <td class="text-center">{{ $factura->id }}</td>
                <td>{{ $factura->numero_factura ?? 'N/A' }}</td>
                <td>{{ $factura->nombre_cliente ?? 'Cliente General' }}</td>
                <td>{{ $factura->rtn ?? 'N/A' }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y') }}</td>
                <td class="text-right">{{ number_format($factura->sub_total_grabado ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($factura->sub_total_exento ?? 0, 2) }}</td>
                <td class="text-right descuento">{{ number_format($factura->monto_descuento ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($factura->isv ?? 0, 2) }}</td>
                <td class="text-right" style="font-weight:bold; color:#1a6b2a;">{{ number_format($factura->total ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="total-label">TOTALES ({{ $totalRegistros }} registros):</td>
                <td class="total-value">{{ number_format($totales->total_gravado ?? 0, 2) }}</td>
                <td class="total-value">{{ number_format($totales->total_exento ?? 0, 2) }}</td>
                <td class="total-value descuento">{{ number_format($totales->total_descuento ?? 0, 2) }}</td>
                <td class="total-value">{{ number_format($totales->total_isv ?? 0, 2) }}</td>
                <td class="total-value" style="color:#1a6b2a;">{{ number_format($totales->total_total ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Sistema ZENVY &mdash; Reporte de Ventas | Generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
