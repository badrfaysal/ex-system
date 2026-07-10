<?php

namespace App\Providers;

use App\Models\Quotation;
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
        // تنبيهات: عروض أسعار عندها فاتورة شراء من غير أمر بيع أو العكس
        View::composer('layouts.app', function ($view) {
            $mismatches = Quotation::query()
                ->withMismatchedDocs()
                ->with('client')
                ->withCount(['purchaseInvoices', 'salesOrders'])
                ->latest()
                ->limit(20)
                ->get()
                ->map(function ($quotation) {
                    $hasPurchase = $quotation->purchase_invoices_count > 0;
                    return [
                        'quotation'    => $quotation,
                        'missing'      => $hasPurchase ? 'sales_order' : 'purchase_invoice',
                        'action_route' => $hasPurchase
                            ? route('sales-orders.create', ['quotation_id' => $quotation->id])
                            : route('purchase-invoices.create', ['quotation_id' => $quotation->id]),
                    ];
                });

            $view->with('navNotifications', $mismatches);
        });
    }
}
