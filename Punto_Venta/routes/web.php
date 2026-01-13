<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PedidosWebController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MorphingLogController;
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
    
    // Bandeja de Pedidos Web
    Route::prefix('pedidos-web')->name('pedidos-web.')->group(function() {
        Route::get('/', [PedidosWebController::class, 'index'])->name('index');
        Route::get('/{id}', [PedidosWebController::class, 'show'])->name('show');
        Route::post('/{id}/process', [PedidosWebController::class, 'process'])->name('process');
        Route::post('/{id}/reject', [PedidosWebController::class, 'reject'])->name('reject');
        Route::get('/api/pendientes', [PedidosWebController::class, 'apiPendientes'])->name('api.pendientes');
    });
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

// Ruta para logging de errores de DOM morphing
Route::post('/api/log-morphing-error', [MorphingLogController::class, 'logMorphingError'])->name('log.morphing.error');

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

    // Rutas para cierre de caja PDF
    Route::get('cierre-caja/{id}/pdf', [App\Http\Controllers\CierreCajaPDFController::class, 'generarPDF'])->name('cierre-caja.pdf');
    Route::get('cierre-caja/{id}/pdf/preview', [App\Http\Controllers\CierreCajaPDFController::class, 'previsualizarPDF'])->name('cierre-caja.pdf.preview');
    Route::get('cierre-caja/{id}/reporte-transacciones', [App\Http\Controllers\CierreCajaPDFController::class, 'reporteTransacciones'])->name('cierre-caja.reporte-transacciones');
    Route::get('cierre-caja/reporte-consolidado', [App\Http\Controllers\CierreCajaPDFController::class, 'reporteConsolidado'])->name('cierre-caja.reporte-consolidado');

    // Ruta para descargar archivos generados por Livewire
    Route::get('/download', [App\Http\Controllers\DownloadController::class, 'downloadFile'])->name('download.file');
});

require __DIR__.'/auth.php';

Route::post('/debug-log', function (Request $request) {
    Log::debug('📩 [JS DEBUG] ' . $request->input('mensaje'));
    return response()->json(['status' => 'ok']);
});

// Ruta para servir archivos temporales de descarga
Route::get('/storage/temp/{filename}', function ($filename) {
    $filepath = storage_path('app/temp/' . $filename);

    if (!file_exists($filepath)) {
        abort(404, 'Archivo no encontrado');
    }

    // Determinar el tipo de archivo
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $mimeType = 'application/octet-stream';

    if ($extension === 'csv') {
        $mimeType = 'text/csv';
    } elseif ($extension === 'html') {
        $mimeType = 'text/html';
    }

    return response()->file($filepath, [
        'Content-Type' => $mimeType,
        'Content-Disposition' => 'attachment; filename="' . $filename . '"'
    ]);
})->middleware('auth');
