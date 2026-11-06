<?php

namespace App;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ملف إدارة وترحيل السيرفر الموحد (كل شيء في ملف واحد)
 * 
 * الرابط على السيرفر:
 * https://your-domain.com/server-deploy?key=efc2026
 * 
 * أو للتشغيل الفوري الشامل:
 * https://your-domain.com/server-deploy?key=efc2026&action=all
 */
class ServerDeploy
{
    protected const DEFAULT_KEY = 'efc2026';

    public static function run(Request $request)
    {
        $expectedKey = env('DEPLOY_KEY', self::DEFAULT_KEY);
        $providedKey = $request->input('key');

        if ($providedKey === $expectedKey) {
            session(['deploy_authenticated' => true]);
        }

        $isAuthenticated = auth()->check() || session('deploy_authenticated') === true;

        if (!$isAuthenticated) {
            if ($request->isMethod('post') && $request->has('pin_key')) {
                if ($request->input('pin_key') === $expectedKey) {
                    session(['deploy_authenticated' => true]);
                    return redirect()->to($request->fullUrl());
                } else {
                    return self::renderLogin('مفتاح الأمان غير صحيح. برجاء المحاولة مرة أخرى.');
                }
            }
            return self::renderLogin();
        }

        $action = $request->input('action');
        $output = '';
        $cleanReport = null;
        $status = null;
        $message = '';

        if ($action === 'migrate') {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $output .= Artisan::output();
                if (class_exists(Setting::class) && method_exists(Setting::class, 'ensureExpenseCategoriesSeeded')) {
                    Setting::ensureExpenseCategoriesSeeded();
                    $output .= "\n✓ تم تهيئة بنود المصروفات الأساسية وحذف البنود الزائدة تلقائياً.\n";
                }
                $status = 'success';
                $message = 'تم تنفيذ كافة ملفات المايجريشن بنجاح على قاعدة بيانات السيرفر!';
            } catch (\Throwable $e) {
                $status = 'error';
                $message = 'خطأ أثناء المايجريشن: ' . $e->getMessage();
                $output .= "\n" . $e->getTraceAsString();
            }
        } elseif ($action === 'clean-test') {
            try {
                $cleanReport = self::cleanTestData(dryRun: false);
                $status = 'success';
                $message = 'تم تنظيف وحذف كافة سجلات الـ TEST والبيانات التجريبية بنجاح من قاعدة بيانات السيرفر!';
            } catch (\Throwable $e) {
                $status = 'error';
                $message = 'خطأ أثناء تنظيف البيانات: ' . $e->getMessage();
            }
        } elseif ($action === 'check-test') {
            try {
                $cleanReport = self::cleanTestData(dryRun: true);
                $status = 'info';
                $message = 'تم فحص قاعدة بيانات السيرفر وحصر السجلات التجريبية الحالية.';
            } catch (\Throwable $e) {
                $status = 'error';
                $message = 'خطأ أثناء الفحص: ' . $e->getMessage();
            }
        } elseif ($action === 'optimize') {
            try {
                Artisan::call('optimize:clear');
                $output .= Artisan::output();
                try {
                    Artisan::call('storage:link');
                    $output .= "\n" . Artisan::output();
                } catch (\Throwable $e) {}
                $status = 'success';
                $message = 'تم مسح الكاش وتحديث رابط التخزين بنجاح!';
            } catch (\Throwable $e) {
                $status = 'error';
                $message = 'خطأ: ' . $e->getMessage();
            }
        } elseif ($action === 'all') {
            try {
                // 1. تشغيل المايجريشن
                Artisan::call('migrate', ['--force' => true]);
                $output .= "=== نتيجة المايجريشن على السيرفر ===\n" . Artisan::output();
                if (class_exists(Setting::class) && method_exists(Setting::class, 'ensureExpenseCategoriesSeeded')) {
                    Setting::ensureExpenseCategoriesSeeded();
                    $output .= "✓ تم تحديث بنود المصروفات وتصفير بيانات البنك الافتراضية.\n\n";
                }

                // 2. تنظيف بيانات الـ Test
                $cleanReport = self::cleanTestData(dryRun: false);
                $output .= "=== نتيجة تنظيف بيانات الـ TEST ===\n";
                $output .= "• العملاء التجريبيين: {$cleanReport['clients']}\n";
                $output .= "• عروض الأسعار التجريبية: {$cleanReport['quotations']}\n";
                $output .= "• الفواتير والمستحقات: " . ($cleanReport['sales_invoices'] + $cleanReport['purchase_invoices'] + $cleanReport['client_receipts'] + $cleanReport['vendor_payments']) . "\n";
                $output .= "• الحسابات والمحافظ التجريبية: {$cleanReport['wallets']}\n";
                $output .= "• المصروفات التجريبية: {$cleanReport['expenses']}\n\n";

                // 3. تهيئة بنود المصروفات والخدمات الخاصة (شهادات/حراسة/تغليف)
                try {
                    \App\Models\Setting::ensureExpenseCategoriesSeeded();
                    \App\Models\Setting::ensureOtherServicesSeeded();
                    $output .= "=== تهيئة الإعدادات ===\nتم تحديث بنود المصروفات وخدمات النظام الخاصة بنجاح.\n\n";
                } catch (\Throwable $e) {}

                // 4. مسح الكاش وربط التخزين
                Artisan::call('optimize:clear');
                $output .= "=== نتيجة مسح الكاش ===\n" . Artisan::output();
                try {
                    Artisan::call('storage:link');
                } catch (\Throwable $e) {}

                $status = 'success';
                $message = 'تم تشغيل المايجريشن وحذف بيانات الـ TEST ومسح الكاش بنجاح دفعة واحدة!';
            } catch (\Throwable $e) {
                $status = 'error';
                $message = 'خطأ: ' . $e->getMessage();
                $output .= "\n" . $e->getTraceAsString();
            }
        }

