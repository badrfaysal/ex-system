<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$columns = \Illuminate\Support\Facades\DB::select("SHOW COLUMNS FROM quotations LIKE 'cost_center_name'");
print_r($columns);
