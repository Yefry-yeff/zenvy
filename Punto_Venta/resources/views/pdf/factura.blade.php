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
            font-size: 16px;
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
        
        .separator {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        
        .company-name {
            font-weight: bold;
            font-size: 18px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .business-name {
            font-weight: bold;
            font-size: 15px;
            margin: 2px 0;
        }
        
        .company-info {
            font-size: 13px;
            margin: 1px 0;
        }
        
        .invoice-info {
            margin: 8px 0;
            font-size: 15px;
        }
        
        .product-item {
            margin-bottom: 6px;
        }
        
        .product-name {
            font-weight: bold;
            font-size: 15px;
        }
        
        .product-line {
            display: table;
            width: 100%;
            font-size: 13px;
        }
        
        .product-left {
            display: table-cell;
            width: 60%;
        }
        
        .product-right {
            display: table-cell;
            width: 40%;
            text-align: right;
        }
        
        .total-line {
            display: table;
            width: 100%;
            margin: 2px 0;
            font-size: 15px;
        }
        
        .total-left {
            display: table-cell;
            width: 60%;
        }
        
        .total-right {
            display: table-cell;
            width: 40%;
            text-align: right;
        }
        
        .final-total {
            font-weight: bold;
            font-size: 17px;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 4px;
        }
        
        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 10px;
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

        <!-- INFORMACIÓN DE LA FACTURA -->
        <div class="invoice-info">
            <div><strong>FACTURA No:</strong> {{ $factura->numero_factura }}</div>
            <div><strong>FECHA:</strong> {{ $factura->fecha_emision->format('d/m/Y H:i') }}</div>
            @if($factura->nombre_cliente)
                <div><strong>CLIENTE:</strong> {{ $factura->nombre_cliente }}</div>
            @endif
        </div>

        <!-- INFORMACIÓN DEL CAI -->
        @if($caiFacturaImpresa)
            <div style="font-size: 13px; margin: 8px 0;">
                <div><strong>CAI:</strong> {{ $caiFacturaImpresa->cai }}</div>
                <div><strong>FECHA LIMITE:</strong> {{ \Carbon\Carbon::parse($caiFacturaImpresa->fecha_limite_emision)->format('d/m/Y') }}</div>
                <div><strong>RANGO:</strong> {{ str_pad($caiFacturaImpresa->rango_inicio, 8, '0', STR_PAD_LEFT) }} - {{ str_pad($caiFacturaImpresa->rango_final, 8, '0', STR_PAD_LEFT) }}</div>
            </div>
        @endif

        <div class="separator"></div>

        <!-- PRODUCTOS -->
        <div>
            @foreach($productos as $producto)
                <div class="product-item">
                    <div class="product-name">{{ $producto->nombre }}</div>
                    <div class="product-line">
                        <div class="product-left">{{ $producto->cantidad }} x L. {{ number_format($producto->precio_unidad, 2) }}</div>
                        <div class="product-right">L. {{ number_format($producto->total, 2) }}</div>
                    </div>
                    @if($producto->isv > 0)
                        <div style="font-size: 12px; color: #666;">
                            ISV: L. {{ number_format($producto->isv, 2) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="separator"></div>

        <!-- TOTALES -->
        <div>
            <div class="total-line">
                <div class="total-left">SUBTOTAL:</div>
                <div class="total-right">L. {{ number_format($factura->sub_total, 2) }}</div>
            </div>
            @if($factura->monto_descuento > 0)
                <div class="total-line">
                    <div class="total-left">DESCUENTO:</div>
                    <div class="total-right">L. {{ number_format($factura->monto_descuento, 2) }}</div>
                </div>
            @endif
            <div class="total-line">
                <div class="total-left">ISV (15%):</div>
                <div class="total-right">L. {{ number_format($factura->isv, 2) }}</div>
            </div>
            <div class="total-line final-total">
                <div class="total-left"><strong>TOTAL:</strong></div>
                <div class="total-right"><strong>L. {{ number_format($factura->total, 2) }}</strong></div>
            </div>
        </div>

        <!-- MÉTODOS DE PAGO -->
        @if(count($pagos) > 0)
            <div style="margin: 8px 0;">
                <div style="font-weight: bold; margin-bottom: 3px; font-size: 15px;">METODOS DE PAGO:</div>
                @foreach($pagos as $pago)
                    <div class="total-line" style="font-size: 14px;">
                        <div class="total-left">{{ $pago->metodo }}:</div>
                        <div class="total-right">L. {{ number_format($pago->pago_recibido, 2) }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="separator"></div>

        <!-- PIE DE PÁGINA -->
        <div class="footer">
            <div>Gracias por su compra!</div>
            <div>{{ now()->format('d/m/Y H:i:s') }}</div>
        </div>
    </div>
</body>
</html>
