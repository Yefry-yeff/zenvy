<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Factura {{ $factura->numero_factura }}</title>
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

        .factura-title {
            font-weight: bold;
            font-size: 20px;
            text-align: center;
            margin: 8px 0;
        }

        .factura-number {
            font-size: 18px;
            text-align: center;
            margin: 4px 0;
        }

        .duplicado-rango {
            font-size: 16px;
            text-align: center;
            margin: 4px 0;
        }

        .fecha-usuario {
            font-size: 16px;
            margin: 6px 0;
        }

        .consumidor-final {
            font-weight: bold;
            font-size: 18px;
            margin: 8px 0;
        }

        .table-header {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .product-row {
            font-size: 15px;
            margin-bottom: 3px;
        }

        .totals-section {
            font-size: 16px;
            margin: 8px 0;
        }

        .total-final {
            font-weight: bold;
            font-size: 18px;
            margin: 4px 0;
        }

        .valor-letras {
            font-size: 14px;
            margin: 8px 0;
            text-align: center;
        }

        .forma-pago {
            font-size: 16px;
            margin: 6px 0;
        }

        .cai-info {
            font-size: 14px;
            margin: 8px 0;
        }

        .footer-info {
            font-size: 13px;
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

        <!-- DUPLICADO Y RANGOS (solo últimos 8 dígitos) -->
        @if($caiFacturaImpresa)
            <div class="duplicado-rango">
                {{ substr(str_pad($caiFacturaImpresa['rango_inicio'], 8, '0', STR_PAD_LEFT), -8) }} - {{ substr(str_pad($caiFacturaImpresa['rango_final'], 8, '0', STR_PAD_LEFT), -8) }}
            </div>
        @endif

        <!-- CONSUMIDOR FINAL / INFORMACIÓN DEL CLIENTE -->
        <div class="consumidor-final" style="font-weight: normal; font-size: 16px;">
            @if($factura->rtn || ($factura->nombre_cliente && $factura->nombre_cliente != 'Consumidor Final'))
                @if($factura->rtn)
                    RTN: {{ $factura->rtn }}
                    @if($factura->nombre_cliente && $factura->nombre_cliente != 'Consumidor Final')
                        <br>
                    @endif
                @endif
                @if($factura->nombre_cliente && $factura->nombre_cliente != 'Consumidor Final')
                    CLIENTE: {{ $factura->nombre_cliente }}
                @endif
            @else
                <strong>CONSUMIDOR FINAL</strong>
            @endif
        </div>

        <!-- FECHA Y USUARIO -->
        <div class="fecha-usuario">
            {{ \Carbon\Carbon::parse($factura->created_at)->format('d/m/Y H:i:s') }} Usuario: {{ $factura->usuario ? $factura->usuario->name : 'Sistema' }}
        </div>

        <div class="separator"></div>

        <!-- TABLA DE PRODUCTOS -->
        <div class="table-header">
            <div class="product-table-row">
                <div class="col-uds"><strong>UDS</strong></div>
                <div class="col-descripcion"><strong>DESCRIPCION</strong></div>
                <div class="col-importe"><strong>IMPORTE</strong></div>
            </div>
        </div>

        @foreach($productos as $producto)
            @php
                // Calcular importe del producto SIN descuentos (cantidad × precio unitario)
                $importeProducto = $producto['cantidad'] * $producto['precio_unidad'];

                // Obtener descuentos desde la nueva estructura agrupada
                $descuentos = $producto['descuentos'] ?? [];
                $descuentoUnitario = $descuentos['Producto'] ?? 0;
                $descuentoIndividual = $descuentos['Individual'] ?? 0;
                $descuentoTerceraEdad = $descuentos['3ra edad'] ?? 0;
                $descuentoCuartaEdad = $descuentos['4ta edad'] ?? 0;

                // Sumar descuento de producto con descuento individual
                $descuentoProductoTotal = $descuentoUnitario + $descuentoIndividual;

                // Calcular total de descuentos de adulto mayor
                $descuentoAdultoMayor = $descuentoTerceraEdad + $descuentoCuartaEdad;
            @endphp
            <div class="product-row">
                <div class="product-table-row">
                    <div class="col-uds">
                        @if($producto['isv'] > 0)
                            {{ $producto['cantidad'] }}(G)
                        @else
                            {{ $producto['cantidad'] }}(E)
                        @endif
                    </div>
                    <div class="col-descripcion">
                        {{ $producto['nombre'] }}<br>
                        <span style="font-size: 13px;">{{ $producto['cantidad'] }} x L. {{ number_format($producto['precio_unidad'], 2) }}</span>

                        @if($descuentoProductoTotal > 0)
                            <br><span style="font-size: 15px;">
                                Descuento de producto
                            </span>
                        @endif

                        @if($descuentoTerceraEdad > 0)
                            <br><span style="font-size: 15px;">
                                Descuento - 25% 3ra edad
                            </span>
                        @endif

                        @if($descuentoCuartaEdad > 0)
                            <br><span style="font-size: 15px;">
                                Descuento - 35% 4ta edad
                            </span>
                        @endif
                    </div>
                    <div class="col-importe">
                        <!-- Importe del producto SIN descuentos -->
                        L. {{ number_format($importeProducto, 2) }}

                        @if($descuentoProductoTotal > 0)
                            <br><span style="font-size: 15px;">-L. {{ number_format($descuentoProductoTotal, 2) }}</span>
                        @endif

                        @if($descuentoTerceraEdad > 0)
                            <br><span style="font-size: 15px;">-L. {{ number_format($descuentoTerceraEdad, 2) }}</span>
                        @endif

                        @if($descuentoCuartaEdad > 0)
                            <br><span style="font-size: 15px;">-L. {{ number_format($descuentoCuartaEdad, 2) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div class="separator"></div>

        <!-- DETALLE DE TOTALES -->
        <div class="totals-section">
            <div class="table-layout">
                @php
                    // Calcular subtotal como suma de importes SIN descuentos (cantidad × precio_unidad)
                    $subtotalSinDescuentos = collect($productos)->sum(function($producto) {
                        return ($producto['cantidad'] ?? 0) * ($producto['precio_unidad'] ?? 0);
                    });

                    // Calcular total de TODOS los descuentos desde la tabla descuentos agrupados
                    $totalDescuentos = collect($productos)->sum(function($producto) {
                        return $producto['total_descuentos'] ?? 0;
                    });
                @endphp
                <div class="table-row">
                    <div class="table-cell-left">SUB-TOTAL</div>
                    <div class="table-cell-right">L. {{ number_format($subtotalSinDescuentos, 2) }}</div>
                </div>
                @if($totalDescuentos > 0)
                <div class="table-row">
                    <div class="table-cell-left">DESCUENTOS Y REBAJAS</div>
                    <div class="table-cell-right">-L. {{ number_format($totalDescuentos, 2) }}</div>
                </div>
                @endif
                <div class="table-row">
                    <div class="table-cell-left">IMPORTE EXONERADO</div>
                    <div class="table-cell-right">L. {{ number_format(collect($productos)->filter(function($producto) {
                        return ($producto['tasa_isv'] ?? 0) == 0;
                    })->sum(function($producto) {
                        return $producto['subtotal'] ?? 0;
                    }), 2) }}</div>
                </div>
                @php
                    // Calcular importes por tasa de ISV
                    $importe15 = collect($productos)->filter(function($producto) {
                        return ($producto['tasa_isv'] ?? 0) == 15;
                    })->sum(function($producto) {
                        return $producto['subtotal'] ?? 0;
                    });

                    $importe18 = collect($productos)->filter(function($producto) {
                        return ($producto['tasa_isv'] ?? 0) == 18;
                    })->sum(function($producto) {
                        return $producto['subtotal'] ?? 0;
                    });

                    // Calcular impuestos por tasa (usar campo 'isv' que siempre tiene el monto calculado)
                    $impuesto15 = collect($productos)->filter(function($producto) {
                        return ($producto['tasa_isv'] ?? 0) == 15;
                    })->sum(function($producto) {
                        return $producto['isv'] ?? 0;
                    });

                    $impuesto18 = collect($productos)->filter(function($producto) {
                        return ($producto['tasa_isv'] ?? 0) == 18;
                    })->sum(function($producto) {
                        return $producto['isv'] ?? 0;
                    });
                @endphp
                <div class="table-row">
                    <div class="table-cell-left">IMPORTE 15%</div>
                    <div class="table-cell-right">L. {{ number_format($importe15, 2) }}</div>
                </div>
                <div class="table-row">
                    <div class="table-cell-left">IMPORTE 18%</div>
                    <div class="table-cell-right">L. {{ number_format($importe18, 2) }}</div>
                </div>
                <div class="table-row">
                    <div class="table-cell-left">TOTAL IMPORTE</div>
                    <div class="table-cell-right">L. {{ number_format($factura->sub_total, 2) }}</div>
                </div>
                <div class="table-row">
                    <div class="table-cell-left">IMPUESTO DEL 15%</div>
                    <div class="table-cell-right">L. {{ number_format($impuesto15, 2) }}</div>
                </div>
                <div class="table-row">
                    <div class="table-cell-left">IMPUESTO DEL 18%</div>
                    <div class="table-cell-right">L. {{ number_format($impuesto18, 2) }}</div>
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
        <div class="valor-letras" style="font-size: 17px;">
            <strong>VALOR EN LETRAS:</strong><br>
            @php
                $total = $factura->total;
                $entero = floor($total);
                $centavos = round(($total - $entero) * 100);

                $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
                $decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
                $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
                $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

                // Convertir parte entera
                $letrasEntero = '';
                if ($entero == 0) {
                    $letrasEntero = 'CERO';
                } else {
                    if ($entero >= 1000) {
                        $miles = floor($entero / 1000);
                        if ($miles == 1) {
                            $letrasEntero .= 'MIL ';
                        } else {
                            $letrasEntero .= $unidades[$miles] . ' MIL ';
                        }
                        $entero %= 1000;
                    }

                    if ($entero >= 100) {
                        $c = floor($entero / 100);
                        if ($entero == 100) {
                            $letrasEntero .= 'CIEN ';
                        } else {
                            $letrasEntero .= $centenas[$c] . ' ';
                        }
                        $entero %= 100;
                    }

                    if ($entero >= 20) {
                        $d = floor($entero / 10);
                        $letrasEntero .= $decenas[$d];
                        $entero %= 10;
                        if ($entero > 0) $letrasEntero .= ' Y ' . $unidades[$entero];
                    } elseif ($entero >= 10) {
                        $letrasEntero .= $especiales[$entero - 10];
                    } elseif ($entero > 0) {
                        $letrasEntero .= $unidades[$entero];
                    }
                }

                $resultado = trim($letrasEntero) . ' LEMPIRAS';

                // Agregar centavos si existen
                if ($centavos > 0) {
                    $letrasCentavos = '';
                    if ($centavos >= 20) {
                        $d = floor($centavos / 10);
                        $letrasCentavos .= $decenas[$d];
                        $centavos %= 10;
                        if ($centavos > 0) $letrasCentavos .= ' Y ' . $unidades[$centavos];
                    } elseif ($centavos >= 10) {
                        $letrasCentavos .= $especiales[$centavos - 10];
                    } elseif ($centavos > 0) {
                        $letrasCentavos .= $unidades[$centavos];
                    }
                    $resultado .= ' CON ' . trim($letrasCentavos) . ' CENTAVOS';
                }
            @endphp
            {{ $resultado }}
        </div>

        <div class="separator"></div>

        <!-- FORMA DE PAGO -->
        @if(count($pagos) > 0)
            <div class="forma-pago">
                <strong>FORMA DE PAGO:</strong><br>
                @foreach($pagos as $pago)
                    {{ $pago['metodo'] }}: L. {{ number_format($pago['pago_recibido'], 2) }}<br>
                @endforeach
                @php
                    $totalPagado = collect($pagos)->sum(function($pago) {
                        return $pago['pago_recibido'] ?? 0;
                    });
                    $cambio = $totalPagado - $factura->total;
                @endphp
                @if($cambio > 0)
                    <strong>CAMBIO:</strong> L. {{ number_format($cambio, 2) }}<br>
                @endif
            </div>
        @endif

        <div class="separator"></div>

        <!-- DATOS DEL CAI -->
        @if($caiFacturaImpresa)
            <div class="cai-info">
                <strong>CAI:</strong> {{ $caiFacturaImpresa['cai'] }}<br>
                <strong>FECHA LIMITE:</strong> {{ \Carbon\Carbon::parse($caiFacturaImpresa['fecha_limite_emision'])->format('d/m/Y') }}<br>
                <strong>RANGO AUTORIZADO:</strong> {{ str_pad($caiFacturaImpresa['rango_inicio'], 8, '0', STR_PAD_LEFT) }} - {{ str_pad($caiFacturaImpresa['rango_final'], 8, '0', STR_PAD_LEFT) }}
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
