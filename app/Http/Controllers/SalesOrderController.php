<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    /**
     * قائمة أوامر البيع
     */
    public function index(Request $request)
    {
        $query = SalesOrder::with('client');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('so_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('sales_orders.index', compact('orders'));
    }

    /**
     * صفحة التحويل — يعرض أصناف عرض السعر مع checkboxes وحقول كميات
     */
    public function create(Request $request)
    {
        $request->validate(['quotation_id' => 'required|exists:quotations,id']);

        $quotation = Quotation::with(['client', 'items.item'])->findOrFail($request->quotation_id);

        if (!in_array($quotation->status, ['approved', 'sent'], true)) {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', app()->getLocale() === 'ar'
                    ? 'التحويل لأمر بيع متاح فقط للعروض المعتمدة أو المرسلة.'
                    : 'Conversion is only available for approved or sent quotations.');
        }

        $items = Item::where('status', 'active')->orderBy('name_ar')->get();

        return view('sales_orders.create', compact('quotation', 'items'));
    }

    /**
     * حفظ أمر البيع مع الكميات المعدّلة + تحويل حالة عرض السعر
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'quotation_id'                => 'required|exists:quotations,id',
            'selected_items'              => 'nullable|array',
            'selected_items.*'            => 'exists:quotation_items,id',
            'quantities'                  => 'nullable|array',
            'quantities.*'                => 'numeric|min:0.001',
            'prices'                      => 'nullable|array',
            'prices.*'                    => 'numeric|min:0',
            'extra_lines'                 => 'nullable|array',
            'extra_lines.*.item_id'       => 'nullable|exists:items,id',
            'extra_lines.*.description'   => 'required_with:extra_lines|string',
            'extra_lines.*.quantity'      => 'required_with:extra_lines|numeric|min:0.001',
            'extra_lines.*.uom'           => 'nullable|string',
            'extra_lines.*.list_price'    => 'required_with:extra_lines|numeric|min:0',
            'extra_lines.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'extra_lines.*.tax_percent'      => 'nullable|numeric|min:0|max:100',
        ]);

        $quotation = Quotation::with(['client', 'items.item'])->findOrFail($data['quotation_id']);

        if (!in_array($quotation->status, ['approved', 'sent'], true)) {
            return back()->with('error', app()->getLocale() === 'ar'
                ? 'هذا العرض لا يمكن تحويله لأمر بيع.'
                : 'This quotation cannot be converted to a sales order.');
        }

        $selectedIds   = $data['selected_items'] ?? [];
        $extraLines    = $data['extra_lines'] ?? [];

        if (empty($selectedIds) && empty($extraLines)) {
            return back()->with('error', app()->getLocale() === 'ar'
                ? 'اختر صنفاً واحداً على الأقل أو أضف صنفاً إضافيًا.'
                : 'Select at least one item or add an extra item.');
        }

        $quantities    = $data['quantities'] ?? [];
        $prices        = $data['prices'] ?? [];
        $selectedItems = $quotation->items->whereIn('id', $selectedIds);

        $salesOrder = DB::transaction(function () use ($quotation, $selectedItems, $quantities, $prices, $extraLines) {
            $so = SalesOrder::create([
                'so_number'      => $this->nextNumber(),
                'quotation_id'   => $quotation->id,
                'client_id'      => $quotation->client_id,
                'so_date'        => now()->toDateString(),
                'currency'       => $quotation->currency,
                'sales_rep'      => $quotation->sales_rep,
                'terms'          => $quotation->terms,
                'status'         => 'confirmed',
                'subtotal'       => 0,
                'total_discount' => 0,
                'tax_amount'     => 0,
                'grand_total'    => 0,
            ]);

            [$subtotal, $lineDiscounts, $taxAmount] = $this->saveItems($so, $selectedItems, $quantities, $prices);
            [$exSubtotal, $exDiscounts, $exTax]      = $this->saveExtraLines($so, $extraLines);

            $subtotal      += $exSubtotal;
            $lineDiscounts += $exDiscounts;
            $taxAmount     += $exTax;

            $so->update([
                'subtotal'       => round($subtotal, 2),
                'total_discount' => round($lineDiscounts, 2),
                'tax_amount'     => round($taxAmount, 2),
                'grand_total'    => round($subtotal - $lineDiscounts + $taxAmount, 2),
            ]);

            // تحويل حالة عرض السعر إلى «محوّل» نهائياً
            $quotation->update(['status' => 'converted']);

            return $so;
        });

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', app()->getLocale() === 'ar'
                ? 'تم إنشاء أمر البيع ' . $salesOrder->so_number . ' بنجاح'
                : 'Sales order ' . $salesOrder->so_number . ' created successfully');
    }

    /**
     * عرض أمر البيع مع الطباعة
     */
    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['client', 'items.item', 'quotation', 'receipts']);
        return view('sales_orders.show', compact('salesOrder'));
    }

    /**
     * صفحة تعديل أمر البيع
     */
    public function edit(SalesOrder $salesOrder)
    {
        $salesOrder->load(['client', 'items.item', 'quotation']);
        return view('sales_orders.edit', compact('salesOrder'));
    }

    /**
     * حفظ تعديلات أمر البيع (كميات + حذف أصناف)
     */
    public function update(Request $request, SalesOrder $salesOrder)
    {
        $request->validate([
            'selected_items'   => 'required|array|min:1',
            'selected_items.*' => 'exists:sales_order_items,id',
            'quantities'       => 'required|array',
            'quantities.*'     => 'numeric|min:0.001',
            'prices'           => 'required|array',
            'prices.*'         => 'numeric|min:0',
        ]);

        $selectedIds = $request->input('selected_items', []);
        $quantities  = $request->input('quantities', []);
        $prices      = $request->input('prices', []);

        DB::transaction(function () use ($salesOrder, $selectedIds, $quantities, $prices) {
            // حذف الأصناف اللي اتشالت
            $salesOrder->items()->whereNotIn('id', $selectedIds)->delete();

            // تحديث كميات وأسعار الأصناف المتبقية
            $items = $salesOrder->items()->whereIn('id', $selectedIds)->get();

            $subtotal = $lineDiscounts = $taxAmount = 0;

            foreach ($items as $item) {
                $qty        = (float) ($quantities[$item->id] ?? $item->quantity);
                $price      = (float) ($prices[$item->id]    ?? $item->list_price);
                $lineBase   = $qty * $price;
                $discVal    = $lineBase * $item->discount_percent / 100;
                $afterDisc  = $lineBase - $discVal;
                $taxVal     = $afterDisc * $item->tax_percent / 100;
                $netTotal   = round($afterDisc + $taxVal, 2);

                $item->update(['quantity' => $qty, 'list_price' => $price, 'net_total' => $netTotal]);

                $subtotal      += $lineBase;
                $lineDiscounts += $discVal;
                $taxAmount     += $taxVal;
            }

            $salesOrder->update([
                'subtotal'       => round($subtotal, 2),
                'total_discount' => round($lineDiscounts, 2),
                'tax_amount'     => round($taxAmount, 2),
                'grand_total'    => round($subtotal - $lineDiscounts + $taxAmount, 2),
            ]);
        });

        return redirect()->route('sales-orders.show', $salesOrder)
            ->with('success', app()->getLocale() === 'ar'
                ? 'تم تحديث أمر البيع بنجاح'
                : 'Sales order updated successfully');
    }

    /* ===================== Helpers ===================== */

    private function saveItems(SalesOrder $so, $items, array $quantities, array $prices = []): array
    {
        $subtotal = $lineDiscounts = $taxAmount = 0;

        foreach ($items as $line) {
            $qty       = (float) ($quantities[$line->id] ?? $line->quantity);
            $price     = (float) ($prices[$line->id] ?? $line->list_price);
            $lineBase  = $qty * $price;
            $discVal   = $lineBase * $line->discount_percent / 100;
            $afterDisc = $lineBase - $discVal;
            $taxVal    = $afterDisc * $line->tax_percent / 100;
            $netTotal  = round($afterDisc + $taxVal, 2);

            $so->items()->create([
                'item_id'          => $line->item_id,
                'item_code'        => $line->item_code,
                'description'      => $line->description,
                'quantity'         => $qty,
                'uom'              => $line->uom,
                'list_price'       => $price,
                'discount_percent' => $line->discount_percent,
                'tax_percent'      => $line->tax_percent,
                'net_total'        => $netTotal,
            ]);

            $subtotal      += $lineBase;
            $lineDiscounts += $discVal;
            $taxAmount     += $taxVal;
        }

        return [$subtotal, $lineDiscounts, $taxAmount];
    }

    /**
     * حفظ الأصناف الإضافية اللي مش موجودة في عرض السعر الأصلي
     */
    private function saveExtraLines(SalesOrder $so, array $extraLines): array
    {
        $subtotal = $lineDiscounts = $taxAmount = 0;

        foreach ($extraLines as $line) {
            if (empty($line['description'])) {
                continue;
            }

            $qty       = (float) $line['quantity'];
            $price     = (float) $line['list_price'];
            $discount  = (float) ($line['discount_percent'] ?? 0);
            $tax       = (float) ($line['tax_percent'] ?? 0);

            $lineBase  = $qty * $price;
            $discVal   = $lineBase * $discount / 100;
            $afterDisc = $lineBase - $discVal;
            $taxVal    = $afterDisc * $tax / 100;
            $netTotal  = round($afterDisc + $taxVal, 2);

            $item = !empty($line['item_id']) ? \App\Models\Item::find($line['item_id']) : null;

            $so->items()->create([
                'item_id'          => $line['item_id'] ?? null,
                'item_code'        => optional($item)->item_code,
                'description'      => $line['description'],
                'quantity'         => $qty,
                'uom'              => $line['uom'] ?? null,
                'list_price'       => $price,
                'discount_percent' => $discount,
                'tax_percent'      => $tax,
                'net_total'        => $netTotal,
            ]);

            $subtotal      += $lineBase;
            $lineDiscounts += $discVal;
            $taxAmount     += $taxVal;
        }

        return [$subtotal, $lineDiscounts, $taxAmount];
    }

    private function nextNumber(): string
    {
        $last = SalesOrder::latest('id')->lockForUpdate()->first();
        $seq  = $last ? $last->id + 1 : 1;
        return 'SO-' . now()->format('Y-m') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
