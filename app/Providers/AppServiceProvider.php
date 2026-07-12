<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\Quotation;
use App\Models\Revenue;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\ClientReceipt;
use App\Models\WalletTransfer;
use App\Observers\ActivityObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // سجل العمليات — يلقط إنشاء/تعديل/حذف على الموديلات الأساسية
        foreach ([
            Quotation::class, SalesOrder::class, SalesInvoice::class, PurchaseInvoice::class,
            Expense::class, VendorPayment::class, ClientReceipt::class, WalletTransfer::class, Revenue::class,
            Client::class, Vendor::class, Item::class,
        ] as $model) {
            $model::observe(ActivityObserver::class);
        }

        // تنبيهات: أوامر بيع عندها فاتورة شراء من غير فاتورة بيع أو العكس
        View::composer('layouts.app', function ($view) {
            $mismatches = SalesOrder::query()
                ->withMismatchedInvoices()
                ->with('client')
                ->withCount(['purchaseInvoices', 'salesInvoices'])
                ->latest()
                ->limit(20)
                ->get()
                ->map(function ($salesOrder) {
                    $hasPurchase = $salesOrder->purchase_invoices_count > 0;
                    return [
                        'sales_order'  => $salesOrder,
                        'missing'      => $hasPurchase ? 'sales_invoice' : 'purchase_invoice',
                        'action_route' => $hasPurchase
                            ? route('sales-invoices.create', ['sales_order_id' => $salesOrder->id])
                            : route('purchase-invoices.create', ['sales_order_id' => $salesOrder->id]),
                    ];
                });

            $view->with('navNotifications', $mismatches);
        });
    }
}
