# Solución: Dropdowns y Bandeja de Pedidos Web

## Cambios Implementados

### 1. Dropdowns con Position Fixed (z-index 9999)

**Problema:** Los dropdowns (campana y perfil) aparecían debajo de otros elementos.

**Solución:**
- Cambié ambos dropdowns de `position: absolute` a `position: fixed`
- Z-index aumentado a `9999` para ambos
- JavaScript calcula dinámicamente la posición usando `getBoundingClientRect()`
- El header ahora tiene `z-[9999] relative`

**Archivos modificados:**
- `resources/views/layouts/app.blade.php`

### 2. Componente Livewire para Bandeja de Pedidos

**Problema:** La ruta `/pedidos-web` no funcionaba dentro del sistema de componentes dinámicos.

**Solución:**
- Creado componente Livewire `BandejaPedidos`
- La vista ahora se carga dentro del componente dinámico
- El enlace "Ver todos los pedidos →" ahora dispara Livewire en lugar de redirigir

**Archivos creados/modificados:**
- `app/Livewire/BandejaPedidos.php` (nuevo)
- `resources/views/livewire/bandeja-pedidos.blade.php` (nuevo)

## Cómo Usar

### Ver Bandeja de Pedidos:

1. **Desde el dropdown:**
   - Click en la campana 🔔
   - Ver últimos 10 pedidos
   - Click en "Ver todos los pedidos →"

2. **Resultado:**
   - Se carga el componente Livewire dentro del área de contenido dinámico
   - Tabla completa con paginación
   - Estados: Pendiente, Procesando, Facturado, Rechazado

### Dropdowns Posicionados Correctamente:

- **Campana (Pedidos):** Siempre visible por encima de cualquier elemento
- **Perfil (Usuario):** Siempre visible por encima de cualquier elemento
- Posicionamiento automático relativo al botón que los abre

## Notas Técnicas

- Los dropdowns usan `position: fixed` con cálculo de posición JavaScript
- Alpine.js usado solo para el dropdown del perfil (ya existía)
- Vanilla JS para el dropdown de pedidos (mayor compatibilidad)
- Componente Livewire se integra perfectamente con el sistema de navegación dinámico existente
