<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

$quoteNumber = 'QT-2026-09-0010'; // يمكنك تغيير رقم العرض من هنا

$quote = Quotation::with(['items', 'sends', 'salesOrders', 'expenses', 'purchaseInvoices', 'salesInvoices', 'receipts'])->where('quote_number', $quoteNumber)->first();

echo "<pre style='font-family: Tahoma; direction: rtl; font-size: 16px;'>";

if (!$quote) {
    echo "لم يتم العثور على عرض السعر رقم: {$quoteNumber}\n";
    exit;
}

echo "تم العثور على عرض السعر...\n";

DB::transaction(function() use ($quote) {
    if ($quote->items()->exists()) {
        $quote->items()->delete();
        echo "- تم حذف الأصناف\n";
    }
    
    if ($quote->sends()->exists()) {
        $quote->sends()->delete();
        echo "- تم حذف سجلات الإرسال\n";
    }
    
    foreach($quote->salesOrders as $so) {
        $so->items()->delete();
        $so->delete();
        echo "- تم حذف أمر البيع المرتبط\n";
    }
    
    foreach($quote->purchaseInvoices as $pi) {
        $pi->items()->delete();
        $pi->delete();
        echo "- تم حذف فاتورة الشراء المرتبطة\n";
    }
    
    foreach($quote->salesInvoices as $si) {
        $si->items()->delete();
        $si->delete();
        echo "- تم حذف فاتورة البيع المرتبطة\n";
    }
    
    if ($quote->expenses()->exists()) {
        $quote->expenses()->delete();
        echo "- تم حذف المصروفات المرتبطة\n";
    }

    if ($quote->receipts()->exists()) {
        $quote->receipts()->delete();
        echo "- تم حذف سندات القبض المرتبطة\n";
    }
    
    $quote->delete();
    echo "<b>- تم حذف عرض السعر الأساسي بنجاح!</b>\n";
});

echo "\nتمت عملية الحذف بالكامل.\n";
echo "</pre>";
