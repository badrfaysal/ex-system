<?php

namespace App\Http\Controllers;

use App\Models\PriceList;
use App\Models\Item;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PriceListController extends Controller
{
    private function settingsLookup(): \Illuminate\Support\Collection
    {
        return Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });
    }

    /**
     * عرض قائمة بكل قوائم الأسعار
     */
    public function index(Request $request)
    {
        $query = PriceList::withCount('items');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $priceLists = $query->latest()->paginate(10)->withQueryString();

        return view('price_lists.index', compact('priceLists'));
    }

    /**
     * شاشة إنشاء قائمة أسعار جديدة
     */
    public function create()
    {
        $lookups    = $this->settingsLookup();
        $items      = Item::orderBy('name_ar')->get();
        $currencies = $lookups->get('currency') ?? collect();
        $uoms       = ($lookups->get('uom') ?? collect())->pluck('display_name', 'key_value');

        // كود تلقائي مقترح
        $last = PriceList::latest('id')->first();
        $nextCode = 'PL-' . str_pad(($last ? $last->id + 1 : 1), 3, '0', STR_PAD_LEFT);

        return view('price_lists.create', compact('items', 'currencies', 'uoms', 'nextCode'));
    }

    /**
     * حفظ قائمة أسعار جديدة
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($data, $request) {
            $priceList = PriceList::create([
                'code'             => $data['code'],
                'name'             => $data['name'],
                'default_currency' => $data['default_currency'],
                'valid_from'       => $data['valid_from'] ?? null,
                'valid_to'         => $data['valid_to'] ?? null,
                'status'           => $data['status'] ?? 'active',
                'notes'            => $data['notes'] ?? null,
            ]);

            $this->syncItems($priceList, $request);
        });

        return redirect()->route('price-lists.index')->with('success', 'تم حفظ قائمة الأسعار بنجاح');
    }

    /**
     * شاشة تعديل قائمة أسعار
     */
    public function edit(PriceList $priceList)
    {
        $priceList->load('items');
        $lookups    = $this->settingsLookup();
        $items      = Item::orderBy('name_ar')->get();
        $currencies = $lookups->get('currency') ?? collect();
        $uoms       = ($lookups->get('uom') ?? collect())->pluck('display_name', 'key_value');

        // خريطة الأسعار الحالية: item_id => price
        $existingPrices = $priceList->items->pluck('price', 'item_id');

        return view('price_lists.edit', compact('priceList', 'items', 'currencies', 'uoms', 'existingPrices'));
    }

    /**
     * تحديث قائمة أسعار
     */
    public function update(Request $request, PriceList $priceList)
    {
        $data = $this->validateData($request, $priceList->id);

        DB::transaction(function () use ($data, $request, $priceList) {
            $priceList->update([
                'code'             => $data['code'],
                'name'             => $data['name'],
                'default_currency' => $data['default_currency'],
                'valid_from'       => $data['valid_from'] ?? null,
                'valid_to'         => $data['valid_to'] ?? null,
                'status'           => $data['status'] ?? 'active',
                'notes'            => $data['notes'] ?? null,
            ]);

            // إعادة بناء الأصناف بالكامل
            $priceList->items()->delete();
            $this->syncItems($priceList, $request);
        });

        return redirect()->route('price-lists.index')->with('success', 'تم تحديث قائمة الأسعار بنجاح');
    }

    /**
     * بيانات قائمة الأسعار كاملة (AJAX للبوب أب)
     */
    public function data(PriceList $priceList)
    {
        $priceList->load('items.item');
        return response()->json([
            'id'               => $priceList->id,
            'code'             => $priceList->code,
            'name'             => $priceList->name,
            'default_currency' => $priceList->default_currency,
            'valid_from'       => optional($priceList->valid_from)->format('Y-m-d'),
            'valid_to'         => optional($priceList->valid_to)->format('Y-m-d'),
            'status'           => $priceList->status,
            'notes'            => $priceList->notes,
            'items'            => $priceList->items->map(fn($pli) => [
                'code'  => optional($pli->item)->item_code ?? '—',
                'name'  => optional($pli->item)->name_ar   ?? '—',
                'uom'   => optional($pli->item)->base_uom  ?? '—',
                'price' => number_format($pli->price, 2),
            ]),
        ]);
    }

    /**
     * حذف قائمة أسعار
     */
    public function destroy(PriceList $priceList)
    {
        $priceList->delete();
        return redirect()->route('price-lists.index')->with('success', 'تم حذف قائمة الأسعار');
    }

    /* ===================== Helpers ===================== */

    private function validateData(Request $request, $ignoreId = null)
    {
        return $request->validate([
            'code'             => 'required|string|unique:price_lists,code' . ($ignoreId ? ",$ignoreId" : ''),
            'name'             => 'required|string|max:255',
            'default_currency' => 'required|string',
            'valid_from'       => 'nullable|date',
            'valid_to'         => 'nullable|date|after_or_equal:valid_from',
            'status'           => 'nullable|string',
            'notes'            => 'nullable|string',
            'items'            => 'nullable|array',
            'items.*.item_id'  => 'required_with:items|exists:items,id',
            'items.*.price'    => 'nullable|numeric|min:0',
        ]);
    }

    /**
     * حفظ صفوف الأصناف القادمة من النموذج
     */
    private function syncItems(PriceList $priceList, Request $request)
    {
        $rows = $request->input('items', []);
        $seen = [];

        foreach ($rows as $row) {
            if (empty($row['item_id']) || in_array($row['item_id'], $seen)) {
                continue; // تجاهل الصفوف الفارغة أو المكررة
            }
            $seen[] = $row['item_id'];

            $priceList->items()->create([
                'item_id' => $row['item_id'],
                'price'   => $row['price'] ?? 0,
            ]);
        }
    }
}
