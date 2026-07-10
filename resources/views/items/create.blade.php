@extends('layouts.app')
@section('header_title', __('messages.items.add_title'))

@section('content')
<div class="container mx-auto px-4 max-w-6xl animate-fade-in">
    
    {{-- ترويسة الشاشة --}}
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
                <i class="fas fa-box-open text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900 tracking-tight">{{ __('messages.items.add_title') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('messages.items.add_sub') }}</p>
            </div>
        </div>
        <a href="{{ route('items.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-arrow-right text-sm"></i> {{ __('messages.common.back_list') }}
        </a>
    </div>

    {{-- النموذج الرئيسي (مهم جداً وجود enctype لرفع الصور المتعددة) --}}
    <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="space-y-6">
            
            {{-- القسم الأول: البيانات الأساسية ووحدات القياس --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-2 pb-3 mb-5 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
                    <i class="fas fa-info-circle"></i>
                    <span>1. {{ __('messages.items.s1') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                    <div>
                        <label for="item_code" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_code') }}</label>
                        <input type="text" id="item_code" name="item_code" value="{{ old('item_code') }}" placeholder="{{ __('messages.items.f_code_ph') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] bg-gray-50">
                    </div>

                    <div>
                        <label for="barcode" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_barcode') }}</label>
                        <input type="text" id="barcode" name="barcode" value="{{ old('barcode') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                    </div>

                    <div class="md:col-span-2">
                        <label for="name_ar" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_name_ar') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                    </div>

                    <div class="md:col-span-2">
                        <label for="name_en" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_name_en') }}</label>
                        <input type="text" id="name_en" name="name_en" value="{{ old('name_en') }}" dir="ltr"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] text-right">
                    </div>

                    {{-- المجموعة الرئيسية --}}
                    <div>
                        <label for="item_group" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_group') }}</label>
                        <select id="item_group" name="item_group" data-search
                            onchange="filterSubCategories(this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                            <option value="">{{ __('messages.options.item_group.choose') }}</option>
                            @foreach($itemGroups as $group)
                                <option value="{{ $group->key_value }}" {{ old('item_group') == $group->key_value ? 'selected' : '' }}>
                                    {{ $group->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- المجموعة الفرعية — تتغير ديناميكياً حسب المجموعة الرئيسية --}}
                    <div>
                        <label for="sub_category" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_sub') }}</label>
                        <select id="sub_category" name="sub_category"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                            <option value="">{{ __('messages.items.f_sub_ph') }}</option>
                            @foreach($itemSubGroups as $sub)
                                <option value="{{ $sub->key_value }}"
                                    data-parent="{{ $sub->parent_key ?? '' }}"
                                    {{ old('sub_category') == $sub->key_value ? 'selected' : '' }}>
                                    {{ $sub->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- وحدة القياس الأساسية (ديناميكي) --}}
                    <div class="md:col-span-4">
                        <label for="base_uom" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_uom') }} <span class="text-red-500">*</span></label>
                        <select id="base_uom" name="base_uom" required class="w-full md:w-1/4 px-4 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                            <option value="" disabled selected>{{ __('messages.options.uom.choose') }}</option>
                            @foreach($uoms as $uom)
                                <option value="{{ $uom->key_value }}" {{ old('base_uom') == $uom->key_value ? 'selected' : '' }}>
                                    {{ $uom->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- القسم الثاني: بيانات المخازن --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-2 pb-3 mb-5 border-b border-gray-100 text-[#005B9F] font-bold text-lg">
                    <i class="fas fa-warehouse"></i>
                    <span>2. {{ __('messages.items.s2') }}</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label for="reorder_point" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_reorder') }}</label>
                        <input type="number" id="reorder_point" name="reorder_point" value="{{ old('reorder_point', 0) }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                    </div>
                    <div>
                        <label for="min_stock" class="block text-sm font-semibold text-gray-700 mb-1.5 text-red-600">{{ __('messages.items.f_min') }}</label>
                        <input type="number" id="min_stock" name="min_stock" value="{{ old('min_stock', 0) }}" class="w-full px-4 py-2 border border-red-300 rounded-md focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                    </div>
                    <div>
                        <label for="max_stock" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_max') }}</label>
                        <input type="number" id="max_stock" name="max_stock" value="{{ old('max_stock') }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                    </div>
                </div>
            </div>

            {{-- القسم الثالث: المشتريات والموردين --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-2 pb-3 mb-5 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
                    <i class="fas fa-shopping-cart"></i>
                    <span>3. {{ __('messages.items.s3') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                    <div class="md:col-span-2">
                        <label for="default_vendor_id" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_vendor') }}</label>
                        <select id="default_vendor_id" name="default_vendor_id" data-search class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                            <option value="">{{ __('messages.items.f_vendor_ph') }}</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('default_vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->name_ar }} - ({{ $vendor->vendor_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="supplier_part_number" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_supplier') }}</label>
                        <input type="text" id="supplier_part_number" name="supplier_part_number" value="{{ old('supplier_part_number') }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                    </div>

                    <div>
                        <label for="moq" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_moq') }}</label>
                        <input type="number" id="moq" name="moq" value="{{ old('moq', 1) }}" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                    </div>

                    {{-- فترة التوريد المتوقعة --}}
                    <div class="md:col-span-2">
                        <label for="lead_time_days" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_lead') }}</label>
                        <div class="relative">
                            <input type="number" id="lead_time_days" name="lead_time_days" value="{{ old('lead_time_days') }}" class="w-full px-4 py-2 border border-gray-300 rounded-md pl-16 focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pr-3 text-sm text-gray-500 bg-gray-100 border-r border-gray-300 rounded-l-md font-medium">{{ __('messages.common.days') }}</div>
                        </div>
                    </div>

                    {{-- الموردين المعتمدين لتوريد هذا الصنف --}}
                    <div class="md:col-span-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-truck text-[#005B9F]"></i> {{ __('messages.items.f_vendors') }}
                        </label>
                        <select id="vendorMultiSelect" name="approved_vendors[]" multiple
                            class="w-full border border-gray-300 rounded-md bg-white focus:outline-none">
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}"
                                    {{ in_array($vendor->id, old('approved_vendors', [])) ? 'selected' : '' }}>
                                    {{ $vendor->name_ar }} — {{ $vendor->vendor_code }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-500 mt-1.5"><i class="fas fa-info-circle"></i> {{ __('messages.items.f_vendors_h') }}</p>
                    </div>
                </div>
            </div>

            {{-- القسم الرابع: الصور المتعددة وحالة الصنف --}}
            <div class="bg-gray-50 rounded-lg border border-gray-200 p-6 shadow-sm">
                <div class="flex items-center justify-between pb-3 mb-5 border-b border-gray-200">
                    <div class="flex items-center gap-2 text-gray-800 font-bold text-lg">
                        <i class="fas fa-images text-gray-500"></i>
                        <span>4. {{ __('messages.items.s4') }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_status') }} <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-md bg-white focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F]">
                            <option value="" disabled selected>{{ __('messages.options.item_status.choose') }}</option>
                            @foreach($itemStatuses as $statusOption)
                                <option value="{{ $statusOption->key_value }}" {{ old('status') == $statusOption->key_value ? 'selected' : '' }}>
                                    {{ $statusOption->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_attachment') }}</label>
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx" class="w-full px-3 py-1.5 border border-gray-300 rounded-md bg-white text-sm focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F]">
                    </div>
                </div>

                {{-- ألبوم الصور مع live preview --}}
                <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-800">{{ __('messages.items.qc_gallery') }}</label>
                            <p class="text-xs text-gray-500 mt-1">{{ __('messages.items.qc_hint') }}</p>
                        </div>
                        <button type="button" onclick="addImageRow()" class="px-4 py-2 bg-[#005B9F] text-white text-xs font-bold rounded-md hover:bg-[#004680] transition-colors flex items-center gap-2 shadow-sm">
                            <i class="fas fa-plus"></i> {{ __('messages.items.add_image') }}
                        </button>
                    </div>

                    <div id="images_container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        {{-- بطاقة الصورة الافتراضية الأولى --}}
                        <div class="image-row bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-4 flex flex-col gap-3 transition-all hover:border-[#005B9F]/40">
                            <div class="relative w-full aspect-video bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                                <img src="" alt="" class="preview-img w-full h-full object-cover hidden rounded-lg">
                                <div class="preview-placeholder flex flex-col items-center text-gray-400 gap-2">
                                    <i class="fas fa-image text-3xl"></i>
                                    <span class="text-xs">{{ __('messages.items.img_preview') }}</span>
                                </div>
                            </div>
                            <input type="file" name="item_images[]" accept="image/*"
                                onchange="previewImage(this)"
                                class="w-full text-xs border border-gray-300 bg-white rounded-md file:py-1.5 file:px-3 file:border-0 file:text-xs file:font-semibold file:bg-[#005B9F] file:text-white hover:file:bg-[#004680] cursor-pointer">
                            <select name="image_categories[]" class="w-full px-3 py-2 border border-gray-300 rounded-md text-xs bg-white focus:outline-none focus:border-[#005B9F]">
                                <option value="main">{{ __('messages.options.img_cat.main') }}</option>
                                <option value="before_opening">{{ __('messages.options.img_cat.before_opening') }}</option>
                                <option value="after_opening">{{ __('messages.options.img_cat.after_opening') }}</option>
                                <option value="barcode_label">{{ __('messages.options.img_cat.barcode_label') }}</option>
                                <option value="other">{{ __('messages.options.img_cat.other') }}</option>
                            </select>
                            <button type="button" onclick="removeImageRow(this)"
                                class="w-full py-1.5 text-xs text-red-500 border border-red-200 bg-white hover:bg-red-50 rounded-md transition-colors flex items-center justify-center gap-1.5">
                                <i class="fas fa-trash-alt"></i> {{ __('messages.common.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- أزرار الحفظ --}}
        <div class="mt-8 flex justify-end gap-4 border-t border-gray-200 pt-6 mb-12">
            <button type="reset" class="px-6 py-2.5 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 font-bold shadow-sm transition-colors">
                {{ __('messages.common.reset') }}
            </button>
            <button type="submit" class="px-8 py-2.5 bg-[#008A3B] border border-transparent rounded-md text-white hover:bg-[#007030] font-bold shadow-md flex items-center gap-2">
                <i class="fas fa-save text-lg"></i> {{ __('messages.items.save') }}
            </button>
        </div>
        
    </form>
</div>

<script>
// =================== Sub-category dynamic filter (Tom Select) ===================
const subCatMap = @json($subCatMap);
const subPh     = @json(__('messages.items.f_sub_ph'));

let subCatTS = null; // Tom Select instance للمجموعة الفرعية

function filterSubCategories(groupKey) {
    const savedVal = subCatTS ? subCatTS.getValue() : '';
    const list = groupKey ? (subCatMap[groupKey] || []) : Object.values(subCatMap).flat();

    if (subCatTS) {
        subCatTS.clearOptions();
        subCatTS.addOption({ value: '', text: subPh });
        list.forEach(item => subCatTS.addOption({ value: item.key, text: item.label }));
        subCatTS.setValue(list.find(i => i.key === savedVal) ? savedVal : '', true);
        subCatTS.refreshOptions(false);
    }

    const hint = document.getElementById('sub_cat_hint');
    if (hint) hint.classList.toggle('hidden', list.length > 0 || !groupKey);
}

document.addEventListener('DOMContentLoaded', function () {
    // تهيئة Tom Select على المجموعة الفرعية
    const subEl = document.getElementById('sub_category');
    if (subEl && typeof TomSelect !== 'undefined') {
        subCatTS = new TomSelect(subEl, { allowEmptyOption: true, maxOptions: 300 });
    }

    // تهيئة Tom Select multi-select للموردين المعتمدين
    const vendorEl = document.getElementById('vendorMultiSelect');
    if (vendorEl && typeof TomSelect !== 'undefined') {
        new TomSelect(vendorEl, {
            plugins: ['remove_button', 'clear_button'],
            maxOptions: 1000,
            placeholder: 'ابحث واختر موردين...',
            create: false,
            render: {
                no_results: function() {
                    return '<div style="padding:.5rem .75rem;color:#9ca3af;font-size:.8rem;">لا توجد نتائج</div>';
                }
            }
        });
    }

    // فلترة فورية إذا كانت المجموعة الرئيسية محددة (بعد فشل التحقق)
    const groupSel = document.getElementById('item_group');
    if (groupSel && groupSel.value) {
        // Tom Select لـ item_group يستخدم القيمة الحقيقية بنفس الآلية
        filterSubCategories(groupSel.value);
        // إعادة تحديد المجموعة الفرعية القديمة
        const oldSub = @json(old('sub_category', ''));
        if (subCatTS && oldSub) subCatTS.setValue(oldSub, true);
    }
});

// =================== Image card preview ===================
const imgCats = {
    main:           @json(__('messages.options.img_cat.main')),
    before_opening: @json(__('messages.options.img_cat.before_opening')),
    after_opening:  @json(__('messages.options.img_cat.after_opening')),
    barcode_label:  @json(__('messages.options.img_cat.barcode_label')),
    other:          @json(__('messages.options.img_cat.other')),
};
const imgDeleteLbl = @json(__('messages.common.delete'));
const imgPreviewLbl = @json(__('messages.items.img_preview'));
const imgAlertMsg   = @json(__('messages.items.alert_img'));

function previewImage(input) {
    if (!input.files || !input.files[0]) return;
    const row = input.closest('.image-row');
    const previewImg = row.querySelector('.preview-img');
    const placeholder = row.querySelector('.preview-placeholder');
    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        previewImg.classList.remove('hidden');
        if (placeholder) placeholder.classList.add('hidden');
    };
    reader.readAsDataURL(input.files[0]);
}

function addImageRow() {
    const container = document.getElementById('images_container');
    const row = document.createElement('div');
    row.className = 'image-row bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-4 flex flex-col gap-3 transition-all hover:border-[#005B9F]/40 animate-fade-in';

    const catOptions = Object.entries(imgCats).map(([v,l]) =>
        `<option value="${v}">${l}</option>`).join('');

    row.innerHTML = `
        <div class="relative w-full aspect-video bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
            <img src="" alt="" class="preview-img w-full h-full object-cover hidden rounded-lg">
            <div class="preview-placeholder flex flex-col items-center text-gray-400 gap-2">
                <i class="fas fa-image text-3xl"></i>
                <span class="text-xs">${imgPreviewLbl}</span>
            </div>
        </div>
        <input type="file" name="item_images[]" accept="image/*"
            onchange="previewImage(this)"
            class="w-full text-xs border border-gray-300 bg-white rounded-md file:py-1.5 file:px-3 file:border-0 file:text-xs file:font-semibold file:bg-[#005B9F] file:text-white hover:file:bg-[#004680] cursor-pointer">
        <select name="image_categories[]" class="w-full px-3 py-2 border border-gray-300 rounded-md text-xs bg-white focus:outline-none focus:border-[#005B9F]">
            ${catOptions}
        </select>
        <button type="button" onclick="removeImageRow(this)"
            class="w-full py-1.5 text-xs text-red-500 border border-red-200 bg-white hover:bg-red-50 rounded-md transition-colors flex items-center justify-center gap-1.5">
            <i class="fas fa-trash-alt"></i> ${imgDeleteLbl}
        </button>
    `;
    container.appendChild(row);
}

function removeImageRow(button) {
    const rows = document.querySelectorAll('#images_container .image-row');
    if (rows.length > 1) {
        button.closest('.image-row').remove();
    } else {
        alert(imgAlertMsg);
    }
}
</script>
@endsection