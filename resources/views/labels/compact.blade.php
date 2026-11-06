@extends('layouts.app')
@section('header_title', 'طباعة الملصق الصغير')

@section('content')
<div class="container mx-auto px-4 max-w-6xl animate-fade-in">
    
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg bg-pink-100 flex items-center justify-center text-pink-600">
                <i class="fas fa-barcode text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900 tracking-tight">طباعة الملصق الصغير</h2>
                <p class="text-sm text-gray-500 mt-0.5">ملصق باركود وستيكر صغير مستطيل (50 × 25 مم) يحتوي على اسم الشركة، الصنف، التاريخ، والشعار</p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-gray-200 mb-6">
        <a href="{{ route('labels.detailed') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 flex items-center gap-2">
            <i class="fas fa-file-invoice"></i> الملصق التفصيلي الكامل
        </a>
        <a href="{{ route('labels.compact') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-pink-600 text-pink-600 flex items-center gap-2">
            <i class="fas fa-barcode"></i> الملصق الصغير (50 × 25 مم)
        </a>
        <a href="{{ route('labels.index') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 flex items-center gap-2">
            <i class="fas fa-history"></i> سجل الطباعة
        </a>
    </div>

    <form action="{{ route('labels.compact.print') }}" method="POST" target="_blank"
        onsubmit="setTimeout(() => { let b = this.querySelector('button[type=submit]'); if(b){ b.disabled=false; b.classList.remove('opacity-75','cursor-not-allowed'); b.innerHTML = b.innerHTML.replace('<i class=\'fas fa-spinner fa-spin mx-1\'></i>', '').trim(); } if(typeof submittedForms !== 'undefined') submittedForms.delete(this); }, 1500);">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {{-- لوحة إدخال البيانات (7 أعمدة) --}}
            <div class="lg:col-span-7 bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-5">
                
                <div class="border-b border-gray-100 pb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-sliders-h text-pink-600"></i> بيانات الملصق الصغير
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">يحتوي فقط على اسم الشركة، واسم الصنف، والتاريخ، مع لوجو الشركة في مستطيل مناسب لورق طابعات الستيكر.</p>
                    </div>

                    {{-- اختيار المقاس --}}
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-700">المقاس:</label>
                        <select name="label_size" id="label_size_compact" onchange="updateCompactPreview()"
                            class="px-3 py-1.5 border border-pink-300 bg-pink-50 rounded-lg text-xs font-bold text-pink-900 focus:outline-none focus:ring-1 focus:ring-pink-500">
                            <option value="50x25" selected>50 × 25 مم (القياسي الشائع)</option>
                            <option value="60x30">60 × 30 مم</option>
                            <option value="70x35">70 × 35 مم</option>
                        </select>
                    </div>
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

                        <!-- عدد النسخ -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                عدد النسخ <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="copies" min="1" max="5000" value="1" required
                                class="w-full px-3.5 py-2 border border-gray-300 rounded-lg text-sm text-center font-bold focus:outline-none focus:border-pink-500">
                        </div>
                    </div>

                    <div class="pt-1">
                        <!-- خيار إظهار اللوجو -->
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-bold text-gray-700 select-none">
                            <input type="checkbox" name="show_logo" id="compact_show_logo" value="1" checked
                                onchange="updateCompactPreview()"
                                class="w-4 h-4 text-pink-600 rounded border-gray-300 focus:ring-pink-500">
                            <span>تضمين شعار الشركة في رأس الستيكر (اللوجو)</span>
                        </label>
                    </div>
                </div>

                {{-- نصيحة ضبط الطابعة --}}
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800 flex items-start gap-2">
                    <i class="fas fa-lightbulb text-amber-500 mt-0.5 shrink-0"></i>
                    <div class="leading-relaxed">
                        <strong>ملاحظة هامة لطابعة الباركود:</strong> في نافذة الطباعة المنبثقة، تأكد من اختيار مقاس الورق الصغير <strong>(50×25 مم / USER)</strong> وضبط <strong>الهوامش (Margins)</strong> على <strong>بدون (None)</strong> ليطبع في منتصف الستيكر بالضبط وبدون انقسام.
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-5 flex justify-end">
                    <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-bold text-sm shadow-md transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-print text-base"></i> طباعة الملصق الصغير
                    </button>
                </div>
            </div>

            {{-- لوحة المعاينة الحية للستيكر الصغير (5 أعمدة) --}}
            <div class="lg:col-span-5 bg-gray-50 border border-gray-200 rounded-xl p-5 space-y-3 sticky top-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                        <i class="fas fa-eye text-pink-600"></i> معاينة شكل الملصق الصغير
                    </span>
                    <span id="previewSizeBadge" class="text-[10px] font-mono px-2 py-0.5 rounded bg-pink-100 text-pink-800 font-bold">
                        بالعرض (50 × 25 مم)
                    </span>
                </div>

                {{-- الستيكر المستطيل الواقعي --}}
                <div class="flex justify-center items-center py-4">
                    <div id="previewCard"
                        class="bg-white text-black border-2 border-dashed border-gray-400 rounded-md shadow-md px-4 py-2.5 transition-all duration-300 flex items-center justify-center"
                        style="width: 270px; height: 135px; aspect-ratio: 2 / 1;">
                        
                        <div class="w-full h-full flex flex-col justify-between">
                            {{-- رأس الملصق: اسم الشركة + اللوجو --}}
                            <div id="pvHeader" class="flex items-center justify-between gap-2 border-b-2 border-black pb-1">
                                <span id="pvCompany" class="font-black text-sm text-black truncate leading-tight">
                                    {{ $defaultCompanyName }}
                                </span>
                                <img id="pvLogo" src="{{ asset('images/EFC-.png') }}" alt="Logo" class="h-7 w-auto object-contain">
                            </div>

                            {{-- منتصف الملصق: اسم الصنف --}}
                            <div class="my-auto py-1 text-center flex items-center justify-center">
                                <span id="pvItem" class="font-black text-lg text-black block leading-tight line-clamp-2 text-center">
                                    اسم الصنف يظهر هنا
                                </span>
                            </div>

                            {{-- أسفل الملصق: التاريخ --}}
                            <div class="border-t-2 border-black pt-1 flex items-center justify-center gap-2 text-[11px] font-black text-black">
                                <span>التاريخ:</span>
                                <span id="pvDate" class="font-mono font-black" dir="ltr">{{ $todayDate }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-[11px] text-gray-500 text-center leading-relaxed">
                    <i class="fas fa-info-circle text-pink-500"></i>
                    المعاينة مطابقة تماماً للملصق المطبوع على رول ستيكرات 50×25 مم.
                </p>
            </div>

        </div>
    </form>
</div>

<script>
let compactItemSelectInstance = null;

function updateCompactPreview() {
    const compName = document.getElementById('compact_company_name')?.value || 'ايجيبيشن فودز';
    const dateVal  = document.getElementById('compact_date')?.value || '';
    const showLogo = document.getElementById('compact_show_logo')?.checked;
    const sizeVal  = document.getElementById('label_size_compact')?.value || '50x25';

    let itemVal = '';
    if (compactItemSelectInstance) {
        itemVal = compactItemSelectInstance.getValue() || '';
    } else {
        const itemEl = document.getElementById('compact_item_name');
        itemVal = itemEl ? itemEl.value : '';
    }

    const pvHeader  = document.getElementById('pvHeader');
    const pvCompany = document.getElementById('pvCompany');
    const pvItem    = document.getElementById('pvItem');
    const pvDate    = document.getElementById('pvDate');
    const pvLogo    = document.getElementById('pvLogo');
    const badge     = document.getElementById('previewSizeBadge');
    const card      = document.getElementById('previewCard');

    if (pvCompany) pvCompany.textContent = compName;
    if (pvItem) pvItem.textContent = itemVal ? itemVal : 'اسم الصنف يظهر هنا';
    if (pvDate) pvDate.textContent = dateVal;
    if (pvLogo) pvLogo.style.display = showLogo ? 'block' : 'none';
    if (pvHeader) {
        pvHeader.classList.toggle('justify-between', !!showLogo);
        pvHeader.classList.toggle('justify-center', !showLogo);
    }

    if (badge && card) {
        if (sizeVal === '50x25') {
            badge.textContent = 'بالعرض (50 × 25 مم)';
            card.style.width  = '270px';
            card.style.height = '135px';
        } else if (sizeVal === '60x30') {
            badge.textContent = 'بالعرض (60 × 30 مم)';
            card.style.width  = '290px';
            card.style.height = '145px';
        } else if (sizeVal === '70x35') {
            badge.textContent = 'بالعرض (70 × 35 مم)';
            card.style.width  = '310px';
            card.style.height = '155px';
        }
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
    }

    updateCompactPreview();
});
</script>
@endsection
