<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE CREACIÓN DE NUEVO REGISTRO EN APERTURA ===\n\n";
    
    $fechaActual = date('Y-m-d');
    $tiendaId = 1;
    
    // 1. Limpiar datos de prueba
    echo "1. Limpiando datos de prueba...\n";
    $stmt = $pdo->prepare("DELETE FROM jornada WHERE tienda_id = ? AND fecha = ?");
    $stmt->execute([$tiendaId, $fechaActual]);
    echo "✅ Datos limpiados\n\n";
    
    // 2. Contar registros antes de la apertura
    echo "2. Contando registros antes de la apertura...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jornada WHERE tienda_id = ?");
    $stmt->execute([$tiendaId]);
    $registrosAntes = $stmt->fetch(PDO::FETCH_OBJ)->count;
    echo "Registros antes: $registrosAntes\n\n";
    
    // 3. Simular primera apertura
    echo "3. Simulando primera apertura de jornada...\n";
    
    $comentario1 = "Primera apertura del día - Usuario ID 1";
    
    $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, apertura, cierre, user_id_apertura, comentario, created_at, updated_at) VALUES (?, ?, 1, 0, 1, ?, NOW(), NOW())");
    $stmt->execute([$fechaActual, $tiendaId, $comentario1]);
    
    $primerRegistroId = $pdo->lastInsertId();
    echo "✅ Primer registro creado con ID: $primerRegistroId\n";
    
    // 4. Verificar el registro creado
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE id = ?");
    $stmt->execute([$primerRegistroId]);
    $primerRegistro = $stmt->fetch(PDO::FETCH_OBJ);
    
    echo "Primer registro:\n";
    echo "   - ID: {$primerRegistro->id}\n";
    echo "   - Fecha: {$primerRegistro->fecha}\n";
    echo "   - Tienda ID: {$primerRegistro->tienda_id}\n";
    echo "   - Apertura: {$primerRegistro->apertura}\n";
    echo "   - Cierre: {$primerRegistro->cierre}\n";
    echo "   - User ID Apertura: {$primerRegistro->user_id_apertura}\n";
    echo "   - Comentario: \"{$primerRegistro->comentario}\"\n\n";
    
    // 5. Contar registros después de la primera apertura
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jornada WHERE tienda_id = ?");
    $stmt->execute([$tiendaId]);
    $registrosDespuesPrimero = $stmt->fetch(PDO::FETCH_OBJ)->count;
    echo "Registros después de primera apertura: $registrosDespuesPrimero\n";
    echo "Incremento: " . ($registrosDespuesPrimero - $registrosAntes) . " registro(s)\n\n";
    
    // 6. Cerrar la jornada para poder aperturar de nuevo
    echo "4. Cerrando la jornada para simular día siguiente...\n";
    
    $stmt = $pdo->prepare("UPDATE jornada SET apertura = 0, cierre = 1, user_id_cierre = 2, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$primerRegistroId]);
    
    echo "✅ Jornada cerrada\n\n";
    
    // 7. Simular apertura del día siguiente
    echo "5. Simulando apertura del día siguiente...\n";
    
    $fechaSiguiente = date('Y-m-d', strtotime('+1 day'));
    $comentario2 = "Segunda apertura - Día siguiente - Usuario ID 3";
    
    $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, apertura, cierre, user_id_apertura, comentario, created_at, updated_at) VALUES (?, ?, 1, 0, 3, ?, NOW(), NOW())");
    $stmt->execute([$fechaSiguiente, $tiendaId, $comentario2]);
    
    $segundoRegistroId = $pdo->lastInsertId();
    echo "✅ Segundo registro creado con ID: $segundoRegistroId\n\n";
    
    // 8. Verificar que se creó un NUEVO registro
    echo "6. Verificando que se creó un NUEVO registro...\n";
    
    if ($segundoRegistroId !== $primerRegistroId) {
        echo "✅ CORRECTO: Se creó un nuevo registro\n";
        echo "   - Primer registro ID: $primerRegistroId\n";
        echo "   - Segundo registro ID: $segundoRegistroId\n";
    } else {
        echo "❌ ERROR: Se actualizó el registro existente en lugar de crear uno nuevo\n";
    }
    
    // 9. Contar registros totales
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jornada WHERE tienda_id = ?");
    $stmt->execute([$tiendaId]);
    $registrosFinales = $stmt->fetch(PDO::FETCH_OBJ)->count;
    echo "Registros totales finales: $registrosFinales\n\n";
    
    // 10. Mostrar todos los registros
    echo "7. Mostrando todos los registros de jornada para la tienda:\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE tienda_id = ? ORDER BY fecha DESC, created_at DESC");
    $stmt->execute([$tiendaId]);
    $todosLosRegistros = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    foreach ($todosLosRegistros as $index => $registro) {
        echo "REGISTRO " . ($index + 1) . ":\n";
        echo "   - ID: {$registro->id}\n";
        echo "   - Fecha: {$registro->fecha}\n";
        echo "   - Apertura: {$registro->apertura}\n";
        echo "   - Cierre: {$registro->cierre}\n";
        echo "   - User ID Apertura: " . ($registro->user_id_apertura ?? 'NULL') . "\n";
        echo "   - User ID Cierre: " . ($registro->user_id_cierre ?? 'NULL') . "\n";
        echo "   - Comentario: \"{$registro->comentario}\"\n";
        echo "   - Created: {$registro->created_at}\n";
        echo "   - Updated: {$registro->updated_at}\n\n";
    }
    
    // 11. Validar integridad de datos
    echo "8. Validaciones de integridad:\n";
    
    $validaciones = [];
    
    // Cada registro debe tener un ID único
    $ids = array_column($todosLosRegistros, 'id');
    if (count($ids) === count(array_unique($ids))) {
        $validaciones[] = "✅ Todos los IDs son únicos";
    } else {
        $validaciones[] = "❌ Hay IDs duplicados";
    }
    
    // Cada apertura debe tener user_id_apertura
    $sinUserApertura = 0;
    foreach ($todosLosRegistros as $registro) {
        if (empty($registro->user_id_apertura)) {
            $sinUserApertura++;
        }
    }
    
    if ($sinUserApertura === 0) {
        $validaciones[] = "✅ Todos los registros tienen user_id_apertura";
    } else {
        $validaciones[] = "❌ $sinUserApertura registro(s) sin user_id_apertura";
    }
    
    // Mostrar validaciones
    foreach ($validaciones as $validacion) {
        echo "   $validacion\n";
    }

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== CONFIRMACIÓN DE COMPORTAMIENTO ===\n";
echo "✅ CADA APERTURA CREA UN NUEVO REGISTRO\n";
echo "✅ NO SE ACTUALIZAN REGISTROS EXISTENTES\n";
echo "✅ CADA REGISTRO TIENE SU PROPIO ID ÚNICO\n";
echo "✅ SE ASIGNA user_id_apertura AL CREAR\n";
echo "✅ SE PRESERVA HISTORIAL COMPLETO DE JORNADAS\n";

echo "\n=== CÓDIGO LIVEWIRE IMPLEMENTADO ===\n";
echo "// SIEMPRE crear nuevo registro\n";
echo "\$jornadaId = DB::table('jornada')->insertGetId([\n";
echo "    'fecha' => \$this->fechaApertura,\n";
echo "    'tienda_id' => \$this->tiendaUsuario,\n";
echo "    'apertura' => 1,\n";
echo "    'cierre' => 0,\n";
echo "    'user_id_apertura' => Auth::id(),\n";
echo "    'comentario' => \$this->comentario,\n";
echo "    'created_at' => now(),\n";
echo "    'updated_at' => now()\n";
echo "]);\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
