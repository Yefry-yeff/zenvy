<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MorphingLogController extends Controller
{
    public function logMorphingError(Request $request)
    {
        try {
            $logData = $request->input('log_data');
            $userAgent = $request->input('user_agent');
            $timestamp = $request->input('timestamp');

            // Formatear el log para Laravel
            $logMessage = sprintf(
                "[DOM-MORPHING-%s] %s: %s | URL: %s | User-Agent: %s",
                $logData['type'],
                $logData['timestamp'],
                $logData['details'],
                $logData['url'],
                $userAgent
            );

            // Agregar información adicional si es un error
            if (!empty($logData['error'])) {
                $logMessage .= " | Error: " . $logData['error'];
            }

            if (!empty($logData['stack'])) {
                $logMessage .= " | Stack: " . substr($logData['stack'], 0, 500); // Limitar stack trace
            }

            // Log según el tipo
            switch ($logData['type']) {
                case 'ERROR':
                    Log::error($logMessage);
                    break;
                case 'WARNING':
                    Log::warning($logMessage);
                    break;
                case 'MORPH_SKIP':
                    Log::info($logMessage);
                    break;
                default:
                    Log::debug($logMessage);
            }

            return response()->json(['status' => 'logged'], 200);

        } catch (\Exception $e) {
            Log::error('Error al procesar log de morphing: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}
