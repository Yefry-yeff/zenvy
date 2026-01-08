<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Consolidado de Cierres</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
        }

        .header h1 {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 11px;
            color: #666;
            margin-bottom: 3px;
        }

        .info-box {
            background-color: #f8f9fa;
            padding: 8px;
            margin-bottom: 12px;
            border-left: 4px solid #667eea;
            border-radius: 3px;
        }

        .info-box p {
            margin: 3px 0;
            font-size: 10px;
        }

        .info-box strong {
            color: #333;
        }

        .usuario-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .usuario-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 10px;
            margin-bottom: 8px;
            border-radius: 4px;
        }

        .usuario-header h2 {
            font-size: 13px;
            margin-bottom: 2px;
        }

        .usuario-header .stats {
            font-size: 9px;
            opacity: 0.9;
        }

        .resumen-usuario {
            background-color: #f8f9fa;
            padding: 8px;
            margin-bottom: 8px;
            border-radius: 3px;
            border: 1px solid #e0e0e0;
        }

        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .resumen-item {
            text-align: center;
            padding: 5px;
            background: white;
            border-radius: 3px;
            border: 1px solid #e0e0e0;
        }

        .resumen-item .label {
            font-size: 8px;
            color: #666;
            margin-bottom: 2px;
        }

        .resumen-item .value {
            font-size: 11px;
            font-weight: bold;
            color: #333;
        }

        .resumen-item.efectivo .value { color: #10b981; }
        .resumen-item.tarjeta .value { color: #3b82f6; }
        .resumen-item.transferencia .value { color: #f59e0b; }
        .resumen-item.cheque .value { color: #6b7280; }
        .resumen-item.total .value { color: #8b5cf6; font-size: 12px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }

        table thead {
            background-color: #4b5563;
            color: white;
        }

        table th {
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
            border: 1px solid #374151;
        }

        table td {
            padding: 5px 4px;
            border: 1px solid #e5e7eb;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        table tbody tr:hover {
            background-color: #f3f4f6;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .diferencia-positiva {
            color: #10b981;
            font-weight: bold;
        }

        .diferencia-negativa {
            color: #ef4444;
            font-weight: bold;
        }

        .diferencia-neutral {
            color: #6b7280;
        }

        .totales-generales {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px;
            margin-top: 15px;
            border-radius: 5px;
            page-break-inside: avoid;
        }

        .totales-generales h2 {
            font-size: 14px;
            margin-bottom: 8px;
            text-align: center;
        }

        .totales-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .total-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px;
            border-radius: 3px;
            text-align: center;
        }

        .total-item .label {
            font-size: 8px;
            margin-bottom: 2px;
            opacity: 0.9;
        }

        .total-item .value {
            font-size: 12px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #666;
            padding: 8px 0;
            border-top: 1px solid #e0e0e0;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <!-- ENCABEZADO -->
    <div class="header">
        <h1>📊 REPORTE CONSOLIDADO DE CIERRES DE CAJA</h1>
        <p class="subtitle">Resumen de actividad por usuario y período</p>
    </div>

    <!-- INFORMACIÓN DEL REPORTE -->
    <div class="info-box">
        <p><strong>Período:</strong> {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}</p>
        <p><strong>Fecha de generación:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        @if($usuarioFiltro)
            <p><strong>Usuario filtrado:</strong> {{ $usuarioFiltro }}</p>
        @endif
        <p><strong>Total de usuarios:</strong> {{ $totalesGenerales['cantidad_usuarios'] }}</p>
        <p><strong>Total de cierres:</strong> {{ $totalesGenerales['total_cierres'] }}</p>
    </div>

    <!-- CONSOLIDADO POR USUARIO -->
    @foreach($consolidadoPorUsuario as $userId => $datos)
        <div class="usuario-section">
            <!-- ENCABEZADO DE USUARIO -->
            <div class="usuario-header">
                <h2>👤 {{ $datos['nombre'] }}</h2>
                <p class="stats">{{ $datos['cantidad_cierres'] }} cierre(s) realizados en el período</p>
            </div>

            <!-- RESUMEN DEL USUARIO -->
            <div class="resumen-usuario">
                <div class="resumen-grid">
                    <div class="resumen-item efectivo">
                        <div class="label">Efectivo Sistema</div>
                        <div class="value">L {{ number_format($datos['total_efectivo_sistema'], 2) }}</div>
                    </div>
                    <div class="resumen-item efectivo">
                        <div class="label">Efectivo Contado</div>
                        <div class="value">L {{ number_format($datos['total_efectivo_contado'], 2) }}</div>
                    </div>
                    <div class="resumen-item tarjeta">
                        <div class="label">Tarjeta</div>
                        <div class="value">L {{ number_format($datos['total_tarjeta'], 2) }}</div>
                    </div>
                    <div class="resumen-item transferencia">
                        <div class="label">Transferencia</div>
                        <div class="value">L {{ number_format($datos['total_transferencia'], 2) }}</div>
                    </div>
                    <div class="resumen-item cheque">
                        <div class="label">Cheque</div>
                        <div class="value">L {{ number_format($datos['total_cheque'], 2) }}</div>
                    </div>
                    <div class="resumen-item total">
                        <div class="label">Total Ventas</div>
                        <div class="value">L {{ number_format($datos['total_ventas'], 2) }}</div>
                    </div>
                    <div class="resumen-item {{ $datos['diferencia_efectivo'] > 0 ? 'efectivo' : ($datos['diferencia_efectivo'] < 0 ? 'cheque' : 'transferencia') }}">
                        <div class="label">Diferencia Efectivo</div>
                        <div class="value">L {{ number_format($datos['diferencia_efectivo'], 2) }}</div>
                    </div>
                </div>
            </div>

            <!-- DETALLE DE CIERRES -->
            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 8%;">ID</th>
                        <th style="width: 15%;">Fecha</th>
                        <th class="text-right" style="width: 12%;">Efect. Sistema</th>
                        <th class="text-right" style="width: 12%;">Efect. Contado</th>
                        <th class="text-right" style="width: 10%;">Tarjeta</th>
                        <th class="text-right" style="width: 10%;">Transfer.</th>
                        <th class="text-right" style="width: 10%;">Cheque</th>
                        <th class="text-right" style="width: 12%;">Total</th>
                        <th class="text-right" style="width: 11%;">Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($datos['cierres'] as $cierre)
                        @php
                            $diferencia = $cierre['efectivo_contado'] - $cierre['efectivo_sistema'];
                            $diferenciaClass = $diferencia > 0 ? 'diferencia-positiva' : ($diferencia < 0 ? 'diferencia-negativa' : 'diferencia-neutral');
                        @endphp
                        <tr>
                            <td class="text-center">{{ $cierre['id'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($cierre['fecha'])->format('d/m/Y H:i') }}</td>
                            <td class="text-right">{{ number_format($cierre['efectivo_sistema'], 2) }}</td>
                            <td class="text-right">{{ number_format($cierre['efectivo_contado'], 2) }}</td>
                            <td class="text-right">{{ number_format($cierre['tarjeta'], 2) }}</td>
                            <td class="text-right">{{ number_format($cierre['transferencia'], 2) }}</td>
                            <td class="text-right">{{ number_format($cierre['cheque'], 2) }}</td>
                            <td class="text-right" style="font-weight: bold;">{{ number_format($cierre['total'], 2) }}</td>
                            <td class="text-right {{ $diferenciaClass }}">{{ number_format($diferencia, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <!-- TOTALES GENERALES -->
    <div class="totales-generales">
        <h2>💰 TOTALES GENERALES DEL PERÍODO</h2>
        <div class="totales-grid">
            <div class="total-item">
                <div class="label">Total Efectivo Sistema</div>
                <div class="value">L {{ number_format($totalesGenerales['total_efectivo_sistema'], 2) }}</div>
            </div>
            <div class="total-item">
                <div class="label">Total Efectivo Contado</div>
                <div class="value">L {{ number_format($totalesGenerales['total_efectivo_contado'], 2) }}</div>
            </div>
            <div class="total-item">
                <div class="label">Diferencia Total</div>
                <div class="value">L {{ number_format($totalesGenerales['diferencia_efectivo'], 2) }}</div>
            </div>
            <div class="total-item">
                <div class="label">Total Tarjeta</div>
                <div class="value">L {{ number_format($totalesGenerales['total_tarjeta'], 2) }}</div>
            </div>
            <div class="total-item">
                <div class="label">Total Transferencia</div>
                <div class="value">L {{ number_format($totalesGenerales['total_transferencia'], 2) }}</div>
            </div>
            <div class="total-item">
                <div class="label">Total Cheque</div>
                <div class="value">L {{ number_format($totalesGenerales['total_cheque'], 2) }}</div>
            </div>
            <div class="total-item" style="grid-column: span 3;">
                <div class="label">TOTAL GENERAL DE VENTAS</div>
                <div class="value" style="font-size: 16px;">L {{ number_format($totalesGenerales['total_ventas'], 2) }}</div>
            </div>
        </div>
    </div>

    <!-- PIE DE PÁGINA -->
    <div class="footer">
        <p>Reporte generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i:s') }}</p>
        <p>Sistema de Punto de Venta - Zenvy</p>
    </div>
</body>
</html>
