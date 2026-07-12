<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
            'nextNumber'     => $this->nextNumber(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'payment_number'      => 'required|string|unique:vendor_payments,payment_number',
            'vendor_id'           => 'required|exists:vendors,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'wallet_id'           => 'required|exists:wallets,id',
            'amount'              => 'required|numeric|min:0.01',
            'currency'            => 'required|string',
            'payment_date'        => 'required|date',
            'payment_method'      => 'nullable|string',
            'notes'               => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();
        VendorPayment::create($data);

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
            'nextNumber'     => $vendorPayment->payment_number,
        ]);
    }

    public function update(Request $request, VendorPayment $vendorPayment)
    {
        $data = $request->validate([
            'payment_number'      => 'required|string|unique:vendor_payments,payment_number,' . $vendorPayment->id,
            'vendor_id'           => 'required|exists:vendors,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'wallet_id'           => 'required|exists:wallets,id',
            'amount'              => 'required|numeric|min:0.01',
            'currency'            => 'required|string',
            'payment_date'        => 'required|date',
            'payment_method'      => 'nullable|string',
            'notes'               => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();
        $vendorPayment->update($data);

        return redirect()->route('payables.show', $data['vendor_id'])
            ->with('success', app()->getLocale() === 'ar' ? 'تم تحديث سند الدفع بنجاح' : 'Payment updated successfully');
    }

    private function nextNumber(): string
    {
        $last = VendorPayment::latest('id')->first();
        $seq  = $last ? $last->id + 1 : 1;
        return 'VP-' . now()->format('Y-m') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
