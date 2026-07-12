<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Services\SequenceGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesInvoice::with(['salesOrder', 'client']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$search}%"))
                  ->orWhereHas('salesOrder', fn ($so) => $so->where('so_number', 'like', "%{$search}%"));
            });
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        return view('sales_invoices.index', compact('invoices'));
    }

    /**
     * فاتورة بيع من أمر بيع — تقدر تعمل أكتر من فاتورة (فوترة جزئية)
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

            return view('sales_invoices.select_order', compact('salesOrders', 'clients'));
        }

        $salesOrder = SalesOrder::with(['client', 'quotation', 'items.item', 'items.salesInvoiceItems'])
            ->findOrFail($request->sales_order_id);

        $lines = $salesOrder->items->map(function ($item) {
            $invoiced = (float) $item->salesInvoiceItems->sum('quantity');
            $remaining = max(0, (float) $item->quantity - $invoiced);

            return [
                'item'      => $item,
                'invoiced'  => $invoiced,
                'remaining' => $remaining,
            ];
        })->filter(fn ($row) => $row['remaining'] > 0)->values();

        if ($lines->isEmpty()) {
            return redirect()->route('sales-orders.show', $salesOrder)
                ->with('error', app()->getLocale() === 'ar'
                    ? 'كل أصناف أمر البيع اتفوترت بالفعل — لا يمكن إنشاء فاتورة بيع جديدة.'
                    : 'All sales order items are already invoiced.');
        }

        $itemsList = \App\Models\Item::orderBy('name_ar')->get();

        $currencies = \Illuminate\Support\Facades\Cache::remember('system_settings', 60 * 60 * 24, function () {
            return \App\Models\Setting::all()->groupBy('category');
        })->get('currency') ?? collect();

        return view('sales_invoices.create', [
            'salesOrder'        => $salesOrder,
            'lines'             => $lines,
            'itemsList'         => $itemsList,
            'currencies'        => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sales_order_id'           => 'required|exists:sales_orders,id',
            'invoice_date'             => 'required|date',
            'currency'                 => 'required|string',
            'notes'                    => 'nullable|string',
            'selected_items'           => 'nullable|array',
            'selected_items.*'         => 'exists:sales_order_items,id',
            'quantities'               => 'nullable|array',
            'quantities.*'             => 'numeric|min:0.001',
            'prices'                   => 'nullable|array',
            'prices.*'                 => 'numeric|min:0',
            'extra_lines'              => 'nullable|array',
            'extra_lines.*.item_id'    => 'nullable|exists:items,id',
            'extra_lines.*.description'=> 'required_with:extra_lines|string',
            'extra_lines.*.quantity'   => 'required_with:extra_lines|numeric|min:0.001',
            'extra_lines.*.uom'        => 'nullable|string',
            'extra_lines.*.unit_price' => 'required_with:extra_lines|numeric|min:0',
            'extra_lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'extra_lines.*.tax_percent'      => 'nullable|numeric|min:0|max:100',
        ]);

        $salesOrder = SalesOrder::with(['items.salesInvoiceItems'])->findOrFail($data['sales_order_id']);
        $selectedIds = $data['selected_items'] ?? [];
        $extraLines = $data['extra_lines'] ?? [];

        if (empty($selectedIds) && empty($extraLines)) {
            return back()->withInput()->with('error', app()->getLocale() === 'ar'
                ? 'يجب اختيار صنف واحد على الأقل أو إضافة صنف إضافي.'
                : 'Select at least one item or add an extra line.');
        }

        $selectedItems = $salesOrder->items->whereIn('id', $selectedIds);

        $invoice = DB::transaction(function () use ($data, $salesOrder, $selectedItems, $extraLines) {
            $invoice = SalesInvoice::create([
                'invoice_number' => SequenceGenerator::next('SI'),
                'sales_order_id' => $salesOrder->id,
                'client_id'      => $salesOrder->client_id,
                'quotation_id'   => $salesOrder->quotation_id,
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

            // حفظ بنود أمر البيع المحددة
            foreach ($selectedItems as $soItem) {
                $qty      = (float) ($data['quantities'][$soItem->id] ?? $soItem->quantity);
                $price    = (float) ($data['prices'][$soItem->id] ?? $soItem->list_price);
                $discount = (float) $soItem->discount_percent;
                $tax      = (float) $soItem->tax_percent;

                $lineBase  = $qty * $price;
                $discVal   = $lineBase * $discount / 100;
                $afterDisc = $lineBase - $discVal;
                $taxVal    = $afterDisc * $tax / 100;
                $netTotal  = round($afterDisc + $taxVal, 2);

                $invoice->items()->create([
                    'sales_order_item_id' => $soItem->id,
                    'item_id'             => $soItem->item_id,
                    'item_code'           => $soItem->item_code,
                    'description'         => $soItem->description,
                    'quantity'            => $qty,
                    'uom'                 => $soItem->uom,
                    'unit_price'          => $price,
                    'discount_percent'    => $discount,
                    'tax_percent'         => $tax,
                    'net_total'           => $netTotal,
                ]);

                $subtotal      += $lineBase;
                $lineDiscounts += $discVal;
                $taxAmount     += $taxVal;
            }

            // حفظ البنود الإضافية (Extra Lines)
            foreach ($extraLines as $line) {
                if (empty($line['description'])) continue;

                $qty      = (float) $line['quantity'];
                $price    = (float) $line['unit_price'];
                $discount = (float) ($line['discount_percent'] ?? 0);
                $tax      = (float) ($line['tax_percent'] ?? 0);

                $lineBase  = $qty * $price;
                $discVal   = $lineBase * $discount / 100;
                $afterDisc = $lineBase - $discVal;
                $taxVal    = $afterDisc * $tax / 100;
                $netTotal  = round($afterDisc + $taxVal, 2);

                $item = !empty($line['item_id']) ? \App\Models\Item::find($line['item_id']) : null;

                $invoice->items()->create([
                    'sales_order_item_id' => null,
                    'item_id'             => $line['item_id'] ?? null,
                    'item_code'           => optional($item)->item_code,
                    'description'         => $line['description'],
                    'quantity'            => $qty,
                    'uom'                 => $line['uom'] ?? null,
                    'unit_price'          => $price,
                    'discount_percent'    => $discount,
                    'tax_percent'         => $tax,
                    'net_total'           => $netTotal,
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

        return redirect()->route('sales-invoices.show', $invoice)
            ->with('success', app()->getLocale() === 'ar'
                ? 'تم إنشاء فاتورة البيع ' . $invoice->invoice_number . ' وأصبحت مستحقًا فوريًا على العميل'
                : 'Sales invoice ' . $invoice->invoice_number . ' created and is now an immediate client receivable');
    }

    public function show(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['salesOrder', 'quotation', 'client', 'items.item', 'receipts.wallet', 'creator']);

        return view('sales_invoices.show', compact('salesInvoice'));
    }

    public function sendEmail(Request $request, SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['client', 'items']);
        $locale = app()->getLocale();

        $toEmail = optional($salesInvoice->client)->email;

        if (!$toEmail) {
            return back()->with('error', $locale === 'ar'
                ? 'لا يوجد بريد إلكتروني مسجل لهذا العميل.'
                : 'No email address is registered for this client.');
        }

        try {
            \Illuminate\Support\Facades\Mail::to($toEmail)
                ->send(new \App\Mail\SalesInvoiceMail($salesInvoice, $locale));

            return back()->with('success',
                $locale === 'ar'
                    ? 'تم إرسال الفاتورة بنجاح إلى ' . $toEmail
                    : 'Invoice sent successfully to ' . $toEmail
            );
        } catch (\Throwable $e) {
            return back()->with('error',
                $locale === 'ar'
                    ? 'فشل إرسال البريد: ' . $e->getMessage()
                    : 'Mail send failed: ' . $e->getMessage()
            );
        }
    }

    public function print(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['client', 'items', 'salesOrder']);
        return view('sales_invoices.print', compact('salesInvoice'));
    }

}
