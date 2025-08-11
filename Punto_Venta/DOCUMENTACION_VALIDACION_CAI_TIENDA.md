# Sistema de Validación CAI por Tienda Antes de Facturar

## Resumen de Implementación

Se ha implementado un sistema completo de validación CAI que **impide la facturación** cuando una tienda no tiene un CAI activo y válido con cantidad disponible.

## Archivos Modificados

### 1. Servicio CAI Mejorado: `app/Services/CAIService.php`

#### Nuevos Métodos Implementados:

**`validarCAIParaTienda($tiendaId)`**
- Valida que la tienda tenga un CAI activo en la tabla CAI
- Verifica que el CAI no esté vencido
- Confirma que existe registro en gestion_cai con cantidad disponible
- Retorna información detallada del resultado

**`obtenerSiguienteNumeroFactura($tiendaId = null)`**
- Modificado para filtrar por tienda específica
- Genera números de factura solo para CAIs de la tienda correspondiente

**`verificarDisponibilidadCAI($tiendaId = null)`**
- Modificado para soportar validación por tienda

### 2. Componente Ventas Mejorado: `app/Livewire/SalaDeVentas/Ventas.php`

#### Validaciones Implementadas:

**En `verificarCAI()`:**
- Validación específica por tienda del usuario
- Mensajes de error detallados
- Actualización automática del estado CAI

**En `finalizarVentaConDistribucion()`:**
- **Validación obligatoria antes de iniciar transacción**
- Bloqueo completo si no hay CAI válido
- Mensajes de error específicos para el usuario

**En `generarNumeroFactura()`:**
- Uso de CAI específico de la tienda del usuario
- Logs detallados con información de tienda

### 3. Vista Mejorada: `resources/views/livewire/sala-de-ventas/ventas.blade.php`

**Botón "Procesar Pago" Mejorado:**
- Se deshabilita automáticamente si hay errores críticos de CAI
- Tooltip informativo sobre el problema
- Validación visual inmediata

## Flujo de Validación

### 1. **Al Cargar la Vista de Ventas**
```php
verificarCAI() → validarCAIParaTienda($tiendaUsuario)
```
- Verifica CAI activo para la tienda del usuario
- Muestra alertas si no hay CAI disponible
- Bloquea el botón de procesamiento si es crítico

### 2. **Al Intentar Procesar Pago**
```php
finalizarVentaConDistribucion() → validarCAIParaTienda($tiendaUsuario)
```
- **VALIDACIÓN OBLIGATORIA** antes de crear la factura
- Si no es válido: cancela el proceso y muestra error
- Si es válido: continúa con la facturación normal

### 3. **Al Generar Número de Factura**
```php
generarNumeroFactura() → obtenerSiguienteNumeroFactura($tiendaUsuario)
```
- Usa exclusivamente CAIs de la tienda del usuario
- Actualiza automáticamente las cantidades disponibles

## Validaciones Implementadas

### ✅ **Validación en Tabla CAI**
- CAI debe existir para la tienda (`tienda_id`)
- CAI debe estar activo (`estado_id = 1`)
- CAI no debe estar vencido (`fecha_limite_emision >= HOY`)

### ✅ **Validación en Tabla gestion_cai**
- Debe existir registro vinculado al CAI
- Registro debe estar activo (`estado_id = 1`)
- Debe tener cantidad disponible (`cantidad_no_utilizada > 0`)

### ✅ **Validación de Usuario**
- Solo puede facturar con CAIs de su tienda asignada
- Acceso basado en `Auth::user()->tienda_id`

## Casos de Error Manejados

| Escenario | Mensaje de Error | Acción |
|-----------|------------------|---------|
| Sin CAI para la tienda | "No hay CAI activo válido para esta tienda" | Bloquea facturación |
| CAI vencido | "La tienda no tiene un CAI activo o el CAI está vencido" | Bloquea facturación |
| Sin cantidad disponible | "No hay CAI con cantidad disponible para facturar" | Bloquea facturación |
| CAI por agotarse | "Su CAI tiene solo X facturas restantes" | Permite facturar con aviso |

## Mensajes de Usuario

### 🔴 **Errores Críticos** (Bloquean facturación)
- "¡CRÍTICO! No hay CAI activos disponibles para facturar en su tienda"
- "❌ No se puede facturar: [detalle del error]"

### 🟡 **Avisos** (Permiten facturar)
- "¡AVISO! Su CAI tiene solo X facturas restantes"
- "¡ATENCIÓN! El CAI de su tienda se ha agotado"

## Seguridad y Integridad

### 🔒 **Prevención de Fraudes**
- Usuarios solo pueden usar CAIs de su tienda
- Validación doble: tabla CAI + gestion_cai
- Transacciones atómicas con rollback automático

### 🛡️ **Manejo de Errores**
- Logs detallados de todas las validaciones
- Rollback automático en caso de error
- Mensajes informativos sin exponer detalles técnicos

### ⚡ **Rendimiento**
- Validaciones eficientes con índices de BD
- Caché de información CAI en memoria
- Validación temprana para evitar procesamiento innecesario

## Estados del Sistema

### ✅ **Sistema Operativo Normal**
```
CAI activo → Gestión activa → Cantidad > 0 → ✅ FACTURACIÓN PERMITIDA
```

### ⚠️ **Sistema en Alerta**
```
CAI activo → Gestión activa → Cantidad ≤ 10 → ⚠️ FACTURACIÓN CON AVISO
```

### 🔴 **Sistema Bloqueado**
```
Sin CAI activo / CAI vencido / Sin cantidad → ❌ FACTURACIÓN BLOQUEADA
```

## Compatibilidad

- ✅ Compatible con sistema multi-tienda existente
- ✅ Preserva funcionalidad de facturación normal
- ✅ Mantiene logs y auditoría completa
- ✅ No afecta facturas existentes

## Beneficios Implementados

1. **Cumplimiento Legal**: Garantiza que todas las facturas usen CAI válidos
2. **Control por Tienda**: Cada tienda solo puede usar sus propios CAIs
3. **Prevención de Errores**: Bloquea facturación con CAIs vencidos o agotados
4. **Experiencia de Usuario**: Mensajes claros y acciones preventivas
5. **Integridad de Datos**: Validaciones múltiples antes de cualquier transacción

## Ejemplo de Uso

```php
// El usuario inicia sesión y va a Ventas
$tiendaId = Auth::user()->tienda_id; // ej: 1

// Sistema valida automáticamente
$validacion = $caiService->validarCAIParaTienda($tiendaId);

if (!$validacion['valido']) {
    // Botón "Procesar Pago" se deshabilita
    // Mensaje: "No hay CAI activo válido para esta tienda"
    return; // No permite facturar
}

// Si es válido, permite continuar con la facturación normal
$numeroFactura = $caiService->obtenerSiguienteNumeroFactura($tiendaId);
// Resultado: "000-001-000001" (solo de CAIs de esa tienda)
```

La implementación garantiza que **no se puede facturar sin un CAI válido, activo y con cantidad disponible** para la tienda específica del usuario.
