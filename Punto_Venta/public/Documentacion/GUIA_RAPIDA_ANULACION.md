# 🚀 Guía Rápida: Anulación de Facturas

## 📋 Pasos de Implementación

### 1️⃣ Ejecutar Scripts SQL

```bash
# En tu gestor de base de datos (phpMyAdmin, MySQL Workbench, etc.)
```

**Obligatorio:**
```sql
-- Ejecutar: Bases de datos/Script/crear_tabla_facturas_anuladas.sql
```

**Opcional (si deseas registrar flujo de caja):**
```sql
-- Ejecutar: Bases de datos/Script/crear_tabla_flujo_caja_opcional.sql
```

---

### 2️⃣ Actualizar Autoload de Composer

```bash
cd Punto_Venta
composer dump-autoload
```

---

### 3️⃣ Agregar Ruta al Sistema

En tu archivo de rutas (probablemente `routes/web.php`):

```php
// Ruta para reporte de facturas anuladas
Route::get('/reportes/facturas-anuladas', \App\Livewire\Reporte\FacturasAnuladas::class)
    ->middleware('auth')
    ->name('reportes.facturas-anuladas');
```

---

### 4️⃣ Agregar al Menú de Navegación

Editar el archivo de menú lateral (probablemente en `resources/views/layouts/` o `components/`):

```html
<!-- Dentro del menú de Reportes -->
<a href="{{ route('reportes.facturas-anuladas') }}" 
   class="nav-link {{ request()->routeIs('reportes.facturas-anuladas') ? 'active' : '' }}">
    <i class="fas fa-ban text-danger"></i>
    <span>Facturas Anuladas</span>
</a>
```

---

## ✨ Funcionalidades Implementadas

### En Lista de Facturas

**Antes:**
```
[🖨️ Imprimir] [📄 PDF]
```

**Después:**
```
[⚙️ Acciones ▼]
  ├─ 🖨️ Imprimir Factura
  ├─ 📄 Descargar PDF
  └─ 🚫 Anular Factura
```

### Modal de Anulación

Campos del formulario:
- ✅ **Motivo de Anulación** (obligatorio, mín 10 caracteres)
- ✅ **Método de Devolución** (efectivo, transferencia, nota crédito, no aplica)
- ✅ **Observaciones** (opcional)
- ✅ **Devolver productos al inventario** (checkbox)
- ✅ **Registrar en flujo de caja** (checkbox)

---

## 🎯 ¿Qué hace la Anulación?

### 1. Inventario
Si está marcado "Devolver productos al inventario":
- ✅ Busca los productos de la factura
- ✅ Incrementa la cantidad en `recibido_bodega`
- ✅ Guarda detalle en JSON

### 2. Flujo de Caja
Si está marcado "Registrar en flujo de caja":
- ✅ Registra salida por el monto total
- ✅ Asocia con la factura anulada
- ✅ Registra método de devolución

### 3. Estado de Factura
- ✅ Cambia `estado_factura_id` de 1 → 3 (Anulada)
- ✅ Badge rojo "Anulada" en la lista

### 4. Registro Histórico
- ✅ Inserta en tabla `facturas_anuladas`
- ✅ Guarda todos los datos de la factura
- ✅ Guarda motivo, observaciones y timestamps
- ✅ Registra quién vendió y quién anuló

### 5. Bitácora
- ✅ Registra la acción en `bitacora`
- ✅ Auditoría completa del cambio

---

## 📊 Reporte de Facturas Anuladas

### Ubicación
```
/reportes/facturas-anuladas
```

### Filtros Disponibles
- 🔍 N° Factura
- 👤 Cliente
- 🔐 Usuario que Anuló
- 💼 Vendedor Original
- 📅 Fecha de Anulación (rango)
- 📅 Fecha de Emisión (rango)
- 📝 Motivo

### Acciones
- 👁️ Ver detalles completos
- 📥 Exportar a Excel
- 🔄 Ordenar por cualquier columna

---

## 🎨 Visualización en Tabla

```
┌─────┬──────────┬──────────┬───────────┬──────────────┬──────────┐
│ ID  │ N° Fact. │ Cliente  │ F. Emis.  │ F. Anulación │  Total   │
├─────┼──────────┼──────────┼───────────┼──────────────┼──────────┤
│ 1   │ 00000123 │ Juan P.  │ 01/12/25  │ 07/12/25     │ L. 500.00│
│     │          │          │           │ 14:30:22     │          │
└─────┴──────────┴──────────┴───────────┴──────────────┴──────────┘
```

