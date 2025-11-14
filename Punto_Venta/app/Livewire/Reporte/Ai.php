<?php

namespace App\Livewire\Reporte;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteAIExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * NOTE: Logging removed as requested by user. This component exposes
 * - `$historial` limited to last 5 entries that have `script`
 * - `$topConsultas` with most frequent queries
 * - `$isAdmin` boolean to indicate admin role (rol_id == 1)
 */

class Ai extends Component
{
    public $prompt = '';
    public $respuesta = '';
    public $cargando = false;
    public $error = '';
    public $historial = [];
    public $datosTabla = null;
    public $consultaSQL = null;
    public $topConsultas = [];
    public $isAdmin = false;
    public $paginaActual = 1;
    public $filasPerPagina = 10;

    public function mount()
    {
        // Cargar historial de consultas recientes
        $this->cargarHistorial();

        // Determinar si el usuario es admin (asumimos rol_id==1 es admin)
        $this->isAdmin = Auth::check() && (isset(Auth::user()->rol_id) ? Auth::user()->rol_id == 1 : false);
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
        $this->paginaActual = 1;

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

                try {
                    // Llamar a la API de Groq; si hubo un error previo se lo indicamos para que repare
                    $respuestaAI = $this->llamarGroqAPI($this->prompt, $contexto, $intentos, $ultimoError);

                    // Intentar extraer y ejecutar SQL
                    $ejecutado = $this->extraerYEjecutarSQL($respuestaAI, $ultimoError);

                    if ($ejecutado === true) {
                        $exito = true;
                        Log::info('Reporte AI generado exitosamente', [
                            'usuario_id' => Auth::id(),
                            'prompt' => $this->prompt,
                            'intentos' => $intentos,
                            'registros' => is_array($this->datosTabla) ? count($this->datosTabla) : 0
                        ]);
                    } else {
                        // $ejecutado puede contener el mensaje de error producido por la ejecución
                        $ultimoError = is_string($ejecutado) ? $ejecutado : 'Error desconocido al ejecutar SQL';

                        Log::warning('Error en intento de generar reporte', [
                            'usuario_id' => Auth::id(),
                            'prompt' => $this->prompt,
                            'intento' => $intentos,
                            'error' => $ultimoError,
                            'sql' => $this->consultaSQL
                        ]);

                        // si aún quedan intentos, ampliamos el contexto para que la IA lo corrija
                        if ($intentos < $maxIntentos) {
                            $contexto .= "\n\nNOTA: La consulta SQL propuesta falló con el siguiente error: {$ultimoError}. Devuelve únicamente una consulta SELECT corregida y válida usando los nombres exactos de las tablas del contexto.";
                        }
                    }
                } catch (\Exception $e) {
                    $ultimoError = $e->getMessage();

                    Log::error('Excepción durante intento de generar reporte', [
                        'usuario_id' => Auth::id(),
                        'prompt' => $this->prompt,
                        'intento' => $intentos,
                        'error' => $e->getMessage(),
                        'archivo' => $e->getFile(),
                        'linea' => $e->getLine()
                    ]);

                    if ($intentos >= $maxIntentos) {
                        throw $e;
                    }
                }
            }

            if (!$exito) {
                // No mostramos detalles técnicos al usuario, sólo un mensaje genérico
                $this->error = 'No se pudo generar el reporte. Intenta reformular tu consulta.';

                Log::error('Falló generación de reporte después de todos los intentos', [
                    'usuario_id' => Auth::id(),
                    'prompt' => $this->prompt,
                    'total_intentos' => $intentos,
                    'ultimo_error' => $ultimoError
                ]);
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
            $this->error = 'Error al generar el reporte. Por favor intenta con otra consulta.';

            Log::critical('Error crítico en generación de reporte', [
                'usuario_id' => Auth::id(),
                'usuario_nombre' => Auth::check() ? Auth::user()->name : 'Desconocido',
                'prompt' => $this->prompt,
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
                'traza' => $e->getTraceAsString()
            ]);
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
            // Agregar delay entre reintentos para respetar límite de tasa
            sleep(3);
        }

