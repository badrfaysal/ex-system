<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\SalesOrder;
use App\Models\Vendor;
use App\Services\SequenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseInvoice::with(['salesOrder', 'vendor']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('vendor_invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($v) => $v->where('name_ar', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('sort')) {
            $sort = $request->sort;
            if ($sort === 'oldest') {
                $query->oldest();
            } elseif ($sort === 'highest') {
                $query->orderBy('grand_total', 'desc');
            } elseif ($sort === 'lowest') {
                $query->orderBy('grand_total', 'asc');
            } else {
                $query->latest();
            }
        } else {
            $query->latest();
        }

        $invoices = $query->paginate(15)->withQueryString();

        return view('purchase_invoices.index', compact('invoices'));
    }

    /**
     * فاتورة شراء واحدة لكل مورد — بتتعمل من أمر بيع، والأصناف من كل الكتالوج
     */
    public function create(Request $request)
    {
        if (!$request->filled('sales_order_id')) {
            $query = SalesOrder::with('client');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('so_number', 'like', "%{$search}%");
            }
            if ($request->filled('client_id')) {
                $query->where('client_id', $request->client_id);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('so_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('so_date', '<=', $request->date_to);
            }

            $salesOrders = $query->orderByDesc('so_date')->orderByDesc('id')->paginate(20)->withQueryString();
            $clients = Client::orderBy('company_name')->get(['id', 'company_name', 'company_name_en']);

            return view('purchase_invoices.select_order', compact('salesOrders', 'clients'));
        }

        $request->validate(['sales_order_id' => 'required|exists:sales_orders,id']);

        $salesOrder = SalesOrder::with(['client', 'quotation', 'items.item'])->findOrFail($request->sales_order_id);

        $vendors = Vendor::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        $items   = Item::with('approvedVendors')->orderBy('name_ar')->get();

        $currencies = \Illuminate\Support\Facades\Cache::remember('system_settings', 60 * 60 * 24, function () {
            return \App\Models\Setting::all()->groupBy('category');
        })->get('currency') ?? collect();

        return view('purchase_invoices.create', [
            'salesOrder'        => $salesOrder,
            'vendors'           => $vendors,
            'items'             => $items,
            'currencies'        => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sales_order_id'           => 'required|exists:sales_orders,id',
            'vendor_id'                => 'required|exists:vendors,id',
            'invoice_date'             => 'required|date',
            'currency'                 => 'required|string',
            'vendor_invoice_number'    => 'nullable|string|max:255',
            'notes'                    => 'nullable|string',
            'lines'                    => 'required|array|min:1',
            'lines.*.item_id'          => 'nullable|exists:items,id',
            'lines.*.description'      => 'required|string',
            'lines.*.quantity'         => 'required|numeric|min:0.001',
            'lines.*.uom'              => 'nullable|string',
            'lines.*.unit_price'       => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_percent'      => 'nullable|numeric|min:0|max:100',
            'attachments'              => 'nullable|array|max:10',
            'attachments.*'            => 'file|mimes:jpeg,png,jpg,pdf,doc,docx,xls,xlsx,zip|max:10240',
        ]);

        $salesOrder = SalesOrder::findOrFail($data['sales_order_id']);

        $invoice = DB::transaction(function () use ($data, $salesOrder) {
            $invoice = PurchaseInvoice::create([
                'invoice_number'         => SequenceGenerator::next('PI'),
                'quotation_id'           => $salesOrder->quotation_id,
                'sales_order_id'         => $salesOrder->id,
                'vendor_id'              => $data['vendor_id'],
                'invoice_date'           => $data['invoice_date'],
                'currency'               => $data['currency'],
                'vendor_invoice_number'  => $data['vendor_invoice_number'] ?? null,
                'notes'                  => $data['notes'] ?? null,
                'created_by'     => Auth::id(),
                'subtotal'       => 0,
                'total_discount' => 0,
                'tax_amount'     => 0,
                'grand_total'    => 0,
            ]);

            $subtotal = $lineDiscounts = $taxAmount = 0;

            foreach ($data['lines'] as $line) {
                $qty      = (float) $line['quantity'];
                $price    = (float) $line['unit_price'];
                $discount = (float) ($line['discount_percent'] ?? 0);
                $tax      = (float) ($line['tax_percent'] ?? 0);

                $lineBase  = $qty * $price;
                $discVal   = $lineBase * $discount / 100;
                $afterDisc = $lineBase - $discVal;
                $taxVal    = $afterDisc * $tax / 100;
                $netTotal  = round($afterDisc + $taxVal, 2);

                $invoice->items()->create([
                    'item_id'          => $line['item_id'] ?? null,
                    'item_code'        => $line['item_code'] ?? null,
                    'description'      => $line['description'],
                    'quantity'         => $qty,
                    'uom'              => $line['uom'] ?? null,
                    'unit_price'       => $price,
                    'discount_percent' => $discount,
                    'tax_percent'      => $tax,
                    'net_total'        => $netTotal,
                ]);

                $subtotal      += $lineBase;
                $lineDiscounts += $discVal;
                $taxAmount     += $taxVal;
            }

            $invoice->update([
                'subtotal'       => round($subtotal, 2),
                'total_discount' => round($lineDiscounts, 2),
                'tax_amount'     => round($taxAmount, 2),
                'grand_total'    => round($subtotal - $lineDiscounts + $taxAmount, 2),
            ]);

            return $invoice;
        });

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments/purchase_invoices', 'public');
                $attachments[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getClientMimeType(),
                ];
            }
            $invoice->update(['attachments' => $attachments]);
        }

        return redirect()->route('purchase-invoices.show', $invoice)
            ->with('success', app()->getLocale() === 'ar'
                ? 'تم إنشاء فاتورة الشراء ' . $invoice->invoice_number . ' وأصبحت التزامًا فوريًا للمورد'
                : 'Purchase invoice ' . $invoice->invoice_number . ' created and is now an immediate vendor liability');
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['salesOrder', 'quotation', 'vendor', 'items.item', 'creator']);

        return view('purchase_invoices.show', compact('purchaseInvoice'));
    }

    public function print(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['vendor', 'quotation', 'items']);

        return view('purchase_invoices.print', compact('purchaseInvoice'));
    }
}