---

## 🔒 Seguridad

### Validaciones Implementadas
- ✅ No se puede anular una factura ya anulada
- ✅ Motivo obligatorio (mínimo 10 caracteres)
- ✅ Transacción de base de datos (todo o nada)
- ✅ Registro de auditoría en bitácora
- ✅ Registro de quién realizó la acción

### Recomendaciones
1. Asignar permiso especial para anular facturas
2. Solo administradores o supervisores
3. Revisar periódicamente el reporte de anuladas

---

## 📤 Exportación Excel

### Archivo generado
```
facturas_anuladas_2025-12-07_14-30-22.xlsx
```

### Columnas incluidas (15)
1. ID
2. N° Factura
3. Cliente
4. RTN
5. Fecha Emisión Original
6. Fecha Anulación
7. Subtotal
8. ISV
9. Total
10. Vendedor Original
11. Usuario que Anuló
12. Motivo Anulación
13. Método Devolución
14. Impacto Flujo Caja
15. Observaciones

---

## ⚠️ Notas Importantes

### Flujo de Caja (Opcional)
Si NO tienes la tabla `flujo_caja`:
- Desmarcar el checkbox "Registrar en flujo de caja"
- O ejecutar el script opcional

### Inventario
La devolución de productos requiere:
- ✅ Tabla `factura_has_stock` con datos
- ✅ Relación correcta con `recibido_bodega`

---

## 🧪 Pruebas Recomendadas

1. **Anular una factura simple**
   - Con inventario marcado
   - Con flujo de caja marcado
   - Verificar que el estado cambie a "Anulada"

2. **Verificar inventario**
   - Comprobar que las cantidades incrementaron
   - Revisar en `recibido_bodega`

3. **Verificar flujo de caja**
   - Ver registro en tabla `flujo_caja`
   - Tipo: "salida"
   - Monto correcto

4. **Ver en reporte**
   - Buscar la factura anulada
   - Ver detalles completos
   - Exportar a Excel

5. **Intentar anular nuevamente**
   - Debe mostrar mensaje de error
   - Botón deshabilitado o mensaje "Ya anulada"

---

## 🆘 Solución de Problemas

### "Column 'productos_devueltos' doesn't exist"
```sql
-- Ejecutar script: crear_tabla_facturas_anuladas.sql
```

### "Class FacturaAnulada not found"
```bash
composer dump-autoload
```

### "Table 'flujo_caja' doesn't exist"
Opciones:
1. Ejecutar `crear_tabla_flujo_caja_opcional.sql`
2. O desmarcar checkbox en modal de anulación

### No devuelve productos
- Verificar que existe `factura_has_stock`
- Verificar relación con `recibido_bodega_id`

---

## 📞 Archivos Importantes

```
📁 Punto_Venta/
  📁 app/
    📁 Models/
      📄 FacturaAnulada.php ⭐ Modelo
    📁 Livewire/
      📁 SalaDeVentas/
        📄 ListaDeFacturas.php ⭐ Lógica de anulación
      📁 Reporte/
        📄 FacturasAnuladas.php ⭐ Reporte
    📁 Exports/
      📄 FacturasAnuladasExport.php ⭐ Excel
  📁 resources/views/livewire/
    📁 sala-de-ventas/
      📄 lista-de-facturas.blade.php ⭐ Vista con modal
    📁 reporte/
      📄 facturas-anuladas.blade.php ⭐ Vista de reporte

📁 Bases de datos/Script/
  📄 crear_tabla_facturas_anuladas.sql ⭐ OBLIGATORIO
  📄 crear_tabla_flujo_caja_opcional.sql ⭐ OPCIONAL

📄 DOCUMENTACION_ANULACION_FACTURAS.md ⭐ Documentación completa
```

---

## ✅ Checklist Final

- [ ] Ejecutar script `crear_tabla_facturas_anuladas.sql`
- [ ] Ejecutar `composer dump-autoload`
- [ ] Agregar ruta al sistema
- [ ] Agregar enlace al menú
- [ ] Probar anular una factura
- [ ] Verificar inventario
- [ ] Verificar flujo de caja (si aplica)
- [ ] Probar reporte
- [ ] Probar exportación Excel
- [ ] Configurar permisos de usuario

---

**¡Implementación Completa! 🎉**

Para más detalles técnicos, consultar: `DOCUMENTACION_ANULACION_FACTURAS.md`
