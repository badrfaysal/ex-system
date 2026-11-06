<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrintedLabel;
use App\Models\Item;
use App\Models\Client;

class LabelController extends Controller
{
    /**
     * سجل طباعة الملصقات
     */
    public function index()
    {
        $labels = PrintedLabel::latest()->get();
        return view('labels.index', compact('labels'));
    }

    /**
     * صفحة الملصق التفصيلي الكامل (يملأ الورقة بالكامل)
     */
    public function createDetailed()
    {
        $items = Item::orderBy('name_ar')->get(['name_ar', 'item_code']);
        $clients = Client::orderBy('company_name')->get(['company_name']);
        $defaultCompanyName = 'ايجيبيشن فودز';
        $defaultAddress = 'اكتوبر , الجيزة , جمهورية مصر العربية';
        $defaultWebsite = 'WWW.efcexport.com / info@efcexport.com';

        return view('labels.detailed', compact('items', 'clients', 'defaultCompanyName', 'defaultAddress', 'defaultWebsite'));
    }

    /**
     * صفحة الملصق الصغير المستطيل (50 × 25 مم)
     */
    public function createCompact()
    {
        $items = Item::orderBy('name_ar')->get(['name_ar', 'item_code']);
        $defaultCompanyName = 'ايجيبيشن فودز';
        $todayDate = now()->format('Y/m/d');

        return view('labels.compact', compact('items', 'defaultCompanyName', 'todayDate'));
    }

    /**
     * توجيه للرابط القديم
     */
    public function create()
    {
        return redirect()->route('labels.detailed');
    }

    /**
     * طباعة الملصق التفصيلي الكامل (يملأ الورقة بالكامل بخط كبير في ستيكر واحد)
     */
    public function printDetailed(Request $request)
    {
        $data = $request->validate([
            'brand_name'           => 'required|string|max:255',
            'item_name'            => 'required|string|max:255',
            'manufacturer'         => 'required|string|max:255',
            'manufacturer_address' => 'required|string|max:255',
            'exporter'             => 'required|string|max:255',
            'exporter_address'     => 'required|string|max:255',
            'importer'             => 'required|string|max:255',
            'importer_address'     => 'required|string|max:255',
            'production_date'      => 'required|string|max:100',
            'expiry_date'          => 'required|string|max:100',
            'batch_code'           => 'required|string|max:100',
            'website'              => 'required|string|max:255',
            'copies'               => 'required|integer|min:1|max:5000',
            'show_logo'            => 'nullable',
            'label_size'           => 'nullable|string|max:50',
        ]);

        $data['show_logo'] = $request->boolean('show_logo', true);

        // مقاس الملصق التفصيلي ثابت دائماً 100 × 100 مم لطابعة الباركود والكرتون
        $dim = [
            'key'   => '100x100',
            'name'  => '100 × 100 مم (ستيكر كرتون مربع)',
            'page'  => '100mm 100mm',
            'w'     => '100mm',
            'h'     => '100mm',
            'class' => 'size-100x100',
        ];
        $data['label_size'] = '100x100';

        // حفظ في جدول الملصقات المطبوعة
        PrintedLabel::create([
            'brand_name'           => $data['brand_name'],
            'item_name'            => $data['item_name'],
            'manufacturer'         => $data['manufacturer'],
            'manufacturer_address' => $data['manufacturer_address'],
            'exporter'             => $data['exporter'],
            'exporter_address'     => $data['exporter_address'],
            'importer'             => $data['importer'],
            'importer_address'     => $data['importer_address'],
            'production_date'      => $data['production_date'],
            'expiry_date'          => $data['expiry_date'],
            'batch_code'           => $data['batch_code'],
            'website'              => $data['website'],
            'copies'               => $data['copies'],
        ]);

        return view('labels.print_detailed', compact('data', 'dim'));
    }

    /**
     * طباعة الملصق الصغير المستطيل (50 × 25 مم)
     */
    public function printCompact(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'item_name'    => 'required|string|max:255',
            'date'         => 'required|string|max:100',
            'show_logo'    => 'nullable',
            'copies'       => 'required|integer|min:1|max:5000',
            'label_size'   => 'nullable|string|max:50',
        ]);

        $data['show_logo'] = $request->boolean('show_logo', true);

        // إعدادات مقاسات الملصق الصغير
        $compactPresets = [
            '50x25' => [
                'key'          => '50x25',
                'w'            => '50mm',
                'h'            => '25mm',
                'label'        => '50 × 25 مم (المقاس القياسي الصغير)',
                'company_font' => '13px',
                'item_font'    => '16.5px',
                'date_font'    => '11px',
                'logo_h'       => '6.3mm',
            ],
            '60x30' => [
                'key'          => '60x30',
                'w'            => '60mm',
                'h'            => '30mm',
                'label'        => '60 × 30 مم',
                'company_font' => '14px',
                'item_font'    => '18px',
                'date_font'    => '12px',
                'logo_h'       => '7.5mm',
            ],
            '70x35' => [
                'key'          => '70x35',
                'w'            => '70mm',
                'h'            => '35mm',
                'label'        => '70 × 35 مم',
                'company_font' => '15px',
                'item_font'    => '20px',
                'date_font'    => '13px',
                'logo_h'       => '9mm',
            ],
        ];

        $sizeKey = $request->input('label_size', '50x25');
        $dim = $compactPresets[$sizeKey] ?? $compactPresets['50x25'];

        // حفظ في جدول الملصقات المطبوعة
        PrintedLabel::create([
            'brand_name'      => $data['company_name'],
            'item_name'       => $data['item_name'],
            'exporter'        => $data['company_name'],
            'production_date' => $data['date'],
            'batch_code'      => 'COMPACT',
            'copies'          => $data['copies'],
        ]);

        return view('labels.print_compact', compact('data', 'dim'));
    }

    /**
     * معالجة نقطة التوافق القديمة لـ POST /labels/print
     */
    public function print(Request $request)
    {
        $labelType = $request->input('label_type');
        if ($labelType === 'compact') {
            return $this->printCompact($request);
        }
        return $this->printDetailed($request);
    }
}
