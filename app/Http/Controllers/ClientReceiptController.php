<?php

namespace App\Http\Controllers;

use App\Models\ClientReceipt;
use App\Models\SalesOrder;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClientReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = ClientReceipt::with('client', 'salesOrder');

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
        $request->validate(['sales_order_id' => 'required|exists:sales_orders,id']);

        $salesOrder = SalesOrder::with('client')->findOrFail($request->sales_order_id);

        $lookups = Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });

        return view('client_receipts.create', [
            'salesOrder'     => $salesOrder,
            'paymentMethods' => $lookups->get('payment_method') ?? collect(),
            'nextNumber'     => $this->nextNumber(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'receipt_number' => 'required|string|unique:client_receipts,receipt_number',
            'amount'         => 'required|numeric|min:0.01',
            'currency'       => 'required|string',
            'receipt_date'   => 'required|date',
            'payment_method' => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        $salesOrder = SalesOrder::findOrFail($data['sales_order_id']);

        ClientReceipt::create([
            'receipt_number' => $data['receipt_number'],
            'client_id'      => $salesOrder->client_id,
            'sales_order_id' => $salesOrder->id,
            'quotation_id'   => $salesOrder->quotation_id,
            'amount'         => $data['amount'],
            'currency'       => $data['currency'],
            'receipt_date'   => $data['receipt_date'],
            'payment_method' => $data['payment_method'] ?? null,
            'notes'          => $data['notes'] ?? null,
        ]);

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', app()->getLocale() === 'ar' ? 'تم تسجيل سند القبض بنجاح' : 'Receipt recorded successfully');
    }

    private function nextNumber(): string
    {
        $last = ClientReceipt::latest('id')->first();
        $seq  = $last ? $last->id + 1 : 1;
        return 'RC-' . now()->format('Y-m') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
