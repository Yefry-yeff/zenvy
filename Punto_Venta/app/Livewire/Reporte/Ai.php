<?php

namespace App\Livewire\Reporte;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteAIExport;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Ai extends Component
{
    public $prompt = '';
    public $respuesta = '';
    public $cargando = false;
    public $error = '';
    public $historial = [];
    public $datosTabla = null;
    public $consultaSQL = null;

    public function mount()
    {
        // Cargar historial de consultas recientes
        $this->cargarHistorial();
    }

    public function generarReporte()
    {
        $this->validate([
            'prompt' => 'required|min:10|max:1000'
        ], [
            'prompt.required' => 'Debe ingresar una consulta',
            'prompt.min' => 'La consulta debe tener al menos 10 caracteres',
            'prompt.max' => 'La consulta no puede exceder 1000 caracteres'
        ]);

        $this->cargando = true;
        $this->error = '';
        $this->respuesta = '';
        $this->datosTabla = null;
        $this->consultaSQL = null;

        try {
            // Obtener contexto de la base de datos
            $contexto = $this->obtenerContextoBaseDatos();

            // Intentar hasta 3 veces si hay error en SQL, solicitando reparación al modelo
            $intentos = 0;
            $maxIntentos = 3;
            $exito = false;
            $ultimoError = null;

            while ($intentos < $maxIntentos && !$exito) {
                $intentos++;

                // Llamar a la API de Groq; si hubo un error previo se lo indicamos para que repare
                $respuestaAI = $this->llamarGroqAPI($this->prompt, $contexto, $intentos, $ultimoError);

                // Intentar extraer y ejecutar SQL
                $ejecutado = $this->extraerYEjecutarSQL($respuestaAI, $ultimoError);

                if ($ejecutado === true) {
                    $exito = true;
                } else {
                    // $ejecutado puede contener el mensaje de error producido por la ejecución
                    $ultimoError = is_string($ejecutado) ? $ejecutado : 'Error desconocido al ejecutar SQL';

                    // si aún quedan intentos, ampliamos el contexto para que la IA lo corrija
                    if ($intentos < $maxIntentos) {
                        $contexto .= "\n\nNOTA: La consulta SQL propuesta falló con el siguiente error: {$ultimoError}. Devuelve únicamente una consulta SELECT corregida y válida usando los nombres exactos de las tablas del contexto.";
                    }
                }
            }

            if (!$exito) {
                // No mostramos detalles técnicos al usuario, sólo un mensaje genérico
                $this->error = 'No se pudo generar el reporte. Intenta reformular tu consulta.';
                Log::warning('AI: no se pudo generar reporte tras varios intentos', ['prompt' => $this->prompt, 'ultimo_error' => $ultimoError]);
            } else {
                // Guardar en historial solo si fue exitoso
                $cantidadRegistros = is_array($this->datosTabla) ? count($this->datosTabla) : 0;
                $this->guardarEnHistorial(
                    $this->prompt,
                    "Tabla con " . $cantidadRegistros . " registros",
                    $this->consultaSQL
                );
                $this->cargarHistorial();
            }

        } catch (\Exception $e) {
            Log::error('Error al generar reporte con AI: ' . $e->getMessage());
            $this->error = 'Error al generar el reporte. Por favor intenta con otra consulta.';
        } finally {
            $this->cargando = false;
        }
    }

