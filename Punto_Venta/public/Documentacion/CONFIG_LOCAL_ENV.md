# 📋 CONFIGURACIÓN LOCAL - .env Zenvy

Copia estos valores en tu archivo `.env` de Zenvy (Punto_Venta/.env):

```env
# ============================================
# SINCRONIZACIÓN DE INVENTARIO CON PÁGINA WEB
# ============================================

# URL de tu página web local (Laragon)
# Opciones según tu configuración:
#   - http://localhost:8000
#   - http://127.0.0.1:8000
#   - http://tudominio.local:8000
WEBHOOK_URL=http://localhost:8000/api/webhook/inventory

# Token que NUNCA vence - Usa este para desarrollo local
# En producción, considera usar tokens con expiración
WEBHOOK_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb
```

---

## 🔍 Cómo encontrar tu IP/Puerto Local

### Opción 1: Laragon en localhost (Recomendado)
```env
WEBHOOK_URL=http://localhost:8000/api/webhook/inventory
```

### Opción 2: IP Estática Local
```env
WEBHOOK_URL=http://192.168.x.x:8000/api/webhook/inventory
```

### Opción 3: Host Virtual
```env
WEBHOOK_URL=http://miwebsite.local:8000/api/webhook/inventory
```

---

## 🧪 Prueba Rápida

1. **Copia los valores de arriba a tu `.env`**

2. **Verifica en tinker:**
   ```bash
   cd Punto_Venta
   php artisan tinker
   >>> env('WEBHOOK_URL')
   >>> env('WEBHOOK_TOKEN')
   ```

3. **Debe mostrar:**
   ```
   "http://localhost:8000/api/webhook/inventory"
   "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
   ```

---

## 📍 Configuración Webhook en tu Página Web Local

Coloca este mismo token en tu `.env` de la página web:

```env
# .env de TU PÁGINA WEB
WEBHOOK_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb
```

---

## ✅ Validar Configuración

Desde terminal Zenvy:

```bash
php artisan tinker
>>> $service = app(\App\Services\WebInventorySyncService::class);
>>> $service->estaConfigurado();
# Debe retornar: true

>>> $service->obtenerWebhookUrl();
# Debe mostrar: "http://localhost:8000/api/webhook/inventory"
```

---

## 🚀 Probar Sincronización

Una vez configurado, prueba con:

```bash
# Ver logs en tiempo real
tail -f Punto_Venta/storage/logs/laravel.log | grep -i webhook

# En otra terminal, ingresa un producto en Zenvy
# O fuerza sincronización:
curl -X POST http://localhost:8000/api/v1/inventory/sync/force \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb"
```

---

## 📝 Token Explicación

```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb

Partes:
- Header: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9
- Payload: eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0
  - sub: "zenvy-dev-token"
  - iat: 1737630000 (Jan 23, 2025)
  - exp: 9999999999 (Never expires - Año 2286)
- Signature: zenvyLocalDevToken2025SincronizacionInventarioPageWeb

✅ NUNCA VENCE - Perfecto para desarrollo local
```

---

**¡Listo! Ahora solo falta crear el endpoint en tu página web.** 👇
