<?php

namespace Tests\Critical;

use Illuminate\Support\Facades\DB;
use Tests\Support\CriticalDatabaseTestCase;

class DatabaseConstraintTest extends CriticalDatabaseTestCase
{
    public function test_critical_business_keys_have_unique_indexes(): void
    {
        $this->assertUniqueIndex('factura', ['numero_factura']);
        $this->assertUniqueIndex('facturas_anuladas', ['factura_id']);
        $this->assertUniqueIndex('id_zenvy_valencia', ['tipo_dato_migrado_id', 'id_valencia']);
    }

    private function assertUniqueIndex(string $table, array $columns): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))
            ->where('Non_unique', 0)
            ->groupBy('Key_name')
            ->map(fn ($parts) => $parts->sortBy('Seq_in_index')->pluck('Column_name')->all());

        $this->assertTrue(
            $indexes->contains(fn (array $indexedColumns): bool => $indexedColumns === $columns),
            sprintf('%s debe tener un indice unico sobre (%s).', $table, implode(', ', $columns))
        );
    }
}