<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $menuGrupos = DB::table('menu_grupo')->orderBy('id')->get();
            $menuItems = DB::table('menu')->orderBy('orden')->get();

            $menu = $menuGrupos->map(function ($grupo) use ($menuItems) {
                return [
                    'label' => $grupo->nombre,
                    'icon' => $menuItems->firstWhere('parent_id', $grupo->id)?->icon ?? '📁',
                    'items' => $menuItems
                        ->where('parent_id', $grupo->id)
                        ->map(function ($item) {
                            return [
                                'label' => $item->txt_comentario,
                                'route' => $item->routa,
                            ];
                        })->values()
                ];
            });

            $view->with('sidebarMenu', $menu);
        });
    }
}

