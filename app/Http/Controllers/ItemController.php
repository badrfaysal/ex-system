<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemImage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Vendor;
class ItemController extends Controller
{
    public function index(Request $request)
    {
$query = \App\Models\Item::with(['defaultVendor', 'images']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_code', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('supplier_part_number', 'like', "%{$search}%");
            });
        }

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

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('specific_date')) {
            $query->whereDate('created_at', $request->specific_date);
        }

        $items = $query->latest()->paginate(10)->withQueryString();

        $idxLookups = Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });
        $settingGroups    = ($idxLookups->get('item_group')        ?? collect())->values();
        $settingUoms      = ($idxLookups->get('uom')               ?? collect())->values();
        $settingSubGroups = ($idxLookups->get('item_sub_category') ?? collect())->values();

        return view('items.index', compact('items', 'settingGroups', 'settingUoms', 'settingSubGroups'));
    }

   public function create()
    {
        $lookups = Cache::remember('system_settings', 60*60*24, function () {
            return Setting::all()->groupBy('category');
        });

        $itemGroups    = $lookups->get('item_group') ?? collect();
        $itemSubGroups = $lookups->get('item_sub_category') ?? collect();
        $uoms          = $lookups->get('uom') ?? collect();
        $itemStatuses  = $lookups->get('item_status') ?? collect();

        // Build sub-category map: expand JSON parent_keys so each sub appears under every parent it belongs to
        $subCatMap = $this->buildSubCatMap($itemSubGroups);

        $vendors = Vendor::select('id', 'name_ar', 'vendor_code')->get();

        return view('items.create', compact('itemGroups', 'itemSubGroups', 'uoms', 'itemStatuses', 'vendors', 'subCatMap'));
    }

