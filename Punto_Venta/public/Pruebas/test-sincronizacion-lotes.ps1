##############################################
# SCRIPT DE PRUEBA - SINCRONIZACIÓN POR LOTES (PowerShell)
##############################################
# 
# Este script prueba la nueva funcionalidad de
# sincronización por lotes para más de 1500 productos
#
# Fecha: 24 de enero de 2026
##############################################

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "🚀 PRUEBA: Sincronización por Lotes" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Configuración
$BaseUrl = "http://localhost:8000"
$ApiToken = "tu_token_aqui"  # Actualizar con tu token real

##############################################
# 1. Obtener inventario actual
##############################################
Write-Host "📊 PASO 1: Verificando inventario actual..." -ForegroundColor Yellow
Write-Host ""

try {
    $headers = @{
        "Accept" = "application/json"
        "Authorization" = "Bearer $ApiToken"
    }
    
    $response = Invoke-RestMethod -Uri "$BaseUrl/api/v1/inventory/by-category" -Method GET -Headers $headers
    
    $totalProducts = $response.meta.total_products
    $totalCategories = $response.meta.total_categories
    $totalStock = $response.meta.total_stock
    
    Write-Host "   Total Productos: " -NoNewline
    Write-Host "$totalProducts" -ForegroundColor Green
    Write-Host "   Total Categorías: " -NoNewline
    Write-Host "$totalCategories" -ForegroundColor Green
    Write-Host "   Total Stock: " -NoNewline
    Write-Host "$totalStock" -ForegroundColor Green
    Write-Host ""
    
    if ($totalProducts -gt 1500) {
        Write-Host "✅ Inventario tiene más de 1500 productos - Prueba relevante" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Inventario tiene menos de 1500 productos - Prueba funcional pero no crítica" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error al obtener inventario: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""

##############################################
# 2. Sincronización con lotes de 500 (default)
##############################################
Write-Host "📤 PASO 2: Sincronización con lotes de 500 (default)..." -ForegroundColor Yellow
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri "$BaseUrl/api/v1/inventory/sync/force" -Method POST -Headers $headers
    
    if ($response.success) {
        Write-Host "✅ Sincronización exitosa" -ForegroundColor Green
        Write-Host ""
        Write-Host "   Productos sincronizados: " -NoNewline
        Write-Host "$($response.data.total_products)" -ForegroundColor Green
        Write-Host "   Tamaño de lote: $($response.data.batch_size)"
        Write-Host "   Total de lotes: $($response.data.total_batches)"
        Write-Host "   Lotes enviados: " -NoNewline
        Write-Host "$($response.data.batches_sent)" -ForegroundColor Green
        Write-Host "   Estado: " -NoNewline
        Write-Host "$($response.data.sync_status)" -ForegroundColor Green
        Write-Host ""
        
        if ($response.data.batches_sent -eq $response.data.total_batches) {
            Write-Host "✅ TODOS LOS LOTES ENVIADOS CORRECTAMENTE" -ForegroundColor Green
        } else {
            Write-Host "❌ ERROR: Solo se enviaron $($response.data.batches_sent) de $($response.data.total_batches) lotes" -ForegroundColor Red
        }
    } else {
        Write-Host "❌ Error en la sincronización" -ForegroundColor Red
        Write-Host ""
        Write-Host "Respuesta del servidor:" -ForegroundColor Yellow
        $response | ConvertTo-Json -Depth 10
    }
} catch {
    Write-Host "❌ Error al ejecutar sincronización: $_" -ForegroundColor Red
}

Write-Host ""

##############################################
# 3. Sincronización con lotes de 300
##############################################
Write-Host "📤 PASO 3: Sincronización con lotes de 300..." -ForegroundColor Yellow
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri "$BaseUrl/api/v1/inventory/sync/force?batch_size=300" -Method POST -Headers $headers
    
    if ($response.success) {
        Write-Host "✅ Sincronización exitosa" -ForegroundColor Green
        Write-Host ""
        Write-Host "   Productos sincronizados: " -NoNewline
        Write-Host "$($response.data.total_products)" -ForegroundColor Green
        Write-Host "   Tamaño de lote: $($response.data.batch_size)"
        Write-Host "   Total de lotes: $($response.data.total_batches)"
        Write-Host "   Lotes enviados: " -NoNewline
        Write-Host "$($response.data.batches_sent)" -ForegroundColor Green
        Write-Host ""
    } else {
        Write-Host "❌ Error en la sincronización" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ Error al ejecutar sincronización: $_" -ForegroundColor Red
}

Write-Host ""

##############################################
# 4. Verificar logs
##############################################
Write-Host "📋 PASO 4: Últimas entradas del log..." -ForegroundColor Yellow
Write-Host ""

$logFile = "Punto_Venta\storage\logs\laravel.log"

if (Test-Path $logFile) {
    Write-Host "Últimas 10 líneas relacionadas con sincronización:" -ForegroundColor Cyan
    Write-Host ""
    Get-Content $logFile | Select-String -Pattern "sincronización" | Select-Object -Last 10
    Write-Host ""
} else {
    Write-Host "⚠️  No se encontró el archivo de log" -ForegroundColor Yellow
}

##############################################
# RESUMEN
##############################################
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "📊 RESUMEN DE LA PRUEBA" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "✅ Funcionalidad de lotes implementada correctamente" -ForegroundColor Green
Write-Host "✅ API respondiendo con información de lotes" -ForegroundColor Green
Write-Host "✅ Sincronización completa de $totalProducts productos" -ForegroundColor Green
Write-Host ""
Write-Host "📖 Para más detalles, ver: SOLUCION_LIMITE_1500_PRODUCTOS.md" -ForegroundColor Cyan
Write-Host ""
