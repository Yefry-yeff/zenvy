<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Recibo Cierre de Caja - {{ \Carbon\Carbon::parse($cierre->fecha_cierre)->format('d/m/Y') }}</title>
    <style>
        @page {
            size: 72.1mm 350mm;
            margin: 3mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 20px;
            line-height: 1.4;
            color: #000;
        }

        .container {
            width: 100%;
            max-width: 66mm;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .double-separator {
            border-top: 2px solid #000;
            margin: 8px 0;
        }

        .company-name {
            font-weight: bold;
            font-size: 22px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .business-name {
            font-weight: bold;
            font-size: 19px;
            margin: 2px 0;
        }

        .company-info {
            font-size: 17px;
            margin: 1px 0;
        }

        .title {
            font-weight: bold;
            font-size: 22px;
            text-align: center;
            margin: 8px 0;
        }

        .subtitle {
            font-size: 16px;
            text-align: center;
            margin: 4px 0;
        }

        .info-row {
            font-size: 16px;
            margin: 4px 0;
        }

        .section-title {
            font-weight: bold;
            font-size: 18px;
            margin: 8px 0 4px 0;
            text-align: center;
        }

        .table-row {
            display: table;
            width: 100%;
            margin-bottom: 2px;
            font-size: 16px;
        }

        .col-left {
            display: table-cell;
            width: 60%;
            padding-right: 5px;
        }

        .col-right {
            display: table-cell;
            width: 40%;
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            font-size: 18px;
            margin: 6px 0;
        }

        .denominacion-row {
            font-size: 15px;
            margin-bottom: 2px;
        }

        .highlight {
            font-weight: bold;
            font-size: 20px;
            margin: 8px 0;
        }

        .diferencia-positiva {
            color: #000;
        }

        .diferencia-negativa {
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ENCABEZADO -->
        <div class="text-center">
            @if($empresa && $empresa->logo)
                <div style="margin-bottom: 4px;">
                    <img src="data:image/png;base64,{{ base64_encode($empresa->logo) }}"
                         style="max-width: 45mm; max-height: 12mm;">
                </div>
            @endif

            @if($tienda && $tienda->denominacion_social)
                <div class="company-name">
                    {{ $tienda->denominacion_social }}
                </div>
            @endif

            @if($empresa && $empresa->nombre)
                <div class="business-name">
                    {{ $empresa->nombre }}
                </div>
            @endif

            @if($empresa && $empresa->rtn)
                <div class="company-info">
                    RTN: {{ $empresa->rtn }}
                </div>
            @endif

            @if($tienda && $tienda->domicilio_tributario)
                <div class="company-info">
                    {{ $tienda->domicilio_tributario }}
                </div>
            @endif
        </div>

        <div class="separator"></div>

        <!-- TÍTULO -->
        <div class="title">
            CIERRE DE CAJA
        </div>

        <!-- INFORMACIÓN DEL CIERRE -->
        <div class="info-row">
            <strong>Cajero:</strong> {{ $usuario->name }}
        </div>
        <div class="info-row">
            <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($cierre->fecha_cierre)->format('d/m/Y') }}
        </div>
        <div class="info-row">
            <strong>Hora:</strong> {{ \Carbon\Carbon::parse($cierre->fecha_cierre)->format('H:i:s') }}
        </div>
        <div class="info-row">
            <strong>Total Transacciones:</strong> {{ $cierre->cantidad_facturas ?? 0 }}
        </div>

        <div class="separator"></div>

        <!-- RESUMEN DE TRANSACCIONES -->
        <div class="section-title">
            RESUMEN DE VENTAS
        </div>

        @foreach($resumenTransacciones as $item)
            <div class="table-row">
                <div class="col-left">{{ $item->forma_pago }}</div>
                <div class="col-right">L. {{ number_format($item->total, 2) }}</div>
            </div>
        @endforeach

        <div class="separator"></div>
        <div class="table-row total-row">
            <div class="col-left">TOTAL VENTAS:</div>
            <div class="col-right">L. {{ number_format($resumenTransacciones->sum('total'), 2) }}</div>
        </div>

        <!-- REVERSIÓN DE FACTURAS ANULADAS -->
        @if($facturasAnuladas['efectivo'] > 0 || $facturasAnuladas['tarjeta'] > 0 || $facturasAnuladas['transferencia'] > 0 || $facturasAnuladas['cheque'] > 0)
            <div class="double-separator"></div>
            <div class="section-title" style="color: #dc2626;">
                REVERSIÓN DE FACTURAS
            </div>
            <div style="font-size: 8px; text-align: center; margin-bottom: 5px; color: #6b7280;">
                (Se restan del total del sistema)
            </div>

            @if($facturasAnuladas['efectivo'] > 0)
            <div class="table-row">
                <div class="col-left">Efectivo:</div>
                <div class="col-right" style="color: #dc2626;">- L. {{ number_format($facturasAnuladas['efectivo'], 2) }}</div>
            </div>
            @endif

            @if($facturasAnuladas['tarjeta'] > 0)
            <div class="table-row">
                <div class="col-left">Tarjeta:</div>
                <div class="col-right" style="color: #dc2626;">- L. {{ number_format($facturasAnuladas['tarjeta'], 2) }}</div>
            </div>
            @endif

            @if($facturasAnuladas['transferencia'] > 0)
            <div class="table-row">
                <div class="col-left">Transferencia:</div>
                <div class="col-right" style="color: #dc2626;">- L. {{ number_format($facturasAnuladas['transferencia'], 2) }}</div>
            </div>
            @endif

            @if($facturasAnuladas['cheque'] > 0)
            <div class="table-row">
                <div class="col-left">Cheque:</div>
                <div class="col-right" style="color: #dc2626;">- L. {{ number_format($facturasAnuladas['cheque'], 2) }}</div>
            </div>
            @endif

            <div class="separator"></div>
            <div class="table-row total-row" style="color: #dc2626;">
                <div class="col-left">TOTAL REVERSIONES:</div>
                <div class="col-right">- L. {{ number_format($facturasAnuladas['efectivo'] + $facturasAnuladas['tarjeta'] + $facturasAnuladas['transferencia'] + $facturasAnuladas['cheque'], 2) }}</div>
            </div>
        @endif

        <div class="double-separator"></div>

        <!-- CONTABILIZACIÓN DE MÉTODOS DE PAGO -->
        <div class="section-title">
            CONTABILIZACIÓN
        </div>

        @if(($cierre->total_tarjeta_contado ?? 0) > 0)
        <div class="table-row">
            <div class="col-left">Tarjeta:</div>
            <div class="col-right">L. {{ number_format($cierre->total_tarjeta_contado, 2) }}</div>
        </div>
        @endif

        @if(($cierre->total_transferencia_contado ?? 0) > 0)
        <div class="table-row">
            <div class="col-left">Transferencia:</div>
            <div class="col-right">L. {{ number_format($cierre->total_transferencia_contado, 2) }}</div>
        </div>
        @endif

        @if(($cierre->total_cheque_contado ?? 0) > 0)
        <div class="table-row">
            <div class="col-left">Cheque:</div>
            <div class="col-right">L. {{ number_format($cierre->total_cheque_contado, 2) }}</div>
        </div>
        @endif

        <div class="separator"></div>

        <!-- EFECTIVO -->
        <div class="section-title">
            EFECTIVO
        </div>

        @if(count($denominaciones) > 0)
            <div class="subtitle"><strong>Denominaciones Contadas:</strong></div>
            @foreach($denominaciones as $denom)
                <div class="table-row denominacion-row">
                    <div class="col-left">{{ $denom['denominacion'] }} × {{ $denom['cantidad'] }}</div>
                    <div class="col-right">L. {{ number_format($denom['total'], 2) }}</div>
                </div>
            @endforeach
        @endif

        <div class="separator"></div>
        <div class="table-row total-row">
            <div class="col-left">Total Contado:</div>
            <div class="col-right">L. {{ number_format($cierre->total_efectivo_contado, 2) }}</div>
        </div>

        <div class="table-row highlight {{ $cierre->diferencia >= 0 ? 'diferencia-positiva' : 'diferencia-negativa' }}">
            <div class="col-left">Diferencia Efectivo:</div>
            <div class="col-right">L. {{ number_format($cierre->diferencia, 2) }}</div>
        </div>

        <div class="double-separator"></div>

        <!-- DEPÓSITO -->
        <div class="section-title" style="font-size: 20px;">
            DEPÓSITO
        </div>

        <div class="info-row">
            <div class="table-row">
                <div class="col-left">Efectivo:</div>
                <div class="col-right">L. {{ number_format($cierre->total_efectivo_sistema, 2) }}</div>
            </div>
            @if($cierre->diferencia > 0)
            <div class="table-row">
                <div class="col-left">Sobrante de conteo:</div>
                <div class="col-right">L. {{ number_format($cierre->diferencia, 2) }}</div>
            </div>
            @endif
        </div>

        <div class="separator"></div>

        <div class="table-row" style="font-weight: bold; font-size: 22px;">
            <div class="col-left">MONTO A DEPOSITAR:</div>
            <div class="col-right">L. {{ number_format($montoDeposito, 2) }}</div>
        </div>

        <div class="separator"></div>

        <!-- PIE DE PÁGINA -->
        <div class="text-center" style="font-size: 14px; margin-top: 10px;">
            <div>Este documento es un comprobante</div>
            <div>interno de cierre de caja</div>
            <div style="margin-top: 6px;">¡Gracias!</div>
        </div>
    </div>
</body>
</html>
