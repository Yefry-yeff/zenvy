<?php

namespace App\Console\Commands;

use App\Models\Factura;
use App\Services\FacturaTotalesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcularResumenFiscalFacturas extends Command
{
    protected $signature = 'facturas:recalcular-resumen-fiscal
                            {--factura= : ID o número completo de una factura}';

    protected $description = 'Recalcula subtotales y descuentos gravados/exentos desde las líneas de factura';

    public function handle(FacturaTotalesService $totalesService): int
    {
        $query = Factura::query()->orderBy('id');
        $factura = $this->option('factura');

        if ($factura !== null) {
            $query->where(function ($consulta) use ($factura) {
                $consulta->where('numero_factura', $factura);

                if (ctype_digit((string) $factura)) {
                    $consulta->orWhere('id', (int) $factura);
                }
            });
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->warn('No se encontraron facturas para recalcular.');
            return self::SUCCESS;
        }

        $actualizadas = 0;
        $omitidas = [];
        $barra = $this->output->createProgressBar($total);
        $barra->start();

        $query->chunkById(200, function ($facturas) use (
            $totalesService,
            &$actualizadas,
            &$omitidas,
            $barra
        ) {
            $totalesService->aplicar($facturas);

            DB::transaction(function () use ($facturas, &$actualizadas, &$omitidas, $barra) {
                foreach ($facturas as $factura) {
                    $totalReconstruido = round(
                        (float) $factura->reporte_gravado
                        + (float) $factura->reporte_exento
                        + (float) $factura->reporte_isv,
                        2
                    );

                    if (abs($totalReconstruido - (float) $factura->total) > 0.02) {
                        $omitidas[] = [
                            $factura->id,
                            $factura->numero_factura,
                            number_format($totalReconstruido, 2),
                            number_format((float) $factura->total, 2),
                        ];
                        $barra->advance();
                        continue;
                    }

                    DB::table('factura')
                        ->where('id', $factura->id)
                        ->update([
                            'sub_total' => round(
                                (float) $factura->reporte_gravado + (float) $factura->reporte_exento,
                                2
                            ),
                            'sub_total_grabado' => $factura->reporte_subtotal_gravado,
                            'sub_total_exento' => $factura->reporte_subtotal_exento,
                            'descuento_gravado' => $factura->reporte_descuento_gravado,
                            'descuento_exento' => $factura->reporte_descuento_exento,
                        ]);

                    $actualizadas++;
                    $barra->advance();
                }
            });
        });

        $barra->finish();
        $this->newLine(2);
        $this->info("Facturas actualizadas: {$actualizadas}");

        if ($omitidas !== []) {
            $this->warn('Facturas omitidas por diferencias entre líneas y total: ' . count($omitidas));
            $this->table(['ID', 'Factura', 'Total reconstruido', 'Total guardado'], $omitidas);
        }

        return self::SUCCESS;
    }
}