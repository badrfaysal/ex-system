<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;
use App\Models\Item;
class VendorController extends Controller
{
    /**
     * عرض قائمة الموردين
     */
public function index(Request $request)
{
    $query = \App\Models\Vendor::query();

    // 0. البحث الفوري (الكود، الاسم عربي/إنجليزي، الموبايل، الإيميل، الرقم الضريبي)
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('vendor_code', 'like', "%{$search}%")
              ->orWhere('name_ar', 'like', "%{$search}%")
              ->orWhere('name_en', 'like', "%{$search}%")
              ->orWhere('mobile', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('tax_id', 'like', "%{$search}%");
        });
    }

    // 1. الفلترة الزمنية السريعة (أمس، الأسبوع، السنة)
    if ($request->filled('date_filter')) {
        $filter = $request->date_filter;
        
        if ($filter == 'yesterday') {
            $query->whereDate('created_at', now()->subDay());
        } elseif ($filter == 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter == 'this_year') {
            $query->whereYear('created_at', now()->year);
        }
    }

    // 2. فلترة بنطاق زمني محدد (من - إلى)
    if ($request->filled('date_from') && $request->filled('date_to')) {
        $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
    } 
    // 3. فلترة بيوم محدد
    elseif ($request->filled('specific_date')) {
        $query->whereDate('created_at', $request->specific_date);
    }

    // جلب الموردين مع الاحتفاظ بالفلاتر أثناء التنقل بين الصفحات
    $vendors = $query->latest()->paginate(10)->withQueryString();

    return view('vendors.index', compact('vendors'));
}
    /**
     * عرض شاشة إضافة مورد جديد
     */
 public function create()
{
    // جلب كل الإعدادات من الكاش دفعة واحدة O(1)
    $lookups = Cache::remember('system_settings', 60*60*24, function () {
        return Setting::all()->groupBy('category');
    });

    $vendorGroups   = $lookups->get('vendor_group') ?? collect();
    $vendorStatuses = $lookups->get('vendor_status') ?? collect();
    $currencies     = $lookups->get('currency') ?? collect();
    $paymentMethods = $lookups->get('payment_method') ?? collect();
$items = Item::where('status', 'active')->select('id', 'name_ar', 'item_code')->get();
    return view('vendors.create', compact('vendorGroups', 'vendorStatuses', 'currencies', 'paymentMethods', 'items'));
}

    /**
     * استقبال وحفظ بيانات المورد
     */
    public function store(Request $request)
    {
        // 1. التحقق من صحة البيانات الأساسية
        $validatedData = $request->validate([
            'vendor_code'      => 'nullable|string|unique:vendors,vendor_code',
            'name_ar'          => 'required|string|max:255',
            'name_en'          => 'nullable|string|max:255',
            'legal_name'       => 'nullable|string|max:255',
            'vendor_group'     => 'required|string',
            'status'           => 'required|string',
            'block_reason'     => 'nullable|string',

            // الاتصال
            'phone'            => 'nullable|string',
            'mobile'           => 'required|string',
            'email'            => 'nullable|email',
            'website'          => 'nullable|string|max:255',
            
            // مسؤول الاتصال
            'contact_person_name'   => 'nullable|string',
            'contact_person_job'    => 'nullable|string',
            'contact_person_mobile' => 'nullable|string',
            'contact_person_email'  => 'nullable|email',
            
            // المالية والضرائب
            'default_currency'    => 'required|string',
            'tax_id'              => 'nullable|string',
            'commercial_registry' => 'nullable|string',
            'credit_limit'        => 'nullable|numeric',
            
            // الدفع والبنوك
            'payment_terms'  => 'nullable|string',
            'payment_method' => 'nullable|string',
            'bank_name'      => 'nullable|string',
            'bank_branch'    => 'nullable|string',
            'account_holder' => 'nullable|string',
            'account_number' => 'nullable|string',
            'iban'           => 'nullable|string',
            'swift_code'     => 'nullable|string',
            'lead_time_days'   => 'nullable|integer',
            'attachment'       => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'approved_items'   => 'nullable|array',
            'approved_items.*' => 'exists:items,id',
        ]);

        // كود تلقائي إذا تُرك فارغاً
        if (empty($validatedData['vendor_code'])) {
            $lastVendor = Vendor::latest('id')->first();
            $nextId = $lastVendor ? $lastVendor->id + 1 : 1;
            $validatedData['vendor_code'] = 'VND-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        }

        // رفع المرفق
        if ($request->hasFile('attachment')) {
            $validatedData['attachment_path'] = $request->file('attachment')->store('vendors/attachments', 'public');
        }

        $vendor = Vendor::create($validatedData);

        // ربط الأصناف المختارة بهذا المورد في جدول item_vendor
        if ($request->has('approved_items')) {
            $vendor->approvedItems()->attach($request->approved_items);
        }

        return redirect()->route('vendors.index')->with('success', 'تم حفظ المورد وربط الأصناف بنجاح');
    }

    /**
     * عرض شاشة تعديل بيانات المورد
     */
    public function edit(Vendor $vendor)
    {
        $items = Item::where('status', 'active')->select('id', 'name_ar', 'item_code')->get();
        $vendor->load('approvedItems');
        return view('vendors.edit', compact('vendor', 'items'));
    }

    /**
     * تحديث بيانات المورد
     */
    public function update(Request $request, Vendor $vendor)
    {
        $validatedData = $request->validate([
            'vendor_code'      => 'nullable|string|unique:vendors,vendor_code,' . $vendor->id,
            'name_ar'          => 'required|string|max:255',
            'name_en'          => 'nullable|string|max:255',
            'legal_name'       => 'nullable|string|max:255',
            'vendor_group'     => 'required|string',
            'status'           => 'required|in:active,on_hold,blocked',
            'block_reason'     => 'nullable|required_if:status,blocked|string',

            // الاتصال
            'phone'            => 'nullable|string',
            'mobile'           => 'required|string',
            'email'            => 'nullable|email',
            'website'          => 'nullable|url',

            // مسؤول الاتصال
            'contact_person_name'   => 'nullable|string',
            'contact_person_job'    => 'nullable|string',
            'contact_person_mobile' => 'nullable|string',
            'contact_person_email'  => 'nullable|email',

            // المالية والضرائب
            'default_currency'    => 'required|string',
            'tax_id'              => 'nullable|string',
            'commercial_registry' => 'nullable|string',
            'credit_limit'        => 'nullable|numeric',

            // الدفع والبنوك
            'payment_terms'  => 'nullable|string',
            'payment_method' => 'nullable|string',
            'bank_name'      => 'nullable|string',
            'bank_branch'    => 'nullable|string',
            'account_holder' => 'nullable|string',
            'account_number' => 'nullable|string',
            'iban'           => 'nullable|string',
            'swift_code'     => 'nullable|string',
            'lead_time_days'   => 'nullable|integer',
            'vendor_rating'    => 'nullable|in:A,B,C,D',
            'attachment'       => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'approved_items'   => 'nullable|array',
            'approved_items.*' => 'exists:items,id',
        ]);

        if (empty($validatedData['vendor_code'])) {
            $validatedData['vendor_code'] = 'VND-' . str_pad($vendor->id, 4, '0', STR_PAD_LEFT);
        }

        // رفع مرفق جديد (أو الاحتفاظ بالقديم)
        if ($request->hasFile('attachment')) {
            if ($vendor->attachment_path) {
                Storage::disk('public')->delete($vendor->attachment_path);
            }
            $validatedData['attachment_path'] = $request->file('attachment')->store('vendors/attachments', 'public');
        }

        $vendor->update($validatedData);

        // مزامنة الأصناف المعتمدة (sync يحذف القديم ويضيف الجديد)
        $vendor->approvedItems()->sync($request->input('approved_items', []));

        return redirect()->route('vendors.index')->with('success', 'تم تحديث بيانات المورد بنجاح');
    }

    /**
     * حذف المورد
     */
    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'تم حذف المورد من النظام');
    }
}