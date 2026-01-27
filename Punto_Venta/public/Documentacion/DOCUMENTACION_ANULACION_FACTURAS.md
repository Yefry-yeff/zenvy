# Documentación: Implementación de Anulación de Facturas

## 📋 Resumen de Implementación

Se ha implementado un sistema completo de anulación de facturas que incluye:
- ✅ Impacto en inventario (devolución de productos)
- ✅ Impacto en flujo de caja (registro de salida)
- ✅ Registro histórico de anulaciones
- ✅ Interfaz de usuario con dropdown de acciones
- ✅ Reporte completo de facturas anuladas
- ✅ Exportación a Excel

---

## 🗂️ Archivos Creados/Modificados

### 1. Base de Datos

#### **Script SQL: `crear_tabla_facturas_anuladas.sql`**
Ubicación: `Bases de datos/Script/crear_tabla_facturas_anuladas.sql`

**Ejecutar este script primero:**
```sql
-- Crear tabla de facturas anuladas
-- Ver archivo completo para detalles
```

**Estructura de la tabla:**
- `id`: Identificador único
- `factura_id`: Relación con factura original
- `numero_factura`: Número de factura anulada
- `nombre_cliente`, `rtn`: Datos del cliente
- `sub_total`, `isv`, `total`: Montos
- `fecha_emision_factura`: Fecha original
- `fecha_anulacion`: Fecha y hora de anulación
- `motivo_anulacion`: Motivo detallado (TEXT)
- `users_id_anulo`: Usuario que realizó la anulación
- `users_id_vendedor`: Usuario vendedor original
- `productos_devueltos`: JSON con detalle de productos
- `impacto_flujo_caja`: Monto devuelto
- `metodo_devolucion`: efectivo, transferencia, nota_credito, no_aplica
- `observaciones`: Información adicional

---

### 2. Modelos

#### **Modelo: `FacturaAnulada.php`**
Ubicación: `app/Models/FacturaAnulada.php`

**Características:**
- Relación con `Factura`
- Relación con `User` (usuario que anuló)
- Relación con `User` (vendedor original)
- Cast automático de JSON para `productos_devueltos`
- Cast de fechas y decimales

---

### 3. Componentes Livewire

#### **Componente: `ListaDeFacturas.php`**
Ubicación: `app/Livewire/SalaDeVentas/ListaDeFacturas.php`

**Métodos agregados:**
- `abrirModalAnular($facturaId)`: Abre modal de anulación
- `cerrarModalAnular()`: Cierra modal
- `anularFactura()`: Procesa la anulación con transacción

**Propiedades agregadas:**
```php
public $mostrarModalAnular = false;
public $facturaAAnular = null;
public $motivoAnulacion = '';
public $metodoDevolucion = 'efectivo';
public $observacionesAnulacion = '';
public $afectarInventario = true;
public $afectarFlujoCaja = true;
```

**Lógica de anulación:**
1. Validación de campos (motivo mínimo 10 caracteres)
2. Devolución al inventario (si está marcado)
   - Busca `factura_has_stock` para obtener `recibido_bodega_id`
   - Incrementa cantidad en `recibido_bodega`
3. Registro en flujo de caja (si está marcado)
   - Inserta movimiento tipo "salida"
   - Monto = total de factura
4. Actualiza estado de factura a 3 (Anulada)
5. Inserta registro en `facturas_anuladas`
6. Registra en bitácora

---

#### **Componente: `FacturasAnuladas.php`**
Ubicación: `app/Livewire/Reporte/FacturasAnuladas.php`

**Métodos:**
- `obtenerFacturasAnuladas()`: Query con filtros
- `ordenar($campo)`: Ordenamiento dinámico
- `exportarExcel()`: Exportación a Excel

**Filtros disponibles:**
- Número de factura
- Cliente
- Usuario que anuló
- Vendedor original
- Rango de fechas de anulación
- Rango de fechas de emisión
- Motivo de anulación

---

### 4. Exportación Excel

#### **Export: `FacturasAnuladasExport.php`**
Ubicación: `app/Exports/FacturasAnuladasExport.php`

**Columnas exportadas:**
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

