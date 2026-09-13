<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index()
    {
        // جلب كل الإعدادات وتصنيفها حسب نوع القائمة (category)
        $settings = Setting::all()->groupBy('category');
        $wallets = \App\Models\Wallet::orderBy('name')->get();
        return view('settings.index_new', compact('settings', 'wallets'));
    }

    public function store(Request $request)
    {
        // ===== إيميلات إشعارات الإدارة =====
        if ($request->category === 'notify_email') {
            $request->validate([
                'email' => 'required|email|max:255',
                'name'  => 'nullable|string|max:255',
            ]);

            $email = strtolower(trim($request->email));

            // منع التكرار
            $exists = Setting::where('category', 'notify_email')
                ->where('key_value', $email)->exists();
            if ($exists) {
                return back()->with('error', 'هذا البريد مضاف بالفعل في قائمة الإشعارات.');
            }

            Setting::create([
                'category'     => 'notify_email',
                'display_name' => $request->name ?: $email,
                'key_value'    => $email,
                'parent_key'   => null,
            ]);

            return back()->with('success', 'تمت إضافة البريد إلى قائمة إشعارات الإدارة.');
        }

        if ($request->category === 'item_sub_category') {
            $request->validate([
                'category'        => 'required|string',
                'display_names'   => 'required|array|min:1',
                'display_names.*' => 'required|string|max:255',
                'parent_keys'     => 'nullable|array',
                'parent_keys.*'   => 'nullable|string|max:255',
            ]);

            $parentKeysJson = !empty($request->parent_keys)
                ? json_encode(array_values($request->parent_keys))
                : null;

            $count = 0;
            foreach ($request->display_names as $name) {
                $name = trim($name);
                if ($name === '') continue;
                Setting::create([
                    'category'     => 'item_sub_category',
                    'display_name' => $name,
                    'key_value'    => Str::slug($name, '_'),
                    'parent_key'   => $parentKeysJson,
                ]);
                $count++;
            }

            return back()->with('success', "تم إضافة {$count} مجموعة فرعية بنجاح");
        }

        $request->validate([
            'category'     => 'required|string',
            'display_name' => 'required|string|max:255',
            'key_value'    => 'nullable|string|max:255',
            'parent_key'   => 'nullable|string|max:255',
        ]);

        $keyValue = $request->key_value ?: Str::slug($request->display_name, '_');

        Setting::create([
            'category'     => $request->category,
            'display_name' => $request->display_name,
            'key_value'    => $keyValue,
            'parent_key'   => $request->parent_key ?: null,
        ]);

        \Illuminate\Support\Facades\Cache::forget('system_settings');

        return back()->with('success', 'تم إضافة العنصر للقائمة بنجاح');
    }

    public function destroy(Setting $setting)
    {
        $setting->delete();
        \Illuminate\Support\Facades\Cache::forget('system_settings');
        return back()->with('success', 'تم حذف العنصر من النظام');
    }

    public function resetDatabase(Request $request)
    {
        if ($request->input('confirm_text') !== 'تصفير') {
            return back()->with('error', 'كلمة التأكيد غير صحيحة، لم يتم تصفير النظام.');
        }

        try {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $tablesToTruncate = [
                'activity_logs', 'client_receipts', 'clients', 'contact_groups', 'contacts', 
                'document_counters', 'expenses', 'item_images', 'item_vendor', 'items', 
                'period_locks', 'price_list_items', 'price_lists', 'purchase_invoice_items', 
                'purchase_invoices', 'quotation_items', 'quotation_sends', 'quotations', 
                'revenues', 'sales_invoice_items', 'sales_invoices', 'sales_order_items', 
                'sales_orders', 'vendor_addresses', 'vendor_payments', 'vendors', 
                'wallet_transfers', 'wallets'
            ];

            foreach ($tablesToTruncate as $table) {
                \Illuminate\Support\Facades\DB::table($table)->truncate();
            }

            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return back()->with('success', 'تم تصفير جميع البيانات بنجاح، مع الاحتفاظ بالمستخدمين والإعدادات الأساسية.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return back()->with('error', 'حدث خطأ أثناء التصفير: ' . $e->getMessage());
        }
    }
}