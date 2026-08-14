<?php

namespace App\Http\Controllers;

use App\Mail\ClientStatementMail;
use App\Models\Client;
use App\Models\Wallet;
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
            ->withSum('receipts as collected_total', \Illuminate\Support\Facades\DB::raw('COALESCE(foreign_amount, amount)'));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('company_name_en', 'like', "%{$search}%");
            });
        }

        $tab = $request->input('tab', 'active');
        if ($tab === 'paid') {
            $query->havingRaw('(COALESCE(invoiced_total, 0) - COALESCE(collected_total, 0)) <= 0');
        } else {
            $query->havingRaw('(COALESCE(invoiced_total, 0) - COALESCE(collected_total, 0)) > 0');
        }

        $sort = $request->input('sort', 'balance_desc');
        match ($sort) {
            'balance_asc' => $query->orderByRaw('(COALESCE(invoiced_total, 0) - COALESCE(collected_total, 0)) ASC'),
            'newest'      => $query->orderByDesc('created_at'),
            'oldest'      => $query->orderBy('created_at'),
            default       => $query->orderByRaw('(COALESCE(invoiced_total, 0) - COALESCE(collected_total, 0)) DESC'), // balance_desc
        };

        // Summary
        $summaryData = \Illuminate\Support\Facades\DB::query()
            ->fromSub(clone $query, 'sub')
            ->selectRaw('SUM(invoiced_total) as sum_invoiced, SUM(collected_total) as sum_collected, SUM(COALESCE(invoiced_total, 0) - COALESCE(collected_total, 0)) as sum_balance')
            ->first();

        $summary = [
            'invoiced'  => $summaryData->sum_invoiced ?? 0,
            'collected' => $summaryData->sum_collected ?? 0,
            'balance'   => $summaryData->sum_balance ?? 0,
        ];

        $clients = $query->paginate(50)->withQueryString();

        $clients->load(['salesInvoices', 'receipts']);

        // Calculate grouped currency balance for each model in the paginated collection
        $clients->getCollection()->transform(function ($client) {
            $currencyBalances = [];
            foreach ($client->salesInvoices as $inv) {
                $c = $inv->currency ?? 'EGP';
                $currencyBalances[$c] = ($currencyBalances[$c] ?? 0) + $inv->grand_total;
            }
            foreach ($client->receipts as $rec) {
                $c = $rec->foreign_currency ?? $rec->currency ?? 'EGP';
                $currencyBalances[$c] = ($currencyBalances[$c] ?? 0) - ($rec->foreign_amount ?? $rec->amount);
            }
            $client->currencyBalances = $currencyBalances;

            // Keep the raw single balance for legacy/sorting fallback if needed elsewhere
            $client->balance = (float) $client->invoiced_total - (float) $client->collected_total;
            return $client;
        });

        return view('receivables.index', compact('clients', 'summary', 'sort'));
    }

    /**
     * كشف حساب عميل — أوامر البيع (مستحق) وسندات القبض (تحصيل) بالترتيب الزمني والرصيد الجاري
     */
    public function show(Client $client)
    {
        [$timeline, $balance, $totalInvoiced, $totalPaid] = $this->buildTimeline($client);

        // فواتير البيع اللي لسه عليها رصيد — تُستخدم في نموذج تسجيل الدفعة (كامل/جزئي)
        $openInvoices = $client->salesInvoices
            ->map(fn ($si) => ['id' => $si->id, 'invoice_number' => $si->invoice_number, 'balance_due' => $si->balance_due, 'currency' => $si->currency])
            ->filter(fn ($si) => $si['balance_due'] > 0)
            ->values();

        $wallets = Wallet::orderBy('name')->get(['id', 'name', 'currency']);

        return view('receivables.show', [
            'client'       => $client,
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
    public function sendEmail(Client $client)
    {
        $locale = app()->getLocale();

        if (!$client->email) {
            return back()->with('error', $locale === 'ar' ? 'لا يوجد بريد إلكتروني مسجل لهذا العميل.' : 'No email address is registered for this client.');
        }

        [$timeline, $balance, $totalInvoiced, $totalPaid] = $this->buildTimeline($client);

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
            'currency' => $si->currency,
            'link'   => route('sales-invoices.show', $si),
        ]);

        $receiptEntries = $client->receipts->map(fn ($r) => [
            'date'   => $r->receipt_date,
            'type'   => 'receipt',
            'ref'    => $r->receipt_number,
            'amount' => -1 * ($r->foreign_amount ?? $r->amount),
            'currency' => $r->foreign_currency ?? $r->currency,
            'link'   => null,
        ]);

        $timeline = $invoiceEntries->concat($receiptEntries)->sortBy('date')->values();

        $running = [];
        $timeline = $timeline->map(function ($entry) use (&$running) {
            $currency = $entry['currency'] ?? 'EGP';
            if (!isset($running[$currency])) {
                $running[$currency] = 0;
            }
            $running[$currency] += $entry['amount'];
            
            $entry['running_balances'] = $running;
            return $entry;
        });

        $totalInvoiced = [];
        foreach ($invoiceEntries as $inv) {
            $c = $inv['currency'] ?? 'EGP';
            $totalInvoiced[$c] = ($totalInvoiced[$c] ?? 0) + $inv['amount'];
        }

        $totalPaid = [];
        foreach ($receiptEntries as $rec) {
            $c = $rec['currency'] ?? 'EGP';
            $totalPaid[$c] = ($totalPaid[$c] ?? 0) + abs($rec['amount']);
        }

        return [$timeline, $running, $totalInvoiced, $totalPaid];
    }
}
