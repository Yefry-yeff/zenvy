# IMPLEMENTACIÓN DEL BOTÓN RTN Y MODO CLIENTE MANUAL

## Resumen de la Funcionalidad

Se implementó un botón "RTN" en el modal de búsqueda de cliente que permite activar directamente el modo de captura manual de datos del cliente, saltándose la búsqueda y habilitando inmediatamente los campos para edición.

## Nueva Funcionalidad

### 1. **Botón RTN en Modal de Búsqueda**
- **Ubicación**: Modal "Buscar Cliente"
- **Color**: Naranja (`bg-orange-600`)
- **Ícono**: `fas fa-edit`
- **Función**: Activa modo cliente manual inmediatamente
- **Acción**: `wire:click="activarModoClienteManual"`

### 2. **Modo Cliente Manual**
- **Estado**: `$modoClienteManual = true`
- **Campos Habilitados**: Todos los campos de información del cliente
- **Aspecto Visual**: Campos con fondo blanco y bordes editables
- **Funcionalidad**: Permite captura manual completa

### 3. **Campos Dinámicos**
Según el modo actual, los campos cambian automáticamente:

| Campo | Cliente Existente | Modo Manual |
|-------|------------------|-------------|
| **RTN/Identidad** | Solo lectura (gris) | Editable (blanco) |
| **Nombre** | Solo lectura (gris) | Editable (blanco) |
| **Teléfono** | Solo lectura (gris) | Editable (blanco) |
| **Correo** | Solo lectura (gris) | Editable (blanco) |
| **Dirección** | Solo lectura (gris) | Editable (blanco) |

## Archivos Modificados

### 1. **app/Livewire/SalaDeVentas/Ventas.php**

#### Nuevas Propiedades:
```php
// Cliente manual - campos editables
public $rtnManual = '';
public $nombreCompletoManual = '';
public $telefonoManual = '';
public $correoManual = '';
public $direccionManual = '';
public $modoClienteManual = false;
```

#### Nuevos Métodos:
```php
public function activarModoClienteManual()
{
    $this->cliente = null;
    $this->modoClienteManual = true;
    $this->limpiarCamposManual();
    $this->dispatch('cerrar-modal-busqueda');
}

public function limpiarCamposManual()
{
    $this->rtnManual = '';
    $this->nombreCompletoManual = '';
    $this->telefonoManual = '';
    $this->correoManual = '';
    $this->direccionManual = '';
}

public function obtenerNombreCliente()
{
    if ($this->cliente) {
        return $this->cliente->nombre_completo;
    } elseif ($this->modoClienteManual && !empty($this->nombreCompletoManual)) {
        return $this->nombreCompletoManual;
    } else {
        return 'Consumidor Final';
    }
}

public function obtenerRtnCliente()
{
    if ($this->cliente) {
        return $this->cliente->rtn;
    } elseif ($this->modoClienteManual && !empty($this->rtnManual)) {
        return $this->rtnManual;
    } else {
        return null;
    }
}
```

#### Métodos Modificados:
- **`buscarClientePorIdentidad()`**: Incluye búsqueda por RTN
- **`seleccionarClienteModal()`**: Desactiva modo manual al seleccionar cliente
- **`resetearFactura()`**: Incluye limpieza de modo manual
- **Guardado de factura**: Usa métodos auxiliares para obtener datos

### 2. **resources/views/livewire/sala-de-ventas/ventas.blade.php**

#### Modal de Búsqueda - Botones:
```blade
<div class="flex justify-end gap-3">
    <button type="button" @click="open = false" class="...">Cancelar</button>
    <button type="button" wire:click="activarModoClienteManual" class="... bg-orange-600">
        <i class="fas fa-edit me-1"></i> RTN
    </button>
    <button type="submit" class="...">
        <i class="fas fa-search me-1"></i> Buscar
    </button>
</div>
```

#### Condición de Vista:
```blade
@if(isset($cliente) || $modoClienteManual)
```

#### Campos Dinámicos:
```blade
@if($modoClienteManual)
    <input type="text" wire:model="rtnManual" class="... bg-white" placeholder="...">
@else
    <input type="text" class="... bg-gray-50" value="..." readonly>
@endif
```

