# ⚡ Inicio Rápido - Insomnia 12.2.0

## 📦 Archivos que Necesitas

1. **Insomnia_Zenvy_API_Ventas.json** - Colección para importar
2. **GUIA_INSOMNIA_CONFIGURACION.md** - Guía completa (léela si tienes dudas)

---

## 🚀 2 Pasos Rápidos

### 1️⃣ Importar en Insomnia 12.2.0

1. Abre **Insomnia 12.2.0**
2. Click en **Import** (o presiona `Ctrl+O`)
3. Selecciona **From File**
4. Elige: `Insomnia_Zenvy_API_Ventas.json`
5. Click en **Scan** y luego **Import**

✅ **Ya viene configurado** con las credenciales correctas:

```json
{
  "base_url": "http://127.0.0.1:8000",
  "api_key": "pk_LhkEAssdwNMPjJMc3gqJL2TtIX96mi1s",
  "api_secret": "sk_3fXa0kouB9l0Qiw1mLYoIcZKRX6WbIfLykKCLK5tT2fKKqPOYoQs7mOeS7tP1LcZ",
  "token": ""
}
```

No necesitas editar nada.

### 2️⃣ Obtener Token y Probar

1. En la barra lateral, selecciona **"Base Environment"** en el dropdown de environments
2. Abre el request **"1. Obtener Token JWT"** → Click en **Send**
3. **Copia el token** de la respuesta (solo el valor del token, sin las comillas)
4. En la barra lateral, click en **"Base Environment"** → edita la variable `token` y pega el valor
5. Abre el request **"2. Crear Venta - Johann Ruiz"** → Click en **Send**
6. ✅ ¡Venta creada!

---

## 📝 Datos del Pedido

```
Cliente: Johann Ruiz
Email: johann_ruiz14@hotmail.com
Teléfono: +50497525987
Dirección: Oficina Francisco Morazán, Tegucigalpa, 
           El centro de AMDC, Frente AMDC
Pago: Efectivo
Notas: Dejar afuera

Productos:
- ID 7463: L. 1,000 (10% desc.) = L. 900
- ID 7462: L. 500 (sin desc.) = L. 500
- ID 7461 (Envío): L. 50 = L. 50

TOTAL: L. 1,667.50
```

---

## ⚠️ Antes de Ejecutar

Verifica que los productos existan:

```sql
SELECT id, nombre FROM producto WHERE id IN (7463, 7462, 7461);
```

Si no existen, edita el request con IDs válidos de tu BD.

---

## ❓ Si Algo Falla

### "Token expirado"
→ Ejecuta nuevamente Request 1 para obtener un token nuevo

### "Producto no encontrado"  
→ Edita el request con product_id válidos de tu BD

### "Credenciales inválidas"
→ Ejecuta: `php crear_credenciales_api.php` y usa las nuevas credenciales

### "404 Not Found"
→ Verifica que el servidor esté corriendo:
```bash
cd c:\laragon\www\Procadts\zenvy\Punto_Venta
php artisan serve --port=8001
```

---

## 🎯 Orden de Ejecución

```
Health Check (opcional)
    ↓
1. Obtener Token JWT
    ↓
Copiar token al Environment
    ↓
2. Crear Venta - Johann Ruiz
    ↓
3. Consultar Venta (opcional)
```

---

## 🔄 Renovar Credenciales

Si necesitas nuevas credenciales:

```bash
cd c:\laragon\www\Procadts\zenvy
php crear_credenciales_api.php
```

Selecciona opción **1** o **2** según necesites.

---

**Servidor:** http://localhost:8001  
**Token válido por:** 1 hora  
**Total del pedido:** L. 1,667.50

¡Todo listo! 🎉
