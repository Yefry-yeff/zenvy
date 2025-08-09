<div class="min-vh-100 bg-light">
    <div class="container-fluid">
        <!-- Barra de herramientas superior -->
        <div class="py-3 mb-3 bg-white row border-bottom">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice"></i>
                        Factura {{ $factura->numero_factura }}
                    </h4>
                    <div class="btn-group">
                        <button onclick="window.print()" class="btn btn-primary btn-sm">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <a href="{{ route('factura.pdf', $factura->id) }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-download"></i> Descargar PDF
                        </a>
                        @if($factura->factura_imagen)
                            <a href="{{ route('factura.imagen', $factura->id) }}" target="_blank" class="btn btn-info btn-sm">
                                <i class="fas fa-image"></i> Ver Imagen
                            </a>
                        @endif
                        <button onclick="history.back()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Volver
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido principal con dos columnas -->
        <div class="row">
            <!-- Columna izquierda: Vista de impresión -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="text-white card-header bg-primary">
                        <h5 class="mb-0"><i class="fas fa-receipt"></i> Vista de Impresión</h5>
                    </div>
                    <div class="card-body" style="overflow-y: auto; max-height: 80vh;">
                        <div class="thermal-receipt" style="width: 100%; max-width: 350px; margin: 0 auto; background: white; padding: 15px; font-family: 'Arial', sans-serif; font-size: 16px; line-height: 1.4; border: 1px solid #ddd;">

                            <!-- ENCABEZADO -->
                            <div class="mb-3 text-center">
                                <!-- LOGO DE LA EMPRESA -->
                                @if($empresa && $empresa->logo)
                                    <div class="mb-2">
                                        <img src="data:image/png;base64,{{ base64_encode($empresa->logo) }}"
                                             alt="Logo"
                                             style="max-width: 200px; max-height: 80px; object-fit: contain;">
                                    </div>
                                @endif

                                <!-- NOMBRE DE LA TIENDA (grande) -->
                                @if($tienda && $tienda->denominacion_social)
                                    <div style="font-weight: bold; font-size: 18px; text-transform: uppercase;">
                                        {{ $tienda->denominacion_social }}
                                    </div>
                                @endif

                                <!-- NOMBRE DE LA EMPRESA (mediano) -->
                                @if($empresa && $empresa->nombre)
                                    <div style="font-weight: bold; font-size: 15px; margin-top: 2px;">
                                        {{ $empresa->nombre }}
                                    </div>
                                @endif

                                <!-- RTN DE LA EMPRESA -->
                                @if($empresa && $empresa->rtn)
                                    <div style="font-size: 13px; margin-top: 1px;">
                                        RTN: {{ $empresa->rtn }}
                                    </div>
                                @endif

                                <!-- DIRECCIÓN TRIBUTARIA -->
                                @if($tienda && $tienda->domicilio_tributario)
                                    <div style="font-size: 13px; margin-top: 1px;">
                                        {{ $tienda->domicilio_tributario }}
                                    </div>
                                @endif

                                <!-- CORREO -->
                                @if($empresa && $empresa->correo)
                                    <div style="font-size: 12px; margin-top: 1px;">
                                        Email: {{ $empresa->correo }}
                                    </div>
                                @endif

                                <!-- TELÉFONO FORMATEADO -->
                                @if($empresa && $empresa->telefono)
                                    <div style="font-size: 12px; margin-top: 1px;">
                                        Tel: {{ $empresa->telefono }}
                                    </div>
                                @endif
                            </div>

                            <!-- SEPARADOR -->
                            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

                            <!-- INFORMACIÓN DE LA FACTURA -->
                            <div style="margin-bottom: 10px;">
                                <div><strong>FACTURA N°:</strong> {{ $factura->numero_factura }}</div>
                                <div><strong>FECHA:</strong> {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('d/m/Y H:i') }}</div>
                                @if($factura->nombre_cliente)
                                    <div><strong>CLIENTE:</strong> {{ $factura->nombre_cliente }}</div>
                                @endif
                            </div>

            <!-- INFORMACIÓN DEL CAI -->
            @if($caiFacturaImpresa)
                <div style="margin-bottom: 10px; font-size: 13px;">
                    <div><strong>CAI:</strong> {{ $caiFacturaImpresa->cai }}</div>
                    <div><strong>FECHA LÍMITE:</strong> {{ \Carbon\Carbon::parse($caiFacturaImpresa->fecha_limite_emision)->format('d/m/Y') }}</div>
                    <div><strong>RANGO:</strong> {{ str_pad($caiFacturaImpresa->rango_inicio, 8, '0', STR_PAD_LEFT) }} - {{ str_pad($caiFacturaImpresa->rango_final, 8, '0', STR_PAD_LEFT) }}</div>
                </div>
            @endif                            <!-- SEPARADOR -->
                            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

                            <!-- PRODUCTOS -->
                            <div style="margin-bottom: 10px;">
                                @foreach($productos as $producto)
                                    <div style="margin-bottom: 8px;">
                                        <div style="font-weight: bold;">{{ $producto->nombre }}</div>
                                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                                            <span>{{ $producto->cantidad }} x L. {{ number_format($producto->precio_unidad, 2) }}</span>
                                            <span>L. {{ number_format($producto->total, 2) }}</span>
                                        </div>
                                        @if($producto->isv > 0)
                                            <div style="font-size: 12px; color: #666;">
                                                ISV: L. {{ number_format($producto->isv, 2) }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <!-- SEPARADOR -->
                            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

                            <!-- TOTALES -->
                            <div style="margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span>SUBTOTAL:</span>
                                    <span>L. {{ number_format($factura->sub_total, 2) }}</span>
                                </div>
                                @if($factura->monto_descuento > 0)
                                    <div style="display: flex; justify-content: space-between;">
                                        <span>DESCUENTO:</span>
                                        <span>L. {{ number_format($factura->monto_descuento, 2) }}</span>
                                    </div>
                                @endif
                                <div style="display: flex; justify-content: space-between;">
                                    <span>ISV (15%):</span>
                                    <span>L. {{ number_format($factura->isv, 2) }}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 18px; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px;">
                                    <span>TOTAL:</span>
                                    <span>L. {{ number_format($factura->total, 2) }}</span>
                                </div>
                            </div>

                            <!-- MÉTODOS DE PAGO -->
                            @if(count($pagos) > 0)
                                <div style="margin-bottom: 10px;">
                                    <div style="font-weight: bold; margin-bottom: 5px; font-size: 15px;">MÉTODOS DE PAGO:</div>
                                    @foreach($pagos as $pago)
                                        <div style="display: flex; justify-content: space-between; font-size: 14px;">
                                            <span>{{ $pago->metodo }}:</span>
                                            <span>L. {{ number_format($pago->pago_recibido, 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- SEPARADOR -->
                            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

                            <!-- PIE DE PÁGINA -->
                            <div style="text-align: center; font-size: 9px;">
                                <div>¡Gracias por su compra!</div>
                                <div style="margin-top: 5px;">{{ now()->format('d/m/Y H:i:s') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: Visor PDF -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="text-white card-header bg-danger">
                        <h5 class="mb-0"><i class="fas fa-file-pdf"></i> Visor PDF</h5>
                    </div>
                    <div class="p-0 card-body">
                        <iframe
                            src="{{ route('factura.pdf.preview', $factura->id) }}"
                            width="100%"
                            height="600"
                            style="border: none; min-height: 80vh;"
                            id="pdfViewer">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos para impresión -->
<style>
    @media print {
        .container-fluid, .row, .col-md-6, .card, .card-header, .card-body {
            all: unset !important;
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .btn-group, .card-header, #pdfViewer {
            display: none !important;
        }

        .thermal-receipt {
            width: 72.1mm !important;
            max-width: 72.1mm !important;
            margin: 0 !important;
            padding: 3mm !important;
            font-size: 14px !important;
            page-break-inside: avoid;
            -webkit-print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 0;
        }
    }

    .thermal-receipt {
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .thermal-receipt img {
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }

    /* Loader para el iframe */
    #pdfViewer {
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="20" fill="none" stroke="%23007bff" stroke-width="4" stroke-dasharray="31.416" stroke-dashoffset="31.416"><animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/><animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/></circle></svg>') center center no-repeat;
        background-size: 50px 50px;
    }
</style>
