<?php

namespace App\Http\Controllers;

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
                  ->orWhereHas('vendor', fn ($v) => $v->where('name_ar', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        return view('purchase_invoices.index', compact('invoices'));
    }

    /**
     * فاتورة شراء واحدة لكل مورد — بتتعمل من أمر بيع، والأصناف من كل الكتالوج
     */
    public function create(Request $request)
    {
        if (!$request->filled('sales_order_id')) {
            $salesOrders = SalesOrder::with('client')->latest()->paginate(20);

            return view('purchase_invoices.select_order', compact('salesOrders'));
        }

        $request->validate(['sales_order_id' => 'required|exists:sales_orders,id']);

        $salesOrder = SalesOrder::with(['client', 'quotation', 'items.item'])->findOrFail($request->sales_order_id);

        $vendors = Vendor::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        $items   = Item::with('approvedVendors')->orderBy('name_ar')->get();

        return view('purchase_invoices.create', [
            'salesOrder'        => $salesOrder,
            'vendors'           => $vendors,
            'items'             => $items,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sales_order_id'           => 'required|exists:sales_orders,id',
            'vendor_id'                => 'required|exists:vendors,id',
            'invoice_date'             => 'required|date',
            'currency'                 => 'required|string',
            'notes'                    => 'nullable|string',
            'lines'                    => 'required|array|min:1',
            'lines.*.item_id'          => 'nullable|exists:items,id',
            'lines.*.description'      => 'required|string',
            'lines.*.quantity'         => 'required|numeric|min:0.001',
            'lines.*.uom'              => 'nullable|string',
            'lines.*.unit_price'       => 'required|numeric|min:0',
            'lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_percent'      => 'nullable|numeric|min:0|max:100',
        ]);

        $salesOrder = SalesOrder::findOrFail($data['sales_order_id']);

        $invoice = DB::transaction(function () use ($data, $salesOrder) {
            $invoice = PurchaseInvoice::create([
                'invoice_number' => SequenceGenerator::next('PI'),
                'quotation_id'   => $salesOrder->quotation_id,
                'sales_order_id' => $salesOrder->id,
                'vendor_id'      => $data['vendor_id'],
                'invoice_date'   => $data['invoice_date'],
                'currency'       => $data['currency'],
                'notes'          => $data['notes'] ?? null,
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
}
