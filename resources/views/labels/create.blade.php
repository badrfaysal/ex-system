@extends('layouts.app')
@section('header_title', 'طباعة الملصقات')

@section('content')
<div class="container mx-auto px-4 max-w-5xl animate-fade-in">
    
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg bg-pink-100 flex items-center justify-center text-pink-600">
                <i class="fas fa-tag text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900 tracking-tight">طباعة الملصقات</h2>
                <p class="text-sm text-gray-500 mt-0.5">إدارة وطباعة ملصقات المنتجات وستيكرات الكرتون والعبوات</p>
            </div>
        </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="flex border-b border-gray-200 mb-6">
        <a href="{{ route('labels.create') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-pink-600 text-pink-600">
            <i class="fas fa-plus ml-2"></i> إصدار ملصق
        </a>
        <a href="{{ route('labels.index') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
            <i class="fas fa-history ml-2"></i> سجل الطباعة
        </a>
    </div>

    <!-- Mode Selector: Compact vs Detailed -->
    <div class="bg-gradient-to-r from-pink-50 to-rose-50 border border-pink-200 rounded-xl p-3 mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-gray-600">نوع وشكل الملصق:</span>
            <div class="inline-flex rounded-lg border border-pink-300 bg-white p-1 shadow-sm">
                <button type="button" id="tabBtnCompact" onclick="switchLabelMode('compact')"
                    class="px-4 py-2 rounded-md text-xs font-bold transition-all flex items-center gap-2 bg-pink-600 text-white shadow-sm">
                    <i class="fas fa-barcode"></i> ملصق صغير مستطيل (شركة + صنف + تاريخ + لوجو)
                </button>
                <button type="button" id="tabBtnStandard" onclick="switchLabelMode('standard')"
                    class="px-4 py-2 rounded-md text-xs font-bold transition-all flex items-center gap-2 text-gray-600 hover:text-pink-600">
                    <i class="fas fa-file-invoice"></i> ملصق تفصيلي / تصديري كامل
                </button>
            </div>
        </div>
        <span class="text-[11px] text-pink-700 font-semibold hidden md:inline">
            <i class="fas fa-check-circle me-1"></i> مخصص لطابعات الملصقات والباركود الحرارية
        </span>
    </div>

    {{-- ==================== 1. نموذج الملصق الصغير المستطيل (الجديد) ==================== --}}
    <div id="sectionCompact" class="animate-fade-in">
        <form action="{{ route('labels.print') }}" method="POST" target="_blank"
            onsubmit="setTimeout(() => { let b = this.querySelector('button[type=submit]'); if(b){ b.disabled=false; b.classList.remove('opacity-75','cursor-not-allowed'); b.innerHTML = b.innerHTML.replace('<i class=\'fas fa-spinner fa-spin mx-1\'></i>', '').trim(); } if(typeof submittedForms !== 'undefined') submittedForms.delete(this); }, 1500);">
            @csrf
            <input type="hidden" name="label_type" value="compact">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                {{-- لوحة إدخال البيانات --}}
                <div class="lg:col-span-7 bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-sliders-h text-pink-600"></i> بيانات الملصق الصغير
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">يحتوي فقط على اسم الشركة، واسم الصنف، والتاريخ، مع لوجو الشركة في مستطيل مناسب لورق طابعات الستيكر.</p>
                    </div>

                    <div class="space-y-4">
                        <!-- اسم الشركة -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                اسم الشركة <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="company_name" id="compact_company_name" required
                                value="{{ old('company_name', $defaultCompanyName) }}"
                                oninput="updateCompactPreview()"
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 font-bold text-gray-800"
                                placeholder="مثال: ايجيبيشن فودز">
                        </div>

                        <!-- اسم الصنف -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                اسم الصنف <span class="text-red-500">*</span>
                            </label>
                            <select name="item_name" id="compact_item_name" required class="w-full">
                                <option value="">اختر أو اكتب اسم الصنف...</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->name_ar }}">{{ $item->name_ar }} ({{ $item->item_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- التاريخ -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                    التاريخ <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="date" id="compact_date" required
                                    value="{{ old('date', $todayDate) }}"
                                    oninput="updateCompactPreview()"
                                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-pink-500 focus:ring-1 focus:ring-pink-500 font-mono text-center font-bold text-gray-800"
                                    placeholder="YYYY/MM/DD">
                            </div>

                            <!-- مقاس ورق الطباعة -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                    مقاس ورق الطباعة (الستيكر)
                                </label>
                                <select name="size" id="compact_size" onchange="updateCompactPreview()"
                                    class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:border-pink-500 bg-white font-medium text-gray-700">
                                    <option value="60x30" selected>60 × 30 مم (مقاس مستطيل قياسي - موصى به)</option>
                                    <option value="50x25">50 × 25 مم (مقاس مستطيل صغير)</option>
                                    <option value="70x35">70 × 35 مم (مقاس مستطيل متوسط)</option>
                                    <option value="80x40">80 × 40 مم (مقاس مستطيل كبير)</option>
                                    <option value="auto">تلقائي (حسب إعدادات طابعة الملصقات)</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                            <!-- خيار إظهار اللوجو -->
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700 select-none">
                                <input type="checkbox" name="show_logo" id="compact_show_logo" value="1" checked
                                    onchange="updateCompactPreview()"
                                    class="w-4 h-4 text-pink-600 rounded border-gray-300 focus:ring-pink-500">
                                <span>تضمين شعار الشركة (اللوجو)</span>
                            </label>

                            <!-- عدد النسخ -->
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-bold text-gray-700">عدد النسخ:</label>
                                <input type="number" name="copies" min="1" max="5000" value="1" required
                                    class="w-20 px-2.5 py-1.5 border border-gray-300 rounded-lg text-sm text-center font-bold focus:outline-none focus:border-pink-500">
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-5 flex justify-end">
                        <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-bold text-sm shadow-md transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-print text-base"></i> طباعة الملصق الصغير
                        </button>
                    </div>
                </div>

                {{-- لوحة المعاينة الحية للستيكر --}}
                <div class="lg:col-span-5 bg-gray-50 border border-gray-200 rounded-xl p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                            <i class="fas fa-eye text-pink-600"></i> معاينة شكل الملصق
                        </span>
                        <span id="previewSizeBadge" class="text-[10px] font-mono px-2 py-0.5 rounded bg-gray-200 text-gray-600 font-bold">
                            60 × 30 مم
                        </span>
                    </div>

                    {{-- الستيكر المستطيل الواقعي --}}
                    <div class="flex justify-center items-center py-4">
                        <div id="previewCard"
                            class="bg-white text-black border-2 border-dashed border-gray-400 rounded-md shadow-md p-3 transition-all duration-300 flex flex-col justify-between"
                            style="width: 260px; min-height: 140px; aspect-ratio: 2 / 1;">
                            
                            {{-- رأس الملصق: اسم الشركة + اللوجو --}}
                            <div class="flex items-center justify-between gap-2 border-b border-black pb-1.5">
                                <span id="pvCompany" class="font-extrabold text-sm text-black truncate leading-tight">
                                    {{ $defaultCompanyName }}
                                </span>
                                <img id="pvLogo" src="{{ asset('images/EFC-.png') }}" alt="Logo" class="h-6 w-auto object-contain">
                            </div>

                            {{-- منتصف الملصق: اسم الصنف بخط عريض وواضح --}}
                            <div class="my-auto py-2 text-center">
                                <span id="pvItem" class="font-black text-base text-black block leading-snug line-clamp-2">
                                    اسم الصنف يظهر هنا
                                </span>
                            </div>

                            {{-- أسفل الملصق: التاريخ --}}
                            <div class="border-t border-black pt-1 flex items-center justify-between text-[11px] font-bold text-black">
                                <span>التاريخ:</span>
                                <span id="pvDate" class="font-mono" dir="ltr">{{ $todayDate }}</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-[11px] text-gray-500 text-center leading-relaxed">
                        <i class="fas fa-info-circle text-pink-500"></i>
                        المعاينة مطابقة تماماً للملصق المطبوع. عند الضغط على <strong>طباعة</strong>، ستفتح شاشة الطباعة المباشرة لطابعتك بدون أي هوامش زائدة.
                    </p>
                </div>

            </div>
        </form>
    </div>


    {{-- ==================== 2. نموذج الملصق التصديري التفصيلي (السابق) ==================== --}}
    <div id="sectionStandard" class="hidden animate-fade-in">
        <form action="{{ route('labels.print') }}" method="POST" target="_blank"
            onsubmit="setTimeout(() => { let b = this.querySelector('button[type=submit]'); if(b){ b.disabled=false; b.classList.remove('opacity-75','cursor-not-allowed'); b.innerHTML = b.innerHTML.replace('<i class=\'fas fa-spinner fa-spin mx-1\'></i>', '').trim(); } if(typeof submittedForms !== 'undefined') submittedForms.delete(this); }, 1500);">
            @csrf
            <input type="hidden" name="label_type" value="standard">
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
                
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-file-invoice text-pink-600"></i> بيانات الملصق التصديري الكامل
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">يحتوي على كافة بيانات المصنع والمصدر والمستورد والتشغيلة وتواريخ الصلاحية والويب سايت.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Brand & Item -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">اسم البراند</label>
                        <input type="text" name="brand_name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="مثال: 7Sta">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">الصنف</label>
                        <select name="item_name" id="item_name_standard" class="w-full">
                            <option value="">اختر الصنف...</option>
                            @foreach($items as $item)
                                <option value="{{ $item->name_ar }}">{{ $item->name_ar }} ({{ $item->item_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Manufacturer -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">الشركة المصنعة</label>
                        <input type="text" name="manufacturer" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="الشركة المصنعة">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">عنوان المصنع</label>
                        <input type="text" name="manufacturer_address" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="عنوان المصنع">
                    </div>

                    <!-- Exporter -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">الشركة المصدرة (شركتنا)</label>
                        <input type="text" name="exporter" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" value="ايجيبيشن فودز">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">عنوان المصدر</label>
                        <input type="text" name="exporter_address" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" value="اكتوبر , الجيزة , جمهورية مصر العربية">
                    </div>

                    <!-- Importer -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">اسم المستورد</label>
                        <select name="importer" id="importer" class="w-full">
                            <option value="">اختر اسم المستورد...</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->company_name }}">{{ $client->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">عنوان المستورد</label>
                        <input type="text" name="importer_address" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="عنوان المستورد">
                    </div>

                    <!-- Dates & Batch -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">تاريخ الانتاج</label>
                        <input type="text" name="production_date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="مثال: 2026/08">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">تاريخ الانتهاء</label>
                        <input type="text" name="expiry_date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="مثال: 2027/10">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">كود التشغيلة (Batch)</label>
                        <input type="text" name="batch_code" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="مثال: OP12EP908">
                    </div>
                    
                    <!-- Website -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">الويب سايت / الإيميل</label>
                        <input type="text" name="website" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" value="WWW.efcexport.com / info@efcexport.com">
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6 mt-6 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-bold text-gray-700">عدد النسخ المطلوبة:</label>
                        <input type="number" name="copies" min="1" value="1" class="w-24 px-3 py-2 border border-gray-300 rounded-md text-center focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                    </div>
                    <button type="submit" class="px-8 py-2.5 bg-gray-800 hover:bg-gray-900 border border-transparent rounded-md text-white font-bold shadow-md flex items-center gap-2">
                        <i class="fas fa-print text-lg"></i> حفظ وطباعة الملصق التفصيلي
                    </button>
                </div>
                
            </div>
        </form>
    </div>

</div>

<script>
let compactItemSelectInstance = null;

function switchLabelMode(mode) {
    const secCompact  = document.getElementById('sectionCompact');
    const secStandard = document.getElementById('sectionStandard');
    const btnCompact  = document.getElementById('tabBtnCompact');
    const btnStandard = document.getElementById('tabBtnStandard');

    if (mode === 'compact') {
        secCompact.classList.remove('hidden');
        secStandard.classList.add('hidden');
        btnCompact.className = 'px-4 py-2 rounded-md text-xs font-bold transition-all flex items-center gap-2 bg-pink-600 text-white shadow-sm';
        btnStandard.className = 'px-4 py-2 rounded-md text-xs font-bold transition-all flex items-center gap-2 text-gray-600 hover:text-pink-600';
    } else {
        secCompact.classList.add('hidden');
        secStandard.classList.remove('hidden');
        btnStandard.className = 'px-4 py-2 rounded-md text-xs font-bold transition-all flex items-center gap-2 bg-pink-600 text-white shadow-sm';
        btnCompact.className = 'px-4 py-2 rounded-md text-xs font-bold transition-all flex items-center gap-2 text-gray-600 hover:text-pink-600';
    }
}

function updateCompactPreview() {
    const compName = document.getElementById('compact_company_name')?.value || 'ايجيبيشن فودز';
    const dateVal  = document.getElementById('compact_date')?.value || '';
    const showLogo = document.getElementById('compact_show_logo')?.checked;
    const sizeVal  = document.getElementById('compact_size')?.value || '60x30';

    let itemVal = '';
    if (compactItemSelectInstance) {
        itemVal = compactItemSelectInstance.getValue() || '';
    } else {
        const itemEl = document.getElementById('compact_item_name');
        itemVal = itemEl ? itemEl.value : '';
    }

    const pvCompany = document.getElementById('pvCompany');
    const pvItem    = document.getElementById('pvItem');
    const pvDate    = document.getElementById('pvDate');
    const pvLogo    = document.getElementById('pvLogo');
    const pvBadge   = document.getElementById('previewSizeBadge');
    const pvCard    = document.getElementById('previewCard');

    if (pvCompany) pvCompany.textContent = compName;
    if (pvItem) pvItem.textContent = itemVal ? itemVal : 'اسم الصنف يظهر هنا';
    if (pvDate) pvDate.textContent = dateVal;
    if (pvLogo) pvLogo.style.display = showLogo ? 'block' : 'none';

    // تحديث شارة المقاس وأبعاد كارت المعاينة
    const sizeMap = {
        '50x25': { text: '50 × 25 مم', w: '240px', minH: '120px' },
        '60x30': { text: '60 × 30 مم', w: '260px', minH: '140px' },
        '70x35': { text: '70 × 35 مم', w: '280px', minH: '150px' },
        '80x40': { text: '80 × 40 مم', w: '300px', minH: '160px' },
        'auto':  { text: 'تلقائي (حسب الطابعة)', w: '260px', minH: '140px' },
    };
    const sInfo = sizeMap[sizeVal] || sizeMap['60x30'];
    if (pvBadge) pvBadge.textContent = sInfo.text;
    if (pvCard) {
        pvCard.style.width = sInfo.w;
        pvCard.style.minHeight = sInfo.minH;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof TomSelect !== 'undefined') {
        compactItemSelectInstance = new TomSelect('#compact_item_name', {
            create: true,
            sortField: { field: 'text', direction: 'asc' },
            placeholder: 'اختر أو اكتب اسم الصنف...',
            onChange: function () {
                updateCompactPreview();
            }
        });

        new TomSelect('#item_name_standard', {
            create: true,
            sortField: { field: 'text', direction: 'asc' },
            placeholder: 'اختر أو اكتب اسم الصنف...'
        });
        
        new TomSelect('#importer', {
            create: true,
            sortField: { field: 'text', direction: 'asc' },
            placeholder: 'اختر أو اكتب اسم المستورد...'
        });
    }

    updateCompactPreview();
});
</script>
@endsection
