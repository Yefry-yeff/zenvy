<?php

// Simular los datos de la prueba que mencionas
$metodosActivosParaPago = [
    [
        'id' => 1,
        'nombre' => 'Efectivo',
        'monto' => 10
    ],
    [
        'id' => 2,
        'nombre' => 'Tarjeta',
        'monto' => 102
    ]
];

$montosPorMetodo = [
    1 => 10,
    2 => 102
];

echo 'Métodos activos:' . PHP_EOL;
foreach($metodosActivosParaPago as $metodo) {
    echo 'ID: ' . $metodo['id'] . ', Nombre: ' . $metodo['nombre'] . ', Monto: ' . $metodo['monto'] . PHP_EOL;
}

echo PHP_EOL . 'Montos por método:' . PHP_EOL;
foreach($montosPorMetodo as $tipoId => $monto) {
    if ($monto > 0) {
        echo 'Tipo ID: ' . $tipoId . ', Monto: ' . $monto . PHP_EOL;
    }
}

// Simulación de la lógica de guardarMetodosPagoDistribucion
echo PHP_EOL . 'Simulación de guardarMetodosPagoDistribucion:' . PHP_EOL;

$metodosParaGuardar = [];

// Prioridad 1: Si hay metodosActivosParaPago, usarlos (solo los que tienen monto > 0)
if (!empty($metodosActivosParaPago)) {
    echo "Usando metodosActivosParaPago" . PHP_EOL;
    
    foreach ($metodosActivosParaPago as $metodo) {
        if ($metodo['monto'] > 0) { // Solo los que tienen monto mayor a 0
            $metodosParaGuardar[] = $metodo;
            
            echo "Método agregado desde metodosActivosParaPago - ID: {$metodo['id']}, Nombre: {$metodo['nombre']}, Monto: {$metodo['monto']}" . PHP_EOL;
        }
    }
}

echo PHP_EOL . 'Métodos finales para guardar:' . PHP_EOL;
foreach ($metodosParaGuardar as $metodo) {
    echo "Método para guardar - ID: {$metodo['id']}, Nombre: {$metodo['nombre']}, Monto: {$metodo['monto']}" . PHP_EOL;
}

echo PHP_EOL . 'Cantidad de métodos a guardar: ' . count($metodosParaGuardar) . PHP_EOL;
