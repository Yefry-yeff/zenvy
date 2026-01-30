<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WebInventorySyncService;

class TestInventoryWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:test-inventory {tipo=stock}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Probar el envío de webhooks de inventario (stock, compra, venta, anulacion, completo)';

    /**
     * Execute the console command.
     */
    public function handle(WebInventorySyncService $service)
    {
        $this->info('🔧 Probando Webhooks de Inventario...');
        $this->newLine();

        // Verificar configuración
        if (!$service->estaConfigurado()) {
            $this->error('❌ WEBHOOK NO CONFIGURADO');
            $this->warn('Asegúrate de configurar en tu .env:');
            $this->line('   WEBHOOK_URL=https://tu-ecommerce.com/api/webhooks/inventario');
            $this->line('   WEBHOOK_TOKEN=tu_token_secreto');
            return 1;
        }

        $this->info('✅ Webhook configurado: ' . $service->obtenerWebhookUrl());
        $this->newLine();

        $tipo = $this->argument('tipo');

        switch ($tipo) {
            case 'stock':
                $this->testCambioStock($service);
                break;

            case 'compra':
                $this->testCompraRecibida($service);
                break;

            case 'venta':
                $this->testVenta($service);
                break;

            case 'anulacion':
                $this->testAnulacion($service);
                break;

            case 'completo':
                $this->testSincronizacionCompleta($service);
                break;

            default:
                $this->error("Tipo de prueba no válido: {$tipo}");
                $this->info('Tipos disponibles: stock, compra, venta, anulacion, completo');
                return 1;
        }

        $this->newLine();
        $this->info('📋 Revisa los logs para ver el resultado:');
        $this->line('   tail -f storage/logs/laravel.log');
        $this->newLine();
        $this->info('🎯 O busca por:');
        $this->line('   📦 WEBHOOK INVENTARIO - Para ver eventos preparados');
        $this->line('   🔄 JOB WEBHOOK - Para ver procesamiento de cola');
        $this->line('   ✅ Enviado exitosamente - Para confirmación');

        return 0;
    }

    protected function testCambioStock(WebInventorySyncService $service)
    {
        $this->info('📦 Enviando webhook de prueba: CAMBIO DE STOCK');
        
        $resultado = $service->sincronizarCambioStock(
            productoId: 999,
            nombreProducto: 'Producto de Prueba',
            stockAnterior: 10,
            stockActual: 8,
            razon: 'prueba',
            detalles: [
                'test' => true,
                'timestamp' => now()->toDateTimeString()
            ]
        );

        if ($resultado) {
            $this->info('✅ Webhook despachado correctamente');
        } else {
            $this->error('❌ Error al despachar webhook');
        }
    }

    protected function testCompraRecibida(WebInventorySyncService $service)
    {
        $this->info('📥 Enviando webhook de prueba: COMPRA RECIBIDA');
        
        $resultado = $service->sincronizarCompraRecibida(
            productoId: 999,
            nombreProducto: 'Producto de Prueba',
            cantidadRecibida: 50,
            detalles: [
                'numero_compra' => 'C-TEST-001',
                'proveedor' => 'Proveedor de Prueba',
                'test' => true
            ]
        );

        if ($resultado) {
            $this->info('✅ Webhook despachado correctamente');
        } else {
            $this->error('❌ Error al despachar webhook');
        }
    }

    protected function testVenta(WebInventorySyncService $service)
    {
        $this->info('💰 Enviando webhook de prueba: VENTA REALIZADA');
        
        $resultado = $service->sincronizarVenta(
            facturaId: 888,
            items: [
                [
                    'producto_id' => 999,
                    'nombre' => 'Producto de Prueba 1',
                    'cantidad' => 2,
                    'precio' => 50000
                ],
                [
                    'producto_id' => 998,
                    'nombre' => 'Producto de Prueba 2',
                    'cantidad' => 1,
                    'precio' => 25000
                ]
            ],
            total: 125000,
            detalles: [
                'cliente' => 'Cliente de Prueba',
                'test' => true
            ]
        );

        if ($resultado) {
            $this->info('✅ Webhook despachado correctamente');
        } else {
            $this->error('❌ Error al despachar webhook');
        }
    }

    protected function testAnulacion(WebInventorySyncService $service)
    {
        $this->info('❌ Enviando webhook de prueba: FACTURA ANULADA');
        
        $resultado = $service->sincronizarAnulacionFactura(
            facturaId: 888,
            items: [
                [
                    'producto_id' => 999,
                    'nombre' => 'Producto de Prueba 1',
                    'cantidad' => 2
                ],
                [
                    'producto_id' => 998,
                    'nombre' => 'Producto de Prueba 2',
                    'cantidad' => 1
                ]
            ],
            detalles: [
                'motivo' => 'Prueba de anulación',
                'test' => true
            ]
        );

        if ($resultado) {
            $this->info('✅ Webhook despachado correctamente');
        } else {
            $this->error('❌ Error al despachar webhook');
        }
    }

    protected function testSincronizacionCompleta(WebInventorySyncService $service)
    {
        $this->info('🔄 Enviando webhook de prueba: SINCRONIZACIÓN COMPLETA');
        
        $inventario = [
            [
                'id' => 1,
                'nombre' => 'Categoría de Prueba',
                'total_productos' => 2,
                'stock_total' => 150,
                'productos' => [
                    [
                        'id' => 999,
                        'nombre' => 'Producto de Prueba 1',
                        'codigo_barras' => '7890123456789',
                        'stock_disponible' => 100,
                        'precio_venta' => 50000
                    ],
                    [
                        'id' => 998,
                        'nombre' => 'Producto de Prueba 2',
                        'codigo_barras' => '7890123456790',
                        'stock_disponible' => 50,
                        'precio_venta' => 25000
                    ]
                ]
            ]
        ];

        $resultado = $service->sincronizarInventarioCompleto($inventario);

        if ($resultado) {
            $this->info('✅ Webhook despachado correctamente');
        } else {
            $this->error('❌ Error al despachar webhook');
        }
    }
}
