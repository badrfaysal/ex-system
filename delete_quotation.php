<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

$quoteNumber = 'QT-2026-09-0010'; // يمكنك تغيير رقم العرض من هنا

$quote = Quotation::with(['items', 'sends', 'salesOrders', 'expenses', 'purchaseInvoices', 'salesInvoices', 'receipts'])->where('quote_number', $quoteNumber)->first();

if (!$quote) {
    echo "لم يتم العثور على عرض السعر رقم: {$quoteNumber}\n";
    exit;
}

echo "تم العثور على عرض السعر...\n";

DB::transaction(function() use ($quote) {
    // حذف الأصناف المرتبطة بعرض السعر
    if ($quote->items()->exists()) {
        $quote->items()->delete();
        echo "- تم حذف الأصناف\n";
    }
    
    // حذف سجلات الإرسال
    if ($quote->sends()->exists()) {
        $quote->sends()->delete();
        echo "- تم حذف سجلات الإرسال\n";
    }
    
    // أوامر البيع المرتبطة
    foreach($quote->salesOrders as $so) {
        $so->items()->delete(); // حذف أصناف أمر البيع
        $so->delete();
        echo "- تم حذف أمر البيع المرتبط\n";
    }
    
    // فواتير الشراء المرتبطة
    foreach($quote->purchaseInvoices as $pi) {
        $pi->items()->delete();
        $pi->delete();
        echo "- تم حذف فاتورة الشراء المرتبطة\n";
    }
    
    // فواتير البيع المرتبطة
    foreach($quote->salesInvoices as $si) {
        $si->items()->delete();
        $si->delete();
        echo "- تم حذف فاتورة البيع المرتبطة\n";
    }
    
    // المصروفات المرتبطة
    if ($quote->expenses()->exists()) {
        $quote->expenses()->delete();
        echo "- تم حذف المصروفات المرتبطة\n";
    }

    // سندات القبض المرتبطة
    if ($quote->receipts()->exists()) {
        $quote->receipts()->delete();
        echo "- تم حذف سندات القبض المرتبطة\n";
    }
    
    // أخيراً حذف عرض السعر نفسه
    $quote->delete();
    echo "- تم حذف عرض السعر الأساسي بنجاح!\n";
});

echo "\nتمت عملية الحذف بالكامل.\n";
