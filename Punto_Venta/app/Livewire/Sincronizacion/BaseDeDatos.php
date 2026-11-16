<?php

namespace App\Livewire\Sincronizacion;

use Livewire\Component;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class BaseDeDatos extends Component
{
    public $configuraciones = [];
    public $nombreBaseDatosOrigen = 'profac_app';
    public $nombreBaseDatosDestino = 'mysql';
    public $mostrarConfiguracionTablas = false;
    
    // Configuración completa de la base de datos de origen
    public $hostOrigen = '127.0.0.1';
    public $puertoOrigen = '3306';
    public $usuarioOrigen = 'root';
    public $passwordOrigen = '';
    
    // Para el modal de edición de bases de datos
    public $mostrarModalEdicion = false;
    
    // Para el modal de edición de tablas
    public $mostrarModalEdicionTabla = false;
    public $tipoSincronizacionEditando = '';
    public $nuevaTablaOrigen = '';
    public $nuevaTablaDestino = '';
    
    // Mensajes de estado
    public $mensajeExito = '';
    public $mensajeError = '';

    public function mount()
    {
        $this->cargarConfiguraciones();
        $this->cargarConfiguracionBaseDatos();
    }

    public function cargarConfiguracionBaseDatos()
    {
        // Cargar la configuración actual del .env
        $this->hostOrigen = env('PROFAC_DB_HOST', '127.0.0.1');
        $this->puertoOrigen = env('PROFAC_DB_PORT', '3306');
        $this->nombreBaseDatosOrigen = env('PROFAC_DB_DATABASE', 'profac_app');
        $this->usuarioOrigen = env('PROFAC_DB_USERNAME', 'root');
        $this->passwordOrigen = env('PROFAC_DB_PASSWORD', '');
    }

    public function cargarConfiguraciones()
    {
        // Cargar configuraciones de los servicios de sincronización existentes
        $this->configuraciones = [
            'marcas' => [
                'nombre' => 'Sincronización de Marcas',
                'servicio' => 'SincronizacionMarcasService',
                'comando' => 'marcas:sincronizar',
                'tabla_origen' => 'marca',
                'tabla_destino' => 'marca',
                'descripcion' => 'Sincroniza marcas desde profac_app a db_zenvy',
                'activo' => true
            ],
            'categorias' => [
                'nombre' => 'Sincronización de Categorías',
                'servicio' => 'SincronizacionCategoriasService',
                'comando' => 'categorias:sincronizar',
                'tabla_origen' => 'categoria',
                'tabla_destino' => 'categoria',
                'descripcion' => 'Sincroniza categorías desde profac_app a db_zenvy',
                'activo' => true
            ],
            'subcategorias' => [
                'nombre' => 'Sincronización de Subcategorías',
                'servicio' => 'SincronizacionSubcategoriasService',
                'comando' => 'subcategorias:sincronizar',
                'tabla_origen' => 'subcategoria',
                'tabla_destino' => 'subcategoria',
                'descripcion' => 'Sincroniza subcategorías desde profac_app a db_zenvy',
                'activo' => true
            ],
            'unidades' => [
                'nombre' => 'Sincronización de Unidades de Medida',
                'servicio' => 'SincronizacionUnidadesService',
                'comando' => 'unidades:sincronizar',
                'tabla_origen' => 'unidad_medida',
                'tabla_destino' => 'unidad_medida',
                'descripcion' => 'Sincroniza unidades de medida desde profac_app a db_zenvy',
                'activo' => true
            ],
            'productos' => [
                'nombre' => 'Sincronización de Productos',
                'servicio' => 'SincronizacionProductosService',
                'comando' => 'sincronizar:productos',
                'tabla_origen' => 'producto',
                'tabla_destino' => 'producto',
                'descripcion' => 'Sincroniza productos desde Valencia automáticamente',
                'activo' => true
            ],
            'compras' => [
                'nombre' => 'Sincronización de Compras',
                'servicio' => 'SincronizacionComprasService',
                'comando' => 'sincronizar:compras',
                'tabla_origen' => 'recibido_bodega',
                'tabla_destino' => 'compra',
                'descripcion' => 'Sincroniza compras desde Valencia a Zenvy - solo nuevas compras',
                'activo' => true
            ]
        ];
    }

    public function abrirEdicionBaseDatos()
    {
        $this->mostrarModalEdicion = true;
        $this->limpiarMensajes();
    }

    public function cancelarEdicion()
    {
        $this->mostrarModalEdicion = false;
        $this->limpiarMensajes();
    }

    public function probarConexion()
    {
        try {
            // Crear configuración temporal para probar la conexión
            $configPrueba = [
                'driver' => 'mysql',
                'host' => $this->hostOrigen,
                'port' => $this->puertoOrigen,
                'database' => $this->nombreBaseDatosOrigen,
                'username' => $this->usuarioOrigen,
                'password' => $this->passwordOrigen,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'options' => [
                    \PDO::ATTR_TIMEOUT => 5, // Timeout de 5 segundos
                ]
            ];

            // Intentar conexión
            $pdo = new \PDO(
                "mysql:host={$this->hostOrigen};port={$this->puertoOrigen};dbname={$this->nombreBaseDatosOrigen}",
                $this->usuarioOrigen,
                $this->passwordOrigen,
                [\PDO::ATTR_TIMEOUT => 5]
            );

            $this->mensajeExito = 'Conexión exitosa! La configuración es válida.';
            $this->mensajeError = '';

        } catch (\Exception $e) {
            $this->mensajeError = 'Error de conexión: ' . $e->getMessage();
            $this->mensajeExito = '';
        }
    }

    public function guardarConfiguracionBaseDatos()
    {
        $this->validate([
            'nombreBaseDatosOrigen' => 'required|string|max:255',
            'nombreBaseDatosDestino' => 'required|string|max:255',
            'hostOrigen' => 'required|string|max:255',
            'puertoOrigen' => 'required|numeric',
            'usuarioOrigen' => 'required|string|max:255'
        ], [
            'nombreBaseDatosOrigen.required' => 'El nombre de la base de datos de origen es obligatorio',
            'nombreBaseDatosDestino.required' => 'El nombre de la base de datos de destino es obligatorio',
            'hostOrigen.required' => 'El host de origen es obligatorio',
            'puertoOrigen.required' => 'El puerto de origen es obligatorio',
            'puertoOrigen.numeric' => 'El puerto debe ser un número',
            'usuarioOrigen.required' => 'El usuario de origen es obligatorio'
        ]);

        try {
            // Actualizar el archivo .env
            $this->actualizarArchivoEnv();
            
            // Actualizar las configuraciones de conexión en los servicios
            $this->actualizarConfiguracionEnServicios();
            
            // Actualizar config/database.php si es necesario
            $this->actualizarConfigDatabase();
            
            $this->mostrarModalEdicion = false;
            $this->mensajeExito = 'Configuración de base de datos actualizada correctamente en .env, servicios y configuración de Laravel';
            
        } catch (\Exception $e) {
            $this->mensajeError = 'Error al actualizar la configuración: ' . $e->getMessage();
            Log::error('Error al actualizar configuración de base de datos: ' . $e->getMessage());
        }
    }

    private function actualizarConfiguracionEnServicios()
    {
        $servicios = [
            'app/Services/SincronizacionMarcasService.php',
            'app/Services/SincronizacionCategoriasService.php', 
            'app/Services/SincronizacionSubcategoriasService.php',
            'app/Services/SincronizacionUnidadesService.php',
            'app/Services/SincronizacionProductosService.php',
            'app/Services/SincronizacionComprasService.php'
        ];

        foreach ($servicios as $servicio) {
            $rutaCompleta = base_path($servicio);
            if (File::exists($rutaCompleta)) {
                $contenido = File::get($rutaCompleta);
                
                // Actualizar la conexión de Valencia en los servicios
                $contenido = preg_replace(
                    '/DB::connection\([\'"][\w_]+[\'"]\)->getPdo\(\);/',
                    'DB::connection(\'' . $this->nombreBaseDatosOrigen . '\')->getPdo();',
                    $contenido
                );
                
                // Actualizar otras referencias a la conexión de Valencia
                $contenido = preg_replace(
                    '/\$this->conexionValencia = DB::connection\([\'"][\w_]+[\'"]\);/',
                    '$this->conexionValencia = DB::connection(\'' . $this->nombreBaseDatosOrigen . '\');',
                    $contenido
                );
                
                // Actualizar la conexión de Zenvy si es necesario
                if ($this->nombreBaseDatosDestino !== 'mysql') {
                    $contenido = preg_replace(
                        '/\$this->conexionZenvy = DB::connection\(\);/',
                        '$this->conexionZenvy = DB::connection(\'' . $this->nombreBaseDatosDestino . '\');',
                        $contenido
                    );
                }

                File::put($rutaCompleta, $contenido);
                Log::info("Configuración actualizada en: {$servicio}");
            }
        }

        // Actualizar modelos externos
        $this->actualizarModelosExternos();
    }

    private function actualizarModelosExternos()
    {
        $modelos = [
            'app/Models/MarcaExterna.php',
            'app/Models/CategoriaExterna.php',
            'app/Models/SubcategoriaExterna.php',
            'app/Models/UnidadMedidaExterna.php',
            'app/Models/ProductoExterno.php',
            'app/Models/CompraExterna.php'
        ];

        foreach ($modelos as $modelo) {
            $rutaCompleta = base_path($modelo);
            if (File::exists($rutaCompleta)) {
                $contenido = File::get($rutaCompleta);
                
                // Actualizar la propiedad protected $connection
                $contenido = preg_replace(
                    '/protected \$connection = [\'"][\w_]+[\'"];/',
                    'protected $connection = \'' . $this->nombreBaseDatosOrigen . '\';',
                    $contenido
                );

                File::put($rutaCompleta, $contenido);
                Log::info("Conexión actualizada en modelo: {$modelo}");
            }
        }
    }

    private function actualizarArchivoEnv()
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            throw new \Exception('Archivo .env no encontrado');
        }

        $envContent = File::get($envPath);
        
        // Actualizar las variables de la base de datos de Valencia
        $variablesEnv = [
            'PROFAC_DB_HOST' => $this->hostOrigen,
            'PROFAC_DB_PORT' => $this->puertoOrigen,
            'PROFAC_DB_DATABASE' => $this->nombreBaseDatosOrigen,
            'PROFAC_DB_USERNAME' => $this->usuarioOrigen,
            'PROFAC_DB_PASSWORD' => $this->passwordOrigen,
        ];

        foreach ($variablesEnv as $variable => $valor) {
            // Buscar si la variable ya existe
            $patron = '/^' . preg_quote($variable, '/') . '=.*$/m';
            $nuevaLinea = $variable . '=' . $valor;
            
            if (preg_match($patron, $envContent)) {
                // Si existe, reemplazarla
                $envContent = preg_replace($patron, $nuevaLinea, $envContent);
            } else {
                // Si no existe, agregarla al final de la sección de base de datos
                $patronSeccion = '/(# Configuración para base de datos externa.*?)(\n\n)/s';
                if (preg_match($patronSeccion, $envContent)) {
                    $envContent = preg_replace(
                        $patronSeccion,
                        '$1' . "\n" . $nuevaLinea . '$2',
                        $envContent
                    );
                } else {
                    // Si no existe la sección, agregarla después de la configuración principal de DB
                    $envContent .= "\n" . $nuevaLinea;
                }
            }
        }

        File::put($envPath, $envContent);
        Log::info('Archivo .env actualizado con nueva configuración de base de datos');
    }

    private function actualizarConfigDatabase()
    {
        $configPath = base_path('config/database.php');
        
        if (!File::exists($configPath)) {
            throw new \Exception('Archivo config/database.php no encontrado');
        }

        $configContent = File::get($configPath);
        
        // Si el nombre de la base de datos cambió, necesitamos actualizar o crear la conexión
        $nombreAnterior = 'profac_app'; // Nombre por defecto anterior
        
        if ($this->nombreBaseDatosOrigen !== $nombreAnterior) {
            // Crear nueva conexión con el nuevo nombre
            $nuevaConexion = "
        // Conexión para la base de datos externa {$this->nombreBaseDatosOrigen}
        '{$this->nombreBaseDatosOrigen}' => [
            'driver' => 'mysql',
            'host' => env('PROFAC_DB_HOST', '127.0.0.1'),
            'port' => env('PROFAC_DB_PORT', '3306'),
            'database' => env('PROFAC_DB_DATABASE', '{$this->nombreBaseDatosOrigen}'),
            'username' => env('PROFAC_DB_USERNAME', 'root'),
            'password' => env('PROFAC_DB_PASSWORD', ''),
            'unix_socket' => env('PROFAC_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],";

            // Buscar y reemplazar la conexión profac_app existente
            $patronConexionAnterior = '/\/\/ Conexión para la base de datos externa.*?\s*\'profac_app\'\s*=>\s*\[.*?\],/s';
            
            if (preg_match($patronConexionAnterior, $configContent)) {
                // Reemplazar la conexión existente
                $configContent = preg_replace($patronConexionAnterior, $nuevaConexion, $configContent);
            } else {
                // Si no existe la conexión profac_app, buscar donde agregar la nueva
                $patronCierreConnections = '/(\s+],\s*\/\*)/';
                if (preg_match($patronCierreConnections, $configContent)) {
                    $configContent = preg_replace($patronCierreConnections, $nuevaConexion . "\n\n$1", $configContent);
                } else {
                    // Fallback: agregar antes del cierre del array
                    $configContent = str_replace('    ],', $nuevaConexion . "\n\n    ],", $configContent);
                }
            }

            File::put($configPath, $configContent);
            Log::info('Archivo config/database.php actualizado con nueva conexión: ' . $this->nombreBaseDatosOrigen);
        } else {
            // Si el nombre no cambió, solo asegurar que la conexión existe
            if (!str_contains($configContent, "'profac_app' =>")) {
                $nuevaConexion = "
        // Conexión para la base de datos externa profac_app
        'profac_app' => [
            'driver' => 'mysql',
            'host' => env('PROFAC_DB_HOST', '127.0.0.1'),
            'port' => env('PROFAC_DB_PORT', '3306'),
            'database' => env('PROFAC_DB_DATABASE', 'profac_app'),
            'username' => env('PROFAC_DB_USERNAME', 'root'),
            'password' => env('PROFAC_DB_PASSWORD', ''),
            'unix_socket' => env('PROFAC_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],";

                $configContent = str_replace('    ],', $nuevaConexion . "\n\n    ],", $configContent);
                File::put($configPath, $configContent);
                Log::info('Conexión profac_app agregada a config/database.php');
            }
        }

        // Limpiar cache de configuración para que los cambios tomen efecto
        try {
            Artisan::call('config:clear');
            Log::info('Cache de configuración limpiado');
        } catch (\Exception $e) {
            Log::warning('No se pudo limpiar el cache de configuración: ' . $e->getMessage());
        }
    }

    public function abrirConfiguracionTablas()
    {
        $this->mostrarConfiguracionTablas = true;
        $this->limpiarMensajes();
    }

    public function cerrarConfiguracionTablas()
    {
        $this->mostrarConfiguracionTablas = false;
    }

    public function abrirEdicionTabla($tipo)
    {
        if (isset($this->configuraciones[$tipo])) {
            $this->tipoSincronizacionEditando = $tipo;
            $this->nuevaTablaOrigen = $this->configuraciones[$tipo]['tabla_origen'];
            $this->nuevaTablaDestino = $this->configuraciones[$tipo]['tabla_destino'];
            $this->mostrarModalEdicionTabla = true;
            $this->limpiarMensajes();
        }
    }

    public function cancelarEdicionTabla()
    {
        $this->mostrarModalEdicionTabla = false;
        $this->tipoSincronizacionEditando = '';
        $this->nuevaTablaOrigen = '';
        $this->nuevaTablaDestino = '';
        $this->limpiarMensajes();
    }

    public function guardarConfiguracionTabla()
    {
        $this->validate([
            'nuevaTablaOrigen' => 'required|string|max:255',
            'nuevaTablaDestino' => 'required|string|max:255'
        ], [
            'nuevaTablaOrigen.required' => 'El nombre de la tabla de origen es obligatorio',
            'nuevaTablaDestino.required' => 'El nombre de la tabla de destino es obligatorio'
        ]);

        try {
            // Actualizar la configuración local
            $this->configuraciones[$this->tipoSincronizacionEditando]['tabla_origen'] = $this->nuevaTablaOrigen;
            $this->configuraciones[$this->tipoSincronizacionEditando]['tabla_destino'] = $this->nuevaTablaDestino;
            
            // Actualizar el archivo del servicio correspondiente
            $this->actualizarTablasEnServicio($this->tipoSincronizacionEditando);
            
            $this->mostrarModalEdicionTabla = false;
            $this->mensajeExito = "Configuración de tablas actualizada correctamente para {$this->configuraciones[$this->tipoSincronizacionEditando]['nombre']}";
            
            $this->tipoSincronizacionEditando = '';
            $this->nuevaTablaOrigen = '';
            $this->nuevaTablaDestino = '';
            
        } catch (\Exception $e) {
            $this->mensajeError = 'Error al actualizar la configuración de tablas: ' . $e->getMessage();
        }
    }

    private function actualizarTablasEnServicio($tipo)
    {
        $servicios = [
            'marcas' => 'app/Services/SincronizacionMarcasService.php',
            'categorias' => 'app/Services/SincronizacionCategoriasService.php',
            'subcategorias' => 'app/Services/SincronizacionSubcategoriasService.php',
            'unidades' => 'app/Services/SincronizacionUnidadesService.php',
            'productos' => 'app/Services/SincronizacionProductosService.php',
            'compras' => 'app/Services/SincronizacionComprasService.php'
        ];

        if (!isset($servicios[$tipo])) {
            throw new \Exception("Tipo de sincronización no válido: {$tipo}");
        }

        $rutaCompleta = base_path($servicios[$tipo]);
        if (!File::exists($rutaCompleta)) {
            throw new \Exception("Archivo del servicio no encontrado: {$servicios[$tipo]}");
        }

        $contenido = File::get($rutaCompleta);
        $tablaOrigen = $this->configuraciones[$tipo]['tabla_origen'];
        $tablaDestino = $this->configuraciones[$tipo]['tabla_destino'];

        // Actualizar referencias a las tablas según el tipo de servicio
        switch ($tipo) {
            case 'marcas':
            case 'categorias':
            case 'subcategorias':
            case 'unidades':
            case 'productos':
                // Actualizar las consultas FROM
                $contenido = preg_replace(
                    '/->from\([\'"][^\'\"]*[\'"]\)/',
                    "->from('{$tablaOrigen}')",
                    $contenido
                );
                
                // Actualizar las consultas table()
                $contenido = preg_replace(
                    '/->table\([\'"][^\'\"]*[\'"]\)/',
                    "->table('{$tablaDestino}')",
                    $contenido
                );
                break;
                
            case 'compras':
                // Para compras, actualizar tanto recibido_bodega como compra
                $contenido = preg_replace(
                    '/->from\([\'"]recibido_bodega[\'"]\)/',
                    "->from('{$tablaOrigen}')",
                    $contenido
                );
                
                $contenido = preg_replace(
                    '/->table\([\'"]compra[\'"]\)/',
                    "->table('{$tablaDestino}')",
                    $contenido
                );
                break;
        }

        File::put($rutaCompleta, $contenido);
        Log::info("Configuración de tablas actualizada en: {$servicios[$tipo]} - Origen: {$tablaOrigen}, Destino: {$tablaDestino}");
    }

    public function toggleSincronizacion($tipo)
    {
        if (isset($this->configuraciones[$tipo])) {
            $this->configuraciones[$tipo]['activo'] = !$this->configuraciones[$tipo]['activo'];
            $this->mensajeExito = "Sincronización de {$tipo} " . ($this->configuraciones[$tipo]['activo'] ? 'activada' : 'desactivada');
        }
    }

    public function probarComando($comando)
    {
        try {
            $this->mensajeExito = "Ejecutando comando: php artisan {$comando}";
            // Aquí podrías ejecutar el comando real si es necesario
        } catch (\Exception $e) {
            $this->mensajeError = 'Error al probar el comando: ' . $e->getMessage();
        }
    }

    private function limpiarMensajes()
    {
        $this->mensajeExito = '';
        $this->mensajeError = '';
    }

    public function render()
    {
        return view('livewire.sincronizacion.base-de-datos');
    }
}
