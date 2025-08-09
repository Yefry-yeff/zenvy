<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Factura {{ $factura->numero_factura }}</title>
    <style>
        @page {
            size: 72.1mm 210mm;
            margin: 3mm;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 18px;
            line-height: 1.4;
            color: #000;
        }
        
        .container {
            width: 100%;
            max-width: 66mm; /* 72.1mm - 6mm margin */
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
        
        .company-name {
            font-weight: bold;
            font-size: 20px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .business-name {
            font-weight: bold;
            font-size: 17px;
            margin: 2px 0;
        }
        
        .company-info {
            font-size: 15px;
            margin: 1px 0;
        }
        
        .factura-title {
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            margin: 8px 0;
        }
        
        .factura-number {
            font-size: 16px;
            text-align: center;
            margin: 4px 0;
        }
        
        .duplicado-rango {
            font-size: 14px;
            text-align: center;
            margin: 4px 0;
        }
        
        .fecha-usuario {
            font-size: 14px;
            margin: 6px 0;
        }
        
        .consumidor-final {
            font-weight: bold;
            font-size: 16px;
            margin: 8px 0;
        }
        
        .table-header {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .product-row {
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        .totals-section {
            font-size: 14px;
            margin: 8px 0;
        }
        
        .total-final {
            font-weight: bold;
            font-size: 16px;
            margin: 4px 0;
        }
        
        .valor-letras {
            font-size: 12px;
            margin: 8px 0;
            text-align: center;
        }
        
        .forma-pago {
            font-size: 14px;
            margin: 6px 0;
        }
        
        .cai-info {
            font-size: 12px;
            margin: 8px 0;
        }
        
        .footer-info {
            font-size: 11px;
            margin-top: 10px;
        }
        
        .table-layout {
            display: table;
            width: 100%;
        }
        
        .table-row {
            display: table-row;
        }
        
        .table-cell-left {
            display: table-cell;
            width: 60%;
            padding-right: 5px;
        }
        
        .table-cell-right {
            display: table-cell;
            width: 40%;
            text-align: right;
        }
        
        .table-cell-center {
            display: table-cell;
            width: 33.33%;
            text-align: center;
        }
        
        .product-table {
            width: 100%;
            margin: 6px 0;
        }
        
        .product-table-row {
            display: table;
            width: 100%;
            margin-bottom: 2px;
        }
        
        .col-uds {
            display: table-cell;
            width: 15%;
            text-align: center;
        }
        
        .col-descripcion {
            display: table-cell;
            width: 55%;
            padding: 0 2px;
        }
        
        .col-importe {
            display: table-cell;
            width: 30%;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ENCABEZADO - DATOS DE LA EMPRESA -->
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

            @if($empresa && $empresa->correo)
                <div class="company-info">
                    Email: {{ $empresa->correo }}
                </div>
            @endif

            @if($empresa && $empresa->telefono)
                <div class="company-info">
                    Tel: {{ $empresa->telefono }}
                </div>
            @endif
        </div>

        <div class="separator"></div>

        <!-- FACTURA VENTA -->
        <div class="factura-title">
            <strong>FACTURA VENTA</strong>
        </div>

        <!-- NÚMERO DE FACTURA -->
        <div class="factura-number">
            {{ $factura->numero_factura }}
        </div>

        <!-- DUPLICADO Y RANGOS -->
        @if($caiFacturaImpresa)
            <div class="duplicado-rango">
                (DUPLICADO) {{ str_pad($caiFacturaImpresa->rango_inicio, 8, '0', STR_PAD_LEFT) }} - {{ str_pad($caiFacturaImpresa->rango_final, 8, '0', STR_PAD_LEFT) }}
            </div>
        @endif

        <!-- FECHA Y USUARIO -->
        <div class="fecha-usuario">
            {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('m.d.Y.H.i') }} {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('A') }} Usuario: {{ $factura->user_id ?? 'Admin' }}
        </div>

        <div class="separator"></div>

        <!-- CONSUMIDOR FINAL -->
        <div class="consumidor-final">
            <strong>CONSUMIDOR FINAL</strong>
            @if($factura->nombre_cliente && $factura->nombre_cliente != 'CONSUMIDOR FINAL')
                <br>{{ $factura->nombre_cliente }}
            @endif
        </div>

        <!-- TABLA DE PRODUCTOS -->
        <div class="table-header">
            <div class="product-table-row">
                <div class="col-uds"><strong>UDS</strong></div>
                <div class="col-descripcion"><strong>DESCRIPCION</strong></div>
                <div class="col-importe"><strong>IMPORTE</strong></div>
            </div>
        </div>

        @foreach($productos as $producto)
            <div class="product-row">
                <div class="product-table-row">
                    <div class="col-uds">
                        @if($producto->isv > 0)
                            {{ $producto->cantidad }}(G)
                        @else
                            {{ $producto->cantidad }}(E)
                        @endif
                    </div>
                    <div class="col-descripcion">
                        {{ $producto->nombre }}<br>
                        <span style="font-size: 11px;">{{ $producto->cantidad }} x L. {{ number_format($producto->precio_unidad, 2) }}</span>
                    </div>
                    <div class="col-importe">
                        L. {{ number_format($producto->total, 2) }}
                    </div>
                </div>
            </div>
        @endforeach

        <div class="separator"></div>

        <!-- DETALLE DE TOTALES -->
        <div class="totals-section">
            <div class="table-layout">
                <div class="table-row">
                    <div class="table-cell-left">SUB-TOTAL</div>
                    <div class="table-cell-right">L. {{ number_format($factura->sub_total, 2) }}</div>
                </div>
                @if($factura->monto_descuento > 0)
                <div class="table-row">
                    <div class="table-cell-left">DESCUENTOS Y REBAJAS</div>
                    <div class="table-cell-right">L. {{ number_format($factura->monto_descuento, 2) }}</div>
                </div>
                @endif
                <div class="table-row">
                    <div class="table-cell-left">IMPORTE EXONERADO</div>
                    <div class="table-cell-right">L. {{ number_format(collect($productos)->where('isv', 0)->sum('total'), 2) }}</div>
                </div>
                <div class="table-row">
                    <div class="table-cell-left">IMPORTE 15%</div>
                    <div class="table-cell-right">L. {{ number_format(collect($productos)->where('isv', '>', 0)->sum('total'), 2) }}</div>
                </div>
                <div class="table-row">
                    <div class="table-cell-left">IMPORTE 18%</div>
                    <div class="table-cell-right">L. 0.00</div>
                </div>
                <div class="table-row">
                    <div class="table-cell-left">TOTAL IMPORTE</div>
                    <div class="table-cell-right">L. {{ number_format($factura->sub_total, 2) }}</div>
                </div>
                <div class="table-row">
                    <div class="table-cell-left">IMPUESTO DEL 15%</div>
                    <div class="table-cell-right">L. {{ number_format($factura->isv, 2) }}</div>
                </div>
                <div class="table-row">
                    <div class="table-cell-left">IMPUESTO DEL 18%</div>
                    <div class="table-cell-right">L. 0.00</div>
                </div>
                <div class="table-row">
                    <div class="table-cell-left">TOTAL IMPUESTOS</div>
                    <div class="table-cell-right">L. {{ number_format($factura->isv, 2) }}</div>
                </div>
            </div>
            
            <div class="total-final">
                <div class="table-layout">
                    <div class="table-row">
                        <div class="table-cell-left"><strong>TOTAL</strong></div>
                        <div class="table-cell-right"><strong>L. {{ number_format($factura->total, 2) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="separator"></div>

        <!-- VALOR EN LETRAS -->
        <div class="valor-letras">
            <strong>VALOR EN LETRAS:</strong><br>
            @php
                $total = $factura->total;
                $entero = floor($total);
                
                $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
                $decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
                $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
                $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
                
                $letras = '';
                if ($entero == 0) {
                    $letras = 'CERO';
                } else {
                    if ($entero >= 1000) {
                        $miles = floor($entero / 1000);
                        if ($miles == 1) {
                            $letras .= 'MIL ';
                        } else {
                            $letras .= $unidades[$miles] . ' MIL ';
                        }
                        $entero %= 1000;
                    }
                    
                    if ($entero >= 100) {
                        $c = floor($entero / 100);
                        if ($entero == 100) {
                            $letras .= 'CIEN';
                        } else {
                            $letras .= $centenas[$c] . ' ';
                        }
                        $entero %= 100;
                    }
                    
                    if ($entero >= 20) {
                        $d = floor($entero / 10);
                        $letras .= $decenas[$d];
                        $entero %= 10;
                        if ($entero > 0) $letras .= ' Y ' . $unidades[$entero];
                    } elseif ($entero >= 10) {
                        $letras .= $especiales[$entero - 10];
                    } elseif ($entero > 0) {
                        $letras .= $unidades[$entero];
                    }
                }
                
                $letras = trim($letras) . ' LEMPIRAS';
            @endphp
            {{ $letras }}
        </div>

        <div class="separator"></div>

        <!-- FORMA DE PAGO -->
        @if(count($pagos) > 0)
            <div class="forma-pago">
                <strong>FORMA DE PAGO:</strong><br>
                @foreach($pagos as $pago)
                    {{ $pago->metodo }}: L. {{ number_format($pago->pago_recibido, 2) }}<br>
                @endforeach
            </div>
        @endif

        <div class="separator"></div>

        <!-- DATOS DEL CAI -->
        @if($caiFacturaImpresa)
            <div class="cai-info">
                <strong>CAI:</strong> {{ $caiFacturaImpresa->cai }}<br>
                <strong>FECHA LIMITE:</strong> {{ \Carbon\Carbon::parse($caiFacturaImpresa->fecha_limite_emision)->format('d/m/Y') }}
            </div>
        @endif

        <!-- PIE DE PÁGINA -->
        <div class="footer-info">
            <div class="table-layout">
                <div class="table-row">
                    <div class="table-cell-left" style="font-size: 10px;">Grabado = G</div>
                    <div class="table-cell-center" style="font-size: 10px; text-align: center;">Obligado Tributario Emisor</div>
                    <div class="table-cell-right" style="font-size: 10px; text-align: right;">Exento = E</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
