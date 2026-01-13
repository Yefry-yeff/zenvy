<?php

namespace App\Models;

enum SaleStatus: string
{
    case PENDING = 'pendiente';
    case PROCESSING = 'procesando';
    case COMPLETED = 'completada';
    case CANCELLED = 'anulada';
    case FAILED = 'fallida';
    case REFUNDED = 'reembolsada';
    case PARTIAL_REFUND = 'reembolso_parcial';

    /**
     * Obtener todos los estados posibles
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verificar si es un estado final
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::CANCELLED,
            self::FAILED,
            self::REFUNDED,
        ]);
    }

    /**
     * Verificar si se puede cancelar
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::PROCESSING,
            self::COMPLETED,
        ]);
    }

    /**
     * Obtener descripción del estado
     */
    public function description(): string
    {
        return match($this) {
            self::PENDING => 'Venta iniciada, pendiente de confirmación',
            self::PROCESSING => 'Venta en proceso de validación',
            self::COMPLETED => 'Venta completada exitosamente',
            self::CANCELLED => 'Venta anulada',
            self::FAILED => 'Venta fallida por error',
            self::REFUNDED => 'Venta reembolsada completamente',
            self::PARTIAL_REFUND => 'Venta con reembolso parcial',
        };
    }

    /**
     * Obtener color para UI
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'secondary',
            self::FAILED => 'danger',
            self::REFUNDED => 'primary',
            self::PARTIAL_REFUND => 'primary',
        };
    }
}
