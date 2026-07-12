<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SourcingController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\VendorPaymentController;
use App\Http\Controllers\ClientReceiptController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletTransferController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ReportsController;

use App\Models\Quotation;

// ===== مسارات تسجيل الدخول (بدون auth) =====
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ===== كل المسارات محمية بـ auth =====
Route::middleware('auth')->group(function () {

    // الصفحة الرئيسية — بيانات حقيقية من قاعدة البيانات
    Route::get('/', function () {

        $quotationsCount = Quotation::count();

        $quotationsByStatus = Quotation::selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        $wallets = \App\Models\Wallet::withBalanceSums()->orderBy('name')->get();

        return view('dashboard', compact('quotationsCount', 'quotationsByStatus', 'wallets'));
    });

    // العملاء
    Route::get('clients/{client}/defaults', [ClientController::class, 'defaults'])->name('clients.defaults');
    Route::get('clients/{client}/quotations', [ClientController::class, 'quotations'])->name('clients.quotations');
    Route::resource('clients', ClientController::class);

    // الموردون والأصناف
    Route::resource('vendors', VendorController::class);
    Route::resource('items', ItemController::class);

    // الإعدادات
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'store'])->name('settings.store');
    Route::delete('settings/{setting}', [SettingController::class, 'destroy'])->name('settings.destroy');
    Route::post('settings/reset-database', [SettingController::class, 'resetDatabase'])->name('settings.reset-database');

    // إدارة المستخدمين (من الإعدادات)
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // ربط الموردين بالأصناف
    Route::get('/sourcing', [SourcingController::class, 'index'])->name('sourcing.index');
    Route::get('/sourcing/search-items', [SourcingController::class, 'searchItems'])->name('sourcing.search-items');
    Route::get('/sourcing/search-vendors', [SourcingController::class, 'searchVendors'])->name('sourcing.search-vendors');
    Route::get('/sourcing/items/{item}', [SourcingController::class, 'itemDetail'])->name('sourcing.item-detail');
    Route::get('/sourcing/vendors/{vendor}', [SourcingController::class, 'vendorDetail'])->name('sourcing.vendor-detail');
    Route::post('/sourcing/attach', [SourcingController::class, 'attach'])->name('sourcing.attach');

    // قوائم الأسعار وعروض الأسعار
    Route::get('price-lists/{price_list}/data', [PriceListController::class, 'data'])->name('price-lists.data');
    Route::resource('price-lists', PriceListController::class)->except(['show']);

    Route::get('quotations/sent-log', [QuotationController::class, 'sentLog'])->name('quotations.sent-log');
    Route::get('quotations/{priceList}/price-list-items', [QuotationController::class, 'priceListItems'])
        ->name('quotations.price-list-items');
    Route::post('quotations/{quotation}/clone', [QuotationController::class, 'clone'])->name('quotations.clone');
    Route::patch('quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.update-status');
    Route::post('quotations/{quotation}/send-email', [QuotationController::class, 'sendEmail'])->name('quotations.send-email');
    Route::resource('quotations', QuotationController::class)->except(['destroy']);

    // أوامر البيع
    Route::get('sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('sales-orders/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
    Route::post('sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
    Route::get('sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::get('sales-orders/{salesOrder}/edit', [SalesOrderController::class, 'edit'])->name('sales-orders.edit');
    Route::patch('sales-orders/{salesOrder}', [SalesOrderController::class, 'update'])->name('sales-orders.update');

    // الإدارة المالية
    Route::resource('expenses', ExpenseController::class)->except(['destroy', 'show']);

    Route::get('purchase-invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase-invoices.index');
    Route::get('purchase-invoices/create', [PurchaseInvoiceController::class, 'create'])->name('purchase-invoices.create');
    Route::post('purchase-invoices', [PurchaseInvoiceController::class, 'store'])->name('purchase-invoices.store');
    Route::get('purchase-invoices/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])->name('purchase-invoices.show');

    Route::get('vendor-payments', [VendorPaymentController::class, 'index'])->name('vendor-payments.index');
    Route::get('vendor-payments/create', [VendorPaymentController::class, 'create'])->name('vendor-payments.create');
    Route::post('vendor-payments', [VendorPaymentController::class, 'store'])->name('vendor-payments.store');
    Route::get('vendor-payments/{vendorPayment}/edit', [VendorPaymentController::class, 'edit'])->name('vendor-payments.edit');
    Route::put('vendor-payments/{vendorPayment}', [VendorPaymentController::class, 'update'])->name('vendor-payments.update');

    Route::get('client-receipts', [ClientReceiptController::class, 'index'])->name('client-receipts.index');
    Route::get('client-receipts/create', [ClientReceiptController::class, 'create'])->name('client-receipts.create');
    Route::post('client-receipts', [ClientReceiptController::class, 'store'])->name('client-receipts.store');

    Route::get('payables', [PayableController::class, 'index'])->name('payables.index');
    Route::get('payables/{vendor}', [PayableController::class, 'show'])->name('payables.show');
    Route::post('payables/{vendor}/send-email', [PayableController::class, 'sendEmail'])->name('payables.send-email');

    Route::get('receivables', [ReceivableController::class, 'index'])->name('receivables.index');
    Route::get('receivables/{client}', [ReceivableController::class, 'show'])->name('receivables.show');
    Route::post('receivables/{client}/send-email', [ReceivableController::class, 'sendEmail'])->name('receivables.send-email');

    Route::get('cost-centers', [CostCenterController::class, 'index'])->name('cost-centers.index');
    Route::get('cost-centers/{quotation}', [CostCenterController::class, 'show'])->name('cost-centers.show');
    Route::patch('cost-centers/{quotation}', [CostCenterController::class, 'update'])->name('cost-centers.update');

    // فواتير البيع
    Route::get('sales-invoices', [SalesInvoiceController::class, 'index'])->name('sales-invoices.index');
    Route::get('sales-invoices/create', [SalesInvoiceController::class, 'create'])->name('sales-invoices.create');
    Route::post('sales-invoices', [SalesInvoiceController::class, 'store'])->name('sales-invoices.store');
    Route::get('sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show'])->name('sales-invoices.show');
    Route::get('sales-invoices/{salesInvoice}/print', [SalesInvoiceController::class, 'print'])->name('sales-invoices.print');
    Route::post('sales-invoices/{salesInvoice}/send-email', [SalesInvoiceController::class, 'sendEmail'])->name('sales-invoices.send-email');

    // المحافظ النقدية/البنكية
    Route::get('wallets', [WalletController::class, 'index'])->name('wallets.index');
    Route::get('wallets/create', [WalletController::class, 'create'])->name('wallets.create');
    Route::post('wallets', [WalletController::class, 'store'])->name('wallets.store');
    Route::get('wallets/{wallet}/edit', [WalletController::class, 'edit'])->name('wallets.edit');
    Route::put('wallets/{wallet}', [WalletController::class, 'update'])->name('wallets.update');
    Route::delete('wallets/{wallet}', [WalletController::class, 'destroy'])->name('wallets.destroy');
    Route::get('wallets/{wallet}', [WalletController::class, 'show'])->name('wallets.show');
    Route::get('wallets/{wallet}/print', [WalletController::class, 'print'])->name('wallets.print');

    Route::get('wallet-transfers/create', [WalletTransferController::class, 'create'])->name('wallet-transfers.create');
    Route::post('wallet-transfers', [WalletTransferController::class, 'store'])->name('wallet-transfers.store');
    
    // إيرادات مباشرة للمحافظ
    Route::post('revenues', [App\Http\Controllers\RevenueController::class, 'store'])->name('revenues.store');

    // التقارير
    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');

    // سجل العمليات
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('financial-logs', [\App\Http\Controllers\FinancialLogController::class, 'index'])->name('financial-logs.index');
    Route::post('financial-logs/{sourceType}/{id}/reverse', [\App\Http\Controllers\FinancialLogController::class, 'reverse'])->name('financial-logs.reverse');

    // تبديل اللغة
    Route::get('/lang/{locale}', function (string $locale) {
        if (in_array($locale, ['ar', 'en'])) session(['locale' => $locale]);
        return redirect()->back();
    })->name('lang.switch');

});