    private function llamarGroqAPI($prompt, $contexto, $intentos = 1, $ultimoError = null)
    {
        $apiKey = env('GROQ_API_KEY', '');

        if (empty($apiKey)) {
            throw new \Exception('No se ha configurado GROQ_API_KEY en el archivo .env. Obtén tu API key gratuita en https://console.groq.com');
        }

        $client = new Client();

        $systemPrompt = "Eres un asistente experto en análisis de datos de un sistema de punto de venta llamado Zenvy.
        Tu tarea es generar reportes claros y útiles basados en los datos disponibles.

        CONTEXTO DE LA BASE DE DATOS:
        {$contexto}

        INSTRUCCIONES IMPORTANTES:
        - Proporciona respuestas en español
        - NO INCLUYAS explicaciones técnicas ni detalles del proceso
        - NO MENCIONES que generaste una consulta SQL
        - NO EXPLIQUES cómo obtuviste los datos
        - SOLO presenta los resultados de forma clara y directa
        - Si el usuario solicita un reporte con datos, SIEMPRE incluye una consulta SQL válida entre bloques de código SQL (el usuario no verá esto, solo los resultados)
        - Formato del SQL: ```sql\nSELECT ...\n```
        - Las consultas SQL deben ser optimizadas y usar los nombres exactos de las tablas del contexto
        - Para reportes de ventas usa la tabla 'factura' y 'factura_has_producto'
        - Para productos usa 'producto'
        - Para clientes usa 'cliente'
        - SOLO genera consultas SELECT, nunca INSERT, UPDATE, DELETE o DROP

        FORMATO DE RESPUESTA:
        Presenta solo un breve título o resumen (1-2 líneas) y deja que la tabla hable por sí misma.
        Ejemplo: 'Aquí están las ventas del día de hoy:' o 'Estos son los productos más vendidos:'";

        $userPrompt = $prompt;
        if ($intentos > 1 && $ultimoError) {
            $userPrompt .= "\n\n[CORRECCIÓN REQUERIDA] La consulta anterior falló con error: {$ultimoError}. Por favor genera una consulta SQL corregida.";
        }

        $response = $client->post('https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'llama-3.3-70b-versatile', // Modelo gratuito
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 2048,
            ],
            'timeout' => 30,
        ]);

        $body = json_decode($response->getBody(), true);

        if (!isset($body['choices'][0]['message']['content'])) {
            throw new \Exception('Respuesta inválida de la API');
        }

        return $body['choices'][0]['message']['content'];
    }

    private function obtenerContextoBaseDatos()
    {
        $contexto = "INFORMACIÓN DEL SISTEMA:\n\n";

        try {
            // Estadísticas generales
            $totalProductos = DB::table('producto')->count();
            $totalFacturas = DB::table('factura')->count();
            $totalClientes = DB::table('cliente')->count();

            $contexto .= "- Total de productos: {$totalProductos}\n";
            $contexto .= "- Total de facturas: {$totalFacturas}\n";
            $contexto .= "- Total de clientes: {$totalClientes}\n\n";

            // Ventas recientes
            $ventasHoy = DB::table('factura')
                ->whereDate('created_at', today())
                ->sum('total');

            $ventasMes = DB::table('factura')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total');

            $contexto .= "VENTAS:\n";
            $contexto .= "- Ventas hoy: L. " . number_format($ventasHoy, 2) . "\n";
            $contexto .= "- Ventas este mes: L. " . number_format($ventasMes, 2) . "\n\n";

            // Productos más vendidos
            $topProductos = DB::table('factura_has_producto as fp')
                ->join('producto as p', 'fp.producto_id', '=', 'p.id')
                ->select('p.nombre', DB::raw('SUM(fp.cantidad) as total_vendido'))
                ->groupBy('p.id', 'p.nombre')
                ->orderByDesc('total_vendido')
                ->limit(5)
                ->get();

            $contexto .= "PRODUCTOS MÁS VENDIDOS:\n";
            foreach ($topProductos as $producto) {
                $contexto .= "- {$producto->nombre}: {$producto->total_vendido} unidades\n";
            }

            // Clientes frecuentes (basado en nombre_cliente de factura)
            $clientesFrecuentes = DB::table('factura')
                ->select('nombre_cliente', DB::raw('COUNT(*) as total_compras'))
                ->whereNotNull('nombre_cliente')
                ->where('nombre_cliente', '!=', '')
                ->groupBy('nombre_cliente')
                ->orderByDesc('total_compras')
                ->limit(5)
                ->get();

            if ($clientesFrecuentes->isNotEmpty()) {
                $contexto .= "\n\nCLIENTES MÁS FRECUENTES:\n";
                foreach ($clientesFrecuentes as $cliente) {
                    $contexto .= "- {$cliente->nombre_cliente}: {$cliente->total_compras} compras\n";
                }
            }

            // Esquema de tablas principales
            $contexto .= "\n\nESQUEMA DE BASE DE DATOS:\n\n";
            $contexto .= $this->obtenerEsquemaTablas();

        } catch (\Exception $e) {
            Log::error('Error al obtener contexto de BD: ' . $e->getMessage());
            $contexto .= "\n\nNOTA: Hubo un error al obtener estadísticas. Usando esquema básico.\n";
            $contexto .= $this->obtenerEsquemaTablas();
        }

        return $contexto;
    }

    private function obtenerEsquemaTablas()
    {
        $esquema = "TABLAS PRINCIPALES Y SUS COLUMNAS:\n\n";

        $esquema .= "1. factura: id, cai_id, tipo_facturacion_id, numero_factura, nombre_cliente, rtn, sub_total, sub_total_grabado, sub_total_exento, isv, total, credito, dias_credito, fecha_emision, fecha_vencimiento, porc_descuento, monto_descuento, estado_factura_id, users_id, descuentos_id, created_at, updated_at\n";
        $esquema .= "   NOTA: La tabla factura NO tiene cliente_id. Usa nombre_cliente y rtn directamente.\n\n";
        $esquema .= "2. factura_has_producto: id, factura_id, producto_id, cantidad, precio_unitario, subtotal, descuento, total, created_at, updated_at\n";
        $esquema .= "3. factura_has_pago: id, tipo_pago_id, factura_id, total_factura, pago_recibido, cambio\n";
        $esquema .= "4. producto: id, codigo_barra, nombre, descripcion, precio_compra, precio_venta, existencia, marca_id, categoria_id, segmento_id, seccion_id, created_at, updated_at\n";
        $esquema .= "5. cliente: id, nombre, apellido, identidad, telefono, email, direccion, rtn, created_at, updated_at\n";
        $esquema .= "   NOTA: Para relacionar clientes con facturas, usa factura.nombre_cliente y factura.rtn\n\n";
        $esquema .= "6. compra: id, proveedor_id, usuario_id, numero_compra, subtotal, isv, total, estado, fecha_compra, created_at, updated_at\n";
        $esquema .= "7. users: id, name, email, rol_id, estado, created_at, updated_at\n";
        $esquema .= "8. marca: id, nombre, descripcion, created_at, updated_at\n";
        $esquema .= "9. categoria: id, nombre, descripcion, created_at, updated_at\n";
        $esquema .= "10. segmento: id, nombre, descripcion, created_at, updated_at\n";
        $esquema .= "11. seccion: id, nombre, descripcion, created_at, updated_at\n";
        $esquema .= "12. tipo_pago: id, nombre, descripcion, created_at, updated_at\n";
        $esquema .= "13. bodega: id, nombre, ubicacion, created_at, updated_at\n";
        $esquema .= "14. descuentos: id, nombre, tipo, valor, activo, created_at, updated_at\n\n";

        $esquema .= "RELACIONES IMPORTANTES:\n";
        $esquema .= "- factura.users_id -> users.id (usuario que creó la factura)\n";
        $esquema .= "- factura.nombre_cliente y factura.rtn -> cliente.nombre y cliente.rtn (relación indirecta por texto)\n";
        $esquema .= "- factura.tipo_facturacion_id -> tipo_facturacion.id\n";
        $esquema .= "- factura.estado_factura_id -> estado_factura.id\n";
        $esquema .= "- factura.descuentos_id -> descuentos.id\n";
        $esquema .= "- factura_has_producto.factura_id -> factura.id\n";
        $esquema .= "- factura_has_producto.producto_id -> producto.id\n";
        $esquema .= "- factura_has_pago.factura_id -> factura.id\n";
        $esquema .= "- factura_has_pago.tipo_pago_id -> tipo_pago.id\n";
        $esquema .= "- producto.marca_id -> marca.id\n";
        $esquema .= "- producto.categoria_id -> categoria.id\n";
        $esquema .= "- producto.segmento_id -> segmento.id\n";
        $esquema .= "- producto.seccion_id -> seccion.id\n\n";

        $esquema .= "EJEMPLOS DE CONSULTAS CORRECTAS:\n";
        $esquema .= "- Clientes con más compras: SELECT nombre_cliente, COUNT(*) as total FROM factura WHERE nombre_cliente IS NOT NULL AND nombre_cliente != '' GROUP BY nombre_cliente\n";
        $esquema .= "- Ventas por usuario: SELECT u.name, COUNT(f.id) as total FROM factura f JOIN users u ON f.users_id = u.id GROUP BY u.id\n";
        $esquema .= "- Productos más vendidos: SELECT p.nombre, SUM(fp.cantidad) as total FROM factura_has_producto fp JOIN producto p ON fp.producto_id = p.id GROUP BY p.id\n";

        return $esquema;
    }

    private function guardarEnHistorial($pregunta, $respuesta, $script = null)
    {
        DB::table('ai_consultas')->insert([
            'usuario_id' => Auth::check() ? Auth::id() : 1,
            'pregunta' => $pregunta,
            'respuesta' => $respuesta,
            'script' => $script,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    private function cargarHistorial()
    {
        $this->historial = DB::table('ai_consultas')
            ->select('id', 'usuario_id', 'pregunta', 'respuesta', 'script', 'created_at', 'updated_at')
            ->where('usuario_id', Auth::check() ? Auth::id() : 1)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function extraerYEjecutarSQL($respuesta, $ultimoError = null)
    {
        // Buscar bloques de código SQL
        preg_match('/```sql\s*(.*?)\s*```/is', $respuesta, $matches);

        if (isset($matches[1])) {
            $sql = trim($matches[1]);
            $this->consultaSQL = $sql;

            try {
                // Validar que sea una consulta SELECT
                if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
                    return 'Solo se permiten consultas SELECT por seguridad';
                }

                // Log: registrar la consulta que se va a ejecutar y contexto básico
                try {
                    Log::info('AI: ejecutando consulta SQL', [
                        'user_id' => Auth::check() ? Auth::id() : null,
                        'prompt' => $this->prompt,
                        'sql' => $sql,
                        'respuesta_ai_snippet' => mb_substr($respuesta, 0, 1000),
                    ]);
                } catch (\Throwable $_logEx) {
                    // No bloquear la ejecución si falla el logging
                }

                // Preparar la consulta para ejecutar respetando el timezone de la app
                $sqlExec = $sql;
                try {
                    $appTz = config('app.timezone') ?: 'UTC';
                    $appNow = Carbon::now()->setTimezone($appTz)->format('Y-m-d H:i:s');
                    $appDate = Carbon::now()->setTimezone($appTz)->format('Y-m-d');

                    // Reemplazos comunes que generan discrepancias por timezone
                    $replacements = [
                        '/\bCURDATE\(\)/i' => "'{$appDate}'",
                        '/\bCURRENT_DATE\b/i' => "'{$appDate}'",
                        '/\bNOW\(\)/i' => "'{$appNow}'",
                        '/\bCURRENT_TIMESTAMP\b/i' => "'{$appNow}'",
                    ];

                    foreach ($replacements as $pattern => $replace) {
                        $sqlExec = preg_replace($pattern, $replace, $sqlExec);
                    }

                    // Log que indica qué reemplazos se realizaron
                    Log::info('AI: reemplazo timezone en SQL', [
                        'original_sql_hash' => substr(md5($sql), 0, 10),
                        'replaced_sql_hash' => substr(md5($sqlExec), 0, 10),
                        'app_timezone' => $appTz,
                        'replacements_preview' => [
                            'CURDATE()' => $appDate,
                            'NOW()' => $appNow,
                        ],
                    ]);
                } catch (\Throwable $_tzEx) {
                    // No bloquear la ejecución si falla el manejo del timezone
                    Log::warning('AI: fallo al aplicar reemplazo de timezone: ' . $_tzEx->getMessage());
                    $sqlExec = $sql; // fallback
                }

                // Ejecutar la consulta SQL (posible con reemplazos aplicados)
                $resultados = DB::select($sqlExec);

                // Log: registrar cantidad de resultados y una muestra del primer registro
                try {
                    $count = is_array($resultados) ? count($resultados) : 0;
                    $sample = [];
                    if ($count > 0) {
                        $first = (array) $resultados[0];
                        // Mantener solo los primeros 5 campos para la muestra
                        $sample = array_slice($first, 0, 5, true);
                    }

                    Log::info('AI: resultados de la consulta', [
                        'count' => $count,
                        'sample' => $sample,
                        'sql_hash' => substr(md5($sql), 0, 10),
                    ]);
                } catch (\Throwable $_logEx) {
                    // No interrumpir la ejecución por fallos de logging
                }

                if (!empty($resultados)) {
                    // Convertir a array asociativo
                    $this->datosTabla = array_map(function($item) {
                        return (array) $item;
                    }, $resultados);

                    // Guardar la respuesta para mostrar al usuario
                    $this->respuesta = $respuesta;

                    return true; // Éxito
                } else {
                    $this->datosTabla = [];
                    $this->respuesta = $respuesta;
                    return true; // Éxito, aunque sin datos
                }
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                // Registrar error con contexto completo (cuidado con información sensible)
                try {
                    Log::error('Error al ejecutar SQL generado por AI', [
                        'exception' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'sql' => $sql,
                        'prompt' => $this->prompt,
                        'respuesta_ai_snippet' => mb_substr($respuesta, 0, 2000),
                        'user_id' => Auth::check() ? Auth::id() : null,
                    ]);
                } catch (\Throwable $_logEx) {
                    // Evitar fallos secundarios por logging
                    Log::error('Error al registrar el error original al ejecutar SQL: ' . $_logEx->getMessage());
                }

                return $errorMsg; // Devolver el mensaje de error
            }
        } else {
            // No hay SQL, solo respuesta de texto
            $this->respuesta = $respuesta;
            return true;
        }
    }

    public function descargarExcel()
    {
        if (empty($this->datosTabla)) {
            $this->error = 'No hay datos para descargar';
            return;
        }

        try {
            return Excel::download(new ReporteAIExport($this->datosTabla), 'reporte_' . date('YmdHis') . '.xlsx');
        } catch (\Exception $e) {
            Log::error('Error al descargar Excel: ' . $e->getMessage());
            $this->error = 'Error al generar el archivo Excel';
        }
    }

    public function ejecutarSQL()
    {
        if (empty($this->consultaSQL)) {
            $this->error = 'No hay consulta SQL para ejecutar';
            return;
        }

        try {
            // Log: registrar la consulta ejecutada manualmente desde la UI
            try {
                Log::info('AI: ejecutarSQL invocado', [
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'consulta' => $this->consultaSQL,
                    'prompt' => $this->prompt,
                ]);
            } catch (\Throwable $_logEx) {
                // no bloquear la ejecución por fallos de logging
            }

            $resultados = DB::select($this->consultaSQL);

            if (!empty($resultados)) {
                $this->datosTabla = array_map(function($item) {
                    return (array) $item;
                }, $resultados);

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'Consulta ejecutada: ' . count($resultados) . ' resultados'
                ]);
            } else {
                $this->datosTabla = null;
                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => 'La consulta no devolvió resultados'
                ]);
            }
        } catch (\Exception $e) {
            // Log error con contexto
            try {
                Log::error('Error al ejecutar SQL (ejecutarSQL)', [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'consulta' => $this->consultaSQL,
                    'prompt' => $this->prompt,
                    'user_id' => Auth::check() ? Auth::id() : null,
                ]);
            } catch (\Throwable $_logEx) {
                Log::error('Error al registrar el error en ejecutarSQL: ' . $_logEx->getMessage());
            }

            $this->error = 'Error al ejecutar la consulta: ' . $e->getMessage();
        }
    }

    public function limpiar()
    {
        $this->prompt = '';
        $this->respuesta = '';
        $this->error = '';
    }

    public function usarHistorial($id)
    {
        $consulta = DB::table('ai_consultas')
            ->select('pregunta', 'respuesta', 'script')
            ->where('id', $id)
            ->first();

        if ($consulta) {
            $this->prompt = $consulta->pregunta;
            $this->respuesta = $consulta->respuesta;
            $this->consultaSQL = $consulta->script;

            // Si hay script, ejecutarlo para regenerar la tabla
            if ($this->consultaSQL) {
                try {
                    $resultados = DB::select($this->consultaSQL);
                    if (!empty($resultados)) {
                        $this->datosTabla = array_map(function($item) {
                            return (array) $item;
                        }, $resultados);
                    }
                } catch (\Exception $e) {
                    // Registrar error al ejecutar SQL desde historial
                    try {
                        Log::error('Error al ejecutar SQL del historial', [
                            'exception' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'consulta' => $this->consultaSQL,
                            'user_id' => Auth::check() ? Auth::id() : null,
                        ]);
                    } catch (\Throwable $_logEx) {
                        Log::error('Error al registrar el error del historial: ' . $_logEx->getMessage());
                    }
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.reporte.ai');
    }
}
