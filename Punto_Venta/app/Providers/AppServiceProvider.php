<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use App\Observers\MenuObserver;

/**
 * Clase AppServiceProvider
 * Este proveedor de servicio se encarga de registrar observadores de modelos
 * y compartir datos globales con todas las vistas del sistema.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Método register
     * Aquí se pueden vincular servicios al contenedor de Laravel.
     * En este caso, no se realiza ninguna acción.
     */
    public function register(): void
    {
        // No se utiliza en este contexto
    }

    /**
     * Método boot
     * Se ejecuta cuando todos los servicios han sido registrados.
     * Aquí se configuran observadores y se comparten datos globales con las vistas.
     */
    public function boot(): void
    {
        // ✅ Se registra el observador MenuObserver para el modelo Menu
        Menu::observe(MenuObserver::class);

        /**
         * View::composer('*', ...) permite compartir datos con todas las vistas del sistema.
         * En este caso, se está generando el menú lateral dinámico y compartiéndolo con la variable 'sidebarMenu'.
         */
        View::composer('*', function ($view) {

            // ✅ Obtener todos los grupos de menú, ordenados por ID
            $menuGrupos = DB::table('menu_grupo')->orderBy('id')->get();

            // ✅ Obtener todos los ítems (submenús) activos ordenados por el campo 'orden'
            $menuItems = DB::table('menu')
                ->where('estado', 1) // Solo submenús activos
                ->orderBy('orden')
                ->get();

            /**
             * Se construye el menú lateral combinando grupos e ítems.
             * Cada grupo incluirá solo los submenús (items) activos que le correspondan.
             */
            $menu = $menuGrupos->map(function ($grupo) use ($menuItems) {
                // Filtrar los ítems que pertenecen al grupo actual
                $items = $menuItems->where('parent_id', $grupo->id);

                // ✅ Si el grupo no tiene submenús activos, se omite del menú
                if ($items->isEmpty()) return null;

                return [
                    'label' => $grupo->nombre, // Nombre del grupo de menú
                    'icon' => $menuItems->firstWhere('parent_id', $grupo->id)?->icon ?? '📁', // Icono del grupo
                    'items' => $items->map(function ($item) {
                        return [
                            'label' => $item->txt_comentario, // Etiqueta del submenú
                            'icon' => $item->icon,             // Icono del submenú
                            'route' => $item->route,           // Ruta asociada al submenú
                        ];
                    })->values(), // Asegura que el array de items tenga índices ordenados
                ];
            })->filter()->values(); // ✅ Se eliminan los grupos nulos (sin submenús) y se reindexa el array

            // ✅ Se comparte el menú generado con todas las vistas usando la variable 'sidebarMenu'
            $view->with('sidebarMenu', $menu);
        });
    }
}
