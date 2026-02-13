@echo off
REM Script de instalación para Windows
REM Módulo de APIs y Webhooks - Zenvy POS

echo ========================================
echo 🚀 Instalando Módulo APIs y Webhooks
echo ========================================
echo.

REM Verificar que estamos en el directorio correcto
if not exist "artisan" (
    echo ❌ Error: No se encuentra el archivo artisan
    echo    Ejecuta este script desde el directorio Punto_Venta
    pause
    exit /b 1
)

echo 📋 Paso 1: Ejecutando migraciones...
php artisan migrate --force
if errorlevel 1 (
    echo ❌ Error al ejecutar migraciones
    pause
    exit /b 1
)
echo ✅ Migraciones ejecutadas correctamente
echo.

echo 📋 Paso 2: Creando tabla de cola de trabajos...
php artisan queue:table
php artisan migrate --force
echo.

echo 📋 Paso 3: Configurando variables de entorno...
echo.

REM Verificar si .env existe
if not exist ".env" (
    echo ⚠️  Archivo .env no encontrado, creando desde .env.example...
    copy .env.example .env
)

REM Generar token
for /f "delims=" %%i in ('php artisan tinker --execute="echo base64_encode('Zenvy-POS-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(16)));"') do set TOKEN=%%i

echo ✅ Token generado
echo.

REM Agregar variables al .env (manualmente por ahora en Windows)
echo 📝 ACCIÓN REQUERIDA:
echo.
echo Agrega estas líneas al final de tu archivo .env:
echo.
echo # Webhooks de APIs
echo WEBHOOK_ENABLED=true
echo WEBHOOK_URL=
echo WEBHOOK_TOKEN=%TOKEN%
echo WEBHOOK_TIMEOUT=5
echo WEBHOOK_RETRY_ATTEMPTS=3
echo QUEUE_CONNECTION=database
echo.

echo 📋 Paso 4: Limpiando cache...
php artisan config:clear
php artisan cache:clear
echo ✅ Cache limpiado
echo.

echo ========================================
echo ✅ Instalación completada!
echo ========================================
echo.
echo 📝 PRÓXIMOS PASOS:
echo.
echo 1. Edita tu archivo .env y agrega las variables mostradas arriba
echo 2. Configura WEBHOOK_URL con la URL de tu ecommerce
echo 3. Inicia el worker: php artisan queue:work --queue=webhooks,default
echo 4. Accede a: http://tu-dominio.com/configuracion/apis
echo 5. Prueba: php artisan webhooks:test
echo.
echo 📚 Ver documentación completa en:
echo    - MODULO_APIS_WEBHOOKS.md
echo    - INSTALACION_RAPIDA_APIS.md
echo.
echo ========================================

pause
