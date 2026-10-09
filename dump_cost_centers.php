<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Quotation;

$qs = Quotation::all();
foreach ($qs as $q) {
    echo $q->quote_number . " : " . $q->cost_center_name . "\n";
}
