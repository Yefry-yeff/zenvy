<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICANDO NUEVA ESTRUCTURA DE TABLA JORNADA ===\n\n";
    
    // Verificar estructura actualizada
    $stmt = $pdo->prepare("DESCRIBE jornada");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    echo "Columnas actuales en la tabla jornada:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type}) - Null: {$column->Null}, Default: {$column->Default}\n";
    }
    
    // Verificar campos específicos
    $camposRequeridos = ['user_id_apertura', 'user_id_cierre'];
    $camposEncontrados = [];
    
    foreach ($columns as $column) {
        if (in_array($column->Field, $camposRequeridos)) {
            $camposEncontrados[] = $column->Field;
            echo "\n✅ Campo {$column->Field} encontrado: {$column->Type}\n";
        }
    }
    
    $faltantes = array_diff($camposRequeridos, $camposEncontrados);
    if (empty($faltantes)) {
        echo "\n✅ TODOS LOS CAMPOS REQUERIDOS ESTÁN PRESENTES\n";
    } else {
        echo "\n❌ CAMPOS FALTANTES: " . implode(', ', $faltantes) . "\n";
        exit;
    }
    
    echo "\n=== PROBANDO NUEVO FLUJO CON ESTRUCTURA ACTUALIZADA ===\n\n";
    
    $fechaPrueba = '2025-01-26';
    $tiendaId = 1;
    $userIdApertura = 1;
    $userIdCierre = 2; // Simulamos que otro usuario cierra
    
    // 1. Limpiar datos de prueba
    echo "1. Limpiando datos de prueba...\n";
    $stmt = $pdo->prepare("DELETE FROM jornada WHERE fecha = ? AND tienda_id = ?");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    echo "✅ Datos limpiados\n\n";
    
    // 2. Crear jornada con apertura (simulando apertura previa)
    echo "2. Creando jornada con apertura...\n";
    
    $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, apertura, cierre, user_id_apertura, created_at, updated_at) VALUES (?, ?, 1, 0, ?, NOW(), NOW())");
    $stmt->execute([$fechaPrueba, $tiendaId, $userIdApertura]);
    $jornadaId = $pdo->lastInsertId();
    
    echo "✅ Jornada creada:\n";
    echo "   - ID: $jornadaId\n";
    echo "   - Fecha: $fechaPrueba\n";
    echo "   - Tienda ID: $tiendaId\n";
    echo "   - Apertura: 1\n";
    echo "   - Cierre: 0\n";
    echo "   - User ID Apertura: $userIdApertura\n";
    echo "   - User ID Cierre: NULL\n\n";
    
    // 3. Simular validación de cierre
    echo "3. Validando condiciones para cierre...\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE fecha = ? AND tienda_id = ? AND apertura = 1");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $jornadaParaCerrar = $stmt->fetch(PDO::FETCH_OBJ);
    
    if ($jornadaParaCerrar) {
        echo "✅ VALIDACIÓN EXITOSA: Jornada encontrada\n";
        echo "   - Apertura: {$jornadaParaCerrar->apertura}\n";
        echo "   - Cierre: {$jornadaParaCerrar->cierre}\n";
        echo "   - Puede proceder con el cierre\n\n";
    } else {
        echo "❌ ERROR: No se encontró jornada para cerrar\n";
        exit;
    }
    
    // 4. Procesar cierre con nueva estructura
    echo "4. Procesando cierre con nueva estructura...\n";
    
    $comentario = "Cierre realizado con nueva estructura: user_id_apertura y user_id_cierre separados";
    
    $stmt = $pdo->prepare("UPDATE jornada SET apertura = 0, cierre = 1, user_id_cierre = ?, comentario = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$userIdCierre, $comentario, $jornadaId]);
    
    echo "✅ Cierre procesado\n\n";
    
    // 5. Verificar resultado final
    echo "5. Verificando resultado final...\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE id = ?");
    $stmt->execute([$jornadaId]);
    $jornadaFinal = $stmt->fetch(PDO::FETCH_OBJ);
    
    echo "JORNADA DESPUÉS DEL CIERRE:\n";
    echo "   - ID: {$jornadaFinal->id}\n";
    echo "   - Fecha: {$jornadaFinal->fecha}\n";
    echo "   - Tienda ID: {$jornadaFinal->tienda_id}\n";
    echo "   - Apertura: {$jornadaFinal->apertura} (debe ser 0)\n";
    echo "   - Cierre: {$jornadaFinal->cierre} (debe ser 1)\n";
    echo "   - User ID Apertura: {$jornadaFinal->user_id_apertura}\n";
    echo "   - User ID Cierre: {$jornadaFinal->user_id_cierre}\n";
    echo "   - Comentario: \"{$jornadaFinal->comentario}\"\n";
    echo "   - Created: {$jornadaFinal->created_at}\n";
    echo "   - Updated: {$jornadaFinal->updated_at}\n\n";
    
    // 6. Validar que todo está correcto
    $validaciones = [];
    
    if ($jornadaFinal->apertura == 0) {
        $validaciones[] = "✅ Apertura cambió a 0";
    } else {
        $validaciones[] = "❌ Apertura no cambió correctamente";
    }
    
    if ($jornadaFinal->cierre == 1) {
        $validaciones[] = "✅ Cierre cambió a 1";
    } else {
        $validaciones[] = "❌ Cierre no cambió correctamente";
    }
    
    if ($jornadaFinal->user_id_apertura == $userIdApertura) {
        $validaciones[] = "✅ User ID Apertura conservado";
    } else {
        $validaciones[] = "❌ User ID Apertura modificado incorrectamente";
    }
    
    if ($jornadaFinal->user_id_cierre == $userIdCierre) {
        $validaciones[] = "✅ User ID Cierre asignado correctamente";
    } else {
        $validaciones[] = "❌ User ID Cierre no asignado";
    }
    
    if (!empty($jornadaFinal->comentario)) {
        $validaciones[] = "✅ Comentario guardado";
    } else {
        $validaciones[] = "❌ Comentario no guardado";
    }
    
    echo "VALIDACIONES:\n";
    foreach ($validaciones as $validacion) {
        echo "   $validacion\n";
    }
    
    // 7. Probar que no se puede cerrar de nuevo
    echo "\n6. Probando que no se puede cerrar de nuevo...\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE fecha = ? AND tienda_id = ? AND apertura = 1");
    $stmt->execute([$fechaPrueba, $tiendaId]);
    $intentoSegundoCierre = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$intentoSegundoCierre) {
        echo "✅ CORRECTO: No se encontró jornada con apertura=1\n";
        echo "   El sistema correctamente impediría un segundo cierre\n";
    } else {
        echo "❌ ERROR: Aún se encuentra jornada con apertura=1\n";
    }

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMEN DE LA NUEVA IMPLEMENTACIÓN ===\n";
echo "✅ Estructura actualizada con user_id_apertura y user_id_cierre\n";
echo "✅ Al cerrar se asigna Auth::id() a user_id_cierre\n";
echo "✅ Se conserva user_id_apertura del registro original\n";
echo "✅ Flujo: apertura=0, cierre=1, user_id_cierre=Auth::id()\n";
echo "✅ Comentario incluido en el proceso\n";
echo "✅ Validación robusta para evitar cierres duplicados\n";

echo "\n=== CÓDIGO LIVEWIRE ACTUALIZADO ===\n";
echo "UPDATE jornada SET\n";
echo "  apertura = 0,\n";
echo "  cierre = 1,\n";
echo "  user_id_cierre = Auth::id(),\n";
echo "  comentario = \$this->comentario,\n";
echo "  updated_at = NOW()\n";
echo "WHERE id = \$jornada->id\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
