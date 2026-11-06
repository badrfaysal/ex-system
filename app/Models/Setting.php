<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'category',
        'display_name',
        'name_en',
        'key_value',
        'code',
        'parent_key',
        'classification',
        'nature',
        'allow_posting',
    ];

    protected $casts = [
        'allow_posting' => 'boolean',
    ];

    // مسح الكاش تلقائياً عند إضافة أو تعديل أي إعداد
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

    /* =========================================================================
     * علاقات شجرة الحسابات (Parent / Children)
     * ========================================================================= */

    public function parentAccount()
    {
        return $this->belongsTo(Setting::class, 'parent_key', 'key_value')
            ->where('category', 'expense_category');
    }

    public function childAccounts()
    {
        return $this->hasMany(Setting::class, 'parent_key', 'key_value')
            ->where('category', 'expense_category')
            ->orderBy('code')
            ->orderBy('key_value');
    }

    /* =========================================================================
     * Scopes & Accessors
     * ========================================================================= */

    public function scopeExpenseParents($query)
    {
        return $query->where('category', 'expense_category')
            ->where(function ($q) {
                $q->whereNull('parent_key')->orWhere('parent_key', '');
            })
            ->orderBy('code');
    }

    public function scopeExpenseItems($query)
    {
        return $query->where('category', 'expense_category')
            ->whereNotNull('parent_key')
            ->where('parent_key', '!=', '')
            ->orderBy('code');
    }

    public function getLocalizedNameAttribute(): string
    {
        if (app()->getLocale() === 'en' && !empty($this->name_en)) {
            return $this->name_en;
        }
        return $this->display_name ?: '';
    }

    public function getAccountCodeAttribute(): string
    {
        return $this->code ?: $this->key_value;
    }

    public function getFullDisplayAttribute(): string
    {
        $code = $this->account_code;
        $name = $this->localized_name;
        return $code ? "{$code} — {$name}" : $name;
    }

    public function isParent(): bool
    {
        return empty($this->parent_key);
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
     * الحسابات الرئيسية لدليل بنود المصروفات (Chart of Accounts Parents)
     */
    public const DEFAULT_EXPENSE_PARENTS = [
        [
            'code'           => '500000',
            'key_value'      => '500000',
            'display_name'   => 'تكلفة المبيعات والشحنات',
            'name_en'        => 'Cost of sales and shipments',
            'parent_key'     => null,
            'classification' => 'Cost of sales and shipments',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '600000',
            'key_value'      => '600000',
            'display_name'   => 'مصروفات التشغيل والإدارة',
            'name_en'        => 'Operating expenses',
            'parent_key'     => null,
            'classification' => 'Operating expenses',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '700000',
            'key_value'      => '700000',
            'display_name'   => 'نتائج التمويل والبنود الأخرى',
            'name_en'        => 'Finance and other results',
            'parent_key'     => null,
            'classification' => 'Finance and other results',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
    ];

    /**
     * بنود المصروفات الفرعية التابعة للحسابات الرئيسية
     */
    public const DEFAULT_EXPENSE_SUB_ITEMS = [
        // تحت 500000: تكلفة المبيعات والشحنات
        [
            'code'           => '510001',
            'key_value'      => 'land_freight',
            'display_name'   => 'نقل بري وشحن / سيارات مبردة',
            'name_en'        => 'Land freight and refrigerated transport',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales and shipments',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510002',
            'key_value'      => 'packaging_materials',
            'display_name'   => 'كرتون وعبوات ومواد تغليف',
            'name_en'        => 'Cartons, boxes and packaging materials',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales and shipments',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510003',
            'key_value'      => 'pallets',
            'display_name'   => 'طبالي خشبية (Pallets)',
            'name_en'        => 'Wooden and plastic pallets',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales and shipments',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510004',
            'key_value'      => 'certificates_inspection',
            'display_name'   => 'شهادات صحية وفحص وحجر زراعي',
            'name_en'        => 'Health certificates, quarantine & inspection',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales and shipments',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510005',
            'key_value'      => 'customs_clearance',
            'display_name'   => 'تخليص جمركي وأوراق ومستندات تصدير',
            'name_en'        => 'Customs clearance and export documentation',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales and shipments',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510006',
            'key_value'      => 'labor_loading',
            'display_name'   => 'أجور عمالة وتعتيق وتحميل (عتالة)',
            'name_en'        => 'Loading, unloading & handling labor',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales and shipments',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510007',
            'key_value'      => 'cold_storage',
            'display_name'   => 'إيجار وتبريد ثلاجات ومخازن',
            'name_en'        => 'Cold storage rent and reefer cooling',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales and shipments',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // تحت 600000: مصروفات التشغيل والإدارة
        [
            'code'           => '610001',
            'key_value'      => 'salaries_wages',
            'display_name'   => 'رواتب وأجور وبدلات عمال وموظفين',
            'name_en'        => 'Salaries, wages and employee allowances',
            'parent_key'     => '600000',
            'classification' => 'Operating expenses',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610002',
            'key_value'      => 'office_buffet_expenses',
            'display_name'   => 'بوفيه وضيافة ومستلزمات مكتب',
            'name_en'        => 'Buffet, hospitality and office supplies',
            'parent_key'     => '600000',
            'classification' => 'Operating expenses',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610003',
            'key_value'      => 'utilities',
            'display_name'   => 'فواتير مرافق (كهرباء ومياه وإنترنت)',
            'name_en'        => 'Utilities (electricity, water, internet)',
            'parent_key'     => '600000',
            'classification' => 'Operating expenses',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610004',
            'key_value'      => 'maintenance_repairs',
            'display_name'   => 'صيانة سيارات ومعدات وإصلاحات',
            'name_en'        => 'Vehicles & equipment maintenance & repairs',
            'parent_key'     => '600000',
            'classification' => 'Operating expenses',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610005',
            'key_value'      => 'general_expenses',
            'display_name'   => 'مصروفات عمومية ونثرية أخرى',
            'name_en'        => 'General and petty expenses',
            'parent_key'     => '600000',
            'classification' => 'Operating expenses',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // تحت 700000: نتائج التمويل والبنود الأخرى
        [
            'code'           => '710001',
            'key_value'      => 'bank_charges',
            'display_name'   => 'مصاريف ورسوم وتحويلات بنكية',
            'name_en'        => 'Bank charges and transfer commissions',
            'parent_key'     => '700000',
            'classification' => 'Finance and other results',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
    ];

    /**
     * التأكد من وجود وتحديث شجرة الحسابات لبنود المصروفات
     */
    public static function ensureExpenseCategoriesSeeded(): void
    {
        if (Cache::get('expense_chart_of_accounts_v1')) {
            return;
        }

        try {
            // 1. إضافة أو تحديث الحسابات الرئيسية (الآباء)
            foreach (self::DEFAULT_EXPENSE_PARENTS as $parent) {
                $rec = static::where('category', 'expense_category')
                    ->where(function ($q) use ($parent) {
                        $q->where('key_value', $parent['key_value'])
                          ->orWhere('code', $parent['code'])
                          ->orWhere('display_name', $parent['display_name']);
                    })
                    ->first();

                if ($rec) {
                    $rec->update([
                        'code'           => $parent['code'],
                        'display_name'   => $parent['display_name'],
                        'name_en'        => $parent['name_en'],
                        'parent_key'     => null,
                        'classification' => $parent['classification'],
                        'nature'         => $parent['nature'],
                        'allow_posting'  => $parent['allow_posting'],
                    ]);
                } else {
                    static::create([
                        'category'       => 'expense_category',
                        'key_value'      => $parent['key_value'],
                        'code'           => $parent['code'],
                        'display_name'   => $parent['display_name'],
                        'name_en'        => $parent['name_en'],
                        'parent_key'     => null,
                        'classification' => $parent['classification'],
                        'nature'         => $parent['nature'],
                        'allow_posting'  => $parent['allow_posting'],
                    ]);
                }
            }

            // 2. تحديث أو إضافة البنود الفرعية
            foreach (self::DEFAULT_EXPENSE_SUB_ITEMS as $item) {
                $rec = static::where('category', 'expense_category')
                    ->where(function ($q) use ($item) {
                        $q->where('key_value', $item['key_value'])
                          ->orWhere('code', $item['code'])
                          ->orWhere('display_name', $item['display_name']);
                    })
                    ->first();

                if ($rec) {
                    $rec->update([
                        'code'           => $rec->code ?: $item['code'],
                        'name_en'        => $rec->name_en ?: $item['name_en'],
                        'parent_key'     => $rec->parent_key ?: $item['parent_key'],
                        'classification' => $rec->classification ?: $item['classification'],
                        'nature'         => $rec->nature ?: $item['nature'],
                        'allow_posting'  => $item['allow_posting'],
                    ]);
                } else {
                    static::create([
                        'category'       => 'expense_category',
                        'key_value'      => $item['key_value'],
                        'code'           => $item['code'],
                        'display_name'   => $item['display_name'],
                        'name_en'        => $item['name_en'],
                        'parent_key'     => $item['parent_key'],
                        'classification' => $item['classification'],
                        'nature'         => $item['nature'],
                        'allow_posting'  => $item['allow_posting'],
                    ]);
                }
            }

            // 3. تسوية أي بنود قديمة كانت موجودة بدون كود أو حساب أب
            $orphans = static::where('category', 'expense_category')
                ->where(function ($q) {
                    $q->whereNull('parent_key')->orWhere('parent_key', '');
                })
                ->whereNotIn('key_value', ['500000', '600000', '700000'])
                ->whereNotIn('code', ['500000', '600000', '700000'])
                ->get();

            $autoCodeIndex = 10;
            foreach ($orphans as $orphan) {
                $parentKey = '600000'; // مصروفات التشغيل والإدارة كافتراضي
                $classification = 'Operating expenses';
                if (str_contains($orphan->display_name, 'نقل') || str_contains($orphan->display_name, 'شحن') || str_contains($orphan->display_name, 'جمارك')) {
                    $parentKey = '500000';
                    $classification = 'Cost of sales and shipments';
                }

                $orphan->update([
                    'code'           => $orphan->code ?: ('6100' . str_pad((string) $autoCodeIndex++, 2, '0', STR_PAD_LEFT)),
                    'parent_key'     => $parentKey,
                    'classification' => $orphan->classification ?: $classification,
                    'nature'         => $orphan->nature ?: 'Debit',
                    'allow_posting'  => true,
                ]);
            }

            Cache::forget('system_settings');
            Cache::forever('expense_chart_of_accounts_v1', true);
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
