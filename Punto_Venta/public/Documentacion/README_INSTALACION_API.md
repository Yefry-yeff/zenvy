# 🚀 Guía de Instalación Rápida - API REST POS

## Instalación en 5 Pasos

### 1️⃣ Instalar Dependencia JWT

```bash
cd C:\laragon\www\Procadts\zenvy\Punto_Venta
composer require firebase/php-jwt
```

### 2️⃣ Ejecutar Script SQL

```bash
# Opción A: Desde línea de comandos
mysql -u root -p punto_venta < "../Bases de datos/Script/crear_tablas_api.sql"

# Opción B: Desde phpMyAdmin
# - Abrir phpMyAdmin
# - Seleccionar base de datos "punto_venta"
# - Ir a pestaña "SQL"
# - Copiar y pegar contenido de crear_tablas_api.sql
# - Ejecutar
```

O ejecutar migraciones de Laravel:

```bash
php artisan migrate
```

### 3️⃣ Configurar .env

Agregar al archivo `.env`:

```env
# API Configuration
API_AUTH_DRIVER=jwt
API_JWT_SECRET=tu-clave-secreta-minimo-32-caracteres-aqui
API_TOKEN_TTL=3600
API_RATE_LIMIT=60

# API Audit
API_AUDIT_ENABLED=true

# CORS
API_ALLOWED_ORIGINS=http://localhost,https://tutienda.com
```

### 4️⃣ Crear Cliente API

```bash
php artisan tinker
```

Luego ejecutar:

```php
use App\Services\Auth\ApiAuthService;

$authService = app(ApiAuthService::class);

$client = $authService->createClient('Mi E-commerce', [
    'rate_limit_per_minute' => 100
]);

echo "API Key: " . $client->api_key . "\n";
echo "API Secret: " . $client->plain_secret . "\n";
```

**⚠️ IMPORTANTE:** Guardar el `API Key` y `API Secret` de forma segura. El secret solo se muestra una vez.

### 5️⃣ Probar el API

```bash
# Verificar que el API está funcionando
curl http://localhost/api/health

# Obtener token
curl -X POST "http://localhost/api/v1/auth/token" \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "TU_API_KEY",
    "api_secret": "TU_API_SECRET"
  }'
```

---

## 🎯 Endpoints Principales

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/health` | Verificar estado del API |
| POST | `/api/v1/auth/token` | Obtener token JWT |
| GET | `/api/v1/inventory` | Listar inventario |
| GET | `/api/v1/inventory/{sku}` | Obtener producto por SKU |
| POST | `/api/v1/inventory/validate-stock` | Validar disponibilidad |
| POST | `/api/v1/sales` | Crear venta |
| GET | `/api/v1/sales/{id}` | Consultar venta |
| PUT | `/api/v1/sales/{id}/cancel` | Anular venta |

---

## 📖 Documentación Completa

Ver [DOCUMENTACION_API_REST_POS.md](./DOCUMENTACION_API_REST_POS.md) para:
- Especificaciones detalladas de cada endpoint
- Ejemplos de request/response
- Códigos de error
- Flujos de integración
- Seguridad
- Deployment
- Troubleshooting

---

## 📁 Archivos Creados

```
Punto_Venta/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── AuthController.php
│   │   │   ├── InventoryController.php
│   │   │   └── SalesController.php
│   │   ├── Middleware/Api/
│   │   │   ├── ApiKeyAuth.php
│   │   │   ├── AuditApiRequest.php
│   │   │   └── RateLimitApi.php
│   │   ├── Requests/Api/
│   │   │   ├── CreateSaleRequest.php
│   │   │   └── ValidateStockRequest.php
│   │   └── Resources/Api/
│   │       ├── ProductResource.php
│   │       ├── SaleResource.php
│   │       └── SaleItemResource.php
│   ├── Services/
│   │   ├── Api/
│   │   │   ├── InventoryService.php
│   │   │   └── SalesService.php
│   │   └── Auth/
│   │       └── ApiAuthService.php
│   ├── Repositories/
│   │   ├── ProductRepository.php
│   │   └── SaleRepository.php
│   ├── Models/
│   │   ├── ApiClient.php
│   │   ├── ApiLog.php
│   │   └── SaleStatus.php
│   └── Exceptions/Api/
│       ├── InsufficientStockException.php
│       ├── ProductNotFoundException.php
│       └── ApiAuthException.php
├── routes/api.php
├── config/api.php
└── database/migrations/
    ├── 2026_01_13_000001_create_api_clients_table.php
    └── 2026_01_13_000002_create_api_logs_table.php

Bases de datos/
└── Script/
    └── crear_tablas_api.sql

DOCUMENTACION_API_REST_POS.md (Documentación completa)
README_INSTALACION_API.md (Este archivo)
```

---

## ✅ Verificación

Ejecutar estas consultas SQL para verificar la instalación:

```sql
-- Verificar tablas creadas
SHOW TABLES LIKE 'api_%';

-- Verificar cliente creado
SELECT id, name, api_key, is_active FROM api_clients;

-- Verificar columnas en factura
SHOW COLUMNS FROM factura LIKE '%api%';
SHOW COLUMNS FROM factura LIKE 'external%';
```

---

## 🐛 Problemas Comunes

### Error: "Class 'Firebase\JWT\JWT' not found"

**Solución:**
```bash
composer require firebase/php-jwt
composer dump-autoload
```

### Error: "Table 'api_clients' doesn't exist"

**Solución:**
```bash
php artisan migrate
# O ejecutar manualmente el script SQL
```

### Error: "Token inválido"

**Verificar:**
1. `API_JWT_SECRET` está configurado en `.env`
2. API Key y Secret son correctos
3. Token no ha expirado (1 hora de validez)

---

## 📞 Soporte

Para más información, consultar la documentación completa en `DOCUMENTACION_API_REST_POS.md`

**Desarrollador:** Yefry Yeff  
**Repositorio:** [github.com/Yefry-yeff/zenvy](https://github.com/Yefry-yeff/zenvy)

---

**✨ ¡API instalado exitosamente!**
