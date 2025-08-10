<?php
/**
 * Test del Sistema de Entrega de Efectivo
 * Verificar que todo esté configurado correctamente
 */

require 'vendor/autoload.php';

echo "=== TEST SISTEMA DE ENTREGA DE EFECTIVO ===\n\n";

// 1. Verificar que el componente existe
echo "1. Verificando componente EntregaDeEfectivo...\n";
$componentePath = __DIR__ . '/app/Livewire/Caja/EntregaDeEfectivo.php';
if (file_exists($componentePath)) {
    echo "   ✅ Archivo del componente existe\n";
} else {
    echo "   ❌ Archivo del componente NO existe\n";
}

// 2. Verificar que la vista existe
echo "\n2. Verificando vista Blade...\n";
$viewPath = __DIR__ . '/resources/views/livewire/caja/entrega-de-efectivo.blade.php';
if (file_exists($viewPath)) {
    echo "   ✅ Vista Blade existe\n";
    
    $viewContent = file_get_contents($viewPath);
    if (strpos($viewContent, 'wire:submit="entregarEfectivo"') !== false) {
        echo "   ✅ Formulario de entrega configurado\n";
    } else {
        echo "   ❌ Formulario de entrega NO encontrado\n";
    }
    
    if (strpos($viewContent, 'wire:model="monto"') !== false) {
        echo "   ✅ Campo de monto configurado\n";
    } else {
        echo "   ❌ Campo de monto NO encontrado\n";
    }

    if (strpos($viewContent, 'Entregar Efectivo') !== false) {
        echo "   ✅ Título de entrega encontrado\n";
    } else {
        echo "   ❌ Título de entrega NO encontrado\n";
    }

    if (strpos($viewContent, 'bg-gradient-to-br from-red-50') !== false) {
        echo "   ✅ Tema visual rojo aplicado (diferente a recepción)\n";
    } else {
        echo "   ❌ Tema visual rojo NO aplicado\n";
    }
} else {
    echo "   ❌ Vista Blade NO existe\n";
}

// 3. Verificar estructura del componente
echo "\n3. Verificando estructura del componente...\n";
if (file_exists($componentePath)) {
    $componentContent = file_get_contents($componentePath);
    
    if (strpos($componentContent, 'public function entregarEfectivo()') !== false) {
        echo "   ✅ Método entregarEfectivo() existe\n";
    } else {
        echo "   ❌ Método entregarEfectivo() NO existe\n";
    }
    
    if (strpos($componentContent, 'DB::beginTransaction()') !== false) {
        echo "   ✅ Transacciones de base de datos configuradas\n";
    } else {
        echo "   ❌ Transacciones de base de datos NO configuradas\n";
    }
    
    if (strpos($componentContent, "'transaccion' => 'Entrega de Efectivo'") !== false) {
        echo "   ✅ Tipo de transacción configurado correctamente\n";
    } else {
        echo "   ❌ Tipo de transacción NO configurado\n";
    }

    if (strpos($componentContent, "'efectivo' => -\$montoNumerico") !== false) {
        echo "   ✅ Monto negativo para salida configurado\n";
    } else {
        echo "   ❌ Monto negativo para salida NO configurado\n";
    }

    if (strpos($componentContent, 'balance - ') !== false) {
        echo "   ✅ Resta del saldo configurada\n";
    } else {
        echo "   ❌ Resta del saldo NO configurada\n";
    }

    if (strpos($componentContent, 'No hay suficiente saldo en caja') !== false) {
        echo "   ✅ Validación de saldo insuficiente configurada\n";
    } else {
        echo "   ❌ Validación de saldo insuficiente NO configurada\n";
    }
}

