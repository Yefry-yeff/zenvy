<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class DownloadController extends Controller
{
    public function downloadFile(Request $request)
    {
        try {
            $filename = $request->query('file');

            if (!$filename) {
                abort(400, 'Nombre de archivo requerido');
            }

            // Validar que el archivo esté en el directorio temp
            $filepath = storage_path('app/temp/' . $filename);

            if (!file_exists($filepath)) {
                abort(404, 'Archivo no encontrado');
            }

            // Validar que el archivo no sea muy antiguo (más de 1 hora)
            if (filemtime($filepath) < (time() - 3600)) {
                unlink($filepath); // Eliminar archivo antiguo
                abort(404, 'Archivo expirado');
            }

            // Determinar el tipo de contenido basado en la extensión
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $contentType = match($extension) {
                'csv' => 'text/csv',
                'html' => 'text/html',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pdf' => 'application/pdf',
                default => 'application/octet-stream'
            };

            // Descargar el archivo y eliminarlo después
            return response()->download($filepath, $filename, [
                'Content-Type' => $contentType,
            ])->deleteFileAfterSend();

        } catch (\Exception $e) {
            Log::error('Error downloading file', [
                'filename' => $filename ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            abort(500, 'Error interno del servidor');
        }
    }
}
