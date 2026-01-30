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

        /* Marca de agua para facturas anuladas */
        .watermark {
            position: fixed;
            top: 30%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            font-weight: bold;
            color: rgba(255, 0, 0, 0.25);
            z-index: 9999;
            pointer-events: none;
            white-space: nowrap;
            letter-spacing: 5px;
        }
    </style>
</head>
<body>
    @php
        // Si la factura viene de la web, cargar los datos del pedido original
        $pedidoWeb = null;
        if(isset($factura->origen_web) && $factura->origen_web) {
            $pedidoWeb = \App\Models\PedidoWeb::where('factura_id', $factura->id)->first();
        }
    @endphp
    
    @if($factura->estado_factura_id == 2)
        <div class="watermark">ANULADA</div>
    @endif
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
            @if($pedidoWeb)
                <br><span style="font-size: 14px; color: #000;">Pedido Web #{{ $pedidoWeb->numero_pedido }}</span>
            @endif
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
            @php
                // Usar datos del pedido web si está disponible
                $clienteNombre = $pedidoWeb ? $pedidoWeb->cliente_nombre : $factura->nombre_cliente;
                $clienteRTN = $pedidoWeb ? $pedidoWeb->cliente_rtn : $factura->rtn;
                $clienteEmail = $pedidoWeb ? $pedidoWeb->cliente_email : null;
                $clienteTelefono = $pedidoWeb ? $pedidoWeb->cliente_telefono : null;
                $clienteDireccion = $pedidoWeb ? $pedidoWeb->cliente_direccion : null;
            @endphp
            
            @if($clienteRTN || ($clienteNombre && $clienteNombre != 'Consumidor Final'))
                @if($clienteRTN)
                    RTN: {{ $clienteRTN }}
                    @if($clienteNombre && $clienteNombre != 'Consumidor Final')
                        <br>
                    @endif
                @endif
                @if($clienteNombre && $clienteNombre != 'Consumidor Final')
                    CLIENTE: {{ $clienteNombre }}
                @endif
                <br>
                No. O/C Exenta:<br>
                No. REG DE EXONERADO:<br>
                No. REG DE LA SAG:
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
                // Para facturas del API, usar valores ya calculados
                if(isset($factura->origen_web) && $factura->origen_web) {
                    // Usar directamente los valores del API
                    $importeProducto = $producto['subtotal'] ?? 0;
                    $isvProducto = $producto['isv'] ?? 0;
                    $precioSinIsv = $producto['precio_unidad'] ?? 0;
                } else {
                    // Calcular precio sin ISV para facturas normales
                    $precioSinIsv = ($producto['tasa_isv'] ?? 0) == 15 ? $producto['precio_unidad'] / 1.15 : $producto['precio_unidad'];
                    
                    // Calcular importe del producto SIN descuentos (cantidad × precio unitario sin ISV)
                    $importeProducto = $producto['cantidad'] * $precioSinIsv;
                }

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
                        <span style="font-size: 13px;">
                            {{ $producto['cantidad'] }}
                            @if(isset($producto['unidad_nombre']) && $producto['unidad_nombre'])
                                {{ $producto['unidad_nombre'] }}
                            @endif
                            x L. {{ number_format($precioSinIsv, 2) }}
                        </span>

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
                    // Separar descuentos de productos de descuentos de factura
                    $descuentosProductos = collect($productos)->sum(function($producto) {
                        return $producto['total_descuentos'] ?? 0;
                    });
                    
                    // Si es factura del API (origen_web), usar valores ya calculados
                    if(isset($factura->origen_web) && $factura->origen_web) {
                        // Para facturas del API, usar directamente los valores del modelo
                        // que ya están correctamente calculados
                        $importeGravado = 0;
                        $importeExento = 0;
                        $impuestoVenta = 0;
                        
                        foreach($productos as $producto) {
                            // Los valores ya vienen correctos del API
                            $subtotalItem = $producto['subtotal'] ?? 0;
                            $isvItem = $producto['isv'] ?? 0;
                            
                            // Todo en facturas web es gravado (con ISV)
                            $importeGravado += $subtotalItem;
                            $impuestoVenta += $isvItem;
                        }
                    } else {
                        // Para facturas normales, calcular como antes
                        $importeGravado = 0;
                        $importeExento = 0;
                        $impuestoVenta = 0;
                        
                        foreach($productos as $producto) {
                            // Calcular precio sin ISV
                            $precioSinIsvItem = ($producto['tasa_isv'] ?? 0) == 15 ? $producto['precio_unidad'] / 1.15 : $producto['precio_unidad'];
                            
                            $subtotalItem = ($producto['cantidad'] ?? 0) * $precioSinIsvItem;
                            $descuentoItem = $producto['total_descuentos'] ?? 0;
                            $subtotalConDescuento = $subtotalItem - $descuentoItem;
                            
                            // Separar por tipo de ISV
                            if(($producto['tasa_isv'] ?? 0) == 15) {
                                // Producto gravado
                                $importeGravado += $subtotalConDescuento;
                                // El ISV se calcula sobre el importe sin ISV
                                $impuestoVenta += $subtotalConDescuento * 0.15;
                            } else if(($producto['tasa_isv'] ?? 0) == 0) {
                                $importeExento += $subtotalConDescuento;
                            }
                        }
                    }

                    // Sub-Total = Importe Gravado + Importe Exento (sin ISV)
                    $subTotal = $importeGravado + $importeExento;

                    // Recalcular el descuento de factura basado en el porcentaje y el nuevo subtotal
                    $porcentajeDescuento = $factura->porc_descuento ?? 0;
                    $descuentoFactura = $porcentajeDescuento > 0 ? ($subTotal * $porcentajeDescuento / 100) : 0;

                    // Total a Pagar = Sub-Total - Descuento Factura + Impuesto
                    $totalAPagar = $subTotal - $descuentoFactura + $impuestoVenta;
                @endphp

                <div class="table-row">
                    <div class="table-cell-left">Importe Gravado</div>
                    <div class="table-cell-right">L {{ number_format($importeGravado, 2) }}</div>
                </div>

                <div class="table-row">
                    <div class="table-cell-left">Importe Exento</div>
                    <div class="table-cell-right">L {{ number_format($importeExento, 2) }}</div>
                </div>

                <div class="table-row">
                    <div class="table-cell-left">Sub-Total</div>
                    <div class="table-cell-right">L {{ number_format($subTotal, 2) }}</div>
                </div>

                @if($descuentoFactura > 0)
                <div class="table-row">
                    <div class="table-cell-left">Descuentos y rebajas ({{ $factura->porc_descuento ?? 0 }}%)</div>
                    <div class="table-cell-right">L {{ number_format($descuentoFactura, 2) }}</div>
                </div>
                @endif

                <div class="table-row">
                    <div class="table-cell-left">Impuesto sobre venta (15%)</div>
                    <div class="table-cell-right">L {{ number_format($impuestoVenta, 2) }}</div>
                </div>

            </div>

            <div class="total-final">
                <div class="table-layout">
                    <div class="table-row">
                        <div class="table-cell-left"><strong>Total a Pagar</strong></div>
                        <div class="table-cell-right"><strong>L {{ number_format($totalAPagar, 2) }}</strong></div>
                    </div>
                </div>
            </div>

            @if($pedidoWeb && isset($pedidoWeb->metadata['shipping_cost']) && $pedidoWeb->metadata['shipping_cost'] > 0)
            <div style="margin-top: 8px;">
                <div class="table-layout">
                    <div class="table-row" style="font-size: 16px;">
                        <div class="table-cell-left">Costo de Envío</div>
                        <div class="table-cell-right">L {{ number_format($pedidoWeb->metadata['shipping_cost'], 2) }}</div>
                    </div>
                </div>
            </div>
            @endif

            @if($descuentosProductos > 0)
            <div style="margin-top: 8px;">
                <div class="table-layout">
                    <div class="table-row" style="font-size: 16px;">
                        <div class="table-cell-left">Ahorros</div>
                        <div class="table-cell-right">L {{ number_format($descuentosProductos, 2) }}</div>
                    </div>
                </div>
            </div>
            @endif
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

                // Función auxiliar para convertir números menores a 1000
                function convertirGrupo($num, $unidades, $decenas, $especiales, $centenas) {
                    $texto = '';
                    
                    if ($num >= 100) {
                        $c = floor($num / 100);
                        if ($num == 100) {
                            $texto .= 'CIEN ';
                        } else {
                            $texto .= $centenas[$c] . ' ';
                        }
                        $num %= 100;
                    }

                    if ($num >= 20) {
                        $d = floor($num / 10);
                        $texto .= $decenas[$d];
                        $num %= 10;
                        if ($num > 0) $texto .= ' Y ' . $unidades[$num];
                    } elseif ($num >= 10) {
                        $texto .= $especiales[$num - 10];
                    } elseif ($num > 0) {
                        $texto .= $unidades[$num];
                    }
                    
                    return $texto;
                }

                // Convertir parte entera
                $letrasEntero = '';
                if ($entero == 0) {
                    $letrasEntero = 'CERO';
                } else {
                    // Manejar millones
                    if ($entero >= 1000000) {
                        $millones = floor($entero / 1000000);
                        if ($millones == 1) {
                            $letrasEntero .= 'UN MILLON ';
                        } else {
                            $letrasEntero .= convertirGrupo($millones, $unidades, $decenas, $especiales, $centenas) . ' MILLONES ';
                        }
                        $entero %= 1000000;
                    }
                    
                    // Manejar miles
                    if ($entero >= 1000) {
                        $miles = floor($entero / 1000);
                        if ($miles == 1) {
                            $letrasEntero .= 'MIL ';
                        } else {
                            $letrasEntero .= convertirGrupo($miles, $unidades, $decenas, $especiales, $centenas) . ' MIL ';
                        }
                        $entero %= 1000;
                    }

                    // Manejar centenas, decenas y unidades
                    if ($entero > 0) {
                        $letrasEntero .= convertirGrupo($entero, $unidades, $decenas, $especiales, $centenas);
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
