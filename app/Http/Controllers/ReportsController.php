<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ClientReceipt;
use App\Models\Item;
use App\Models\PriceList;
use App\Models\PurchaseInvoice;
use App\Models\Quotation;
use App\Models\Revenue;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /**
     * شاشة تقارير شاملة — بتحلل كل مصدر بيانات في النظام (مبيعات، مشتريات،
     * مخزون، ماليات، مراكز تكلفة، نشاط مستخدمين) في مكان واحد.
     *
     * الفلتر الزمني (date_from/date_to) بيأثر على المقاييس "التدفقية" (حركات
     * حصلت خلال فترة: عروض أسعار، فواتير، مصروفات...)، أما المقاييس
     * "اللحظية" (أرصدة المحافظ، حالة المخزون، المستحقات/الالتزامات الحالية)
     * فبتفضل تعكس آخر حالة دايمًا بغض النظر عن الفلتر.
     */
    public function index(Request $request)
    {
        $from = $request->filled('date_from') ? $request->date('date_from')->startOfDay() : now()->subMonths(6)->startOfDay();
        $to   = $request->filled('date_to') ? $request->date('date_to')->endOfDay() : now()->endOfDay();

        $overdueInvoices = $this->overdueInvoices();

        return view('reports.index', [
            'dateFrom'          => $from->toDateString(),
            'dateTo'            => $to->toDateString(),
            'kpis'              => $this->kpis($from, $to),
            'salesFunnel'       => $this->salesFunnel(),
            'topClients'        => $this->topClients($from, $to),
            'topVendors'        => $this->topVendors($from, $to),
            'topItems'          => $this->topItems($from, $to),
            'topReceivables'    => $this->topReceivables(),
            'topPayables'       => $this->topPayables(),
            'overdueInvoices'   => $overdueInvoices,
            'overdueTotals'     => $overdueInvoices->groupBy('currency')->map(fn ($g) => $g->sum('balance_due_calc')),
            'itemsByGroup'      => Item::selectRaw('item_group, count(*) as cnt')->groupBy('item_group')->orderByDesc('cnt')->get(),
            'itemsByStatus'     => Item::selectRaw('status, count(*) as cnt')->groupBy('status')->get(),
            'vendorsByStatus'   => Vendor::selectRaw('status, count(*) as cnt')->groupBy('status')->get(),
            'clientsByType'     => Client::selectRaw('client_type, count(*) as cnt')->groupBy('client_type')->get(),
            'expenseByCategory' => Expense::notReversed()->whereBetween('expense_date', [$from, $to])
                                        ->selectRaw('category, sum(amount) as total, count(*) as cnt')
                                        ->groupBy('category')->orderByDesc('total')->get(),
            'walletBalances'    => Wallet::withBalanceSums()->orderBy('name')->get(),
            'cashFlowTrend'     => $this->monthlyTrend(),
            'quotationTrend'    => $this->quotationTrend(),
            'costCenters'       => $this->costCenterInsights(),
            'sourcingGaps'      => [
                'items_without_vendor' => Item::doesntHave('approvedVendors')->count(),
                'items_total'          => Item::count(),
            ],
            'priceLists'        => [
                'total'  => PriceList::count(),
                'active' => PriceList::where('status', 'active')->count(),
            ],
            'activity'          => $this->activityStats($from, $to),
            'settings'          => \Illuminate\Support\Facades\Cache::remember('system_settings', 60 * 60 * 24, function () {
                return \App\Models\Setting::all()->groupBy('category');
            }),
        ]);
    }

    private function kpis($from, $to): array
    {
        $receipts       = ClientReceipt::notReversed()->whereBetween('receipt_date', [$from, $to])->sum('amount');
        $revenues       = Revenue::notReversed()->whereBetween('revenue_date', [$from, $to])->sum('amount');
        $expenses       = Expense::notReversed()->whereBetween('expense_date', [$from, $to])->sum('amount');
        $vendorPayments = VendorPayment::notReversed()->whereBetween('payment_date', [$from, $to])->sum('amount');

        return [
            'clients_total'           => Client::count(),
            'clients_new'             => Client::whereBetween('created_at', [$from, $to])->count(),
            'vendors_total'           => Vendor::count(),
            'items_total'             => Item::count(),
            'items_active'            => Item::where('status', 'active')->count(),
            'quotations_count'        => Quotation::whereBetween('quote_date', [$from, $to])->count(),
            'quotations_value'        => (float) Quotation::whereBetween('quote_date', [$from, $to])->sum('grand_total'),
            'sales_orders_count'      => SalesOrder::whereBetween('so_date', [$from, $to])->count(),
            'sales_invoices_count'    => SalesInvoice::whereBetween('invoice_date', [$from, $to])->count(),
            'sales_invoices_value'    => (float) SalesInvoice::whereBetween('invoice_date', [$from, $to])->sum('grand_total'),
            'purchase_invoices_count' => PurchaseInvoice::whereBetween('invoice_date', [$from, $to])->count(),
            'purchase_invoices_value' => (float) PurchaseInvoice::whereBetween('invoice_date', [$from, $to])->sum('grand_total'),
            'cash_in'                 => (float) ($receipts + $revenues),
            'cash_out'                => (float) ($expenses + $vendorPayments),
            'net_cash_flow'           => (float) (($receipts + $revenues) - ($expenses + $vendorPayments)),
            'receivables_outstanding' => $this->receivablesOutstanding(),
            'payables_outstanding'    => $this->payablesOutstanding(),
        ];
    }

    private function receivablesOutstanding(): float
    {
        return (float) Client::query()
            ->withSum('salesInvoices as invoiced_total', 'grand_total')
            ->withSum(['receipts as collected_total' => fn ($q) => $q->whereNull('reversed_at')], 'amount')
            ->get(['id'])
            ->sum(fn ($c) => max(0, (float) $c->invoiced_total - (float) $c->collected_total));
    }

    private function payablesOutstanding(): float
    {
        return (float) Vendor::query()
            ->withSum('purchaseInvoices as invoiced_total', 'grand_total')
            ->withSum(['payments as paid_total' => fn ($q) => $q->whereNull('reversed_at')], 'amount')
            ->get(['id'])
            ->sum(fn ($v) => max(0, (float) $v->invoiced_total - (float) $v->paid_total));
    }

    /**
     * أكتر العملاء اللي ليّا عندهم فلوس (أعلى رصيد مستحق حالي — مش مرتبط بفلتر الفترة)
     */
    private function topReceivables(int $limit = 5): Collection
    {
        return Client::query()
            ->withSum('salesInvoices as invoiced_total', 'grand_total')
            ->withSum(['receipts as collected_total' => fn ($q) => $q->whereNull('reversed_at')], 'amount')
            ->get(['id', 'company_name', 'company_name_en'])
            ->map(function ($c) {
                $c->balance_due = max(0, (float) $c->invoiced_total - (float) $c->collected_total);
                return $c;
            })
            ->filter(fn ($c) => $c->balance_due > 0)
            ->sortByDesc('balance_due')
            ->take($limit)
            ->values();
    }

    /**
     * أكتر الموردين اللي ليهم عندي فلوس (أعلى التزام حالي — مش مرتبط بفلتر الفترة)
     */
    private function topPayables(int $limit = 5): Collection
    {
        return Vendor::query()
            ->withSum('purchaseInvoices as invoiced_total', 'grand_total')
            ->withSum(['payments as paid_total' => fn ($q) => $q->whereNull('reversed_at')], 'amount')
            ->get(['id', 'name_ar', 'name_en'])
            ->map(function ($v) {
                $v->balance_due = max(0, (float) $v->invoiced_total - (float) $v->paid_total);
                return $v;
            })
            ->filter(fn ($v) => $v->balance_due > 0)
            ->sortByDesc('balance_due')
            ->take($limit)
            ->values();
    }

    /**
     * فواتير بيع فات موعد استحقاقها ولسه فيها مبلغ متبقي — مش مرتبطة بفلتر الفترة
     * (زي المستحقات، بتعكس الحالة الحالية دايمًا)
     */
    private function overdueInvoices(): Collection
    {
        return SalesInvoice::query()
            ->overdue()
            ->with('client')
            ->withSum(['receipts as received_sum' => fn ($q) => $q->whereNull('reversed_at')], 'amount')
            ->orderBy('due_date')
            ->get()
            ->map(function ($invoice) {
                $invoice->balance_due_calc = (float) $invoice->grand_total - (float) ($invoice->received_sum ?? 0);
                $invoice->days_overdue = (int) round(abs(now()->diffInSeconds($invoice->due_date)) / 86400);
                return $invoice;
            })
            ->filter(fn ($invoice) => $invoice->balance_due_calc > 0.01)
            ->sortByDesc('days_overdue')
            ->values();
    }

    private function salesFunnel(): Collection
    {
        return Quotation::selectRaw('status, count(*) as cnt, sum(grand_total) as total')
            ->groupBy('status')->orderByDesc('cnt')->get();
    }

    private function topClients($from, $to, int $limit = 5): Collection
    {
        return Client::query()
            ->withSum(['salesInvoices as period_total' => fn ($q) => $q->whereBetween('invoice_date', [$from, $to])], 'grand_total')
            ->orderByDesc('period_total')
            ->limit($limit)
            ->get(['id', 'company_name', 'company_name_en'])
            ->filter(fn ($c) => (float) $c->period_total > 0)
            ->values();
    }

    private function topVendors($from, $to, int $limit = 5): Collection
    {
        return Vendor::query()
            ->withSum(['purchaseInvoices as period_total' => fn ($q) => $q->whereBetween('invoice_date', [$from, $to])], 'grand_total')
            ->orderByDesc('period_total')
            ->limit($limit)
            ->get(['id', 'name_ar', 'name_en'])
            ->filter(fn ($v) => (float) $v->period_total > 0)
            ->values();
    }

    private function topItems($from, $to, int $limit = 5): Collection
    {
        return SalesInvoiceItem::query()
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->whereBetween('sales_invoices.invoice_date', [$from, $to])
            ->selectRaw('sales_invoice_items.item_code, sales_invoice_items.description, SUM(sales_invoice_items.quantity) as total_qty, SUM(sales_invoice_items.net_total) as total_value')
            ->groupBy('sales_invoice_items.item_code', 'sales_invoice_items.description')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();
    }

    /**
     * تدفق نقدي شهري (وارد/منصرف) لآخر 6 شهور — من الجداول المالية الخمسة
     * (قبض/إيراد = وارد، مصروف/دفع مورد = منصرف)، مستبعد منها المعكوس.
     */
    private function monthlyTrend(int $months = 6): Collection
    {
        $start = now()->subMonths($months - 1)->startOfMonth()->toDateString();

        $receipts = DB::table('client_receipts')->whereNull('reversed_at')->where('receipt_date', '>=', $start)
            ->select(DB::raw("DATE_FORMAT(receipt_date, '%Y-%m') as ym"), 'amount');
        $revenues = DB::table('revenues')->whereNull('reversed_at')->where('revenue_date', '>=', $start)
            ->select(DB::raw("DATE_FORMAT(revenue_date, '%Y-%m') as ym"), 'amount');
        $expenses = DB::table('expenses')->whereNull('reversed_at')->where('expense_date', '>=', $start)
            ->select(DB::raw("DATE_FORMAT(expense_date, '%Y-%m') as ym"), DB::raw('amount * -1 as amount'));
        $payments = DB::table('vendor_payments')->whereNull('reversed_at')->where('payment_date', '>=', $start)
            ->select(DB::raw("DATE_FORMAT(payment_date, '%Y-%m') as ym"), DB::raw('amount * -1 as amount'));

        $union = $receipts->unionAll($revenues)->unionAll($expenses)->unionAll($payments);

        $rows = DB::query()->fromSub($union, 'flow')
            ->selectRaw('ym, SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as cash_in, SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) as cash_out')
            ->groupBy('ym')->get()->keyBy('ym');

        return $this->fillMonths($months, function ($ym) use ($rows) {
            $row = $rows->get($ym);
            return [
                'cash_in'  => (float) ($row->cash_in ?? 0),
                'cash_out' => abs((float) ($row->cash_out ?? 0)),
            ];
        });
    }

    private function quotationTrend(int $months = 6): Collection
    {
        $start = now()->subMonths($months - 1)->startOfMonth()->toDateString();

        $rows = Quotation::where('quote_date', '>=', $start)
            ->selectRaw("DATE_FORMAT(quote_date, '%Y-%m') as ym, count(*) as cnt, sum(grand_total) as total")
            ->groupBy('ym')->get()->keyBy('ym');

        return $this->fillMonths($months, function ($ym) use ($rows) {
            $row = $rows->get($ym);
            return [
                'count' => (int) ($row->cnt ?? 0),
                'value' => (float) ($row->total ?? 0),
            ];
        });
    }

    /**
     * يضمن ظهور كل شهر في آخر $months شهر حتى لو مفيهوش حركات (صفر) —
     * عشان الرسم البياني يفضل متصل بدل ما يقفز على الفجوات.
     */
    private function fillMonths(int $months, \Closure $dataFor): Collection
    {
        $result = collect();
        for ($i = $months - 1; $i >= 0; $i--) {
            $point = now()->subMonths($i);
            $ym = $point->format('Y-m');
            $result->push(array_merge(['ym' => $ym, 'label' => $point->translatedFormat('M Y')], $dataFor($ym)));
        }
        return $result;
    }

    private function costCenterInsights(int $limit = 5): array
    {
        $quotations = Quotation::query()
            ->withSum(['receipts as revenue_sum' => fn ($q) => $q->whereNull('reversed_at')], 'amount')
            ->withSum(['expenses as expenses_sum' => fn ($q) => $q->whereNull('reversed_at')], 'amount')
            ->withSum('purchaseInvoices as purchases_sum', 'grand_total')
            ->get(['id', 'quote_number', 'cost_center_name', 'client_id'])
            ->map(function ($q) {
                $q->revenue = (float) $q->revenue_sum;
                $q->cost    = (float) $q->expenses_sum + (float) $q->purchases_sum;
                $q->profit  = $q->revenue - $q->cost;
                return $q;
            });

        return [
            'total'          => $quotations->count(),
            'total_profit'   => $quotations->sum('profit'),
            'top_profitable' => $quotations->sortByDesc('profit')->filter(fn ($q) => $q->profit > 0)->take($limit)->values(),
            'top_losses'     => $quotations->filter(fn ($q) => $q->profit < 0)->sortBy('profit')->take($limit)->values(),
        ];
    }

    private function activityStats($from, $to): array
    {
        $topUserRow = ActivityLog::whereBetween('created_at', [$from, $to])
            ->whereNotNull('user_id')
            ->selectRaw('user_id, count(*) as cnt')
            ->groupBy('user_id')->orderByDesc('cnt')->first();

        return [
            'total_actions'   => ActivityLog::whereBetween('created_at', [$from, $to])->count(),
            'reversed_count'  => ActivityLog::where('action', 'reversed')->whereBetween('created_at', [$from, $to])->count(),
            'users_total'     => User::count(),
            'top_user_name'   => $topUserRow ? optional(User::find($topUserRow->user_id))->name : null,
            'top_user_count'  => $topUserRow->cnt ?? 0,
        ];
    }
}