public function store(Request $request)
    {
        $validatedData = $request->validate([
            // ... (نفس الفاليديشن السابق الخاص بالصنف) ...
            'item_code'            => 'nullable|string|unique:items,item_code',
            'barcode'              => 'nullable|string',
            'name_ar'              => 'required|string|max:255',
            'name_en'              => 'nullable|string|max:255',
            'item_group'           => 'nullable|string',
            'sub_category'         => 'nullable|string',
            'base_uom'             => 'required|string',
            'reorder_point'        => 'nullable|integer',
            'min_stock'            => 'nullable|integer',
            'max_stock'            => 'nullable|integer',
            'default_vendor_id'    => 'nullable|exists:vendors,id',
            'supplier_part_number' => 'nullable|string',
            'moq'                  => 'nullable|integer|min:1',
            'lead_time_days'       => 'nullable|integer',
            'status'               => 'required|string',
            
            'attachment'           => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'item_images.*'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'image_categories.*'   => 'nullable|string',
            'approved_vendors'     => 'nullable|array',
            'approved_vendors.*'   => 'exists:vendors,id',
        ]);

        if (empty($validatedData['item_code'])) {
            $lastItem = Item::latest('id')->first();
            $nextId = $lastItem ? $lastItem->id + 1 : 1;
            $validatedData['item_code'] = 'ITM-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
        }

        // 1. إنشاء الصنف الأساسي
        $item = Item::create($validatedData);

        // 2. رفع المرفق (كتالوج PDF / مواصفات)
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('items/attachments', 'public');
            $item->update(['attachment_path' => $path]);
        }

        // 3. معالجة الصور المتعددة وتصنيفاتها
        if ($request->hasFile('item_images')) {
            foreach ($request->file('item_images') as $index => $imageFile) {
                $path = $imageFile->store('items', 'public');
                $item->images()->create([
                    'image_path' => $path,
                    'category'   => $request->image_categories[$index] ?? 'other',
                ]);
            }
        }

        // 4. ربط الموردين المعتمدين بالصنف
        if ($request->has('approved_vendors')) {
            $item->approvedVendors()->attach($request->approved_vendors);
        }

        return redirect()->route('items.index')->with('success', 'تم حفظ الصنف مع الصور بنجاح');
    }
   public function edit(Item $item)
    {
        $item->load('images');

        $lookups = Cache::remember('system_settings', 60*60*24, function () {
            return Setting::all()->groupBy('category');
        });

        $itemGroups    = $lookups->get('item_group') ?? collect();
        $itemSubGroups = $lookups->get('item_sub_category') ?? collect();
        $uoms          = $lookups->get('uom') ?? collect();
        $itemStatuses  = $lookups->get('item_status') ?? collect();

        $subCatMap = $this->buildSubCatMap($itemSubGroups);

        $vendors = Vendor::select('id', 'name_ar', 'vendor_code')->get();

        return view('items.edit', compact('item', 'itemGroups', 'itemSubGroups', 'uoms', 'itemStatuses', 'vendors', 'subCatMap'));
    }

    public function update(Request $request, Item $item)
    {
        $validatedData = $request->validate([
            'item_code'            => 'nullable|string|unique:items,item_code,' . $item->id,
            'barcode'              => 'nullable|string',
            'name_ar'              => 'required|string|max:255',
            'name_en'              => 'nullable|string|max:255',
            'item_group'           => 'nullable|string',
            'sub_category'         => 'nullable|string',
            'base_uom'             => 'required|string',
            'reorder_point'        => 'nullable|integer',
            'min_stock'            => 'nullable|integer',
            'max_stock'            => 'nullable|integer',
            'default_vendor_id'    => 'nullable|exists:vendors,id',
            'supplier_part_number' => 'nullable|string',
            'moq'                  => 'nullable|integer|min:1',
            'lead_time_days'       => 'nullable|integer',
            'status'               => 'required|string',
            'approved_vendors'     => 'nullable|array',
            'approved_vendors.*'   => 'exists:vendors,id',
            'attachment'           => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'item_images.*'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'image_categories.*'   => 'nullable|string',
            'delete_images'        => 'nullable|array',
            'delete_images.*'      => 'exists:item_images,id',
        ]);

        if (empty($validatedData['item_code'])) {
            $validatedData['item_code'] = 'ITM-' . str_pad($item->id, 5, '0', STR_PAD_LEFT);
        }

        $item->update($validatedData);

        // تحديث/رفع المرفق
        if ($request->hasFile('attachment')) {
            if ($item->attachment_path) {
                Storage::disk('public')->delete($item->attachment_path);
            }
            $path = $request->file('attachment')->store('items/attachments', 'public');
            $item->update(['attachment_path' => $path]);
        }

        // Delete images marked for removal
        if ($request->filled('delete_images')) {
            $toDelete = ItemImage::whereIn('id', $request->delete_images)
                ->where('item_id', $item->id)->get();
            foreach ($toDelete as $img) {
                Storage::disk('public')->delete($img->image_path);
                $img->delete();
            }
        }

        // Upload new images
        if ($request->hasFile('item_images')) {
            foreach ($request->file('item_images') as $index => $imageFile) {
                $path = $imageFile->store('items', 'public');
                $item->images()->create([
                    'image_path' => $path,
                    'category'   => $request->image_categories[$index] ?? 'other',
                ]);
            }
        }

        $item->approvedVendors()->sync($request->input('approved_vendors', []));

        return redirect()->route('items.index')->with('success', 'تم تحديث بيانات الصنف بنجاح');
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()->route('items.index')->with('success', 'تم حذف الصنف من النظام');
    }

    /**
     * Build JS-ready sub-category map keyed by parent group.
     * parent_key may be a JSON array (new format) or a plain string (legacy).
     * Each sub-category appears under EVERY parent it belongs to.
     */
    private function buildSubCatMap($itemSubGroups): array
    {
        $map = [];
        foreach ($itemSubGroups as $sub) {
            $entry      = ['key' => $sub->key_value, 'label' => $sub->display_name];
            $parentKeys = json_decode($sub->parent_key, true);

            if (!is_array($parentKeys)) {
                // legacy: plain string or null
                $parentKeys = $sub->parent_key ? [$sub->parent_key] : [];
            }

            if (empty($parentKeys)) {
                // no parent → available for all groups
                $map[''][] = $entry;
            } else {
                foreach ($parentKeys as $pk) {
                    $map[$pk][] = $entry;
                }
            }
        }
        return $map;
    }
}