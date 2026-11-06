@extends('layouts.app')
@section('header_title', 'طباعة الملصق التفصيلي')

@section('content')
<div class="container mx-auto px-4 max-w-6xl animate-fade-in">
    
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg bg-pink-100 flex items-center justify-center text-pink-600">
                <i class="fas fa-file-invoice text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900 tracking-tight">طباعة الملصق التفصيلي</h2>
                <p class="text-sm text-gray-500 mt-0.5">ملصق تصديري كامل يملأ مقاس الورقة بالكامل بخط كبير وواضح في ورقة واحدة فقط</p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-gray-200 mb-6">
        <a href="{{ route('labels.detailed') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-pink-600 text-pink-600 flex items-center gap-2">
            <i class="fas fa-file-invoice"></i> الملصق التفصيلي الكامل
        </a>
        <a href="{{ route('labels.compact') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 flex items-center gap-2">
            <i class="fas fa-barcode"></i> الملصق الصغير (50 × 25 مم)
        </a>
        <a href="{{ route('labels.index') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 flex items-center gap-2">
            <i class="fas fa-history"></i> سجل الطباعة
        </a>
    </div>

    <form action="{{ route('labels.detailed.print') }}" method="POST" target="_blank"
        onsubmit="setTimeout(() => { let b = this.querySelector('button[type=submit]'); if(b){ b.disabled=false; b.classList.remove('opacity-75','cursor-not-allowed'); b.innerHTML = b.innerHTML.replace('<i class=\'fas fa-spinner fa-spin mx-1\'></i>', '').trim(); } if(typeof submittedForms !== 'undefined') submittedForms.delete(this); }, 1500);">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {{-- لوحة إدخال البيانات (7 أعمدة) --}}
            <div class="lg:col-span-7 bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
                
                <div class="border-b border-gray-100 pb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-sliders-h text-pink-600"></i> بيانات الملصق التصديري
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">يملأ مساحة الورقة بالكامل وبخط كبير في ورقة واحدة فقط.</p>
                    </div>

                    {{-- مقاس الستيكر ثابت 100 × 100 مم --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs bg-pink-50 text-pink-700 border border-pink-200 font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                            <i class="fas fa-barcode"></i> مقاس الستيكر: 100 × 100 مم
                        </span>
                        <input type="hidden" name="label_size" id="dt_label_size" value="100x100">
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- اسم البراند -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                اسم البراند <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="brand_name" id="dt_brand_name" required
                                value="{{ old('brand_name', '6 ستارز') }}"
                                oninput="updateDetailedPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-bold focus:outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500"
                                placeholder="مثال: 6 ستارز">
                        </div>

                        <!-- الصنف -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                الصنف <span class="text-red-500">*</span>
                            </label>
                            <select name="item_name" id="dt_item_name" required class="w-full">
                                <option value="">اختر أو اكتب اسم الصنف...</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->name_ar }}">{{ $item->name_ar }} ({{ $item->item_code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- الشركة المصنعة -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                الشركة المصنعة <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="manufacturer" id="dt_manufacturer" required
                                value="{{ old('manufacturer') }}"
                                oninput="updateDetailedPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-bold focus:outline-none focus:border-pink-500"
                                placeholder="مثال: شركة التصنيع الغذائي">
                        </div>

                        <!-- عنوان المصنع -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                عنوان المصنع <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="manufacturer_address" id="dt_manufacturer_address" required
                                value="{{ old('manufacturer_address') }}"
                                oninput="updateDetailedPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-pink-500"
                                placeholder="عنوان المصنع">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- الشركة المصدرة -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                الشركة المصدرة (شركتنا) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="exporter" id="dt_exporter" required
                                value="{{ old('exporter', $defaultCompanyName) }}"
                                oninput="updateDetailedPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-bold focus:outline-none focus:border-pink-500">
                        </div>

                        <!-- عنوان المصدر -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                عنوان المصدر <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="exporter_address" id="dt_exporter_address" required
                                value="{{ old('exporter_address', $defaultAddress) }}"
                                oninput="updateDetailedPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-pink-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- المستورد -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                اسم المستورد <span class="text-red-500">*</span>
                            </label>
                            <select name="importer" id="dt_importer" required class="w-full">
                                <option value="">اختر أو اكتب اسم المستورد...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->company_name }}">{{ $client->company_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- عنوان المستورد -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                عنوان المستورد <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="importer_address" id="dt_importer_address" required
                                value="{{ old('importer_address') }}"
                                oninput="updateDetailedPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-pink-500"
                                placeholder="الدولة، المدينة، الشارع">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- تاريخ الانتاج -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                تاريخ الانتاج <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="production_date" id="dt_prod_date" required
                                value="{{ old('production_date', now()->format('m/Y')) }}"
                                oninput="updateDetailedPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-mono text-center focus:outline-none focus:border-pink-500"
                                placeholder="مثال: 08/2026">
                        </div>

                        <!-- تاريخ الانتهاء -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                تاريخ الانتهاء <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="expiry_date" id="dt_exp_date" required
                                value="{{ old('expiry_date', now()->addYears(2)->format('m/Y')) }}"
                                oninput="updateDetailedPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-mono text-center focus:outline-none focus:border-pink-500"
                                placeholder="مثال: 08/2028">
                        </div>

                        <!-- كود التشغيلة -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                كود التشغيلة (Batch) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="batch_code" id="dt_batch_code" required
                                value="{{ old('batch_code', rand(100000, 999999)) }}"
                                oninput="updateDetailedPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-mono text-center font-bold focus:outline-none focus:border-pink-500"
                                placeholder="مثال: OP12EP908">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- الموقع الإلكتروني / الإيميل -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                الموقع الإلكتروني / الإيميل <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="website" id="dt_website" required
                                value="{{ old('website', $defaultWebsite) }}"
                                oninput="updateDetailedPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-mono text-center focus:outline-none focus:border-pink-500"
                                dir="ltr">
                        </div>

                        <!-- عدد النسخ -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                عدد النسخ <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="copies" min="1" max="5000" value="1" required
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm font-bold text-center focus:outline-none focus:border-pink-500">
                        </div>
                    </div>

                    <!-- تضمين اللوجو -->
                    <div class="pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700 select-none">
                            <input type="checkbox" name="show_logo" id="dt_show_logo" value="1" checked
                                onchange="updateDetailedPreview()"
                                class="w-4 h-4 text-pink-600 rounded border-gray-300 focus:ring-pink-500">
                            <span>تضمين شعار الشركة في رأس الملصق (اللوجو)</span>
                        </label>
                    </div>

                    <!-- تنبيه لطباعة ورقة واحدة كاملة -->
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-900 flex items-start gap-2.5">
                        <i class="fas fa-lightbulb text-amber-600 mt-0.5 text-sm shrink-0"></i>
                        <div class="leading-relaxed">
                            <strong>للحصول على ملصق يملأ الورقة بالكامل:</strong>
                            في شاشة الطباعة تأكد من ضبط <strong>الهوامش (Margins)</strong> على <strong>بلا (None)</strong>، وسيقوم النظام بتوزيع المحتوى بالكامل في ورقة واحدة فقط دون أي انقسام أو صفحات بيضاء زائدة.
                        </div>
                    </div>

                </div>

                <div class="border-t border-gray-100 pt-5 flex justify-end">
                    <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-gray-900 hover:bg-black text-white rounded-lg font-bold text-sm shadow-md transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-print text-base"></i> طباعة الملصق التفصيلي
                    </button>
                </div>
            </div>

            {{-- لوحة المعاينة الحية للملصق (5 أعمدة) --}}
            <div class="lg:col-span-5 bg-gray-50 border border-gray-200 rounded-xl p-5 space-y-3 sticky top-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                        <i class="fas fa-eye text-pink-600"></i> معاينة شكل الملصق
                    </span>
                    <span id="previewSizeBadge" class="text-[11px] font-bold px-2.5 py-0.5 rounded bg-pink-100 text-pink-800">
                        100 × 100 مم (مربع)
                    </span>
                </div>

                {{-- بطاقة المحاكاة الواقعية --}}
                <div class="flex justify-center items-center py-2">
                    <div id="dt_preview_card"
                        class="bg-white text-black border-2 border-dashed border-gray-400 rounded-md shadow-md p-4 transition-all duration-300 flex flex-col justify-between"
                        style="width: 320px; height: 320px; font-size: 12.5px;">
                        
                        {{-- رأس الملصق: اللوجو --}}
                        <div id="pv_dt_header" class="flex justify-end items-center pb-1">
                            <img id="pv_dt_logo" src="{{ asset('images/EFC-.png') }}" alt="Logo" class="h-10 w-auto object-contain">
                        </div>

                        {{-- المحتوى الرئيسي --}}
                        <div class="space-y-1.5 overflow-hidden my-auto">
                            <div id="pv_dt_title" class="font-black text-base border-b-2 border-black pb-1 leading-tight truncate">
                                6 ستارز - باذنجان مجمد
                            </div>

                            <div class="flex items-baseline gap-1.5 text-xs leading-tight truncate">
                                <span class="underline font-black shrink-0">شركة المصنعة:</span>
                                <span id="pv_dt_mfr" class="truncate font-bold">—</span>
                            </div>

                            <div class="flex items-baseline gap-1.5 text-xs leading-tight truncate">
                                <span class="underline font-black shrink-0">عنوان:</span>
                                <span id="pv_dt_mfr_addr" class="truncate font-bold">—</span>
                            </div>

                            <div class="flex items-baseline gap-1.5 text-xs leading-tight truncate">
                                <span class="underline font-black shrink-0">شركة المصدرة:</span>
                                <span id="pv_dt_exp" class="truncate font-bold">{{ $defaultCompanyName }}</span>
                            </div>

                            <div class="flex items-baseline gap-1.5 text-xs leading-tight truncate">
                                <span class="underline font-black shrink-0">عنوان:</span>
                                <span id="pv_dt_exp_addr" class="truncate font-bold">{{ $defaultAddress }}</span>
                            </div>

                            <div class="flex items-baseline gap-1.5 text-xs leading-tight truncate">
                                <span class="underline font-black shrink-0">مستورد:</span>
                                <span id="pv_dt_imp" class="truncate font-bold">—</span>
                            </div>

                            <div class="flex items-baseline gap-1.5 text-xs leading-tight truncate">
                                <span class="underline font-black shrink-0">عنوان:</span>
                                <span id="pv_dt_imp_addr" class="truncate font-bold">—</span>
                            </div>
                        </div>

                        {{-- التواريخ والتشغيلة --}}
                        <div class="border-t-2 border-dashed border-black pt-1.5 text-xs leading-tight space-y-1">
                            <div class="flex justify-between items-center">
                                <div><span class="underline font-black">انتاج:</span> <span id="pv_dt_pdate" class="font-mono font-bold">--</span></div>
                                <div><span class="underline font-black">انتهاء:</span> <span id="pv_dt_edate" class="font-mono font-bold">--</span></div>
                            </div>
                            <div><span class="underline font-black">تشغيلة:</span> <span id="pv_dt_batch" class="font-mono font-bold">--</span></div>
                        </div>

                        {{-- الفوتر --}}
                        <div id="pv_dt_web" class="border-t-2 border-black pt-1 text-center text-[11px] font-mono font-black truncate" dir="ltr">
                            {{ $defaultWebsite }}
                        </div>
                    </div>
                </div>

                <p class="text-[11px] text-gray-500 text-center leading-relaxed">
                    <i class="fas fa-check-circle text-green-600"></i>
                    الطباعة تملأ مقاس ستيكر الورقة بالكامل بخط كبير وواضح في ورقة واحدة.
                </p>
            </div>

        </div>
    </form>
