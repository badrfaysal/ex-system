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
        return view('settings.index', compact('settings', 'wallets'));
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

    /**
     * تصفير قاعدة البيانات بالكامل — مسح كل الجداول وإعادة تشغيل الميجريشنز من الصفر.
     * محمي بيئيًا: يعمل فقط على بيئة local، عشان محدش يعمله بالغلط على بيانات حقيقية.
     */
    public function resetDatabase()
    {
        abort_unless(app()->environment('local'), 403, 'تصفير قاعدة البيانات متاح فقط في بيئة التطوير المحلية.');

        Auth::logout();

        Artisan::call('migrate:fresh', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        return redirect()->route('login')
            ->with('success', 'تم تصفير قاعدة البيانات بالكامل. سجّل الدخول بحساب: test@example.com / password');
    }
}