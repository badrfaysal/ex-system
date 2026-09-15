<?php

use Illuminate\Support\Facades\Route;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

// ... (باقي المسارات الموجودة في الملف) ...

Route::get('/delete-qt-10', function () {
    $quoteNumber = 'QT-2026-09-0010';
    $quote = Quotation::with(['items', 'sends', 'salesOrders', 'expenses', 'purchaseInvoices', 'salesInvoices', 'receipts'])
        ->where('quote_number', $quoteNumber)->first();

    if (!$quote) {
        return "لم يتم العثور على عرض السعر: " . $quoteNumber;
    }

    DB::transaction(function() use ($quote) {
        if ($quote->items()->exists()) $quote->items()->delete();
        if ($quote->sends()->exists()) $quote->sends()->delete();
        
        foreach($quote->salesOrders as $so) {
            $so->items()->delete();
            $so->delete();
        }
        foreach($quote->purchaseInvoices as $pi) {
            $pi->items()->delete();
            $pi->delete();
        }
        foreach($quote->salesInvoices as $si) {
            $si->items()->delete();
            $si->delete();
        }
        if ($quote->expenses()->exists()) $quote->expenses()->delete();
        if ($quote->receipts()->exists()) $quote->receipts()->delete();
        
        $quote->delete();
    });

    return "تم حذف عرض السعر وكل ما يرتبط به بنجاح!";
});
