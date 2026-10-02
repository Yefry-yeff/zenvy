<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Response;

class ProductoImagenController extends Controller
{
    public function show(int $producto): Response
    {
        $imagen = Producto::query()->whereKey($producto)->value('imagen');

        abort_if(!$imagen, 404, 'Imagen de producto no encontrada');

        $tipoContenido = (new \finfo(FILEINFO_MIME_TYPE))->buffer($imagen);
        if (!is_string($tipoContenido) || !str_starts_with($tipoContenido, 'image/')) {
            $tipoContenido = 'image/jpeg';
        }

        return response($imagen)
            ->header('Content-Type', $tipoContenido)
            ->header('Content-Length', strlen($imagen))
            ->header('Cache-Control', 'private, max-age=3600');
    }
}