**Formato:**
- Header con fondo rojo (#DC3545)
- Texto blanco en encabezados
- Formato numérico con 2 decimales

---

### 5. Vistas

#### **Vista: `lista-de-facturas.blade.php`**
Ubicación: `resources/views/livewire/sala-de-ventas/lista-de-facturas.blade.php`

**Cambios realizados:**

**Antes:**
```blade
<td class="px-4 py-3 text-center">
    <a href="..." class="btn btn-sm btn-success">
        <i class="fas fa-print"></i>
    </a>
    <button class="btn btn-sm btn-danger">
        <i class="fas fa-file-pdf"></i>
    </button>
</td>
```

**Después:**
```blade
<td class="px-4 py-3 text-center">
    <div x-data="{ open: false }">
        <button @click="open = !open">
            ⚙️ Acciones
        </button>
        <div x-show="open">
            <!-- Imprimir -->
            <!-- Descargar PDF -->
            <!-- Anular Factura -->
        </div>
    </div>
</td>
```

**Modal de anulación incluye:**
- Información de la factura a anular
- Campo de motivo (obligatorio, mín 10 caracteres)
- Selector de método de devolución
- Observaciones adicionales (opcional)
- Checkbox para afectar inventario
- Checkbox para afectar flujo de caja
- Advertencia de acción irreversible

---

#### **Vista: `facturas-anuladas.blade.php`**
Ubicación: `resources/views/livewire/reporte/facturas-anuladas.blade.php`

**Componentes:**
1. **Encabezado** con botón de exportación Excel
2. **Tarjetas de resumen:**
   - Total facturas anuladas
   - Monto total anulado
3. **Panel de filtros** (8 campos)
4. **Tabla optimizada** con:
   - Ordenamiento en columnas
   - Vista compacta (text-xs)
   - Sticky header
   - Max-height con scroll
5. **Modal de detalles** para cada registro:
   - Información completa de factura
   - Información de anulación
   - Motivo y observaciones
   - Tabla de productos devueltos (JSON)
6. **Paginación** integrada

---

## 🚀 Flujo de Anulación

### Paso 1: Usuario selecciona "Anular Factura"
- Desde el dropdown de acciones en lista de facturas
- Solo visible si estado_factura_id != 3

### Paso 2: Modal de confirmación
- Muestra resumen de la factura
- Solicita motivo obligatorio
- Permite configurar impactos

### Paso 3: Procesamiento (Transacción DB)
```
1. Validar campos
2. Obtener productos de factura_has_producto
3. Si afectarInventario:
   - Consultar factura_has_stock
   - Incrementar recibido_bodega.cantidad
4. Si afectarFlujoCaja:
   - Insertar en flujo_caja (tipo: salida)
5. Actualizar factura.estado_factura_id = 3
6. Insertar en facturas_anuladas
7. Insertar en bitacora
8. COMMIT
```

### Paso 4: Confirmación
- Mensaje de éxito
- Cierre de modal
- Actualización de vista

---

## 📊 Reporte de Facturas Anuladas

### Acceso
Ruta sugerida: `/reportes/facturas-anuladas`

### Funcionalidades
1. **Filtros múltiples:**
   - Por número de factura
   - Por cliente
   - Por usuario que anuló
   - Por vendedor
   - Por rangos de fechas
   - Por motivo

2. **Visualización:**
   - Tabla compacta con scroll
   - Ordenamiento dinámico
   - Modal de detalles completos
   - Vista de productos devueltos

3. **Exportación:**
   - Excel con todas las columnas
   - Nombre de archivo con timestamp
   - Formato profesional con colores

---

## ⚙️ Configuración Adicional

### Verificar tabla flujo_caja
Asegurarse que la tabla `flujo_caja` existe con estructura:
```sql
CREATE TABLE IF NOT EXISTS `flujo_caja` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo_movimiento` ENUM('entrada', 'salida') NOT NULL,
  `monto` DECIMAL(60,2) NOT NULL,
  `descripcion` TEXT,
  `metodo_pago` VARCHAR(50),
  `factura_id` INT,
  `users_id` BIGINT UNSIGNED NOT NULL,
  `fecha_movimiento` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Agregar ruta al menú
Archivo de rutas (probablemente `web.php` o similar):
```php
Route::get('/reportes/facturas-anuladas', \App\Livewire\Reporte\FacturasAnuladas::class)
    ->name('reportes.facturas-anuladas');
```

---

## 🔐 Permisos y Seguridad

### Consideraciones:
1. Solo usuarios autorizados deben poder anular facturas
2. El motivo es obligatorio y debe tener mínimo 10 caracteres
3. La acción es irreversible (no hay "desanular")
4. Se registra en bitácora para auditoría
5. Se valida que la factura no esté ya anulada

### Sugerencia de permisos:
- Crear permiso "anular_facturas"
- Asignar solo a administradores o supervisores
- Validar en el componente antes de mostrar botón

---

## 📈 Métricas Disponibles

Desde el reporte se pueden obtener:
- Total de facturas anuladas
- Monto total anulado
- Facturas anuladas por usuario
- Facturas anuladas por vendedor
- Facturas anuladas por periodo
- Motivos más comunes de anulación

---

## 🐛 Solución de Problemas

### Error: "Column not found: productos_devueltos"
**Solución:** Ejecutar script `crear_tabla_facturas_anuladas.sql`

### Error: "Class FacturaAnulada not found"
**Solución:** 
```bash
composer dump-autoload
```

### Error: "flujo_caja table doesn't exist"
**Solución:** Crear tabla o desmarcar checkbox "Afectar Flujo de Caja"

### No se devuelven productos al inventario
**Solución:** Verificar que existe relación en `factura_has_stock`

---

## ✅ Checklist de Implementación

- [x] Crear tabla `facturas_anuladas`
- [x] Crear modelo `FacturaAnulada`
- [x] Actualizar componente `ListaDeFacturas`
- [x] Crear componente `FacturasAnuladas`
- [x] Crear export `FacturasAnuladasExport`
- [x] Actualizar vista `lista-de-facturas.blade.php`
- [x] Crear vista `facturas-anuladas.blade.php`
- [x] Documentar cambios
- [ ] Ejecutar script SQL en base de datos
- [ ] Agregar ruta al menú de navegación
- [ ] Probar funcionalidad completa
- [ ] Configurar permisos de usuario

---

## 📞 Soporte

Para dudas o problemas con la implementación, revisar:
1. Logs de Laravel (`storage/logs/laravel.log`)
2. Bitácora de base de datos
3. Console del navegador (errores JS)

---

**Fecha de implementación:** 2025-12-07
**Versión:** 1.0
