<?php
require __DIR__."/vendor/autoload.php"; 
$app = require_once __DIR__."/bootstrap/app.php"; 
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); 
$kernel->bootstrap(); 

$quotation = \App\Models\Quotation::where('quote_number', 'QT-2026-10-0008')->first();
if($quotation) {
    dump("Quotation: " . $quotation->id);
    dump("Receipts:");
    dump(\App\Models\ClientReceipt::where('quotation_id', $quotation->id)->get()->toArray());
    dump("Expenses:");
    dump(\App\Models\Expense::where('quotation_id', $quotation->id)->get()->toArray());
    dump("Purchase Invoices:");
    dump(\App\Models\PurchaseInvoice::where('quotation_id', $quotation->id)->get()->toArray());
} else {
    dump("Quotation not found");
}
