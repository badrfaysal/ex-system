<?php
require __DIR__."/vendor/autoload.php"; 
$app = require_once __DIR__."/bootstrap/app.php"; 
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); 
$kernel->bootstrap(); 

$query = \App\Models\Quotation::query()
    ->withSum('receipts as revenue_sum', 'base_amount')
    ->withSum('expenses as expenses_sum', 'base_amount')
    ->withSum('purchaseInvoices as purchases_sum', 'base_grand_total')
    ->where('quote_number', 'QT-2026-10-0008')
    ->with('client');

$q = $query->first();

$q->revenue = (float) $q->revenue_sum;
$q->cost    = (float) $q->expenses_sum + (float) $q->purchases_sum;
$q->profit  = $q->revenue - $q->cost;

dump([
    'revenue_sum' => $q->revenue_sum,
    'expenses_sum' => $q->expenses_sum,
    'purchases_sum' => $q->purchases_sum,
    'revenue' => $q->revenue,
    'cost' => $q->cost,
    'profit' => $q->profit,
]);
