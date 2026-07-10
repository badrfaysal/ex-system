<?php
namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Http\Request;

class SourcingController extends Controller
{
    public function index()
    {
        // جلب البيانات بالكامل لاستخدامها في الشاشة السريعة (SPA)
        $items = Item::with('approvedVendors')->where('status', 'active')->get();
        $vendors = Vendor::with('approvedItems')->where('status', 'active')->get();

        return view('sourcing.index', compact('items', 'vendors'));
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