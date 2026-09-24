<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th colspan="11" style="background-color: #4CAF50; color: white; font-size: 16px; text-align: center; padding: 10px;">
                🎁 HISTORIAL DE REGALÍAS Y REQUISICIONES (BODEGA 2)
            </th>
        </tr>
        <tr>
            <th colspan="11" style="background-color: #f5f5f5; text-align: left; padding: 8px;">
                <strong>Fecha de generación:</strong> {{ $fechaGeneracion }}
            </th>
        </tr>
        <tr>
            <th colspan="11" style="background-color: #f5f5f5; text-align: left; padding: 8px;">
                <strong>Usuario:</strong> {{ $usuarioReporte }}
            </th>
        </tr>
        <tr>
            <th colspan="11" style="background-color: #f5f5f5; text-align: left; padding: 8px;">
                <strong>Total de registros:</strong> {{ $totalRegistros }}
            </th>
        </tr>
        <tr>
            <th colspan="11" style="background-color: #f5f5f5; text-align: left; padding: 8px;">
                <strong>Filtros aplicados:</strong> {{ $filtrosAplicados }}
            </th>
        </tr>
        <tr style="background-color: #2196F3; color: white; font-weight: bold;">
            <th>Producto</th>
            <th>Código</th>
            <th>Marca</th>
            <th>Origen</th>
            <th>Proveedor</th>
            <th>Segmento</th>
            <th>Sección</th>
            <th>Cantidad Inicial</th>
            <th>Cantidad Actual</th>
            <th>Unidad</th>
            <th>Fecha Ingreso</th>
            <th>Usuario</th>
            <th>Comentario</th>
        </tr>
    </thead>
    <tbody>
        @forelse($registros as $item)
            <tr>
                <td>{{ $item->producto_nombre }}</td>
                <td>{{ $item->codigo_barra ?? 'N/A' }}</td>
                <td>{{ $item->marca_nombre ?? 'Sin marca' }}</td>
                <td>{{ $item->origen }}</td>
                <td>{{ $item->proveedor ?? 'N/A' }}</td>
                <td>{{ $item->segmento_descripcion ?? 'N/A' }}</td>
                <td>{{ $item->seccion_descripcion ?? 'N/A' }}</td>
                <td style="text-align: center;">{{ number_format($item->cantidad_inicial_seccion, 2) }}</td>
                <td style="text-align: center;">{{ number_format($item->cantidad_disponible, 2) }}</td>
                <td>{{ $item->unidad_medida_venta ?? $item->unidad_medida ?? 'Unidad' }}</td>
                <td style="text-align: center;">{{ \Carbon\Carbon::parse($item->fecha_recibido)->format('d/m/Y H:i') }}</td>
                <td>{{ $item->usuario_registro }}</td>
                <td>{{ $item->comentario ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="13" style="text-align: center; padding: 20px; color: #999;">
                    No hay registros para mostrar
                </td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr style="background-color: #f5f5f5; font-weight: bold;">
            <td colspan="7" style="text-align: right;">TOTAL DE REGISTROS:</td>
            <td colspan="6" style="text-align: left;">{{ $totalRegistros }}</td>
        </tr>
    </tfoot>
</table>
