<?php

namespace App\Http\Controllers;

use App\Mail\ClientStatementMail;
use App\Models\Client;
use App\Models\ClientReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ReceivableController extends Controller
{
    /**
     * كل العملاء اللي ليهم رصيد — إجمالي أوامر البيع مقابل إجمالي المحصّل
     */
    public function index(Request $request)
    {
        $query = Client::query()
            ->whereHas('salesInvoices')
            ->withSum('salesInvoices as invoiced_total', 'grand_total')
            ->withSum('receipts as collected_total', 'amount');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('company_name', 'like', "%{$search}%")
                  ->orWhere('company_name_en', 'like', "%{$search}%");
        }

        $clients = $query->get()
            ->map(function ($client) {
                $client->balance = (float) $client->invoiced_total - (float) $client->collected_total;
                return $client;
            });

        $tab = $request->input('tab', 'active');
        if ($tab === 'paid') {
            $clients = $clients->filter(fn ($c) => $c->balance <= 0);
        } else {
            $clients = $clients->filter(fn ($c) => $c->balance > 0);
        }

        $sort = $request->input('sort', 'balance_desc');
        $clients = match ($sort) {
            'balance_asc' => $clients->sortBy('balance'),
            'newest'      => $clients->sortByDesc('created_at'),
            'oldest'      => $clients->sortBy('created_at'),
            default       => $clients->sortByDesc('balance'), // balance_desc
        };
        $clients = $clients->values();

        $summary = [
            'invoiced'  => $clients->sum('invoiced_total'),
            'collected' => $clients->sum('collected_total'),
            'balance'   => $clients->sum('balance'),
        ];

        return view('receivables.index', compact('clients', 'summary', 'sort'));
    }

    /**
     * كشف حساب عميل — أوامر البيع (مستحق) وسندات القبض (تحصيل) بالترتيب الزمني والرصيد الجاري
     */
    public function show(Client $client)
    {
        [$timeline, $balance] = $this->buildTimeline($client);

        // فواتير البيع اللي لسه عليها رصيد — تُستخدم في نموذج تسجيل الدفعة (كامل/جزئي)
        $openInvoices = $client->salesInvoices
            ->map(fn ($si) => ['id' => $si->id, 'invoice_number' => $si->invoice_number, 'balance_due' => $si->balance_due, 'currency' => $si->currency])
            ->filter(fn ($si) => $si['balance_due'] > 0)
            ->values();

        $wallets = \App\Models\Wallet::orderBy('name')->get(['id', 'name']);

        return view('receivables.show', [
            'client'            => $client,
            'timeline'          => $timeline,
            'balance'           => $balance,
            'openInvoices'      => $openInvoices,
            'nextReceiptNumber' => $this->nextReceiptNumber(),
            'wallets'           => $wallets,
        ]);
    }

    /**
     * إرسال كشف الحساب بالبريد الإلكتروني كملف PDF
     */
    public function sendEmail(Client $client)
    {
        $locale = app()->getLocale();

        if (!$client->email) {
            return back()->with('error', $locale === 'ar' ? 'لا يوجد بريد إلكتروني مسجل لهذا العميل.' : 'No email address is registered for this client.');
        }

        [$timeline, $balance] = $this->buildTimeline($client);

        try {
            Mail::to($client->email)->send(new ClientStatementMail($client, $timeline, $balance, $locale));

            return back()->with('success', $locale === 'ar'
                ? 'تم إرسال كشف الحساب بنجاح إلى ' . $client->email
                : 'Statement sent successfully to ' . $client->email);
        } catch (\Throwable $e) {
            return back()->with('error', $locale === 'ar' ? 'فشل إرسال البريد: ' . $e->getMessage() : 'Mail send failed: ' . $e->getMessage());
        }
    }

    /**
     * بناء الجدول الزمني للحركات + الرصيد الجاري لعميل معيّن
     */
    private function buildTimeline(Client $client): array
    {
        $client->load(['salesInvoices', 'receipts']);

        $invoiceEntries = $client->salesInvoices->map(fn ($si) => [
            'date'   => $si->invoice_date,
            'type'   => 'invoice',
            'ref'    => $si->invoice_number,
            'amount' => $si->grand_total,
            'link'   => route('sales-invoices.show', $si),
        ]);

        $receiptEntries = $client->receipts->map(fn ($r) => [
            'date'   => $r->receipt_date,
            'type'   => 'receipt',
            'ref'    => $r->receipt_number,
            'amount' => -1 * $r->amount,
            'link'   => null,
        ]);

        $timeline = $invoiceEntries->concat($receiptEntries)->sortBy('date')->values();

        $running = 0;
        $timeline = $timeline->map(function ($entry) use (&$running) {
            $running += $entry['amount'];
            $entry['balance'] = $running;
            return $entry;
        });

        return [$timeline, $running];
    }

    private function nextReceiptNumber(): string
    {
        $last = ClientReceipt::latest('id')->first();
        $seq  = $last ? $last->id + 1 : 1;
        return 'RC-' . now()->format('Y-m') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
