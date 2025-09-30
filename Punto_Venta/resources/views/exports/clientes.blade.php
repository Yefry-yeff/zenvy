<div>
    <!-- Encabezado del reporte -->
    <table>
        <tr>
            <td colspan="8" style="font-size: 14px; font-weight: bold; background-color: #1F4E78; color: white;">
                REPORTE DE ACTORES
            </td>
        </tr>
        <tr>
            <td colspan="8"></td>
        </tr>
        <!-- Información del reporte -->
        <tr>
            <td colspan="8" style="font-size: 11px;">
                Fecha de exportación: {{ $fechaExportacion }}
                @if($filtrosAplicados !== 'Ninguno')
                    <br>Filtros aplicados: {{ $filtrosAplicados }}
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="8"></td>
        </tr>
        <!-- Encabezados de columna -->
        <tr style="background-color: #2C7BE5; color: white; font-weight: bold;">
            <td>ID</td>
            <td>Actor</td>
            <td>Identidad</td>
            <td>Correo</td>
            <td>Tipo Persona</td>
            <td>Tipo Actor</td>
            <td>Estado</td>
        </tr>
        <!-- Datos -->
        @foreach($clientes as $cliente)
        <tr>
            <td>{{ $cliente->id }}</td>
            <td>{{ $cliente->nombre }}</td>
            <td>{{ $cliente->identidad }}</td>
            <td>{{ $cliente->correo }}</td>
            <td>{{ optional($cliente->tipoPersona)->nombre }}</td>
            <td>{{ optional($cliente->tipoCliente)->nombre }}</td>
            <td>{{ optional($cliente->estado)->nombre }}</td>
        </tr>
        @endforeach
    </table>
</div>