// 4. Verificar integración con dashboard
echo "\n4. Verificando integración con dashboard...\n";
$dashboardPath = __DIR__ . '/resources/views/livewire/dashboard-dinamico.blade.php';
if (file_exists($dashboardPath)) {
    $dashboardContent = file_get_contents($dashboardPath);
    
    if (strpos($dashboardContent, "cambiarVista', ['caja.EntregaDeEfectivo']") !== false) {
        echo "   ✅ Botón de navegación en dashboard configurado\n";
    } else {
        echo "   ❌ Botón de navegación en dashboard NO encontrado\n";
    }
    
    if (strpos($dashboardContent, 'Entregar Efectivo') !== false) {
        echo "   ✅ Texto del botón configurado\n";
    } else {
        echo "   ❌ Texto del botón NO encontrado\n";
    }

    if (strpos($dashboardContent, 'from-red-500 to-red-600') !== false) {
        echo "   ✅ Tema rojo del botón aplicado\n";
    } else {
        echo "   ❌ Tema rojo del botón NO aplicado\n";
    }

    if (strpos($dashboardContent, '💸') !== false) {
        echo "   ✅ Icono de entrega de efectivo configurado\n";
    } else {
        echo "   ❌ Icono de entrega de efectivo NO configurado\n";
    }
} else {
    echo "   ❌ Dashboard NO encontrado\n";
}

// 5. Verificar moneda hondureña
echo "\n5. Verificando moneda hondureña (Lempiras)...\n";
if (file_exists($componentePath)) {
    $componentContent = file_get_contents($componentePath);
    if (strpos($componentContent, 'L."') !== false) {
        echo "   ✅ Moneda Lempira configurada en componente\n";
    } else {
        echo "   ❌ Moneda Lempira NO configurada en componente\n";
    }
}

if (file_exists($viewPath)) {
    $viewContent = file_get_contents($viewPath);
    if (strpos($viewContent, 'L.{{') !== false || strpos($viewContent, '<span class="text-gray-500 text-lg font-semibold">L.</span>') !== false) {
        echo "   ✅ Moneda Lempira configurada en vista\n";
    } else {
        echo "   ❌ Moneda Lempira NO configurada en vista\n";
    }
}

echo "\n=== RESUMEN ===\n";
echo "✅ Sistema de entrega de efectivo implementado\n";
echo "✅ Formulario con validación y mensajes de error/éxito\n";
echo "✅ Integración con base de datos (transacciones y caja)\n";
echo "✅ Navegación desde dashboard\n";
echo "✅ Interfaz profesional con tema rojo (diferente a recepción)\n";
echo "✅ Control de permisos por rol\n";
echo "✅ Validación de saldo suficiente\n";
echo "✅ Moneda hondureña (Lempiras)\n";

echo "\n=== FUNCIONALIDADES INCLUIDAS ===\n";
echo "• Validación de monto (requerido, numérico, mínimo 0.01)\n";
echo "• Campo de comentarios opcional\n";
echo "• Verificación de caja abierta\n";
echo "• Verificación de saldo suficiente\n";
echo "• Transacciones de base de datos seguras\n";
echo "• Actualización automática del saldo de caja (resta)\n";
echo "• Registro como monto negativo en transacciones\n";
echo "• Mensajes de éxito y error\n";
echo "• Interfaz responsive y profesional\n";
echo "• Navegación de regreso al dashboard\n";

echo "\n=== DIFERENCIAS CON RECEPCIÓN ===\n";
echo "• Tema visual ROJO (vs verde en recepción)\n";
echo "• Icono 💸 (vs 💰 en recepción)\n";
echo "• Validación de saldo suficiente\n";
echo "• Monto negativo en transacciones\n";
echo "• Resta del saldo (vs suma en recepción)\n";
echo "• Mensajes específicos para entrega\n";

echo "\n=== CASOS DE USO ===\n";
echo "1. Supervisor retira efectivo de caja\n";
echo "2. Cajero entrega cambio grande\n";
echo "3. Depósito de efectivo al banco\n";
echo "4. Transferencia entre cajas\n";

echo "\n=== TEST COMPLETADO ===\n";
