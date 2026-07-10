@extends('layouts.app')
@section('header_title', __('messages.items.edit_title'))

@section('content')
<div class="container mx-auto px-4 max-w-6xl animate-fade-in">
    
    {{-- ترويسة الشاشة --}}
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500">
                <i class="fas fa-edit text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.items.edit_title') }}: {{ $item->name_ar }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('messages.items.edit_sub') }}</p>
            </div>
        </div>
        <a href="{{ route('items.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-arrow-right text-sm"></i> {{ __('messages.common.back_list') }}
        </a>
    </div>

    {{-- رسالة الأخطاء إن وجدت --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl">
            <ul class="list-disc list-inside text-sm font-medium">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- النموذج الرئيسي للتعديل --}}
    <form action="{{ route('items.update', $item->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT') {{-- هام جداً في لارافيل لتحديث البيانات --}}

        <div class="space-y-8">
            
            {{-- القسم الأول: البيانات الأساسية ووحدات القياس --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
                    <i class="fas fa-info-circle"></i>
                    <span>1. {{ __('messages.items.s1') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    {{-- كود الصنف --}}
                    <div>
                        <label for="item_code" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_code') }}</label>
                        <input type="text" id="item_code" name="item_code" value="{{ old('item_code', $item->item_code) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] bg-gray-50">
                    </div>

                    {{-- الباركود --}}
                    <div>
                        <label for="barcode" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_barcode') }}</label>
                        <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $item->barcode) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>

                    {{-- اسم الصنف عربي --}}
                    <div class="md:col-span-2">
                        <label for="name_ar" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_name_ar') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar', $item->name_ar) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>

                    {{-- اسم الصنف إنجليزي --}}
                    <div class="md:col-span-2">
                        <label for="name_en" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_name_en') }}</label>
                        <input type="text" id="name_en" name="name_en" value="{{ old('name_en', $item->name_en) }}" dir="ltr"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] text-right">
                    </div>

                    {{-- المجموعة الرئيسية --}}
                    <div>
                        <label for="item_group" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_group') }}</label>
                        <select id="item_group" name="item_group" data-search
                            onchange="filterSubCategories(this.value)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                            <option value="">{{ __('messages.options.item_group.choose') }}</option>
                            @foreach($itemGroups as $group)
                                <option value="{{ $group->key_value }}" {{ old('item_group', $item->item_group) == $group->key_value ? 'selected' : '' }}>
                                    {{ $group->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- المجموعة الفرعية — ديناميكية --}}
                    <div>
                        <label for="sub_category" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_sub') }}</label>
                        <select id="sub_category" name="sub_category"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                            <option value="">{{ __('messages.items.f_sub_ph') }}</option>
                            @foreach($itemSubGroups as $sub)
                                <option value="{{ $sub->key_value }}"
                                    data-parent="{{ $sub->parent_key ?? '' }}"
                                    {{ old('sub_category', $item->sub_category) == $sub->key_value ? 'selected' : '' }}>
                                    {{ $sub->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- وحدة القياس الأساسية --}}
                    <div>
                        <label for="base_uom" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_uom') }} <span class="text-red-500">*</span></label>
                        <select id="base_uom" name="base_uom" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                            <option value="" disabled selected>{{ __('messages.options.uom.choose') }}</option>
                            @foreach($uoms as $uom)
                                <option value="{{ $uom->key_value }}" {{ old('base_uom', $item->base_uom) == $uom->key_value ? 'selected' : '' }}>
                                    {{ $uom->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- القسم الثاني: بيانات المخازن والرقابة --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#005B9F] font-bold text-lg">
                    <i class="fas fa-warehouse"></i>
                    <span>2. {{ __('messages.items.s2') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="reorder_point" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_reorder') }}</label>
                        <input type="number" id="reorder_point" name="reorder_point" value="{{ old('reorder_point', $item->reorder_point) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>
                    <div>
                        <label for="min_stock" class="block text-sm font-semibold text-gray-700 mb-1.5 text-red-600">{{ __('messages.items.f_min') }}</label>
                        <input type="number" id="min_stock" name="min_stock" value="{{ old('min_stock', $item->min_stock) }}"
                            class="w-full px-4 py-2 border border-red-300 rounded-lg focus:outline-none focus:border-red-500">
                    </div>
                    <div>
                        <label for="max_stock" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_max') }}</label>
                        <input type="number" id="max_stock" name="max_stock" value="{{ old('max_stock', $item->max_stock) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>
                </div>
            </div>

            {{-- القسم الثالث: المشتريات والموردين --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
                    <i class="fas fa-shopping-cart"></i>
                    <span>3. {{ __('messages.items.s3') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    {{-- المورد الافتراضي --}}
                    <div class="md:col-span-2">
                     <label for="default_vendor_id" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_vendor') }}</label>
<select id="default_vendor_id" name="default_vendor_id" data-search class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
    <option value="">{{ __('messages.items.f_vendor_ph') }}</option>
    @foreach($vendors as $vendor)
        <option value="{{ $vendor->id }}" {{ old('default_vendor_id', $item->default_vendor_id ?? '') == $vendor->id ? 'selected' : '' }}>
            {{ $vendor->name_ar }} - ({{ $vendor->vendor_code }})
        </option>
    @endforeach
</select>
                    </div>

                    {{-- كود الصنف عند المورد --}}
                    <div>
                        <label for="supplier_part_number" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_supplier') }}</label>
                        <input type="text" id="supplier_part_number" name="supplier_part_number" value="{{ old('supplier_part_number', $item->supplier_part_number) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>

                    {{-- أقل كمية للطلب (MOQ) --}}
                    <div>
                        <label for="moq" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_moq') }}</label>
                        <input type="number" id="moq" name="moq" value="{{ old('moq', $item->moq) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>

                    {{-- الموردين المعتمدين لتوريد هذا الصنف --}}
                    <div class="md:col-span-4">
                        @php $selectedVendors = old('approved_vendors', $item->approvedVendors->pluck('id')->toArray()); @endphp
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-truck text-[#005B9F]"></i> {{ __('messages.items.f_vendors') }}
                        </label>
                        <select id="vendorMultiSelect" name="approved_vendors[]" multiple
                            class="w-full border border-gray-300 rounded-lg bg-white focus:outline-none">
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}"
                                    {{ in_array($vendor->id, $selectedVendors) ? 'selected' : '' }}>
                                    {{ $vendor->name_ar }} — {{ $vendor->vendor_code }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-500 mt-1.5"><i class="fas fa-info-circle"></i> {{ __('messages.items.f_vendors_h') }}</p>
                    </div>
                </div>
            </div>

            {{-- القسم الرابع: صور جودة الصنف --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-purple-700 font-bold text-lg">
                    <i class="fas fa-images"></i>
                    <span>4. {{ __('messages.items.s4') }}</span>
                </div>

                {{-- الصور الحالية --}}
                @if($item->images && $item->images->count() > 0)
                <div class="mb-6">
                    <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <i class="fas fa-photo-video text-purple-500"></i>
                        {{ __('messages.items.existing_images') }}
                        <span class="bg-purple-100 text-purple-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ $item->images->count() }}</span>
                    </h4>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($item->images as $image)
                        <div class="relative group bg-gray-50 border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <div class="aspect-video overflow-hidden">
                                <img src="{{ Storage::url($image->image_path) }}"
                                    alt="{{ $image->category }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                            <div class="p-2.5">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                                    <i class="fas fa-tag text-[9px]"></i>
                                    {{ __('messages.options.img_cat.' . $image->category) }}
                                </span>
                            </div>
                            {{-- زر الحذف --}}
                            <label class="absolute top-2 right-2 cursor-pointer">
                                <input type="checkbox" name="delete_images[]" value="{{ $image->id }}"
                                    class="sr-only delete-img-check"
                                    onchange="toggleDeleteOverlay(this)">
                                <div class="delete-btn-ui w-7 h-7 bg-white/90 hover:bg-red-50 border border-gray-300 hover:border-red-400 rounded-full flex items-center justify-center shadow-sm transition-all">
                                    <i class="fas fa-trash-alt text-xs text-gray-400 hover:text-red-500"></i>
                                </div>
                            </label>
                            {{-- overlay عند التحديد للحذف --}}
                            <div class="delete-overlay absolute inset-0 bg-red-500/20 border-2 border-red-500 rounded-xl hidden flex items-center justify-center pointer-events-none">
                                <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded shadow">{{ __('messages.common.will_delete') }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-3 flex items-center gap-1.5">
                        <i class="fas fa-info-circle text-red-400"></i>
                        {{ __('messages.items.alert_img') }}
                    </p>
                </div>
                <div class="border-t border-gray-100 pt-6 mb-4"></div>
                @endif

                {{-- رفع صور جديدة --}}
                <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-cloud-upload-alt text-green-500"></i>
                    {{ __('messages.items.upload_new') }}
                </h4>

                <div id="images_container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <div class="image-row bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-4 flex flex-col gap-3 hover:border-purple-400 transition-colors">
                        <div class="relative w-full aspect-video bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center">
                            <img src="" alt="" class="preview-img w-full h-full object-cover hidden rounded-lg">
                            <div class="preview-placeholder flex flex-col items-center text-gray-400 gap-2">
                                <i class="fas fa-image text-3xl"></i>
                                <span class="text-xs">{{ __('messages.items.img_preview') }}</span>
                            </div>
                        </div>
                        <input type="file" name="item_images[]" accept="image/*"
                            onchange="previewImage(this)"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer">
                        <select name="image_categories[]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-purple-400">
                            <option value="front">{{ __('messages.options.img_cat.front') }}</option>
                            <option value="back">{{ __('messages.options.img_cat.back') }}</option>
                            <option value="label">{{ __('messages.options.img_cat.label') }}</option>
                            <option value="defect">{{ __('messages.options.img_cat.defect') }}</option>
                            <option value="other" selected>{{ __('messages.options.img_cat.other') }}</option>
                        </select>
                        <button type="button" onclick="removeImageRow(this)"
                            class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1 self-end">
                            <i class="fas fa-times-circle"></i> {{ __('messages.common.delete') }}
                        </button>
                    </div>
                </div>

                <button type="button" onclick="addImageRow()"
                    class="px-4 py-2 border-2 border-dashed border-purple-300 text-purple-600 rounded-lg hover:bg-purple-50 text-sm font-semibold flex items-center gap-2 transition-colors">
                    <i class="fas fa-plus-circle"></i>
                    {{ __('messages.items.add_img') }}
                </button>
            </div>

            {{-- القسم الخامس: الحالة والمرفق --}}
            <div class="bg-gray-50 rounded-2xl border border-gray-200 p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_status') }}</label>
                        <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white shadow-sm">
                            <option value="active" {{ old('status', $item->status) == 'active' ? 'selected' : '' }}>{{ __('messages.options.item_status.active') }}</option>
                            <option value="suspended" {{ old('status', $item->status) == 'suspended' ? 'selected' : '' }}>{{ __('messages.options.item_status.suspended') }}</option>
                            <option value="obsolete" {{ old('status', $item->status) == 'obsolete' ? 'selected' : '' }}>{{ __('messages.options.item_status.obsolete') }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.items.f_attachment') }}</label>
                        @if($item->attachment_path)
                            <div class="flex items-center gap-3 mb-2 p-2.5 bg-red-50 border border-red-200 rounded-lg">
                                <i class="fas fa-file-pdf text-red-500 text-lg"></i>
                                <a href="{{ Storage::url($item->attachment_path) }}" target="_blank"
                                   class="text-sm font-bold text-red-700 hover:underline flex-1 truncate">
                                    {{ __('messages.items.view_attachment') }}
                                </a>
                                <span class="text-[10px] text-gray-400">{{ __('messages.items.replace_attachment') }}</span>
                            </div>
                        @endif
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx"
                            class="w-full px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:border-[#005B9F]">
                    </div>
                </div>
            </div>

        </div>

        {{-- أزرار الحفظ --}}
        <div class="mt-8 flex justify-end gap-4 border-t border-gray-200 pt-6 mb-12">
            <a href="{{ route('items.index') }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium transition-colors">
                {{ __('messages.common.cancel') }}
            </a>
            <button type="submit" class="px-8 py-2.5 bg-amber-500 border border-transparent rounded-lg text-white hover:bg-amber-600 font-bold shadow-lg flex items-center gap-2">
                <i class="fas fa-save"></i>
                {{ __('messages.common.save_changes') }}
            </button>
        </div>
        
    </form>
</div>

<script>
// ==================== Sub-category filtering (Tom Select) ====================
const subCatMap = @json($subCatMap);
const subPh    = @json(__('messages.items.f_sub_ph'));

let subCatTS = null;

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
}

document.addEventListener('DOMContentLoaded', function () {
    const subEl = document.getElementById('sub_category');
    if (subEl && typeof TomSelect !== 'undefined') {
        subCatTS = new TomSelect(subEl, { allowEmptyOption: true, maxOptions: 300 });
    }

    const groupSel = document.getElementById('item_group');
    if (groupSel && groupSel.value) {
        filterSubCategories(groupSel.value);
        const savedSub = @json(old('sub_category', $item->sub_category));
        if (subCatTS && savedSub) subCatTS.setValue(savedSub, true);
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
});

// ==================== Image preview ====================
function previewImage(input) {
    if (!input.files || !input.files[0]) return;
    const row         = input.closest('.image-row');
    const previewImg  = row.querySelector('.preview-img');
    const placeholder = row.querySelector('.preview-placeholder');
    const reader      = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        previewImg.classList.remove('hidden');
        if (placeholder) placeholder.classList.add('hidden');
    };
    reader.readAsDataURL(input.files[0]);
}

// ==================== Add / remove image rows ====================
function addImageRow() {
    const container = document.getElementById('images_container');
    const template  = container.querySelector('.image-row');
    const clone     = template.cloneNode(true);
    // Reset clone
    const img = clone.querySelector('.preview-img');
    img.src   = '';
    img.classList.add('hidden');
    const ph = clone.querySelector('.preview-placeholder');
    if (ph) ph.classList.remove('hidden');
    clone.querySelector('input[type="file"]').value = '';
    clone.querySelector('select').selectedIndex     = 4; // 'other'
    container.appendChild(clone);
}

function removeImageRow(btn) {
    const container = document.getElementById('images_container');
    if (container.querySelectorAll('.image-row').length <= 1) return;
    btn.closest('.image-row').remove();
}

// ==================== Delete existing image overlay ====================
function toggleDeleteOverlay(checkbox) {
    const card    = checkbox.closest('.relative.group');
    const overlay = card.querySelector('.delete-overlay');
    const btnUI   = card.querySelector('.delete-btn-ui');
    if (checkbox.checked) {
        overlay.classList.remove('hidden');
        btnUI.classList.add('bg-red-100', 'border-red-500');
    } else {
        overlay.classList.add('hidden');
        btnUI.classList.remove('bg-red-100', 'border-red-500');
    }
}
</script>
@endsection