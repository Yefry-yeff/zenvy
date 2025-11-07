# Configuración de Reportes con IA

## ✅ Sistema Instalado Exitosamente

Se ha implementado un sistema de generación de reportes usando **Inteligencia Artificial gratuita** con Groq (Llama 3.3 70B).

---

## 📋 Pasos para Configurar

### 1. Obtener API Key Gratuita de Groq

1. Visita: **https://console.groq.com**
2. Crea una cuenta gratuita (con Google, GitHub o email)
3. Ve a **API Keys** en el menú lateral
4. Haz clic en **Create API Key**
5. Dale un nombre (ejemplo: "Zenvy POS")
6. Copia la API key generada

### 2. Configurar en el Proyecto

1. Abre el archivo `.env` en la raíz del proyecto
2. Agrega esta línea al final:

```env
GROQ_API_KEY=tu_api_key_aqui
```

3. Reemplaza `tu_api_key_aqui` con la key que copiaste
4. Guarda el archivo

### 3. Limpiar Cache (Opcional)

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 🚀 Cómo Usar

### Acceder al Módulo

1. Navega a la sección de **Reportes > AI** en tu sistema
2. Verás una interfaz con:
   - **Campo de consulta**: Escribe tu pregunta en lenguaje natural
   - **Historial**: Consultas anteriores
   - **Configuración**: Estado de la API

### Ejemplos de Consultas

```
✅ "Dame un resumen de las ventas del último mes"
✅ "¿Cuáles son los 10 productos más vendidos?"
✅ "Muestra los clientes con más compras"
✅ "¿Qué productos están bajos en stock?"
✅ "Genera una consulta SQL para ver productos sin vender"
✅ "Analiza las ventas de esta semana comparadas con la semana pasada"
```

### Capacidades de la IA

La IA tiene acceso a:
- ✅ Información de productos
- ✅ Datos de ventas y facturas
- ✅ Clientes
- ✅ Inventario y stock
- ✅ Métodos de pago
- ✅ Descuentos aplicados

Puede:
- 📊 Generar reportes en formato Markdown
- 📈 Crear tablas comparativas
- 💾 Sugerir consultas SQL optimizadas
- 📝 Analizar tendencias
- 🔍 Identificar patrones

---

## 🎯 Características

### ✨ Interfaz Moderna
- Diseño responsive
- Botones con estado de carga
- Validación de formularios
- Mensajes de error claros

### 📚 Historial de Consultas
- Guarda las últimas 10 consultas
- Reutiliza consultas anteriores con un clic
- Timestamps con formato "hace X minutos"

### 🎨 Respuestas Formateadas
- Markdown renderizado
- Tablas con estilo
- Código SQL con highlight
- Listas ordenadas y desordenadas

### ⚡ Rendimiento
- Modelo rápido (Llama 3.3 70B)
- Timeout de 30 segundos
- Respuestas de hasta 2048 tokens
- API gratuita con límites generosos

---

## 🔒 Seguridad

- ✅ API Key almacenada en `.env` (no en código)
- ✅ Validación de entrada del usuario
- ✅ Logs de errores
- ✅ Timeout para evitar bloqueos
- ✅ Historial por usuario

---

## 📊 Límites de la API Gratuita

**Groq Free Tier:**
- ✅ 30 requests/minuto
- ✅ 14,400 requests/día
- ✅ Sin costo
- ✅ Sin tarjeta de crédito requerida

**Suficiente para:**
- Uso normal de un punto de venta
- Reportes diarios
- Consultas esporádicas

---

## 🛠️ Archivos Modificados/Creados

```
✅ app/Livewire/Reporte/Ai.php (componente Livewire)
✅ resources/views/livewire/reporte/ai.blade.php (vista)
✅ database/migrations/2025_11_06_164309_create_ai_consultas_table.php
✅ composer.json (agregado guzzlehttp/guzzle)
```

---

## 🐛 Solución de Problemas

### Error: "No se ha configurado GROQ_API_KEY"
**Solución:** Agrega `GROQ_API_KEY=tu_key` en el archivo `.env`

### Error: "Respuesta inválida de la API"
**Solución:** 
- Verifica que la API key sea correcta
- Revisa que tengas conexión a internet
- Verifica límites de rate limit

### No aparece el módulo en el menú
**Solución:** 
- Verifica que la ruta esté configurada
- Limpia cache: `php artisan route:clear`

---

## 📞 Soporte

Si tienes problemas:
1. Revisa los logs en `storage/logs/laravel.log`
2. Verifica la configuración en el panel de "Configuración"
3. Prueba con una consulta simple: "Hola"

---

## 🎉 ¡Listo para Usar!

El sistema está completamente funcional. Solo necesitas:
1. ✅ Obtener tu API key de Groq (gratis)
2. ✅ Agregarla al archivo `.env`
3. ✅ ¡Empezar a generar reportes inteligentes!

---

**Fecha de instalación:** 6 de noviembre de 2025
**Modelo AI:** Llama 3.3 70B (Groq)
**Estado:** ✅ Completamente funcional
