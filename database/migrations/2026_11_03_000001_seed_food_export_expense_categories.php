<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. تنظيف أي بيانات بنكية تجريبية سابقة
        DB::table('settings')
            ->where('category', 'doc_bank_info')
            ->where(function ($q) {
                $q->where('key_value', 'like', '%FOODICO%')
                  ->orWhere('key_value', 'like', '%QNB%')
                  ->orWhere('key_value', 'like', '%EG680037070808402031980204271%');
            })
            ->update(['key_value' => '']);

        // 2. البنود التي لا تحتاجها شركة صغيرة (موانئ وطيران)
        $obsoleteKeys = [
            'sea_freight', 'air_freight', 'internal_transport', 'reefer_genset', 'temp_data_loggers',
            'cargo_insurance', 'port_handling_thc', 'demurrage_detention', 'shipping_line_fees',
            'port_reefer_power', 'weighbridge_scale', 'fsa_inspection', 'agricultural_quarantine',
            'health_certificates', 'certificate_of_origin', 'embassy_legalization', 'lab_analysis_pesticides',
            'fumigation_pest_control', 'sgs_inspection', 'packing_house_fees', 'cartons_boxes',
            'wrapping_strapping', 'labels_stickers', 'food_preservatives_wax', 'loading_unloading_labor',
            'sorting_packing_labor', 'forklift_equipment_rent', 'samples_dhl_courier', 'bank_lc_charges',
            'sales_broker_commission', 'exhibitions_marketing', 'travel_accommodation', 'office_rent_utilities',
            'hospitality_buffet', 'maintenance_spare_parts', 'gov_taxes_fees',
        ];

        // لا نحذف بنداً مرتبطاً بمصروف مسجل فعلياً
        $usedInExpenses = DB::table('expenses')->distinct()->pluck('category')->filter()->all();
        $toDelete = array_diff($obsoleteKeys, $usedInExpenses);

        if (!empty($toDelete)) {
            DB::table('settings')
                ->where('category', 'expense_category')
                ->whereIn('key_value', $toDelete)
                ->delete();
        }

        // 3. تعديل مسمى بند النقل لحذف كلمة نولون
        DB::table('settings')
            ->where('category', 'expense_category')
            ->where('key_value', 'land_freight')
            ->where('display_name', 'like', '%نولون%')
            ->update(['display_name' => 'نقل بري وشحن / سيارات مبردة']);

        // 4. بنود المصروفات الأساسية فقط
        $rows = [
            ['key_value' => 'land_freight',            'display_name' => 'نقل بري وشحن / سيارات مبردة'],
            ['key_value' => 'packaging_materials',     'display_name' => 'كرتون وعبوات ومواد تغليف'],
            ['key_value' => 'pallets',                 'display_name' => 'طبالي خشبية (Pallets)'],
            ['key_value' => 'certificates_inspection', 'display_name' => 'شهادات صحية وفحص وحجر زراعي'],
            ['key_value' => 'customs_clearance',       'display_name' => 'تخليص جمركي وأوراق ومستندات تصدير'],
            ['key_value' => 'labor_loading',           'display_name' => 'أجور عمالة وتعتيق وتحميل (عتالة)'],
            ['key_value' => 'cold_storage',            'display_name' => 'إيجار وتبريد ثلاجات ومخازن'],
            ['key_value' => 'bank_charges',            'display_name' => 'مصاريف ورسوم وتحويلات بنكية'],
            ['key_value' => 'salaries_wages',          'display_name' => 'رواتب وأجور وبدلات عمال وموظفين'],
            ['key_value' => 'office_buffet_expenses',  'display_name' => 'بوفيه وضيافة ومستلزمات مكتب'],
            ['key_value' => 'utilities',               'display_name' => 'فواتير مرافق (كهرباء ومياه وإنترنت)'],
            ['key_value' => 'maintenance_repairs',     'display_name' => 'صيانة سيارات ومعدات وإصلاحات'],
            ['key_value' => 'general_expenses',        'display_name' => 'مصروفات عمومية ونثرية أخرى'],
        ];

        $existingKeys = DB::table('settings')
            ->where('category', 'expense_category')
            ->pluck('key_value')
            ->all();

        $existingNames = DB::table('settings')
            ->where('category', 'expense_category')
            ->pluck('display_name')
            ->all();

        foreach ($rows as $row) {
            if (in_array($row['key_value'], $existingKeys, true) || in_array($row['display_name'], $existingNames, true)) {
                continue;
            }

            DB::table('settings')->insert([
                'category'     => 'expense_category',
                'key_value'    => $row['key_value'],
                'display_name' => $row['display_name'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        Cache::forget('system_settings');
        Cache::forever('food_export_expense_categories_v2', true);
    }

    public function down(): void
    {
        // Keep user settings intact on rollback
    }
};