        $response = $client->post('https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'qwen/qwen3-32b', // Modelo liviano y optimizado
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
        $contexto = "CONTEXTO DEL SISTEMA:\n\n";

        try {
            // Estadísticas generales
            $totalProductos = DB::table('producto')->count();
            $totalFacturas = DB::table('factura')->count();
            $totalClientes = DB::table('cliente')->count();
            $totalUsuarios = DB::table('users')->count();
            $totalTiendas = DB::table('tienda')->count();
            $totalBodegas = DB::table('bodega')->count();

            $contexto .= "RESUMEN GENERAL:\n";
            $contexto .= "- Total de productos: {$totalProductos}\n";
            $contexto .= "- Total de facturas: {$totalFacturas}\n";
            $contexto .= "- Total de clientes: {$totalClientes}\n";
            $contexto .= "- Total de usuarios: {$totalUsuarios}\n";
            $contexto .= "- Total de tiendas: {$totalTiendas}\n";
            $contexto .= "- Total de bodegas: {$totalBodegas}\n\n";

            // Estadísticas de ventas
            $ventasHoy = DB::table('factura')
                ->whereDate('created_at', today())
                ->sum('total');

            $ventasSemana = DB::table('factura')
                ->whereBetween('created_at', [now()->subDays(7), now()])
                ->sum('total');

            $ventasMes = DB::table('factura')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total');

            $contexto .= "ESTADÍSTICAS DE VENTAS:\n";
            $contexto .= "- Ventas hoy: L. " . number_format($ventasHoy ?? 0, 2) . "\n";
            $contexto .= "- Ventas últimos 7 días: L. " . number_format($ventasSemana ?? 0, 2) . "\n";
            $contexto .= "- Ventas este mes: L. " . number_format($ventasMes ?? 0, 2) . "\n\n";

            // Productos más vendidos
            $topProductos = DB::table('factura_has_producto as fp')
                ->join('producto as p', 'fp.producto_id', '=', 'p.id')
                ->select('p.nombre', DB::raw('SUM(fp.cantidad) as total_vendido'))
                ->groupBy('p.id', 'p.nombre')
                ->orderByDesc('total_vendido')
                ->limit(5)
                ->get();

            if ($topProductos->isNotEmpty()) {
                $contexto .= "TOP 5 PRODUCTOS MÁS VENDIDOS:\n";
                foreach ($topProductos as $producto) {
                    $contexto .= "- {$producto->nombre}: {$producto->total_vendido} unidades\n";
                }
                $contexto .= "\n";
            }

            // Clientes frecuentes
            $clientesFrecuentes = DB::table('factura')
                ->select('nombre_cliente', DB::raw('COUNT(*) as total_compras'))
                ->whereNotNull('nombre_cliente')
                ->where('nombre_cliente', '!=', '')
                ->groupBy('nombre_cliente')
                ->orderByDesc('total_compras')
                ->limit(5)
                ->get();

            if ($clientesFrecuentes->isNotEmpty()) {
                $contexto .= "TOP 5 CLIENTES MÁS FRECUENTES:\n";
                foreach ($clientesFrecuentes as $cliente) {
                    $contexto .= "- {$cliente->nombre_cliente}: {$cliente->total_compras} compras\n";
                }
                $contexto .= "\n";
            }

            // Productos con bajo stock
            $bajoStock = DB::table('recibido_bodega as rb')
                ->join('producto as p', 'rb.producto_id', '=', 'p.id')
                ->select('p.nombre', DB::raw('SUM(rb.cantidad_disponible) as total_stock'))
                ->where('rb.estado_id', 1)
                ->groupBy('p.id', 'p.nombre')
                ->havingRaw('total_stock <= 10')
                ->limit(5)
                ->get();

            if ($bajoStock->isNotEmpty()) {
                $contexto .= "PRODUCTOS CON BAJO STOCK (10 o menos):\n";
                foreach ($bajoStock as $producto) {
                    $contexto .= "- {$producto->nombre}: {$producto->total_stock} unidades\n";
                }
                $contexto .= "\n";
            }

            // Conversiones de unidades recientes
            $conversionesRecientes = DB::table('cambio_unidades as cu')
                ->join('recibido_bodega as rb_orig', 'cu.recibido_bodega_id_original', '=', 'rb_orig.id')
                ->join('recibido_bodega as rb_nuevo', 'cu.recibido_bodega_id_cambio', '=', 'rb_nuevo.id')
                ->join('producto as p', 'rb_orig.producto_id', '=', 'p.id')
                ->join('users as u', 'cu.users_id', '=', 'u.id')
                ->select(
                    'p.nombre as producto',
                    'cu.cantidad_rebajada',
                    'cu.cantidad_convertir',
                    'u.name as usuario',
                    'cu.created_at'
                )
                ->orderByDesc('cu.created_at')
                ->limit(5)
                ->get();

            if ($conversionesRecientes->isNotEmpty()) {
                $contexto .= "CONVERSIONES DE UNIDADES RECIENTES:\n";
                foreach ($conversionesRecientes as $conversion) {
                    $contexto .= "- {$conversion->producto}: {$conversion->cantidad_rebajada} → {$conversion->cantidad_convertir} unidades (por {$conversion->usuario})\n";
                }
                $contexto .= "\n";
            }

            // Estadísticas de tiendas
            $tiendas = DB::table('tienda')
                ->where('estado_id', 1)
                ->limit(3)
                ->get();

            if ($tiendas->isNotEmpty()) {
                $contexto .= "TIENDAS ACTIVAS:\n";
                foreach ($tiendas as $tienda) {
                    $contexto .= "- {$tienda->denominacion_social}\n";
                }
                $contexto .= "\n";
            }

            // Usuarios activos
            $usuariosActivos = DB::table('users')
                ->where('estado_id', 1)
                ->count();

            $contexto .= "USUARIOS ACTIVOS: {$usuariosActivos}\n\n";

        } catch (\Exception $e) {
            $contexto .= "\nNOTA: Hubo un error al obtener algunas estadísticas. Usando esquema completo para asegurar respuestas.\n\n";
        }

        // Agregar esquema completo
        $contexto .= $this->obtenerEsquemaTablas();

        return $contexto;
    }

