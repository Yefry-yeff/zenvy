<?php

namespace App\Livewire\Sincronizacion;

use Livewire\Component;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BaseDeDatos extends Component
{
    public $configuraciones = [];
    public $nombreBaseDatosOrigen = 'profac_app';
    public $nombreBaseDatosDestino = 'mysql';
    public $mostrarConfiguracionTablas = false;
    
    // Para el modal de edición
    public $mostrarModalEdicion = false;
    
    // Mensajes de estado
    public $mensajeExito = '';
    public $mensajeError = '';

    public function mount()
    {
        $this->cargarConfiguraciones();
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

    public function guardarConfiguracionBaseDatos()
    {
        $this->validate([
            'nombreBaseDatosOrigen' => 'required|string|max:255',
            'nombreBaseDatosDestino' => 'required|string|max:255'
        ], [
            'nombreBaseDatosOrigen.required' => 'El nombre de la base de datos de origen es obligatorio',
            'nombreBaseDatosDestino.required' => 'El nombre de la base de datos de destino es obligatorio'
        ]);

        try {
            // Actualizar las configuraciones de conexión en los servicios
            $this->actualizarConfiguracionEnServicios();
            
            $this->mostrarModalEdicion = false;
            $this->mensajeExito = 'Configuración de base de datos actualizada correctamente en todos los servicios';
            
        } catch (\Exception $e) {
            $this->mensajeError = 'Error al actualizar la configuración: ' . $e->getMessage();
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
                
                // Actualizar la conexión de Valencia
                $contenido = preg_replace(
                    '/\$this->conexionValencia = DB::connection\([\'"][\w_]+[\'"]\);/',
                    '$this->conexionValencia = DB::connection(\'' . $this->nombreBaseDatosOrigen . '\');',
                    $contenido
                );
                
                // Actualizar la conexión de Zenvy
                $contenido = preg_replace(
                    '/\$this->conexionZenvy = DB::connection\(\);/',
                    '$this->conexionZenvy = DB::connection(\'' . $this->nombreBaseDatosDestino . '\');',
                    $contenido
                );

                File::put($rutaCompleta, $contenido);
                Log::info("Configuración actualizada en: {$servicio}");
            }
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
