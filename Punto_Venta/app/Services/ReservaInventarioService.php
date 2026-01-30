<?php

namespace App\Services;

use App\Models\ReservaInventario;
use App\Models\AuditoriaReservaInventario;
use App\Models\PedidoWeb;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservaInventarioService
{
    /**
     * Crear reservas de inventario para un pedido web
     */
    public function crearReservas(PedidoWeb $pedido, ?int $usuarioId = null): array
    {
        $reservasCreadas = [];
        $errores = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($pedido->items as $item) {
                $producto = Producto::find($item->producto_id);
                
                if (!$producto) {
                    $errores[] = "Producto ID {$item->producto_id} no encontrado";
                    continue;
                }
                
                // Verificar si hay stock suficiente
                $stockDisponible = $this->getStockDisponible($producto->id);
                
                if ($stockDisponible < $item->cantidad) {
                    $errores[] = "Stock insuficiente para {$producto->nombre_producto}. Disponible: {$stockDisponible}, Solicitado: {$item->cantidad}";
                    continue;
                }
                
                // Crear la reserva
                $reserva = ReservaInventario::create([
                    'pedido_web_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad_reservada' => $item->cantidad,
                    'estado' => 'activa',
                ]);
                
                // Registrar auditoría
                $this->registrarAuditoria(
                    reserva: $reserva,
                    accion: 'crear',
                    estadoAnterior: null,
                    estadoNuevo: 'activa',
                    cantidadAnterior: 0,
                    cantidadNueva: $item->cantidad,
                    stockAntesAccion: $stockDisponible,
                    usuarioId: $usuarioId,
                    motivo: "Reserva creada al recibir pedido web {$pedido->numero_pedido}"
                );
                
                $reservasCreadas[] = $reserva;
                
                Log::info('Reserva de inventario creada', [
                    'reserva_id' => $reserva->id,
                    'pedido_web_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'producto_id' => $producto->id,
                    'producto_nombre' => $producto->nombre_producto,
                    'cantidad_reservada' => $item->cantidad,
                    'stock_disponible_antes' => $stockDisponible,
                    'stock_disponible_despues' => $stockDisponible - $item->cantidad,
                ]);
            }
            
            if (!empty($errores)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'errores' => $errores,
                    'reservas' => [],
                ];
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'reservas' => $reservasCreadas,
                'errores' => [],
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear reservas de inventario', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'errores' => ['Error inesperado: ' . $e->getMessage()],
                'reservas' => [],
            ];
        }
    }
    
    /**
     * Liberar reservas cuando se rechaza un pedido
     */
    public function liberarReservas(PedidoWeb $pedido, string $motivo, ?int $usuarioId = null): bool
    {
        DB::beginTransaction();
        
        try {
            $reservas = ReservaInventario::where('pedido_web_id', $pedido->id)
                ->where('estado', 'activa')
                ->get();
            
            foreach ($reservas as $reserva) {
                $producto = Producto::find($reserva->producto_id);
                $stockAntes = $this->getStockDisponible($reserva->producto_id);
                
                $reserva->update([
                    'estado' => 'liberada',
                    'motivo_liberacion' => $motivo,
                    'fecha_liberacion' => now(),
                    'liberado_por' => $usuarioId,
                ]);
                
                // Registrar auditoría
                $this->registrarAuditoria(
                    reserva: $reserva,
                    accion: 'liberar',
                    estadoAnterior: 'activa',
                    estadoNuevo: 'liberada',
                    cantidadAnterior: $reserva->cantidad_reservada,
                    cantidadNueva: $reserva->cantidad_reservada,
                    stockAntesAccion: $stockAntes,
                    usuarioId: $usuarioId,
                    motivo: $motivo
                );
                
                Log::info('Reserva de inventario liberada', [
                    'reserva_id' => $reserva->id,
                    'pedido_web_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'producto_id' => $reserva->producto_id,
                    'producto_nombre' => $producto->nombre_producto ?? 'N/A',
                    'cantidad_liberada' => $reserva->cantidad_reservada,
                    'motivo' => $motivo,
                ]);
            }
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al liberar reservas de inventario', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Consumir reservas cuando se factura un pedido
     * NOTA: Este método solo marca las reservas como consumidas.
     * El descuento real del stock lo hace SalesService->decrementarStockBodega()
     */
    public function consumirReservas(PedidoWeb $pedido, ?int $usuarioId = null): bool
    {
        DB::beginTransaction();
        
        try {
            $reservas = ReservaInventario::where('pedido_web_id', $pedido->id)
                ->where('estado', 'activa')
                ->get();
            
            foreach ($reservas as $reserva) {
                $producto = Producto::find($reserva->producto_id);
                
                // Obtener stock disponible antes de marcar como consumida
                $stockAntes = $this->getStockDisponible($reserva->producto_id);
                
                // Marcar reserva como consumida (NO descuenta stock, eso lo hace SalesService)
                $reserva->update([
                    'estado' => 'consumida',
                    'fecha_consumo' => now(),
                    'consumido_por' => $usuarioId,
                ]);
                
                $stockDespues = $this->getStockDisponible($reserva->producto_id);
                
                // Registrar auditoría
                $this->registrarAuditoria(
                    reserva: $reserva,
                    accion: 'consumir',
                    estadoAnterior: 'activa',
                    estadoNuevo: 'consumida',
                    cantidadAnterior: $reserva->cantidad_reservada,
                    cantidadNueva: $reserva->cantidad_reservada,
                    stockAntesAccion: $stockAntes,
                    usuarioId: $usuarioId,
                    motivo: "Reserva consumida al facturar pedido {$pedido->numero_pedido}"
                );
                
                Log::info('Reserva de inventario consumida (stock descontado)', [
                    'reserva_id' => $reserva->id,
                    'pedido_web_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'producto_id' => $reserva->producto_id,
                    'producto_nombre' => $producto->nombre_producto ?? 'N/A',
                    'cantidad_consumida' => $reserva->cantidad_reservada,
                    'stock_antes' => $stockAntes,
                    'stock_despues' => $stockDespues,
                ]);
            }
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al consumir reservas de inventario', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Obtener stock disponible (stock real - reservas activas)
     */
    public function getStockDisponible(int $productoId): int
    {
        // Obtener stock total disponible en bodega (FIFO)
        $stockReal = \App\Models\RecibidoBodega::where('producto_id', $productoId)
            ->where('estado_id', 1)
            ->where('cantidad_disponible', '>', 0)
            ->sum('cantidad_disponible');
        
        // Restar reservas activas
        $reservasActivas = ReservaInventario::cantidadReservadaProducto($productoId);
        
        return max(0, $stockReal - $reservasActivas);
    }
    
    /**
     * Registrar auditoría de acción sobre reserva
     */
    private function registrarAuditoria(
        ReservaInventario $reserva,
        string $accion,
        ?string $estadoAnterior,
        ?string $estadoNuevo,
        int $cantidadAnterior,
        int $cantidadNueva,
        int $stockAntesAccion,
        ?int $usuarioId,
        string $motivo
    ): void {
        $producto = Producto::find($reserva->producto_id);
        $stockDespuesAccion = $this->getStockDisponible($reserva->producto_id);
        
        $usuario = $usuarioId ? \App\Models\User::find($usuarioId) : null;
        
        AuditoriaReservaInventario::create([
            'reserva_id' => $reserva->id,
            'pedido_web_id' => $reserva->pedido_web_id,
            'producto_id' => $reserva->producto_id,
            'numero_pedido' => $reserva->pedidoWeb->numero_pedido ?? 'N/A',
            'nombre_producto' => $producto->nombre_producto ?? 'Producto eliminado',
            'accion' => $accion,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'cantidad_anterior' => $cantidadAnterior,
            'cantidad_nueva' => $cantidadNueva,
            'stock_disponible_antes' => $stockAntesAccion,
            'stock_disponible_despues' => $stockDespuesAccion,
            'usuario_id' => $usuarioId,
            'usuario_nombre' => $usuario->name ?? 'Sistema',
            'motivo' => $motivo,
            'metadata' => [
                'pedido_numero' => $reserva->pedidoWeb->numero_pedido ?? null,
                'producto_codigo' => $producto->codigo_barra ?? null,
            ],
            'ip' => request()->ip(),
            'fecha_accion' => now(),
        ]);
    }
}
