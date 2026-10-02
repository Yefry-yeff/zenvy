<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        // Configurar zona horaria de Honduras globalmente
        \Illuminate\Support\Carbon::setLocale('es');
        date_default_timezone_set('America/Tegucigalpa');
        
        Menu::observe(MenuObserver::class);

        View::composer('*', function ($view) {
            $usuario = Auth::user();

            if (!$usuario) {
                $view->with('sidebarMenu', []);
                return;
            }

            $menu = Cache::remember(
                "sidebar_menu_role_{$usuario->roles_id}",
                now()->addMinutes(5),
                function () use ($usuario) {
                    $menuGrupos = DB::table('menu_grupo')->orderBy('id')->get();
                    $esAdmin = DB::table('roles')
                        ->where('id', $usuario->roles_id)
                        ->where('txt_nombre', 'admin')
                        ->exists();

                    $menuItems = DB::table('menu')
                        ->join('menu_grupo', 'menu.parent_id', '=', 'menu_grupo.id')
                        ->where('menu.estado_id', 1)
                        ->when(!$esAdmin, function ($query) use ($usuario) {
                            $query->join('rol_permiso', 'menu.id', '=', 'rol_permiso.menu_id')
                                ->where('rol_permiso.rol_id', $usuario->roles_id)
                                ->where('rol_permiso.estado', 1);
                        })
                        ->select('menu.*', 'menu_grupo.icon')
                        ->distinct()
                        ->orderBy('menu.orden')
                        ->get();

                    return $menuGrupos->map(function ($grupo) use ($menuItems) {
                        $items = $menuItems->where('parent_id', $grupo->id);

                        if ($items->isEmpty()) {
                            return null;
                        }

                        return [
                            'label' => $grupo->nombre,
                            'icon' => $grupo->icon ?? 'folder',
                            'items' => $items->map(fn ($item) => [
                                'label' => $item->txt_comentario,
                                'route' => $item->route,
                            ])->values(),
                        ];
                    })->filter()->values();
                }
            );

            $view->with('sidebarMenu', $menu);
        });
    }
}
