<div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
    <div class="thermal-receipt" style="width: 80mm; background: white; padding: 5mm; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.2;">
        
        <!-- ENCABEZADO -->
        <div class="text-center mb-3">
            <div style="font-weight: bold; font-size: 14px;">ZENVY POS</div>
            <div style="font-size: 10px;">Sistema de Punto de Venta</div>
            <div style="font-size: 10px;">================================</div>
        </div>

        <!-- INFORMACIÓN DE LA FACTURA -->
        <div class="mb-3" style="font-size: 11px;">
            <div><strong>Factura #:</strong> {{ $factura->numero_factura }}</div>
            <div><strong>Fecha:</strong> {{ $factura->fecha_emision->format('d/m/Y H:i') }}</div>
            <div><strong>Cliente:</strong> {{ $factura->nombre_cliente }}</div>
            @if($factura->rtn)
                <div><strong>RTN:</strong> {{ $factura->rtn }}</div>
            @endif
            <div style="font-size: 10px;">================================</div>
        </div>

        <!-- PRODUCTOS -->
        <div class="mb-3" style="font-size: 10px;">
            <div style="font-weight: bold;">PRODUCTOS:</div>
            
            @foreach($productos as $producto)
                <div class="mb-2">
                    <div style="font-weight: bold;">{{ $producto->nombre }}</div>
                    <div class="d-flex justify-content-between">
                        <span>{{ $producto->cantidad }} x L.{{ number_format($producto->precio_unidad, 2) }}</span>
                        <span>L.{{ number_format($producto->total, 2) }}</span>
                    </div>
                    @if($producto->codigo_barra)
                        <div style="font-size: 9px; color: #666;">Código: {{ $producto->codigo_barra }}</div>
                    @endif
                </div>
            @endforeach
            
            <div style="font-size: 10px;">================================</div>
        </div>

        <!-- TOTALES -->
        <div class="mb-3" style="font-size: 11px;">
            <div class="d-flex justify-content-between">
                <span>Subtotal:</span>
                <span>L.{{ number_format($factura->sub_total, 2) }}</span>
            </div>
            @if($factura->isv > 0)
                <div class="d-flex justify-content-between">
                    <span>ISV (15%):</span>
                    <span>L.{{ number_format($factura->isv, 2) }}</span>
                </div>
            @endif
            <div style="font-size: 10px; margin: 5px 0;">--------------------------------</div>
            <div class="d-flex justify-content-between" style="font-weight: bold; font-size: 12px;">
                <span>TOTAL:</span>
                <span>L.{{ number_format($factura->total, 2) }}</span>
            </div>
        </div>

        <!-- MÉTODOS DE PAGO -->
        <div class="mb-3" style="font-size: 11px;">
            <div style="font-size: 10px;">================================</div>
            <div style="font-weight: bold;">FORMA DE PAGO:</div>
            @foreach($pagos as $pago)
                <div class="d-flex justify-content-between">
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
                <div class="d-flex justify-content-between" style="margin-top: 5px;">
                    <span>Cambio:</span>
                    <span>L.{{ number_format($cambio, 2) }}</span>
                </div>
            @endif
        </div>

        <!-- PIE DE PÁGINA -->
        <div class="text-center" style="font-size: 9px; margin-top: 10px;">
            <div style="font-size: 10px;">================================</div>
            <div>¡Gracias por su compra!</div>
            <div>Conserve este ticket</div>
            <div style="margin-top: 5px;">{{ now()->format('d/m/Y H:i:s') }}</div>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="text-center mt-4" style="margin-top: 15px;">
            <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
                🖨️ Imprimir
            </button>
            <button wire:click="volverAVentas" class="btn btn-secondary btn-sm">
                ← Volver a Ventas
            </button>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .thermal-receipt, .thermal-receipt * {
            visibility: visible;
        }
        .thermal-receipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 80mm !important;
            margin: 0 !important;
            padding: 5mm !important;
        }
        .btn {
            display: none !important;
        }
        .mt-4 {
            display: none !important;
        }
    }
    
    .thermal-receipt {
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        border: 1px solid #ddd;
    }
</style>
