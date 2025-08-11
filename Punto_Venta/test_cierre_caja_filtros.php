<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Database\Capsule\Manager as DB;

// Configuración de la base de datos
$capsule = new DB;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'],
    'database'  => $_ENV['DB_DATABASE'],
    'username'  => $_ENV['DB_USERNAME'],
    'password'  => $_ENV['DB_PASSWORD'],
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== TEST FILTROS CIERRE DE CAJA ===\n\n";

// Simulamos un usuario de la tienda 1
$usuario_id = 1;
$tienda_id = 1;

echo "Usuario ID: $usuario_id\n";
echo "Tienda ID: $tienda_id\n\n";

// 1. Verificar cajas disponibles por tienda
echo "1. CAJAS POR TIENDA:\n";
$cajas = DB::table('caja')
    ->where('users_id', $usuario_id)
    ->where('tienda_id', $tienda_id)
    ->get();

foreach ($cajas as $caja) {
    echo "Caja ID: {$caja->id}, Usuario: {$caja->users_id}, Tienda: {$caja->tienda_id}, Estado: {$caja->estado_caja}, Balance: {$caja->balance}\n";
}

// 2. Método actual de cargarDatosCaja()
echo "\n2. MÉTODO ACTUAL cargarDatosCaja():\n";
$cajaActual = DB::table('caja')
    ->where('users_id', $usuario_id)
    ->where('tienda_id', $tienda_id)
    ->where('estado_caja', 1) // 1 = abierta
    ->first();

if ($cajaActual) {
    echo "Caja encontrada - ID: {$cajaActual->id}, Usuario: {$cajaActual->users_id}, Tienda: {$cajaActual->tienda_id}\n";
    
    // 3. Verificar transacciones de esa caja
    echo "\n3. TRANSACCIONES DE LA CAJA:\n";
    $fechaHoy = date('Y-m-d');
    $transacciones = DB::table('transaccion')
        ->where('caja_id', $cajaActual->id)
        ->whereDate('created_at', $fechaHoy)
        ->get();
    
    echo "Fecha: $fechaHoy\n";
    echo "Total transacciones encontradas: " . count($transacciones) . "\n";
    
    foreach ($transacciones as $trans) {
        echo "Trans ID: {$trans->id}, Caja: {$trans->caja_id}, Tipo: {$trans->tipo_transaccion}, Monto: {$trans->monto}, Fecha: {$trans->created_at}\n";
    }
    
    // 4. Verificar resumen de transacciones
    echo "\n4. RESUMEN TRANSACCIONES:\n";
    $resumen = DB::table('transaccion')
        ->where('caja_id', $cajaActual->id)
        ->whereDate('created_at', $fechaHoy)
        ->selectRaw('
            SUM(CASE WHEN tipo_transaccion = "venta" THEN monto ELSE 0 END) as total_ventas,
            SUM(CASE WHEN tipo_transaccion = "recibido_efectivo" THEN monto ELSE 0 END) as total_recibido,
            SUM(CASE WHEN tipo_transaccion = "entrega_efectivo" THEN monto ELSE 0 END) as total_entrega,
            SUM(CASE WHEN tipo_transaccion = "diferencia_positiva" THEN monto ELSE 0 END) as total_dif_positiva,
            SUM(CASE WHEN tipo_transaccion = "diferencia_negativa" THEN monto ELSE 0 END) as total_dif_negativa,
            COUNT(*) as total_transacciones
        ')
        ->first();
    
    if ($resumen) {
        echo "Total Ventas: {$resumen->total_ventas}\n";
        echo "Total Recibido: {$resumen->total_recibido}\n";
        echo "Total Entrega: {$resumen->total_entrega}\n";
        echo "Total Dif. Positiva: {$resumen->total_dif_positiva}\n";
        echo "Total Dif. Negativa: {$resumen->total_dif_negativa}\n";
        echo "Total Transacciones: {$resumen->total_transacciones}\n";
    }
    
} else {
    echo "No se encontró caja abierta para el usuario en esta tienda\n";
}

// 5. Verificar si hay cajas de otras tiendas que podrían estar interfiriendo
echo "\n5. VERIFICAR OTRAS TIENDAS:\n";
$otras_cajas = DB::table('caja')
    ->where('users_id', $usuario_id)
    ->where('tienda_id', '!=', $tienda_id)
    ->get();

echo "Cajas del usuario en otras tiendas: " . count($otras_cajas) . "\n";
foreach ($otras_cajas as $caja) {
    echo "Caja ID: {$caja->id}, Usuario: {$caja->users_id}, Tienda: {$caja->tienda_id}, Estado: {$caja->estado_caja}\n";
}

// 6. Verificar todas las tiendas disponibles
echo "\n6. TODAS LAS TIENDAS:\n";
$tiendas = DB::table('tienda')->get();
foreach ($tiendas as $tienda) {
    echo "Tienda ID: {$tienda->id}, Nombre: {$tienda->nombre}\n";
}

echo "\n=== FIN TEST ===\n";
