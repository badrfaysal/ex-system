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
        static::saved(function () {
            Cache::forget('system_settings');
            Cache::forget('other_services_list');
        });
        static::deleted(function () {
            Cache::forget('system_settings');
            Cache::forget('other_services_list');
        });
    }

    /**
     * الخدمات الأخرى الخاصة (شهادات، حراسة، تغليف، تجهيز) كبديل ديناميكي لبند الشهادات الثابت
     */
    public const DEFAULT_OTHER_SERVICES = [
        [
            'code'          => 'CERT',
            'name_ar'       => 'شهادات',
            'name_en'       => 'Certificates',
            'default_price' => null,
        ],
        [
            'code'          => 'SRV-GUARD',
            'name_ar'       => 'خدمة حراسة',
            'name_en'       => 'Guarding Service',
            'default_price' => null,
        ],
        [
            'code'          => 'SRV-PACK',
            'name_ar'       => 'خدمة تغليف وتجهيز',
            'name_en'       => 'Packaging & Handling Service',
            'default_price' => null,
        ],
    ];

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

    /**
     * التأكد من وجود الخدمات الخاصة الافتراضية (شهادات، حراسة، تغليف)
     */
    public static function ensureOtherServicesSeeded(): void
    {
        try {
            $hasAny = static::where('category', 'other_service')->exists();
            if (!$hasAny) {
                foreach (self::DEFAULT_OTHER_SERVICES as $srv) {
                    static::create([
                        'category'     => 'other_service',
                        'display_name' => $srv['name_ar'],
                        'key_value'    => $srv['code'],
                        'parent_key'   => json_encode([
                            'name_en'       => $srv['name_en'],
                            'default_price' => $srv['default_price'],
                        ], JSON_UNESCAPED_UNICODE),
                    ]);
                }
                Cache::forget('system_settings');
                Cache::forget('other_services_list');
            }
        } catch (\Throwable $e) {}
    }

    /**
     * جلب قائمة الخدمات الأخرى المُعرفة في النظام
     */
    public static function getOtherServices(): \Illuminate\Support\Collection
    {
        try {
            return Cache::remember('other_services_list', 3600, function () {
                self::ensureOtherServicesSeeded();

                $records = static::where('category', 'other_service')->orderBy('id')->get();
                if ($records->isEmpty()) {
                    return collect(self::DEFAULT_OTHER_SERVICES)->map(fn($s) => [
                        'id'            => null,
                        'code'          => $s['code'],
                        'name_ar'       => $s['name_ar'],
                        'name_en'       => $s['name_en'],
                        'name'          => app()->getLocale() === 'en' ? $s['name_en'] : $s['name_ar'],
                        'default_price' => $s['default_price'],
                    ]);
                }

                $locale = app()->getLocale();

                return $records->map(function ($rec) use ($locale) {
                    $meta = [];
                    if (!empty($rec->parent_key)) {
                        $decoded = json_decode($rec->parent_key, true);
                        if (is_array($decoded)) {
                            $meta = $decoded;
                        } else {
                            $meta['name_en'] = (string) $rec->parent_key;
                        }
                    }

                    $nameAr = $rec->display_name ?: '';
                    $nameEn = $meta['name_en'] ?? $nameAr;
                    $defaultPrice = $meta['default_price'] ?? null;

                    return [
                        'id'            => $rec->id,
                        'code'          => $rec->key_value ?: 'SRV-' . $rec->id,
                        'name_ar'       => $nameAr,
                        'name_en'       => $nameEn,
                        'name'          => ($locale === 'en' && !empty($nameEn)) ? $nameEn : $nameAr,
                        'default_price' => $defaultPrice,
                    ];
                });
            });
        } catch (\Throwable $e) {
            return collect(self::DEFAULT_OTHER_SERVICES)->map(fn($s) => [
                'id'            => null,
                'code'          => $s['code'],
                'name_ar'       => $s['name_ar'],
                'name_en'       => $s['name_en'],
                'name'          => app()->getLocale() === 'en' ? $s['name_en'] : $s['name_ar'],
                'default_price' => $s['default_price'],
            ]);
        }
    }

    /**
     * البحث عن خدمة أخرى بالكود أو الاسم لمعرفة ترجمتها الثنائية ونوعها
     */
    public static function findOtherService(?string $code, ?string $name = null): ?array
    {
        $codeClean = strtoupper(trim((string) $code));
        $nameClean = trim((string) $name);
        $nameLower = strtolower($nameClean);

        $services = static::getOtherServices();

        // 1. بحث بالكود المباشر
        if ($codeClean !== '') {
            $match = $services->first(fn($s) => strtoupper(trim((string) $s['code'])) === $codeClean);
            if ($match) {
                return $match;
            }
        }

        // 2. كود الشهادات التقليدي CERT أو كلمات شهادة/شهادات/certificate
        if ($codeClean === 'CERT' || str_contains($nameLower, 'certificate') || str_contains($nameClean, 'شهادات') || str_contains($nameClean, 'شهادة')) {
            $certMatch = $services->first(function ($s) {
                return strtoupper(trim((string) $s['code'])) === 'CERT'
                    || str_contains(strtolower($s['name_en']), 'cert')
                    || str_contains($s['name_ar'], 'شه');
            });
            if ($certMatch) {
                return $certMatch;
            }

            return [
                'id'            => null,
                'code'          => 'CERT',
                'name_ar'       => 'شهادات',
                'name_en'       => 'Certificates',
                'name'          => app()->getLocale() === 'en' ? 'Certificates' : 'شهادات',
                'default_price' => null,
            ];
        }

        // 3. مطابقة الاسم بالعربي أو بالإنجليزي
        if ($nameClean !== '') {
            $match = $services->first(function ($s) use ($nameClean, $nameLower) {
                $ar = trim((string) $s['name_ar']);
                $en = strtolower(trim((string) $s['name_en']));
                return ($ar !== '' && (str_contains($nameClean, $ar) || str_contains($ar, $nameClean)))
                    || ($en !== '' && (str_contains($nameLower, $en) || str_contains($en, $nameLower)));
            });
            if ($match) {
                return $match;
            }
        }

        return null;
    }

    /**
     * التحقق مما إذا كان الكود أو الاسم يمثل خدمة خاصة (شهادات أو غيرها)
     */
    public static function isOtherServiceCodeOrName(?string $code, ?string $name = null): bool
    {
        return static::findOtherService($code, $name) !== null;
    }
}
