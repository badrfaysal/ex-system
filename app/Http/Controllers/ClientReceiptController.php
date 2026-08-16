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
            'amount'           => 'required|numeric|min:0.01',
            'foreign_amount'   => 'nullable|numeric|min:0.01',
            'foreign_currency' => 'nullable|string',
            'exchange_rate'    => 'nullable|numeric|min:0.000001',
            'currency'         => 'required|string',
            'receipt_date'     => 'required|date',
            'payment_method'   => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $salesInvoice = SalesInvoice::findOrFail($data['sales_invoice_id']);
        $wallet = Wallet::findOrFail($data['wallet_id']);

        $paidTowardsInvoice = $data['foreign_amount'] ?? $data['amount'];
        
        if (round((float) $paidTowardsInvoice, 2) > round($salesInvoice->balance_due, 2)) {
            return back()->withErrors(['amount' => $isAr 
                ? 'المبلغ المدخل أكبر من المتبقي على فاتورة البيع (' . number_format($salesInvoice->balance_due, 2) . ' ' . $salesInvoice->currency . ').'
                : 'The amount exceeds the sales invoice balance due (' . number_format($salesInvoice->balance_due, 2) . ' ' . $salesInvoice->currency . ').'])->withInput();
        }

        // If currencies differ, use foreign_amount for the invoice currency, and amount for wallet currency
        if ($salesInvoice->currency !== $wallet->currency) {
            $data['foreign_currency'] = $salesInvoice->currency;
            $data['foreign_amount'] = $paidTowardsInvoice;
            $data['exchange_rate'] = $data['exchange_rate'] ?? 1;
            $data['currency'] = $wallet->currency;
            $data['amount'] = round($data['foreign_amount'] * $data['exchange_rate'], 2);
        } else {
            $data['currency'] = $wallet->currency;
            $data['amount'] = $paidTowardsInvoice;
            $data['foreign_amount'] = null;
            $data['foreign_currency'] = null;
            $data['exchange_rate'] = 1;
        }

        $salesInvoice = SalesInvoice::findOrFail($data['sales_invoice_id']);

        DB::transaction(function () use ($data, $salesInvoice) {
            ClientReceipt::create([
                'receipt_number'   => SequenceGenerator::next('RC'),
                'client_id'        => $salesInvoice->client_id,
                'sales_invoice_id' => $salesInvoice->id,
                'quotation_id'     => $salesInvoice->quotation_id,
                'wallet_id'        => $data['wallet_id'],
                'amount'           => $data['amount'],
                'base_amount'      => $data['currency'] === 'EGP' ? $data['amount'] : ($data['amount'] * ($data['exchange_rate'] ?? 1)),
                'foreign_amount'   => $data['foreign_amount'] ?? null,
                'foreign_currency' => $data['foreign_currency'] ?? null,
                'exchange_rate'    => $data['exchange_rate'] ?? 1,
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
