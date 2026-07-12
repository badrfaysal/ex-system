<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    /**
     * كل عروض الأسعار كمراكز تكلفة، مع إيراد/تكلفة/ربح محسوبة لكل واحد
     */
    public function index(Request $request)
    {
        $query = Quotation::query()
            ->withSum('receipts as revenue_sum', 'amount')
            ->withSum('expenses as expenses_sum', 'amount')
            ->withSum('purchaseInvoices as purchases_sum', 'grand_total')
            ->with('client');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($c) => $c->where('company_name', 'like', "%{$search}%"));
            });
        }

        $quotations = $query->latest()->paginate(15)->withQueryString();

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
            'cost_center_name' => 'required|string|max:255|unique:quotations,cost_center_name,' . $quotation->id,
        ], [
            'cost_center_name.unique' => app()->getLocale() === 'ar'
                ? 'اسم مركز التكلفة ده مستخدم قبل كده — اختر اسم تاني.'
                : 'This cost center name is already taken — choose another.',
        ]);

        $quotation->update($data);

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم تحديث اسم مركز التكلفة' : 'Cost center name updated');
    }
}
