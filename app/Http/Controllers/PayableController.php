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
            ->withSum('payments as paid_total', 'amount');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('vendor_code', 'like', "%{$search}%");
            });
        }

        $vendors = $query->get()
            ->map(function ($vendor) {
                $vendor->balance = (float) $vendor->invoiced_total - (float) $vendor->paid_total;
                return $vendor;
            });

        $tab = $request->input('tab', 'active');
        if ($tab === 'paid') {
            $vendors = $vendors->filter(fn ($v) => $v->balance <= 0);
        } else {
            $vendors = $vendors->filter(fn ($v) => $v->balance > 0);
        }

        $sort = $request->input('sort', 'balance_desc');
        $vendors = match ($sort) {
            'balance_asc' => $vendors->sortBy('balance'),
            'newest'      => $vendors->sortByDesc('created_at'),
            'oldest'      => $vendors->sortBy('created_at'),
            default       => $vendors->sortByDesc('balance'), // balance_desc
        };
        $vendors = $vendors->values();

        $summary = [
            'invoiced' => $vendors->sum('invoiced_total'),
            'paid'     => $vendors->sum('paid_total'),
            'balance'  => $vendors->sum('balance'),
        ];

        return view('payables.index', compact('vendors', 'summary', 'sort'));
    }

    /**
     * كشف حساب مورد — فواتير الشراء (دين) وسندات الدفع (سداد) بالترتيب الزمني والرصيد الجاري
     */
    public function show(Vendor $vendor)
    {
        [$timeline, $balance] = $this->buildTimeline($vendor);

        return view('payables.show', [
            'vendor'   => $vendor,
            'timeline' => $timeline,
            'balance'  => $balance,
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

        [$timeline, $balance] = $this->buildTimeline($vendor);

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
                    'link'   => route('purchase-invoices.show', $invoice),
                ];
            })->values();

        $paymentEntries = $vendor->payments->map(fn ($p) => [
            'date'   => $p->payment_date,
            'type'   => 'payment',
            'ref'    => $p->payment_number,
            'amount' => -1 * $p->amount,
            'link'   => route('vendor-payments.edit', $p),
        ]);

        $timeline = $invoiceEntries->concat($paymentEntries)->sortBy('date')->values();

        $running = 0;
        $timeline = $timeline->map(function ($entry) use (&$running) {
            $running += $entry['amount'];
            $entry['balance'] = $running;
            return $entry;
        });

        return [$timeline, $running];
    }
}
