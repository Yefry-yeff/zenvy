<?php

namespace Tests\Support;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class CriticalDatabaseTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $database = DB::connection()->getDatabaseName();

        $this->assertStringStartsWith(
            'paperland_fase0_',
            $database,
            "La suite critica no puede ejecutarse sobre la base {$database}."
        );
    }
}