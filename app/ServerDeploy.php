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
                $output .= "=== 1. تشغيل أمر المايجريشن (Artisan Migrate) ===\n";
                Artisan::call('migrate', ['--force' => true]);
                $output .= Artisan::output() . "\n";

                $output .= "=== 2. فحص وتأكيد سلامة الجداول والأعمدة الجديدة ===\n";
                $output .= self::ensureLatestSchemaAndTables();

                // 3. مسح وتحديث الكاش
                Artisan::call('optimize:clear');
                Cache::flush();
                $output .= "\n✓ تم مسح وتحديث كاش النظام بنجاح.\n";

                $status = 'success';
                $message = 'تم تنفيذ كافة ملفات المايجريشن والتأكد من سلامة الجداول بنجاح على قاعدة بيانات السيرفر!';
            } catch (\Throwable $e) {
                try {
                    $output .= "\n=== محاولة التأكد المباشر من الجداول والأعمدة (Schema Fallback) ===\n";
                    $output .= self::ensureLatestSchemaAndTables();
                    $status = 'warning';
                    $message = 'تم التأكد من سلامة الجداول والأعمدة الأساسية مع وجود تنبيه: ' . $e->getMessage();
                } catch (\Throwable $e2) {
                    $status = 'error';
                    $message = 'خطأ أثناء المايجريشن: ' . $e->getMessage();
                }
                $output .= "\n" . $e->getTraceAsString();
            }
        } elseif ($action === 'clean-test') {
            try {
                $withRiceQuote = $request->boolean('with_rice_quote', false);
                $cleanReport = self::cleanTestData(dryRun: false, deleteRiceQuote: $withRiceQuote);
                $status = 'success';
                $message = 'تم بنجاح حذف عميل test، وعرض سعر test، وأمر بيع الأرز الأخضر للبيع بالتجزئة للمواد الغذائية وكافة البيانات التجريبية!';
            } catch (\Throwable $e) {
                $status = 'error';
                $message = 'خطأ أثناء تنظيف البيانات: ' . $e->getMessage();
            }
        } elseif ($action === 'clean-rice') {
            try {
                $withQuote = $request->boolean('with_quote', false);
                $cleanReport = self::cleanRiceOrderOnly(dryRun: false, deleteQuote: $withQuote);
                $status = 'success';
                $message = 'تم بنجاح حذف أمر بيع الأرز الأخضر للبيع بالتجزئة للمواد الغذائية مع بنوده وفواتيره!';
            } catch (\Throwable $e) {
                $status = 'error';
                $message = 'خطأ أثناء حذف أمر البيع: ' . $e->getMessage();
            }
        } elseif ($action === 'check-test') {
            try {
                $withRiceQuote = $request->boolean('with_rice_quote', false);
                $cleanReport = self::cleanTestData(dryRun: true, deleteRiceQuote: $withRiceQuote);
                $status = 'info';
                $message = 'تم فحص قاعدة بيانات السيرفر وحصر السجلات المستهدفة (عميل test، عروض test، وأمر بيع الأرز الأخضر).';
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
                // 1. تشغيل المايجريشن وفحص الجداول
                $output .= "=== 1. نتيجة المايجريشن على السيرفر ===\n";
                Artisan::call('migrate', ['--force' => true]);
                $output .= Artisan::output() . "\n";
                $output .= self::ensureLatestSchemaAndTables() . "\n";

                // 2. تنظيف بيانات الـ Test + أمر بيع الأرز الأخضر
                $cleanReport = self::cleanTestData(dryRun: false);
                $output .= "=== نتيجة تنظيف السجلات المستهدفة على السيرفر ===\n";
                $output .= "• العملاء التجريبيين (test): {$cleanReport['clients']}\n";
                $output .= "• عروض الأسعار المحذوفة (test): {$cleanReport['quotations']}\n";
                $output .= "• أوامر البيع المحذوفة (الأرز الأخضر / test): {$cleanReport['sales_orders']}\n";
                $output .= "• الفواتير والمستحقات: " . ($cleanReport['sales_invoices'] + $cleanReport['purchase_invoices'] + $cleanReport['client_receipts'] + $cleanReport['vendor_payments']) . "\n";
                $output .= "• الحسابات والمحافظ التجريبية: {$cleanReport['wallets']}\n";
                $output .= "• المصروفات التجريبية: {$cleanReport['expenses']}\n\n";

                if (!empty($cleanReport['details'])) {
                    $output .= "=== قائمة السجلات المحددة التي تم حذفها ===\n";
                    foreach ($cleanReport['details'] as $line) {
                        $output .= "• {$line}\n";
                    }
                    $output .= "\n";
                }

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
                $message = 'تم تشغيل المايجريشن وحذف عميل test وعرض سعره وأمر بيع الأرز الأخضر ومسح الكاش بنجاح!';
            } catch (\Throwable $e) {
                $status = 'error';
                $message = 'خطأ: ' . $e->getMessage();
                $output .= "\n" . $e->getTraceAsString();
            }
        }

        return self::renderDashboard($status, $message, $output, $cleanReport);
    }

    /**
     * قائمة أنماط البحث عن "الأرز الأخضر للبيع بالتجزئة للمواد الغذائية" بكافة أشكال الهمزات والتاء المربوطة
     */
    protected static function getGreenRicePatterns(): array
    {
        return [
            '%الارز الاخضر للبيع بالتجزئه للمواد الغذائيه%',
            '%الأرز الأخضر للبيع بالتجزئة للمواد الغذائية%',
            '%الارز الاخضر للبيع بالتجزئة للمواد الغذائية%',
            '%الأرز الأخضر للبيع بالتجزئه للمواد الغذائيه%',
            '%الارز الاخضر%',
            '%الأرز الأخضر%',
            '%الارز%الاخضر%',
            '%الأرز%الأخضر%',
            '%أرز أخضر%',
            '%ارز اخضر%',
        ];
    }

    /**
     * دالة تنظيف البيانات التجريبية الشاملة وحذف:
     * 1. عميل test
     * 2. عروض أسعار test
     * 3. أمر بيع الأرز الأخضر للبيع بالتجزئة للمواد الغذائية مع متعلقاته
     */
    public static function cleanTestData(bool $dryRun = false, bool $deleteRiceQuote = false): array
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
            'details'           => [],
        ];

        $greenRicePatterns = self::getGreenRicePatterns();

        // 1. العملاء التجريبيين (مثل test وتجريبي)
        $clientIds = [];
        if (Schema::hasTable('clients')) {
            $clientQuery = DB::table('clients')->where(function ($q) {
                self::addSearchPattern($q, 'clients', ['company_name', 'company_name_en', 'contact_person', 'email', 'phone']);
            });

            $clientsFound = $clientQuery->get(['id', 'company_name'])->all();
            foreach ($clientsFound as $c) {
                $clientIds[] = $c->id;
                $report['details'][] = "عميل تجريبي: {$c->company_name} (ID: {$c->id})";
            }
        }

        // معرفات العملاء باسم الأرز الأخضر (لاستهداف أمر البيع وليس لحذف العميل نفسه)
        $greenRiceClientIds = [];
        if (Schema::hasTable('clients')) {
            $greenRiceClientIds = DB::table('clients')->where(function ($q) use ($greenRicePatterns) {
                foreach ($greenRicePatterns as $p) {
                    $q->orWhere('company_name', 'like', $p)
                      ->orWhere('company_name_en', 'like', $p);
                }
            })->pluck('id')->all();
        }

        // 2. عروض الأسعار ومراكز التكلفة (عروض test)
        $quoteIds = [];
        if (Schema::hasTable('quotations')) {
            $quotesQuery = DB::table('quotations')->where(function ($q) use ($clientIds) {
                if (!empty($clientIds) && Schema::hasColumn('quotations', 'client_id')) {
                    $q->whereIn('client_id', $clientIds);
                }
                self::addSearchPattern($q, 'quotations', ['quote_number', 'cost_center_name', 'cost_center', 'terms']);
            });

            $quotesFound = $quotesQuery->get(['id', 'quote_number', 'cost_center_name'])->all();
            foreach ($quotesFound as $qt) {
                $quoteIds[] = $qt->id;
                $name = !empty($qt->cost_center_name) ? " - {$qt->cost_center_name}" : '';
                $report['details'][] = "عرض سعر: {$qt->quote_number}{$name} (ID: {$qt->id})";
            }
        }

        // عروض أسعار خاصة بالأرز الأخضر (إن وُجدت)
        $greenRiceQuoteIds = [];
        if (Schema::hasTable('quotations')) {
            $greenRiceQuoteIds = DB::table('quotations')->where(function ($q) use ($greenRicePatterns, $greenRiceClientIds) {
                if (!empty($greenRiceClientIds) && Schema::hasColumn('quotations', 'client_id')) {
                    $q->whereIn('client_id', $greenRiceClientIds);
                }
                foreach ($greenRicePatterns as $p) {
                    if (Schema::hasColumn('quotations', 'cost_center_name')) $q->orWhere('cost_center_name', 'like', $p);
                    if (Schema::hasColumn('quotations', 'cost_center')) $q->orWhere('cost_center', 'like', $p);
                    if (Schema::hasColumn('quotations', 'terms')) $q->orWhere('terms', 'like', $p);
                }
            })->pluck('id')->all();
        }

        // بنود أوامر البيع التي قد تحتوي على اسم الأرز الأخضر
        $greenRiceSoItemIds = [];
        if (Schema::hasTable('sales_order_items')) {
            $greenRiceSoItemIds = DB::table('sales_order_items')->where(function ($q) use ($greenRicePatterns) {
                foreach ($greenRicePatterns as $p) {
                    $q->orWhere('description', 'like', $p);
                }
            })->pluck('sales_order_id')->filter()->unique()->all();
        }

        // 3. أوامر البيع (المستهدفة: أمر بيع الأرز الأخضر + أوامر الـ test)
        $soIds = [];
        $soRecords = [];
        if (Schema::hasTable('sales_orders')) {
            $soQuery = DB::table('sales_orders')->where(function ($q) use (
                $quoteIds, $clientIds, $greenRiceClientIds, $greenRiceQuoteIds, $greenRiceSoItemIds, $greenRicePatterns
            ) {
                // أوامر بيع test
                if (!empty($quoteIds) && Schema::hasColumn('sales_orders', 'quotation_id')) {
                    $q->whereIn('quotation_id', $quoteIds);
                }
                if (!empty($clientIds) && Schema::hasColumn('sales_orders', 'client_id')) {
                    $q->orWhereIn('client_id', $clientIds);
                }
                self::addSearchPattern($q, 'sales_orders', ['so_number', 'terms', 'cost_center']);

                // أمر بيع الأرز الأخضر للبيع بالتجزئة للمواد الغذائية
                if (!empty($greenRiceClientIds) && Schema::hasColumn('sales_orders', 'client_id')) {
                    $q->orWhereIn('client_id', $greenRiceClientIds);
                }
                if (!empty($greenRiceQuoteIds) && Schema::hasColumn('sales_orders', 'quotation_id')) {
                    $q->orWhereIn('quotation_id', $greenRiceQuoteIds);
                }
                if (!empty($greenRiceSoItemIds)) {
                    $q->orWhereIn('id', $greenRiceSoItemIds);
                }
                foreach ($greenRicePatterns as $p) {
                    if (Schema::hasColumn('sales_orders', 'cost_center')) $q->orWhere('cost_center', 'like', $p);
                    if (Schema::hasColumn('sales_orders', 'terms')) $q->orWhere('terms', 'like', $p);
                    if (Schema::hasColumn('sales_orders', 'so_number')) $q->orWhere('so_number', 'like', $p);
                }
            });

            $soRecords = $soQuery->get(['id', 'so_number', 'client_id', 'quotation_id'])->all();
            foreach ($soRecords as $so) {
                $soIds[] = $so->id;
                $report['details'][] = "أمر بيع: {$so->so_number} (ID: {$so->id})";
            }
        }

        // عروض الأسعار المرتبطة بأوامر البيع
        $linkedQuoteIds = array_filter(array_map(fn($o) => $o->quotation_id, $soRecords));
        $quotesToReset = array_diff($linkedQuoteIds, $quoteIds);

        // إذا طُلب صراحة حذف عرض سعر الأرز الأخضر أيضاً
        if ($deleteRiceQuote && !empty($greenRiceQuoteIds)) {
            $quoteIds = array_unique(array_merge($quoteIds, $greenRiceQuoteIds, $linkedQuoteIds));
            $quotesToReset = [];
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
            $piIds = DB::table('purchase_invoices')->where(function ($q) use ($soIds, $quoteIds) {
                if (!empty($soIds) && Schema::hasColumn('purchase_invoices', 'sales_order_id')) {
                    $q->whereIn('sales_order_id', $soIds);
                }
                if (!empty($quoteIds) && Schema::hasColumn('purchase_invoices', 'quotation_id')) {
                    $q->orWhereIn('quotation_id', $quoteIds);
                }
                self::addSearchPattern($q, 'purchase_invoices', ['invoice_number', 'vendor_invoice_number', 'notes']);
            })->pluck('id')->all();
        }

        // 6. الحسابات البنكية والمحافظ التجريبية
        $walletIds = [];
        if (Schema::hasTable('wallets')) {
            $walletIds = DB::table('wallets')->where(function ($q) {
                self::addSearchPattern($q, 'wallets', ['name', 'notes']);
            })->pluck('id')->all();
        }

        // 7. سندات القبض (مستحقات عملاء)
        $receiptIds = [];
        if (Schema::hasTable('client_receipts')) {
            $receiptIds = DB::table('client_receipts')->where(function ($q) use ($clientIds, $siIds, $soIds, $quoteIds, $walletIds) {
                if (!empty($clientIds) && Schema::hasColumn('client_receipts', 'client_id')) {
                    $q->whereIn('client_id', $clientIds);
                }
                if (!empty($siIds) && Schema::hasColumn('client_receipts', 'sales_invoice_id')) {
                    $q->orWhereIn('sales_invoice_id', $siIds);
                }
                if (!empty($soIds) && Schema::hasColumn('client_receipts', 'sales_order_id')) {
                    $q->orWhereIn('sales_order_id', $soIds);
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

        // 9. المصروفات التجريبية
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

        // 10. الإيرادات التجريبية
        $revenueIds = [];
        if (Schema::hasTable('revenues')) {
            $revenueIds = DB::table('revenues')->where(function ($q) use ($walletIds) {
                if (!empty($walletIds) && Schema::hasColumn('revenues', 'wallet_id')) {
                    $q->whereIn('wallet_id', $walletIds);
                }
                self::addSearchPattern($q, 'revenues', ['revenue_number', 'description', 'notes']);
            })->pluck('id')->all();
        }

        // 11. الموردين التجريبيين
        $vendorIds = [];
        if (Schema::hasTable('vendors')) {
            $vendorIds = DB::table('vendors')->where(function ($q) {
                self::addSearchPattern($q, 'vendors', ['vendor_code', 'name_ar', 'name_en', 'legal_name', 'email']);
            })->pluck('id')->all();
        }

        // 12. الأصناف التجريبية
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
            $walletIds, $vendorIds, $itemIds, $labelIds, $quotesToReset, &$report
        ) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            try {
                // حذف البنود التابعة أولاً لمنع أخطاء القيود
                if (!empty($siIds) && Schema::hasTable('sales_invoice_items')) {
                    DB::table('sales_invoice_items')->whereIn('sales_invoice_id', $siIds)->delete();
                }
                if (!empty($soIds) && Schema::hasTable('sales_order_items')) {
                    DB::table('sales_order_items')->whereIn('sales_order_id', $soIds)->delete();
                }
                if (!empty($quoteIds) && Schema::hasTable('quotation_items')) {
                    DB::table('quotation_items')->whereIn('quotation_id', $quoteIds)->delete();
                }
                if (!empty($quoteIds) && Schema::hasTable('quotation_sends')) {
                    DB::table('quotation_sends')->whereIn('quotation_id', $quoteIds)->delete();
                }
                if (!empty($piIds) && Schema::hasTable('purchase_invoice_items')) {
                    DB::table('purchase_invoice_items')->whereIn('purchase_invoice_id', $piIds)->delete();
                }
                if (!empty($walletIds) && Schema::hasTable('wallet_transfers')) {
                    DB::table('wallet_transfers')->whereIn('from_wallet_id', $walletIds)->orWhereIn('to_wallet_id', $walletIds)->delete();
                }
                if (!empty($itemIds) && Schema::hasTable('item_images')) {
                    DB::table('item_images')->whereIn('item_id', $itemIds)->delete();
                }
                if (!empty($itemIds) && Schema::hasTable('item_vendor')) {
                    DB::table('item_vendor')->whereIn('item_id', $itemIds)->delete();
                }
                if (!empty($vendorIds) && Schema::hasTable('vendor_addresses')) {
                    DB::table('vendor_addresses')->whereIn('vendor_id', $vendorIds)->delete();
                }

                // حذف السجلات المالية والمستندات الرئيسية
                if (!empty($receiptIds) && Schema::hasTable('client_receipts')) {
                    DB::table('client_receipts')->whereIn('id', $receiptIds)->delete();
                }
                if (!empty($paymentIds) && Schema::hasTable('vendor_payments')) {
                    DB::table('vendor_payments')->whereIn('id', $paymentIds)->delete();
                }
                if (!empty($expenseIds) && Schema::hasTable('expenses')) {
                    DB::table('expenses')->whereIn('id', $expenseIds)->delete();
                }
                if (!empty($revenueIds) && Schema::hasTable('revenues')) {
                    DB::table('revenues')->whereIn('id', $revenueIds)->delete();
                }
                if (!empty($siIds) && Schema::hasTable('sales_invoices')) {
                    DB::table('sales_invoices')->whereIn('id', $siIds)->delete();
                }
                if (!empty($piIds) && Schema::hasTable('purchase_invoices')) {
                    DB::table('purchase_invoices')->whereIn('id', $piIds)->delete();
                }
                if (!empty($soIds) && Schema::hasTable('sales_orders')) {
                    DB::table('sales_orders')->whereIn('id', $soIds)->delete();
                }
                if (!empty($quoteIds) && Schema::hasTable('quotations')) {
                    DB::table('quotations')->whereIn('id', $quoteIds)->delete();
                }
                if (!empty($clientIds) && Schema::hasTable('clients')) {
                    DB::table('clients')->whereIn('id', $clientIds)->delete();
                }
                if (!empty($walletIds) && Schema::hasTable('wallets')) {
                    DB::table('wallets')->whereIn('id', $walletIds)->delete();
                }
                if (!empty($vendorIds) && Schema::hasTable('vendors')) {
                    DB::table('vendors')->whereIn('id', $vendorIds)->delete();
                }
                if (!empty($itemIds) && Schema::hasTable('items')) {
                    DB::table('items')->whereIn('id', $itemIds)->delete();
                }
                if (!empty($labelIds) && Schema::hasTable('printed_labels')) {
                    DB::table('printed_labels')->whereIn('id', $labelIds)->delete();
                }

                // تحرير أي عرض سعر غير محذوف كانت حالته converted إلى approved حتى لا يظل معلقاً
                if (!empty($quotesToReset) && Schema::hasTable('quotations')) {
                    DB::table('quotations')
                        ->whereIn('id', $quotesToReset)
                        ->where('status', 'converted')
                        ->update(['status' => 'approved']);
                }

                // سجلات العمليات التجريبية
                if (Schema::hasTable('activity_logs')) {
                    $report['activity_logs'] = DB::table('activity_logs')
                        ->where('description', 'like', '%test%')
                        ->orWhere('description', 'like', '%تجريب%')
                        ->orWhere('description', 'like', '%الارز الاخضر%')
                        ->orWhere('description', 'like', '%الأرز الأخضر%')
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
     * دالة مخصصة لحذف أمر بيع الأرز الأخضر للبيع بالتجزئة للمواد الغذائية فقط
     */
    public static function cleanRiceOrderOnly(bool $dryRun = false, bool $deleteQuote = false): array
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
            'details'           => [],
        ];

        $greenRicePatterns = self::getGreenRicePatterns();

        // معرفات العملاء باسم الأرز الأخضر
        $clientIds = [];
        if (Schema::hasTable('clients')) {
            $clientIds = DB::table('clients')->where(function ($q) use ($greenRicePatterns) {
                foreach ($greenRicePatterns as $p) {
                    $q->orWhere('company_name', 'like', $p)
                      ->orWhere('company_name_en', 'like', $p);
                }
            })->pluck('id')->all();
        }

        // معرفات عروض الأسعار باسم الأرز الأخضر
        $quoteIds = [];
        if (Schema::hasTable('quotations')) {
            $quoteIds = DB::table('quotations')->where(function ($q) use ($greenRicePatterns, $clientIds) {
                if (!empty($clientIds) && Schema::hasColumn('quotations', 'client_id')) {
                    $q->whereIn('client_id', $clientIds);
                }
                foreach ($greenRicePatterns as $p) {
                    if (Schema::hasColumn('quotations', 'cost_center_name')) $q->orWhere('cost_center_name', 'like', $p);
                    if (Schema::hasColumn('quotations', 'cost_center')) $q->orWhere('cost_center', 'like', $p);
                    if (Schema::hasColumn('quotations', 'terms')) $q->orWhere('terms', 'like', $p);
                }
            })->pluck('id')->all();
        }

        // بنود أوامر البيع باسم الأرز الأخضر
        $soIdsFromItems = [];
        if (Schema::hasTable('sales_order_items')) {
            $soIdsFromItems = DB::table('sales_order_items')->where(function ($q) use ($greenRicePatterns) {
                foreach ($greenRicePatterns as $p) {
                    $q->orWhere('description', 'like', $p);
                }
            })->pluck('sales_order_id')->filter()->unique()->all();
        }

        // أوامر البيع المستهدفة
        $soRecords = [];
        $soIds = [];
        if (Schema::hasTable('sales_orders')) {
            $soQuery = DB::table('sales_orders')->where(function ($q) use ($clientIds, $quoteIds, $soIdsFromItems, $greenRicePatterns) {
                if (!empty($clientIds) && Schema::hasColumn('sales_orders', 'client_id')) {
                    $q->whereIn('client_id', $clientIds);
                }
                if (!empty($quoteIds) && Schema::hasColumn('sales_orders', 'quotation_id')) {
                    $q->orWhereIn('quotation_id', $quoteIds);
                }
                if (!empty($soIdsFromItems)) {
                    $q->orWhereIn('id', $soIdsFromItems);
                }
                foreach ($greenRicePatterns as $p) {
                    if (Schema::hasColumn('sales_orders', 'cost_center')) $q->orWhere('cost_center', 'like', $p);
                    if (Schema::hasColumn('sales_orders', 'terms')) $q->orWhere('terms', 'like', $p);
                    if (Schema::hasColumn('sales_orders', 'so_number')) $q->orWhere('so_number', 'like', $p);
                }
            });

            $soRecords = $soQuery->get(['id', 'so_number', 'quotation_id', 'client_id'])->all();
            $soIds = array_map(fn($o) => $o->id, $soRecords);
        }

        if (empty($soIds)) {
            $report['details'][] = 'لم يتم العثور على أي أمر بيع مسجل باسم "الأرز الأخضر للبيع بالتجزئة للمواد الغذائية".';
            return $report;
        }

        foreach ($soRecords as $so) {
            $report['details'][] = "أمر بيع الأرز الأخضر: {$so->so_number} (ID: {$so->id})";
        }

        $linkedQuoteIds = array_filter(array_map(fn($o) => $o->quotation_id, $soRecords));
        $targetQuoteIds = $deleteQuote ? array_unique(array_merge($quoteIds, $linkedQuoteIds)) : [];

        // فواتير المبيعات
        $siIds = [];
        if (Schema::hasTable('sales_invoices')) {
            $siIds = DB::table('sales_invoices')->whereIn('sales_order_id', $soIds)->pluck('id')->all();
        }

        // فواتير المشتريات
        $piIds = [];
        if (Schema::hasTable('purchase_invoices')) {
            $piQuery = DB::table('purchase_invoices');
            if (Schema::hasColumn('purchase_invoices', 'sales_order_id')) {
                $piQuery->whereIn('sales_order_id', $soIds);
            }
            if (!empty($targetQuoteIds) && Schema::hasColumn('purchase_invoices', 'quotation_id')) {
                $piQuery->orWhereIn('quotation_id', $targetQuoteIds);
            }
            $piIds = $piQuery->pluck('id')->all();
        }

        // سندات القبض
        $receiptIds = [];
        if (Schema::hasTable('client_receipts')) {
            $rcQuery = DB::table('client_receipts');
            if (Schema::hasColumn('client_receipts', 'sales_order_id')) {
                $rcQuery->whereIn('sales_order_id', $soIds);
            }
            if (!empty($siIds) && Schema::hasColumn('client_receipts', 'sales_invoice_id')) {
                $rcQuery->orWhereIn('sales_invoice_id', $siIds);
            }
            $receiptIds = $rcQuery->pluck('id')->all();
        }

        // سندات الصرف
        $paymentIds = [];
        if (!empty($piIds) && Schema::hasTable('vendor_payments')) {
            $paymentIds = DB::table('vendor_payments')->whereIn('purchase_invoice_id', $piIds)->pluck('id')->all();
        }

        $report['sales_orders']      = count($soIds);
        $report['sales_invoices']    = count($siIds);
        $report['purchase_invoices'] = count($piIds);
        $report['client_receipts']   = count($receiptIds);
        $report['vendor_payments']   = count($paymentIds);
        $report['quotations']        = count($targetQuoteIds);

        if ($dryRun) {
            return $report;
        }

        DB::transaction(function () use (
            $soIds, $siIds, $piIds, $receiptIds, $paymentIds, $targetQuoteIds, $linkedQuoteIds, $deleteQuote
        ) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            try {
                if (!empty($siIds) && Schema::hasTable('sales_invoice_items')) {
                    DB::table('sales_invoice_items')->whereIn('sales_invoice_id', $siIds)->delete();
                }
                if (!empty($soIds) && Schema::hasTable('sales_order_items')) {
                    DB::table('sales_order_items')->whereIn('sales_order_id', $soIds)->delete();
                }
                if (!empty($piIds) && Schema::hasTable('purchase_invoice_items')) {
                    DB::table('purchase_invoice_items')->whereIn('purchase_invoice_id', $piIds)->delete();
                }
                if (!empty($receiptIds) && Schema::hasTable('client_receipts')) {
                    DB::table('client_receipts')->whereIn('id', $receiptIds)->delete();
                }
                if (!empty($paymentIds) && Schema::hasTable('vendor_payments')) {
                    DB::table('vendor_payments')->whereIn('id', $paymentIds)->delete();
                }
                if (!empty($siIds) && Schema::hasTable('sales_invoices')) {
                    DB::table('sales_invoices')->whereIn('id', $siIds)->delete();
                }
                if (!empty($piIds) && Schema::hasTable('purchase_invoices')) {
                    DB::table('purchase_invoices')->whereIn('id', $piIds)->delete();
                }
                if (!empty($soIds) && Schema::hasTable('sales_orders')) {
                    DB::table('sales_orders')->whereIn('id', $soIds)->delete();
                }

                if ($deleteQuote && !empty($targetQuoteIds) && Schema::hasTable('quotations')) {
                    if (Schema::hasTable('quotation_items')) {
                        DB::table('quotation_items')->whereIn('quotation_id', $targetQuoteIds)->delete();
                    }
                    if (Schema::hasTable('quotation_sends')) {
                        DB::table('quotation_sends')->whereIn('quotation_id', $targetQuoteIds)->delete();
                    }
                    DB::table('quotations')->whereIn('id', $targetQuoteIds)->delete();
                } elseif (!$deleteQuote && !empty($linkedQuoteIds) && Schema::hasTable('quotations')) {
                    // تحرير حالة عرض السعر إلى معتمد حتى لا يظل مقفولاً على converted
                    DB::table('quotations')->whereIn('id', $linkedQuoteIds)->where('status', 'converted')->update(['status' => 'approved']);
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

    /**
     * فحص وتأكيد سلامة الجداول والأعمدة الحديثة مباشرةً (Direct Schema Verification)
     * لضمان عمل كافة التحديثات حتى لو كانت صلاحيات Artisan مقيدة على السيرفر المشترك
     */
    public static function ensureLatestSchemaAndTables(): string
    {
        $log = [];

        // 1. التأكد من إنشاء جدول ملصقات الطباعة printed_labels
        try {
            if (!Schema::hasTable('printed_labels')) {
                Schema::create('printed_labels', function ($table) {
                    $table->id();
                    $table->string('brand_name')->nullable();
                    $table->string('item_name')->nullable();
                    $table->string('manufacturer')->nullable();
                    $table->string('manufacturer_address')->nullable();
                    $table->string('exporter')->nullable();
                    $table->string('exporter_address')->nullable();
                    $table->string('importer')->nullable();
                    $table->string('importer_address')->nullable();
                    $table->string('production_date')->nullable();
                    $table->string('expiry_date')->nullable();
                    $table->string('batch_code')->nullable();
                    $table->string('website')->nullable();
                    $table->integer('copies')->default(1);
                    $table->timestamps();
                });
                $log[] = "✓ تم إنشاء جدول ملصقات الطباعة (printed_labels) بنجاح.";
            } else {
                $log[] = "• جدول ملصقات الطباعة (printed_labels) موجود ومؤكد.";
            }
        } catch (\Throwable $e) {
            $log[] = "⚠ تنبيه فحص/إنشاء جدول printed_labels: " . $e->getMessage();
        }

        // 2. التأكد من إضافة أعمدة شجرة ودليل الحسابات إلى جدول settings
        try {
            if (Schema::hasTable('settings')) {
                $addedCols = [];
                Schema::table('settings', function ($table) use (&$addedCols) {
                    if (!Schema::hasColumn('settings', 'code')) {
                        $table->string('code', 50)->nullable()->after('key_value');
                        $addedCols[] = 'code';
                    }
                    if (!Schema::hasColumn('settings', 'name_en')) {
                        $table->string('name_en', 255)->nullable()->after('display_name');
                        $addedCols[] = 'name_en';
                    }
                    if (!Schema::hasColumn('settings', 'classification')) {
                        $table->string('classification', 255)->nullable()->after('parent_key');
                        $addedCols[] = 'classification';
                    }
                    if (!Schema::hasColumn('settings', 'nature')) {
                        $table->string('nature', 50)->default('Debit')->after('classification');
                        $addedCols[] = 'nature';
                    }
                    if (!Schema::hasColumn('settings', 'allow_posting')) {
                        $table->boolean('allow_posting')->default(true)->after('nature');
                        $addedCols[] = 'allow_posting';
                    }
                });

                if (!empty($addedCols)) {
                    $log[] = "✓ تم إضافة أعمدة شجرة الحسابات لجدول settings: " . implode(', ', $addedCols);
                } else {
                    $log[] = "• أعمدة شجرة الحسابات في جدول settings موجودة ومكتملة.";
                }
            }
        } catch (\Throwable $e) {
            $log[] = "⚠ تنبيه فحص/إضافة أعمدة settings: " . $e->getMessage();
        }

        // 3. تهيئة بنود المصروفات ودليل الحسابات
        try {
            if (class_exists(Setting::class) && method_exists(Setting::class, 'ensureExpenseCategoriesSeeded')) {
                Setting::ensureExpenseCategoriesSeeded();
                $log[] = "✓ تم تهيئة وتحديث دليل الحسابات وبنود المصروفات في جدول settings بنجاح.";
            }
        } catch (\Throwable $e) {
            $log[] = "⚠ تنبيه أثناء تهيئة بنود المصروفات: " . $e->getMessage();
        }

        // 4. تهيئة الخدمات الإضافية الخاصة
        try {
            if (class_exists(Setting::class) && method_exists(Setting::class, 'ensureOtherServicesSeeded')) {
                Setting::ensureOtherServicesSeeded();
                $log[] = "✓ تم تهيئة خدمات النظام الخاصة (شهادات/حراسة/تغليف).";
            }
        } catch (\Throwable $e) {
            $log[] = "⚠ تنبيه أثناء تهيئة الخدمات الخاصة: " . $e->getMessage();
        }

        return implode("\n", $log) . "\n";
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
            $totalDeleted = array_sum(array_slice($cleanReport, 0, 14));
            
            $detailsHtml = '';
            if (!empty($cleanReport['details'])) {
                $itemsHtml = '';
                foreach ($cleanReport['details'] as $detail) {
                    $itemsHtml .= "<li style='margin-bottom: 4px;'>{$detail}</li>";
                }
                $detailsHtml = "
                <div style='margin-top: 14px; padding-top: 12px; border-top: 1px dashed #cbd5e1;'>
                    <strong style='font-size: 13px; color: #1e293b; display: block; margin-bottom: 8px;'>📋 تفاصيل السجلات المستهدفة:</strong>
                    <ul style='margin: 0; padding-right: 20px; font-size: 12px; color: #475569; line-height: 1.6;'>
                        {$itemsHtml}
                    </ul>
                </div>";
            }

            $reportBox = "
            <div class='clean-report'>
                <div class='clean-header'>
                    <strong>نتائج فحص / تنظيف البيانات المحددة (إجمالي السجلات المستهدفة: {$totalDeleted})</strong>
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
                {$detailsHtml}
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
        .container { max-width: 960px; margin: 0 auto; }
        .header { background: #fff; border-radius: 16px; padding: 24px 32px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 800; color: #005B9F; }
        .btn-home { padding: 8px 16px; background: #f1f5f9; color: #334155; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: bold; border: 1px solid #cbd5e1; }
        .alert { padding: 14px 20px; border-radius: 12px; font-size: 14px; font-weight: bold; margin-bottom: 24px; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .card { background: #fff; border-radius: 14px; padding: 20px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; justify-content: space-between; }
        .card h3 { margin: 0 0 8px 0; font-size: 16px; font-weight: 800; }
        .card p { margin: 0 0 16px 0; font-size: 12px; color: #64748b; line-height: 1.5; flex-grow: 1; }
        .btn { display: inline-flex; align-items: center; justify-content: center; width: 100%; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: bold; text-decoration: none; border: none; cursor: pointer; text-align: center; }
        .btn-primary { background: #005B9F; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-warning { background: #d97706; color: #fff; }
        .btn-success { background: #008A3B; color: #fff; font-size: 15px; padding: 14px; }
        .btn-secondary { background: #475569; color: #fff; }
        .btn-rice { background: #059669; color: #fff; }
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
                <p>تشغيل المايجريشن وتنظيف بيانات الـ TEST وأمر بيع الأرز الأخضر مباشرةً على السيرفر</p>
            </div>
            <a href="{$appUrl}" class="btn-home">⬅ فتح النظام</a>
        </div>

        {$statusAlert}

        <div class="card card-all" style="margin-bottom: 20px;">
            <h3>⚡ تشغيل شامل دفعة واحدة (All-in-One)</h3>
            <p>يقوم بجميع العمليات معاً على السيرفر: <strong>1. تشغيل المايجريشن</strong> + <strong>2. حذف عميل test وعرض سعره وأمر بيع الأرز الأخضر وكافة بيانات الـ TEST</strong> + <strong>3. مسح وتحديث الكاش ورابط التخزين</strong>.</p>
            <a href="{$currentUrl}?action=all{$keyParam}" onclick="return confirm('تأكيد: هل ترغب في تشغيل المايجريشن وحذف عميل test وعرض سعره وأمر بيع الأرز الأخضر وتحديث الكاش دفعة واحدة على قاعدة بيانات السيرفر؟');" class="btn btn-success">
                ⚡ تشغيل المايجريشن + تنظيف السجلات المستهدفة + مسح الكاش الآن
            </a>
        </div>

        <div class="cards-grid">
            <div class="card">
                <div>
                    <h3 style="color:#005B9F;">1. ترحيل وتحديث قاعدة البيانات (Migrate)</h3>
                    <p>ينفذ أمر <code>migrate --force</code> على قاعدة بيانات السيرفر لإنشاء وتحديث الجداول (بما في ذلك جدول ملصقات الطباعة <code>printed_labels</code>، وأعمدة شجرة الحسابات في <code>settings</code>)، وتهيئة بنود المصروفات ومسح الكاش.</p>
                </div>
                <a href="{$currentUrl}?action=migrate{$keyParam}" class="btn btn-primary">🚀 تنفيذ المايجريشن وتأكيد الجداول</a>
            </div>

            <div class="card">
                <div>
                    <h3 style="color:#d97706;">2. فحص السجلات المستهدفة (معاينة)</h3>
                    <p>فحص وحصر السجلات التجريبية الحالية في قاعدة بيانات السيرفر (عميل test، عروض test، وأمر بيع الأرز الأخضر للبيع بالتجزئة) دون حذف أي شيء.</p>
                </div>
                <a href="{$currentUrl}?action=check-test{$keyParam}" class="btn btn-warning">🔍 فحص السجلات المستهدفة</a>
            </div>

            <div class="card">
                <div>
                    <h3 style="color:#dc2626;">3. حذف عميل test وأمر بيع الأرز الأخضر</h3>
                    <p>يحذف نهائياً <strong>العميل test</strong>، و<strong>عروض أسعار test</strong>، و<strong>أمر بيع الأرز الأخضر للبيع بالتجزئة للمواد الغذائية</strong> وبنوده وفواتيره، ويعيد حالة عرض السعر إلى معتمد.</p>
                </div>
                <a href="{$currentUrl}?action=clean-test{$keyParam}" onclick="return confirm('تأكيد نهائي: هل أنت متأكد من رغبتك في حذف عميل test وعروض أسعاره وأمر بيع الأرز الأخضر نهائياً من قاعدة بيانات السيرفر؟');" class="btn btn-danger">
                    🧹 حذف بيانات TEST وأمر بيع الأرز الأخضر
                </a>
            </div>

            <div class="card">
                <div>
                    <h3 style="color:#059669;">4. حذف أمر بيع الأرز الأخضر فقط</h3>
                    <p>يحذف <strong>أمر بيع الأرز الأخضر للبيع بالتجزئة للمواد الغذائية</strong> وبنوده وفواتيره فقط، مع الإبقاء على بيانات العميل نفسه وإعادة حالة عرض السعر إلى معتمد.</p>
                </div>
                <a href="{$currentUrl}?action=clean-rice{$keyParam}" onclick="return confirm('تأكيد: هل ترغب في حذف أمر بيع الأرز الأخضر للبيع بالتجزئة للمواد الغذائية وبنوده فقط؟');" class="btn btn-rice">
                    🌾 حذف أمر بيع الأرز الأخضر فقط
                </a>
            </div>

            <div class="card">
                <div>
                    <h3 style="color:#475569;">5. مسح الكاش والروابط</h3>
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