        return self::renderDashboard($status, $message, $output, $cleanReport);
    }

    /**
     * دالة تنظيف البيانات التجريبية
     */
    public static function cleanTestData(bool $dryRun = false): array
    {
        $report = [
            'clients'           => 0,
            'quotations'        => 0,
            'sales_orders'      => 0,
            'sales_invoices'    => 0,
            'purchase_invoices' => 0,
            'client_receipts'   => 0,
            'vendor_payments'   => 0,
            'expenses'          => 0,
            'revenues'          => 0,
            'wallets'           => 0,
            'vendors'           => 0,
            'items'             => 0,
            'printed_labels'    => 0,
            'activity_logs'     => 0,
        ];

        // 1. العملاء التجريبيين
        $clientIds = [];
        if (Schema::hasTable('clients')) {
            $clientIds = DB::table('clients')->where(function ($q) {
                self::addSearchPattern($q, 'clients', ['company_name', 'company_name_en', 'contact_person', 'email', 'phone']);
            })->pluck('id')->all();
        }

        // 2. عروض الأسعار ومراكز التكلفة
        $quoteIds = [];
        if (Schema::hasTable('quotations')) {
            $quoteIds = DB::table('quotations')->where(function ($q) use ($clientIds) {
                if (!empty($clientIds) && Schema::hasColumn('quotations', 'client_id')) {
                    $q->whereIn('client_id', $clientIds);
                }
                self::addSearchPattern($q, 'quotations', ['quote_number', 'cost_center_name', 'terms']);
            })->pluck('id')->all();
        }

        // 3. أوامر البيع (العمود هو so_number وليس order_number)
        $soIds = [];
        if (Schema::hasTable('sales_orders')) {
            $soIds = DB::table('sales_orders')->where(function ($q) use ($quoteIds, $clientIds) {
                if (!empty($quoteIds) && Schema::hasColumn('sales_orders', 'quotation_id')) {
                    $q->whereIn('quotation_id', $quoteIds);
                }
                if (!empty($clientIds) && Schema::hasColumn('sales_orders', 'client_id')) {
                    $q->orWhereIn('client_id', $clientIds);
                }
                self::addSearchPattern($q, 'sales_orders', ['so_number', 'terms']);
            })->pluck('id')->all();
        }

        // 4. فواتير المبيعات
        $siIds = [];
        if (Schema::hasTable('sales_invoices')) {
            $siIds = DB::table('sales_invoices')->where(function ($q) use ($soIds, $quoteIds, $clientIds) {
                if (!empty($soIds) && Schema::hasColumn('sales_invoices', 'sales_order_id')) {
                    $q->whereIn('sales_order_id', $soIds);
                }
                if (!empty($quoteIds) && Schema::hasColumn('sales_invoices', 'quotation_id')) {
                    $q->orWhereIn('quotation_id', $quoteIds);
                }
                if (!empty($clientIds) && Schema::hasColumn('sales_invoices', 'client_id')) {
                    $q->orWhereIn('client_id', $clientIds);
                }
                self::addSearchPattern($q, 'sales_invoices', ['invoice_number', 'notes']);
            })->pluck('id')->all();
        }

        // 5. فواتير المشتريات
        $piIds = [];
        if (Schema::hasTable('purchase_invoices')) {
            $piIds = DB::table('purchase_invoices')->where(function ($q) use ($quoteIds) {
                if (!empty($quoteIds) && Schema::hasColumn('purchase_invoices', 'quotation_id')) {
                    $q->whereIn('quotation_id', $quoteIds);
                }
                self::addSearchPattern($q, 'purchase_invoices', ['invoice_number', 'vendor_invoice_number', 'notes']);
            })->pluck('id')->all();
        }

        // 6. الحسابات البنكية والمحافظ
        $walletIds = [];
        if (Schema::hasTable('wallets')) {
            $walletIds = DB::table('wallets')->where(function ($q) {
                self::addSearchPattern($q, 'wallets', ['name', 'notes']);
            })->pluck('id')->all();
        }

        // 7. سندات القبض (مستحقات عملاء)
        $receiptIds = [];
        if (Schema::hasTable('client_receipts')) {
            $receiptIds = DB::table('client_receipts')->where(function ($q) use ($clientIds, $siIds, $quoteIds, $walletIds) {
                if (!empty($clientIds) && Schema::hasColumn('client_receipts', 'client_id')) {
                    $q->whereIn('client_id', $clientIds);
                }
                if (!empty($siIds) && Schema::hasColumn('client_receipts', 'sales_invoice_id')) {
                    $q->orWhereIn('sales_invoice_id', $siIds);
                }
                if (!empty($quoteIds) && Schema::hasColumn('client_receipts', 'quotation_id')) {
                    $q->orWhereIn('quotation_id', $quoteIds);
                }
                if (!empty($walletIds) && Schema::hasColumn('client_receipts', 'wallet_id')) {
                    $q->orWhereIn('wallet_id', $walletIds);
                }
                self::addSearchPattern($q, 'client_receipts', ['notes']);
            })->pluck('id')->all();
        }

        // 8. سندات الصرف (مستحقات موردين)
        $paymentIds = [];
        if (Schema::hasTable('vendor_payments')) {
            $paymentIds = DB::table('vendor_payments')->where(function ($q) use ($piIds, $walletIds) {
                if (!empty($piIds) && Schema::hasColumn('vendor_payments', 'purchase_invoice_id')) {
                    $q->whereIn('purchase_invoice_id', $piIds);
                }
                if (!empty($walletIds) && Schema::hasColumn('vendor_payments', 'wallet_id')) {
                    $q->orWhereIn('wallet_id', $walletIds);
                }
                self::addSearchPattern($q, 'vendor_payments', ['notes']);
            })->pluck('id')->all();
        }

        // 9. المصروفات
        $expenseIds = [];
        if (Schema::hasTable('expenses')) {
            $expenseIds = DB::table('expenses')->where(function ($q) use ($quoteIds, $walletIds) {
                if (!empty($quoteIds) && Schema::hasColumn('expenses', 'quotation_id')) {
                    $q->whereIn('quotation_id', $quoteIds);
                }
                if (!empty($walletIds) && Schema::hasColumn('expenses', 'wallet_id')) {
                    $q->orWhereIn('wallet_id', $walletIds);
                }
                self::addSearchPattern($q, 'expenses', ['expense_number', 'description', 'notes']);
            })->pluck('id')->all();
        }

        // 10. الإيرادات
        $revenueIds = [];
        if (Schema::hasTable('revenues')) {
            $revenueIds = DB::table('revenues')->where(function ($q) use ($walletIds) {
                if (!empty($walletIds) && Schema::hasColumn('revenues', 'wallet_id')) {
                    $q->whereIn('wallet_id', $walletIds);
                }
                self::addSearchPattern($q, 'revenues', ['revenue_number', 'description', 'notes']);
            })->pluck('id')->all();
        }

        // 11. الموردين
        $vendorIds = [];
        if (Schema::hasTable('vendors')) {
            $vendorIds = DB::table('vendors')->where(function ($q) {
                self::addSearchPattern($q, 'vendors', ['vendor_code', 'name_ar', 'name_en', 'legal_name', 'email']);
            })->pluck('id')->all();
        }

        // 12. الأصناف
        $itemIds = [];
        if (Schema::hasTable('items')) {
            $itemIds = DB::table('items')->where(function ($q) {
                self::addSearchPattern($q, 'items', ['name_ar', 'name_en', 'item_code']);
            })->pluck('id')->all();
        }

        // 13. ملصقات الطباعة
        $labelIds = [];
        if (Schema::hasTable('printed_labels')) {
            $labelIds = DB::table('printed_labels')->where(function ($q) {
                self::addSearchPattern($q, 'printed_labels', ['brand_name', 'item_name', 'batch_code']);
            })->pluck('id')->all();
        }

        $report['clients']           = count($clientIds);
        $report['quotations']        = count($quoteIds);
        $report['sales_orders']      = count($soIds);
        $report['sales_invoices']    = count($siIds);
        $report['purchase_invoices'] = count($piIds);
        $report['client_receipts']   = count($receiptIds);
        $report['vendor_payments']   = count($paymentIds);
        $report['expenses']          = count($expenseIds);
        $report['revenues']          = count($revenueIds);
        $report['wallets']           = count($walletIds);
        $report['vendors']           = count($vendorIds);
        $report['items']             = count($itemIds);
        $report['printed_labels']    = count($labelIds);

        if ($dryRun) {
            return $report;
        }

        DB::transaction(function () use (
            $clientIds, $quoteIds, $soIds, $siIds, $piIds,
            $receiptIds, $paymentIds, $expenseIds, $revenueIds,
            $walletIds, $vendorIds, $itemIds, $labelIds, &$report
        ) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            try {
                if (!empty($quoteIds) && Schema::hasTable('quotation_items')) DB::table('quotation_items')->whereIn('quotation_id', $quoteIds)->delete();
                if (!empty($quoteIds) && Schema::hasTable('quotation_sends')) DB::table('quotation_sends')->whereIn('quotation_id', $quoteIds)->delete();
                if (!empty($soIds) && Schema::hasTable('sales_order_items')) DB::table('sales_order_items')->whereIn('sales_order_id', $soIds)->delete();
                if (!empty($siIds) && Schema::hasTable('sales_invoice_items')) DB::table('sales_invoice_items')->whereIn('sales_invoice_id', $siIds)->delete();
                if (!empty($piIds) && Schema::hasTable('purchase_invoice_items')) DB::table('purchase_invoice_items')->whereIn('purchase_invoice_id', $piIds)->delete();
                if (!empty($walletIds) && Schema::hasTable('wallet_transfers')) DB::table('wallet_transfers')->whereIn('from_wallet_id', $walletIds)->orWhereIn('to_wallet_id', $walletIds)->delete();
                if (!empty($itemIds) && Schema::hasTable('item_images')) DB::table('item_images')->whereIn('item_id', $itemIds)->delete();
                if (!empty($itemIds) && Schema::hasTable('item_vendor')) DB::table('item_vendor')->whereIn('item_id', $itemIds)->delete();
                if (!empty($vendorIds) && Schema::hasTable('vendor_addresses')) DB::table('vendor_addresses')->whereIn('vendor_id', $vendorIds)->delete();

                if (!empty($receiptIds) && Schema::hasTable('client_receipts')) DB::table('client_receipts')->whereIn('id', $receiptIds)->delete();
                if (!empty($paymentIds) && Schema::hasTable('vendor_payments')) DB::table('vendor_payments')->whereIn('id', $paymentIds)->delete();
                if (!empty($expenseIds) && Schema::hasTable('expenses')) DB::table('expenses')->whereIn('id', $expenseIds)->delete();
                if (!empty($revenueIds) && Schema::hasTable('revenues')) DB::table('revenues')->whereIn('id', $revenueIds)->delete();
                if (!empty($siIds) && Schema::hasTable('sales_invoices')) DB::table('sales_invoices')->whereIn('id', $siIds)->delete();
                if (!empty($piIds) && Schema::hasTable('purchase_invoices')) DB::table('purchase_invoices')->whereIn('id', $piIds)->delete();
                if (!empty($soIds) && Schema::hasTable('sales_orders')) DB::table('sales_orders')->whereIn('id', $soIds)->delete();
                if (!empty($quoteIds) && Schema::hasTable('quotations')) DB::table('quotations')->whereIn('id', $quoteIds)->delete();
                if (!empty($clientIds) && Schema::hasTable('clients')) DB::table('clients')->whereIn('id', $clientIds)->delete();
                if (!empty($walletIds) && Schema::hasTable('wallets')) DB::table('wallets')->whereIn('id', $walletIds)->delete();
                if (!empty($vendorIds) && Schema::hasTable('vendors')) DB::table('vendors')->whereIn('id', $vendorIds)->delete();
                if (!empty($itemIds) && Schema::hasTable('items')) DB::table('items')->whereIn('id', $itemIds)->delete();
                if (!empty($labelIds) && Schema::hasTable('printed_labels')) DB::table('printed_labels')->whereIn('id', $labelIds)->delete();

                if (Schema::hasTable('activity_logs')) {
                    $report['activity_logs'] = DB::table('activity_logs')
                        ->where('description', 'like', '%test%')
                        ->orWhere('description', 'like', '%تجريب%')
                        ->delete();
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        });

        Cache::flush();
        return $report;
    }

    /**
     * إضافة فلتر البحث على الأعمدة الموجودة فعلياً في الجدول فقط لمنع أخطاء SQL
     */
    protected static function addSearchPattern($query, string $table, array $columns): void
    {
        try {
            $tableCols = Schema::getColumnListing($table);
        } catch (\Throwable $e) {
            $tableCols = [];
        }

        $patterns = ['%test%', '%تجريب%', '%تست%', '%dummy%', '%وهمي%'];
        $existingCols = !empty($tableCols)
            ? array_values(array_intersect($columns, $tableCols))
            : array_values(array_filter($columns, fn($col) => Schema::hasColumn($table, $col)));

        if (empty($existingCols)) {
            return;
        }

        foreach ($existingCols as $col) {
            foreach ($patterns as $pattern) {
                $query->orWhere($col, 'like', $pattern);
            }
        }
    }

    protected static function renderLogin(?string $error = null): string
    {
        $errHtml = $error ? "<div style='background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px;font-size:13px;font-weight:bold;'>{$error}</div>" : '';
        $csrf = csrf_token();

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أداة إدارة السيرفر - قفل الأمان</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f3f4f6; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; width: 100%; max-width: 420px; padding: 32px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; box-sizing: border-box; text-align: center; }
        h1 { font-size: 20px; font-weight: 800; color: #111827; margin: 0 0 8px 0; }
        p { font-size: 13px; color: #6b7280; margin: 0 0 24px 0; line-height: 1.5; }
        input[type="password"] { width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; box-sizing: border-box; margin-bottom: 16px; text-align: center; font-family: monospace; letter-spacing: 2px; }
        button { width: 100%; padding: 12px; background: #005B9F; color: #fff; font-size: 14px; font-weight: bold; border: none; border-radius: 8px; cursor: pointer; }
        .hint { font-size: 11px; color: #9ca3af; margin-top: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:36px;margin-bottom:12px;">🔐</div>
        <h1>أداة إدارة وترحيل السيرفر</h1>
        <p>الرجاء إدخال مفتاح الأمان للتشغيل على قاعدة بيانات السيرفر.</p>
        {$errHtml}
        <form method="POST">
            <input type="hidden" name="_token" value="{$csrf}">
            <input type="password" name="pin_key" placeholder="أدخل مفتاح الأمان (مثال: efc2026)" required autofocus>
            <button type="submit">دخول وتأكيد الصلاحية</button>
        </form>
        <div class="hint">مفتاح الأمان الافتراضي: <code>efc2026</code></div>
    </div>
</body>
</html>
HTML;
    }

    protected static function renderDashboard(?string $status, string $message, string $output, ?array $cleanReport): string
    {
        $statusAlert = '';
        if ($status === 'success') $statusAlert = "<div class='alert alert-success'>✅ {$message}</div>";
        elseif ($status === 'error') $statusAlert = "<div class='alert alert-error'>❌ {$message}</div>";
        elseif ($status === 'info') $statusAlert = "<div class='alert alert-info'>ℹ️ {$message}</div>";

        $reportBox = '';
        if ($cleanReport !== null) {
            $totalDeleted = array_sum(array_slice($cleanReport, 0, 13));
            $reportBox = "
            <div class='clean-report'>
                <div class='clean-header'>
                    <strong>نتائج فحص / تنظيف البيانات التجريبية (إجمالي السجلات: {$totalDeleted})</strong>
                </div>
                <div class='grid-report'>
                    <div class='report-item'><span>العملاء:</span> <strong>{$cleanReport['clients']}</strong></div>
                    <div class='report-item'><span>عروض الأسعار:</span> <strong>{$cleanReport['quotations']}</strong></div>
                    <div class='report-item'><span>أوامر البيع:</span> <strong>{$cleanReport['sales_orders']}</strong></div>
                    <div class='report-item'><span>فواتير المبيعات:</span> <strong>{$cleanReport['sales_invoices']}</strong></div>
                    <div class='report-item'><span>فواتير المشتريات:</span> <strong>{$cleanReport['purchase_invoices']}</strong></div>
                    <div class='report-item'><span>سندات القبض (مستحقات):</span> <strong>{$cleanReport['client_receipts']}</strong></div>
                    <div class='report-item'><span>سندات الصرف (مستحقات):</span> <strong>{$cleanReport['vendor_payments']}</strong></div>
                    <div class='report-item'><span>المصروفات:</span> <strong>{$cleanReport['expenses']}</strong></div>
                    <div class='report-item'><span>الإيرادات:</span> <strong>{$cleanReport['revenues']}</strong></div>
                    <div class='report-item'><span>المحافظ والحسابات:</span> <strong>{$cleanReport['wallets']}</strong></div>
                    <div class='report-item'><span>الموردين:</span> <strong>{$cleanReport['vendors']}</strong></div>
                    <div class='report-item'><span>الأصناف:</span> <strong>{$cleanReport['items']}</strong></div>
                    <div class='report-item'><span>سجلات الطباعة:</span> <strong>{$cleanReport['printed_labels']}</strong></div>
                    <div class='report-item'><span>سجلات العمليات:</span> <strong>{$cleanReport['activity_logs']}</strong></div>
                </div>
            </div>";
        }

        $terminalBox = '';
        if (!empty($output)) {
            $escapedOut = htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
            $terminalBox = "
            <div class='terminal-wrap'>
                <div class='terminal-bar'>سجل مخرجات الأوامر المباشرة على السيرفر</div>
                <pre class='terminal'>{$escapedOut}</pre>
            </div>";
        }

        $appUrl = url('/');
        $currentUrl = request()->url();
        $keyParam = request('key') ? '&key=' . urlencode(request('key')) : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة ترحيل السيرفر وتنظيف البيانات</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 24px 16px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { background: #fff; border-radius: 16px; padding: 24px 32px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 800; color: #005B9F; }
        .btn-home { padding: 8px 16px; background: #f1f5f9; color: #334155; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: bold; border: 1px solid #cbd5e1; }
        .alert { padding: 14px 20px; border-radius: 12px; font-size: 14px; font-weight: bold; margin-bottom: 24px; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .card { background: #fff; border-radius: 14px; padding: 20px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: space-between; }
        .card h3 { margin: 0 0 8px 0; font-size: 16px; font-weight: 800; }
        .card p { margin: 0 0 16px 0; font-size: 12px; color: #64748b; line-height: 1.5; flex-grow: 1; }
        .btn { display: inline-flex; align-items: center; justify-content: center; width: 100%; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; text-decoration: none; border: none; cursor: pointer; text-align: center; }
        .btn-primary { background: #005B9F; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-warning { background: #d97706; color: #fff; }
        .btn-success { background: #008A3B; color: #fff; font-size: 15px; padding: 14px; }
        .btn-secondary { background: #475569; color: #fff; }
        .card-all { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-color: #86efac; grid-column: 1 / -1; }
        .card-all h3 { color: #166534; font-size: 18px; }
        .clean-report { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 24px; }
        .clean-header { font-size: 14px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 14px; }
        .grid-report { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .report-item { background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 13px; }
        .terminal-wrap { background: #0f172a; border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
        .terminal-bar { background: #1e293b; color: #94a3b8; padding: 8px 16px; font-size: 11px; font-weight: bold; border-bottom: 1px solid #334155; }
        .terminal { margin: 0; padding: 16px; color: #38bdf8; font-family: monospace; font-size: 12px; line-height: 1.6; max-height: 350px; overflow-y: auto; white-space: pre-wrap; direction: ltr; text-align: left; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🚀 لوحة إدارة وترحيل السيرفر</h1>
                <p>تشغيل المايجريشن وتنظيف بيانات الـ TEST مباشرةً على قاعدة بيانات السيرفر</p>
            </div>
            <a href="{$appUrl}" class="btn-home">⬅ فتح النظام</a>
        </div>

        {$statusAlert}

        <div class="card card-all" style="margin-bottom: 20px;">
            <h3>⚡ تشغيل شامل دفعة واحدة (All-in-One)</h3>
            <p>يقوم بجميع العمليات معاً على السيرفر: <strong>1. تشغيل المايجريشن</strong> + <strong>2. حذف كافة بيانات الـ TEST</strong> + <strong>3. مسح وتحديث الكاش ورابط التخزين</strong>.</p>
            <a href="{$currentUrl}?action=all{$keyParam}" onclick="return confirm('تأكيد: هل ترغب في تشغيل المايجريشن وحذف كافة بيانات الـ TEST وتحديث الكاش دفعة واحدة على قاعدة بيانات السيرفر؟');" class="btn btn-success">
                ⚡ تشغيل المايجريشن + تنظيف الـ Test + مسح الكاش الآن
            </a>
        </div>

        <div class="cards-grid">
            <div class="card">
                <div>
                    <h3 style="color:#005B9F;">1. ترحيل قاعدة البيانات (Migrate)</h3>
                    <p>ينفذ أمر <code>migrate --force</code> على قاعدة بيانات السيرفر لإنشاء وتحديث الجداول، وتهيئة بنود المصروفات.</p>
                </div>
                <a href="{$currentUrl}?action=migrate{$keyParam}" class="btn btn-primary">🚀 تنفيذ المايجريشن فقط</a>
            </div>

            <div class="card">
                <div>
                    <h3 style="color:#d97706;">2. فحص بيانات الـ TEST (معاينة)</h3>
                    <p>يقوم بحصر كافة السجلات التجريبية (عملاء، عروض، فواتير، مستحقات، محافظ) في قاعدة بيانات السيرفر دون حذف أي شيء لمعاينتها.</p>
                </div>
                <a href="{$currentUrl}?action=check-test{$keyParam}" class="btn btn-warning">🔍 فحص السجلات التجريبية</a>
            </div>

            <div class="card">
                <div>
                    <h3 style="color:#dc2626;">3. حذف بيانات الـ TEST نهائياً</h3>
                    <p>يحذف نهائياً أي عميل أو عرض سعر أو أمر بيع أو فاتورة أو مستحق أو حساب بنكي يحتوي على كلمة Test أو تجريبي من قاعدة بيانات السيرفر.</p>
                </div>
                <a href="{$currentUrl}?action=clean-test{$keyParam}" onclick="return confirm('تأكيد نهائي: هل أنت متأكد من رغبتك في حذف كافة بيانات الـ TEST نهائياً من قاعدة بيانات السيرفر؟');" class="btn btn-danger">
                    🧹 حذف وتنظيف بيانات الـ TEST
                </a>
            </div>

            <div class="card">
                <div>
                    <h3 style="color:#475569;">4. مسح الكاش والروابط</h3>
                    <p>مسح كاش الإعدادات والقوالب على السيرفر وتشغيل <code>storage:link</code> لظهور المرفقات والصور.</p>
                </div>
                <a href="{$currentUrl}?action=optimize{$keyParam}" class="btn btn-secondary">🔄 مسح الكاش وربط التخزين</a>
            </div>
        </div>

        {$reportBox}
        {$terminalBox}
    </div>
</body>
</html>
HTML;
    }
}
