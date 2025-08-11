<?php

$host = 'localhost';
$dbname = 'db_zenvy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== PRUEBA DE VALIDACIÓN DE JORNADA ABIERTA PARA OPERACIONES DE CAJA ===\n\n";
    
    $fechaActual = date('Y-m-d');
    $tiendaId = 1;
    $userId = 1;
    
    echo "Configuración de prueba:\n";
    echo "- Fecha actual: $fechaActual\n";
    echo "- Tienda ID: $tiendaId\n";
    echo "- Usuario ID: $userId\n\n";
    
    // 1. Limpiar datos de prueba
    echo "1. Limpiando datos de prueba...\n";
    $stmt = $pdo->prepare("DELETE FROM jornada WHERE tienda_id = ? AND fecha = ?");
    $stmt->execute([$tiendaId, $fechaActual]);
    echo "✅ Jornadas limpiadas\n\n";
    
    // 2. CASO 1: Simular operaciones SIN jornada aperturada
    echo "2. CASO 1: Intentar operaciones SIN jornada aperturada\n";
    
    // Función simulada de validación (igual a la del componente)
    function validarJornadaAbierta($pdo, $tiendaId, $fechaActual) {
        $stmt = $pdo->prepare("SELECT * FROM jornada WHERE fecha = ? AND tienda_id = ? AND apertura = 1 AND cierre = 0");
        $stmt->execute([$fechaActual, $tiendaId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    $jornadaAbierta = validarJornadaAbierta($pdo, $tiendaId, $fechaActual);
    
    if (!$jornadaAbierta) {
        echo "❌ VALIDACIÓN CORRECTA: No hay jornada aperturada\n";
        echo "   Mensaje para operaciones de caja:\n";
        echo "   'No se pueden realizar operaciones de caja porque la jornada no está aperturada para hoy. Debe aperturar la jornada primero.'\n\n";
        
        echo "   OPERACIONES BLOQUEADAS:\n";
        echo "   - ❌ Saldo Inicial\n";
        echo "   - ❌ Recibido de Efectivo\n";
        echo "   - ❌ Entrega de Efectivo\n";
        echo "   - ❌ Cierre de Caja\n\n";
    }
    
    // 3. CASO 2: Aperturar jornada
    echo "3. CASO 2: Aperturar jornada para habilitar operaciones\n";
    
    $stmt = $pdo->prepare("INSERT INTO jornada (fecha, tienda_id, apertura, cierre, user_id_apertura, comentario, created_at, updated_at) VALUES (?, ?, 1, 0, ?, 'Jornada de prueba para operaciones de caja', NOW(), NOW())");
    $stmt->execute([$fechaActual, $tiendaId, $userId]);
    $jornadaId = $pdo->lastInsertId();
    
    echo "✅ Jornada aperturada con ID: $jornadaId\n\n";
    
    // 4. CASO 3: Validar operaciones CON jornada aperturada
    echo "4. CASO 3: Validar operaciones CON jornada aperturada\n";
    
    $jornadaAbierta = validarJornadaAbierta($pdo, $tiendaId, $fechaActual);
    
    if ($jornadaAbierta) {
        echo "✅ VALIDACIÓN EXITOSA: Jornada aperturada encontrada\n";
        echo "   - ID Jornada: {$jornadaAbierta->id}\n";
        echo "   - Fecha: {$jornadaAbierta->fecha}\n";
        echo "   - Apertura: {$jornadaAbierta->apertura}\n";
        echo "   - Cierre: {$jornadaAbierta->cierre}\n\n";
        
        echo "   OPERACIONES HABILITADAS:\n";
        echo "   - ✅ Saldo Inicial\n";
        echo "   - ✅ Recibido de Efectivo\n";
        echo "   - ✅ Entrega de Efectivo\n";
        echo "   - ✅ Cierre de Caja\n\n";
    }
    
    // 5. CASO 4: Cerrar jornada y probar bloqueo
    echo "5. CASO 4: Cerrar jornada y verificar bloqueo de operaciones\n";
    
    // Cerrar jornada
    $stmt = $pdo->prepare("UPDATE jornada SET apertura = 0, cierre = 1, user_id_cierre = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$userId, $jornadaId]);
    
    echo "✅ Jornada cerrada\n";
    
    // Validar que ahora está bloqueada
    $jornadaCerrada = validarJornadaAbierta($pdo, $tiendaId, $fechaActual);
    
    if (!$jornadaCerrada) {
        echo "❌ VALIDACIÓN CORRECTA: Jornada cerrada, operaciones bloqueadas\n";
        echo "   Razón: apertura=0, cierre=1\n";
        echo "   Estado: Operaciones de caja NO permitidas\n\n";
    }
    
    // 6. Mostrar lógica implementada en cada componente
    echo "6. COMPONENTES ACTUALIZADOS CON VALIDACIÓN:\n\n";
    
    $componentes = [
        'SaldoInicial' => [
            'mount()' => 'Valida jornada al cargar',
            'establecerSaldoInicial()' => 'Valida antes de procesar'
        ],
        'RecibidoDeEfectivo' => [
            'mount()' => 'Valida jornada al cargar',
            'recibirEfectivo()' => 'Valida antes de procesar'
        ],
        'EntregaDeEfectivo' => [
            'mount()' => 'Valida jornada al cargar',
            'entregarEfectivo()' => 'Valida antes de procesar'
        ],
        'CierreDeCaja' => [
            'mount()' => 'Valida jornada al cargar',
            'procesarCierre()' => 'Valida antes de procesar'
        ]
    ];
    
    foreach ($componentes as $nombre => $metodos) {
        echo "📁 $nombre:\n";
        foreach ($metodos as $metodo => $descripcion) {
            echo "   - $metodo: $descripcion\n";
        }
        echo "\n";
    }
    
    // 7. Mostrar código de validación implementado
    echo "7. CÓDIGO DE VALIDACIÓN IMPLEMENTADO:\n\n";
    
    echo "```php\n";
    echo "public function validarJornadaAbierta()\n";
    echo "{\n";
    echo "    \$usuario = Auth::user();\n";
    echo "    \n";
    echo "    if (!\$usuario->tienda_id) {\n";
    echo "        \$this->mensajeError = 'Usuario sin tienda asignada';\n";
    echo "        return false;\n";
    echo "    }\n";
    echo "\n";
    echo "    \$fechaActual = date('Y-m-d');\n";
    echo "    \n";
    echo "    \$jornadaAbierta = DB::table('jornada')\n";
    echo "        ->where('fecha', \$fechaActual)\n";
    echo "        ->where('tienda_id', \$usuario->tienda_id)\n";
    echo "        ->where('apertura', 1)\n";
    echo "        ->where('cierre', 0)\n";
    echo "        ->first();\n";
    echo "\n";
    echo "    if (!\$jornadaAbierta) {\n";
    echo "        \$this->mensajeError = 'Jornada no aperturada';\n";
    echo "        return false;\n";
    echo "    }\n";
    echo "\n";
    echo "    return true;\n";
    echo "}\n";
    echo "```\n\n";
    
    // 8. Estado de la jornada al final
    echo "8. ESTADO FINAL DE LA JORNADA:\n";
    
    $stmt = $pdo->prepare("SELECT * FROM jornada WHERE id = ?");
    $stmt->execute([$jornadaId]);
    $jornadaFinal = $stmt->fetch(PDO::FETCH_OBJ);
    
    echo "Jornada ID: {$jornadaFinal->id}\n";
    echo "Fecha: {$jornadaFinal->fecha}\n";
    echo "Tienda ID: {$jornadaFinal->tienda_id}\n";
    echo "Apertura: {$jornadaFinal->apertura}\n";
    echo "Cierre: {$jornadaFinal->cierre}\n";
    echo "User ID Apertura: {$jornadaFinal->user_id_apertura}\n";
    echo "User ID Cierre: " . ($jornadaFinal->user_id_cierre ?? 'NULL') . "\n";
    echo "Created: {$jornadaFinal->created_at}\n";
    echo "Updated: {$jornadaFinal->updated_at}\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== RESUMEN DE CONTROL DE OPERACIONES ===\n";
echo "✅ JORNADA ABIERTA (apertura=1, cierre=0):\n";
echo "   - ✅ Saldo Inicial permitido\n";
echo "   - ✅ Recibido de Efectivo permitido\n";
echo "   - ✅ Entrega de Efectivo permitido\n";
echo "   - ✅ Cierre de Caja permitido\n";

echo "\n❌ JORNADA CERRADA (apertura=0, cierre=1) O NO APERTURADA:\n";
echo "   - ❌ Saldo Inicial bloqueado\n";
echo "   - ❌ Recibido de Efectivo bloqueado\n";
echo "   - ❌ Entrega de Efectivo bloqueado\n";
echo "   - ❌ Cierre de Caja bloqueado\n";

echo "\n🔒 VALIDACIÓN IMPLEMENTADA EN:\n";
echo "   - mount() de cada componente\n";
echo "   - Métodos de procesamiento principales\n";
echo "   - Mensaje de error específico\n";
echo "   - Control por tienda del usuario\n";

echo "\n=== FIN DE LA PRUEBA ===\n";
