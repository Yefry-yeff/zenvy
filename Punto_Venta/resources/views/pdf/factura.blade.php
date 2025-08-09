<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Factura {{ $factura->numero_factura }}</title>
    <style>
        body {
            font-family: 'Courier', monospace;
            font-size: 9px;
            line-height: 1.2;
            margin: 0;
            padding: 5mm;
            width: 80mm;
        }
        
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 2px; }
        .mb-2 { margin-bottom: 4px; }
        .mb-3 { margin-bottom: 6px; }
        .mt-1 { margin-top: 2px; }
        .mt-2 { margin-top: 4px; }
        
        .logo {
            max-width: 60mm;
            max-height: 20mm;
            object-fit: contain;
        }
        
        .empresa-nombre {
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .empresa-info {
            font-size: 8px;
        }
        
        .separator {
            border-top: 1px dashed #000;
            margin: 3px 0;
        }
        
        .producto-item {
            margin-bottom: 3px;
        }
        
        .producto-nombre {
            font-weight: bold;
            font-size: 8px;
        }
        
        .producto-detalle {
            font-size: 8px;
            display: flex;
            justify-content: space-between;
        }
        
        .totales {
            font-size: 9px;
        }
        
        .total-final {
            font-weight: bold;
            font-size: 10px;
        }
        
        .cai-info {
            border: 1px solid #000;
            padding: 3px;
            font-size: 7px;
            margin: 5px 0;
        }
        
        .fiscal-info {
            border-top: 1px dashed #333;
            padding-top: 3px;
            margin-top: 3px;
            font-size: 6px;
        }
        
        .pie-pagina {
            text-align: center;
            font-size: 7px;
            margin-top: 8px;
        }
        
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .codigo-barra {
            font-size: 6px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- ENCABEZADO -->
    <div class="text-center mb-3">
        <!-- LOGO DE LA EMPRESA -->
        @if($empresa && $empresa->logo)
            <div class="mb-2">
                <img src="data:image/png;base64,{{ base64_encode($empresa->logo) }}" 
                     alt="Logo" class="logo">
            </div>
        @endif
        
        <!-- NOMBRE DE LA TIENDA -->
        @if($tienda && $tienda->denominacion_social)
            <div class="empresa-nombre">
                {{ $tienda->denominacion_social }}
            </div>
        @endif
        
        <!-- NOMBRE DE LA EMPRESA -->
        @if($empresa && $empresa->nombre)
            <div class="font-bold" style="font-size: 10px; margin-top: 2px;">
                {{ $empresa->nombre }}
            </div>
        @endif
        
        <!-- RTN DE LA EMPRESA -->
        @if($empresa && $empresa->rtn)
            <div class="empresa-info mt-1">
                RTN: {{ $empresa->rtn }}
            </div>
        @endif
        
        <!-- DIRECCIÓN TRIBUTARIA -->
        @if($direccion && $direccion->domicilio_tributario)
            <div class="empresa-info">
                {{ $direccion->domicilio_tributario }}
            </div>
        @endif
        
        <!-- CORREO -->
        @if($empresa && $empresa->correo)
            <div class="empresa-info">
                Email: {{ $empresa->correo }}
            </div>
        @endif
        
        <!-- TELÉFONO FORMATEADO -->
        @if($empresa && $empresa->telefono)
            @php
                $telefono = $empresa->telefono;
                $telefonoFormateado = strlen($telefono) == 8 
                    ? substr($telefono, 0, 4) . '-' . substr($telefono, 4, 4)
                    : $telefono;
            @endphp
            <div class="empresa-info">
                Tel: {{ $telefonoFormateado }}
            </div>
        @endif
        
        <div class="separator"></div>
    </div>

    <!-- INFORMACIÓN DE LA FACTURA -->
    <div class="mb-3" style="font-size: 9px;">
        <div><strong>Factura #:</strong> {{ $factura->numero_factura }}</div>
        <div><strong>Fecha:</strong> {{ $factura->fecha_emision->format('d/m/Y H:i') }}</div>
        <div><strong>Cliente:</strong> {{ $factura->nombre_cliente }}</div>
        @if($factura->rtn)
            <div><strong>RTN:</strong> {{ $factura->rtn }}</div>
        @endif
        
        <!-- INFORMACIÓN DEL CAI -->
        @if($caiFacturaImpresa)
            <div class="cai-info">
                <div><strong>CAI:</strong> {{ $caiFacturaImpresa->cai }}</div>
                <div><strong>Fecha límite emisión:</strong> {{ \Carbon\Carbon::parse($caiFacturaImpresa->fecha_limite_emision)->format('d/m/Y') }}</div>
                <div><strong>Rango autorizado:</strong></div>
                <div>{{ $caiFacturaImpresa->rango_inicio }} - {{ $caiFacturaImpresa->rango_final }}</div>
                
                <!-- INFORMACIÓN FISCAL ADICIONAL -->
                <div class="fiscal-info">
                    <div>Este documento es válido para efectos fiscales</div>
                    <div>Resolución No. 123-2025 del {{ now()->format('d/m/Y') }}</div>
                    <div>Autorización de impresión vigente hasta: {{ \Carbon\Carbon::parse($caiFacturaImpresa->fecha_limite_emision)->format('d/m/Y') }}</div>
                    <div>Factura generada por sistema POS</div>
                </div>
            </div>
        @endif
        
        <div class="separator"></div>
    </div>

    <!-- PRODUCTOS -->
    <div class="mb-3" style="font-size: 8px;">
        <div class="font-bold">PRODUCTOS:</div>
        
        @foreach($productos as $producto)
            <div class="producto-item">
                <div class="producto-nombre">{{ $producto->nombre }}</div>
                <div class="flex-between">
                    <span>{{ $producto->cantidad }} x L.{{ number_format($producto->precio_unidad, 2) }}</span>
                    <span>L.{{ number_format($producto->total, 2) }}</span>
                </div>
                @if($producto->codigo_barra)
                    <div class="codigo-barra">Código: {{ $producto->codigo_barra }}</div>
                @endif
            </div>
        @endforeach
        
        <div class="separator"></div>
    </div>

    <!-- TOTALES -->
    <div class="mb-3 totales">
        <div class="flex-between">
            <span>Subtotal:</span>
            <span>L.{{ number_format($factura->sub_total, 2) }}</span>
        </div>
        @if($factura->isv > 0)
            <div class="flex-between">
                <span>ISV (15%):</span>
                <span>L.{{ number_format($factura->isv, 2) }}</span>
            </div>
        @endif
        <div style="border-top: 1px dashed #000; margin: 3px 0;"></div>
        <div class="flex-between total-final">
            <span>TOTAL:</span>
            <span>L.{{ number_format($factura->total, 2) }}</span>
        </div>
    </div>

    <!-- MÉTODOS DE PAGO -->
    <div class="mb-3 totales">
        <div class="separator"></div>
        <div class="font-bold">FORMA DE PAGO:</div>
        @foreach($pagos as $pago)
            <div class="flex-between">
                <span>{{ $pago->metodo }}:</span>
                <span>L.{{ number_format($pago->pago_recibido, 2) }}</span>
            </div>
        @endforeach
        
        @php
            $pagoEfectivo = collect($pagos)->firstWhere('metodo', 'Efectivo');
            $montoEfectivo = $pagoEfectivo ? $pagoEfectivo->pago_recibido : 0;
            $cambio = $montoEfectivo > $factura->total ? ($montoEfectivo - $factura->total) : 0;
        @endphp
        
        @if($cambio > 0)
            <div class="flex-between mt-1">
                <span>Cambio:</span>
                <span>L.{{ number_format($cambio, 2) }}</span>
            </div>
        @endif
    </div>

    <!-- PIE DE PÁGINA -->
    <div class="pie-pagina">
        <div class="separator"></div>
        <div>¡Gracias por su compra!</div>
        <div>Conserve este ticket</div>
        <div class="mt-1">{{ now()->format('d/m/Y H:i:s') }}</div>
    </div>
</body>
</html>
