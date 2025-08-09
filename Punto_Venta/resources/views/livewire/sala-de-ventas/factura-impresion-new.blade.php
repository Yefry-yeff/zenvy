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
                        <div class="thermal-receipt" style="width: 100%; max-width: 350px; margin: 0 auto; background: white; padding: 15px; font-family: 'Arial', sans-serif; font-size: 18px; line-height: 1.4; border: 1px solid #ddd;">

                            <!-- ENCABEZADO - DATOS DE LA EMPRESA -->
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
                                    <div style="font-weight: bold; font-size: 20px; text-transform: uppercase;">
                                        {{ $tienda->denominacion_social }}
                                    </div>
                                @endif

                                <!-- NOMBRE DE LA EMPRESA (mediano) -->
                                @if($empresa && $empresa->nombre)
                                    <div style="font-weight: bold; font-size: 17px; margin-top: 2px;">
                                        {{ $empresa->nombre }}
                                    </div>
                                @endif

                                <!-- RTN DE LA EMPRESA -->
                                @if($empresa && $empresa->rtn)
                                    <div style="font-size: 15px; margin-top: 1px;">
                                        RTN: {{ $empresa->rtn }}
                                    </div>
                                @endif

                                <!-- DIRECCIÓN TRIBUTARIA -->
                                @if($tienda && $tienda->domicilio_tributario)
                                    <div style="font-size: 15px; margin-top: 1px;">
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

                            <!-- FACTURA VENTA -->
                            <div style="text-align: center; font-weight: bold; font-size: 18px; margin: 8px 0;">
                                FACTURA VENTA
                            </div>

                            <!-- NÚMERO DE FACTURA -->
                            <div style="text-align: center; font-size: 16px; margin: 4px 0;">
                                {{ $factura->numero_factura }}
                            </div>

                            <!-- DUPLICADO Y RANGOS -->
                            @if($caiFacturaImpresa)
                                <div style="text-align: center; font-size: 14px; margin: 4px 0;">
                                    (DUPLICADO) {{ str_pad($caiFacturaImpresa->rango_inicio, 8, '0', STR_PAD_LEFT) }} - {{ str_pad($caiFacturaImpresa->rango_final, 8, '0', STR_PAD_LEFT) }}
                                </div>
                            @endif

                            <!-- FECHA Y USUARIO -->
                            <div style="font-size: 14px; margin: 6px 0;">
                                {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('m.d.Y.H.i') }} {{ \Carbon\Carbon::parse($factura->fecha_emision)->format('A') }} Usuario: {{ $factura->user_id ?? 'Admin' }}
                            </div>

                            <!-- SEPARADOR -->
                            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

                            <!-- CONSUMIDOR FINAL -->
                            <div style="font-weight: bold; font-size: 16px; margin: 8px 0;">
                                CONSUMIDOR FINAL
                                @if($factura->nombre_cliente && $factura->nombre_cliente != 'CONSUMIDOR FINAL')
                                    <br>{{ $factura->nombre_cliente }}
                                @endif
                            </div>

                            <!-- TABLA DE PRODUCTOS -->
                            <div style="font-weight: bold; font-size: 14px; margin-bottom: 4px; display: flex;">
                                <div style="width: 15%; text-align: center;">UDS</div>
                                <div style="width: 55%; padding: 0 2px;">DESCRIPCION</div>
                                <div style="width: 30%; text-align: right;">IMPORTE</div>
                            </div>

                            @foreach($productos as $producto)
                                <div style="margin-bottom: 3px; font-size: 13px;">
                                    <div style="display: flex;">
                                        <div style="width: 15%; text-align: center;">
                                            @if($producto->isv > 0)
                                                {{ $producto->cantidad }}(G)
                                            @else
                                                {{ $producto->cantidad }}(E)
                                            @endif
                                        </div>
                                        <div style="width: 55%; padding: 0 2px;">
                                            {{ $producto->nombre }}<br>
                                            <span style="font-size: 11px;">{{ $producto->cantidad }} x L. {{ number_format($producto->precio_unidad, 2) }}</span>
                                        </div>
                                        <div style="width: 30%; text-align: right;">
                                            L. {{ number_format($producto->total, 2) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <!-- SEPARADOR -->
                            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

                            <!-- DETALLE DE TOTALES -->
                            <div style="font-size: 14px; margin: 8px 0;">
                                <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                                    <span>SUB-TOTAL</span>
                                    <span>L. {{ number_format($factura->sub_total, 2) }}</span>
                                </div>
                                @if($factura->monto_descuento > 0)
                                <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                                    <span>DESCUENTOS Y REBAJAS</span>
                                    <span>L. {{ number_format($factura->monto_descuento, 2) }}</span>
                                </div>
                                @endif
                                <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                                    <span>IMPORTE EXONERADO</span>
                                    <span>L. {{ number_format($productos->where('isv', 0)->sum('total'), 2) }}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                                    <span>IMPORTE 15%</span>
                                    <span>L. {{ number_format($productos->where('isv', '>', 0)->sum('total'), 2) }}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                                    <span>IMPORTE 18%</span>
                                    <span>L. 0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                                    <span>TOTAL IMPORTE</span>
                                    <span>L. {{ number_format($factura->sub_total, 2) }}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                                    <span>IMPUESTO DEL 15%</span>
                                    <span>L. {{ number_format($factura->isv, 2) }}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                                    <span>IMPUESTO DEL 18%</span>
                                    <span>L. 0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin: 2px 0;">
                                    <span>TOTAL IMPUESTOS</span>
                                    <span>L. {{ number_format($factura->isv, 2) }}</span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; margin: 4px 0;">
                                    <span>TOTAL</span>
                                    <span>L. {{ number_format($factura->total, 2) }}</span>
                                </div>
                            </div>

                            <!-- SEPARADOR -->
                            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

                            <!-- VALOR EN LETRAS -->
                            <div style="font-size: 12px; margin: 8px 0; text-align: center;">
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

                            <!-- SEPARADOR -->
                            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

                            <!-- FORMA DE PAGO -->
                            @if(count($pagos) > 0)
                                <div style="font-size: 14px; margin: 6px 0;">
                                    <strong>FORMA DE PAGO:</strong><br>
                                    @foreach($pagos as $pago)
                                        {{ $pago->metodo }}: L. {{ number_format($pago->pago_recibido, 2) }}<br>
                                    @endforeach
                                </div>
                            @endif

                            <!-- SEPARADOR -->
                            <div style="border-top: 1px dashed #000; margin: 10px 0;"></div>

                            <!-- INFORMACIÓN DEL CAI -->
                            @if($caiFacturaImpresa)
                                <div style="margin-bottom: 10px; font-size: 12px;">
                                    <div><strong>CAI:</strong> {{ $caiFacturaImpresa->cai }}</div>
                                    <div><strong>FECHA LÍMITE:</strong> {{ \Carbon\Carbon::parse($caiFacturaImpresa->fecha_limite_emision)->format('d/m/Y') }}</div>
                                </div>
                            @endif

                            <!-- PIE DE PÁGINA -->
                            <div style="font-size: 11px; margin-top: 10px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Grabado = G</span>
                                    <span>Obligado Tributario Emisor</span>
                                    <span>Exento = E</span>
                                </div>
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
            font-size: 16px !important;
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
