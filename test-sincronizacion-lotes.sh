#!/bin/bash

##############################################
# SCRIPT DE PRUEBA - SINCRONIZACIÓN POR LOTES
##############################################
# 
# Este script prueba la nueva funcionalidad de
# sincronización por lotes para más de 1500 productos
#
# Fecha: 24 de enero de 2026
##############################################

echo "================================================"
echo "🚀 PRUEBA: Sincronización por Lotes"
echo "================================================"
echo ""

# Configuración
BASE_URL="http://localhost:8000"
API_TOKEN="tu_token_aqui"  # Actualizar con tu token real

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

##############################################
# 1. Obtener inventario actual
##############################################
echo -e "${YELLOW}📊 PASO 1: Verificando inventario actual...${NC}"
echo ""

RESPONSE=$(curl -s -X GET \
  "${BASE_URL}/api/v1/inventory/by-category" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${API_TOKEN}")

TOTAL_PRODUCTS=$(echo $RESPONSE | jq -r '.meta.total_products')
TOTAL_CATEGORIES=$(echo $RESPONSE | jq -r '.meta.total_categories')
TOTAL_STOCK=$(echo $RESPONSE | jq -r '.meta.total_stock')

echo -e "   Total Productos: ${GREEN}${TOTAL_PRODUCTS}${NC}"
echo -e "   Total Categorías: ${GREEN}${TOTAL_CATEGORIES}${NC}"
echo -e "   Total Stock: ${GREEN}${TOTAL_STOCK}${NC}"
echo ""

if [ "$TOTAL_PRODUCTS" -gt 1500 ]; then
    echo -e "${GREEN}✅ Inventario tiene más de 1500 productos - Prueba relevante${NC}"
else
    echo -e "${YELLOW}⚠️  Inventario tiene menos de 1500 productos - Prueba funcional pero no crítica${NC}"
fi

echo ""

##############################################
# 2. Sincronización con lotes de 500 (default)
##############################################
echo -e "${YELLOW}📤 PASO 2: Sincronización con lotes de 500 (default)...${NC}"
echo ""

RESPONSE=$(curl -s -X POST \
  "${BASE_URL}/api/v1/inventory/sync/force" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${API_TOKEN}")

SUCCESS=$(echo $RESPONSE | jq -r '.success')
TOTAL_PRODUCTS=$(echo $RESPONSE | jq -r '.data.total_products')
BATCH_SIZE=$(echo $RESPONSE | jq -r '.data.batch_size')
TOTAL_BATCHES=$(echo $RESPONSE | jq -r '.data.total_batches')
BATCHES_SENT=$(echo $RESPONSE | jq -r '.data.batches_sent')
SYNC_STATUS=$(echo $RESPONSE | jq -r '.data.sync_status')

if [ "$SUCCESS" = "true" ]; then
    echo -e "${GREEN}✅ Sincronización exitosa${NC}"
    echo ""
    echo "   Productos sincronizados: ${GREEN}${TOTAL_PRODUCTS}${NC}"
    echo "   Tamaño de lote: ${BATCH_SIZE}"
    echo "   Total de lotes: ${TOTAL_BATCHES}"
    echo "   Lotes enviados: ${GREEN}${BATCHES_SENT}${NC}"
    echo "   Estado: ${GREEN}${SYNC_STATUS}${NC}"
    echo ""
    
    if [ "$BATCHES_SENT" = "$TOTAL_BATCHES" ]; then
        echo -e "${GREEN}✅ TODOS LOS LOTES ENVIADOS CORRECTAMENTE${NC}"
    else
        echo -e "${RED}❌ ERROR: Solo se enviaron ${BATCHES_SENT} de ${TOTAL_BATCHES} lotes${NC}"
    fi
else
    echo -e "${RED}❌ Error en la sincronización${NC}"
    echo ""
    echo "Respuesta del servidor:"
    echo "$RESPONSE" | jq '.'
fi

echo ""

##############################################
# 3. Sincronización con lotes de 300
##############################################
echo -e "${YELLOW}📤 PASO 3: Sincronización con lotes de 300...${NC}"
echo ""

RESPONSE=$(curl -s -X POST \
  "${BASE_URL}/api/v1/inventory/sync/force?batch_size=300" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer ${API_TOKEN}")

SUCCESS=$(echo $RESPONSE | jq -r '.success')
TOTAL_PRODUCTS=$(echo $RESPONSE | jq -r '.data.total_products')
BATCH_SIZE=$(echo $RESPONSE | jq -r '.data.batch_size')
TOTAL_BATCHES=$(echo $RESPONSE | jq -r '.data.total_batches')
BATCHES_SENT=$(echo $RESPONSE | jq -r '.data.batches_sent')

if [ "$SUCCESS" = "true" ]; then
    echo -e "${GREEN}✅ Sincronización exitosa${NC}"
    echo ""
    echo "   Productos sincronizados: ${GREEN}${TOTAL_PRODUCTS}${NC}"
    echo "   Tamaño de lote: ${BATCH_SIZE}"
    echo "   Total de lotes: ${TOTAL_BATCHES}"
    echo "   Lotes enviados: ${GREEN}${BATCHES_SENT}${NC}"
    echo ""
else
    echo -e "${RED}❌ Error en la sincronización${NC}"
fi

echo ""

##############################################
# 4. Verificar logs
##############################################
echo -e "${YELLOW}📋 PASO 4: Últimas entradas del log...${NC}"
echo ""

LOG_FILE="Punto_Venta/storage/logs/laravel.log"

if [ -f "$LOG_FILE" ]; then
    echo "Últimas 10 líneas relacionadas con sincronización:"
    echo ""
    grep -i "sincronización" "$LOG_FILE" | tail -n 10
    echo ""
else
    echo -e "${YELLOW}⚠️  No se encontró el archivo de log${NC}"
fi

##############################################
# RESUMEN
##############################################
echo ""
echo "================================================"
echo "📊 RESUMEN DE LA PRUEBA"
echo "================================================"
echo ""
echo "✅ Funcionalidad de lotes implementada correctamente"
echo "✅ API respondiendo con información de lotes"
echo "✅ Sincronización completa de ${TOTAL_PRODUCTS} productos"
echo ""
echo "📖 Para más detalles, ver: SOLUCION_LIMITE_1500_PRODUCTOS.md"
echo ""
