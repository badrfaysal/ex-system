<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Vendor;
use App\Models\Item;
use App\Models\PriceList;
use App\Models\Quotation;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $clients = $this->seedClients();
        $vendors = $this->seedVendors();
        $items   = $this->seedItems($vendors);
        $lists   = $this->seedPriceLists($items);
        $this->seedQuotations($clients, $items, $lists);
        $this->command->info('✅ تم إدخال داتا الأغذية والخضروات والفاكهة بنجاح.');
    }

    /* ===== الإعدادات ===== */
    private function seedSettings(): void
    {
        $data = [
            'currency' => [
                'EGP' => 'جنيه مصري',
                'USD' => 'دولار أمريكي',
                'EUR' => 'يورو',
                'SAR' => 'ريال سعودي',
            ],
            'uom' => [
                'kg'    => 'كيلوجرام',
                'ton'   => 'طن',
                'box'   => 'كرتونة',
                'crate' => 'صندوق',
                'pcs'   => 'حبة',
                'dozen' => 'دستة',
                'bag'   => 'شوال',
            ],
            'item_group' => [
                'vegetables' => 'خضروات',
                'fruits'     => 'فاكهة',
                'herbs'      => 'أعشاب وتوابل',
                'frozen'     => 'أغذية مجمدة',
                'dairy'      => 'ألبان وأجبان',
                'grains'     => 'حبوب وبقوليات',
            ],
            'item_status' => [
                'active'   => 'نشط',
                'inactive' => 'متوقف',
            ],
            'client_type' => [
                'supermarket' => 'سوبرماركت',
                'restaurant'  => 'مطعم / فندق',
                'wholesale'   => 'تاجر جملة',
                'retail'      => 'تجزئة',
                'corporate'   => 'شركات',
            ],
            'vendor_group' => [
                'farm'         => 'مزرعة',
                'importer'     => 'مستورد',
                'manufacturer' => 'مصنع',
                'local'        => 'مورد محلي',
            ],
            'vendor_status' => [
                'active'   => 'نشط',
                'on_hold'  => 'معلق',
                'blocked'  => 'محظور',
            ],
            'payment_method' => [
                'cash'          => 'نقدي',
                'bank_transfer' => 'تحويل بنكي',
                'cheque'        => 'شيك',
            ],
            'payment_terms' => [
                'immediate' => 'فوري',
                '7_days'    => '7 أيام',
                '15_days'   => '15 يوم',
                '30_days'   => '30 يوم',
            ],
            'expense_category' => [
                'transportation' => 'تنقلات',
                'wages'          => 'أجور',
                'other'          => 'أخرى',
            ],
        ];

        foreach ($data as $category => $pairs) {
            foreach ($pairs as $key => $label) {
                Setting::firstOrCreate(
                    ['category' => $category, 'key_value' => $key],
                    ['display_name' => $label]
                );
            }
        }
    }

    /* ===== العملاء ===== */
    private function seedClients(): array
    {
        $rows = [
            ['كارفور مصر للتجزئة',            'أ. محمود الشناوي',   '0224561200', 'supermarket', 'مصر',     'مصر - القاهرة، الدقي، ميدان لبنان'],
            ['سوبرماركت سبينيس مصر',          'م. نهى حسين',        '0224410800', 'supermarket', 'مصر',     'مصر - القاهرة، المعادي'],
            ['مجموعة مطاعم كودو مصر',         'أ. عصام ربيع',       '0225201234', 'restaurant',  'مصر',     'مصر - القاهرة، مدينة نصر'],
            ['فندق فور سيزونز القاهرة',        'مدير المشتريات',     '0227360000', 'restaurant',  'مصر',     'مصر - القاهرة، نايل سيتي'],
            ['شركة أبو عوف للتوريدات',        'أ. طارق أبو عوف',    '0223050050', 'wholesale',   'مصر',     'مصر - القاهرة، مصر الجديدة'],
            ['سوق العبور للجملة',              'أ. حامد سعيد',       '0244850000', 'wholesale',   'مصر',     'مصر - القاهرة، العبور'],
            ['مجموعة مطاعم قصر الدوحة',       'م. خالد الرشيد',     '0097444501122','restaurant', 'قطر',     'قطر - الدوحة، الخليج الغربي'],
            ['متاجر لولو هايبر ماركت',        'م. سمية البلوشي',    '0097144590000','supermarket','الإمارات','الإمارات - دبي، البرشاء'],
            ['شركة زاد للأغذية والتوريد',     'أ. رامي قدري',       '0225879900', 'wholesale',   'مصر',     'مصر - الجيزة، إمبابة'],
            ['مطاعم ماكدونالدز مصر',          'إدارة سلسلة التوريد', '0222703030', 'restaurant',  'مصر',     'مصر - القاهرة، هليوبوليس'],
            ['سوبرماركت الرانيا الإسكندرية',  'أ. إيمان محفوظ',     '0342201100', 'supermarket', 'مصر',     'مصر - الإسكندرية، سموحة'],
            ['شركة فريش ماركت للتجزئة',       'م. هاني طاهر',       '0221105678', 'retail',      'مصر',     'مصر - القاهرة، الزمالك'],
            ['مجموعة فنادق هيلتون مصر',       'مدير الإمداد',       '0225740000', 'restaurant',  'مصر',     'مصر - القاهرة، كورنيش النيل'],
            ['شركة الدلتا للتوريدات الغذائية','أ. نبيل الجعفري',    '0402203344', 'corporate',   'مصر',     'مصر - المنوفية، شبين الكوم'],
        ];

        $clients = [];
        foreach ($rows as $r) {
            $clients[] = Client::create([
                'company_name'   => $r[0],
                'contact_person' => $r[1],
                'phone'          => $r[2],
                'email'          => Str::slug($r[0]) . '@example.com',
                'client_type'    => $r[3],
                'country'        => $r[4],
                'address'        => $r[5],
                'tax_id'         => (string) rand(200000000, 899999999),
            ]);
        }

        return $clients;
    }

    /* ===== الموردين ===== */
    private function seedVendors(): array
    {
        $rows = [
            ['مزارع دلتا النيل للخضروات',     'Delta Nile Farms',      'farm',      'A'],
            ['شركة فريش إيجيبت للاستيراد',    'Fresh Egypt Imports',   'importer',  'A'],
            ['مزرعة الواحة الخضراء',          'Green Oasis Farm',      'farm',      'B'],
            ['شركة النيل للفاكهة الطازجة',    'Nile Fresh Fruits',     'local',     'A'],
            ['مزارع سيناء للنخيل والتمر',     'Sinai Palm Farms',      'farm',      'B'],
            ['شركة دلتا للأغذية المجمدة',     'Delta Frozen Foods',    'manufacturer','C'],
            ['مجموعة أجروميد للاستيراد',      'Agromed Import Group',  'importer',  'A'],
        ];

        $vendors = [];
        foreach ($rows as $i => $r) {
            $vendors[] = Vendor::create([
                'vendor_code'         => 'VND-' . str_pad($i + 100, 4, '0', STR_PAD_LEFT),
                'name_ar'             => $r[0],
                'name_en'             => $r[1],
                'legal_name'          => $r[0] . ' (ش.ذ.م.م)',
                'vendor_group'        => $r[2],
                'status'              => 'active',
                'vendor_rating'       => $r[3],
                'mobile'              => '010' . rand(10000000, 99999999),
                'phone'               => '02' . rand(20000000, 29999999),
                'email'               => Str::slug($r[1]) . '@example.com',
                'contact_person_name' => 'مسؤول المبيعات',
                'default_currency'    => 'EGP',
                'tax_id'              => (string) rand(100000000, 999999999),
                'commercial_registry' => (string) rand(10000, 99999),
                'payment_terms'       => '7_days',
                'payment_method'      => 'bank_transfer',
                'lead_time_days'      => rand(1, 7),
            ]);
        }

        return $vendors;
    }

    /* ===== الأصناف (خضروات + فاكهة + أعشاب) ===== */
    private function seedItems(array $vendors): array
    {
        // [name_ar, name_en, group, uom, price_per_unit]
        $rows = [
            // خضروات
            ['طماطم طازجة - درجة أولى',         'Fresh Tomatoes Grade A',       'vegetables', 'kg',    12],
            ['بطاطس بيضاء - مصرية',             'White Potatoes Egyptian',       'vegetables', 'kg',     8],
            ['بصل أبيض جاف',                    'White Onion Dry',               'vegetables', 'kg',    10],
            ['خيار أخضر طازج',                   'Fresh Green Cucumber',         'vegetables', 'kg',    14],
            ['فلفل ألوان مشكل',                  'Mixed Bell Peppers',           'vegetables', 'kg',    28],
            ['جزر برتقالي طازج',                 'Fresh Orange Carrot',          'vegetables', 'kg',    11],
            ['ثوم مصري مجفف',                    'Egyptian Dried Garlic',        'vegetables', 'kg',    55],
            ['خس روماني طازج',                   'Fresh Romaine Lettuce',        'vegetables', 'kg',    18],
            ['باذنجان بلدي',                     'Egyptian Eggplant',            'vegetables', 'kg',    10],
            ['كوسة خضراء طازجة',                'Fresh Green Zucchini',          'vegetables', 'kg',    13],
            ['فول أخضر طازج',                   'Fresh Green Beans',             'vegetables', 'kg',    20],
            ['ملوخية طازجة',                     'Fresh Molokhia Leaves',        'vegetables', 'kg',    22],
            ['قرنبيط أبيض',                      'White Cauliflower',            'vegetables', 'kg',    16],
            ['كرنب أخضر',                        'Green Cabbage',                'vegetables', 'kg',     9],
            ['فجل أبيض طازج',                    'Fresh White Radish',           'vegetables', 'kg',     7],
            // فاكهة
            ['تفاح أحمر - مستورد',               'Red Apple Imported',           'fruits',     'kg',    45],
            ['برتقال بلدي - الإسكندرية',         'Local Orange Alexandria',      'fruits',     'kg',    15],
            ['موز أصفر - إكوادور',               'Yellow Banana Ecuador',        'fruits',     'kg',    30],
            ['عنب أخضر بدون بذر',               'Seedless Green Grapes',         'fruits',     'kg',    60],
            ['مانجو عسلي مصري',                  'Egyptian Honey Mango',         'fruits',     'kg',    35],
            ['فراولة طازجة - إسنا',              'Fresh Strawberry Esna',        'fruits',     'kg',    40],
            ['بطيخ أحمر موسمي',                  'Red Watermelon Seasonal',      'fruits',     'kg',     6],
            ['جوافة بيضاء طازجة',               'Fresh White Guava',             'fruits',     'kg',    20],
            ['خوخ مشمش موسمي',                  'Apricot Peach Seasonal',        'fruits',     'kg',    38],
            ['رمان مصري حلو',                    'Egyptian Sweet Pomegranate',   'fruits',     'kg',    50],
            ['كمثرى لبناني مستورد',              'Lebanese Pear Imported',       'fruits',     'kg',    55],
            ['كيوي نيوزيلندي',                   'New Zealand Kiwi',             'fruits',     'kg',    70],
            ['ليمون أصفر بلدي',                  'Local Yellow Lemon',           'fruits',     'kg',    18],
            // أعشاب وتوابل
            ['بقدونس طازج - حزمة',              'Fresh Parsley Bunch',           'herbs',      'kg',    25],
            ['نعناع طازج',                        'Fresh Mint',                  'herbs',      'kg',    30],
            ['كزبرة خضراء طازجة',               'Fresh Green Coriander',         'herbs',      'kg',    28],
            ['زعتر طازج - أوراق',               'Fresh Thyme Leaves',            'herbs',      'kg',    45],
            ['فجل خوخ - ميكس',                  'Radish Mix Seasonal',           'herbs',      'kg',    20],
        ];

        $items = [];
        foreach ($rows as $i => $r) {
            $item = Item::create([
                'item_code'         => 'ITM-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'barcode'           => '622' . rand(1000000000, 9999999999),
                'name_ar'           => $r[0],
                'name_en'           => $r[1],
                'item_group'        => $r[2],
                'base_uom'          => $r[3],
                'reorder_point'     => rand(50, 200),
                'min_stock'         => rand(20, 50),
                'max_stock'         => rand(500, 2000),
                'moq'               => rand(5, 20),
                'lead_time_days'    => rand(1, 5),
                'status'            => 'active',
                'default_vendor_id' => $vendors[array_rand($vendors)]->id,
            ]);

            $picked = collect($vendors)->random(rand(1, 3));
            foreach ($picked as $v) {
                $item->approvedVendors()->syncWithoutDetaching([
                    $v->id => ['last_purchase_price' => round($r[4] * rand(55, 75) / 100, 2)],
                ]);
            }

            $items[] = ['model' => $item, 'price' => $r[4]];
        }

        return $items;
    }

    /* ===== قوائم الأسعار ===== */
    private function seedPriceLists(array $items): array
    {
        $defs = [
            ['قائمة أسعار التجزئة الموحدة',      1.00, 'EGP', 'active'],
            ['قائمة أسعار الجملة - موزعين معتمدين', 0.85, 'EGP', 'active'],
            ['قائمة أسعار الفنادق والمطاعم',      0.92, 'EGP', 'active'],
            ['قائمة أسعار التصدير (دولار)',       0.00, 'USD', 'active'],
        ];

        $lists = [];
        foreach ($defs as $i => $d) {
            $list = PriceList::create([
                'code'             => 'PL-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name'             => $d[0],
                'default_currency' => $d[2],
                'valid_from'       => Carbon::now()->startOfYear(),
                'valid_to'         => Carbon::now()->endOfYear(),
                'status'           => $d[3],
            ]);

            foreach ($items as $it) {
                $factor = $d[1] ?: (1 / 48.5); // تحويل تقريبي للدولار
                $list->items()->create([
                    'item_id' => $it['model']->id,
                    'price'   => round($it['price'] * $factor, $d[2] === 'USD' ? 2 : 0),
                ]);
            }

            $lists[] = $list;
        }

        return $lists;
    }

    /* ===== عروض الأسعار ===== */
    private function seedQuotations(array $clients, array $items, array $lists): void
    {
        $statuses  = ['draft', 'sent', 'converted', 'sent', 'draft', 'cancelled', 'converted', 'sent', 'draft', 'sent'];
        $salesReps = ['أحمد فتحي عبد العزيز', 'سمر محمد الشريف', 'خالد عمرو ربيع', 'نهى إبراهيم حسن'];
        $terms     = "- الأسعار سارية حتى تاريخ انتهاء صلاحية العرض.\n"
                   . "- يتم التسليم خلال 24-48 ساعة من إصدار أمر التوريد.\n"
                   . "- المنتجات طازجة ومضمونة الجودة وفق معايير الشركة.\n"
                   . "- الدفع: نقدي عند التسليم أو تحويل بنكي خلال 7 أيام.";

        foreach ($statuses as $n => $status) {
            $client = $clients[array_rand($clients)];
            $list   = $lists[array_rand(array_slice($lists, 0, 3))]; // قوائم EGP فقط
            $date   = Carbon::now()->subDays(rand(0, 45));

            $quotation = Quotation::create([
                'quote_number'    => 'QT-' . $date->format('Y-m') . '-' . str_pad($n + 1, 4, '0', STR_PAD_LEFT),
                'quote_date'      => $date,
                'expiry_date'     => (clone $date)->addDays(7),
                'client_id'       => $client->id,
                'price_list_id'   => $list->id,
                'sales_rep'       => $salesReps[array_rand($salesReps)],
                'currency'        => 'EGP',
                'cost_center_name' => 'مركز تكلفة العميل ' . $client->displayName('ar') . ' بتاريخ ' . $date->format('Y-m-d'),
                'status'          => $status,
                'terms'           => $terms,
            ]);

            $chosen   = collect($items)->random(rand(3, 7));
            $subtotal = 0; $lineDisc = 0; $taxAmount = 0;

            foreach ($chosen as $it) {
                $qty   = rand(5, 200);
                $price = round($it['price'] * ($list->items()->where('item_id', $it['model']->id)->value('price') / $it['price'] ?: 1), 2);
                $price = $it['price']; // نستخدم السعر الأصلي للبساطة
                $disc  = [0, 0, 0, 5, 10][array_rand([0, 1, 2, 3, 4])];
                $tax   = 14;

                $base      = $qty * $price;
                $discVal   = $base * $disc / 100;
                $afterDisc = $base - $discVal;
                $taxVal    = $afterDisc * $tax / 100;
                $net       = $afterDisc + $taxVal;

                $quotation->items()->create([
                    'item_id'          => $it['model']->id,
                    'item_code'        => $it['model']->item_code,
                    'description'      => $it['model']->name_ar,
                    'quantity'         => $qty,
                    'uom'              => $it['model']->base_uom,
                    'list_price'       => $price,
                    'discount_percent' => $disc,
                    'tax_percent'      => $tax,
                    'net_total'        => round($net, 2),
                ]);

                $subtotal  += $base;
                $lineDisc  += $discVal;
                $taxAmount += $taxVal;
            }

            $extra = rand(0, 1) ? round($subtotal * 0.01, 2) : 0;
            $quotation->update([
                'subtotal'       => round($subtotal, 2),
                'total_discount' => round($lineDisc + $extra, 2),
                'tax_amount'     => round($taxAmount, 2),
                'grand_total'    => round($subtotal - $lineDisc - $extra + $taxAmount, 2),
            ]);
        }
    }
}
