@extends('layouts.app')
@section('header_title', __('messages.vendors.add_title'))

@section('content')
<div class="container mx-auto px-4 max-w-6xl animate-fade-in">
    
    {{-- ترويسة الشاشة --}}
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
                <i class="fas fa-truck-loading text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.vendors.add_title') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('messages.vendors.add_sub') }}</p>
            </div>
        </div>
        <a href="{{ route('vendors.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-arrow-right text-sm"></i>
            {{ __('messages.common.back_list') }}
        </a>
    </div>

    {{-- أخطاء التحقق --}}
    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-300 text-red-800 rounded-xl p-4">
        <div class="flex items-center gap-2 font-bold mb-2"><i class="fas fa-exclamation-circle"></i> يرجى مراجعة الأخطاء التالية:</div>
        <ul class="list-disc list-inside space-y-1 text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- النموذج الرئيسي --}}
    <form action="{{ route('vendors.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="space-y-8">
            
            {{-- القسم الأول: البيانات الأساسية (ديناميكي) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
                    <i class="fas fa-info-circle"></i>
                    <span>1. {{ __('messages.vendors.s1') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="vendor_code" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_code') }}</label>
                        <input type="text" id="vendor_code" name="vendor_code" value="{{ old('vendor_code') }}" placeholder="{{ __('messages.vendors.f_code_ph') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none focus:border-[#008A3B]">
                    </div>

                    <div>
                        <label for="name_ar" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_name_ar') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>

                    <div>
                        <label for="name_en" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_name_en') }}</label>
                        <input type="text" id="name_en" name="name_en" value="{{ old('name_en') }}" dir="ltr"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg text-right focus:outline-none focus:border-[#008A3B]">
                    </div>

                    <div class="md:col-span-2">
                        <label for="legal_name" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_legal') }}</label>
                        <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>

                    {{-- مجموعة الموردين (من الإعدادات) --}}
                    <div>
                        <label for="vendor_group" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_group') }} <span class="text-red-500">*</span></label>
                        <select id="vendor_group" name="vendor_group" required data-search class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                            <option value="" disabled selected>{{ __('messages.options.vendor_group.choose') }}</option>
                            @foreach($vendorGroups as $group)
                                <option value="{{ $group->key_value }}" {{ old('vendor_group') == $group->key_value ? 'selected' : '' }}>
                                    {{ $group->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- حالة المورد (من الإعدادات) --}}
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_status') }}</label>
                        <select id="status" name="status" onchange="toggleBlockReason()" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                            @foreach($vendorStatuses as $status)
                                <option value="{{ $status->key_value }}" {{ old('status', 'active') == $status->key_value ? 'selected' : '' }}>
                                    {{ $status->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="block_reason_div" class="md:col-span-2 hidden">
                        <label for="block_reason" class="block text-sm font-semibold text-gray-700 mb-1.5 text-red-600">{{ __('messages.vendors.f_block') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="block_reason" name="block_reason" value="{{ old('block_reason') }}" placeholder="{{ __('messages.vendors.f_block_ph') }}"
                            class="w-full px-4 py-2 border border-red-300 rounded-lg focus:outline-none focus:border-red-500">
                    </div>
                </div>
            </div>

            {{-- القسم الثاني: بيانات الاتصال --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#005B9F] font-bold text-lg">
                    <i class="fas fa-address-book"></i>
                    <span>2. {{ __('messages.vendors.s2') }}</span>
                </div>
                
                <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-phone text-gray-400"></i> {{ __('messages.vendors.contact_main') }}</h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div>
                        <label for="phone" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_phone') }}</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                    <div>
                        <label for="mobile" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_mobile') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="mobile" name="mobile" value="{{ old('mobile') }}" required dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                    <div>
                        <label for="email" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_email') }}</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                    <div>
                        <label for="website" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_website') }}</label>
                        <input type="url" id="website" name="website" value="{{ old('website') }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                </div>

                <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-user-tie text-gray-400"></i> {{ __('messages.vendors.contact_resp') }}</h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-xl">
                    <div>
                        <label for="contact_person_name" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_cname') }}</label>
                        <input type="text" id="contact_person_name" name="contact_person_name" value="{{ old('contact_person_name') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="contact_person_job" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_cjob') }}</label>
                        <input type="text" id="contact_person_job" name="contact_person_job" value="{{ old('contact_person_job') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="contact_person_mobile" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_cmobile') }}</label>
                        <input type="text" id="contact_person_mobile" name="contact_person_mobile" value="{{ old('contact_person_mobile') }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                    <div>
                        <label for="contact_person_email" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_cemail') }}</label>
                        <input type="email" id="contact_person_email" name="contact_person_email" value="{{ old('contact_person_email') }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                </div>
            </div>

            {{-- القسم الثالث: المالية (ديناميكي) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
                    <i class="fas fa-calculator"></i>
                    <span>3. {{ __('messages.vendors.s3') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    {{-- العملة الافتراضية (من الإعدادات) --}}
                    <div>
                        <label for="default_currency" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_currency') }} <span class="text-red-500">*</span></label>
                        <select id="default_currency" name="default_currency" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                            <option value="" disabled selected>{{ __('messages.options.currency.choose') }}</option>
                            @foreach($currencies as $currency)
                                <option value="{{ $currency->key_value }}" {{ old('default_currency') == $currency->key_value ? 'selected' : '' }}>
                                    {{ $currency->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tax_id" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_tax') }}</label>
                        <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>

                    <div>
                        <label for="commercial_registry" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_registry') }}</label>
                        <input type="text" id="commercial_registry" name="commercial_registry" value="{{ old('commercial_registry') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>

                    <div>
                        <label for="credit_limit" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_credit') }}</label>
                        <input type="number" step="0.01" id="credit_limit" name="credit_limit" value="{{ old('credit_limit') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>
                </div>
            </div>

            {{-- القسم الرابع: اللوجستيات والبنوك (ديناميكي) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#005B9F] font-bold text-lg">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>4. {{ __('messages.vendors.s4') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label for="payment_terms" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_terms') }}</label>
                        <select id="payment_terms" name="payment_terms" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                            <option value="cash">{{ __('messages.options.pay_terms.cash') }}</option>
                            <option value="advance">{{ __('messages.options.pay_terms.advance') }}</option>
                            <option value="30_days">{{ __('messages.options.pay_terms.30_days') }}</option>
                            <option value="60_days">{{ __('messages.options.pay_terms.60_days') }}</option>
                            <option value="cod">{{ __('messages.options.pay_terms.cod') }}</option>
                        </select>
                    </div>

                    {{-- طريقة الدفع (من الإعدادات) --}}
                    <div>
                        <label for="payment_method" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_method') }}</label>
                        <select id="payment_method" name="payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                            <option value="" disabled selected>{{ __('messages.options.pay_method.choose') }}</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->key_value }}" {{ old('payment_method') == $method->key_value ? 'selected' : '' }}>
                                    {{ $method->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="lead_time_days" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_lead') }}</label>
                        <div class="relative">
                            <input type="number" id="lead_time_days" name="lead_time_days" value="{{ old('lead_time_days') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg pl-12">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400 bg-gray-100 border-r border-gray-300 px-3 rounded-l-lg">{{ __('messages.common.days') }}</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-xl">
                    <div>
                        <label for="bank_name" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_bank') }}</label>
                        <input type="text" id="bank_name" name="bank_name" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label for="account_holder" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_holder') }}</label>
                        <input type="text" id="account_holder" name="account_holder" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label for="iban" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_iban') }}</label>
                        <input type="text" id="iban" name="iban" dir="ltr" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-right">
                    </div>
                </div>
            </div>

            {{-- القسم الخامس: تقييم المورد والأصناف --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-amber-500 font-bold text-lg">
                    <i class="fas fa-star-half-alt"></i>
                    <span>5. {{ __('messages.vendors.s5') }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- درجة التقييم --}}
                    <div>
                        <label for="vendor_rating" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_rating') }}</label>
                        <select id="vendor_rating" name="vendor_rating" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-amber-500">
                            <option value="A" {{ old('vendor_rating') == 'A' ? 'selected' : '' }}>{{ __('messages.options.rating.A') }}</option>
                            <option value="B" {{ old('vendor_rating') == 'B' ? 'selected' : '' }}>{{ __('messages.options.rating.B') }}</option>
                            <option value="C" {{ old('vendor_rating') == 'C' ? 'selected' : '' }}>{{ __('messages.options.rating.C') }}</option>
                            <option value="D" {{ old('vendor_rating') == 'D' ? 'selected' : '' }}>{{ __('messages.options.rating.D') }}</option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">{{ __('messages.vendors.f_rating_h') }}</p>
                    </div>

                    {{-- الأصناف المعتمدة (Autocomplete Search) --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_items') }}</label>

                        {{-- الأصناف المختارة (Chips) --}}
                        <div id="selectedItemsChips" class="flex flex-wrap gap-2 mb-2 min-h-[8px]"></div>

                        {{-- حقل البحث --}}
                        <div class="relative">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                <i class="fas fa-search text-sm"></i>
                            </div>
                            <input type="text" id="itemSearchInput" autocomplete="off"
                                placeholder="{{ app()->getLocale() === 'ar' ? 'ابحث عن صنف بالاسم أو الكود...' : 'Search by item name or code...' }}"
                                class="w-full pr-10 pl-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors bg-gray-50 focus:bg-white text-sm">
                            {{-- قائمة النتائج --}}
                            <div id="itemSearchResults" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-[220px] overflow-y-auto hidden sidebar-scroll"></div>
                        </div>

                        {{-- Hidden inputs container --}}
                        <div id="selectedItemsInputs"></div>

                        <p class="text-[10px] text-gray-500 mt-1"><i class="fas fa-info-circle"></i> {{ __('messages.vendors.f_items_h') }}</p>
                    </div>
                </div>
            </div>

            {{-- القسم السادس: المرفقات --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
                    <i class="fas fa-paperclip"></i>
                    <span>6. {{ __('messages.vendors.s6') }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('messages.vendors.f_attach') }}</label>
                        <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-[#008A3B] bg-gray-50 transition-colors">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                                <div class="flex text-sm text-gray-600 justify-center">
                                    <label for="attachments" class="relative cursor-pointer bg-white rounded-md font-medium text-[#005B9F] hover:text-[#008A3B]">
                                        <span>{{ __('messages.vendors.f_choose') }}</span>
                                        <input id="attachments" name="attachment" type="file" accept=".pdf,.doc,.docx" class="sr-only">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 flex items-start gap-3">
                        <i class="fas fa-shield-alt text-[#005B9F] text-lg mt-0.5"></i>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            <span class="font-bold block text-gray-900 mb-1">{{ __('messages.vendors.legal_title') }}</span>
                            {{ __('messages.vendors.legal_note') }}
                        </p>
                    </div>
                </div>
            </div>

        </div>

        {{-- أزرار الحفظ --}}
        <div class="mt-8 flex justify-end gap-4 border-t border-gray-200 pt-6 mb-12">
            <button type="reset" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium">{{ __('messages.common.reset') }}</button>
            <button type="submit" class="px-8 py-2.5 bg-[#008A3B] border border-transparent rounded-lg text-white hover:bg-[#007030] font-bold shadow-lg flex items-center gap-2">
                <i class="fas fa-save"></i> {{ __('messages.vendors.save') }}
            </button>
        </div>
        
    </form>
</div>

<script>
    function toggleBlockReason() {
        var statusSelect = document.getElementById('status');
        var reasonDiv = document.getElementById('block_reason_div');
        var reasonInput = document.getElementById('block_reason');
        
        // إذا كان المفتاح الديناميكي المسجل في الإعدادات للحظر هو "blocked"
        if (statusSelect.value === 'blocked') {
            reasonDiv.classList.remove('hidden');
            reasonInput.required = true;
        } else {
            reasonDiv.classList.add('hidden');
            reasonInput.required = false;
            reasonInput.value = '';
        }
    }
    window.onload = toggleBlockReason;
</script>

<script>
(function() {
    // بيانات الأصناف كاملة (من السيرفر)
    @php
        $itemsJson = $items->map(function($i) {
            return ['id' => $i->id, 'name_ar' => $i->name_ar, 'name_en' => $i->name_en, 'item_code' => $i->item_code];
        })->values();
    @endphp
    const allItems = @json($itemsJson);
    const selectedItems = new Map(); // id => item object
    const oldSelected = @json(old('approved_items', []));

    const searchInput   = document.getElementById('itemSearchInput');
    const resultsBox    = document.getElementById('itemSearchResults');
    const chipsBox      = document.getElementById('selectedItemsChips');
    const inputsBox     = document.getElementById('selectedItemsInputs');

    // تحميل الأصناف المحفوظة (old input)
    if (oldSelected.length) {
        oldSelected.forEach(id => {
            const item = allItems.find(i => i.id == id);
            if (item) addItem(item, true);
        });
    }

    // بحث فوري
    searchInput.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        if (q.length === 0) { resultsBox.classList.add('hidden'); return; }

        const matches = allItems.filter(item => {
            if (selectedItems.has(item.id)) return false;
            return (item.name_ar && item.name_ar.toLowerCase().includes(q))
                || (item.name_en && item.name_en.toLowerCase().includes(q))
                || (item.item_code && item.item_code.toLowerCase().includes(q));
        }).slice(0, 15);

        if (matches.length === 0) {
            resultsBox.innerHTML = '<div class="p-4 text-center text-gray-400 text-sm"><i class="fas fa-search text-lg mb-1 opacity-40"></i><br>{{ app()->getLocale() === "ar" ? "لا توجد نتائج" : "No results" }}</div>';
        } else {
            resultsBox.innerHTML = matches.map(item => `
                <div class="item-result flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-[#EBF7F0] transition-colors border-b border-gray-50 last:border-0"
                     data-id="${item.id}">
                    <i class="fas fa-box text-gray-300 text-sm"></i>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-bold text-gray-800 block truncate">${item.name_ar || item.name_en}</span>
                        <span class="text-[10px] font-mono text-gray-400">${item.item_code || ''}</span>
                    </div>
                    <i class="fas fa-plus-circle text-[#008A3B] opacity-50"></i>
                </div>
            `).join('');

            resultsBox.querySelectorAll('.item-result').forEach(el => {
                el.addEventListener('click', function() {
                    const id = parseInt(this.dataset.id);
                    const item = allItems.find(i => i.id === id);
                    if (item) addItem(item);
                    searchInput.value = '';
                    resultsBox.classList.add('hidden');
                    searchInput.focus();
                });
            });
        }
        resultsBox.classList.remove('hidden');
    });

    // إغلاق القائمة عند الضغط خارجها
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.classList.add('hidden');
        }
    });

    // فتح القائمة عند الفوكس (إذا فيه نص)
    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length > 0) {
            this.dispatchEvent(new Event('input'));
        }
    });

    function addItem(item, silent) {
        if (selectedItems.has(item.id)) return;
        selectedItems.set(item.id, item);

        // Chip
        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1 bg-[#EBF7F0] text-[#008A3B] text-xs font-bold rounded-full border border-[#008A3B]/20 transition-all hover:shadow-sm';
        chip.dataset.id = item.id;
        chip.innerHTML = `
            <i class="fas fa-box text-[10px] opacity-60"></i>
            <span>${item.name_ar || item.name_en}</span>
            <span class="font-mono text-[9px] text-gray-400 mx-0.5">${item.item_code || ''}</span>
            <button type="button" class="w-5 h-5 flex items-center justify-center rounded-full hover:bg-red-100 hover:text-red-500 text-gray-400 transition-colors ml-0.5" title="{{ app()->getLocale() === 'ar' ? 'إزالة' : 'Remove' }}">
                <i class="fas fa-times text-[9px]"></i>
            </button>
        `;
        chip.querySelector('button').addEventListener('click', () => removeItem(item.id));
        chipsBox.appendChild(chip);

        // Hidden input
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'approved_items[]';
        inp.value = item.id;
        inp.id = 'item-input-' + item.id;
        inputsBox.appendChild(inp);
    }

    function removeItem(id) {
        selectedItems.delete(id);
        const chip = chipsBox.querySelector(`[data-id="${id}"]`);
        if (chip) chip.remove();
        const inp = document.getElementById('item-input-' + id);
        if (inp) inp.remove();
    }
})();
</script>
@endsection