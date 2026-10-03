<?php

namespace Tests\Critical;

use App\Models\User;
use Tests\Support\CriticalDatabaseTestCase;

class LayoutAssetTest extends CriticalDatabaseTestCase
{
    public function test_cancelled_invoice_pdf_uses_invalid_title(): void
    {
        $template = file_get_contents(resource_path('views/pdf/factura.blade.php'));

        $this->assertStringContainsString("\$factura->estado_factura_id == 2", $template);
        $this->assertStringContainsString('FACTURA ANULADA - SIN VALIDEZ', $template);
        $this->assertStringContainsString("'FACTURA VENTA'", $template);
    }

    public function test_login_uses_current_vite_assets(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = $manifest['resources/css/app.css']['file'];
        $js = $manifest['resources/js/app.js']['file'];

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('/build/' . $css, false);
        $response->assertSee('/build/' . $js, false);
        $response->assertDontSee('app-poWAtTx1.css', false);
        $response->assertDontSee('app-BLl8G-P3.js', false);
        $this->assertFileExists(public_path('build/' . $css));
        $this->assertFileExists(public_path('build/' . $js));
    }

    public function test_authenticated_layout_uses_unified_table_initializer(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('jquery.dataTables.min.js', false);
        $response->assertSee('initializeVisibleTables', false);
        $response->assertDontSee('TablasBoostrap/listafacturas.js', false);
        $response->assertDontSee('TablasBoostrap/productos.js', false);
        $response->assertSee("navegarVista('dashboard')", false);
        $response->assertSee('peticionesLivewirePendientes', false);
        $response->assertSee("Livewire.dispatch('cambiarVista', [ruta])", false);
        $response->assertSee('x-on:cargando-vista.window="iniciarCarga()"', false);
        $response->assertSee('x-on:vista-cargada.window="finalizarCarga()"', false);
        $response->assertSee("Livewire.hook('commit'", false);
        $response->assertSee("component.name !== 'dynamic-content'", false);
        $response->assertSee('style="display: none;"', false);
        $response->assertDontSee('pedidos-badge', false);
        $response->assertDontSee('/pedidos-web/api/pendientes', false);
        $response->assertDontSee('/pedidos-web/api/unread-count', false);
        $response->assertDontSee('Notification.requestPermission()', false);
        $response->assertDontSee('setInterval(updatePedidosBadgeConAlertas', false);
    }
}