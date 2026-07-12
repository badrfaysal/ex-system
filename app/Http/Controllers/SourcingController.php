<?php
namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Http\Request;

class SourcingController extends Controller
{
    public function index()
    {
        // الشاشة بتحمّل الأصناف والموردين عبر بحث AJAX (searchItems/searchVendors)
        // بدل ما تحمّل الكتالوج كله دفعة واحدة — عشان تفضل سريعة مهما كبر عدد الأصناف/الموردين
        return view('sourcing.index');
    }

    /**
     * AJAX: بحث سريع في الأصناف (بالاسم أو الكود) — أول 20 نتيجة بس
     */
    public function searchItems(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $items = Item::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name_ar', 'like', "%{$q}%")
                      ->orWhere('item_code', 'like', "%{$q}%");
                });
            })
            ->orderBy('name_ar')
            ->limit(20)
            ->get(['id', 'name_ar', 'item_code']);

        return response()->json($items);
    }

    /**
     * AJAX: بحث سريع في الموردين (بالاسم أو الكود) — أول 20 نتيجة بس
     */
    public function searchVendors(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $vendors = Vendor::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name_ar', 'like', "%{$q}%")
                      ->orWhere('vendor_code', 'like', "%{$q}%");
                });
            })
            ->orderBy('name_ar')
            ->limit(20)
            ->get(['id', 'name_ar', 'vendor_code']);

        return response()->json($vendors);
    }

    /**
     * AJAX: تفاصيل صنف واحد + الموردين المعتمدين ليه (تُجلب فقط لما يتحدد الصنف)
     */
    public function itemDetail(Item $item)
    {
        $item->load('approvedVendors:id,name_ar,vendor_code,mobile,tax_id');

        return response()->json($item->only(['id', 'name_ar', 'item_code', 'barcode', 'reorder_point'])
            + ['approved_vendors' => $item->approvedVendors]);
    }

    /**
     * AJAX: تفاصيل مورد واحد + الأصناف المعتمد عليها (تُجلب فقط لما يتحدد المورد)
     */
    public function vendorDetail(Vendor $vendor)
    {
        $vendor->load('approvedItems:id,name_ar,item_code,barcode,reorder_point');

        return response()->json($vendor->only(['id', 'name_ar', 'vendor_code', 'mobile', 'tax_id'])
            + ['approved_items' => $vendor->approvedItems]);
    }

    public function attach(Request $request)
    {
        // دالة لربط مورد بصنف من نفس الشاشة
        $request->validate([
            'item_id' => 'required|exists:items,id',
            'vendor_id' => 'required|exists:vendors,id',
            'last_purchase_price' => 'nullable|numeric',
        ]);

        $item = Item::findOrFail($request->item_id);
        $item->approvedVendors()->syncWithoutDetaching([
            $request->vendor_id => [
                'last_purchase_price' => $request->last_purchase_price,
            ]
        ]);

        return back()->with('success', 'تم اعتماد المورد لتوريد هذا الصنف بنجاح.');
    }
}
