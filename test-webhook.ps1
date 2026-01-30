# Test Webhook - Zenvy Inventory
# Script para probar webhooks de inventario manualmente

$url = "http://127.0.0.1:8001/api/webhook/inventory"
$token = "WmVudnktUE9TLTIwMjYwMTIzMjMzNTMxLTFjQTZHOWI4UlRTVVZMQ0Qwcm01d096ZHRmam9CYU1J"

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  TEST WEBHOOK INVENTARIO" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "URL: $url" -ForegroundColor Yellow
Write-Host "Enviando webhook de prueba...`n" -ForegroundColor White

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
}

$body = @{
    evento = "inventario.stock_actualizado"
    timestamp = (Get-Date).ToString("yyyy-MM-ddTHH:mm:ss.fffZ")
    producto = @{
        id = 1762
        nombre = "ACUARELA CON PINCEL 12 COLORES"
        stock_anterior = 1
        stock_actual = 0
        cambio = -1
        razon = "factura"
    }
} | ConvertTo-Json -Depth 10

Write-Host "Payload enviado:" -ForegroundColor Cyan
Write-Host $body -ForegroundColor Gray
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri $url -Method POST -Headers $headers -Body $body
    
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "  RESPUESTA EXITOSA" -ForegroundColor Green
    Write-Host "========================================`n" -ForegroundColor Green
    
    $response | ConvertTo-Json -Depth 10 | Write-Host -ForegroundColor White
    
    Write-Host "`n✅ Webhook recibido correctamente" -ForegroundColor Green
    
} catch {
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "  ERROR" -ForegroundColor Red
    Write-Host "========================================`n" -ForegroundColor Red
    
    Write-Host "Mensaje: $($_.Exception.Message)" -ForegroundColor Yellow
    Write-Host "StatusCode: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Yellow
    
    if ($_.ErrorDetails.Message) {
        Write-Host "Detalles: $($_.ErrorDetails.Message)" -ForegroundColor Yellow
    }
    
    Write-Host "`n❌ Error al enviar webhook" -ForegroundColor Red
}

Write-Host ""
