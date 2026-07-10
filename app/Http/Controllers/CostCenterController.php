<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    /**
     * كل عروض الأسعار كمراكز تكلفة، مع إيراد/تكلفة/ربح محسوبة لكل واحد
     */
    public function index()
    {
        $quotations = Quotation::query()
            ->withSum('receipts as revenue_sum', 'amount')
            ->withSum('expenses as expenses_sum', 'amount')
            ->withSum('purchaseInvoices as purchases_sum', 'grand_total')
            ->with('client')
            ->latest()
            ->paginate(15);

        $quotations->getCollection()->transform(function ($q) {
            $q->revenue = (float) $q->revenue_sum;
            $q->cost    = (float) $q->expenses_sum + (float) $q->purchases_sum;
            $q->profit  = $q->revenue - $q->cost;
            return $q;
        });

        return view('cost_centers.index', compact('quotations'));
    }

    /**
     * تفصيل مركز تكلفة واحد: المصروفات + فواتير الشراء + التحصيلات + الربح
     */
    public function show(Quotation $quotation)
    {
        $quotation->load(['client', 'expenses', 'purchaseInvoices', 'receipts', 'salesOrders']);

        $revenue = (float) $quotation->receipts->sum('amount');
        $cost    = (float) $quotation->expenses->sum('amount') + (float) $quotation->purchaseInvoices->sum('grand_total');
        $profit  = $revenue - $cost;

        return view('cost_centers.show', compact('quotation', 'revenue', 'cost', 'profit'));
    }

    /**
     * تعديل اسم مركز التكلفة بحرية بعد الإنشاء
     */
    public function update(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'cost_center_name' => 'required|string|max:255',
        ]);

        $quotation->update($data);

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم تحديث اسم مركز التكلفة' : 'Cost center name updated');
    }
}
