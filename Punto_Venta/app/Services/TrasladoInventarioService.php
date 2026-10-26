<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoValenciaZenvy;
use App\Models\RecibidoBodega;
use App\Models\Traslado;
use App\Models\TrasladoDetalle;
use App\Models\TrasladoLote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TrasladoInventarioService
{
    public function crearSalida(array $datos, int $usuarioId): Traslado
    {
        $datos = Validator::make($datos, [
            'bodega_origen_id' => ['required', 'integer'],
            'comentario' => ['nullable', 'string', 'max:500'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'integer'],
            'productos.*.precio_venta_id' => ['required', 'integer', 'distinct:strict'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
        ])->validate();

        return DB::transaction(function () use ($datos, $usuarioId): Traslado {
            $tiendaId = DB::table('users')->where('id', $usuarioId)->value('tienda_id');
            if (!$tiendaId) {
                throw new RuntimeException('El usuario no tiene una tienda válida para preparar el traslado.');
            }

            $bodega = DB::table('bodega')
                ->where('id', $datos['bodega_origen_id'])
                ->where('tienda_id', $tiendaId)
                ->where('estado_id', 1)
                ->where('tipo_bodega', 'fisica')
                ->where('cuenta_como_disponible', 1)
                ->first();

            if (!$bodega) {
                throw ValidationException::withMessages([
                    'bodega_origen_id' => 'La bodega de origen no está disponible para traslados.',
                ]);
            }

            $bodegaTransito = DB::table('bodega')
                ->where('tienda_id', $bodega->tienda_id)
                ->where('tipo_bodega', 'transito_salida')
                ->where('estado_id', 1)
                ->first();
            if (!$bodegaTransito) {
                throw new RuntimeException('No está configurada la bodega de tránsito hacia Valencia.');
            }

            $uuid = (string) Str::uuid();
            $traslado = Traslado::create([
                'uuid' => $uuid,
                'codigo' => 'ZNV-' . now()->format('Ymd') . '-' . strtoupper(substr(str_replace('-', '', $uuid), 0, 8)),
                'direccion' => 'zenvy_valencia',
                'sistema_origen' => 'zenvy',
                'bodega_origen_id' => $bodega->id,
                'bodega_destino_id' => $bodegaTransito->id,
                'estado' => 'borrador',
                'fecha_solicitud' => now(),
                'usuario_creacion_id' => $usuarioId,
                'comentario' => $datos['comentario'] ?? null,
            ]);

            foreach ($datos['productos'] as $item) {
                $producto = Producto::where('id', $item['producto_id'])
                    ->where('estado_id', 1)
                    ->firstOrFail();
                $presentacion = DB::table('precio_has_venta')
                    ->where('id', $item['precio_venta_id'])
                    ->where('producto_id', $producto->id)
                    ->where('estado_id', 1)
                    ->first();

                if (!$presentacion) {
                    throw ValidationException::withMessages([
                        'productos' => "La presentación indicada para {$producto->nombre} no es válida.",
                    ]);
                }

                if (!$this->unidadAutorizadaPorValencia($producto->id, $presentacion->unidad_medida_id)) {
                    throw ValidationException::withMessages([
                        'productos' => "La unidad seleccionada para {$producto->nombre} no coincide con una unidad recibida previamente desde Valencia.",
                    ]);
                }

                $idValencia = ProductoValenciaZenvy::obtenerIdValencia($producto->id);
                if (!$idValencia) {
                    throw ValidationException::withMessages([
                        'productos' => "El producto {$producto->nombre} no tiene mapeo activo con Valencia.",
                    ]);
                }

                TrasladoDetalle::create([
                    'traslado_id' => $traslado->id,
                    'producto_id' => $producto->id,
                    'producto_externo_id' => $idValencia,
                    'precio_venta_id' => $presentacion->id,
                    'unidad_medida_id' => $presentacion->unidad_medida_id,
                    'cantidad_solicitada' => (int) $item['cantidad'],
                ]);
            }

            $this->registrarBitacora($traslado, $usuarioId, 'Crear traslado', 'Traslado de salida creado en borrador.');

            return $traslado->load('detalles.producto', 'bodegaOrigen');
        });
    }

    public function despachar(int $trasladoId, int $usuarioId): Traslado
    {
        return DB::transaction(function () use ($trasladoId, $usuarioId): Traslado {
            $traslado = Traslado::with('detalles.producto')->lockForUpdate()->findOrFail($trasladoId);

            if ($traslado->direccion !== 'zenvy_valencia' || $traslado->estado !== 'borrador') {
                throw new RuntimeException('Solo se pueden despachar traslados de salida en estado borrador.');
            }

            $tiendaId = DB::table('users')->where('id', $usuarioId)->value('tienda_id');
            $bodegaValida = $tiendaId && DB::table('bodega')
                ->where('id', $traslado->bodega_origen_id)
                ->where('tienda_id', $tiendaId)
                ->where('estado_id', 1)
                ->where('tipo_bodega', 'fisica')
                ->where('cuenta_como_disponible', 1)
                ->exists();
            if (!$bodegaValida) {
                throw new RuntimeException('La bodega de origen ya no está disponible para este usuario.');
            }

            if ($traslado->detalles->isEmpty()) {
                throw new RuntimeException('El traslado no contiene productos para despachar.');
            }

            foreach ($traslado->detalles as $detalle) {
                if ($detalle->cantidad_solicitada < 1) {
                    throw new RuntimeException("La cantidad de {$detalle->producto->nombre} debe ser mayor que cero.");
                }

                if (!$this->unidadAutorizadaPorValencia($detalle->producto_id, $detalle->unidad_medida_id)) {
                    throw new RuntimeException(
                        "La unidad de {$detalle->producto->nombre} ya no está autorizada por el historial de traslados de Valencia."
                    );
                }

                $productoValenciaId = ProductoValenciaZenvy::obtenerIdValencia($detalle->producto_id);
                if (!$productoValenciaId || (int) $productoValenciaId !== (int) $detalle->producto_externo_id) {
                    throw new RuntimeException(
                        "El mapeo de {$detalle->producto->nombre} con Valencia cambió. Prepare un nuevo traslado."
                    );
                }

                $lotes = DB::table('recibido_bodega as rb')
                    ->join('seccion as s', 's.id', '=', 'rb.seccion_id')
                    ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
                    ->join('bodega as b', 'b.id', '=', 'sg.bodega_id')
                    ->where('b.id', $traslado->bodega_origen_id)
                    ->where('b.cuenta_como_disponible', 1)
                    ->where('rb.producto_id', $detalle->producto_id)
                    ->where('rb.precio_venta_id', $detalle->precio_venta_id)
                    ->where('rb.estado_id', 1)
                    ->where('rb.cantidad_disponible', '>', 0)
                    ->orderBy('rb.fecha_recibido')
                    ->orderBy('rb.id')
                    ->select('rb.id', 'rb.cantidad_disponible')
                    ->lockForUpdate()
                    ->get();

                if ((int) $lotes->sum('cantidad_disponible') < $detalle->cantidad_solicitada) {
                    throw new RuntimeException(
                        "Stock insuficiente para {$detalle->producto->nombre}. Disponible: " .
                        (int) $lotes->sum('cantidad_disponible') .
                        ", solicitado: {$detalle->cantidad_solicitada}."
                    );
                }

                $pendiente = $detalle->cantidad_solicitada;
                foreach ($lotes as $lote) {
                    if ($pendiente === 0) {
                        break;
                    }

                    $cantidad = min($pendiente, (int) $lote->cantidad_disponible);
                    $nuevoSaldo = (int) $lote->cantidad_disponible - $cantidad;

                    DB::table('recibido_bodega')->where('id', $lote->id)->update([
                        'cantidad_disponible' => $nuevoSaldo,
                        'estado_id' => $nuevoSaldo === 0 ? 2 : 1,
                        'updated_at' => now(),
                    ]);

                    TrasladoLote::create([
                        'traslado_detalle_id' => $detalle->id,
                        'recibido_bodega_id' => $lote->id,
                        'cantidad' => $cantidad,
                    ]);

                    MovimientoInventario::create([
                        'traslado_id' => $traslado->id,
                        'traslado_detalle_id' => $detalle->id,
                        'recibido_bodega_id' => $lote->id,
                        'producto_id' => $detalle->producto_id,
                        'bodega_id' => $traslado->bodega_origen_id,
                        'tipo' => 'salida_traslado',
                        'cantidad' => -$cantidad,
                        'saldo_anterior' => $lote->cantidad_disponible,
                        'saldo_nuevo' => $nuevoSaldo,
                        'referencia' => $traslado->uuid,
                        'usuario_id' => $usuarioId,
                    ]);

                    $pendiente -= $cantidad;
                }

                $detalle->update(['cantidad_enviada' => $detalle->cantidad_solicitada]);
            }

            $traslado->update([
                'estado' => 'en_transito',
                'fecha_despacho' => now(),
                'usuario_despacho_id' => $usuarioId,
            ]);

            $this->registrarBitacora($traslado, $usuarioId, 'Despachar traslado', 'Existencia descontada y traslado enviado a Valencia.');

            return $traslado->fresh(['detalles.producto', 'bodegaOrigen']);
        });
    }

    public function registrarEntradaValencia(array $datos): Traslado
    {
        return DB::transaction(function () use ($datos): Traslado {
            $existente = Traslado::where('uuid', $datos['uuid'])
                ->orWhere(function ($query) use ($datos): void {
                    $query->where('sistema_origen', 'valencia')
                        ->where('referencia_externa', $datos['referencia_externa']);
                })
                ->first();

            if ($existente) {
                return $existente->load('detalles.producto', 'bodegaDestino');
            }

            $bodegaDestino = DB::table('bodega')
                ->where('id', $datos['bodega_destino_id'])
                ->where('estado_id', 1)
                ->where('cuenta_como_disponible', 1)
                ->first();

            if (!$bodegaDestino) {
                throw ValidationException::withMessages([
                    'bodega_destino_id' => 'La bodega física de destino no es válida.',
                ]);
            }

            $bodegaTransito = DB::table('bodega')
                ->where('tienda_id', $bodegaDestino->tienda_id)
                ->where('tipo_bodega', 'transito_entrada')
                ->where('estado_id', 1)
                ->first();
            if (!$bodegaTransito) {
                throw new RuntimeException('No está configurada la bodega de tránsito desde Valencia.');
            }

            $traslado = Traslado::create([
                'uuid' => $datos['uuid'],
                'codigo' => 'VAL-' . substr($datos['referencia_externa'], 0, 24) . '-' . substr(str_replace('-', '', $datos['uuid']), 0, 8),
                'direccion' => 'valencia_zenvy',
                'sistema_origen' => 'valencia',
                'referencia_externa' => $datos['referencia_externa'],
                'bodega_origen_id' => $bodegaTransito->id,
                'bodega_destino_id' => $bodegaDestino->id,
                'estado' => 'en_transito',
                'fecha_solicitud' => $datos['fecha_solicitud'] ?? $datos['fecha_despacho'],
                'fecha_despacho' => $datos['fecha_despacho'],
                'comentario' => $datos['comentario'] ?? null,
                'metadata' => $datos['metadata'] ?? null,
            ]);

            $productosAgrupados = collect($datos['productos'])
                ->groupBy(fn (array $item): string => $item['producto_id_valencia'] . '|' . ($item['codigo_barra'] ?? ''))
                ->map(function ($items): array {
                    $item = $items->first();
                    $item['cantidad'] = $items->sum('cantidad');
                    return $item;
                });

            foreach ($productosAgrupados as $item) {
                $productoId = ProductoValenciaZenvy::obtenerIdZenvy($item['producto_id_valencia']);
                $producto = $productoId ? Producto::find($productoId) : null;
                if (!$producto) {
                    throw ValidationException::withMessages([
                        'productos' => "El producto Valencia {$item['producto_id_valencia']} no está sincronizado en Zenvy.",
                    ]);
                }

                $presentacion = DB::table('precio_has_venta')
                    ->where('producto_id', $producto->id)
                    ->where('estado_id', 1)
                    ->when(
                        !empty($item['codigo_barra']),
                        fn ($query) => $query->where('codigo_barra', $item['codigo_barra'])
                    )
                    ->orderBy('cantidad')
                    ->first();

                if (!$presentacion) {
                    throw ValidationException::withMessages([
                        'productos' => "El producto {$producto->nombre} no tiene una presentación activa.",
                    ]);
                }

                TrasladoDetalle::create([
                    'traslado_id' => $traslado->id,
                    'producto_id' => $producto->id,
                    'producto_externo_id' => $item['producto_id_valencia'],
                    'precio_venta_id' => $presentacion->id,
                    'unidad_medida_id' => $presentacion->unidad_medida_id,
                    'cantidad_solicitada' => (int) $item['cantidad'],
                    'cantidad_enviada' => (int) $item['cantidad'],
                    'observacion' => $item['observacion'] ?? null,
                ]);
            }

            return $traslado->load('detalles.producto', 'bodegaDestino');
        });
    }

    public function recibirEntrada(int $trasladoId, array $recepciones, int $usuarioId): Traslado
    {
        return DB::transaction(function () use ($trasladoId, $recepciones, $usuarioId): Traslado {
            $traslado = Traslado::with('detalles.producto')->lockForUpdate()->findOrFail($trasladoId);

            if ($traslado->direccion !== 'valencia_zenvy' || !in_array($traslado->estado, ['en_transito', 'recibido_parcial'], true)) {
                throw new RuntimeException('Este traslado no está disponible para recepción.');
            }

            foreach ($recepciones as $recepcion) {
                $detalle = $traslado->detalles->firstWhere('id', (int) $recepcion['detalle_id']);
                if (!$detalle) {
                    throw new RuntimeException('Uno de los detalles no pertenece al traslado.');
                }

                $seccion = DB::table('seccion as s')
                    ->join('segmento as sg', 'sg.id', '=', 's.segmento_id')
                    ->where('s.id', $recepcion['seccion_id'])
                    ->where('s.estado_id', 1)
                    ->where('sg.bodega_id', $traslado->bodega_destino_id)
                    ->select('s.id')
                    ->first();
                if (!$seccion) {
                    throw new RuntimeException("La sección seleccionada para {$detalle->producto->nombre} no pertenece a la bodega destino.");
                }

                $cantidad = (int) $recepcion['cantidad_recibida'];
                $rechazada = (int) ($recepcion['cantidad_rechazada'] ?? 0);
                $pendiente = $detalle->cantidad_enviada - $detalle->cantidad_recibida - $detalle->cantidad_rechazada;
                if ($cantidad < 0 || $rechazada < 0 || ($cantidad + $rechazada) > $pendiente) {
                    throw new RuntimeException("Las cantidades de {$detalle->producto->nombre} exceden lo pendiente.");
                }

                if ($cantidad > 0) {
                    $lote = RecibidoBodega::create([
                        'producto_id' => $detalle->producto_id,
                        'seccion_id' => $seccion->id,
                        'cantidad_compra_lote' => $cantidad,
                        'cantidad_inicial_seccion' => $cantidad,
                        'cantidad_disponible' => $cantidad,
                        'fecha_recibido' => now()->toDateString(),
                        'comentario' => "Traslado {$traslado->codigo}: " . ($traslado->comentario ?? 'Recepción desde Valencia'),
                        'unidades_compra' => (string) $cantidad,
                        'precio_venta_id' => $detalle->precio_venta_id,
                        'unidad_medida_id' => $detalle->unidad_medida_id,
                        'users_registro_id' => $usuarioId,
                        'estado_id' => 1,
                    ]);

                    MovimientoInventario::create([
                        'traslado_id' => $traslado->id,
                        'traslado_detalle_id' => $detalle->id,
                        'recibido_bodega_id' => $lote->id,
                        'producto_id' => $detalle->producto_id,
                        'bodega_id' => $traslado->bodega_destino_id,
                        'tipo' => 'entrada_traslado',
                        'cantidad' => $cantidad,
                        'saldo_anterior' => 0,
                        'saldo_nuevo' => $cantidad,
                        'referencia' => $traslado->uuid,
                        'usuario_id' => $usuarioId,
                    ]);
                }

                $detalle->increment('cantidad_recibida', $cantidad);
                $detalle->increment('cantidad_rechazada', $rechazada);
            }

            $traslado->load('detalles');
            $pendienteTotal = $traslado->detalles->sum(
                fn (TrasladoDetalle $detalle): int => $detalle->cantidad_enviada - $detalle->cantidad_recibida - $detalle->cantidad_rechazada
            );
            $recibidoTotal = $traslado->detalles->sum('cantidad_recibida');
            $estado = $pendienteTotal === 0 ? 'recibido' : ($recibidoTotal > 0 ? 'recibido_parcial' : 'en_transito');

            $traslado->update([
                'estado' => $estado,
                'fecha_recepcion' => $estado === 'recibido' ? now() : null,
                'usuario_recepcion_id' => $usuarioId,
            ]);

            $this->registrarBitacora($traslado, $usuarioId, 'Recibir traslado', "Traslado recibido con estado {$estado}.");

            return $traslado->fresh(['detalles.producto', 'bodegaDestino']);
        });
    }

    public function confirmarRecepcionValencia(string $uuid, string $recepcionUuid, array $productos): Traslado
    {
        return DB::transaction(function () use ($uuid, $recepcionUuid, $productos): Traslado {
            $traslado = Traslado::with('detalles')->where('uuid', $uuid)->lockForUpdate()->firstOrFail();
            $metadata = $traslado->metadata ?? [];
            $recepcionesProcesadas = $metadata['recepciones_valencia'] ?? [];
            if (in_array($recepcionUuid, $recepcionesProcesadas, true)) {
                return $traslado->load('detalles.producto');
            }

            if ($traslado->direccion !== 'zenvy_valencia' || !in_array($traslado->estado, ['en_transito', 'recibido_parcial'], true)) {
                throw new RuntimeException('El traslado no está pendiente de confirmación por Valencia.');
            }

            foreach ($productos as $producto) {
                $detalleId = isset($producto['detalle_id_zenvy']) ? (int) $producto['detalle_id_zenvy'] : null;
                $detallesProducto = $traslado->detalles->where(
                    'producto_externo_id',
                    (int) $producto['producto_id_valencia']
                );
                $detalle = $detalleId
                    ? $detallesProducto->firstWhere('id', $detalleId)
                    : ($detallesProducto->count() === 1 ? $detallesProducto->first() : null);
                if (!$detalle) {
                    throw new RuntimeException(
                        "No se pudo identificar de forma única el detalle del producto Valencia {$producto['producto_id_valencia']}."
                    );
                }

                $recibida = (int) $producto['cantidad_recibida'];
                $rechazada = (int) ($producto['cantidad_rechazada'] ?? 0);
                $pendiente = $detalle->cantidad_enviada - $detalle->cantidad_recibida - $detalle->cantidad_rechazada;
                if ($recibida < 0 || $rechazada < 0 || ($recibida + $rechazada) > $pendiente) {
                    throw new RuntimeException('La confirmación de Valencia excede la cantidad pendiente.');
                }
                if (($recibida + $rechazada) < 1) {
                    throw new RuntimeException('La confirmación de Valencia debe procesar al menos una unidad.');
                }

                $detalle->increment('cantidad_recibida', $recibida);
                $detalle->increment('cantidad_rechazada', $rechazada);
            }

            $traslado->load('detalles');
            $pendiente = $traslado->detalles->sum(
                fn (TrasladoDetalle $detalle): int => $detalle->cantidad_enviada - $detalle->cantidad_recibida - $detalle->cantidad_rechazada
            );
            $estado = $pendiente === 0 ? 'recibido' : 'recibido_parcial';
            $recepcionesProcesadas[] = $recepcionUuid;
            $metadata['recepciones_valencia'] = $recepcionesProcesadas;
            $traslado->update([
                'estado' => $estado,
                'fecha_recepcion' => $estado === 'recibido' ? now() : null,
                'metadata' => $metadata,
            ]);

            return $traslado->fresh('detalles.producto');
        });
    }

    public function cancelarBorrador(int $trasladoId, int $usuarioId): void
    {
        DB::transaction(function () use ($trasladoId, $usuarioId): void {
            $traslado = Traslado::lockForUpdate()->findOrFail($trasladoId);
            if ($traslado->estado !== 'borrador') {
                throw new RuntimeException('Solo se pueden cancelar traslados que aún no han sido despachados.');
            }
            $traslado->update(['estado' => 'cancelado']);
            $this->registrarBitacora($traslado, $usuarioId, 'Cancelar traslado', 'Borrador de traslado cancelado.');
        });
    }

    private function registrarBitacora(Traslado $traslado, int $usuarioId, string $accion, string $descripcion): void
    {
        Bitacora::registrar(
            $usuarioId,
            'Inventario - Traslados',
            $accion,
            "{$descripcion} Código: {$traslado->codigo}.",
            $traslado->id,
            'traslado',
            null,
            ['estado' => $traslado->estado, 'uuid' => $traslado->uuid]
        );
    }

    private function unidadAutorizadaPorValencia(int $productoId, int $unidadMedidaId): bool
    {
        $unidadMapeada = DB::table('id_zenvy_valencia')
            ->where('id_zenvy', $unidadMedidaId)
            ->where('tipo_dato_migrado_id', 5)
            ->exists();

        if (!$unidadMapeada) {
            return false;
        }

        return DB::table('id_zenvy_valencia as iz')
            ->join('compra_has_producto as chp', 'chp.compra_id', '=', 'iz.id_zenvy')
            ->where('iz.tipo_dato_migrado_id', 9)
            ->where('chp.producto_id', $productoId)
            ->where('chp.unidad_medida_id', $unidadMedidaId)
            ->exists();
    }
}
