<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['category', 'display_name', 'key_value', 'parent_key'];

    // هذه الدالة السحرية ستمسح الكاش تلقائياً عند إضافة أو تعديل أي إعداد
    protected static function booted()
    {
        static::saved(fn() => Cache::forget('system_settings'));
        static::deleted(fn() => Cache::forget('system_settings'));
    }

    /**
     * بنود المصروفات الأساسية لشركة شحن وتصدير مأكولات غذائية وحاصلات زراعية (شركة صغيرة)
     */
    public const DEFAULT_EXPENSE_CATEGORIES = [
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

    /**
     * بنود زائدة عن حاجة الشركات الصغيرة (موانئ، خطوط ملاحية، طيران) لتنظيفها تلقائياً
     */
    public const OBSOLETE_EXPENSE_KEYS = [
        'sea_freight',
        'air_freight',
        'internal_transport',
        'reefer_genset',
        'temp_data_loggers',
        'cargo_insurance',
        'port_handling_thc',
        'demurrage_detention',
        'shipping_line_fees',
        'port_reefer_power',
        'weighbridge_scale',
        'fsa_inspection',
        'agricultural_quarantine',
        'health_certificates',
        'certificate_of_origin',
        'embassy_legalization',
        'lab_analysis_pesticides',
        'fumigation_pest_control',
        'sgs_inspection',
        'packing_house_fees',
        'cartons_boxes',
        'wrapping_strapping',
        'labels_stickers',
        'food_preservatives_wax',
        'loading_unloading_labor',
        'sorting_packing_labor',
        'forklift_equipment_rent',
        'samples_dhl_courier',
        'bank_lc_charges',
        'sales_broker_commission',
        'exhibitions_marketing',
        'travel_accommodation',
        'office_rent_utilities',
        'hospitality_buffet',
        'maintenance_spare_parts',
        'gov_taxes_fees',
    ];

    /**
     * التأكد من وجود بنود المصروفات الأساسية وحذف البنود الزائدة غير المستخدمة
     */
    public static function ensureExpenseCategoriesSeeded(): void
    {
        if (Cache::get('food_export_expense_categories_v3')) {
            return;
        }

        try {
            // تنظيف بيانات البنك التجريبية القديمة إن وُجدت في جدول settings
            static::where('category', 'doc_bank_info')
                ->where(function ($q) {
                    $q->where('key_value', 'like', '%FOODICO%')
                      ->orWhere('key_value', 'like', '%QNB%')
                      ->orWhere('key_value', 'like', '%EG680037070808402031980204271%');
                })
                ->update(['key_value' => '']);

            // تعديل مسمى بند النقل لحذف كلمة نولون
            static::where('category', 'expense_category')
                ->where('key_value', 'land_freight')
                ->where('display_name', 'like', '%نولون%')
                ->update(['display_name' => 'نقل بري وشحن / سيارات مبردة']);

            // حذف بنود المصروفات الزائدة (موانئ وطيران) غير المستخدمة في أي مصروف فعلي
            $usedKeys = \App\Models\Expense::distinct()->pluck('category')->filter()->all();
            $keysToDelete = array_diff(self::OBSOLETE_EXPENSE_KEYS, $usedKeys);

            if (!empty($keysToDelete)) {
                static::where('category', 'expense_category')
                    ->whereIn('key_value', $keysToDelete)
                    ->delete();
            }

            // إضافة البنود الأساسية إن لم تكن مضافة
            $existingKeys = static::where('category', 'expense_category')->pluck('key_value')->all();
            $existingNames = static::where('category', 'expense_category')->pluck('display_name')->all();

            $inserted = false;
            foreach (self::DEFAULT_EXPENSE_CATEGORIES as $row) {
                if (in_array($row['key_value'], $existingKeys, true) || in_array($row['display_name'], $existingNames, true)) {
                    continue;
                }
                static::create([
                    'category'     => 'expense_category',
                    'key_value'    => $row['key_value'],
                    'display_name' => $row['display_name'],
                    'parent_key'   => null,
                ]);
                $inserted = true;
            }

            Cache::forget('system_settings');
            Cache::forever('food_export_expense_categories_v3', true);
        } catch (\Throwable $e) {
            // تجاهل أي خطأ أثناء التهيئة المبدئية
        }
    }

    /**
     * بيانات البنك وملاحظات المستندات الموحدة (عروض الأسعار، أوامر البيع، فواتير البيع)
     */
    public static function documentBankDetails(): array
    {
        $rows = static::where('category', 'doc_bank_info')->pluck('key_value', 'display_name');

        return [
            'note'             => $rows->get('note', ''),
            'beneficiary_name' => $rows->get('beneficiary_name', ''),
            'bank_name'        => $rows->get('bank_name', ''),
            'iban'             => $rows->get('iban', ''),
            'acc_usd'          => $rows->get('acc_usd', ''),
            'swift_code'       => $rows->get('swift_code', ''),
        ];
    }
}
