<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class UomHelper
{
    /**
     * القاموس الشامل لوحدات القياس باللغتين العربية والإنجليزية مع المرادفات
     */
    protected static array $units = [
        'carton' => [
            'ar' => 'كرتونة',
            'en' => 'Carton',
            'aliases' => ['كرتونه', 'كرتونة', 'كرتون', 'كراتين', 'carton', 'cartons', 'ctn', 'ctns'],
        ],
        'box' => [
            'ar' => 'علبة',
            'en' => 'Box',
            'aliases' => ['علبه', 'علبة', 'علب', 'box', 'boxes', 'bx'],
        ],
        'ton' => [
            'ar' => 'طن',
            'en' => 'Ton',
            'aliases' => ['طن', 'اطنان', 'أطنان', 'طنان', 'ton', 'tons', 'tonne', 'tonnes', 't', 'mt'],
        ],
        'kg' => [
            'ar' => 'كيلوجرام',
            'en' => 'Kg',
            'aliases' => ['كيلوجرام', 'كيلو جرام', 'كيلو', 'كجم', 'كغ', 'كيلوجرامات', 'kg', 'kgs', 'kilogram', 'kilograms', 'kilo', 'kilos'],
        ],
        'gram' => [
            'ar' => 'جرام',
            'en' => 'Gram',
            'aliases' => ['جرام', 'غرام', 'جم', 'غم', 'جرامات', 'g', 'gm', 'gms', 'gram', 'grams'],
        ],
        'piece' => [
            'ar' => 'قطعة',
            'en' => 'Piece',
            'aliases' => ['قطعه', 'قطعة', 'قطع', 'حبه', 'حبة', 'حبات', 'عدد', 'piece', 'pieces', 'pc', 'pcs', 'unit', 'units', 'item', 'items'],
        ],
        'crate' => [
            'ar' => 'صندوق',
            'en' => 'Crate',
            'aliases' => ['صندوق', 'صناديق', 'قفص', 'اقفاص', 'أقفاص', 'crate', 'crates'],
        ],
        'bag' => [
            'ar' => 'شوال',
            'en' => 'Bag',
            'aliases' => ['شوال', 'شوالات', 'كيس', 'اكياس', 'أكياس', 'شيكاره', 'شيكارة', 'خيشه', 'خيشة', 'bag', 'bags', 'sack', 'sacks'],
        ],
        'dozen' => [
            'ar' => 'دستة',
            'en' => 'Dozen',
            'aliases' => ['دسته', 'دستة', 'دست', 'dozen', 'doz', 'dz'],
        ],
        'pack' => [
            'ar' => 'عبوة',
            'en' => 'Pack',
            'aliases' => ['عبوه', 'عبوة', 'باكت', 'باكيت', 'طرد', 'pack', 'packs', 'package', 'packages', 'pkg', 'pkgs'],
        ],
        'pallet' => [
            'ar' => 'بالتة',
            'en' => 'Pallet',
            'aliases' => ['بالته', 'بالتة', 'طبليه', 'طبلية', 'باليت', 'باليته', 'باليتة', 'pallet', 'pallets', 'plt'],
        ],
        'meter' => [
            'ar' => 'متر',
            'en' => 'Meter',
            'aliases' => ['متر', 'امتار', 'أمتار', 'meter', 'meters', 'metre', 'metres', 'm'],
        ],
        'cm' => [
            'ar' => 'سنتيمتر',
            'en' => 'cm',
            'aliases' => ['سنتيمتر', 'سم', 'cm', 'centimeter', 'centimeters'],
        ],
        'liter' => [
            'ar' => 'لتر',
            'en' => 'Liter',
            'aliases' => ['لتر', 'ليتر', 'لترات', 'liter', 'liters', 'litre', 'litres', 'l', 'ltr'],
        ],
        'ml' => [
            'ar' => 'مليلتر',
            'en' => 'ml',
            'aliases' => ['مليلتر', 'مل', 'ملي', 'ml', 'milliliter', 'millilitre'],
        ],
        'drum' => [
            'ar' => 'برميل',
            'en' => 'Drum',
            'aliases' => ['برميل', 'براميل', 'drum', 'drums'],
        ],
        'roll' => [
            'ar' => 'رول',
            'en' => 'Roll',
            'aliases' => ['رول', 'لفه', 'لفة', 'roll', 'rolls'],
        ],
        'bottle' => [
            'ar' => 'زجاجة',
            'en' => 'Bottle',
            'aliases' => ['زجاجه', 'زجاجة', 'قاروره', 'قارورة', 'bottle', 'bottles', 'btl'],
        ],
        'can' => [
            'ar' => 'علبة',
            'en' => 'Can',
            'aliases' => ['كانز', 'صفيحه', 'صفيحة', 'can', 'cans', 'tin', 'tins'],
        ],
        'container' => [
            'ar' => 'حاوية',
            'en' => 'Container',
            'aliases' => ['حاويه', 'حاوية', 'container', 'cntr'],
        ],
    ];

    /**
     * تطبيع النص العربي واللاتيني للمقارنة بدقة
     */
    public static function normalize(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        $text = trim($text);

        // إزالة التشكيل
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text);

        // توحيد الألف
        $text = preg_replace('/[أإآٱ]/u', 'ا', $text);

        // توحيد التاء المربوطة والهاء
        $text = preg_replace('/ة/u', 'ه', $text);

        // توحيد الياء والألف المقصورة
        $text = preg_replace('/ى/u', 'ي', $text);

        // إزالة التطويل
        $text = preg_replace('/ـ/u', '', $text);

        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * ترجمة أي وحدة قياس (سواء كانت كود أو بالعربي أو بالإنجليزي) إلى اللغة المطلوبة
     */
    public static function translate(?string $uom, ?string $locale = null, string $fallback = '—'): string
    {
        if ($uom === null || trim($uom) === '' || trim($uom) === '—' || trim($uom) === '-') {
            return $fallback;
        }

        $raw = trim($uom);
        $norm = self::normalize($raw);
        $targetLocale = ($locale === 'en' || $locale === 'ar') ? $locale : app()->getLocale();

        // 1. البحث في القاموس الثابت
        foreach (self::$units as $key => $data) {
            if ($norm === self::normalize($key)) {
                return $data[$targetLocale] ?? $raw;
            }

            foreach ($data['aliases'] as $alias) {
                if ($norm === self::normalize($alias)) {
                    return $data[$targetLocale] ?? $raw;
                }
            }
        }

        // 2. البحث في إعدادات النظام (Settings)
        try {
            $settings = Cache::remember('system_settings_uom_map', 60 * 60, function () {
                return Setting::where('category', 'uom')->get();
            });

            foreach ($settings as $s) {
                $kNorm = self::normalize($s->key_value);
                $dNorm = self::normalize($s->display_name);

                if ($norm === $kNorm || $norm === $dNorm) {
                    if ($targetLocale === 'ar') {
                        return $s->display_name;
                    }
                    if (isset(self::$units[$s->key_value])) {
                        return self::$units[$s->key_value]['en'];
                    }
                    return ucfirst(str_replace('_', ' ', $s->key_value));
                }
            }
        } catch (\Throwable $e) {
            // صامت في حال تعذر الاتصال بالقاعدة
        }

        // 3. محاولة الترجمة عبر ملفات اللغة الرسمية
        $langKey = "messages.options.uom.{$raw}";
        if (\Illuminate\Support\Facades\Lang::has($langKey, $targetLocale)) {
            return __($langKey, [], $targetLocale);
        }

        return $raw;
    }

    /**
     * إرجاع قائمة خيارات وحدات القياس [key => display_name] بحسب اللغة المطلوبة
     */
    public static function options(?string $locale = null, $dbSettings = null): array
    {
        $targetLocale = ($locale === 'en' || $locale === 'ar') ? $locale : app()->getLocale();

        $result = [];

        // لو وصلت قائمة من إعدادات الداتابيز
        if ($dbSettings && $dbSettings->isNotEmpty()) {
            foreach ($dbSettings as $s) {
                $key = $s->key_value;
                $result[$key] = self::translate($s->display_name ?: $key, $targetLocale);
            }
            return $result;
        }

        // افتراضياً من القاموس
        foreach (self::$units as $key => $data) {
            $result[$key] = $data[$targetLocale] ?? $data['en'];
        }

        return $result;
    }
}
