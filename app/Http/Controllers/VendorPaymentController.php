<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\Wallet;
use App\Rules\MatchesWalletCurrency;
use App\Services\SequenceGenerator;
use App\Services\WalletLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VendorPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorPayment::with('vendor');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($v) => $v->where('name_ar', 'like', "%{$search}%"));
            });
        }

        $payments = $query->latest()->paginate(15)->withQueryString();

        return view('vendor_payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $request->validate(['purchase_invoice_id' => 'required|exists:purchase_invoices,id']);

        $purchaseInvoice = \App\Models\PurchaseInvoice::with(['vendor', 'quotation'])->findOrFail($request->purchase_invoice_id);

        $lookups = Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });

        return view('vendor_payments.create', [
            'purchaseInvoice' => $purchaseInvoice,
            'paymentMethods'  => $lookups->get('payment_method') ?? collect(),
            'wallets'         => Wallet::orderBy('name')->get(['id', 'name', 'currency']),
        ]);
    }

    public function store(Request $request)
    {
        $isAr = app()->getLocale() === 'ar';

        $data = $request->validate([
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'wallet_id'           => 'required|exists:wallets,id',
            'amount'              => 'required|numeric|min:0.01',
            'currency'            => [
                'required', 'string', new MatchesWalletCurrency,
                function ($attribute, $value, $fail) use ($request, $isAr) {
                    $invoice = \App\Models\PurchaseInvoice::find($request->input('purchase_invoice_id'));
                    if ($invoice && $value !== $invoice->currency) {
                        $fail($isAr
                            ? "فاتورة الشراء {$invoice->invoice_number} كانت بعملة {$invoice->currency} — لا يمكن تسجيل سند دفع بعملة مختلفة."
                            : "Purchase invoice {$invoice->invoice_number} was issued in {$invoice->currency} — a payment in a different currency is not allowed.");
                    }
                },
            ],
            'payment_date'        => 'required|date',
            'payment_method'      => 'nullable|string',
            'notes'               => 'nullable|string',
        ]);

        $purchaseInvoice = \App\Models\PurchaseInvoice::findOrFail($data['purchase_invoice_id']);
        $data['vendor_id'] = $purchaseInvoice->vendor_id;
        $data['created_by'] = auth()->id();

        try {
            DB::transaction(function () use (&$data) {
                // قفل صف المحفظة والتحقق من كفاية الرصيد — آمن حتى مع طلبات متزامنة
                WalletLedger::lockAndCheck($data['wallet_id'], $data['amount']);

                $data['payment_number'] = SequenceGenerator::next('VP');

                VendorPayment::create($data);
            });
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('payables.show', $data['vendor_id'])
            ->with('success', app()->getLocale() === 'ar' ? 'تم تسجيل سند الدفع بنجاح' : 'Payment recorded successfully');
    }

    public function edit(VendorPayment $vendorPayment)
    {
        $lookups = Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });

        $purchaseInvoice = \App\Models\PurchaseInvoice::with(['vendor', 'quotation'])->findOrFail($vendorPayment->purchase_invoice_id);

        return view('vendor_payments.edit', [
            'payment'         => $vendorPayment,
            'purchaseInvoice' => $purchaseInvoice,
            'paymentMethods'  => $lookups->get('payment_method') ?? collect(),
            'wallets'         => Wallet::orderBy('name')->get(['id', 'name', 'currency']),
        ]);
    }

    public function update(Request $request, VendorPayment $vendorPayment)
    {
        $isAr = app()->getLocale() === 'ar';

        $data = $request->validate([
            'wallet_id'      => 'required|exists:wallets,id',
            'amount'         => 'required|numeric|min:0.01',
            'currency'       => [
                'required', 'string', new MatchesWalletCurrency,
                function ($attribute, $value, $fail) use ($vendorPayment, $isAr) {
                    $invoice = \App\Models\PurchaseInvoice::find($vendorPayment->purchase_invoice_id);
                    if ($invoice && $value !== $invoice->currency) {
                        $fail($isAr
                            ? "فاتورة الشراء {$invoice->invoice_number} كانت بعملة {$invoice->currency} — لا يمكن تسجيل سند دفع بعملة مختلفة."
                            : "Purchase invoice {$invoice->invoice_number} was issued in {$invoice->currency} — a payment in a different currency is not allowed.");
                    }
                },
            ],
            'payment_date'   => 'required|date',
            'payment_method' => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();

        try {
            DB::transaction(function () use ($data, $vendorPayment) {
                WalletLedger::lockAndCheck($data['wallet_id'], $data['amount'], excludeAmount: (float) $vendorPayment->amount);
                $vendorPayment->update($data);
            });
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('payables.show', $vendorPayment->vendor_id)
            ->with('success', app()->getLocale() === 'ar' ? 'تم تحديث سند الدفع بنجاح' : 'Payment updated successfully');
    }
}
