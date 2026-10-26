<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Traslado;
use App\Services\TrasladoInventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransfersController extends Controller
{
    public function __construct(private TrasladoInventarioService $traslados)
    {
    }

    public function incoming(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'uuid' => ['required', 'uuid'],
            'referencia_externa' => ['required', 'string', 'max:100'],
            'bodega_destino_id' => ['required', 'integer'],
            'fecha_solicitud' => ['nullable', 'date'],
            'fecha_despacho' => ['required', 'date'],
            'comentario' => ['nullable', 'string', 'max:500'],
            'metadata' => ['nullable', 'array'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id_valencia' => ['required', 'integer'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
            'productos.*.codigo_barra' => ['nullable', 'string', 'max:100'],
            'productos.*.observacion' => ['nullable', 'string', 'max:255'],
        ]);

        $traslado = $this->traslados->registrarEntradaValencia($datos);

        return response()->json([
            'success' => true,
            'data' => $this->formatear($traslado),
            'message' => 'Traslado registrado idempotentemente en Zenvy.',
        ], $traslado->wasRecentlyCreated ? 201 : 200);
    }

    public function outgoing(): JsonResponse
    {
        $traslados = Traslado::with('detalles.producto')
            ->where('direccion', 'zenvy_valencia')
            ->whereIn('estado', ['en_transito', 'recibido_parcial'])
            ->orderBy('fecha_despacho')
            ->get()
            ->map(fn (Traslado $traslado): array => $this->formatear($traslado));

        return response()->json(['success' => true, 'data' => $traslados]);
    }

    public function receipt(Request $request, string $uuid): JsonResponse
    {
        $datos = $request->validate([
            'recepcion_uuid' => ['required', 'uuid'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.detalle_id_zenvy' => ['nullable', 'integer'],
            'productos.*.producto_id_valencia' => ['required', 'integer'],
            'productos.*.cantidad_recibida' => ['required', 'integer', 'min:0'],
            'productos.*.cantidad_rechazada' => ['nullable', 'integer', 'min:0'],
        ]);

        $traslado = $this->traslados->confirmarRecepcionValencia(
            $uuid,
            $datos['recepcion_uuid'],
            $datos['productos']
        );

        return response()->json([
            'success' => true,
            'data' => $this->formatear($traslado),
            'message' => 'Recepción de Valencia aplicada.',
        ]);
    }

    private function formatear(Traslado $traslado): array
    {
        return [
            'uuid' => $traslado->uuid,
            'codigo' => $traslado->codigo,
            'referencia_externa' => $traslado->referencia_externa,
            'direccion' => $traslado->direccion,
            'estado' => $traslado->estado,
            'fecha_despacho' => $traslado->fecha_despacho?->toIso8601String(),
            'productos' => $traslado->detalles->map(fn ($detalle): array => [
                'detalle_id_zenvy' => $detalle->id,
                'producto_id_zenvy' => $detalle->producto_id,
                'producto_id_valencia' => $detalle->producto_externo_id,
                'nombre' => $detalle->producto->nombre,
                'precio_venta_id' => $detalle->precio_venta_id,
                'unidad_medida_id' => $detalle->unidad_medida_id,
                'codigo_barra' => $detalle->precioVenta?->codigo_barra,
                'cantidad_presentacion' => $detalle->precioVenta?->cantidad,
                'cantidad_enviada' => $detalle->cantidad_enviada,
                'cantidad_recibida' => $detalle->cantidad_recibida,
                'cantidad_rechazada' => $detalle->cantidad_rechazada,
            ])->values(),
        ];
    }
}
