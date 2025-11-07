# Configuración de AI para Reportes con Conexión a Base de Datos

## ✅ Estado Actual
La funcionalidad de AI para reportes **ya está configurada para conectarse a la base de datos** usando Laravel's Eloquent ORM y Query Builder.

## 🔧 Configuración Requerida

### 1. Base de Datos
Asegúrate de que tu archivo `.env` tenga la configuración correcta de base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=punto_venta
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

### 2. API Key de Groq
Para que la AI funcione, necesitas configurar tu API Key de Groq (servicio gratuito):

```env
GROQ_API_KEY=tu_api_key_aqui
```

**Obtener API Key gratuita:**
1. Visita: https://console.groq.com
2. Regístrate gratuitamente
3. Genera tu API Key
4. Agrégala al archivo `.env`

### 3. Tabla para Historial (Opcional)
La AI guarda un historial de consultas. Crea la tabla si no existe:

```sql
CREATE TABLE IF NOT EXISTS ai_consultas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    pregunta TEXT NOT NULL,
    respuesta TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 🎯 Cómo Funciona

### Conexión a Base de Datos
El componente `App\Livewire\Reporte\Ai` se conecta automáticamente a tu base de datos usando:

1. **Query Builder de Laravel**: `DB::table('tabla')->...`
2. **Consultas SQL directas**: `DB::select($sql)`

### Flujo de Trabajo
1. Usuario ingresa una consulta en lenguaje natural
2. Sistema obtiene contexto de la base de datos (tablas, estadísticas, esquema)
3. AI (Groq) genera una consulta SQL basada en el contexto
4. Sistema ejecuta la consulta SQL en tu base de datos
5. Resultados se muestran en tabla interactiva
6. Usuario puede descargar resultados en Excel

### Ejemplo de Uso
```
Usuario: "Muestra las ventas del día de hoy"
AI: Genera SQL → SELECT * FROM factura WHERE DATE(created_at) = CURDATE()
Sistema: Ejecuta en BD → Muestra resultados
```

## 🔒 Seguridad

### Validaciones Implementadas
1. ✅ **Solo consultas SELECT**: Rechaza INSERT, UPDATE, DELETE, DROP
2. ✅ **Reintentos inteligentes**: Si hay error SQL, AI lo corrige automáticamente (máx 3 intentos)
3. ✅ **Logging de errores**: Todos los errores se registran sin exponer detalles al usuario
4. ✅ **Validación de entrada**: Mínimo 10 caracteres, máximo 1000

## 📊 Tablas Disponibles para Reportes

El sistema tiene acceso a todas las tablas de tu base de datos, incluyendo:

### Principales
- `factura` - Facturas de venta
- `factura_has_producto` - Detalle de productos en facturas
- `producto` - Catálogo de productos
- `cliente` - Información de clientes
- `compra` - Compras a proveedores
- `usuario` - Usuarios del sistema

### Catálogos
- `marca` - Marcas de productos
- `categoria` - Categorías
- `segmento` - Segmentos
- `seccion` - Secciones
- `tipo_pago` - Tipos de pago
- `bodega` - Bodegas/Almacenes
- `descuentos` - Descuentos aplicables

## 🚀 Mejoras Implementadas

### v2.0 - Conexión Inteligente a Base de Datos
- ✅ Esquema detallado de tablas enviado a la AI
- ✅ Relaciones entre tablas documentadas
- ✅ Sistema de reintentos con corrección automática de errores SQL
- ✅ Validación de sintaxis SQL antes de ejecutar
- ✅ Manejo robusto de excepciones
- ✅ Contexto enriquecido con estadísticas en tiempo real

### Funcionalidades
- ✅ Generación automática de SQL basada en lenguaje natural
- ✅ Ejecución segura de consultas
- ✅ Visualización de resultados en tabla
- ✅ Exportación a Excel
- ✅ Historial de consultas
- ✅ Reutilización de consultas anteriores

## 📝 Ejemplos de Consultas

### Ventas
- "Muestra las ventas de esta semana"
- "¿Cuáles son los productos más vendidos este mes?"
- "Total de ventas por cliente en el último trimestre"

### Productos
- "Lista de productos con existencia menor a 10 unidades"
- "Productos por marca ordenados por precio"
- "Productos sin ventas en los últimos 30 días"

### Clientes
- "Top 10 clientes con más compras"
- "Clientes que no han comprado en 3 meses"
- "Monto total de compras por cliente"

### Compras
- "Compras realizadas esta semana"
- "Total gastado por proveedor este mes"
- "Productos más comprados en el año"

## 🐛 Troubleshooting

### Error: "No se ha configurado GROQ_API_KEY"
**Solución**: Agrega tu API Key en el archivo `.env`

### Error: "No se pudo generar el reporte"
**Posibles causas**:
1. Consulta muy ambigua - reformula más específicamente
2. Nombres de tablas/columnas incorrectos - verifica el esquema
3. Error de sintaxis SQL - el sistema intentará corregir automáticamente

### La AI no genera SQL
**Solución**: Sé más específico en tu consulta, usa palabras como "reporte", "mostrar datos", "lista"

### Consulta muy lenta
**Solución**: 
1. Limita el rango de fechas
2. Usa condiciones WHERE más específicas
3. Evita JOINs complejos en tablas muy grandes

## 📦 Dependencias

```json
{
    "guzzlehttp/guzzle": "^7.8",
    "maatwebsite/excel": "^3.1",
    "laravel/framework": "^11.0"
}
```

Si falta alguna, instalar con:
```bash
composer require guzzlehttp/guzzle
composer require maatwebsite/excel
```

## 🎓 Notas Técnicas

- **Modelo AI**: Llama-3.3-70b-versatile (Groq)
- **Temperatura**: 0.7 (balance entre creatividad y precisión)
- **Max tokens**: 2048
- **Timeout**: 30 segundos
- **Base de datos**: MySQL/MariaDB
- **Framework**: Laravel 11.x + Livewire 3.x

---

**Última actualización**: Noviembre 2025
**Versión**: 2.0
**Autor**: Sistema Zenvy
