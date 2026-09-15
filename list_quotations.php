<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

$quotes = DB::table('quotations')->get();
foreach($quotes as $q) {
    echo "ID: $q->id | Number: $q->quote_number | Client: $q->client_id | Total: $q->grand_total\n";
}
