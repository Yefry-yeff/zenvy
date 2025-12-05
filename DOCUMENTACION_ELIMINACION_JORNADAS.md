# 🔄 ZENVY POS - ELIMINACIÓN DEL SISTEMA DE JORNADAS
## Transformación a Sistema Simplificado de Cierre de Caja

---

## 📋 RESUMEN EJECUTIVO

### Cambio Principal
**ANTES:** Sistema complejo con Jornadas → Apertura de Caja → Ventas → Cierre de Caja → Cierre de Jornada  
**DESPUÉS:** Sistema simplificado: Caja Fija (L. 2,000.00) → Ventas → Cierre de Caja

### Objetivos
1. ✅ Eliminar completamente el concepto de "jornada"
2. ✅ Permitir facturar libremente sin validaciones de jornada
3. ✅ Mantener caja inicial fija de L. 2,000.00
4. ✅ Simplificar el flujo a solo "Cierre de Caja"
5. ✅ Mantener auditoría y registros históricos

---

## 🎯 NUEVO FLUJO DEL SISTEMA

### Flujo Operativo Simplificado

```
┌─────────────────────────────────────┐
│   CAJA SIEMPRE ACTIVA               │
│   Saldo Inicial: L. 2,000.00        │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   VENTAS LIBRES                     │
│   • Sin validación de jornada       │
│   • Sin validación de caja abierta  │
│   • Registro directo de transacciones│
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   CIERRE DE CAJA (Cuando se desee)  │
│   • Imprime reporte                 │
│   • Registra el cierre              │
│   • Resetea caja a L. 2,000.00      │
│   • Guarda auditoría                │
└─────────────────────────────────────┘
```

### Reglas del Nuevo Sistema

1. **Caja Inicial Fija**
   - Cada caja inicia automáticamente con L. 2,000.00
   - No requiere apertura manual
   - Estado permanente: ACTIVA

2. **Ventas Sin Restricciones**
   - No valida jornada abierta
   - No valida caja abierta
   - Solo valida que el usuario tenga tienda asignada

3. **Cierre de Caja**
   - Se ejecuta cuando el usuario lo solicite
   - Genera reporte del período
   - Registra: fecha, hora, usuario, montos, diferencias
   - Automáticamente restablece saldo a L. 2,000.00

4. **Auditoría**
   - Cada cierre queda registrado en tabla `cierre_caja_historico`
   - Se mantiene historial de todas las transacciones
   - Se pueden generar reportes históricos

---

## 📁 ARCHIVOS QUE SE MODIFICARÁN

### 1. Componentes Livewire a Modificar

| Archivo | Acción | Descripción |
|---------|--------|-------------|
| `app/Livewire/SalaDeVentas/Ventas.php` | ✏️ MODIFICAR | Eliminar validación `validarJornadaYCaja()` |
| `app/Livewire/Caja/SaldoInicial.php` | ✏️ MODIFICAR | Eliminar validación de jornada abierta |
| `app/Livewire/Caja/CierreDeCaja.php` | 🔄 REHACER | Simplificar lógica, eliminar dependencia de jornada |
| `app/Livewire/DashboardDinamico.php` | ✏️ MODIFICAR | Eliminar `cargarEstadoJornada()` |
| `app/Livewire/GestionDeSucursales/AperturaDeJornada.php` | ❌ DEPRECAR | Componente completo obsoleto |
| `app/Livewire/GestionDeSucursales/CierreDeJornada.php` | ❌ DEPRECAR | Componente completo obsoleto |

### 2. Vistas Blade a Modificar

| Archivo | Acción | Descripción |
|---------|--------|-------------|
| `resources/views/livewire/sala-de-ventas/ventas.blade.php` | ✏️ MODIFICAR | Eliminar alertas de jornada |
| `resources/views/livewire/caja/cierre-de-caja.blade.php` | 🔄 REHACER | Nueva interfaz simplificada |
| `resources/views/livewire/caja/saldo-inicial.blade.php` | ✏️ MODIFICAR | Mostrar saldo fijo |
| `resources/views/livewire/gestion-de-sucursales/apertura-de-jornada.blade.php` | ❌ DEPRECAR | Vista obsoleta |
| `resources/views/livewire/gestion-de-sucursales/cierre-de-jornada.blade.php` | ❌ DEPRECAR | Vista obsoleta |

### 3. Rutas a Modificar/Eliminar

En `routes/web.php`:
- ❌ Eliminar rutas de apertura de jornada
- ❌ Eliminar rutas de cierre de jornada
- ✅ Mantener/modificar ruta de cierre de caja