#### Botones de Control:
```blade
@if($modoClienteManual)
    <button wire:click="resetearFactura" class="... text-red-700 bg-red-50">
        <i class="fas fa-times"></i> Cancelar Modo Manual
    </button>
@else
    <button wire:click="activarModoClienteManual" class="... text-orange-700 bg-orange-50">
        <i class="fas fa-edit"></i> Activar Modo Manual
    </button>
@endif
```

## Flujo de Trabajo

### **Escenario 1: Botón RTN desde Modal**
1. Usuario abre modal "Buscar Cliente"
2. Usuario presiona botón "RTN" (naranja)
3. Modal se cierra automáticamente
4. Sistema activa `modoClienteManual = true`
5. Campos se habilitan para edición
6. Usuario completa información manualmente
7. Al facturar se guardan los datos manuales

### **Escenario 2: Cliente Existente**
1. Usuario busca cliente por identidad/RTN
2. Sistema encuentra cliente en BD
3. `modoClienteManual = false`
4. Campos se llenan automáticamente
5. Campos se bloquean (readonly)
6. Al facturar se usan datos del cliente

### **Escenario 3: Cambio de Modo**
1. Usuario puede cambiar entre modos usando botones:
   - "Activar Modo Manual" → Habilita edición
   - "Cancelar Modo Manual" → Vuelve al estado inicial

## Estados del Sistema

| Estado | Cliente | ModoManual | Campos | Datos Factura |
|--------|---------|------------|--------|---------------|
| **Inicial** | `null` | `false` | Ocultos | No disponible |
| **Cliente Encontrado** | `objeto` | `false` | Bloqueados | Del cliente |
| **Modo Manual** | `null` | `true` | Habilitados | Manuales |

## Características Visuales

### **Campos Bloqueados (Cliente Existente)**
- Fondo: `bg-gray-50` (gris claro)
- Texto: `text-gray-700`
- Estado: `readonly`
- Cursor: `cursor-not-allowed`

### **Campos Habilitados (Modo Manual)**
- Fondo: `bg-white` (blanco)
- Borde: `border-gray-300`
- Enfoque: `focus:border-blue-500 focus:ring-1 focus:ring-blue-200`
- Placeholder: Texto guía

### **Botones**
- **RTN**: `bg-orange-600` (naranja)
- **Buscar**: Según tema del usuario
- **Cancelar Modo**: `bg-red-50 text-red-700`
- **Activar Modo**: `bg-orange-50 text-orange-700`

## Validaciones y Lógica

### **Obtención de Datos para Factura**
```php
// Nombre del cliente
if (cliente existe) → cliente.nombre_completo
else if (modo manual Y nombre manual lleno) → nombreCompletoManual
else → "Consumidor Final"

// RTN del cliente
if (cliente existe) → cliente.rtn
else if (modo manual Y RTN manual lleno) → rtnManual
else → null
```

### **Búsqueda Mejorada**
```php
WHERE cliente.identidad = $busqueda 
   OR cliente.rtn = $busqueda
```

## Archivos de Prueba

- **`test_boton_rtn.php`**: Prueba completa de funcionalidad
  - Verifica flujo del botón RTN
  - Simula datos manuales
  - Compara con cliente existente
  - Valida guardado en factura

## Beneficios

1. **Acceso Directo**: Botón RTN permite activar modo manual inmediatamente
2. **Flexibilidad**: Usuario puede cambiar entre modos fácilmente
3. **Claridad Visual**: Diferenciación clara entre campos bloqueados y editables
4. **Eficiencia**: No requiere búsqueda fallida para activar modo manual
5. **Compatibilidad**: Mantiene toda la funcionalidad existente

## Casos de Uso

- **Cliente Ocasional**: Usar botón RTN para captura rápida
- **Cliente con RTN**: Ingresar RTN directamente sin registrar cliente
- **Factura Corporativa**: Capturar datos específicos para empresas
- **Cliente Temporal**: Información que no requiere almacenamiento permanente

La implementación está completa y lista para producción, permitiendo mayor flexibilidad en la captura de información del cliente.
