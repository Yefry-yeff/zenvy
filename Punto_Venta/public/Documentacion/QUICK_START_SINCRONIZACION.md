# 🚀 QUICK START - SINCRONIZACIÓN INVENTARIO (LOCAL)

## 3 Pasos para Activar

### 1️⃣ Configurar Zenvy (.env)

```env
# Agregar a: Punto_Venta/.env

WEBHOOK_URL=http://localhost:8000/api/webhook/inventory
WEBHOOK_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb
```

**Token explicación:**
- ✅ Nunca vence (exp: año 2286)
- ✅ Perfecto para desarrollo local
- ✅ Sub: "zenvy-dev-token"

### 2️⃣ Crear Endpoint en tu Página Web

```php
// routes/api.php (tu página web local)

Route::post('/webhook/inventory', function(Request $request) {
    // Validar token (IGUAL al de arriba)
    $token = str_replace('Bearer ', '', $request->header('Authorization'));
    $tokenEsperado = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb';
    if ($token !== $tokenEsperado) return response(null, 401);
    
    // Procesar evento
    match($request->input('evento')) {
        'inventario.compra_recibida' => DB::table('productos')
            ->where('zenvy_id', $request->input('producto.id'))
            ->increment('stock', $request->input('producto.cantidad_ingresada')),
        'inventario.venta_realizada' => collect($request->input('productos_vendidos'))
            ->each(fn($p) => DB::table('productos')
                ->where('zenvy_id', $p['id'])
                ->decrement('stock', $p['cantidad'])),
        'inventario.factura_anulada' => collect($request->input('productos_restaurados'))
            ->each(fn($p) => DB::table('productos')
                ->where('zenvy_id', $p['id'])
                ->increment('stock', $p['cantidad'])),
    };
    
    return response()->json(['success' => true]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```

### 3️⃣ Probar

```bash
# Terminal 1: Ver logs
tail -f Punto_Venta/storage/logs/laravel.log | grep webhook

# Terminal 2: Forzar sync
curl -X POST http://localhost:8000/api/v1/inventory/sync/force \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb"

# O: Ingresar compra en Zenvy → Se sincroniza automáticamente
```

---

## 📊 Qué se Sincroniza

| Acción | Evento | Stock |
|--------|--------|-------|
| Ingresa compra | `compra_recibida` | ➕ |
| Factura | `venta_realizada` | ➖ |
| Anula factura | `factura_anulada` | ➕ |

---

## 🔐 Headers

```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9eyJzdWIiOiJ6ZW52eS1kZXYtdG9rZW4iLCJpYXQiOjE3Mzc2MzAwMDAsImV4cCI6OTk5OTk5OTk5OX0.zenvyLocalDevToken2025SincronizacionInventarioPageWeb
X-Event-Type: inventario.compra_recibida
X-Timestamp: 2025-01-23T10:30:45Z
```

---

## 🛠️ Debugging

```bash
# Ver logs
tail -f Punto_Venta/storage/logs/laravel.log | grep webhook

# Verificar config en Zenvy
cd Punto_Venta && php artisan tinker
>>> env('WEBHOOK_URL')
>>> env('WEBHOOK_TOKEN')
```

---

## 📖 Ver Docs Completa

- `CONFIG_LOCAL_ENV.md` - Configuración local ⭐
- `SINCRONIZACION_INVENTARIO_PAGINA_WEB.md` - Técnica
- `EJEMPLO_WEBHOOK_RECEPTOR.php` - Código ejemplo
- `FLUJO_SINCRONIZACION_VISUAL.md` - Diagramas

---

## ✅ Checklist

- [ ] `.env` configurado en Zenvy
- [ ] Endpoint creado en tu página web
- [ ] Token validado en webhook
- [ ] Probado con ingreso de compra
- [ ] Probado con facturación
- [ ] Logs verificados

¡Listo! 🎉
