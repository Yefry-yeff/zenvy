<?php

require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "=== PRUEBA DE VALIDACIÓN DE APERTURA PARA CIERRE DE JORNADA ===\n\n";

try {
    // Configuración de prueba
    $fechaPrueba = '2025-01-24';
    $tiendaId = 1; // Cambiar por un ID de tienda existente
    
    echo "1. Verificando estado actual de jornadas para fecha: $fechaPrueba, tienda: $tiendaId\n";
    
    // Consultar jornadas existentes
    $jornadas = DB::table('jornada')
        ->where('tienda_id', $tiendaId)
        ->whereDate('fecha', $fechaPrueba)
        ->get();
    
    echo "Jornadas encontradas: " . count($jornadas) . "\n";
    
    foreach ($jornadas as $jornada) {
        echo "- ID: {$jornada->id}, Fecha: {$jornada->fecha}, Apertura: {$jornada->apertura}, Cierre: {$jornada->cierre}\n";
    }
    
    echo "\n2. Simulando validación de apertura:\n";
    
    // Verificar si existe una jornada aperturada
    $jornadaAperturada = DB::table('jornada')
        ->whereDate('fecha', $fechaPrueba)
        ->where('tienda_id', $tiendaId)
        ->where('apertura', 1)
        ->first();
    
    if (!$jornadaAperturada) {
        echo "❌ ERROR: No se ha aperturado la jornada para la fecha $fechaPrueba\n";
        echo "   Mensaje: No se puede cerrar la jornada porque no se ha aperturado la jornada para la fecha $fechaPrueba. Debe aperturar la jornada primero.\n";
    } else {
        echo "✅ JORNADA APERTURADA: Jornada ID {$jornadaAperturada->id} está aperturada\n";
        
        // Verificar si ya está cerrada
        if ($jornadaAperturada->cierre == 1) {
            echo "❌ ERROR: La jornada ya está cerrada\n";
        } else {
            echo "✅ PUEDE CERRAR: La jornada está abierta y puede ser cerrada\n";
        }
    }
    
    echo "\n3. Consultando información de la tienda:\n";
    
    $tienda = DB::table('tienda')->where('id', $tiendaId)->first();
    
    if ($tienda) {
        echo "Tienda: {$tienda->denominacion_social}\n";
        echo "Dirección: {$tienda->direccion}\n";
    } else {
        echo "❌ No se encontró la tienda con ID: $tiendaId\n";
    }
    
    echo "\n4. Creando jornada de prueba (apertura):\n";
    
    // Insertar una jornada de apertura si no existe
    $jornadaExiste = DB::table('jornada')
        ->whereDate('fecha', $fechaPrueba)
        ->where('tienda_id', $tiendaId)
        ->exists();
    
    if (!$jornadaExiste) {
        $jornadaId = DB::table('jornada')->insertGetId([
            'fecha' => $fechaPrueba,
            'tienda_id' => $tiendaId,
            'apertura' => 1,
            'cierre' => 0,
            'fecha_apertura' => now(),
            'usuario_apertura' => 1, // Cambiar por un usuario existente
            'comentario' => 'Jornada de prueba creada automáticamente',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        echo "✅ Jornada de apertura creada con ID: $jornadaId\n";
    } else {
        echo "ℹ️  Ya existe una jornada para esta fecha\n";
    }
    
    echo "\n5. Verificando nuevamente después de la creación:\n";
    
    $jornadaAperturadaNueva = DB::table('jornada')
        ->whereDate('fecha', $fechaPrueba)
        ->where('tienda_id', $tiendaId)
        ->where('apertura', 1)
        ->first();
    
    if ($jornadaAperturadaNueva) {
        echo "✅ VALIDACIÓN EXITOSA: Ahora existe una jornada aperturada\n";
        echo "   - Puede proceder con el cierre de jornada\n";
        echo "   - ID Jornada: {$jornadaAperturadaNueva->id}\n";
        echo "   - Estado cierre: {$jornadaAperturadaNueva->cierre}\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
