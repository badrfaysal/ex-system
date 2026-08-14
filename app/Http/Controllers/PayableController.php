<?php

namespace App\Http\Controllers;

use App\Mail\VendorStatementMail;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PayableController extends Controller
{
    /**
     * كل الموردين اللي ليهم رصيد (مستحق أو متابع) — إجمالي الفواتير مقابل إجمالي المدفوع
     */
    public function index(Request $request)
    {
        $query = Vendor::query()
            ->whereHas('purchaseInvoices')
            ->withSum('purchaseInvoices as invoiced_total', 'grand_total')
            ->withSum('payments as paid_total', \Illuminate\Support\Facades\DB::raw('COALESCE(foreign_amount, amount)'));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('vendor_code', 'like', "%{$search}%");
            });
        }

        $tab = $request->input('tab', 'active');
        if ($tab === 'paid') {
            $query->havingRaw('(COALESCE(invoiced_total, 0) - COALESCE(paid_total, 0)) <= 0');
        } else {
            $query->havingRaw('(COALESCE(invoiced_total, 0) - COALESCE(paid_total, 0)) > 0');
        }

        $sort = $request->input('sort', 'balance_desc');
        match ($sort) {
            'balance_asc' => $query->orderByRaw('(COALESCE(invoiced_total, 0) - COALESCE(paid_total, 0)) ASC'),
            'newest'      => $query->orderByDesc('created_at'),
            'oldest'      => $query->orderBy('created_at'),
            default       => $query->orderByRaw('(COALESCE(invoiced_total, 0) - COALESCE(paid_total, 0)) DESC'), // balance_desc
        };

        // Summary
        $summaryData = \Illuminate\Support\Facades\DB::query()
            ->fromSub(clone $query, 'sub')
            ->selectRaw('SUM(invoiced_total) as sum_invoiced, SUM(paid_total) as sum_paid, SUM(COALESCE(invoiced_total, 0) - COALESCE(paid_total, 0)) as sum_balance')
            ->first();

        $summary = [
            'invoiced' => $summaryData->sum_invoiced ?? 0,
            'paid'     => $summaryData->sum_paid ?? 0,
            'balance'  => $summaryData->sum_balance ?? 0,
        ];

        $vendors = $query->paginate(50)->withQueryString();

        $vendors->load(['purchaseInvoices', 'payments']);

        // Calculate grouped currency balance for each model in the paginated collection
        $vendors->getCollection()->transform(function ($vendor) {
            $currencyBalances = [];
            foreach ($vendor->purchaseInvoices as $inv) {
                $c = $inv->currency ?? 'EGP';
                $currencyBalances[$c] = ($currencyBalances[$c] ?? 0) + $inv->grand_total;
            }
            foreach ($vendor->payments as $pay) {
                $c = $pay->foreign_currency ?? $pay->currency ?? 'EGP';
                $currencyBalances[$c] = ($currencyBalances[$c] ?? 0) - ($pay->foreign_amount ?? $pay->amount);
            }
            $vendor->currencyBalances = $currencyBalances;

            // Keep the raw single balance for legacy/sorting fallback if needed elsewhere
            $vendor->balance = (float) $vendor->invoiced_total - (float) $vendor->paid_total;
            return $vendor;
        });

        return view('payables.index', compact('vendors', 'summary', 'sort'));
    }

    /**
     * كشف حساب مورد — فواتير الشراء (دين) وسندات الدفع (سداد) بالترتيب الزمني والرصيد الجاري
     */
    public function show(Vendor $vendor)
    {
        [$timeline, $balance, $totalInvoiced, $totalPaid] = $this->buildTimeline($vendor);

        // فواتير الشراء اللي لسه عليها رصيد — تُستخدم في نموذج تسجيل سند الدفع
        $openInvoices = $vendor->purchaseInvoices
            ->map(fn ($pi) => ['id' => $pi->id, 'invoice_number' => $pi->invoice_number, 'balance_due' => $pi->balance_due, 'currency' => $pi->currency])
            ->filter(fn ($pi) => $pi['balance_due'] > 0)
            ->values();

        $wallets = \App\Models\Wallet::orderBy('name')->get(['id', 'name', 'currency']);

        return view('payables.show', [
            'vendor'       => $vendor,
            'timeline'     => $timeline,
            'balance'      => $balance,
            'totalInvoiced'=> $totalInvoiced,
            'totalPaid'    => $totalPaid,
            'openInvoices' => $openInvoices,
            'wallets'      => $wallets,
        ]);
    }

    /**
     * إرسال كشف الحساب بالبريد الإلكتروني كملف PDF
     */
    public function sendEmail(Vendor $vendor)
    {
        $locale = app()->getLocale();

        if (!$vendor->email) {
            return back()->with('error', $locale === 'ar' ? 'لا يوجد بريد إلكتروني مسجل لهذا المورد.' : 'No email address is registered for this vendor.');
        }

        [$timeline, $balance, $totalInvoiced, $totalPaid] = $this->buildTimeline($vendor);

        try {
            Mail::to($vendor->email)->send(new VendorStatementMail($vendor, $timeline, $balance, $locale));

            return back()->with('success', $locale === 'ar'
                ? 'تم إرسال كشف الحساب بنجاح إلى ' . $vendor->email
                : 'Statement sent successfully to ' . $vendor->email);
        } catch (\Throwable $e) {
            return back()->with('error', $locale === 'ar' ? 'فشل إرسال البريد: ' . $e->getMessage() : 'Mail send failed: ' . $e->getMessage());
        }
    }

    /**
     * بناء الجدول الزمني للحركات + الرصيد الجاري لمورد معيّن
     */
    private function buildTimeline(Vendor $vendor): array
    {
        $vendor->load(['purchaseInvoices', 'payments']);

        $invoiceEntries = $vendor->purchaseInvoices
            ->map(function ($invoice) {
                return [
                    'date'   => $invoice->invoice_date,
                    'type'   => 'invoice',
                    'ref'    => $invoice->invoice_number,
                    'amount' => $invoice->grand_total,
                    'currency' => $invoice->currency,
                    'link'   => route('purchase-invoices.show', $invoice),
                ];
            })->values();

        $paymentEntries = $vendor->payments->map(fn ($p) => [
            'date'   => $p->payment_date,
            'type'   => 'payment',
            'ref'    => $p->payment_number,
            'amount' => -1 * ($p->foreign_amount ?? $p->amount),
            'currency' => $p->foreign_currency ?? $p->currency,
            'link'   => route('vendor-payments.edit', $p),
        ]);

        $timeline = $invoiceEntries->concat($paymentEntries)->sortBy('date')->values();

        $running = [];
        $timeline = $timeline->map(function ($entry) use (&$running) {
            $currency = $entry['currency'] ?? 'EGP';
            if (!isset($running[$currency])) {
                $running[$currency] = 0;
            }
            $running[$currency] += $entry['amount'];
            
            // Capture all current balances for this entry
            $entry['running_balances'] = $running;
            return $entry;
        });

        // Compute total invoiced per currency
        $totalInvoiced = [];
        foreach ($invoiceEntries as $inv) {
            $c = $inv['currency'] ?? 'EGP';
            $totalInvoiced[$c] = ($totalInvoiced[$c] ?? 0) + $inv['amount'];
        }

        // Compute total paid per currency
        $totalPaid = [];
        foreach ($paymentEntries as $pay) {
            $c = $pay['currency'] ?? 'EGP';
            $totalPaid[$c] = ($totalPaid[$c] ?? 0) + abs($pay['amount']);
        }

        return [$timeline, $running, $totalInvoiced, $totalPaid];
    }
}
