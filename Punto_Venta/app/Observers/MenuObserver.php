<?php
namespace App\Observers;

use App\Models\Menu;
use Illuminate\Support\Facades\File;

class MenuObserver
{
    public function created(Menu $menu)
    {
        if (!$menu->route) return;

        $parts = explode('.', $menu->route);
        if (count($parts) !== 2) return;

        [$folder, $file] = $parts;

        $dirPath = resource_path("views/livewire/{$folder}");
        $filePath = "{$dirPath}/{$file}.blade.php";

        File::ensureDirectoryExists($dirPath);

        if (!File::exists($filePath)) {
            File::put($filePath, "<div>\n    <!-- Vista para {$menu->route} -->\n</div>");
        }
    }
}
