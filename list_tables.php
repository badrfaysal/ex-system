<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = DB::select('SHOW TABLES');
$tableNames = array_map(function($t) {
    return array_values((array)$t)[0];
}, $tables);

echo implode("\n", $tableNames);
