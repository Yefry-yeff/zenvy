$webhookUrl = "http://127.0.0.1:8001/api/webhook/inventory"
$webhookToken = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0Iiwic3ViIjoiYXBpX2FjY2VzcyIsImNsaWVudF9pZCI6MywiY2xpZW50X25hbWUiOiJQYXBlcmxhbmQgV2ViIC0gQVBJIENsaWVudCIsImlhdCI6MTc2OTIzMzYxNSwiZXhwIjoxNzcxODI1NjE1fQ.dHc7UTUYf1QADyFAyzp5Vg8yZ2uKvmMMT6pM8rcZeLg"

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "🔧 PRUEBA MANUAL DE WEBHOOK" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor White
Write-Host ""

Write-Host "URL: $webhookUrl" -ForegroundColor Yellow
Write-Host "Enviando petición POST..." -ForegroundColor Yellow
Write-Host ""

$payload = @{
    evento = "inventario.stock_actualizado"
    timestamp = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
    producto = @{
        id = 1
        nombre = "Producto de Prueba Manual"
        stock_anterior = 10
        stock_actual = 8
        stock_total = 8
        reservas_activas = 0
        cambio = -2
        razon = "test_manual"
    }
    detalles = @{
        tipo_prueba = "manual_powershell"
        mensaje = "Prueba manual desde PowerShell"
    }
} | ConvertTo-Json -Depth 10

$headers = @{
    "Content-Type" = "application/json"
    "Authorization" = "Bearer $webhookToken"
    "X-Event-Type" = "inventario.stock_actualizado"
}

try {
    # Timeout de 5 segundos
    $response = Invoke-WebRequest -Uri $webhookUrl `
        -Method POST `
        -Headers $headers `
        -Body $payload `
        -TimeoutSec 5 `
        -ErrorAction Stop
    
    Write-Host "✅ WEBHOOK RECIBIDO EXITOSAMENTE" -ForegroundColor Green
    Write-Host ""
    Write-Host "Status Code: $($response.StatusCode)" -ForegroundColor Green
    Write-Host "Response:" -ForegroundColor Yellow
    Write-Host $response.Content
    Write-Host ""
    Write-Host "✅ Tu endpoint está funcionando correctamente" -ForegroundColor Green
    
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    $errorMessage = $_.Exception.Message
    
    Write-Host "❌ ERROR AL ENVIAR WEBHOOK" -ForegroundColor Red
    Write-Host ""
    
    if ($statusCode) {
        Write-Host "Status Code: $statusCode" -ForegroundColor Red
    }
    
    Write-Host "Error: $errorMessage" -ForegroundColor Red
    Write-Host ""
    
    # Diagnóstico del problema
    Write-Host "🔍 POSIBLES CAUSAS:" -ForegroundColor Yellow
    Write-Host "-------------------------------------------" -ForegroundColor White
    
    if ($errorMessage -like "*timeout*" -or $errorMessage -like "*timed out*") {
        Write-Host "1. El servidor web NO está corriendo en el puerto 8001" -ForegroundColor White
        Write-Host "2. El endpoint está demorando más de 5 segundos en responder" -ForegroundColor White
    }
    elseif ($errorMessage -like "*denied*" -or $errorMessage -like "*connection refused*") {
        Write-Host "1. El servidor web NO está corriendo" -ForegroundColor White
        Write-Host "2. El puerto 8001 está bloqueado por firewall" -ForegroundColor White
    }
    elseif ($statusCode -eq 401 -or $statusCode -eq 403) {
        Write-Host "1. Token de autorización inválido" -ForegroundColor White
        Write-Host "2. El endpoint requiere autenticación diferente" -ForegroundColor White
    }
    elseif ($statusCode -eq 404) {
        Write-Host "1. La ruta /api/webhook/inventory no existe" -ForegroundColor White
        Write-Host "2. Verifica la configuración de rutas en tu aplicación web" -ForegroundColor White
    }
    elseif ($statusCode -eq 500) {
        Write-Host "1. Error interno en el servidor al procesar el webhook" -ForegroundColor White
        Write-Host "2. Revisa los logs de tu aplicación web" -ForegroundColor White
    }
    else {
        Write-Host "1. Verifica que tu servidor web esté corriendo" -ForegroundColor White
        Write-Host "2. Verifica la URL del webhook" -ForegroundColor White
        Write-Host "3. Revisa los logs de tu aplicación web" -ForegroundColor White
    }
    
    Write-Host ""
    Write-Host "📋 VERIFICA:" -ForegroundColor Yellow
    Write-Host "-------------------------------------------" -ForegroundColor White
    Write-Host "• ¿Está tu aplicación web corriendo en http://127.0.0.1:8001?" -ForegroundColor White
    Write-Host "• ¿Existe la ruta /api/webhook/inventory?" -ForegroundColor White
    Write-Host "• ¿El token de autorización es correcto?" -ForegroundColor White
}

Write-Host ""
Write-Host "==================================================" -ForegroundColor Cyan
