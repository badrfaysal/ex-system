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
        $lookups = Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });

        return view('vendor_payments.create', [
            'vendors'        => Vendor::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'paymentMethods' => $lookups->get('payment_method') ?? collect(),
            'currencies'     => $lookups->get('currency') ?? collect(),
            'wallets'        => Wallet::orderBy('name')->get(['id', 'name', 'currency']),
            'selectedVendorId' => $request->integer('vendor_id') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_id'           => 'required|exists:vendors,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'wallet_id'           => 'required|exists:wallets,id',
            'amount'              => 'required|numeric|min:0.01',
            'currency'            => ['required', 'string', new MatchesWalletCurrency],
            'payment_date'        => 'required|date',
            'payment_method'      => 'nullable|string',
            'notes'               => 'nullable|string',
        ]);

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

        return view('vendor_payments.edit', [
            'payment'        => $vendorPayment,
            'vendors'        => Vendor::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']),
            'paymentMethods' => $lookups->get('payment_method') ?? collect(),
            'currencies'     => $lookups->get('currency') ?? collect(),
            'wallets'        => Wallet::orderBy('name')->get(['id', 'name', 'currency']),
            'selectedVendorId' => $vendorPayment->vendor_id,
        ]);
    }

    public function update(Request $request, VendorPayment $vendorPayment)
    {
        $data = $request->validate([
            'vendor_id'           => 'required|exists:vendors,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'wallet_id'           => 'required|exists:wallets,id',
            'amount'              => 'required|numeric|min:0.01',
            'currency'            => ['required', 'string', new MatchesWalletCurrency],
            'payment_date'        => 'required|date',
            'payment_method'      => 'nullable|string',
            'notes'               => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();

        try {
            DB::transaction(function () use ($data, $vendorPayment) {
                // رجّع المبلغ القديم للمتاح قبل ما نتحقق من الجديد
                WalletLedger::lockAndCheck($data['wallet_id'], $data['amount'], excludeAmount: (float) $vendorPayment->amount);

                $vendorPayment->update($data);
            });
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('payables.show', $data['vendor_id'])
            ->with('success', app()->getLocale() === 'ar' ? 'تم تحديث سند الدفع بنجاح' : 'Payment updated successfully');
    }
}
