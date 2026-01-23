#!/bin/bash
# Zenvy Inventory API - Setup Script
# Este script ayuda a configurar la API de inventario

echo "================================"
echo "🚀 Zenvy Inventory API Setup"
echo "================================"
echo ""

# Verificar que estamos en la carpeta correcta
if [ ! -f "composer.json" ]; then
    echo "❌ Error: Ejecuta este script desde la raíz del proyecto"
    exit 1
fi

echo "1️⃣  Verificando código..."
echo ""

# Verificar que los archivos existan
echo "Buscando archivos del backend..."

if [ -f "app/Services/Api/InventoryService.php" ]; then
    echo "✅ InventoryService.php"
else
    echo "❌ InventoryService.php no encontrado"
fi

if [ -f "app/Http/Controllers/Api/V1/InventoryController.php" ]; then
    echo "✅ InventoryController.php"
else
    echo "❌ InventoryController.php no encontrado"
fi

if [ -f "routes/api.php" ]; then
    echo "✅ routes/api.php"
else
    echo "❌ routes/api.php no encontrado"
fi

echo ""
echo "2️⃣  Limpiando caché..."
php artisan cache:clear
echo "✅ Caché limpiado"

echo ""
echo "3️⃣  Verificando rutas..."
php artisan route:list | grep inventory

echo ""
echo "================================"
echo "✅ Setup completado"
echo "================================"
echo ""
echo "📝 Próximos pasos:"
echo "1. Importa Insomnia_Inventory_API.json en Insomnia"
echo "2. Configura las variables de entorno"
echo "3. Prueba los endpoints"
echo ""
echo "📚 Documentación:"
echo "- GUIA_RAPIDA_API_INVENTARIO.md"
echo "- INSOMNIA_INVENTORY_GUIA.md"
echo "- RESUMEN_API_INVENTARIO.md"
echo ""