### 4. Migraciones Nuevas

```sql
-- Crear tabla para histórico de cierres de caja
CREATE TABLE cierre_caja_historico (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tienda_id BIGINT UNSIGNED NOT NULL,
    fecha_cierre DATETIME NOT NULL,
    periodo_inicio DATETIME,
    periodo_fin DATETIME,
    total_efectivo_sistema DECIMAL(10,2),
    total_efectivo_contado DECIMAL(10,2),
    diferencia DECIMAL(10,2),
    total_tarjeta DECIMAL(10,2),
    total_transferencia DECIMAL(10,2),
    total_cheque DECIMAL(10,2),
    total_general DECIMAL(10,2),
    cantidad_facturas INT,
    observaciones TEXT,
    desglose_billetes JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (tienda_id) REFERENCES tienda(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 💻 CÓDIGO DE LOS CAMBIOS

### CAMBIO 1: Ventas.php - Eliminar Validación de Jornada

**Archivo:** `app/Livewire/SalaDeVentas/Ventas.php`

**ANTES (líneas ~2001-2068):**
```php
// Validar jornada y caja antes de permitir procesar pago
if (!$this->validarJornadaYCaja()) {
    return;
}

private function validarJornadaYCaja()
{
    // 1. Verificar jornada (apertura = 1 y cierre = 0)
    $jornadaAbierta = DB::table('jornada')
        ->where('tienda_id', Auth::user()->tienda_id)
        ->where('apertura', 1)
        ->where('cierre', 0)
        ->first();

    if (!$jornadaAbierta) {
        session()->flash('error', '❌ No se puede procesar la venta...');
        return false;
    }
    // ... más validaciones
}
```

**DESPUÉS:**
```php
// NUEVA VALIDACIÓN SIMPLIFICADA
private function validarUsuarioTienda()
{
    $usuario = Auth::user();
    
    if (!$usuario || !$usuario->tienda_id) {
        session()->flash('error', '❌ Usuario sin tienda asignada. No se pueden procesar ventas.');
        return false;
    }
    
    return true;
}

// En el método de procesamiento de pago, reemplazar:
// if (!$this->validarJornadaYCaja()) { return; }
// POR:
if (!$this->validarUsuarioTienda()) { return; }
```

---

### CAMBIO 2: SaldoInicial.php - Mostrar Saldo Fijo

**Archivo:** `app/Livewire/Caja/SaldoInicial.php`

**CAMBIOS:**
1. Eliminar método `validarJornadaAbierta()`
2. Modificar `mount()` para mostrar saldo fijo
3. Actualizar vista para mostrar estado permanente

**CÓDIGO NUEVO:**
```php
<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaldoInicial extends Component
{
    public $saldoInicial = 2000.00; // Saldo fijo del sistema
    public $cajaActual;
    public $mensaje = '';
    public $tipoMensaje = 'info';

    public function mount()
    {
        $this->cargarInformacionCaja();
    }

    public function cargarInformacionCaja()
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->tienda_id) {
            $this->mensaje = 'Usuario sin tienda asignada.';
            $this->tipoMensaje = 'error';
            return;
        }

        // Buscar o crear registro de caja
        $this->cajaActual = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->first();

        if (!$this->cajaActual) {
            // Crear registro de caja con saldo inicial
            DB::table('caja')->insert([
                'users_id' => $usuario->id,
                'tienda_id' => $usuario->tienda_id,
                'balance' => $this->saldoInicial,
                'estado_caja' => 1, // Siempre activa
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->cajaActual = DB::table('caja')
                ->where('users_id', $usuario->id)
                ->where('tienda_id', $usuario->tienda_id)
                ->first();
        }

        $this->mensaje = 'Su caja está siempre activa con un saldo inicial de L. ' . number_format($this->saldoInicial, 2);
        $this->tipoMensaje = 'success';
    }

    public function render()
    {
        return view('livewire.caja.saldo-inicial');
    }
}
```

---

### CAMBIO 3: CierreDeCaja.php - Versión Simplificada

**Archivo:** `app/Livewire/Caja/CierreDeCaja.php`

**CÓDIGO COMPLETO NUEVO:**

```php
<?php

namespace App\Livewire\Caja;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CierreDeCaja extends Component
{
    public $cajaActual = null;
    public $resumenTransacciones = [];
    public $desglose_entradas = [];
    public $desgloseTarjetas = [];
    public $desgloseTransferencias = [];
    public $desgloseCheques = [];

