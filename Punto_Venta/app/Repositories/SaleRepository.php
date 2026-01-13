<?php

namespace App\Repositories;

use App\Models\Factura;
use Illuminate\Support\Collection;

class SaleRepository
{
    /**
     * Crear una nueva factura
     */
    public function create(array $data): Factura
    {
        return Factura::create($data);
    }

    /**
     * Buscar factura por ID con relaciones
     */
    public function findWithRelations(int $id): ?Factura
    {
        return Factura::with(['items.producto', 'cliente', 'usuario'])
            ->find($id);
    }

    /**
     * Buscar factura por external_order_id
     */
    public function findByExternalOrderId(string $externalOrderId): ?Factura
    {
        return Factura::where('external_order_id', $externalOrderId)
            ->with(['items.producto'])
            ->first();
    }

    /**
     * Actualizar estado de factura
     */
    public function updateStatus(int $id, string $status, array $additionalData = []): bool
    {
        $data = array_merge(['estado' => $status], $additionalData);
        
        return Factura::where('id', $id)
            ->update($data) > 0;
    }

    /**
     * Obtener facturas por estado
     */
    public function getByStatus(string $status, int $limit = 100): Collection
    {
        return Factura::where('estado', $status)
            ->with(['items.producto', 'cliente'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener facturas de un cliente API
     */
    public function getByApiClient(int $clientId, int $perPage = 50)
    {
        return Factura::where('api_client_id', $clientId)
            ->with(['items.producto'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Obtener facturas por rango de fechas
     */
    public function getByDateRange(string $startDate, string $endDate, array $filters = [])
    {
        $query = Factura::whereBetween('created_at', [$startDate, $endDate])
            ->with(['items.producto', 'cliente']);

        if (isset($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (isset($filters['api_client_id'])) {
            $query->where('api_client_id', $filters['api_client_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtener total de ventas por período
     */
    public function getTotalSalesByPeriod(string $startDate, string $endDate, string $status = 'completada'): float
    {
        return Factura::whereBetween('created_at', [$startDate, $endDate])
            ->where('estado', $status)
            ->sum('total');
    }

    /**
     * Contar facturas por estado
     */
    public function countByStatus(string $status): int
    {
        return Factura::where('estado', $status)->count();
    }
}
