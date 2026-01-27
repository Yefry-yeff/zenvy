# Implementación: Mostrar Código Valencia en Lista y Edición de Productos

## ✅ Cambios Realizados

### 1. **Modelo `Producto.php`**
Se agregaron métodos para acceder al código de producto (Valencia o Zenvy):

```php
// Relación con mapeo Valencia
public function mapeoValencia()
{
    return $this->hasOne(ProductoValenciaZenvy::class, 'producto_id_zenvy', 'id');
}

// Atributo calculado: muestra código Valencia si existe, sino Zenvy
public function getCodigoMostrarAttribute()
{
    $mapeo = $this->mapeoValencia;
    
    if ($mapeo && $mapeo->codigo_producto_valencia) {
        return $mapeo->codigo_producto_valencia;
    }
    
    return $this->codigo_estatal;
}

// Verificar si está sincronizado con Valencia
public function estaEnValencia()
{
    return $this->mapeoValencia()->exists();
}
```

### 2. **Componente Livewire `Producto.php`**

**Cambios en el método `render()`:**
```php
$query = ProductoModel::select([
    // ... campos existentes
    'producto.codigo_estatal', // ✅ Agregado
])
->with([
    // ... relaciones existentes
    'mapeoValencia:producto_id_zenvy,producto_id_valencia,codigo_producto_valencia' // ✅ Agregado
])
```

**Cambios en `confirmarEliminar()`:**
```php
$producto = ProductoModel::with([
    'marca', 
    'subcategoria.categoria', 
    'mapeoValencia' // ✅ Agregado
])->find($id);

$this->productoSeleccionado = (object) [
    // ... campos existentes
    'codigo_estatal' => $producto->codigo_estatal, // ✅ Agregado
    'mapeoValencia' => $producto->mapeoValencia // ✅ Agregado
];
```

### 3. **Vista Blade `producto.blade.php`**

#### a) Nueva Columna en Encabezado de Tabla
**Antes de "Cód. Barras":**
```blade
<th class="px-2 py-1.5 text-left border-b w-24">
    <div class="flex items-center space-x-1">
        <span class="text-xs font-semibold">Cód. Prod</span>
    </div>
</th>
```

#### b) Nuevo Input de Filtro
```blade
<th class="px-2 py-1 border-b">
    <input type="text"
           wire:model.live.debounce.300ms="filtroCodigo"
           placeholder="..."
           class="...">
</th>
```

#### c) Columna en Filas con Presentaciones
```blade
@if($index === 0)
    <td class="px-2 py-1 text-xs text-gray-700 border-r"
        rowspan="{{ $numPresentaciones }}"
        wire:click="editar({{ $producto->id }})">
        @if($producto->mapeoValencia && $producto->mapeoValencia->codigo_producto_valencia)
            <!-- Código VALENCIA (naranja con icono) -->
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-xs font-mono text-orange-800 bg-orange-50 border border-orange-200 rounded" title="Código Valencia">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 9a1 1 0 112 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z"/>
                </svg>
                {{ $producto->mapeoValencia->codigo_producto_valencia }}
            </span>
        @elseif($producto->codigo_estatal)
            <!-- Código ZENVY (gris) -->
            <span class="text-xs font-mono text-gray-600" title="Código Zenvy">
                {{ $producto->codigo_estatal }}
            </span>
        @else
            <!-- Sin código -->
            <span class="text-xs text-gray-400">Sin código</span>
        @endif
    </td>
@endif
```

#### d) Columna en Productos Sin Presentaciones
Similar al anterior, pero con clases para productos completos:
```blade
<td class="px-4 py-3 text-sm" wire:click="editar({{ $producto->id }})">
    <!-- Lógica idéntica con clases ajustadas -->
</td>
```

#### e) Modal de Eliminación
```blade
<div class="p-3 mb-4 rounded bg-gray-50">
    <h6 class="font-medium">{{ $productoSeleccionado->nombre ?? '' }}</h6>
    <div class="mt-1 space-y-1">
        @php
            $mapeo = $productoSeleccionado->mapeoValencia;
        @endphp
        @if($mapeo && $mapeo->codigo_producto_valencia)
            <p class="text-sm text-gray-600">
                <span class="font-semibold">Código Valencia:</span>
                <span class="px-2 py-0.5 ml-1 text-xs font-mono text-orange-800 bg-orange-50 border border-orange-200 rounded">
                    {{ $mapeo->codigo_producto_valencia }}
                </span>
            </p>
        @elseif($productoSeleccionado->codigo_estatal)
            <p class="text-sm text-gray-600">
                <span class="font-semibold">Código Producto:</span>
                <span class="ml-1 font-mono">{{ $productoSeleccionado->codigo_estatal }}</span>
            </p>
        @endif
        <p class="text-sm text-gray-600">
            <span class="font-semibold">Código Barras:</span>
            <span class="ml-1 font-mono">{{ $productoSeleccionado->codigo_barra ?? 'Sin código' }}</span>
        </p>
    </div>
</div>
```

## 🎨 Diseño Visual

### Código Valencia
- **Color**: Naranja (`text-orange-800`, `bg-orange-50`, `border-orange-200`)
- **Icono**: SVG de información circular
- **Formato**: Badge con borde redondeado
- **Tooltip**: "Código Valencia"

### Código Zenvy
- **Color**: Gris (`text-gray-600`)
- **Formato**: Texto mono-espaciado simple
- **Tooltip**: "Código Zenvy"

### Sin Código
- **Color**: Gris claro (`text-gray-400`)
- **Texto**: "Sin código"

## 🔍 Lógica de Prioridad

```
1. ¿Existe mapeoValencia Y tiene codigo_producto_valencia?
   ✅ Mostrar código Valencia (naranja con icono)
   
2. ¿No? ¿Tiene codigo_estatal en tabla producto?
   ✅ Mostrar código Zenvy (gris)
   
3. ¿Ninguno de los anteriores?
   ✅ Mostrar "Sin código"
```

## 📊 Ubicaciones Actualizadas

1. ✅ **Lista de productos** - Columna "Cód. Prod" (antes de "Cód. Barras")
2. ✅ **Productos con presentaciones** - Rowspan en primera fila
3. ✅ **Productos sin presentaciones** - Columna individual
4. ✅ **Modal de eliminación** - Sección de información del producto
5. ✅ **Filtros** - Input de filtro por código de producto

## 🚀 Beneficios

- ✅ **Identificación visual**: Los códigos Valencia se destacan con color naranja
- ✅ **Fallback automático**: Si no hay código Valencia, muestra el de Zenvy
- ✅ **Información clara**: Tooltips indican el origen del código
- ✅ **Consistencia**: Mismo diseño en lista y modal
- ✅ **Rendimiento**: Eager loading de relación mapeoValencia