    // Billetes
    public $billetes_500 = 0;
    public $billetes_200 = 0;
    public $billetes_100 = 0;
    public $billetes_50 = 0;
    public $billetes_20 = 0;
    public $billetes_10 = 0;
    public $billetes_5 = 0;
    public $billetes_2 = 0;
    public $billetes_1 = 0;

    // Monedas
    public $monedas_0_50 = 0;
    public $monedas_0_20 = 0;
    public $monedas_0_10 = 0;
    public $monedas_0_05 = 0;
    public $monedas_0_02 = 0;
    public $monedas_0_01 = 0;

    // Cálculos
    public $totalContado = 0;
    public $diferenciaEfectivo = 0;
    public $totalSistema = 0;
    public $observaciones = '';

    // Mensajes
    public $mensajeExito = '';
    public $mensajeError = '';
    public $cierreProcesado = false;

    const SALDO_INICIAL = 2000.00;

    public function mount()
    {
        $this->cargarDatosCaja();
        $this->calcularResumen();
    }

    public function cargarDatosCaja()
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->tienda_id) {
            $this->cajaActual = null;
            return;
        }

        $this->cajaActual = DB::table('caja')
            ->where('users_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->first();
    }

    public function calcularResumen()
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->tienda_id) {
            return;
        }

        // Obtener último cierre
        $ultimoCierre = DB::table('cierre_caja_historico')
            ->where('user_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id)
            ->orderBy('fecha_cierre', 'desc')
            ->first();

        $fechaInicio = $ultimoCierre ? $ultimoCierre->fecha_cierre : null;

        // Resumen de transacciones desde el último cierre
        $queryBase = DB::table('factura as f')
            ->join('formas_pago as fp', 'f.formas_pago_id', '=', 'fp.id')
            ->where('f.user_id', $usuario->id)
            ->where('f.tienda_id', $usuario->tienda_id);

        if ($fechaInicio) {
            $queryBase->where('f.created_at', '>', $fechaInicio);
        }

        $this->resumenTransacciones = $queryBase
            ->select(
                'fp.denominacion_social as forma_pago',
                DB::raw('COUNT(f.id) as cantidad'),
                DB::raw('SUM(f.total) as total')
            )
            ->groupBy('fp.id', 'fp.denominacion_social')
            ->get();

        // Calcular total del sistema
        $this->totalSistema = $this->resumenTransacciones->sum('total');

        // Desgloses específicos
        $this->cargarDesgloseEntradas($fechaInicio);
        $this->cargarDesgloseTarjetas($fechaInicio);
        $this->cargarDesgloseTransferencias($fechaInicio);
        $this->cargarDesgloseCheques($fechaInicio);
    }

    private function cargarDesgloseEntradas($fechaInicio)
    {
        $usuario = Auth::user();
        
        $query = DB::table('entradas_caja')
            ->where('user_id', $usuario->id)
            ->where('tienda_id', $usuario->tienda_id);

        if ($fechaInicio) {
            $query->where('created_at', '>', $fechaInicio);
        }

        $this->desglose_entradas = $query
            ->select('id', 'monto', 'descripcion', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function cargarDesgloseTarjetas($fechaInicio)
    {
        $usuario = Auth::user();
        
        $query = DB::table('factura as f')
            ->join('pago_con_tarjeta as pct', 'f.id', '=', 'pct.factura_id')
            ->where('f.user_id', $usuario->id)
            ->where('f.tienda_id', $usuario->tienda_id);

        if ($fechaInicio) {
            $query->where('f.created_at', '>', $fechaInicio);
        }

        $this->desgloseTarjetas = $query
            ->select('f.id as factura_id', 'pct.numero_tarjeta', 'pct.monto', 'f.created_at')
            ->orderBy('f.created_at', 'desc')
            ->get();
    }

    private function cargarDesgloseTransferencias($fechaInicio)
    {
        $usuario = Auth::user();
        
        $query = DB::table('factura as f')
            ->join('pago_con_transferencia as pt', 'f.id', '=', 'pt.factura_id')
            ->where('f.user_id', $usuario->id)
            ->where('f.tienda_id', $usuario->tienda_id);

        if ($fechaInicio) {
            $query->where('f.created_at', '>', $fechaInicio);
        }

        $this->desgloseTransferencias = $query
            ->select('f.id as factura_id', 'pt.numero_referencia', 'pt.monto', 'f.created_at')
            ->orderBy('f.created_at', 'desc')
            ->get();
    }

    private function cargarDesgloseCheques($fechaInicio)
    {
        $usuario = Auth::user();
        
        $query = DB::table('factura as f')
            ->join('pago_con_cheque as pch', 'f.id', '=', 'pch.factura_id')
            ->where('f.user_id', $usuario->id)
            ->where('f.tienda_id', $usuario->tienda_id);

        if ($fechaInicio) {
            $query->where('f.created_at', '>', $fechaInicio);
        }

        $this->desgloseCheques = $query
            ->select('f.id as factura_id', 'pch.numero_cheque', 'pch.monto', 'pch.banco', 'f.created_at')
            ->orderBy('f.created_at', 'desc')
            ->get();
    }

    public function updatedBilletes500() { $this->calcularTotalContado(); }
    public function updatedBilletes200() { $this->calcularTotalContado(); }
    public function updatedBilletes100() { $this->calcularTotalContado(); }
    public function updatedBilletes50() { $this->calcularTotalContado(); }
    public function updatedBilletes20() { $this->calcularTotalContado(); }
    public function updatedBilletes10() { $this->calcularTotalContado(); }
    public function updatedBilletes5() { $this->calcularTotalContado(); }
    public function updatedBilletes2() { $this->calcularTotalContado(); }
    public function updatedBilletes1() { $this->calcularTotalContado(); }
    public function updatedMonedas050() { $this->calcularTotalContado(); }
    public function updatedMonedas020() { $this->calcularTotalContado(); }
    public function updatedMonedas010() { $this->calcularTotalContado(); }
    public function updatedMonedas005() { $this->calcularTotalContado(); }
    public function updatedMonedas002() { $this->calcularTotalContado(); }
    public function updatedMonedas001() { $this->calcularTotalContado(); }

    public function calcularTotalContado()
    {
        $this->totalContado = (
            ($this->billetes_500 * 500) +
            ($this->billetes_200 * 200) +
            ($this->billetes_100 * 100) +
            ($this->billetes_50 * 50) +
            ($this->billetes_20 * 20) +
            ($this->billetes_10 * 10) +
            ($this->billetes_5 * 5) +
            ($this->billetes_2 * 2) +
            ($this->billetes_1 * 1) +
            ($this->monedas_0_50 * 0.50) +
            ($this->monedas_0_20 * 0.20) +
            ($this->monedas_0_10 * 0.10) +
            ($this->monedas_0_05 * 0.05) +
            ($this->monedas_0_02 * 0.02) +
            ($this->monedas_0_01 * 0.01)
        );

        // Calcular diferencia (contado menos el efectivo que debería haber según el sistema)
        $efectivoSistema = $this->resumenTransacciones
            ->where('forma_pago', 'EFECTIVO')
            ->first()->total ?? 0;

        $this->diferenciaEfectivo = $this->totalContado - $efectivoSistema;
    }

    public function procesarCierre()
    {
        try {
            DB::beginTransaction();

            $usuario = Auth::user();

            // Obtener período
            $ultimoCierre = DB::table('cierre_caja_historico')
                ->where('user_id', $usuario->id)
                ->where('tienda_id', $usuario->tienda_id)
                ->orderBy('fecha_cierre', 'desc')
                ->first();

            $periodoInicio = $ultimoCierre ? $ultimoCierre->fecha_cierre : null;
            $periodoFin = now();

            // Contar facturas del período
            $queryFacturas = DB::table('factura')
                ->where('user_id', $usuario->id)
                ->where('tienda_id', $usuario->tienda_id);

            if ($periodoInicio) {
                $queryFacturas->where('created_at', '>', $periodoInicio);
            }

            $cantidadFacturas = $queryFacturas->count();

            // Guardar cierre en histórico
            DB::table('cierre_caja_historico')->insert([
                'user_id' => $usuario->id,
                'tienda_id' => $usuario->tienda_id,
                'fecha_cierre' => $periodoFin,
                'periodo_inicio' => $periodoInicio,
                'periodo_fin' => $periodoFin,
                'total_efectivo_sistema' => $this->resumenTransacciones->where('forma_pago', 'EFECTIVO')->first()->total ?? 0,
                'total_efectivo_contado' => $this->totalContado,
                'diferencia' => $this->diferenciaEfectivo,
                'total_tarjeta' => $this->resumenTransacciones->where('forma_pago', 'TARJETA')->first()->total ?? 0,
                'total_transferencia' => $this->resumenTransacciones->where('forma_pago', 'TRANSFERENCIA')->first()->total ?? 0,
                'total_cheque' => $this->resumenTransacciones->where('forma_pago', 'CHEQUE')->first()->total ?? 0,
                'total_general' => $this->totalSistema,
                'cantidad_facturas' => $cantidadFacturas,
                'observaciones' => $this->observaciones,
                'desglose_billetes' => json_encode([
                    'billetes' => [
                        '500' => $this->billetes_500,
                        '200' => $this->billetes_200,
                        '100' => $this->billetes_100,
                        '50' => $this->billetes_50,
                        '20' => $this->billetes_20,
                        '10' => $this->billetes_10,
                        '5' => $this->billetes_5,
                        '2' => $this->billetes_2,
                        '1' => $this->billetes_1,
                    ],
                    'monedas' => [
                        '0.50' => $this->monedas_0_50,
                        '0.20' => $this->monedas_0_20,
                        '0.10' => $this->monedas_0_10,
                        '0.05' => $this->monedas_0_05,
                        '0.02' => $this->monedas_0_02,
                        '0.01' => $this->monedas_0_01,
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Resetear caja al saldo inicial
            DB::table('caja')
                ->where('users_id', $usuario->id)
                ->where('tienda_id', $usuario->tienda_id)
                ->update([
                    'balance' => self::SALDO_INICIAL,
                    'updated_at' => now()
                ]);

            DB::commit();

            $this->cierreProcesado = true;
            $this->mensajeExito = '✅ Cierre de caja procesado exitosamente. La caja se ha restablecido a L. ' . number_format(self::SALDO_INICIAL, 2);

            Log::info("Cierre de caja procesado - Usuario: {$usuario->id}, Tienda: {$usuario->tienda_id}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->mensajeError = '❌ Error al procesar cierre: ' . $e->getMessage();
            Log::error("Error en cierre de caja: " . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.caja.cierre-de-caja');
    }
}
```

---

### CAMBIO 4: DashboardDinamico.php - Eliminar Estado de Jornada

**Archivo:** `app/Livewire/DashboardDinamico.php`

**ELIMINAR:**
- Propiedad `$estadoJornada`
- Método `cargarEstadoJornada()`
- Método `obtenerTextoEstadoJornada()`
- Método `obtenerFechaJornadaAbierta()`
- Cualquier referencia a jornada en `mount()`

**MANTENER:**
- Indicadores de ventas
- Indicadores de productos
- Balances de caja (sin filtrar por fecha de jornada)

---

## 🎨 VISTAS BLADE MODIFICADAS

### Vista: saldo-inicial.blade.php

```blade
<div class="container p-6 mx-auto">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 text-white rounded-t-lg bg-gradient-to-r from-blue-600 to-blue-700">
            <h1 class="flex items-center text-2xl font-bold">
                <i class="mr-3 fas fa-cash-register"></i>
                Estado de Caja
            </h1>
            <p class="mt-1 text-blue-100">Sistema de caja siempre activa</p>
        </div>

        <div class="p-6">
            @if($mensaje)
                <div class="p-4 mb-6 rounded-lg {{ $tipoMensaje === 'success' ? 'bg-green-50 border border-green-200' : 'bg-blue-50 border border-blue-200' }}">
                    <div class="flex items-center">
                        <i class="mr-3 fas {{ $tipoMensaje === 'success' ? 'fa-check-circle text-green-500' : 'fa-info-circle text-blue-500' }}"></i>
                        <span class="font-medium {{ $tipoMensaje === 'success' ? 'text-green-800' : 'text-blue-800' }}">{{ $mensaje }}</span>
                    </div>
                </div>
            @endif

            <div class="p-6 border-2 border-blue-300 rounded-lg bg-blue-50">
                <div class="text-center">
                    <div class="mb-2 text-sm font-medium text-blue-700">SALDO INICIAL FIJO</div>
                    <div class="text-5xl font-bold text-blue-900">
                        L. {{ number_format($saldoInicial, 2) }}
                    </div>
                    <div class="mt-4 text-sm text-blue-600">
                        <i class="mr-2 fas fa-info-circle"></i>
                        Su caja está siempre activa y lista para procesar ventas
                    </div>
                </div>
            </div>

            @if($cajaActual)
                <div class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-2">
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="text-sm text-gray-600">Balance Actual</div>
                        <div class="text-2xl font-bold text-gray-900">
                            L. {{ number_format($cajaActual->balance, 2) }}
                        </div>
                    </div>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="text-sm text-gray-600">Estado</div>
                        <div class="text-2xl font-bold text-green-600">
                            <i class="mr-2 fas fa-check-circle"></i>
                            ACTIVA
                        </div>
                    </div>
                </div>
            @endif

            <div class="p-4 mt-6 border border-yellow-200 rounded-lg bg-yellow-50">
                <h4 class="mb-2 font-semibold text-yellow-800">
                    <i class="mr-2 fas fa-lightbulb"></i>
                    Información Importante
                </h4>
                <ul class="space-y-1 text-sm text-yellow-700">
                    <li><i class="mr-2 fas fa-check"></i>La caja siempre inicia con L. 2,000.00</li>
                    <li><i class="mr-2 fas fa-check"></i>No requiere apertura manual</li>
                    <li><i class="mr-2 fas fa-check"></i>Puede procesar ventas en cualquier momento</li>
                    <li><i class="mr-2 fas fa-check"></i>Al cerrar caja, el saldo se restablece automáticamente</li>
                </ul>
            </div>
        </div>
    </div>
</div>
```

---

### Vista: cierre-de-caja.blade.php (Simplificada)

```blade
<div class="container p-6 mx-auto">
    <div class="bg-white rounded-lg shadow-lg">
        <div class="p-6 text-white rounded-t-lg bg-gradient-to-r from-red-600 to-red-700">
            <h1 class="flex items-center text-2xl font-bold">
                <i class="mr-3 fas fa-times-circle"></i>
                Cierre de Caja
            </h1>
            <p class="mt-1 text-red-100">Procesamiento de cierre y reinicio de caja</p>
        </div>

        <div class="p-6">
            {{-- Mensajes --}}
            @if($mensajeExito)
                <div class="p-4 mb-6 border border-green-200 rounded-lg bg-green-50">
                    <div class="flex items-center">
                        <i class="mr-3 text-green-500 fas fa-check-circle"></i>
                        <span class="font-medium text-green-800">{{ $mensajeExito }}</span>
                    </div>
                </div>
            @endif

            @if($mensajeError)
                <div class="p-4 mb-6 border border-red-200 rounded-lg bg-red-50">
                    <div class="flex items-center">
                        <i class="mr-3 text-red-500 fas fa-exclamation-triangle"></i>
                        <span class="font-medium text-red-800">{{ $mensajeError }}</span>
                    </div>
                </div>
            @endif

            @if(!$cierreProcesado)
                {{-- Resumen de Transacciones --}}
                <div class="mb-6">
                    <h3 class="mb-4 text-lg font-semibold">Resumen de Transacciones</h3>
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Forma de Pago</th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Cantidad</th>
                                    <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($resumenTransacciones as $resumen)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">{{ $resumen->forma_pago }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $resumen->cantidad }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-right text-gray-900 whitespace-nowrap">L. {{ number_format($resumen->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-sm text-center text-gray-500">No hay transacciones registradas</td>
                                    </tr>
                                @endforelse
                                <tr class="bg-gray-50">
                                    <td colspan="2" class="px-6 py-4 text-sm font-bold text-gray-900">TOTAL GENERAL</td>
                                    <td class="px-6 py-4 text-sm font-bold text-right text-gray-900">L. {{ number_format($totalSistema, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Desglose de Efectivo --}}
                <div class="mb-6">
                    <h3 class="mb-4 text-lg font-semibold">Conteo de Efectivo</h3>
                    <div class="p-6 border border-gray-200 rounded-lg">
                        <div class="grid grid-cols-2 gap-4 mb-4 md:grid-cols-4">
                            {{-- Billetes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 500</label>
                                <input type="number" wire:model.live="billetes_500" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 200</label>
                                <input type="number" wire:model.live="billetes_200" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 100</label>
                                <input type="number" wire:model.live="billetes_100" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 50</label>
                                <input type="number" wire:model.live="billetes_50" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 20</label>
                                <input type="number" wire:model.live="billetes_20" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 10</label>
                                <input type="number" wire:model.live="billetes_10" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 5</label>
                                <input type="number" wire:model.live="billetes_5" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 2</label>
                                <input type="number" wire:model.live="billetes_2" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 1</label>
                                <input type="number" wire:model.live="billetes_1" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>

                            {{-- Monedas --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.50</label>
                                <input type="number" wire:model.live="monedas_0_50" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.20</label>
                                <input type="number" wire:model.live="monedas_0_20" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.10</label>
                                <input type="number" wire:model.live="monedas_0_10" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.05</label>
                                <input type="number" wire:model.live="monedas_0_05" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.02</label>
                                <input type="number" wire:model.live="monedas_0_02" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">L. 0.01</label>
                                <input type="number" wire:model.live="monedas_0_01" class="block w-full mt-1 border-gray-300 rounded-md">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div class="p-4 rounded-lg bg-blue-50">
                                    <div class="text-sm text-blue-700">Total Contado</div>
                                    <div class="text-2xl font-bold text-blue-900">L. {{ number_format($totalContado, 2) }}</div>
                                </div>
                                <div class="p-4 rounded-lg bg-gray-50">
                                    <div class="text-sm text-gray-700">Efectivo Sistema</div>
                                    <div class="text-2xl font-bold text-gray-900">
                                        L. {{ number_format($resumenTransacciones->where('forma_pago', 'EFECTIVO')->first()->total ?? 0, 2) }}
                                    </div>
                                </div>
                                <div class="p-4 rounded-lg {{ $diferenciaEfectivo >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
                                    <div class="text-sm {{ $diferenciaEfectivo >= 0 ? 'text-green-700' : 'text-red-700' }}">Diferencia</div>
                                    <div class="text-2xl font-bold {{ $diferenciaEfectivo >= 0 ? 'text-green-900' : 'text-red-900' }}">
                                        L. {{ number_format($diferenciaEfectivo, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Observaciones --}}
                <div class="mb-6">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Observaciones</label>
                    <textarea wire:model="observaciones" rows="3" class="block w-full border-gray-300 rounded-md" placeholder="Notas adicionales sobre el cierre..."></textarea>
                </div>

                {{-- Botón de Cierre --}}
                <div class="flex justify-end">
                    <button
                        wire:click="procesarCierre"
                        wire:loading.attr="disabled"
                        class="flex items-center px-8 py-3 font-semibold text-white transition-colors bg-red-600 rounded-lg hover:bg-red-700"
                    >
                        <i class="mr-2 fas fa-times-circle"></i>
                        <span wire:loading.remove>Procesar Cierre de Caja</span>
                        <span wire:loading>
                            <i class="mr-2 fas fa-spinner fa-spin"></i>
                            Procesando...
                        </span>
                    </button>
                </div>
            @else
                <div class="text-center">
                    <i class="mb-4 text-6xl text-green-500 fas fa-check-circle"></i>
                    <h2 class="mb-2 text-2xl font-bold text-gray-900">Cierre Procesado Exitosamente</h2>
                    <p class="mb-6 text-gray-600">La caja se ha restablecido a L. 2,000.00</p>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="mr-2 fas fa-home"></i>
                        Volver al Dashboard
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
```

---

## 📊 MIGRACIÓN SQL

```sql
-- =============================================
-- Script: Crear tabla cierre_caja_historico
-- Descripción: Nueva tabla para registrar histórico de cierres de caja
--              sin dependencia de jornadas
-- Fecha: 2025-12-05
-- =============================================

CREATE TABLE IF NOT EXISTS `cierre_caja_historico` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Usuario que realizó el cierre',
    `tienda_id` BIGINT UNSIGNED NOT NULL COMMENT 'Tienda donde se realizó el cierre',
    `fecha_cierre` DATETIME NOT NULL COMMENT 'Fecha y hora del cierre',
    `periodo_inicio` DATETIME NULL COMMENT 'Inicio del período (último cierre)',
    `periodo_fin` DATETIME NULL COMMENT 'Fin del período (este cierre)',
    `total_efectivo_sistema` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total efectivo según sistema',
    `total_efectivo_contado` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total efectivo contado físicamente',
    `diferencia` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Diferencia (contado - sistema)',
    `total_tarjeta` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total pagos con tarjeta',
    `total_transferencia` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total pagos con transferencia',
    `total_cheque` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total pagos con cheque',
    `total_general` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total general de ventas',
    `cantidad_facturas` INT DEFAULT 0 COMMENT 'Cantidad de facturas en el período',
    `observaciones` TEXT NULL COMMENT 'Observaciones del cierre',
    `desglose_billetes` JSON NULL COMMENT 'Desglose de billetes y monedas en formato JSON',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_tienda` (`user_id`, `tienda_id`),
    INDEX `idx_fecha_cierre` (`fecha_cierre`),
    INDEX `idx_tienda_fecha` (`tienda_id`, `fecha_cierre`),
    CONSTRAINT `fk_cierre_usuario` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_cierre_tienda` FOREIGN KEY (`tienda_id`) REFERENCES `tienda`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de cierres de caja sin dependencia de jornadas';

-- Índices adicionales para consultas comunes
CREATE INDEX `idx_periodo` ON `cierre_caja_historico`(`periodo_inicio`, `periodo_fin`);

-- =============================================
-- Script: Actualizar tabla caja
-- Descripción: Asegurar que todas las cajas tengan estado activo
--              y saldo inicial de L. 2,000.00
-- =============================================

-- Actualizar cajas existentes que estén cerradas
UPDATE `caja`
SET 
    `estado_caja` = 1,
    `balance` = 2000.00,
    `updated_at` = CURRENT_TIMESTAMP
WHERE `estado_caja` = 2;

-- Agregar comentario a la columna estado_caja
ALTER TABLE `caja`
MODIFY COLUMN `estado_caja` TINYINT(1) DEFAULT 1 COMMENT '1=Activa (siempre), 2=Deprecado';

-- =============================================
-- NOTA IMPORTANTE:
-- =============================================
-- Las tablas "jornada", "apertura_de_jornada" y "cierre_de_jornada"
-- NO se eliminan para mantener el histórico.
-- Sin embargo, ya no se utilizarán en el nuevo flujo.
-- Se pueden archivar o mantener para consultas históricas.
-- =============================================
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### Fase 1: Preparación
- [ ] Hacer backup completo de la base de datos
- [ ] Crear rama git nueva: `feature/remove-jornada-system`
- [ ] Ejecutar migración para crear `cierre_caja_historico`
- [ ] Actualizar tabla `caja` con script SQL

### Fase 2: Modificación de Componentes Livewire
- [ ] Modificar `SalaDeVentas/Ventas.php`
- [ ] Modificar `Caja/SaldoInicial.php`
- [ ] Reescribir `Caja/CierreDeCaja.php`
- [ ] Modificar `DashboardDinamico.php`
- [ ] Deprecar `GestionDeSucursales/AperturaDeJornada.php`
- [ ] Deprecar `GestionDeSucursales/CierreDeJornada.php`

### Fase 3: Modificación de Vistas
- [ ] Actualizar `sala-de-ventas/ventas.blade.php`
- [ ] Reescribir `caja/cierre-de-caja.blade.php`
- [ ] Actualizar `caja/saldo-inicial.blade.php`
- [ ] Marcar como obsoletas las vistas de jornada

### Fase 4: Rutas y Navegación
- [ ] Actualizar `routes/web.php`
- [ ] Modificar menús de navegación
- [ ] Actualizar dashboard para remover widgets de jornada

### Fase 5: Testing
- [ ] Probar flujo completo de ventas
- [ ] Probar cierre de caja
- [ ] Verificar reseteo de saldo
- [ ] Validar histórico de cierres
- [ ] Verificar cálculos de diferencias

### Fase 6: Documentación
- [ ] Actualizar manual de usuario
- [ ] Documentar nuevo flujo operativo
- [ ] Crear guía de migración para usuarios

---

## 🎓 CAPACITACIÓN DE USUARIOS

### Puntos Clave a Comunicar

1. **YA NO EXISTE APERTURA DE JORNADA**
   - La caja siempre está activa
   - No necesitan "abrir el día"
   
2. **VENTAS LIBRES**
   - Pueden facturar en cualquier momento
   - No hay restricciones de horario
   
3. **CIERRE DE CAJA SIMPLIFICADO**
   - Se hace cuando terminen sus operaciones
   - El sistema genera el reporte automáticamente
   - La caja vuelve a L. 2,000.00
   
4. **SALDO INICIAL FIJO**
   - Siempre inician con L. 2,000.00
   - No se modifica manualmente

---

## 🚀 MEJORAS FUTURAS SUGERIDAS

1. **Reportes Mejorados**
   - Dashboard con histórico de cierres
   - Gráficas de tendencias
   - Comparativas por período

2. **Alertas Automáticas**
   - Notificación si la diferencia excede un umbral
   - Recordatorio de cierre pendiente

3. **Multi-Caja**
   - Permitir múltiples cajas por tienda
   - Cierre independiente por cajero

4. **Auditoría Avanzada**
   - Logs detallados de todas las operaciones
   - Trazabilidad completa

5. **Exportación de Datos**
   - Excel de cierres históricos
   - PDF de reportes de cierre

---

## 📞 SOPORTE Y CONTACTO

Para cualquier duda sobre la implementación de estos cambios:
- Revisar este documento completo
- Consultar el código comentado
- Validar con pruebas en ambiente de desarrollo

---

**Fecha del Documento:** 05 de Diciembre de 2025  
**Versión:** 1.0  
**Estado:** Propuesta Lista para Implementación
