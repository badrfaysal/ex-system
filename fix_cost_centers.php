<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Quotation;

// find duplicates
$duplicates = Quotation::select('cost_center_name', \DB::raw('count(*) as count'))
    ->groupBy('cost_center_name')
    ->having('count', '>', 1)
    ->get();

foreach ($duplicates as $dup) {
    echo $dup->cost_center_name . " -> " . $dup->count . "\n";
    $qs = Quotation::where('cost_center_name', $dup->cost_center_name)->get();
    foreach ($qs as $q) {
        $clientName = $q->client ? $q->client->displayName('ar') : '';
        $newName = trim('مركز تكلفة ' . $clientName . ' - ' . $q->quote_number);
        $q->cost_center_name = $newName;
        $q->save();
        echo "   Updated to: $newName\n";
    }
}
