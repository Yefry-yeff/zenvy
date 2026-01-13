<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cai = DB::table('cai')->orderBy('id', 'desc')->first();
if($cai) {
    echo "CAI ID: {$cai->id}\n";
    echo "Numero: {$cai->numero_cai}\n";
    echo "Estado: {$cai->estado_id}\n";
} else {
    echo "No hay CAI registrado\n";
}
