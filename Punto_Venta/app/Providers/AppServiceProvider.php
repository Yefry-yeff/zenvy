<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Menu;
use App\Observers\MenuObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No se utiliza
    }

    public function boot(): void
    {
        Menu::observe(MenuObserver::class);

        View::composer('*', function ($view) {
            $usuario = Auth::user();

            if (!$usuario) {
                $view->with('sidebarMenu', []);
                return;
            }

            // Obtener todos los grupos de menú
            $menuGrupos = DB::table('menu_grupo')->orderBy('id')->get();

            // Verificar si el usuario tiene el rol "admin"
            $esAdmin = DB::table('user_rol')
                ->join('roles', 'user_rol.rol_id', '=', 'roles.id')
                ->where('user_rol.user_id', $usuario->id)
                ->where('roles.txt_nombre', 'admin')
                ->where('user_rol.estado', 1)
                ->exists();

            if ($esAdmin) {
                // Mostrar todos los menús activos
                $menuItems = DB::table('menu')
                    ->where('estado', 1)
                    ->orderBy('orden')
                    ->get();
            } else {
                // Mostrar menús según permisos del usuario
                $menuItems = DB::table('menu')
                    ->join('permisos', 'menu.id', '=', 'permisos.id_menu')
                    ->join('rol_permiso', 'permisos.id', '=', 'rol_permiso.permiso_id')
                    ->join('user_rol', 'rol_permiso.rol_id', '=', 'user_rol.rol_id')
                    ->where('user_rol.user_id', $usuario->id)
                    ->where('user_rol.estado', 1)
                    ->where('rol_permiso.estado', 1)
                    ->where('menu.estado', 1)
                    ->select('menu.*')
                    ->distinct()
                    ->orderBy('menu.orden')
                    ->get();
            }

            // Armar estructura del menú lateral
            $menu = $menuGrupos->map(function ($grupo) use ($menuItems) {
                $items = $menuItems->where('parent_id', $grupo->id);

                if ($items->isEmpty()) return null;

                return [
                    'label' => $grupo->nombre,
                    'icon' => $items->first()?->icon ?? '📁',
                    'items' => $items->map(function ($item) {
                        return [
                            'label' => $item->txt_comentario,
                            'icon' => $item->icon,
                            'route' => $item->route,
                        ];
                    })->values(),
                ];
            })->filter()->values();

            $view->with('sidebarMenu', $menu);
        });
    }
}
