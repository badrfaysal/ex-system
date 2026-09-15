<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$clients = DB::table('clients')->get();
foreach($clients as $c) {
    echo "ID: $c->id | Name: $c->company_name\n";
}
