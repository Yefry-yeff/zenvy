<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Pedido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header.rechazado {
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
        }
        .pedido-info {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .pedido-info.rechazado {
            border-left-color: #f56565;
        }
        .pedido-info h2 {
            margin-top: 0;
            color: #667eea;
            font-size: 18px;
        }
        .pedido-info.rechazado h2 {
            color: #f56565;
        }
        .info-row {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .comentario {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .comentario h3 {
            margin-top: 0;
            color: #92400e;
            font-size: 16px;
        }
        .productos {
            margin: 20px 0;
        }
        .productos table {
            width: 100%;
            border-collapse: collapse;
        }
        .productos th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
        }
        .productos td {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .total-section {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
        }
        .total-row.final {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            border-top: 2px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
        }
        .transfer-info {
            background: #e0f2fe;
            border: 1px solid #0284c7;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .transfer-info h3 {
            margin-top: 0;
            color: #0369a1;
            font-size: 16px;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white !important;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $tipo === 'rechazado' ? 'rechazado' : '' }}">
            <h1>
                @if($tipo === 'facturado')
                    ✓ Pedido Confirmado
                @else
                    ✗ Pedido Rechazado
                @endif
            </h1>
            <p style="margin: 10px 0 0 0;">{{ $pedido->numero_pedido }}</p>
        </div>

        <div class="content">
            <p>Estimado/a <strong>{{ $pedido->cliente_nombre }}</strong>,</p>
            
            @if($tipo === 'facturado')
                <p>Le informamos que su pedido ha sido <strong>procesado y confirmado exitosamente</strong>.</p>
            @else
                <p>Le informamos que lamentablemente su pedido ha sido <strong>rechazado</strong>.</p>
            @endif

            <div class="pedido-info {{ $tipo === 'rechazado' ? 'rechazado' : '' }}">
                <h2>Información del Pedido</h2>
                <div class="info-row">
                    <span class="info-label">Número de Pedido:</span>
                    <span class="info-value">{{ $pedido->numero_pedido }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha:</span>
                    <span class="info-value">{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
                </div>
                @if($tipo === 'facturado' && $facturaId)
                <div class="info-row">
                    <span class="info-label">Factura ID:</span>
                    <span class="info-value">#{{ $facturaId }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Método de Pago:</span>
                    <span class="info-value">{{ $pedido->metodo_pago ?? 'No especificado' }}</span>
                </div>
                @if(isset($pedido->metadata['delivery_type']))
                <div class="info-row">
                    <span class="info-label">Tipo de Entrega:</span>
                    <span class="info-value">
                        {{ $pedido->metadata['delivery_type'] === 'domicilio' ? 'Envío a domicilio' : 'Retiro en tienda' }}
                    </span>
                </div>
                @endif
            </div>

            @if($comentario)
            <div class="comentario">
                <h3>Mensaje de la tienda:</h3>
                <p style="margin: 0;">{{ $comentario }}</p>
            </div>
            @endif

            <div class="productos">
                <h3>Productos:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th style="text-align: center;">Cant.</th>
                            <th style="text-align: right;">Precio</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pedido->items as $item)
                        <tr>
                            <td>{{ $item->producto->nombre ?? "Producto ID {$item->producto_id}" }}</td>
                            <td style="text-align: center;">{{ $item->cantidad }}</td>
                            <td style="text-align: right;">L {{ number_format($item->precio_unitario, 2) }}</td>
                            <td style="text-align: right;">L {{ number_format($item->total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>L {{ number_format($pedido->subtotal, 2) }}</span>
                </div>
                @if($pedido->descuento > 0)
                <div class="total-row">
                    <span>Descuento:</span>
                    <span style="color: #10b981;">- L {{ number_format($pedido->descuento, 2) }}</span>
                </div>
                @endif
                @if(isset($pedido->metadata['shipping_cost']) && $pedido->metadata['shipping_cost'] > 0)
                <div class="total-row">
                    <span>Costo de Envío:</span>
                    <span>L {{ number_format($pedido->metadata['shipping_cost'], 2) }}</span>
                </div>
                @endif
                <div class="total-row">
                    <span>ISV (15%):</span>
                    <span>L {{ number_format($pedido->isv, 2) }}</span>
                </div>
                <div class="total-row final">
                    <span>TOTAL:</span>
                    <span>L {{ number_format($pedido->total, 2) }}</span>
                </div>
            </div>

            @if($pedido->metodo_pago === 'Transferencia Bancaria' && isset($pedido->metadata['transfer_info']))
            <div class="transfer-info">
                <h3>Datos de la Transferencia:</h3>
                @php
                    $transferInfo = $pedido->metadata['transfer_info'];
                @endphp
                <div class="info-row">
                    <span class="info-label">Banco:</span>
                    <span class="info-value">{{ $transferInfo['account_bank'] ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tipo de Cuenta:</span>
                    <span class="info-value">{{ ucfirst($transferInfo['account_type'] ?? 'N/A') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Número de Cuenta:</span>
                    <span class="info-value">{{ $transferInfo['account_number'] ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Titular:</span>
                    <span class="info-value">{{ $transferInfo['account_holder'] ?? 'N/A' }}</span>
                </div>
                @if(isset($transferInfo['transfer_date']))
                <div class="info-row">
                    <span class="info-label">Fecha de Transferencia:</span>
                    <span class="info-value">{{ $transferInfo['transfer_date'] }}</span>
                </div>
                @endif
            </div>
            @endif

            @if($tipo === 'facturado')
                <p>Nos pondremos en contacto con usted para coordinar la entrega de su pedido.</p>
                <p>Si tiene alguna pregunta, no dude en contactarnos.</p>
            @else
                <p>Si tiene alguna pregunta sobre este rechazo, no dude en contactarnos.</p>
            @endif

            <p style="margin-top: 30px;">Gracias por su preferencia.</p>
        </div>

        <div class="footer">
            <p><strong>Paperland S.A.</strong></p>
            <p>Este es un correo automático, por favor no responder a este mensaje.</p>
            <p style="margin-top: 10px; color: #999; font-size: 11px;">
                © {{ date('Y') }} Paperland. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>
