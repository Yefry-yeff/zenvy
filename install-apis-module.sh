#!/bin/bash

# Script de instalación automática del módulo APIs y Webhooks
# Uso: bash install-apis-module.sh

echo "🚀 Instalando Módulo de APIs y Webhooks - Zenvy POS"
echo "======================================================"
echo ""

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: No se encuentra el archivo artisan${NC}"
    echo "   Ejecuta este script desde el directorio Punto_Venta"
    exit 1
fi

echo -e "${YELLOW}📋 Paso 1: Ejecutando migraciones...${NC}"
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migraciones ejecutadas correctamente${NC}"
else
    echo -e "${RED}❌ Error al ejecutar migraciones${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}📋 Paso 2: Creando tabla de cola de trabajos...${NC}"
php artisan queue:table
php artisan migrate --force

echo ""
echo -e "${YELLOW}📋 Paso 3: Generando token de seguridad...${NC}"
TOKEN=$(php artisan tinker --execute="echo base64_encode('Zenvy-POS-' . now()->format('YmdHis') . '-' . bin2hex(random_bytes(16)));" 2>/dev/null | tail -n 1)

if [ ! -z "$TOKEN" ]; then
    echo -e "${GREEN}✅ Token generado: ${TOKEN}${NC}"
else
    echo -e "${YELLOW}⚠️  No se pudo generar token automáticamente${NC}"
    TOKEN="GENERAR_MANUALMENTE"
fi

echo ""
echo -e "${YELLOW}📋 Paso 4: Configurando variables de entorno...${NC}"

# Verificar si .env existe
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  Archivo .env no encontrado, creando desde .env.example...${NC}"
    cp .env.example .env
fi

# Agregar o actualizar variables de webhook
if ! grep -q "WEBHOOK_ENABLED" .env; then
    echo "" >> .env
    echo "# Webhooks de APIs" >> .env
    echo "WEBHOOK_ENABLED=true" >> .env
    echo "WEBHOOK_URL=" >> .env
    echo "WEBHOOK_TOKEN=${TOKEN}" >> .env
    echo "WEBHOOK_TIMEOUT=5" >> .env
    echo "WEBHOOK_RETRY_ATTEMPTS=3" >> .env
    echo "" >> .env
    echo -e "${GREEN}✅ Variables agregadas al archivo .env${NC}"
else
    echo -e "${YELLOW}⚠️  Variables WEBHOOK ya existen en .env (no se modificaron)${NC}"
fi

# Configurar cola de base de datos si está en sync
if grep -q "QUEUE_CONNECTION=sync" .env; then
    sed -i.bak 's/QUEUE_CONNECTION=sync/QUEUE_CONNECTION=database/' .env
    echo -e "${GREEN}✅ Cola de trabajos configurada a 'database'${NC}"
fi

echo ""
echo -e "${YELLOW}📋 Paso 5: Limpiando cache...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan config:cache

echo ""
echo -e "${GREEN}✅✅✅ Instalación completada exitosamente! ✅✅✅${NC}"
echo ""
echo "======================================================"
echo "📝 CONFIGURACIÓN REQUERIDA:"
echo "======================================================"
echo ""
echo "1. Edita tu archivo .env y configura:"
echo "   WEBHOOK_URL=https://tu-ecommerce.com/api/webhooks"
echo ""
echo "2. (Opcional) Si generaste token manualmente, actualiza:"
echo "   WEBHOOK_TOKEN=${TOKEN}"
echo ""
echo "3. Inicia el worker de cola:"
echo "   php artisan queue:work --queue=webhooks,default"
echo ""
echo "4. Accede al panel de administración:"
echo "   http://tu-dominio.com/configuracion/apis"
echo ""
echo "5. Probar el webhook:"
echo "   php artisan webhooks:test"
echo ""
echo "======================================================"
echo "📚 Documentación completa en:"
echo "   - MODULO_APIS_WEBHOOKS.md"
echo "   - INSTALACION_RAPIDA_APIS.md"
echo "======================================================"
echo ""

# Preguntar si desea iniciar el worker
read -p "¿Deseas iniciar el worker de cola ahora? (s/n): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[SsYy]$ ]]; then
    echo -e "${GREEN}🚀 Iniciando worker de cola...${NC}"
    echo -e "${YELLOW}💡 Presiona Ctrl+C para detener${NC}"
    php artisan queue:work --queue=webhooks,default
fi
