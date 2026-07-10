<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * بيانات وهمية لتجربة نظام ربط الموردين بالأصناف (شركة تصدير).
     */
    public function run(): void
    {
        // ===== الموردين =====
        $vendors = [
            ['name_ar' => 'مزرعة الأمل للخضروات',     'name_en' => 'Al-Amal Farms',        'group' => 'local',         'rating' => 'A', 'city' => 'البحيرة'],
            ['name_ar' => 'شركة الدلتا للحاصلات',      'name_en' => 'Delta Crops Co.',      'group' => 'local',         'rating' => 'A', 'city' => 'كفر الشيخ'],
            ['name_ar' => 'مصنع النيل للتعبئة والتغليف','name_en' => 'Nile Packaging',       'group' => 'local',         'rating' => 'B', 'city' => 'القاهرة'],
            ['name_ar' => 'مؤسسة الصعيد للموالح',      'name_en' => 'Upper Egypt Citrus',   'group' => 'local',         'rating' => 'A', 'city' => 'المنيا'],
            ['name_ar' => 'الشركة الدولية للتبريد',    'name_en' => 'Intl Cooling Logistics','group' => 'subcontractor', 'rating' => 'C', 'city' => 'الإسكندرية'],
        ];

        $createdVendors = [];
        foreach ($vendors as $i => $v) {
            $createdVendors[] = Vendor::create([
                'vendor_code'         => 'VND-' . str_pad($i + 100, 4, '0', STR_PAD_LEFT),
                'name_ar'             => $v['name_ar'],
                'name_en'             => $v['name_en'],
                'legal_name'          => $v['name_ar'] . ' (ش.ذ.م.م)',
                'vendor_group'        => $v['group'],
                'status'              => 'active',
                'vendor_rating'       => $v['rating'],
                'mobile'              => '01' . rand(0, 2) . rand(10000000, 99999999),
                'phone'               => '02' . rand(20000000, 29999999),
                'email'               => Str::slug($v['name_en']) . '@example.com',
                'contact_person_name' => 'مسؤول المبيعات',
                'default_currency'    => 'EGP',
                'tax_id'              => rand(100000000, 999999999),
                'commercial_registry' => rand(10000, 99999),
                'payment_terms'       => '30_days',
                'payment_method'      => 'bank_transfer',
                'lead_time_days'      => rand(2, 14),
            ]);
        }

        // ===== الأصناف =====
        $items = [
            ['name_ar' => 'برتقال أبو سرة',      'name_en' => 'Navel Orange',     'group' => 'finished_product', 'uom' => 'kg'],
            ['name_ar' => 'بطاطس فريش',          'name_en' => 'Fresh Potatoes',   'group' => 'finished_product', 'uom' => 'kg'],
            ['name_ar' => 'فراولة مجمدة',        'name_en' => 'Frozen Strawberry','group' => 'finished_product', 'uom' => 'kg'],
            ['name_ar' => 'بصل أحمر',            'name_en' => 'Red Onion',        'group' => 'finished_product', 'uom' => 'kg'],
            ['name_ar' => 'كرتونة تصدير 5 كجم',  'name_en' => 'Export Box 5kg',   'group' => 'packaging',        'uom' => 'box'],
            ['name_ar' => 'عنب بناتي',           'name_en' => 'Banati Grapes',    'group' => 'finished_product', 'uom' => 'kg'],
            ['name_ar' => 'ليمون أضاليا',        'name_en' => 'Adalia Lemon',     'group' => 'finished_product', 'uom' => 'kg'],
        ];

        $createdItems = [];
        foreach ($items as $i => $it) {
            $createdItems[] = Item::create([
                'item_code'      => 'ITM-' . str_pad($i + 100, 5, '0', STR_PAD_LEFT),
                'barcode'        => '622' . rand(1000000000, 9999999999),
                'name_ar'        => $it['name_ar'],
                'name_en'        => $it['name_en'],
                'item_group'     => $it['group'],
                'base_uom'       => $it['uom'],
                'reorder_point'  => rand(100, 500),
                'min_stock'      => rand(50, 100),
                'max_stock'      => rand(1000, 5000),
                'moq'            => rand(1, 10) * 100,
                'lead_time_days' => rand(2, 10),
                'status'         => 'active',
            ]);
        }

        // ===== الربط بين الموردين والأصناف (item_vendor) =====
        // كل صنف يتورد من عدة موردين بأسعار مختلفة
        $links = [
            // [item index, vendor index, price]
            [0, 0, 12.50], [0, 1, 13.00], [0, 3, 11.75],   // برتقال: 3 موردين
            [1, 0, 8.25],  [1, 1, 8.00],                    // بطاطس: موردين
            [2, 1, 22.00], [2, 4, 21.50],                   // فراولة مجمدة
            [3, 0, 9.50],  [3, 3, 9.00],                    // بصل
            [4, 2, 4.75],                                    // كرتونة تصدير: مورد واحد
            [5, 1, 18.00], [5, 3, 17.50], [5, 0, 18.25],   // عنب: 3 موردين
            [6, 3, 14.00], [6, 1, 14.50],                   // ليمون
        ];

        foreach ($links as [$itemIdx, $vendorIdx, $price]) {
            $createdItems[$itemIdx]->approvedVendors()->attach(
                $createdVendors[$vendorIdx]->id,
                ['last_purchase_price' => $price]
            );
        }

        $this->command->info('تم إنشاء ' . count($createdVendors) . ' موردين و ' . count($createdItems) . ' أصناف و ' . count($links) . ' علاقة ربط.');
    }
}
