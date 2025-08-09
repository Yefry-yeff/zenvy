<div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
    <div class="thermal-receipt" style="width: 80mm; background: white; padding: 5mm; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.2;">

        <!-- ENCABEZADO -->
        <div class="mb-3 text-center">
            <!-- LOGO DE LA EMPRESA -->
            @if($empresa && $empresa->logo)
                <div class="mb-2">
                    <img src="data:image/png;base64,{{ base64_encode($empresa->logo) }}"
                         alt="Logo"
                         style="max-width: 60mm; max-height: 20mm; object-fit: contain;">
                </div>
            @endif

            <!-- NOMBRE DE LA TIENDA (grande) -->
            @if($tienda && $tienda->denominacion_social)
                <div style="font-weight: bold; font-size: 16px; text-transform: uppercase;">
                    {{ $tienda->denominacion_social }}
                </div>
            @endif

            <!-- NOMBRE DE LA EMPRESA (mediano) -->
            @if($empresa && $empresa->nombre)
                <div style="font-weight: bold; font-size: 12px; margin-top: 2px;">
                    {{ $empresa->nombre }}
                </div>
            @endif

            <!-- RTN DE LA EMPRESA -->
            @if($empresa && $empresa->rtn)
                <div style="font-size: 10px; margin-top: 1px;">
                    RTN: {{ $empresa->rtn }}
                </div>
            @endif

            <!-- DIRECCIÓN TRIBUTARIA -->
            @if($tienda && $tienda->domicilio_tributario)
                <div style="font-size: 10px; margin-top: 1px;">
                    {{ $tienda->domicilio_tributario }}
                </div>
            @endif

            <!-- CORREO -->
            @if($empresa && $empresa->correo)
                <div style="font-size: 9px; margin-top: 1px;">
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
                <div style="font-size: 9px; margin-top: 1px;">
                    Tel: {{ $telefonoFormateado }}
                </div>
            @endif

            <div style="font-size: 10px; margin-top: 5px;">================================</div>
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

        <!-- INFORMACIÓN FISCAL ADICIONAL -->
        <div style="font-size: 8px; margin-top: 8px; text-align: center; border-top: 1px dashed #000; padding-top: 5px;">
            <div style="margin-bottom: 3px;">
                <strong>ORIGINAL: CLIENTE</strong>
            </div>
                        <!-- INFORMACIÓN DEL CAI -->
            @if($caiFacturaImpresa)
                <div style="margin-top: 8px; padding: 3px; border: 1px solid #000; font-size: 9px;">
                    <div><strong>CAI:</strong> {{ $caiFacturaImpresa->cai }}</div>
                    <div><strong>Fecha límite emisión:</strong> {{ \Carbon\Carbon::parse($caiFacturaImpresa->fecha_limite_emision)->format('d/m/Y') }}</div>
                    <div><strong>Rango autorizado:</strong></div>
                    <div>{{ $caiFacturaImpresa->rango_inicio }} - {{ $caiFacturaImpresa->rango_final }}</div>
                </div>
            @endif
        </div>

        <!-- PIE DE PÁGINA -->
        <div class="text-center" style="font-size: 9px; margin-top: 10px;">
            <div style="font-size: 10px;">================================</div>
            <div>¡Gracias por su compra!</div>
            <div>Obligado Tributario Emisor</div>
            <div style="margin-top: 5px;">{{ now()->format('d/m/Y H:i:s') }}</div>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="mt-4 text-center" style="margin-top: 15px;">
            <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
                🖨️ Imprimir
            </button>

            <!-- Botones para imagen de factura -->
            @if($factura->factura_imagen)
                <a href="{{ route('factura.imagen', $factura->id) }}" target="_blank" class="btn btn-info btn-sm me-2">
                    🖼️ Ver Imagen
                </a>
                <a href="{{ route('factura.imagen.descargar', $factura->id) }}" class="btn btn-success btn-sm me-2">
                    💾 Descargar
                </a>
            @else
                <span class="text-muted small">📷 Imagen no disponible</span>
            @endif

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

        /* Optimizar logo para impresión */
        .thermal-receipt img {
            max-width: 50mm !important;
            max-height: 15mm !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
    }

    .thermal-receipt {
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        border: 1px solid #ddd;
    }

    /* Asegurar que el logo se vea bien en pantalla también */
    .thermal-receipt img {
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }
</style>
