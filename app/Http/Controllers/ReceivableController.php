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
            ->whereHas('salesOrders')
            ->withSum('salesOrders as ordered_total', 'grand_total')
            ->withSum('receipts as collected_total', 'amount');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('company_name', 'like', "%{$search}%")
                  ->orWhere('company_name_en', 'like', "%{$search}%");
        }

        $clients = $query->get()
            ->map(function ($client) {
                $client->balance = (float) $client->ordered_total - (float) $client->collected_total;
                return $client;
            });

        $sort = $request->input('sort', 'balance_desc');
        $clients = match ($sort) {
            'balance_asc' => $clients->sortBy('balance'),
            'newest'      => $clients->sortByDesc('created_at'),
            'oldest'      => $clients->sortBy('created_at'),
            default       => $clients->sortByDesc('balance'), // balance_desc
        };
        $clients = $clients->values();

        $summary = [
            'ordered'   => $clients->sum('ordered_total'),
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

        // أوامر البيع اللي لسه عليها رصيد — تُستخدم في نموذج تسجيل الدفعة (كامل/جزئي)
        $openOrders = $client->salesOrders
            ->map(fn ($so) => ['id' => $so->id, 'so_number' => $so->so_number, 'balance_due' => $so->balance_due, 'currency' => $so->currency])
            ->filter(fn ($so) => $so['balance_due'] > 0)
            ->values();

        return view('receivables.show', [
            'client'            => $client,
            'timeline'          => $timeline,
            'balance'           => $balance,
            'openOrders'        => $openOrders,
            'nextReceiptNumber' => $this->nextReceiptNumber(),
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
        $client->load(['salesOrders', 'receipts']);

        $orderEntries = $client->salesOrders->map(fn ($so) => [
            'date'   => $so->so_date,
            'type'   => 'order',
            'ref'    => $so->so_number,
            'amount' => $so->grand_total,
            'link'   => route('sales-orders.show', $so),
        ]);

        $receiptEntries = $client->receipts->map(fn ($r) => [
            'date'   => $r->receipt_date,
            'type'   => 'receipt',
            'ref'    => $r->receipt_number,
            'amount' => -1 * $r->amount,
            'link'   => null,
        ]);

        $timeline = $orderEntries->concat($receiptEntries)->sortBy('date')->values();

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
