<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

Route::get('/', function () {
    //return view('welcome');
    return redirect('/login');
});

Route::get('/dashboard', function () {
    return view('layouts.app');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/logout', function () {
    Auth::logout();
    Session::flush();
    return redirect('/login');
})->name('logout');

Route::get('/register', function () {
    return redirect('/login')->with('status', 'El registro está deshabilitado. Contacta al administrador.');
})->name('register');


Route::get('/menus/data', [MenuController::class, 'data'])->name('menus.data');

// Rutas de Sala de Ventas
Route::middleware('auth')->group(function () {
    Route::get('/facturacion', function () {
        return view('layouts.app');
    })->name('facturacion');

    // Ruta para gestión de empresa
    Route::get('/empresa', function () {
        return view('layouts.app');
    })->name('empresa');

    // Rutas para imágenes de facturas
    Route::get('/factura/{id}/imagen', function ($id) {
        $factura = \App\Models\Factura::find($id);

        if (!$factura || !$factura->factura_imagen) {
            abort(404, 'Imagen de factura no encontrada');
        }

        return response($factura->factura_imagen)
            ->header('Content-Type', 'image/png')
            ->header('Content-Length', strlen($factura->factura_imagen))
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Content-Disposition', 'inline; filename="factura_' . $factura->numero_factura . '.png"');
    })->name('factura.imagen');

    Route::get('/factura/{id}/imagen/descargar', function ($id) {
        $factura = \App\Models\Factura::find($id);

        if (!$factura || !$factura->factura_imagen) {
            abort(404, 'Imagen de factura no encontrada');
        }

        $nombreArchivo = 'factura_' . $factura->numero_factura . '.png';

        return response($factura->factura_imagen)
            ->header('Content-Type', 'image/png')
            ->header('Content-Length', strlen($factura->factura_imagen))
            ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
            ->header('Cache-Control', 'no-cache, must-revalidate');
    })->name('factura.imagen.descargar');

    // Ruta para generar PDF de factura
    // Rutas para factura PDF
    Route::get('factura/{id}/pdf', [App\Http\Controllers\FacturaPDFController::class, 'generarPDF'])->name('factura.pdf');
    Route::get('factura/{id}/pdf/preview', [App\Http\Controllers\FacturaPDFController::class, 'previsualizarPDF'])->name('factura.pdf.preview');
    
    // Ruta para ver detalle de factura
    Route::get('factura/{id}/detalle', [App\Http\Controllers\FacturaController::class, 'detalle'])->name('factura.detalle');
});

require __DIR__.'/auth.php';

Route::post('/debug-log', function (Request $request) {
    Log::debug('📩 [JS DEBUG] ' . $request->input('mensaje'));
    return response()->json(['status' => 'ok']);
});
