<?php
require __DIR__."/vendor/autoload.php"; 
$app = require_once __DIR__."/bootstrap/app.php"; 
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); 
$kernel->bootstrap(); 

$query = \App\Models\Client::query()
    ->whereHas("salesInvoices")
    ->withSum("salesInvoices as invoiced_total", "grand_total")
    ->withSum("receipts as collected_total", \Illuminate\Support\Facades\DB::raw("COALESCE(foreign_amount, amount)"))
    ->havingRaw("(COALESCE(invoiced_total, 0) - COALESCE(collected_total, 0)) > 0");

$clientIds = clone $query;

$invoicedByCurrency = \Illuminate\Support\Facades\DB::table('sales_invoices')
    ->whereIn('client_id', (clone $query)->pluck('clients.id'))
    ->groupByRaw('COALESCE(currency, "EGP")')
    ->selectRaw('COALESCE(currency, "EGP") as currency_group, SUM(grand_total) as total')
    ->pluck('total', 'currency_group');
    
$collectedByCurrency = \Illuminate\Support\Facades\DB::table('client_receipts')
    ->whereIn('client_id', (clone $query)->pluck('clients.id'))
    ->groupByRaw('COALESCE(foreign_currency, currency, "EGP")')
    ->selectRaw('COALESCE(foreign_currency, currency, "EGP") as currency_group, SUM(COALESCE(foreign_amount, amount)) as total')
    ->pluck('total', 'currency_group');
    
$currencies = collect(array_keys($invoicedByCurrency->toArray()))
    ->merge(array_keys($collectedByCurrency->toArray()))
    ->unique();
    
$summary = [];
foreach ($currencies as $curr) {
    $inv = $invoicedByCurrency[$curr] ?? 0;
    $col = $collectedByCurrency[$curr] ?? 0;
    $summary[$curr] = [
        'invoiced'  => $inv,
        'collected' => $col,
        'balance'   => $inv - $col,
    ];
}

dump($summary);
