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
     * الدليل المحاسبي الكامل وشجرة الحسابات لشركات تصدير الحاصلات الزراعية والمواد الغذائية
     */
    public const FULL_CHART_OF_ACCOUNTS = [
        // =========================================================================
        // 100000: الأصول (Assets)
        // =========================================================================
        [
            'code'           => '100000',
            'key_value'      => '100000',
            'display_name'   => 'الأصول',
            'name_en'        => 'Assets',
            'parent_key'     => null,
            'classification' => 'Assets',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        // 110000: النقدية وما في حكمها
        [
            'code'           => '110000',
            'key_value'      => '110000',
            'display_name'   => 'النقدية وما في حكمها',
            'name_en'        => 'Cash and cash equivalents',
            'parent_key'     => '100000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '110101',
            'key_value'      => '110101',
            'display_name'   => 'خزينة بالجنيه المصري',
            'name_en'        => 'Cash EGP',
            'parent_key'     => '110000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '110102',
            'key_value'      => '110102',
            'display_name'   => 'خزينة عملات أجنبية',
            'name_en'        => 'Cash foreign currencies',
            'parent_key'     => '110000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '110103',
            'key_value'      => '110103',
            'display_name'   => 'عهد نقدية مؤقتة ومستديمة',
            'name_en'        => 'Petty cash and imprests',
            'parent_key'     => '110000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '110201',
            'key_value'      => '110201',
            'display_name'   => 'بنك حساب جاري - جنيه',
            'name_en'        => 'Bank EGP',
            'parent_key'     => '110000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '110202',
            'key_value'      => '110202',
            'display_name'   => 'بنك حساب جاري - دولار',
            'name_en'        => 'Bank USD',
            'parent_key'     => '110000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '110203',
            'key_value'      => '110203',
            'display_name'   => 'بنك حساب جاري - يورو',
            'name_en'        => 'Bank EUR',
            'parent_key'     => '110000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '110204',
            'key_value'      => '110204',
            'display_name'   => 'ودائع قصيرة الأجل مؤهلة كنقدية',
            'name_en'        => 'Qualifying short-term deposits',
            'parent_key'     => '110000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // 120000: العملاء والأرصدة المدينة
        [
            'code'           => '120000',
            'key_value'      => '120000',
            'display_name'   => 'العملاء والأرصدة المدينة',
            'name_en'        => 'Trade and other receivables',
            'parent_key'     => '100000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '120101',
            'key_value'      => '120101',
            'display_name'   => 'عملاء تصدير - حساب تحكم',
            'name_en'        => 'Export customers control',
            'parent_key'     => '120000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '120102',
            'key_value'      => '120102',
            'display_name'   => 'مستندات تصدير لدى البنك للتحصيل',
            'name_en'        => 'Export documents under collection',
            'parent_key'     => '120000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '120103',
            'key_value'      => '120103',
            'display_name'   => 'مبالغ محجوزة لدى العملاء',
            'name_en'        => 'Customer retentions',
            'parent_key'     => '120000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '120109',
            'key_value'      => '120109',
            'display_name'   => 'مخصص الخسائر الائتمانية المتوقعة (-)',
            'name_en'        => 'Expected credit loss allowance',
            'parent_key'     => '120000',
            'classification' => 'Asset',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '120201',
            'key_value'      => '120201',
            'display_name'   => 'ذمم مدينة أخرى',
            'name_en'        => 'Other receivables',
            'parent_key'     => '120000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '120202',
            'key_value'      => '120202',
            'display_name'   => 'دفعات مقدمة للموردين والمزارعين',
            'name_en'        => 'Advances to suppliers and growers',
            'parent_key'     => '120000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '120203',
            'key_value'      => '120203',
            'display_name'   => 'تأمينات مستردة لدى الغير',
            'name_en'        => 'Refundable deposits and guarantees',
            'parent_key'     => '120000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '120204',
            'key_value'      => '120204',
            'display_name'   => 'مساندة تصديرية مستحقة',
            'name_en'        => 'Export subsidies receivable',
            'parent_key'     => '120000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // 130000: المخزون ومهمات التعبئة
        [
            'code'           => '130000',
            'key_value'      => '130000',
            'display_name'   => 'المخزون ومهمات التعبئة',
            'name_en'        => 'Inventory and packaging supplies',
            'parent_key'     => '100000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '130101',
            'key_value'      => '130101',
            'display_name'   => 'مخزون محاصيل زراعية معدة للتصدير',
            'name_en'        => 'Export agricultural produce inventory',
            'parent_key'     => '130000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '130102',
            'key_value'      => '130102',
            'display_name'   => 'مخزون كرتون ومواد تغليف ومطبوعات',
            'name_en'        => 'Packaging materials and cartons inventory',
            'parent_key'     => '130000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '130103',
            'key_value'      => '130103',
            'display_name'   => 'مخزون طبالي ولوازم شحن',
            'name_en'        => 'Pallets and shipping supplies inventory',
            'parent_key'     => '130000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // 160000: الأصول الثابتة والمعدات
        [
            'code'           => '160000',
            'key_value'      => '160000',
            'display_name'   => 'الأصول الثابتة والمعدات',
            'name_en'        => 'Fixed assets and equipment',
            'parent_key'     => '100000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '160101',
            'key_value'      => '160101',
            'display_name'   => 'أجهزة ومعدات مكتبية',
            'name_en'        => 'Office equipment',
            'parent_key'     => '160000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '160102',
            'key_value'      => '160102',
            'display_name'   => 'سيارات النقل المملوكة',
            'name_en'        => 'Owned vehicles',
            'parent_key'     => '160000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '160103',
            'key_value'      => '160103',
            'display_name'   => 'أجهزة الحاسب والأنظمة',
            'name_en'        => 'IT equipment and systems',
            'parent_key'     => '160000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '160104',
            'key_value'      => '160104',
            'display_name'   => 'أثاث وتجهيزات',
            'name_en'        => 'Furniture and fittings',
            'parent_key'     => '160000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '160105',
            'key_value'      => '160105',
            'display_name'   => 'معدات وزن وفحص مملوكة',
            'name_en'        => 'Owned weighing/testing equipment',
            'parent_key'     => '160000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '160106',
            'key_value'      => '160106',
            'display_name'   => 'أصول حق الاستخدام - عند الانطباق',
            'name_en'        => 'Right-of-use assets where applicable',
            'parent_key'     => '160000',
            'classification' => 'Asset',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '160190',
            'key_value'      => '160190',
            'display_name'   => 'مجمع الإهلاك والإطفاء (-)',
            'name_en'        => 'Accumulated depreciation/amortization',
            'parent_key'     => '160000',
            'classification' => 'Asset',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],

        // =========================================================================
        // 200000: الالتزامات (Liabilities)
        // =========================================================================
        [
            'code'           => '200000',
            'key_value'      => '200000',
            'display_name'   => 'الالتزامات',
            'name_en'        => 'Liabilities',
            'parent_key'     => null,
            'classification' => 'Liabilities',
            'nature'         => 'Credit',
            'allow_posting'  => false,
        ],
        // 210000: الموردون والدائنون التجاريون
        [
            'code'           => '210000',
            'key_value'      => '210000',
            'display_name'   => 'الموردون والدائنون التجاريون',
            'name_en'        => 'Trade payables',
            'parent_key'     => '200000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '210101',
            'key_value'      => '210101',
            'display_name'   => 'موردو محاصيل ومزارعون - تحكم',
            'name_en'        => 'Produce suppliers control',
            'parent_key'     => '210000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '210102',
            'key_value'      => '210102',
            'display_name'   => 'محطات فرز وتعبئة خارجية',
            'name_en'        => 'Outsourced packhouses payable',
            'parent_key'     => '210000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '210103',
            'key_value'      => '210103',
            'display_name'   => 'موردو مواد التعبئة',
            'name_en'        => 'Packaging suppliers payable',
            'parent_key'     => '210000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '210104',
            'key_value'      => '210104',
            'display_name'   => 'موردو النقل الداخلي',
            'name_en'        => 'Inland transport payable',
            'parent_key'     => '210000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '210105',
            'key_value'      => '210105',
            'display_name'   => 'شركات الشحن البحري والجوي',
            'name_en'        => 'Ocean/air freight payable',
            'parent_key'     => '210000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '210106',
            'key_value'      => '210106',
            'display_name'   => 'التخليص والموانئ والفحص',
            'name_en'        => 'Customs brokers, ports and inspection',
            'parent_key'     => '210000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '210107',
            'key_value'      => '210107',
            'display_name'   => 'فواتير مستلمة غير مسجلة / استحقاقات توريد',
            'name_en'        => 'Goods/services received not invoiced',
            'parent_key'     => '210000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],

        // 220000: دفعات العملاء والالتزامات التعاقدية
        [
            'code'           => '220000',
            'key_value'      => '220000',
            'display_name'   => 'دفعات العملاء والالتزامات التعاقدية',
            'name_en'        => 'Customer advances and contract liabilities',
            'parent_key'     => '200000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '220101',
            'key_value'      => '220101',
            'display_name'   => 'دفعات مقدمة من عملاء التصدير',
            'name_en'        => 'Export customer advances',
            'parent_key'     => '220000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '220102',
            'key_value'      => '220102',
            'display_name'   => 'التزامات تعويض / خصومات جودة متوقعة',
            'name_en'        => 'Expected quality claims/refund liabilities',
            'parent_key'     => '220000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],

        // 230000: ضرائب وتأمينات مستحقة
        [
            'code'           => '230000',
            'key_value'      => '230000',
            'display_name'   => 'ضرائب وتأمينات مستحقة',
            'name_en'        => 'Taxes and statutory payables',
            'parent_key'     => '200000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '230101',
            'key_value'      => '230101',
            'display_name'   => 'ضريبة قيمة مضافة مستحقة',
            'name_en'        => 'Output VAT payable',
            'parent_key'     => '230000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '230102',
            'key_value'      => '230102',
            'display_name'   => 'ضريبة مرتبات مستحقة',
            'name_en'        => 'Payroll tax payable',
            'parent_key'     => '230000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '230103',
            'key_value'      => '230103',
            'display_name'   => 'خصم وتحصيل تحت حساب الضريبة',
            'name_en'        => 'Withholding tax payable',
            'parent_key'     => '230000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '230104',
            'key_value'      => '230104',
            'display_name'   => 'هيئة التأمينات الاجتماعية مستحقة',
            'name_en'        => 'Social insurance payable',
            'parent_key'     => '230000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '230105',
            'key_value'      => '230105',
            'display_name'   => 'ضريبة الدخل والأرباح التجارية مستحقة',
            'name_en'        => 'Corporate income tax payable',
            'parent_key'     => '230000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],

        // 240000: تسهيلات وقروض بنكية
        [
            'code'           => '240000',
            'key_value'      => '240000',
            'display_name'   => 'تسهيلات وقروض بنكية',
            'name_en'        => 'Bank facilities and loans',
            'parent_key'     => '200000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '240101',
            'key_value'      => '240101',
            'display_name'   => 'سحب على المكشوف / تسهيلات جارية',
            'name_en'        => 'Bank overdraft facilities',
            'parent_key'     => '240000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '240102',
            'key_value'      => '240102',
            'display_name'   => 'تمويل شحنات تصدير قصيرة الأجل',
            'name_en'        => 'Export pre-shipment financing',
            'parent_key'     => '240000',
            'classification' => 'Liability',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],

        // =========================================================================
        // 300000: حقوق الملكية (Equity)
        // =========================================================================
        [
            'code'           => '300000',
            'key_value'      => '300000',
            'display_name'   => 'حقوق الملكية',
            'name_en'        => 'Equity',
            'parent_key'     => null,
            'classification' => 'Equity',
            'nature'         => 'Credit',
            'allow_posting'  => false,
        ],
        // 310000: رأس المال والاحتياطيات
        [
            'code'           => '310000',
            'key_value'      => '310000',
            'display_name'   => 'رأس المال والاحتياطيات',
            'name_en'        => 'Capital and reserves',
            'parent_key'     => '300000',
            'classification' => 'Equity',
            'nature'         => 'Credit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '310101',
            'key_value'      => '310101',
            'display_name'   => 'رأس المال المدفوع',
            'name_en'        => 'Paid-in capital',
            'parent_key'     => '310000',
            'classification' => 'Equity',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '310102',
            'key_value'      => '310102',
            'display_name'   => 'الاحتياطي القانوني - عند الانطباق',
            'name_en'        => 'Statutory reserve if applicable',
            'parent_key'     => '310000',
            'classification' => 'Equity',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '310103',
            'key_value'      => '310103',
            'display_name'   => 'احتياطيات أخرى',
            'name_en'        => 'Other reserves',
            'parent_key'     => '310000',
            'classification' => 'Equity',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '310104',
            'key_value'      => '310104',
            'display_name'   => 'أرباح وخسائر مرحلة',
            'name_en'        => 'Retained earnings',
            'parent_key'     => '310000',
            'classification' => 'Equity',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '310105',
            'key_value'      => '310105',
            'display_name'   => 'نتيجة العام الجاري',
            'name_en'        => 'Current-year result',
            'parent_key'     => '310000',
            'classification' => 'Equity',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '310106',
            'key_value'      => '310106',
            'display_name'   => 'حسابات جارية للشركاء - عند الانطباق',
            'name_en'        => 'Partner current accounts if applicable',
            'parent_key'     => '310000',
            'classification' => 'Equity',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '310107',
            'key_value'      => '310107',
            'display_name'   => 'توزيعات أرباح تحت التسوية',
            'name_en'        => 'Dividends under settlement',
            'parent_key'     => '310000',
            'classification' => 'Equity',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],

        // =========================================================================
        // 400000: الإيرادات (Revenue)
        // =========================================================================
        [
            'code'           => '400000',
            'key_value'      => '400000',
            'display_name'   => 'الإيرادات',
            'name_en'        => 'Revenue',
            'parent_key'     => null,
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => false,
        ],
        // 410000: مبيعات التصدير
        [
            'code'           => '410000',
            'key_value'      => '410000',
            'display_name'   => 'مبيعات التصدير',
            'name_en'        => 'Export sales',
            'parent_key'     => '400000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '410101',
            'key_value'      => '410101',
            'display_name'   => 'صادرات موالح',
            'name_en'        => 'Citrus exports',
            'parent_key'     => '410000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '410102',
            'key_value'      => '410102',
            'display_name'   => 'صادرات عنب',
            'name_en'        => 'Grape exports',
            'parent_key'     => '410000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '410103',
            'key_value'      => '410103',
            'display_name'   => 'صادرات رمان',
            'name_en'        => 'Pomegranate exports',
            'parent_key'     => '410000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '410104',
            'key_value'      => '410104',
            'display_name'   => 'صادرات مانجو',
            'name_en'        => 'Mango exports',
            'parent_key'     => '410000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '410105',
            'key_value'      => '410105',
            'display_name'   => 'صادرات فراولة',
            'name_en'        => 'Strawberry exports',
            'parent_key'     => '410000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '410106',
            'key_value'      => '410106',
            'display_name'   => 'صادرات بطاطس',
            'name_en'        => 'Potato exports',
            'parent_key'     => '410000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '410107',
            'key_value'      => '410107',
            'display_name'   => 'صادرات بصل',
            'name_en'        => 'Onion exports',
            'parent_key'     => '410000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '410108',
            'key_value'      => '410108',
            'display_name'   => 'صادرات خضروات أخرى',
            'name_en'        => 'Other vegetable exports',
            'parent_key'     => '410000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '410109',
            'key_value'      => '410109',
            'display_name'   => 'صادرات فواكه أخرى',
            'name_en'        => 'Other fruit exports',
            'parent_key'     => '410000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '410110',
            'key_value'      => '410110',
            'display_name'   => 'صادرات تمور وحاصلات مجففة',
            'name_en'        => 'Dates and dried produce exports',
            'parent_key'     => '410000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],

        // 420000: تسويات الإيرادات
        [
            'code'           => '420000',
            'key_value'      => '420000',
            'display_name'   => 'تسويات الإيرادات',
            'name_en'        => 'Revenue adjustments',
            'parent_key'     => '400000',
            'classification' => 'Contra revenue',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '420101',
            'key_value'      => '420101',
            'display_name'   => 'خصومات جودة وتسويات أسعار',
            'name_en'        => 'Quality allowances / price adjustments',
            'parent_key'     => '420000',
            'classification' => 'Contra revenue',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '420102',
            'key_value'      => '420102',
            'display_name'   => 'مرتجعات تصدير',
            'name_en'        => 'Export sales returns',
            'parent_key'     => '420000',
            'classification' => 'Contra revenue',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '420103',
            'key_value'      => '420103',
            'display_name'   => 'خصومات تجارية',
            'name_en'        => 'Trade discounts',
            'parent_key'     => '420000',
            'classification' => 'Contra revenue',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // 430000: إيرادات تشغيلية وأخرى
        [
            'code'           => '430000',
            'key_value'      => '430000',
            'display_name'   => 'إيرادات تشغيلية وأخرى',
            'name_en'        => 'Other operating revenues',
            'parent_key'     => '400000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '430101',
            'key_value'      => '430101',
            'display_name'   => 'دعم ومساندة تنمية الصادرات',
            'name_en'        => 'Export subsidy revenue',
            'parent_key'     => '430000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '430102',
            'key_value'      => '430102',
            'display_name'   => 'إيرادات تقديم خدمات فرز وتعبئة للغير',
            'name_en'        => 'Outsourced packhouse service revenue',
            'parent_key'     => '430000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '430103',
            'key_value'      => '430103',
            'display_name'   => 'إيرادات تشغيلية متنوعة',
            'name_en'        => 'Miscellaneous operating revenue',
            'parent_key'     => '430000',
            'classification' => 'Revenue',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],

        // =========================================================================
        // 500000: تكلفة المبيعات والشحنات (Cost of sales and shipments)
        // =========================================================================
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
        // 510000: تكلفة المحاصيل المباعة
        [
            'code'           => '510000',
            'key_value'      => '510000',
            'display_name'   => 'تكلفة المحاصيل المباعة',
            'name_en'        => 'Cost of produce sold',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '510101',
            'key_value'      => '510101',
            'display_name'   => 'تكلفة موالح مباعة',
            'name_en'        => 'Citrus cost of sales',
            'parent_key'     => '510000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510102',
            'key_value'      => '510102',
            'display_name'   => 'تكلفة عنب مباع',
            'name_en'        => 'Grape cost of sales',
            'parent_key'     => '510000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510103',
            'key_value'      => '510103',
            'display_name'   => 'تكلفة رمان مباع',
            'name_en'        => 'Pomegranate cost of sales',
            'parent_key'     => '510000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510104',
            'key_value'      => '510104',
            'display_name'   => 'تكلفة خضروات مباعة',
            'name_en'        => 'Vegetables cost of sales',
            'parent_key'     => '510000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510105',
            'key_value'      => '510105',
            'display_name'   => 'تكلفة فواكه أخرى مباعة',
            'name_en'        => 'Other fruit cost of sales',
            'parent_key'     => '510000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510106',
            'key_value'      => '510106',
            'display_name'   => 'تكلفة مانجو وفراولة مباعة',
            'name_en'        => 'Mango & strawberry cost of sales',
            'parent_key'     => '510000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '510107',
            'key_value'      => '510107',
            'display_name'   => 'تكلفة بطاطس وبصل مباع',
            'name_en'        => 'Potato & onion cost of sales',
            'parent_key'     => '510000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // 520000: تكاليف تجهيز وتعبئة الشحنات
        [
            'code'           => '520000',
            'key_value'      => '520000',
            'display_name'   => 'تكاليف تجهيز وتعبئة الشحنات',
            'name_en'        => 'Packhouse & packing costs',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '520101',
            'key_value'      => 'packaging_materials',
            'display_name'   => 'كرتون وعبوات ومواد تغليف',
            'name_en'        => 'Cartons & packaging',
            'parent_key'     => '520000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '520102',
            'key_value'      => 'pallets',
            'display_name'   => 'طبالي خشبية',
            'name_en'        => 'Pallets',
            'parent_key'     => '520000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '520103',
            'key_value'      => 'packing_labor',
            'display_name'   => 'أجور فرز وتعبئة وتجهيز',
            'name_en'        => 'Sorting & packing labor',
            'parent_key'     => '520000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '520104',
            'key_value'      => 'cold_storage',
            'display_name'   => 'إيجار وتبريد ثلاجات ومخازن',
            'name_en'        => 'Cold storage rental',
            'parent_key'     => '520000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '520105',
            'key_value'      => '520105',
            'display_name'   => 'أحزمة وزوايا ومستلزمات تحزيم',
            'name_en'        => 'Strapping, corner boards & wrapping',
            'parent_key'     => '520000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // 530000: تكاليف النقل والشحن والتخليص
        [
            'code'           => '530000',
            'key_value'      => '530000',
            'display_name'   => 'تكاليف النقل والشحن والتخليص',
            'name_en'        => 'Logistics & clearance costs',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '530101',
            'key_value'      => 'land_freight',
            'display_name'   => 'نقل بري وشحن / سيارات مبردة',
            'name_en'        => 'Land freight',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '530102',
            'key_value'      => '530102',
            'display_name'   => 'نولون شحن بحري وجوي',
            'name_en'        => 'Ocean & air freight',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '530103',
            'key_value'      => 'customs_clearance',
            'display_name'   => 'تخليص جمركي وأوراق ومستندات تصدير',
            'name_en'        => 'Customs clearance',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '530104',
            'key_value'      => 'certificates_inspection',
            'display_name'   => 'شهادات صحية وفحص وحجر زراعي',
            'name_en'        => 'Inspection & health certs',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '530105',
            'key_value'      => 'labor_loading',
            'display_name'   => 'عتالة وتحميل وتعتيق',
            'name_en'        => 'Loading & unloading',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '530106',
            'key_value'      => '530106',
            'display_name'   => 'تأمين بحري وجوي على الشحنات',
            'name_en'        => 'Marine & cargo insurance',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '530107',
            'key_value'      => '530107',
            'display_name'   => 'غرامات أرضيات وتأخير حاويات',
            'name_en'        => 'Demurrage & container detention',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // 540000: تسويات تكاليف الشحنات والتوالف
        [
            'code'           => '540000',
            'key_value'      => '540000',
            'display_name'   => 'تسويات تكاليف الشحنات والتوالف',
            'name_en'        => 'Shipment adjustments & damages',
            'parent_key'     => '500000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '540101',
            'key_value'      => '540101',
            'display_name'   => 'هالك وتوالف فرز وتعبئة',
            'name_en'        => 'Packhouse sorting cull & scrap',
            'parent_key'     => '540000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '540104',
            'key_value'      => '540104',
            'display_name'   => 'تالف وشحنات مرفوضة',
            'name_en'        => 'Rejected/damaged export shipments',
            'parent_key'     => '540000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '540105',
            'key_value'      => '540105',
            'display_name'   => 'تسويات تكلفة شحنات سابقة',
            'name_en'        => 'Prior shipment cost adjustments',
            'parent_key'     => '540000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // =========================================================================
        // 600000: مصروفات التشغيل والإدارة (Operating expenses)
        // =========================================================================
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
        // 610000: مصروفات الإدارة والعمومية
        [
            'code'           => '610000',
            'key_value'      => '610000',
            'display_name'   => 'مصروفات الإدارة والعمومية',
            'name_en'        => 'General & administrative expenses',
            'parent_key'     => '600000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '610101',
            'key_value'      => 'salaries_wages',
            'display_name'   => 'رواتب الإدارة',
            'name_en'        => 'Admin salaries',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610102',
            'key_value'      => '610102',
            'display_name'   => 'إيجار المكتب',
            'name_en'        => 'Office rent',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610103',
            'key_value'      => 'utilities',
            'display_name'   => 'كهرباء ومرافق',
            'name_en'        => 'Utilities',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610104',
            'key_value'      => '610104',
            'display_name'   => 'اتصالات وإنترنت',
            'name_en'        => 'Telecom and internet',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610105',
            'key_value'      => '610105',
            'display_name'   => 'أتعاب محاسبة ومراجعة',
            'name_en'        => 'Accounting and audit fees',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610106',
            'key_value'      => '610106',
            'display_name'   => 'أتعاب قانونية',
            'name_en'        => 'Legal fees',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610107',
            'key_value'      => '610107',
            'display_name'   => 'أنظمة معلومات وبرامج',
            'name_en'        => 'Software/ERP subscriptions',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610108',
            'key_value'      => '610108',
            'display_name'   => 'قرطاسية ولوازم مكتبية',
            'name_en'        => 'Office supplies',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610109',
            'key_value'      => '610109',
            'display_name'   => 'سفر وانتقالات إدارية',
            'name_en'        => 'Admin travel',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610110',
            'key_value'      => 'maintenance_repairs',
            'display_name'   => 'صيانة ومصروفات إدارية أخرى',
            'name_en'        => 'Repairs and other admin expenses',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610111',
            'key_value'      => '610111',
            'display_name'   => 'إهلاك أصول إدارية',
            'name_en'        => 'Administrative depreciation',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610112',
            'key_value'      => '610112',
            'display_name'   => 'خسائر ائتمانية متوقعة',
            'name_en'        => 'Expected credit loss expense',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610113',
            'key_value'      => 'office_buffet_expenses',
            'display_name'   => 'ضيافة وبوفيه واستقبال',
            'name_en'        => 'Hospitality and buffet',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610114',
            'key_value'      => 'general_expenses',
            'display_name'   => 'رسوم وتراخيص حكومية واشتراكات',
            'name_en'        => 'Government licenses & subscriptions',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // 620000: مصروفات بيع وتسويق التصدير
        [
            'code'           => '620000',
            'key_value'      => '620000',
            'display_name'   => 'مصروفات بيع وتسويق التصدير',
            'name_en'        => 'Export selling and marketing expenses',
            'parent_key'     => '600000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '620101',
            'key_value'      => '620101',
            'display_name'   => 'عمولات وسطاء ووكلاء التصدير',
            'name_en'        => 'Export sales commissions',
            'parent_key'     => '620000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '620102',
            'key_value'      => '620102',
            'display_name'   => 'معارض دولية وتسويق',
            'name_en'        => 'Trade fairs and marketing',
            'parent_key'     => '620000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '620103',
            'key_value'      => '620103',
            'display_name'   => 'عينات ودعاية',
            'name_en'        => 'Samples and promotion',
            'parent_key'     => '620000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '620104',
            'key_value'      => '620104',
            'display_name'   => 'سفر تجاري وزيارات عملاء',
            'name_en'        => 'Commercial travel',
            'parent_key'     => '620000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '620105',
            'key_value'      => '620105',
            'display_name'   => 'خدمات اعتماد وشهادات تسويقية',
            'name_en'        => 'Marketing certification services',
            'parent_key'     => '620000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '620106',
            'key_value'      => '620106',
            'display_name'   => 'تسويق رقمي وأبحاث أسواق',
            'name_en'        => 'Digital marketing & market research',
            'parent_key'     => '620000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // =========================================================================
        // 700000: نتائج التمويل والبنود الأخرى (Finance and other results)
        // =========================================================================
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
        // 710000: إيرادات ومصروفات تمويلية
        [
            'code'           => '710000',
            'key_value'      => '710000',
            'display_name'   => 'إيرادات ومصروفات تمويلية',
            'name_en'        => 'Financing costs & revenues',
            'parent_key'     => '700000',
            'classification' => 'Finance and other results',
            'nature'         => 'Debit',
            'allow_posting'  => false,
        ],
        [
            'code'           => '710101',
            'key_value'      => 'bank_charges',
            'display_name'   => 'مصاريف وعمولات بنكية',
            'name_en'        => 'Bank charges & commissions',
            'parent_key'     => '710000',
            'classification' => 'Finance and other results',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '710102',
            'key_value'      => '710102',
            'display_name'   => 'أرباح فروق عملة أجنبية',
            'name_en'        => 'Foreign exchange gains',
            'parent_key'     => '710000',
            'classification' => 'Finance and other results',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '710103',
            'key_value'      => '710103',
            'display_name'   => 'خسائر فروق عملة أجنبية',
            'name_en'        => 'Foreign exchange losses',
            'parent_key'     => '710000',
            'classification' => 'Finance and other results',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '710104',
            'key_value'      => '710104',
            'display_name'   => 'فوائد وأرباح ودائع بنكية',
            'name_en'        => 'Interest & deposit income',
            'parent_key'     => '710000',
            'classification' => 'Finance and other results',
            'nature'         => 'Credit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '710105',
            'key_value'      => '710105',
            'display_name'   => 'فوائد تسهيلات وتمويل شحنات',
            'name_en'        => 'Interest expense & facility finance',
            'parent_key'     => '710000',
            'classification' => 'Finance and other results',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],

        // =========================================================================
        // 800000: حسابات رقابية خارج الميزانية (Off-balance memorandum accounts)
        // =========================================================================
        [
            'code'           => '800000',
            'key_value'      => '800000',
            'display_name'   => 'حسابات رقابية خارج الميزانية',
            'name_en'        => 'Off-balance memorandum accounts',
            'parent_key'     => null,
            'classification' => 'Off-balance memorandum accounts',
            'nature'         => 'Memo',
            'allow_posting'  => false,
        ],
        // 810000: اعتمادات ومستندات تحت التنفيذ
        [
            'code'           => '810000',
            'key_value'      => '810000',
            'display_name'   => 'اعتمادات ومستندات تحت التنفيذ',
            'name_en'        => 'Letters of credit & commitments',
            'parent_key'     => '800000',
            'classification' => 'Off-balance memorandum accounts',
            'nature'         => 'Memo',
            'allow_posting'  => false,
        ],
        [
            'code'           => '810101',
            'key_value'      => '810101',
            'display_name'   => 'خطابات ضمان مصدرة للغير',
            'name_en'        => 'Guarantees issued to others',
            'parent_key'     => '810000',
            'classification' => 'Off-balance memorandum accounts',
            'nature'         => 'Memo',
            'allow_posting'  => true,
        ],
        [
            'code'           => '810102',
            'key_value'      => '810102',
            'display_name'   => 'اعتمادات مستندية مفتوحة للتصدير',
            'name_en'        => 'Export LCs open',
            'parent_key'     => '810000',
            'classification' => 'Off-balance memorandum accounts',
            'nature'         => 'Memo',
            'allow_posting'  => true,
        ],
        [
            'code'           => '810103',
            'key_value'      => '810103',
            'display_name'   => 'التزامات عقود تصدير آجلة',
            'name_en'        => 'Export forward contract commitments',
            'parent_key'     => '810000',
            'classification' => 'Off-balance memorandum accounts',
            'nature'         => 'Memo',
            'allow_posting'  => true,
        ],
    ];

    /**
     * الحسابات الرئيسية لدليل بنود المصروفات (للتوافق مع الاستخدامات السابقة)
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
     * بنود المصروفات الفرعية التابعة للحسابات الرئيسية (للتوافق)
     */
    public const DEFAULT_EXPENSE_SUB_ITEMS = [
        [
            'code'           => '530101',
            'key_value'      => 'land_freight',
            'display_name'   => 'نقل بري وشحن / سيارات مبردة',
            'name_en'        => 'Land freight',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '520101',
            'key_value'      => 'packaging_materials',
            'display_name'   => 'كرتون وعبوات ومواد تغليف',
            'name_en'        => 'Cartons & packaging',
            'parent_key'     => '520000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '520102',
            'key_value'      => 'pallets',
            'display_name'   => 'طبالي خشبية',
            'name_en'        => 'Pallets',
            'parent_key'     => '520000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '530104',
            'key_value'      => 'certificates_inspection',
            'display_name'   => 'شهادات صحية وفحص وحجر زراعي',
            'name_en'        => 'Inspection & health certs',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '530103',
            'key_value'      => 'customs_clearance',
            'display_name'   => 'تخليص جمركي وأوراق ومستندات تصدير',
            'name_en'        => 'Customs clearance',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '530105',
            'key_value'      => 'labor_loading',
            'display_name'   => 'عتالة وتحميل وتعتيق',
            'name_en'        => 'Loading & unloading',
            'parent_key'     => '530000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '520104',
            'key_value'      => 'cold_storage',
            'display_name'   => 'إيجار وتبريد ثلاجات ومخازن',
            'name_en'        => 'Cold storage rental',
            'parent_key'     => '520000',
            'classification' => 'Cost of sales',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610101',
            'key_value'      => 'salaries_wages',
            'display_name'   => 'رواتب الإدارة',
            'name_en'        => 'Admin salaries',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610113',
            'key_value'      => 'office_buffet_expenses',
            'display_name'   => 'ضيافة وبوفيه واستقبال',
            'name_en'        => 'Hospitality and buffet',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610103',
            'key_value'      => 'utilities',
            'display_name'   => 'كهرباء ومرافق',
            'name_en'        => 'Utilities',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610110',
            'key_value'      => 'maintenance_repairs',
            'display_name'   => 'صيانة ومصروفات إدارية أخرى',
            'name_en'        => 'Repairs and other admin expenses',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '610114',
            'key_value'      => 'general_expenses',
            'display_name'   => 'رسوم وتراخيص حكومية واشتراكات',
            'name_en'        => 'Government licenses & subscriptions',
            'parent_key'     => '610000',
            'classification' => 'Expense',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
        [
            'code'           => '710101',
            'key_value'      => 'bank_charges',
            'display_name'   => 'مصاريف وعمولات بنكية',
            'name_en'        => 'Bank charges & commissions',
            'parent_key'     => '710000',
            'classification' => 'Finance and other results',
            'nature'         => 'Debit',
            'allow_posting'  => true,
        ],
    ];

    /**
     * زراعة وتحديث دليل شجرة الحسابات المحاسبية الكامل (أكثر من 80 حساب رئيسي وفرعي وتفصيلي)
     */
    public static function seedFullChartOfAccounts(bool $force = false): int
    {
        $cacheKey = 'chart_of_accounts_v2026_full';
        if (!$force && Cache::get($cacheKey)) {
            return static::where('category', 'expense_category')->count();
        }

        $seededCount = 0;

        try {
            // الترتيب: المستوى الأول (بدون أب)، ثم المستوى الثاني، ثم المستوى الثالث
            $items = collect(self::FULL_CHART_OF_ACCOUNTS)->sortBy(function ($item) {
                if (empty($item['parent_key'])) {
                    return 1;
                }
                if (str_ends_with((string) $item['code'], '0000')) {
                    return 2;
                }
                return 3;
            })->values();

            foreach ($items as $item) {
                $code = (string) $item['code'];
                $keyValue = (string) ($item['key_value'] ?? $code);

                // البحث عن السجل القائم إما بالكود أو بمفتاح key_value أو بالاسم العربي
                $rec = static::where('category', 'expense_category')
                    ->where(function ($q) use ($code, $keyValue, $item) {
                        $q->where('code', $code)
                          ->orWhere('key_value', $keyValue)
                          ->orWhere('key_value', $code)
                          ->orWhere('display_name', $item['display_name']);
                    })
                    ->first();

                if ($rec) {
                    $rec->update([
                        'code'           => $code,
                        'display_name'   => $item['display_name'],
                        'name_en'        => $item['name_en'],
                        'parent_key'     => $item['parent_key'],
                        'classification' => $item['classification'],
                        'nature'         => $item['nature'],
                        'allow_posting'  => (bool) $item['allow_posting'],
                    ]);
                } else {
                    static::create([
                        'category'       => 'expense_category',
                        'key_value'      => $keyValue,
                        'code'           => $code,
                        'display_name'   => $item['display_name'],
                        'name_en'        => $item['name_en'],
                        'parent_key'     => $item['parent_key'],
                        'classification' => $item['classification'],
                        'nature'         => $item['nature'],
                        'allow_posting'  => (bool) $item['allow_posting'],
                    ]);
                }
                $seededCount++;
            }

            Cache::forget('system_settings');
            Cache::forget('expense_chart_of_accounts_v1');
            Cache::forever($cacheKey, true);
        } catch (\Throwable $e) {
            // تجاهل أي خطأ أثناء التهيئة المبدئية
        }

        return $seededCount;
    }

    /**
     * التأكد من وجود وتحديث شجرة الحسابات لبنود المصروفات
     */
    public static function ensureExpenseCategoriesSeeded(): void
    {
        self::seedFullChartOfAccounts(false);
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
