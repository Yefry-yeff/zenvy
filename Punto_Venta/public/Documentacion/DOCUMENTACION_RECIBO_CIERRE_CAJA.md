# IMPLEMENTACIÓN DE RECIBO DE CIERRE DE CAJA

## Fecha: 05 de Diciembre 2025
## Sistema: ZENVY POS

---

## RESUMEN DE CAMBIOS

Se implementó un sistema completo de impresión de recibos para el cierre de caja, que genera un documento PDF similar al formato de las facturas del sistema.

---

## ARCHIVOS CREADOS

### 1. Vista PDF del Recibo
**Archivo:** `resources/views/pdf/recibo-cierre-caja.blade.php`
- Diseño tipo ticket térmico (72.1mm x 350mm)
- Muestra encabezado de empresa con logo
- Información del usuario y fecha del cierre
- Resumen de transacciones por método de pago
- Detalle de efectivo con denominaciones contadas
- Totales de tarjeta, transferencia y cheque (sistema vs contado)
- **Efectivo a entregar**: Cálculo automático (efectivo contado - L. 2,000.00 saldo inicial)

### 2. Controlador PDF
**Archivo:** `app/Http/Controllers/CierreCajaPDFController.php`
- Método `generarPDF($cierreId)`: Descarga el recibo en PDF
- Método `previsualizarPDF($cierreId)`: Muestra el recibo en navegador
- Carga datos del cierre de la tabla `cierre_caja_historico`
- Genera resumen de transacciones del día
- Filtra denominaciones con cantidad > 0

### 3. Script de Base de Datos
**Archivo:** `Bases de datos/Script/agregar_denominaciones_cierre_caja.sql`
- Agrega columnas individuales para cada denominación:
  * `billetes_500`, `billetes_200`, `billetes_100`, `billetes_50`
  * `billetes_20`, `billetes_10`, `billetes_5`, `billetes_2`, `billetes_1`
  * `monedas_0_50`, `monedas_0_20`, `monedas_0_10`, `monedas_0_05`

---

## ARCHIVOS MODIFICADOS

### 1. Rutas Web
**Archivo:** `routes/web.php`
- Agregó `Route::get('cierre-caja/{id}/pdf', ...)` para descargar PDF
- Agregó `Route::get('cierre-caja/{id}/pdf/preview', ...)` para previsualizar PDF

### 2. Componente Livewire
**Archivo:** `app/Livewire/Caja/CierreDeCaja.php`
- Modificó método `procesarCierre()`:
  * Usa `insertGetId()` para obtener el ID del cierre insertado
  * Corrige nombres de columnas: `user_id`, `total_efectivo_sistema`, `diferencia`
  * Guarda denominaciones en columnas individuales (no JSON)
  * Incluye el saldo inicial (L. 2,000.00) en el efectivo del sistema
  * Dispara evento `abrirReciboCierre` al finalizar el cierre

### 3. Vista Blade del Cierre
**Archivo:** `resources/views/livewire/caja/cierre-de-caja.blade.php`
- Agregó script JavaScript para escuchar evento `abrirReciboCierre`
- Abre automáticamente el PDF del recibo en nueva ventana al procesar cierre

---

## FUNCIONALIDAD IMPLEMENTADA

### Flujo Completo:

1. **Usuario procesa el cierre de caja:**
   - Ingresa denominaciones de efectivo
   - Ingresa totales contados de tarjeta, transferencia, cheque
   - Presiona botón "Procesar Cierre de Caja"

2. **Sistema guarda el cierre:**
   - Inserta registro en `cierre_caja_historico` con todos los datos
   - Resetea la caja al saldo inicial (L. 2,000.00)
   - Obtiene el ID del cierre insertado

3. **Sistema genera y abre el recibo automáticamente:**
   - Dispara evento JavaScript con el ID del cierre
   - Abre nueva ventana con el PDF del recibo
   - El recibo muestra toda la información del cierre

### Contenido del Recibo:

#### Encabezado
- Logo de la empresa
- Nombre comercial y razón social
- RTN y dirección

#### Información del Cierre
- Usuario que realizó el cierre
- Fecha y hora del cierre

#### Resumen de Ventas
- Efectivo: L. X,XXX.XX
- Tarjeta (POS): L. X,XXX.XX
- Transferencia: L. X,XXX.XX
- Cheque: L. X,XXX.XX
- **Total Ventas: L. X,XXX.XX**

#### Efectivo (Detallado)
- Sistema (incluye L. 2,000 inicial): L. X,XXX.XX
- **Denominaciones Contadas:**
  * L. 500 × 10 = L. 5,000.00
  * L. 100 × 15 = L. 1,500.00
  * (etc., solo las ingresadas)
- Total Contado: L. X,XXX.XX
- **Diferencia Efectivo: L. X,XXX.XX** (verde si positivo, rojo si negativo)

#### Tarjeta (POS)
- Sistema: L. X,XXX.XX
- Contado: L. X,XXX.XX
- Diferencia: L. X,XXX.XX

#### Transferencia
- Sistema: L. X,XXX.XX
- Contado: L. X,XXX.XX
- Diferencia: L. X,XXX.XX

#### Cheque
- Sistema: L. X,XXX.XX
- Contado: L. X,XXX.XX
- Diferencia: L. X,XXX.XX

