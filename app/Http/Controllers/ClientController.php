<?php

namespace App\Http\Controllers;
use App\Models\Setting;
use App\Models\PriceList;
use Illuminate\Support\Facades\Cache;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * عرض قائمة العملاء
     */
public function index(Request $request)
{
    // نبدأ استعلام جديد
    $query = Client::query();

    // 0. البحث الفوري (اسم الشركة، المسؤول، الهاتف، الإيميل، الدولة، الرقم الضريبي)
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('company_name', 'like', "%{$search}%")
              ->orWhere('contact_person', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('country', 'like', "%{$search}%")
              ->orWhere('tax_id', 'like', "%{$search}%");
        });
    }

    // 1. فلترة التاريخ (أمس، الأسبوع، السنة)
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

    // 2. فلترة بنطاق محدد (من - إلى)
    if ($request->filled('date_from') && $request->filled('date_to')) {
        $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
    } 
    // 3. فلترة بيوم محدد
    elseif ($request->filled('specific_date')) {
        $query->whereDate('created_at', $request->specific_date);
    }

    // جلب البيانات مع الاحتفاظ ببيانات الفلترة في روابط الصفحات (Pagination)
    $clients = $query->latest()->paginate(10)->withQueryString();

    return view('clients.index', compact('clients'));
}
    /**
     * عرض شاشة إضافة عميل جديد
     */
    public function create()
    {
        $lookups = Cache::remember('system_settings', 60*60*24, fn() => Setting::all()->groupBy('category'));
        $clientTypes = $lookups->get('client_type') ?? collect();
        $currencies   = $lookups->get('currency')    ?? collect();
        $priceLists   = PriceList::where('status', 'active')->orderBy('name')->get();

        return view('clients.create', compact('clientTypes', 'currencies', 'priceLists'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'company_name'          => 'required|string|max:255',
            'company_name_en'       => 'nullable|string|max:255',
            'contact_person'        => 'nullable|string|max:255',
            'phone'                 => 'required|string|max:20',
            'email'                 => 'nullable|email|max:255',
            'country'               => 'required|string|max:10',
            'tax_id'                => 'nullable|string|max:50',
            'client_type'           => 'required|string|max:50',
            'address'               => 'nullable|string',
            'default_price_list_id' => 'nullable|exists:price_lists,id',
            'default_sales_rep'     => 'nullable|string|max:255',
            'default_currency'      => 'nullable|string|max:10',
        ]);

        Client::create($validatedData);

        return redirect()->route('clients.index')->with('success', 'تم إضافة العميل بنجاح إلى النظام.');
    }

    public function edit(Client $client)
    {
        $lookups = Cache::remember('system_settings', 60*60*24, fn() => Setting::all()->groupBy('category'));
        $clientTypes = $lookups->get('client_type') ?? collect();
        $currencies   = $lookups->get('currency')    ?? collect();
        $priceLists   = PriceList::where('status', 'active')->orderBy('name')->get();

        return view('clients.edit', compact('client', 'clientTypes', 'currencies', 'priceLists'));
    }

    public function update(Request $request, Client $client)
    {
        $validatedData = $request->validate([
            'company_name'          => 'required|string|max:255',
            'company_name_en'       => 'nullable|string|max:255',
            'contact_person'        => 'nullable|string|max:255',
            'phone'                 => 'required|string|max:20',
            'email'                 => 'nullable|email|max:255',
            'country'               => 'required|string|max:10',
            'tax_id'                => 'nullable|string|max:50',
            'client_type'           => 'required|string|max:50',
            'address'               => 'nullable|string',
            'default_price_list_id' => 'nullable|exists:price_lists,id',
            'default_sales_rep'     => 'nullable|string|max:255',
            'default_currency'      => 'nullable|string|max:10',
        ]);

        $client->update($validatedData);

        return redirect()->route('clients.index')->with('success', 'تم تحديث بيانات العميل بنجاح.');
    }

    public function defaults(Client $client)
    {
        return response()->json([
            'default_price_list_id' => $client->default_price_list_id,
            'default_sales_rep'     => $client->default_sales_rep,
            'default_currency'      => $client->default_currency,
        ]);
    }


 public function show(Client $client)
    {
        return view('clients.show', compact('client'));
    }

    /**
     * صفحة عروض أسعار العميل — يفتحها الـ QR كود من عرض السعر
     */
    public function quotations(Client $client)
    {
        $quotations = $client->quotations()->latest()->paginate(15);

        $stats = [
            'count'    => $client->quotations()->count(),
            'total'    => $client->quotations()->sum('grand_total'),
            'approved' => $client->quotations()->where('status', 'approved')->count(),
            'sent'     => $client->quotations()->where('status', 'sent')->count(),
        ];

        return view('clients.quotations', compact('client', 'quotations', 'stats'));
    }
    /**
     * حذف العميل
     */
    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')
                         ->with('success', 'تم حذف العميل من النظام.');
    }
}