</div>

<script>
let dtItemSelect = null;
let dtImpSelect  = null;

function updateDetailedPreview() {
    const brand    = document.getElementById('dt_brand_name')?.value || '6 ستارز';
    const mfr      = document.getElementById('dt_manufacturer')?.value || 'شركة المصنعة';
    const mfrAddr  = document.getElementById('dt_manufacturer_address')?.value || 'عنوان المصنع';
    const exp      = document.getElementById('dt_exporter')?.value || 'ايجيبيشن فودز';
    const expAddr  = document.getElementById('dt_exporter_address')?.value || 'اكتوبر , الجيزة';
    const impAddr  = document.getElementById('dt_importer_address')?.value || 'عنوان المستورد';
    const pdate    = document.getElementById('dt_prod_date')?.value || '';
    const edate    = document.getElementById('dt_exp_date')?.value || '';
    const batch    = document.getElementById('dt_batch_code')?.value || '';
    const web      = document.getElementById('dt_website')?.value || '';
    const showLogo = document.getElementById('dt_show_logo')?.checked;

    let itemVal = '';
    if (dtItemSelect) {
        itemVal = dtItemSelect.getValue() || '';
    } else {
        const itemEl = document.getElementById('dt_item_name');
        itemVal = itemEl ? itemEl.value : '';
    }

    let impVal = '';
    if (dtImpSelect) {
        impVal = dtImpSelect.getValue() || '';
    } else {
        const impEl = document.getElementById('dt_importer');
        impVal = impEl ? impEl.value : '';
    }

    // Update Elements
    const pvTitle    = document.getElementById('pv_dt_title');
    const pvMfr      = document.getElementById('pv_dt_mfr');
    const pvMfrAddr  = document.getElementById('pv_dt_mfr_addr');
    const pvExp      = document.getElementById('pv_dt_exp');
    const pvExpAddr  = document.getElementById('pv_dt_exp_addr');
    const pvImp      = document.getElementById('pv_dt_imp');
    const pvImpAddr  = document.getElementById('pv_dt_imp_addr');
    const pvPdate    = document.getElementById('pv_dt_pdate');
    const pvEdate    = document.getElementById('pv_dt_edate');
    const pvBatch    = document.getElementById('pv_dt_batch');
    const pvWeb      = document.getElementById('pv_dt_web');
    const pvLogo     = document.getElementById('pv_dt_logo');

    if (pvTitle)   pvTitle.textContent = brand + ' - ' + (itemVal || 'اسم الصنف');
    if (pvMfr)     pvMfr.textContent = mfr;
    if (pvMfrAddr) pvMfrAddr.textContent = mfrAddr;
    if (pvExp)     pvExp.textContent = exp;
    if (pvExpAddr) pvExpAddr.textContent = expAddr;
    if (pvImp)     pvImp.textContent = impVal || 'اسم المستورد';
    if (pvImpAddr) pvImpAddr.textContent = impAddr;
    if (pvPdate)   pvPdate.textContent = pdate;
    if (pvEdate)   pvEdate.textContent = edate;
    if (pvBatch)   pvBatch.textContent = batch;
    if (pvWeb)     pvWeb.textContent = web;
    if (pvLogo)    pvLogo.style.display = showLogo ? 'block' : 'none';

    // كارت المعاينة ثابت دائماً على مقاس 100 × 100 مم
    const pvBadge  = document.getElementById('previewSizeBadge');
    const pvCard   = document.getElementById('dt_preview_card');

    if (pvBadge) pvBadge.textContent = '100 × 100 مم (مربع)';
    if (pvCard) {
        pvCard.style.width = '320px';
        pvCard.style.height = '320px';
        pvCard.style.fontSize = '12px';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof TomSelect !== 'undefined') {
        dtItemSelect = new TomSelect('#dt_item_name', {
            create: true,
            sortField: { field: 'text', direction: 'asc' },
            placeholder: 'اختر أو اكتب اسم الصنف...',
            onChange: function () {
                updateDetailedPreview();
            }
        });

        dtImpSelect = new TomSelect('#dt_importer', {
            create: true,
            sortField: { field: 'text', direction: 'asc' },
            placeholder: 'اختر أو اكتب اسم المستورد...',
            onChange: function () {
                updateDetailedPreview();
            }
        });
    }

    updateDetailedPreview();
});
</script>
@endsection
