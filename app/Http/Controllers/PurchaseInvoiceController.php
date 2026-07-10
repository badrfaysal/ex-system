<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\PurchaseInvoice;
use App\Models\Quotation;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    /**
     * قائمة فواتير الشراء
     */
    public function index(Request $request)
    {
        $query = PurchaseInvoice::with('quotation');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('quotation', fn ($qq) => $qq->where('quote_number', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        return view('purchase_invoices.index', compact('invoices'));
    }

    /**
     * شاشة إنشاء فاتورة شراء من عرض سعر — أصناف العرض + إمكانية إضافة أصناف زيادة
     */
    public function create(Request $request)
    {
        $request->validate(['quotation_id' => 'required|exists:quotations,id']);

        $quotation = Quotation::with(['client', 'items.item.approvedVendors'])->findOrFail($request->quotation_id);

        // فاتورة شراء واحدة بس لكل مركز تكلفة — لو موجودة نرجّع لها بدل ما نكرر
        $existing = $quotation->purchaseInvoices()->latest()->first();
        if ($existing) {
            $isAr = app()->getLocale() === 'ar';
            return redirect()->route('purchase-invoices.show', $existing)->with('error', $isAr
                ? "تم إنشاء فاتورة شراء لعرض السعر ده قبل كده: {$existing->invoice_number} بتاريخ {$existing->invoice_date->format('Y-m-d')} بإجمالي " . number_format($existing->grand_total, 2) . ' ' . $existing->currency
                : "A purchase invoice already exists for this quotation: {$existing->invoice_number} dated {$existing->invoice_date->format('Y-m-d')}, total " . number_format($existing->grand_total, 2) . ' ' . $existing->currency);
        }

        $vendors = Vendor::orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        $items   = Item::where('status', 'active')->with('approvedVendors')->orderBy('name_ar')->get();
        $nextInvoiceNumber = $this->nextNumber();

        return view('purchase_invoices.create', compact('quotation', 'vendors', 'items', 'nextInvoiceNumber'));
    }

    /**
     * حفظ فاتورة الشراء — بمجرد الحفظ تتحول لالتزام فوري لكل مورد في أسطرها
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'quotation_id'              => 'required|exists:quotations,id',
            'invoice_number'            => 'required|string|unique:purchase_invoices,invoice_number',
            'invoice_date'              => 'required|date',
            'currency'                  => 'required|string',
            'notes'                     => 'nullable|string',
            'lines'                     => 'required|array|min:1',
            'lines.*.vendor_id'         => 'required|exists:vendors,id',
            'lines.*.item_id'           => 'nullable|exists:items,id',
            'lines.*.quotation_item_id' => 'nullable|exists:quotation_items,id',
            'lines.*.description'       => 'required|string',
            'lines.*.quantity'          => 'required|numeric|min:0.001',
            'lines.*.uom'               => 'nullable|string',
            'lines.*.unit_price'        => 'required|numeric|min:0',
            'lines.*.discount_percent'  => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_percent'       => 'nullable|numeric|min:0|max:100',
        ]);

        $quotation = Quotation::findOrFail($data['quotation_id']);
        $existing  = $quotation->purchaseInvoices()->latest()->first();
        if ($existing) {
            return redirect()->route('purchase-invoices.show', $existing)
                ->with('error', app()->getLocale() === 'ar'
                    ? 'تم إنشاء فاتورة شراء لعرض السعر ده قبل كده بالفعل.'
                    : 'A purchase invoice already exists for this quotation.');
        }

        $invoice = DB::transaction(function () use ($data) {
            $invoice = PurchaseInvoice::create([
                'invoice_number' => $data['invoice_number'],
                'quotation_id'   => $data['quotation_id'],
                'invoice_date'   => $data['invoice_date'],
                'currency'       => $data['currency'],
                'notes'          => $data['notes'] ?? null,
                'subtotal'       => 0,
                'total_discount' => 0,
                'tax_amount'     => 0,
                'grand_total'    => 0,
            ]);

            $subtotal = $lineDiscounts = $taxAmount = 0;

            foreach ($data['lines'] as $line) {
                $qty       = (float) $line['quantity'];
                $price     = (float) $line['unit_price'];
                $discount  = (float) ($line['discount_percent'] ?? 0);
                $tax       = (float) ($line['tax_percent'] ?? 0);

                $lineBase  = $qty * $price;
                $discVal   = $lineBase * $discount / 100;
                $afterDisc = $lineBase - $discVal;
                $taxVal    = $afterDisc * $tax / 100;
                $netTotal  = round($afterDisc + $taxVal, 2);

                $invoice->items()->create([
                    'vendor_id'         => $line['vendor_id'],
                    'item_id'           => $line['item_id'] ?? null,
                    'quotation_item_id' => $line['quotation_item_id'] ?? null,
                    'item_code'         => $line['item_code'] ?? null,
                    'description'       => $line['description'],
                    'quantity'          => $qty,
                    'uom'               => $line['uom'] ?? null,
                    'unit_price'        => $price,
                    'discount_percent'  => $discount,
                    'tax_percent'       => $tax,
                    'net_total'         => $netTotal,
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
                ? 'تم إنشاء فاتورة الشراء ' . $invoice->invoice_number . ' وأصبحت التزامًا فوريًا للموردين'
                : 'Purchase invoice ' . $invoice->invoice_number . ' created and is now an immediate vendor liability');
    }

    private function nextNumber(): string
    {
        $last = PurchaseInvoice::latest('id')->first();
        $seq  = $last ? $last->id + 1 : 1;
        return 'PI-' . now()->format('Y-m') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['quotation', 'items.vendor', 'items.item']);
        $byVendor = $purchaseInvoice->items->groupBy('vendor_id');

        return view('purchase_invoices.show', compact('purchaseInvoice', 'byVendor'));
    }
}
