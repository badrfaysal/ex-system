<?php

namespace App\Http\Controllers;

use App\Models\ClientReceipt;
use App\Models\SalesInvoice;
use App\Models\Setting;
use App\Models\Wallet;
use App\Rules\MatchesWalletCurrency;
use App\Services\SequenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClientReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = ClientReceipt::with('client', 'salesInvoice');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$search}%"));
            });
        }

        $receipts = $query->latest()->paginate(15)->withQueryString();

        return view('client_receipts.index', compact('receipts'));
    }

    public function create(Request $request)
    {
        $request->validate(['sales_invoice_id' => 'required|exists:sales_invoices,id']);

        $salesInvoice = SalesInvoice::with(['client', 'salesOrder'])->findOrFail($request->sales_invoice_id);

        $lookups = Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });

        return view('client_receipts.create', [
            'salesInvoice'   => $salesInvoice,
            'paymentMethods' => $lookups->get('payment_method') ?? collect(),
            'wallets'        => Wallet::orderBy('name')->get(['id', 'name', 'currency']),
        ]);
    }

    public function store(Request $request)
    {
        $isAr = app()->getLocale() === 'ar';

        $data = $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,id',
            'wallet_id'        => 'required|exists:wallets,id',
            'amount'           => [
                'required', 'numeric', 'min:0.01',
                function ($attribute, $value, $fail) use ($request, $isAr) {
                    $invoice = SalesInvoice::find($request->input('sales_invoice_id'));
                    if ($invoice && round((float) $value, 2) > round($invoice->balance_due, 2)) {
                        $fail($isAr
                            ? 'المبلغ المدخل أكبر من المتبقي على فاتورة البيع (' . number_format($invoice->balance_due, 2) . ' ' . $invoice->currency . ').'
                            : 'The amount exceeds the sales invoice balance due (' . number_format($invoice->balance_due, 2) . ' ' . $invoice->currency . ').');
                    }
                },
            ],
            'currency'         => [
                'required', 'string', new MatchesWalletCurrency,
                function ($attribute, $value, $fail) use ($request, $isAr) {
                    $invoice = SalesInvoice::find($request->input('sales_invoice_id'));
                    if ($invoice && $value !== $invoice->currency) {
                        $fail($isAr
                            ? "فاتورة البيع {$invoice->invoice_number} كانت بعملة {$invoice->currency} — لا يمكن تسجيل سند قبض بعملة مختلفة."
                            : "Sales invoice {$invoice->invoice_number} was issued in {$invoice->currency} — a receipt in a different currency is not allowed.");
                    }
                },
            ],
            'receipt_date'     => 'required|date',
            'payment_method'   => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $salesInvoice = SalesInvoice::findOrFail($data['sales_invoice_id']);

        DB::transaction(function () use ($data, $salesInvoice) {
            ClientReceipt::create([
                'receipt_number'   => SequenceGenerator::next('RC'),
                'client_id'        => $salesInvoice->client_id,
                'sales_invoice_id' => $salesInvoice->id,
                'quotation_id'     => $salesInvoice->quotation_id,
                'wallet_id'        => $data['wallet_id'],
                'amount'           => $data['amount'],
                'currency'         => $data['currency'],
                'receipt_date'     => $data['receipt_date'],
                'payment_method'   => $data['payment_method'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'created_by'       => auth()->id(),
            ]);
        });

        return redirect()->route('sales-invoices.show', $salesInvoice)
            ->with('success', app()->getLocale() === 'ar' ? 'تم تسجيل سند القبض بنجاح' : 'Receipt recorded successfully');
    }
}
