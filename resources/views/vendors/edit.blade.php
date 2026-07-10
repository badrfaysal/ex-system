@extends('layouts.app')
@section('header_title', __('messages.vendors.edit_title'))

@section('content')
<div class="container mx-auto px-4 max-w-6xl animate-fade-in">
    
    {{-- ترويسة الشاشة --}}
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
                <i class="fas fa-edit text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.vendors.edit_title') }}: {{ $vendor->name_ar }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('messages.vendors.edit_sub') }}</p>
            </div>
        </div>
        <a href="{{ route('vendors.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-arrow-right text-sm"></i>
            {{ __('messages.common.back_list') }}
        </a>
    </div>

    {{-- عرض الأخطاء إن وجدت --}}
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl">
            <ul class="list-disc list-inside text-sm font-medium">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- نموذج التعديل --}}
    <form action="{{ route('vendors.update', $vendor->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT') {{-- حتمي في لارافيل لعمل الـ Update --}}

        <div class="space-y-8">
            
            {{-- القسم الأول: البيانات الأساسية --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
                    <i class="fas fa-info-circle"></i>
                    <span>1. {{ __('messages.vendors.s1') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- كود المورد --}}
                    <div>
                        <label for="vendor_code" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.p_code') }}</label>
                        <input type="text" id="vendor_code" name="vendor_code" value="{{ old('vendor_code', $vendor->vendor_code) }}" readonly
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed font-mono font-bold">
                    </div>

                    {{-- الاسم التجاري - عربي --}}
                    <div>
                        <label for="name_ar" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_name_ar') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar', $vendor->name_ar) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                    </div>

                    {{-- الاسم التجاري - إنجليزي --}}
                    <div>
                        <label for="name_en" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_name_en') }}</label>
                        <input type="text" id="name_en" name="name_en" value="{{ old('name_en', $vendor->name_en) }}" dir="ltr"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] text-right">
                    </div>

                    {{-- الاسم القانوني للشركة --}}
                    <div class="md:col-span-2">
                        <label for="legal_name" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_legal') }}</label>
                        <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name', $vendor->legal_name) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>

                    {{-- مجموعة الموردين --}}
                    <div>
                        <label for="vendor_group" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_group') }} <span class="text-red-500">*</span></label>
                        <select id="vendor_group" name="vendor_group" required data-search
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                            <option value="local" {{ old('vendor_group', $vendor->vendor_group) == 'local' ? 'selected' : '' }}>{{ __('messages.options.vendor_group.local') }}</option>
                            <option value="international" {{ old('vendor_group', $vendor->vendor_group) == 'international' ? 'selected' : '' }}>{{ __('messages.options.vendor_group.international') }}</option>
                            <option value="subcontractor" {{ old('vendor_group', $vendor->vendor_group) == 'subcontractor' ? 'selected' : '' }}>{{ __('messages.options.vendor_group.subcontractor') }}</option>
                            <option value="government" {{ old('vendor_group', $vendor->vendor_group) == 'government' ? 'selected' : '' }}>{{ __('messages.options.vendor_group.government') }}</option>
                        </select>
                    </div>

                    {{-- حالة المورد --}}
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_status') }}</label>
                        <select id="status" name="status" onchange="toggleBlockReason()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                            <option value="active" {{ old('status', $vendor->status) == 'active' ? 'selected' : '' }}>{{ __('messages.options.vendor_status.active') }}</option>
                            <option value="on_hold" {{ old('status', $vendor->status) == 'on_hold' ? 'selected' : '' }}>{{ __('messages.options.vendor_status.on_hold') }}</option>
                            <option value="blocked" {{ old('status', $vendor->status) == 'blocked' ? 'selected' : '' }}>{{ __('messages.options.vendor_status.blocked') }}</option>
                        </select>
                    </div>

                    {{-- سبب الحظر --}}
                    <div id="block_reason_div" class="md:col-span-2 hidden">
                        <label for="block_reason" class="block text-sm font-semibold text-gray-700 mb-1.5 text-red-600">{{ __('messages.vendors.f_block') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="block_reason" name="block_reason" value="{{ old('block_reason', $vendor->block_reason) }}" placeholder="{{ __('messages.vendors.f_block_ph') }}"
                            class="w-full px-4 py-2 border border-red-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500">
                    </div>
                </div>
            </div>

            {{-- القسم الثاني: بيانات الاتصال ومسؤول الاتصال --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#005B9F] font-bold text-lg">
                    <i class="fas fa-address-book"></i>
                    <span>2. {{ __('messages.vendors.s2') }}</span>
                </div>
                
                <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-phone text-gray-400"></i> {{ __('messages.vendors.contact_main') }}</h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div>
                        <label for="phone" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_phone') }}</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $vendor->phone) }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                    <div>
                        <label for="mobile" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_mobile') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="mobile" name="mobile" value="{{ old('mobile', $vendor->mobile) }}" required dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                    <div>
                        <label for="email" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_email') }}</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $vendor->email) }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                    <div>
                        <label for="website" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_website') }}</label>
                        <input type="url" id="website" name="website" value="{{ old('website', $vendor->website) }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                </div>

                <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-user-tie text-gray-400"></i> {{ __('messages.vendors.contact_resp') }}</h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-xl">
                    <div>
                        <label for="contact_person_name" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_cname') }}</label>
                        <input type="text" id="contact_person_name" name="contact_person_name" value="{{ old('contact_person_name', $vendor->contact_person_name) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="contact_person_job" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_cjob') }}</label>
                        <input type="text" id="contact_person_job" name="contact_person_job" value="{{ old('contact_person_job', $vendor->contact_person_job) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="contact_person_mobile" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_cmobile') }}</label>
                        <input type="text" id="contact_person_mobile" name="contact_person_mobile" value="{{ old('contact_person_mobile', $vendor->contact_person_mobile) }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                    <div>
                        <label for="contact_person_email" class="block text-sm text-gray-600 mb-1">{{ __('messages.vendors.f_cemail') }}</label>
                        <input type="email" id="contact_person_email" name="contact_person_email" value="{{ old('contact_person_email', $vendor->contact_person_email) }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
                    </div>
                </div>
            </div>

            {{-- القسم الثالث: البيانات المالية والضرائب --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
                    <i class="fas fa-calculator"></i>
                    <span>3. {{ __('messages.vendors.s3') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    {{-- العملة الافتراضية --}}
                    <div>
                        <label for="default_currency" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_currency') }} <span class="text-red-500">*</span></label>
                        <select id="default_currency" name="default_currency" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                            <option value="EGP" {{ old('default_currency', $vendor->default_currency) == 'EGP' ? 'selected' : '' }}>{{ __('messages.options.currency.EGP') }}</option>
                            <option value="USD" {{ old('default_currency', $vendor->default_currency) == 'USD' ? 'selected' : '' }}>{{ __('messages.options.currency.USD') }}</option>
                            <option value="EUR" {{ old('default_currency', $vendor->default_currency) == 'EUR' ? 'selected' : '' }}>{{ __('messages.options.currency.EUR') }}</option>
                        </select>
                    </div>

                    {{-- رقم التسجيل الضريبي --}}
                    <div>
                        <label for="tax_id" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_tax') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id', $vendor->tax_id) }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>

                    {{-- رقم السجل التجاري --}}
                    <div>
                        <label for="commercial_registry" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_registry') }}</label>
                        <input type="text" id="commercial_registry" name="commercial_registry" value="{{ old('commercial_registry', $vendor->commercial_registry) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>

                    {{-- الحد الائتماني --}}
                    <div>
                        <label for="credit_limit" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_credit') }}</label>
                        <input type="number" step="0.01" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $vendor->credit_limit) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                    </div>
                </div>
            </div>

            {{-- القسم الرابع: الحساب البنكي واللوجستيات --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#005B9F] font-bold text-lg">
                    <i class="fas fa-university"></i>
                    <span>4. {{ __('messages.vendors.s4') }}</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label for="payment_terms" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_terms') }}</label>
                        <select id="payment_terms" name="payment_terms" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                            <option value="cash" {{ old('payment_terms', $vendor->payment_terms) == 'cash' ? 'selected' : '' }}>{{ __('messages.options.pay_terms.cash') }}</option>
                            <option value="advance" {{ old('payment_terms', $vendor->payment_terms) == 'advance' ? 'selected' : '' }}>{{ __('messages.options.pay_terms.advance') }}</option>
                            <option value="30_days" {{ old('payment_terms', $vendor->payment_terms) == '30_days' ? 'selected' : '' }}>{{ __('messages.options.pay_terms.30_days') }}</option>
                            <option value="60_days" {{ old('payment_terms', $vendor->payment_terms) == '60_days' ? 'selected' : '' }}>{{ __('messages.options.pay_terms.60_days') }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="payment_method" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_method') }}</label>
                        <select id="payment_method" name="payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                            <option value="bank_transfer" {{ old('payment_method', $vendor->payment_method) == 'bank_transfer' ? 'selected' : '' }}>{{ __('messages.options.pay_method.bank_transfer') }}</option>
                            <option value="check" {{ old('payment_method', $vendor->payment_method) == 'check' ? 'selected' : '' }}>{{ __('messages.options.pay_method.check') }}</option>
                            <option value="cash" {{ old('payment_method', $vendor->payment_method) == 'cash' ? 'selected' : '' }}>{{ __('messages.options.pay_method.cash') }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="lead_time_days" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_lead') }}</label>
                        <div class="relative">
                            <input type="number" id="lead_time_days" name="lead_time_days" value="{{ old('lead_time_days', $vendor->lead_time_days) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg pl-12">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400 bg-gray-100 border-r border-gray-300 px-3 rounded-l-lg">{{ __('messages.common.days') }}</div>
                        </div>
                    </div>
                </div>

                <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2 text-sm"><i class="fas fa-university text-gray-400"></i> {{ __('messages.vendors.bank_data') }}</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-xl text-sm">
                    <div>
                        <label for="bank_name" class="block text-xs text-gray-600 mb-1">{{ __('messages.vendors.f_bank') }}</label>
                        <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $vendor->bank_name) }}" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="bank_branch" class="block text-xs text-gray-600 mb-1">{{ __('messages.vendors.f_branch') }}</label>
                        <input type="text" id="bank_branch" name="bank_branch" value="{{ old('bank_branch', $vendor->bank_branch) }}" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="account_holder" class="block text-xs text-gray-600 mb-1">{{ __('messages.vendors.f_holder') }}</label>
                        <input type="text" id="account_holder" name="account_holder" value="{{ old('account_holder', $vendor->account_holder) }}" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="account_number" class="block text-xs text-gray-600 mb-1">{{ __('messages.vendors.f_account') }}</label>
                        <input type="text" id="account_number" name="account_number" value="{{ old('account_number', $vendor->account_number) }}" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="iban" class="block text-xs text-gray-600 mb-1">{{ __('messages.vendors.f_iban') }}</label>
                        <input type="text" id="iban" name="iban" value="{{ old('iban', $vendor->iban) }}" dir="ltr" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-right">
                    </div>
                    <div>
                        <label for="swift_code" class="block text-xs text-gray-600 mb-1">{{ __('messages.vendors.f_swift') }}</label>
                        <input type="text" id="swift_code" name="swift_code" value="{{ old('swift_code', $vendor->swift_code) }}" dir="ltr" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-right">
                    </div>
                </div>
            </div>

            {{-- القسم الخامس: تقييم المورد والأصناف المعتمدة --}}
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
                            <option value="A" {{ old('vendor_rating', $vendor->vendor_rating) == 'A' ? 'selected' : '' }}>{{ __('messages.options.rating.A') }}</option>
                            <option value="B" {{ old('vendor_rating', $vendor->vendor_rating) == 'B' ? 'selected' : '' }}>{{ __('messages.options.rating.B') }}</option>
                            <option value="C" {{ old('vendor_rating', $vendor->vendor_rating) == 'C' ? 'selected' : '' }}>{{ __('messages.options.rating.C') }}</option>
                            <option value="D" {{ old('vendor_rating', $vendor->vendor_rating) == 'D' ? 'selected' : '' }}>{{ __('messages.options.rating.D') }}</option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">{{ __('messages.vendors.f_rating_h') }}</p>
                    </div>

                    {{-- الأصناف المعتمدة --}}
                    <div class="md:col-span-2">
                        @php $selectedItems = old('approved_items', $vendor->approvedItems->pluck('id')->toArray()); @endphp
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.vendors.f_items') }}</label>
                        <div class="w-full h-[160px] overflow-y-auto border border-gray-300 bg-white rounded-lg p-3 space-y-1 shadow-inner">
                            @forelse($items as $item)
                                <label class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-md cursor-pointer border border-transparent hover:border-gray-200 transition-colors">
                                    <input type="checkbox" name="approved_items[]" value="{{ $item->id }}"
                                        {{ in_array($item->id, $selectedItems) ? 'checked' : '' }}
                                        class="w-4 h-4 text-[#008A3B] border-gray-300 rounded focus:ring-[#008A3B]">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-800 leading-tight">{{ $item->name_ar }}</span>
                                        <span class="text-[10px] font-mono text-gray-400 mt-0.5">{{ $item->item_code }}</span>
                                    </div>
                                </label>
                            @empty
                                <div class="text-center text-gray-400 text-sm py-8 flex flex-col items-center">
                                    <i class="fas fa-box-open text-3xl mb-2 opacity-30"></i>
                                    {{ __('messages.vendors.no_items') }}
                                </div>
                            @endforelse
                        </div>
                        <p class="text-[10px] text-gray-500 mt-1"><i class="fas fa-info-circle"></i> {{ __('messages.vendors.f_items_h') }}</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- المرفق (PDF / وثائق) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
            <div class="flex items-center gap-2 pb-4 mb-4 border-b border-gray-100 text-gray-600 font-bold text-sm">
                <i class="fas fa-paperclip text-[#005B9F]"></i>
                <span>{{ __('messages.vendors.f_attach') }}</span>
            </div>
            @if($vendor->attachment_path)
            <div class="mb-4 flex items-center gap-3 p-3 bg-red-50 border border-red-100 rounded-xl">
                <i class="fas fa-file-pdf text-red-500 text-xl"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-500">{{ __('messages.vendors.current_attach') }}</p>
                    <a href="{{ Storage::url($vendor->attachment_path) }}" target="_blank"
                        class="text-sm font-bold text-red-700 hover:underline truncate block">
                        {{ basename($vendor->attachment_path) }}
                    </a>
                </div>
                <a href="{{ Storage::url($vendor->attachment_path) }}" target="_blank"
                    class="shrink-0 px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs font-bold hover:bg-red-200 transition-colors">
                    <i class="fas fa-external-link-alt mr-1"></i>{{ __('messages.common.view') }}
                </a>
            </div>
            @endif
            <input type="file" name="attachment" accept=".pdf,.doc,.docx"
                class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-[#005B9F]/10 file:text-[#005B9F] hover:file:bg-[#005B9F]/20 cursor-pointer">
            <p class="text-xs text-gray-400 mt-2">{{ __('messages.vendors.attach_hint') }}</p>
        </div>

        {{-- أزرار التحكم --}}
        <div class="mt-8 flex justify-end gap-4 border-t border-gray-200 pt-6 mb-12">
            <a href="{{ route('vendors.index') }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium transition-colors">
                {{ __('messages.common.cancel') }}
            </a>
            <button type="submit" class="px-8 py-2.5 bg-[#008A3B] border border-transparent rounded-lg text-white hover:bg-[#007030] font-bold shadow-lg flex items-center gap-2">
                <i class="fas fa-save"></i>
                {{ __('messages.common.save_changes') }}
            </button>
        </div>
        
    </form>
</div>

<script>
    function toggleBlockReason() {
        var statusSelect = document.getElementById('status');
        var reasonDiv = document.getElementById('block_reason_div');
        var reasonInput = document.getElementById('block_reason');
        
        if (statusSelect.value === 'blocked') {
            reasonDiv.classList.remove('hidden');
            reasonInput.required = true;
        } else {
            reasonDiv.classList.add('hidden');
            reasonInput.required = false;
        }
    }
    window.onload = toggleBlockReason;
</script>
@endsection