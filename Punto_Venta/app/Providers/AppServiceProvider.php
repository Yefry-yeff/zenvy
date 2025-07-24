<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use App\Observers\MenuObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Menu::observe(MenuObserver::class);

        View::composer('*', function ($view) {
            $menuGrupos = DB::table('menu_grupo')->orderBy('id')->get();
            $menuItems = DB::table('menu')
                ->where('estado', 1) // ✅ Solo submenús activos
                ->orderBy('orden')
                ->get();

            $menu = $menuGrupos->map(function ($grupo) use ($menuItems) {
                $items = $menuItems->where('parent_id', $grupo->id);

                // ✅ Solo incluir grupo si tiene submenús activos
                if ($items->isEmpty()) return null;

                return [
                    'label' => $grupo->nombre,
                    'icon' => $menuItems->firstWhere('parent_id', $grupo->id)?->icon ?? '📁',
                    'items' => $items->map(function ($item) {
                        return [
                            'label' => $item->txt_comentario,
                            'icon' => $item->icon,
                            'route' => $item->route,
                        ];
                    })->values(),
                ];
            })->filter()->values(); // ✅ Remover nulos

            // ✅ Poner la variable a disposición de todas las vistas
            $view->with('sidebarMenu', $menu);
        });
    }
}
