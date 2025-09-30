<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Listado de Productos</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4472C4;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            color: #4472C4;
            margin: 0 0 5px 0;
            font-weight: bold;
        }

        .header .subtitle {
            font-size: 11px;
            color: #666;
            margin: 0;
        }

        .info-box {
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 9px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
            padding: 8px 4px;
            text-align: center;
            border: 1px solid #fff;
            font-size: 9px;
        }

        td {
            padding: 6px 4px;
            border: 1px solid #ddd;
            font-size: 8px;
            vertical-align: top;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .precio {
            font-weight: bold;
            color: #28a745;
        }

        .origen-valencia {
            background-color: #FFF3CD;
            color: #856404;
        }

        .origen-paperland {
            background-color: #D4EDDA;
            color: #155724;
        }

        .footer {
            position: fixed;
            bottom: 10mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .page-break {
            page-break-after: always;
        }

        /* Ajustes para celdas más pequeñas */
        .col-id { width: 4%; }
        .col-codigo { width: 12%; }
        .col-nombre { width: 28%; }
        .col-marca { width: 12%; }
        .col-categoria { width: 15%; }
        .col-subcategoria { width: 12%; }
        .col-precio { width: 10%; }
        .col-origen { width: 7%; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 LISTADO DE PRODUCTOS</h1>
        <p class="subtitle">Sistema ZENVY - Gestión de Inventario</p>
    </div>

    <div class="info-box">
        <strong>Información del Reporte:</strong><br>
        📅 Generado el: {{ $fechaGeneracion }}<br>
        📊 Total de productos: {{ $totalProductos }}<br>
        🔍 Filtros aplicados: {{ $filtrosAplicados }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th class="col-codigo">Código</th>
                <th class="col-nombre">Nombre</th>
                <th class="col-marca">Marca</th>
                <th class="col-categoria">Categoría</th>
                <th class="col-subcategoria">Subcategoría</th>
                <th class="col-precio">Precio</th>
                <th class="col-origen">Origen</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $index => $producto)
                <tr>
                    <td class="text-center">{{ $producto->id }}</td>
                    <td>{{ $producto->codigo_barra ?: 'Sin código' }}</td>
                    <td>{{ $producto->nombre }}</td>
                    <td>{{ $producto->marca->nombre ?? 'Sin marca' }}</td>
                    <td>{{ $producto->subcategoria->categoria->nombre ?? 'N/A' }}</td>
                    <td>{{ $producto->subcategoria->nombre ?? 'N/A' }}</td>
                    <td class="text-right precio">L. {{ number_format($producto->precio_base, 2) }}</td>
                    <td class="text-center {{ $producto->producto_valencia ? 'origen-valencia' : 'origen-paperland' }}">
                        {{ $producto->producto_valencia ? '🏢 VLC' : '🏠 PPL' }}
                    </td>
                </tr>

                @if(($index + 1) % 35 == 0 && !$loop->last)
                    </tbody>
                    </table>
                    <div class="page-break"></div>

                    <table>
                        <thead>
                            <tr>
                                <th class="col-id">ID</th>
                                <th class="col-codigo">Código</th>
                                <th class="col-nombre">Nombre</th>
                                <th class="col-marca">Marca</th>
                                <th class="col-categoria">Categoría</th>
                                <th class="col-subcategoria">Subcategoría</th>
                                <th class="col-precio">Precio</th>
                                <th class="col-origen">Origen</th>
                            </tr>
                        </thead>
                        <tbody>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Sistema ZENVY - Gestión de Inventario | Reporte generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