#### **EFECTIVO A ENTREGAR** (Destacado)
```
Efectivo contado:        L. 15,000.00
Menos saldo inicial:     L.  2,000.00
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TOTAL A ENTREGAR:        L. 13,000.00
```

---

## CAMBIOS EN BASE DE DATOS

### Tabla: `cierre_caja_historico`

#### Columnas Existentes (mantener):
- `user_id`, `tienda_id`, `fecha_cierre`
- `periodo_inicio`, `periodo_fin`
- `total_efectivo_sistema`, `total_efectivo_contado`, `diferencia`
- `total_tarjeta`, `total_tarjeta_contado`, `diferencia_tarjeta`
- `total_transferencia`, `total_transferencia_contado`, `diferencia_transferencia`
- `total_cheque`, `total_cheque_contado`, `diferencia_cheque`
- `total_general`, `cantidad_facturas`, `observaciones`

#### Columnas Nuevas (agregar con script SQL):
- `billetes_500`, `billetes_200`, `billetes_100`, `billetes_50`
- `billetes_20`, `billetes_10`, `billetes_5`, `billetes_2`, `billetes_1`
- `monedas_0_50`, `monedas_0_20`, `monedas_0_10`, `monedas_0_05`

**IMPORTANTE:** Ejecutar el script `agregar_denominaciones_cierre_caja.sql` antes de usar esta funcionalidad.

---

## INSTRUCCIONES DE INSTALACIÓN

### 1. Ejecutar Script SQL:
```bash
# En MySQL o phpMyAdmin, ejecutar:
Bases de datos/Script/agregar_denominaciones_cierre_caja.sql
```

### 2. Verificar Rutas:
Las rutas ya están agregadas en `routes/web.php`:
- `/cierre-caja/{id}/pdf` → Descargar recibo
- `/cierre-caja/{id}/pdf/preview` → Ver recibo en navegador

### 3. Probar Funcionalidad:
1. Abrir caja (si está cerrada)
2. Realizar algunas ventas con diferentes métodos de pago
3. Ir a "Cierre de Caja"
4. Ingresar denominaciones y totales contados
5. Procesar cierre
6. **El recibo se abrirá automáticamente** en nueva ventana

---

## CÁLCULO DEL EFECTIVO A ENTREGAR

El sistema calcula automáticamente cuánto efectivo debe entregar el cajero:

```
Efectivo a Entregar = Efectivo Contado - Saldo Inicial
Efectivo a Entregar = Total Contado - L. 2,000.00
```

**Ejemplo:**
- Total efectivo contado: L. 15,000.00
- Saldo inicial (queda en caja): L. 2,000.00
- **Efectivo a entregar: L. 13,000.00**

Este monto es el que el cajero debe entregar al administrador, dejando los L. 2,000.00 en la caja para el siguiente turno.

---

## NOTAS TÉCNICAS

### Generación del PDF:
- Utiliza la librería `barryvdh/laravel-dompdf`
- Tamaño de página: 72.1mm x 350mm (ticket térmico)
- Fuente: Arial, tamaño 20px base
- DPI: 150 para mejor calidad

### Nombres de Columnas:
- En tabla `cierre_caja_historico`: `user_id` (no `users_id`)
- Efectivo sistema: `total_efectivo_sistema`
- Diferencia efectivo: `diferencia` (no `diferencia_efectivo`)

### Evento Livewire:
```php
$this->dispatch('abrirReciboCierre', cierreId: $cierreId);
```

### JavaScript Listener:
```javascript
Livewire.on('abrirReciboCierre', (event) => {
    window.open(`/cierre-caja/${event.cierreId}/pdf/preview`, '_blank');
});
```

---

## TESTING

### Caso de Prueba 1: Cierre Exacto
- Total sistema: L. 10,000.00
- Total contado: L. 10,000.00
- **Diferencia: L. 0.00** ✅

### Caso de Prueba 2: Sobrante
- Total sistema: L. 10,000.00
- Total contado: L. 10,050.00
- **Diferencia: L. 50.00** (positiva, verde) ✅

### Caso de Prueba 3: Faltante
- Total sistema: L. 10,000.00
- Total contado: L. 9,950.00
- **Diferencia: -L. 50.00** (negativa, roja) ⚠️

---

## MANTENIMIENTO FUTURO

### Para agregar más denominaciones:
1. Agregar columna a la tabla: `ALTER TABLE cierre_caja_historico ADD COLUMN monedas_0_01 INT DEFAULT 0;`
2. Agregar input en `cierre-de-caja.blade.php`
3. Agregar propiedad en `CierreDeCaja.php`
4. Agregar en array de guardado en `procesarCierre()`
5. Agregar en array del controlador PDF

### Para cambiar el saldo inicial:
1. Modificar constante `SALDO_INICIAL` en `CierreDeCaja.php`
2. Actualizar texto hardcodeado en vistas blade y PDF

---

## SOPORTE

Para dudas o problemas:
1. Verificar que el script SQL se ejecutó correctamente
2. Revisar logs en `storage/logs/laravel.log`
3. Verificar permisos de escritura en `storage/`
4. Comprobar que DomPDF está instalado: `composer show barryvdh/laravel-dompdf`

---

**Fin del Documento**