    private function obtenerEsquemaTablas()
    {
        $esquema = "ESQUEMA COMPLETO DE BASE DE DATOS:\n\n";

        $esquema .= "TABLA 1: users\n";
        $esquema .= "Columnas: id (BIGINT), name, email, password, email_verified_at, two_factor_secret, two_factor_recovery_codes, remember_token, current_team_id, profile_photo_path, created_user, update_user, roles_id, estado_id, tienda_id, created_at, updated_at\n";
        $esquema .= "Relaciones: roles_id -> roles.id, estado_id -> estado.id, tienda_id -> tienda.id\n\n";

        $esquema .= "TABLA 2: producto\n";
        $esquema .= "Columnas: id, nombre, descripcion, isv, precio_base, ultimo_costo_compra, costo_promedio, codigo_barra, codigo_estatal, estado_id, subcategoria_id, marca_id, unidad_compra, unidad_medida_compra_id, precio1, precio2, precio3, precio4, users_id, created_at, updated_at\n";
        $esquema .= "Relaciones: estado_id -> estado.id, subcategoria_id -> subcategoria.id, marca_id -> marca.id, unidad_medida_compra_id -> unidad_medida.id, users_id -> users.id\n\n";

        $esquema .= "TABLA 3: factura\n";
        $esquema .= "Columnas: id, numero_factura, cai, numero_secuencia_cai, nombre_cliente, rtn, sub_total, sub_total_grabado, sub_total_exento, isv, total, credito, dias_credito, fecha_emision, fecha_vencimiento, cai_id, tipo_facturacion_id, comentario, porc_descuento, monto_descuento, precio_dolar, users_id, estado_factura_id, created_at, updated_at\n";
        $esquema .= "Relaciones: cai_id -> cai.id, tipo_facturacion_id -> tipo_facturacion.id, users_id -> users.id, estado_factura_id -> estado_factura.id\n\n";

        $esquema .= "TABLA 4: factura_has_producto\n";
        $esquema .= "Columnas: factura_id, producto_id, seccion_id, unidad_medida_id, factura_has_productocol, indice, numero_unidades_resta_inventario, unidades_nota_credito_resta_inventario, resta_inventario_total, precio_unidad, tipo_precio, cantidad, subtotal, isv, total, idPrecioSeleccionado, precio_seleccionado\n";
        $esquema .= "Relaciones: factura_id -> factura.id, producto_id -> producto.id, seccion_id -> seccion.id, unidad_medida_id -> unidad_medida.id\n\n";

        $esquema .= "TABLA 5: cliente\n";
        $esquema .= "Columnas: id, nombre, correo, direccion_id, estado_id, identidad, rtn, tipo_persona_id, tipo_cliente_id, users_id, created_at, updated_at\n";
        $esquema .= "Relaciones: direccion_id -> direccion.id, estado_id -> estado.id, tipo_persona_id -> tipo_persona.id, tipo_cliente_id -> tipo_cliente.id, users_id -> users.id\n\n";

        $esquema .= "TABLA 6: recibido_bodega\n";
        $esquema .= "Columnas: id, producto_id, seccion_id, cantidad_compra_lote, cantidad_inicial_seccion, cantidad_disponible, fecha_recibido, fecha_expiracion, comentario, unidades_compra, unidad_compra_id, users_registro_id, estado_id, created_at, updated_at\n";
        $esquema .= "Relaciones: producto_id -> producto.id, seccion_id -> seccion.id, unidad_compra_id -> unidad_medida.id, users_registro_id -> users.id, estado_id -> estado.id\n\n";

        $esquema .= "TABLA 7: bodega\n";
        $esquema .= "Columnas: id, nombre, direccion_id, estado_id, tienda_id, created_at, updated_at\n";
        $esquema .= "Relaciones: direccion_id -> direccion.id, estado_id -> estado.id, tienda_id -> tienda.id\n\n";

        $esquema .= "TABLA 8: segmento\n";
        $esquema .= "Columnas: id, descripcion, bodega_id, created_at, updated_at\n";
        $esquema .= "Relaciones: bodega_id -> bodega.id\n\n";

        $esquema .= "TABLA 9: seccion\n";
        $esquema .= "Columnas: id, descripcion, numeracion, estado_id, segmento_id, created_at, updated_at\n";
        $esquema .= "Relaciones: estado_id -> estado.id, segmento_id -> segmento.id\n\n";

        $esquema .= "TABLA 10: marca\n";
        $esquema .= "Columnas: id, nombre, created_at, updated_at\n\n";

        $esquema .= "TABLA 11: categoría\n";
        $esquema .= "Columnas: id, nombre, created_at, updated_at\n\n";

        $esquema .= "TABLA 12: subcategoria\n";
        $esquema .= "Columnas: id, nombre, categoría_id, created_at, updated_at\n";
        $esquema .= "Relaciones: categoría_id -> categoría.id\n\n";

        $esquema .= "TABLA 13: unidad_medida\n";
        $esquema .= "Columnas: id, unidad, nombre, simbolo, created_at, updated_at\n\n";

        $esquema .= "TABLA 14: tienda\n";
        $esquema .= "Columnas: id, denominacion_social, descripcion, telefono, celular, correo, tipo_tienda_id, estado_id, users_creador_id, numero_sucursal, identificador_legal, direccion_sucursal_id, created_at, updated_at\n";
        $esquema .= "Relaciones: tipo_tienda_id -> tipo_tienda.id, estado_id -> estado.id, users_creador_id -> users.id, direccion_sucursal_id -> direccion.id\n\n";

        $esquema .= "TABLA 15: tipo_tienda\n";
        $esquema .= "Columnas: id, nombre, users_id, created_at, updated_at\n";
        $esquema .= "Relaciones: users_id -> users.id\n\n";

        $esquema .= "TABLA 16: direccion\n";
        $esquema .= "Columnas: id, domicilio_tributario, colonia, calle_blv, sector_zona, bloque, tipo_direccion_id, municipio_id, estado_id, latitud, longitud, created_at, updated_at\n";
        $esquema .= "Relaciones: tipo_direccion_id -> tipo_direccion.id, municipio_id -> municipio.id, estado_id -> estado.id\n\n";

        $esquema .= "TABLA 17: tipo_direccion\n";
        $esquema .= "Columnas: id, nombre, created_at, updated_at\n\n";

        $esquema .= "TABLA 18: municipio\n";
        $esquema .= "Columnas: id, nombre, departamento_id, users_registro_id, created_at, updated_at\n";
        $esquema .= "Relaciones: departamento_id -> departamento.id, users_registro_id -> users.id\n\n";

        $esquema .= "TABLA 19: departamento\n";
        $esquema .= "Columnas: id, nombre, user_registro_id, created_at, updated_at\n";
        $esquema .= "Relaciones: user_registro_id -> users.id\n\n";

        $esquema .= "TABLA 20: tipo_persona\n";
        $esquema .= "Columnas: id, nombre, created_at, updated_at\n\n";

        $esquema .= "TABLA 21: tipo_cliente\n";
        $esquema .= "Columnas: id, nombre, created_at, updated_at\n\n";

        $esquema .= "TABLA 22: tipo_facturacion\n";
        $esquema .= "Columnas: id, nombre, created_at, updated_at\n\n";

        $esquema .= "TABLA 23: estado_factura\n";
        $esquema .= "Columnas: id, nombre, created_at, updated_at\n\n";

        $esquema .= "TABLA 24: cai\n";
        $esquema .= "Columnas: id, cai, fecha_limite_emision, fecha_solicitud, punto_emision, tipo_documento_fiscal_id, cantidad_solicitada, cantidad_otorgada, rango_inicio, rango_final, tienda_id, users_registro_id, estado_id, created_at, updated_at\n";
        $esquema .= "Relaciones: tipo_documento_fiscal_id -> tipo_documento_fiscal.id, tienda_id -> tienda.id, users_registro_id -> users.id, estado_id -> estado.id\n\n";

        $esquema .= "TABLA 25: tipo_documento_fiscal\n";
        $esquema .= "Columnas: id, nombre, users_registro_id, estado_id, created_at, updated_at\n";
        $esquema .= "Relaciones: users_registro_id -> users.id, estado_id -> estado.id\n\n";

        $esquema .= "TABLA 26: gestion_cai\n";
        $esquema .= "Columnas: id, numero_actual, numero_base, serie, cantidad_no_utilizada, cai_id, estado_id, created_at, updated_at\n";
        $esquema .= "Relaciones: cai_id -> cai.id, estado_id -> estado.id\n\n";

        $esquema .= "TABLA 27: estado\n";
        $esquema .= "Columnas: id, descripcion, created_at, updated_at\n\n";

        $esquema .= "TABLA 28: roles\n";
        $esquema .= "Columnas: id, txt_nombre, estado, updated_at, updated_user, created_at, created_user\n\n";

        $esquema .= "TABLA 29: rol_permiso\n";
        $esquema .= "Columnas: id, rol_id, estado, updated_at, updated_user, created_at, created_user, menu_id\n";
        $esquema .= "Relaciones: rol_id -> roles.id, menu_id -> menu.id\n\n";

        $esquema .= "TABLA 30: menu\n";
        $esquema .= "Columnas: id, txt_comentario, parent_id, route, orden, icon, estado_id, updated_at, created_at\n";
        $esquema .= "Relaciones: parent_id -> menu_grupo.id, estado_id -> estado.id\n\n";

        $esquema .= "TABLA 31: menu_grupo\n";
        $esquema .= "Columnas: id, nombre, icon, created_at, updated_at\n\n";

        $esquema .= "TABLA 32: user_detalle\n";
        $esquema .= "Columnas: id, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, direccion, telefono, created_user, update_user, fecha_nacimiento, genero, identidad, users_id, estado_id, created_at, updated_at\n";
        $esquema .= "Relaciones: users_id -> users.id, estado_id -> estado.id\n\n";

        $esquema .= "TABLA 33: ai_consultas\n";
        $esquema .= "Columnas: id, usuario_id, pregunta, respuesta, script, created_at, updated_at\n";
        $esquema .= "Relaciones: usuario_id -> users.id\n\n";

        $esquema .= "TABLA 34: cambio_unidades\n";
        $esquema .= "Columnas: id, cantidad_rebajada, cantidad_convertir, recibido_bodega_id_original, recibido_bodega_id_cambio, users_id, created_at, update_at\n";
        $esquema .= "Relaciones: recibido_bodega_id_original -> recibido_bodega.id, recibido_bodega_id_cambio -> recibido_bodega.id, users_id -> users.id\n";
        $esquema .= "DESCRIPCIÓN: Registra las conversiones de unidades de medida de productos. Cuando se convierte un producto de una unidad a otra (ej: cajas a unidades)\n\n";

        $esquema .= "TABLA 35: bitacora\n";
        $esquema .= "Columnas: id, users_id, modulo, accion, descripcion, idReferencia, tablaReferencia, datosAnteriores, datosNuevos, ip_Equipo, created_at\n";
        $esquema .= "Relaciones: users_id -> users.id\n";
        $esquema .= "DESCRIPCIÓN: Tabla de auditoría que registra todas las acciones importantes del sistema (inserciones, actualizaciones, cambios de unidades, etc.)\n\n";

        $esquema .= "EJEMPLOS DE CONSULTAS ÚTILES:\n";
        $esquema .= "- Ventas por fecha: SELECT DATE(f.fecha_emision) as fecha, SUM(f.total) as total FROM factura f WHERE f.estado_factura_id = 1 GROUP BY DATE(f.fecha_emision)\n";
        $esquema .= "- Clientes frecuentes: SELECT f.nombre_cliente, COUNT(*) as compras FROM factura f WHERE f.nombre_cliente IS NOT NULL GROUP BY f.nombre_cliente ORDER BY compras DESC\n";
        $esquema .= "- Productos más vendidos: SELECT p.nombre, SUM(fp.cantidad) as total FROM factura_has_producto fp JOIN producto p ON fp.producto_id = p.id GROUP BY p.id ORDER BY total DESC\n";
        $esquema .= "- Stock bajo: SELECT p.nombre, rb.cantidad_disponible FROM recibido_bodega rb JOIN producto p ON rb.producto_id = p.id WHERE rb.cantidad_disponible <= 10 AND rb.estado_id = 1\n";
        $esquema .= "- Historial de precios: SELECT p.nombre, p.precio_base, p.precio1, p.precio2, p.precio3, p.precio4 FROM producto p\n";
        $esquema .= "- Conversiones de unidades: SELECT p.nombre, cu.cantidad_rebajada as cantidad_original, cu.cantidad_convertir as cantidad_nueva, u.name as usuario, cu.created_at FROM cambio_unidades cu JOIN recibido_bodega rb ON cu.recibido_bodega_id_original = rb.id JOIN producto p ON rb.producto_id = p.id JOIN users u ON cu.users_id = u.id ORDER BY cu.created_at DESC\n";
        $esquema .= "- Historial de cambios: SELECT b.accion, b.tablaReferencia, b.descripcion, u.name as usuario, b.created_at FROM bitacora b JOIN users u ON b.users_id = u.id ORDER BY b.created_at DESC\n";

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
        // Mostrar solo las últimas 5 consultas que incluyen un script SQL (script IS NOT NULL y no vacío)
        $this->historial = DB::table('ai_consultas')
            ->select('id', 'usuario_id', 'pregunta', 'respuesta', 'script', 'created_at', 'updated_at')
            ->where('usuario_id', Auth::check() ? Auth::id() : 1)
            ->whereNotNull('script')
            ->where('script', '<>', '')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->toArray();

        // Cargar las consultas más frecuentes para mostrar en un recuadro desplegable
        $this->topConsultas = DB::table('ai_consultas')
            ->select('pregunta', DB::raw('COUNT(*) as total'))
            ->whereNotNull('script')
            ->where('script', '<>', '')
            ->groupBy('pregunta')
            ->orderByDesc('total')
            ->limit(5)
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

                } catch (\Throwable $_tzEx) {
                    // No bloquear la ejecución si falla el manejo del timezone
                    $sqlExec = $sql; // fallback
                }

                // Ejecutar la consulta SQL (posible con reemplazos aplicados)
                $resultados = DB::select($sqlExec);



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
                    // Ignorar errores al ejecutar SQL del historial
                }
            }
        }
    }

    public function render()
    {
        return view('livewire.reporte.ai');
    }
}
