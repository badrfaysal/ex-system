@extends('layouts.app')
@section('header_title', __('messages.vendors.title'))

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    {{-- ترويسة الشاشة وزر الإضافة --}}
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
                <i class="fas fa-truck text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.vendors.title') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('messages.vendors.subtitle') }}</p>
            </div>
        </div>
        <a href="{{ route('vendors.create') }}" class="px-6 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030] transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> {{ __('messages.vendors.add') }}
        </a>
    </div>

    {{-- شريط الفلاتر الذكي (بحث وفلترة فورية بدون أزرار) --}}
    <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 mb-6">
        <form id="filterForm" action="{{ route('vendors.index') }}" method="GET" class="flex flex-col lg:flex-row lg:items-center gap-3">

            {{-- البحث الفوري --}}
            <div class="relative flex-1">
                <i class="fas fa-search absolute top-1/2 -translate-y-1/2 right-4 text-gray-400"></i>
                <input type="text" name="search" id="searchInput" value="{{ request('search') }}" autocomplete="off"
                    placeholder="{{ __('messages.vendors.search') }}"
                    class="w-full pr-11 pl-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] focus:bg-white transition-colors">
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                {{-- الفترة الزمنية --}}
                <div class="relative">
                    <i class="fas fa-calendar-alt absolute top-1/2 -translate-y-1/2 right-3 text-gray-400 text-sm pointer-events-none"></i>
                    <select name="date_filter" id="date_filter" onchange="onDateFilterChange()"
                        class="appearance-none pr-9 pl-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] cursor-pointer">
                        <option value="">{{ __('messages.filter.all_times') }}</option>
                        <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>{{ __('messages.filter.yesterday') }}</option>
                        <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>{{ __('messages.filter.this_week') }}</option>
                        <option value="this_year" {{ request('date_filter') == 'this_year' ? 'selected' : '' }}>{{ __('messages.filter.this_year') }}</option>
                        <option value="specific" {{ request('date_filter') == 'specific' ? 'selected' : '' }}>{{ __('messages.filter.specific') }}</option>
                        <option value="range" {{ request('date_filter') == 'range' ? 'selected' : '' }}>{{ __('messages.filter.range') }}</option>
                    </select>
                    <i class="fas fa-chevron-down absolute top-1/2 -translate-y-1/2 left-3 text-gray-400 text-xs pointer-events-none"></i>
                </div>

                {{-- يوم محدد --}}
                <div id="specific_date_div" class="{{ request('date_filter') == 'specific' ? 'block' : 'hidden' }}">
                    <input type="date" name="specific_date" value="{{ request('specific_date') }}" onchange="submitFilters()"
                        class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#008A3B]">
                </div>

                {{-- نطاق زمني --}}
                <div id="range_date_div" class="{{ request('date_filter') == 'range' ? 'flex' : 'hidden' }} items-center gap-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" onchange="submitFilters()"
                        class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#008A3B]">
                    <span class="text-gray-400 text-xs">{{ __('messages.filter.to') }}</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" onchange="submitFilters()"
                        class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#008A3B]">
                </div>

                {{-- زر مسح الفلتر --}}
                @if(request('search') || request('date_filter') || request('specific_date') || request('date_from'))
                    <a href="{{ route('vendors.index') }}" title="{{ __('messages.filter.clear') }}"
                        class="w-10 h-10 flex items-center justify-center border border-gray-200 rounded-xl text-gray-500 hover:bg-gray-50 hover:text-red-500 transition-colors shrink-0">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>

            <button type="submit" class="sr-only">{{ __('messages.common.apply') }}</button>
        </form>
    </div>

    {{-- كارت الجدول --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ __('messages.vendors.code') }}</th>
                        <th class="p-4">{{ __('messages.vendors.name') }}</th>
                        <th class="p-4">{{ __('messages.vendors.group') }}</th>
                        <th class="p-4">{{ __('messages.vendors.mobile') }}</th>
                        <th class="p-4 text-center">{{ __('messages.vendors.status') }}</th>
                    </tr>
                </thead>
         {{-- تعريف خرائط البيانات O(1) --}}
@php
    $vendorGroups = [
        'local'         => ['name' => __('messages.options.vendor_group.local'), 'class' => 'bg-green-50 text-green-700'],
        'international' => ['name' => __('messages.options.vendor_group.international'), 'class' => 'bg-purple-50 text-purple-700'],
        'subcontractor' => ['name' => __('messages.options.vendor_group.subcontractor'), 'class' => 'bg-blue-50 text-blue-700'],
        'government'    => ['name' => __('messages.options.vendor_group.government'), 'class' => 'bg-gray-100 text-gray-700'],
    ];

    $vendorStatuses = [
        'active'   => ['name' => __('messages.options.vendor_status.active'), 'class' => 'bg-green-100 text-green-800'],
        'on_hold'  => ['name' => __('messages.options.vendor_status.on_hold'), 'class' => 'bg-amber-100 text-amber-800'],
        'blocked'  => ['name' => __('messages.options.vendor_status.blocked'), 'class' => 'bg-red-100 text-red-800'],
    ];
@endphp

<tbody class="divide-y divide-gray-100 text-sm">
    @forelse ($vendors as $vendor)
        @php
            $vGroup = $vendorGroups[$vendor->vendor_group] ?? $vendorGroups['government'];
            $vStatus = $vendorStatuses[$vendor->status] ?? $vendorStatuses['active'];
        @endphp
        <tr onclick="openVendorModal({{ json_encode($vendor) }})" class="hover:bg-green-50/40 cursor-pointer transition-colors group">
            <td class="p-4 font-mono font-bold text-gray-600 group-hover:text-[#008A3B]">{{ $vendor->vendor_code }}</td>
            <td class="p-4 font-bold text-gray-900">{{ $vendor->name_ar }}</td>
            <td class="p-4">
                <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $vGroup['class'] }}">{{ $vGroup['name'] }}</span>
            </td>
            <td class="p-4 font-mono text-gray-700" dir="ltr">{{ $vendor->mobile }}</td>
            <td class="p-4 text-center">
                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $vStatus['class'] }}">{{ $vStatus['name'] }}</span>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="p-8 text-center text-gray-500">{{ __('messages.vendors.no_data') }}</td></tr>
    @endforelse
</tbody>
            </table>
        </div>
        @if($vendors->hasPages()) <div class="p-4 border-t border-gray-100 bg-white">{{ $vendors->links() }}</div> @endif
    </div>
</div>

{{-- النافذة المنبثقة الشيك لملف المورد الكامل (Vendor Profile Modal) --}}
<div id="vendorModal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl transform scale-95 transition-transform duration-300" id="modalContent">
        
        {{-- الهيدر --}}
        <div class="flex justify-between items-center p-6 border-b border-gray-100 bg-gray-50 rounded-t-2xl">
            <div>
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-store text-[#005B9F]"></i> {{ __('messages.vendors.profile') }}
                </h3>
                <p class="text-xs text-gray-500 mt-1">{{ __('messages.vendors.p_code') }}: <span id="m_code" class="font-bold font-mono text-gray-700"></span></p>
            </div>
            <button onclick="closeVendorModal()" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors" title="{{ __('messages.common.close') }}">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- الجسم مقسم على هيئة قطاعات تنظيمية شيك --}}
        <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto sidebar-scroll">
            
            {{-- 1. البيانات التجارية الأساسية --}}
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-wider flex items-center gap-1"><i class="fas fa-info-circle"></i> {{ __('messages.vendors.sec_commercial') }}</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div><span class="text-gray-500 block">{{ __('messages.vendors.p_name_ar') }}:</span> <span id="m_name_ar" class="font-bold text-gray-900"></span></div>
                    <div><span class="text-gray-500 block">{{ __('messages.vendors.p_name_en') }}:</span> <span id="m_name_en" class="font-mono text-gray-800"></span></div>
                    <div><span class="text-gray-500 block">{{ __('messages.vendors.p_group') }}:</span> <span id="m_group" class="font-bold text-[#005B9F]"></span></div>
                    <div class="md:col-span-3"><span class="text-gray-500 block">{{ __('messages.vendors.p_legal') }}:</span> <span id="m_legal" class="text-gray-800"></span></div>
                    <div id="m_reason_container" class="md:col-span-3 hidden bg-red-50 p-2.5 rounded-lg border border-red-100 text-red-800"><span class="font-bold">{{ __('messages.vendors.p_block') }}:</span> <span id="m_reason"></span></div>
                </div>
            </div>

            {{-- 2. بيانات الاتصال ومسؤول التواصل --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-gray-100 p-4 rounded-xl shadow-sm">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 flex items-center gap-1"><i class="fas fa-envelope"></i> {{ __('messages.vendors.sec_contact') }}</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="text-gray-500">{{ __('messages.vendors.p_mobile') }}:</span> <span id="m_mobile" class="font-mono font-bold" dir="ltr"></span></div>
                        <div><span class="text-gray-500">{{ __('messages.vendors.p_phone') }}:</span> <span id="m_phone" class="font-mono" dir="ltr"></span></div>
                        <div><span class="text-gray-500">{{ __('messages.vendors.p_email') }}:</span> <span id="m_email" class="font-mono"></span></div>
                        <div><span class="text-gray-500">{{ __('messages.vendors.p_web') }}:</span> <span id="m_web" class="font-mono text-blue-600"></span></div>
                    </div>
                </div>

                <div class="border border-gray-100 p-4 rounded-xl shadow-sm bg-blue-50/30">
                    <h4 class="text-xs font-bold text-[#005B9F] uppercase mb-3 flex items-center gap-1"><i class="fas fa-user-tie"></i> {{ __('messages.vendors.sec_resp') }}</h4>
                    <div class="space-y-2 text-sm">
                        <div><span class="text-gray-500">{{ __('messages.vendors.p_cname') }}:</span> <span id="m_c_name" class="font-bold text-gray-800"></span></div>
                        <div><span class="text-gray-500">{{ __('messages.vendors.p_cjob') }}:</span> <span id="m_c_job"></span></div>
                        <div><span class="text-gray-500">{{ __('messages.vendors.p_cmobile') }}:</span> <span id="m_c_mobile" class="font-mono font-bold" dir="ltr"></span></div>
                        <div><span class="text-gray-500">{{ __('messages.vendors.p_cemail') }}:</span> <span id="m_c_email" class="font-mono"></span></div>
                    </div>
                </div>
            </div>

            {{-- 3. البيانات المالية والبنكية والضرائب --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                <div class="bg-gray-100 px-4 py-2.5 text-xs font-bold text-gray-700 flex items-center gap-1"><i class="fas fa-money-check-alt"></i> {{ __('messages.vendors.sec_finance') }}</div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm bg-white">
                    <div><span class="text-gray-500 block">{{ __('messages.vendors.p_currency') }}:</span> <span id="m_currency" class="font-bold text-emerald-700"></span></div>
                    <div><span class="text-gray-500 block">{{ __('messages.vendors.p_tax') }}:</span> <span id="m_tax_id" class="font-mono font-bold"></span></div>
                    <div><span class="text-gray-500 block">{{ __('messages.vendors.p_registry') }}:</span> <span id="m_comm" class="font-mono"></span></div>
                    <div><span class="text-gray-500 block">{{ __('messages.vendors.p_credit') }}:</span> <span id="m_credit" class="font-bold text-red-600"></span></div>

                    <div class="md:col-span-2 border-t pt-2 mt-2"><span class="text-gray-500">{{ __('messages.vendors.p_bank') }}:</span> <span id="m_bank"></span></div>
                    <div class="md:col-span-2 border-t pt-2 mt-2"><span class="text-gray-500">{{ __('messages.vendors.p_holder') }}:</span> <span id="m_holder"></span></div>
                    <div class="md:col-span-2 border-t pt-2"><span class="text-gray-500">{{ __('messages.vendors.p_account') }}:</span> <span id="m_account" class="font-mono"></span></div>
                    <div class="md:col-span-2 border-t pt-2"><span class="text-gray-500">{{ __('messages.vendors.f_swift') }} / IBAN:</span> <span id="m_iban" class="font-mono text-xs"></span></div>
                </div>
            </div>

            {{-- 4. اللوجستيات وفترة التوريد --}}
            <div class="bg-[#EBF7F0]/40 p-4 rounded-xl border border-green-100 flex items-center justify-between text-sm">
                <div class="flex items-center gap-2 text-gray-800">
                    <i class="fas fa-business-time text-[#008A3B] text-xl"></i>
                    <span>{{ __('messages.vendors.sec_logistics') }}</span>
                </div>
                <div class="flex gap-4 font-bold text-gray-900">
                    <span class="bg-white border px-3 py-1 rounded-md text-xs shadow-sm">{{ __('messages.vendors.p_terms') }}: <span id="m_terms"></span></span>
                    <span class="bg-white border px-3 py-1 rounded-md text-xs shadow-sm">{{ __('messages.vendors.p_lead') }}: <span id="m_lead" class="text-[#008A3B]"></span> {{ __('messages.common.days') }}</span>
                </div>
            </div>

            {{-- 5. المرفق (كتالوج / وثائق) --}}
            <div id="m_attach_wrap" class="hidden">
                <a id="m_attach_link" href="#" target="_blank"
                    class="inline-flex items-center gap-2.5 px-4 py-3 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl transition-colors font-bold text-sm w-full sm:w-auto">
                    <i class="fas fa-file-pdf text-xl text-red-500"></i>
                    <span>{{ __('messages.items.view_attachment') }}</span>
                    <i class="fas fa-external-link-alt text-xs opacity-60 mr-auto"></i>
                </a>
            </div>

        </div>

        {{-- التذييل --}}
        <div class="p-6 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-2xl">
            <button onclick="closeVendorModal()" class="px-5 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">{{ __('messages.common.close') }}</button>
            <button onclick="printVendor()" class="px-5 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium flex items-center gap-2">
                <i class="fas fa-print"></i> {{ __('messages.common.print') }}
            </button>
            <a href="#" id="m_edit_btn" class="px-5 py-2 bg-[#008A3B] text-white rounded-lg hover:bg-[#007030] font-bold flex items-center gap-2">
                <i class="fas fa-pen"></i> {{ __('messages.vendors.edit_profile') }}
            </a>
        </div>
    </div>
</div>

{{-- جافا سكريبت التحكم بالفلاتر والـ Popup --}}
<script>
    // عناوين أساسية تُحقن من Laravel — تجعل الكود يعمل من أي subfolder
    const STORAGE_URL = @json(asset('storage'));
    const VENDORS_URL = @json(url('vendors'));

    // ===== الفلترة والبحث الفوري =====
    const filterForm = document.getElementById('filterForm');

    function submitFilters() {
        const pl = document.getElementById('pageLoader');
        if (pl) pl.classList.add('active');
        filterForm.submit();
    }

    function toggleDateInputs() {
        const filter = document.getElementById('date_filter').value;
        const specificDiv = document.getElementById('specific_date_div');
        const rangeDiv = document.getElementById('range_date_div');

        specificDiv.classList.add('hidden');
        rangeDiv.classList.add('hidden');
        rangeDiv.classList.remove('flex');

        if (filter === 'specific') {
            specificDiv.classList.remove('hidden');
        } else if (filter === 'range') {
            rangeDiv.classList.remove('hidden');
            rangeDiv.classList.add('flex');
        }
    }

    function onDateFilterChange() {
        toggleDateInputs();
        const filter = document.getElementById('date_filter').value;
        if (filter !== 'specific' && filter !== 'range') {
            submitFilters();
        }
    }

    let searchTimer;
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(submitFilters, 450);
    });
    if (searchInput.value) {
        searchInput.focus();
        searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
    }

    // ===== النافذة المنبثقة (Modal) =====
    const modal = document.getElementById('vendorModal');
    const modalContent = document.getElementById('modalContent');
    let currentVendor = null;

    function openVendorModal(vendor) {
        currentVendor = vendor;
        // 1. تعبئة البيانات التجارية والشخصية
        document.getElementById('m_code').innerText = vendor.vendor_code;
        document.getElementById('m_name_ar').innerText = vendor.name_ar;
        document.getElementById('m_name_en').innerText = vendor.name_en || '-';
        document.getElementById('m_legal').innerText = vendor.legal_name || '{{ __("messages.common.not_reg") }}';

        const groupsTranslations = {
            'local': '{{ __("messages.options.vendor_group.local") }}',
            'international': '{{ __("messages.options.vendor_group.international") }}',
            'subcontractor': '{{ __("messages.options.vendor_group.subcontractor") }}',
            'government': '{{ __("messages.options.vendor_group.government") }}'
        };
        document.getElementById('m_group').innerText = groupsTranslations[vendor.vendor_group] || '{{ __("messages.common.none") }}';

        // الحظر والسبب
        const reasonContainer = document.getElementById('m_reason_container');
        if (vendor.status === 'blocked') {
            reasonContainer.classList.remove('hidden');
            document.getElementById('m_reason').innerText = vendor.block_reason || '{{ __("messages.common.not_set") }}';
        } else {
            reasonContainer.classList.add('hidden');
        }

        // 2. الاتصال ومسؤول التواصل
        document.getElementById('m_mobile').innerText = vendor.mobile;
        document.getElementById('m_phone').innerText = vendor.phone || '-';
        document.getElementById('m_email').innerText = vendor.email || '{{ __("messages.common.not_reg") }}';
        document.getElementById('m_web').innerText = vendor.website || '-';

        document.getElementById('m_c_name').innerText = vendor.contact_person_name || '{{ __("messages.common.not_set") }}';
        document.getElementById('m_c_job').innerText = vendor.contact_person_job || '-';
        document.getElementById('m_c_mobile').innerText = vendor.contact_person_mobile || '-';
        document.getElementById('m_c_email').innerText = vendor.contact_person_email || '-';

        // 3. البيانات المالية والبنكية
        document.getElementById('m_currency').innerText = vendor.default_currency;
        document.getElementById('m_tax_id').innerText = vendor.tax_id;
        document.getElementById('m_comm').innerText = vendor.commercial_registry || '-';
        document.getElementById('m_credit').innerText = vendor.credit_limit ? parseFloat(vendor.credit_limit).toLocaleString() + ' ' + vendor.default_currency : '{{ __("messages.vendors.no_limit") }}';

        document.getElementById('m_bank').innerText = (vendor.bank_name || '-') + ' (' + (vendor.bank_branch || '-') + ')';
        document.getElementById('m_holder').innerText = vendor.account_holder || '-';
        document.getElementById('m_account').innerText = vendor.account_number || '-';
        document.getElementById('m_iban').innerText = 'IBAN: ' + (vendor.iban || '-') + ' | SWIFT: ' + (vendor.swift_code || '-');

        // 4. اللوجستيات وشروط الدفع
        const termsTranslations = {
            'cash': '{{ __("messages.options.pay_terms.cash") }}',
            'advance': '{{ __("messages.options.pay_terms.advance") }}',
            '30_days': '{{ __("messages.options.pay_terms.30_days") }}',
            '60_days': '{{ __("messages.options.pay_terms.60_days") }}',
            'cod': '{{ __("messages.options.pay_terms.cod") }}'
        };
        document.getElementById('m_terms').innerText = termsTranslations[vendor.payment_terms] || '{{ __("messages.options.pay_terms.cash") }}';
        document.getElementById('m_lead').innerText = vendor.lead_time_days || '0';

        // ربط زر التعديل بصفحة تعديل هذا المورد
        document.getElementById('m_edit_btn').href = VENDORS_URL + '/' + vendor.id + '/edit';

        // 5. المرفق
        const attachWrap = document.getElementById('m_attach_wrap');
        const attachLink = document.getElementById('m_attach_link');
        if (vendor.attachment_path) {
            attachLink.href = STORAGE_URL + '/' + vendor.attachment_path;
            attachWrap.classList.remove('hidden');
        } else {
            attachWrap.classList.add('hidden');
        }

        // إظهار البوب اب بحركة ناعمة
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    function closeVendorModal() {
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // طباعة ملف المورد الحالي
    function printVendor() {
        if (!currentVendor) return;
        const v = currentVendor;
        const groups = {
            'local': '{{ __("messages.options.vendor_group.local") }}',
            'international': '{{ __("messages.options.vendor_group.international") }}',
            'subcontractor': '{{ __("messages.options.vendor_group.subcontractor") }}',
            'government': '{{ __("messages.options.vendor_group.government") }}'
        };
        const statuses = {
            'active': '{{ __("messages.options.vendor_status.active") }}',
            'on_hold': '{{ __("messages.options.vendor_status.on_hold") }}',
            'blocked': '{{ __("messages.options.vendor_status.blocked") }}'
        };
        const terms = {
            'cash': '{{ __("messages.options.pay_terms.cash") }}',
            'advance': '{{ __("messages.options.pay_terms.advance") }}',
            '30_days': '{{ __("messages.options.pay_terms.30_days") }}',
            '60_days': '{{ __("messages.options.pay_terms.60_days") }}',
            'cod': '{{ __("messages.options.pay_terms.cod") }}'
        };
        printData('{{ __("messages.vendors.profile") }}', v.name_ar + ' — ' + v.vendor_code, [
            ['كود المورد', v.vendor_code],
            ['الاسم العربي', v.name_ar],
            ['الاسم الإنجليزي', v.name_en],
            ['الاسم القانوني', v.legal_name],
            ['المجموعة', groups[v.vendor_group] || v.vendor_group],
            ['الحالة', statuses[v.status] || v.status],
            ['المحمول', v.mobile],
            ['الهاتف الأرضي', v.phone],
            ['البريد الإلكتروني', v.email],
            ['الموقع الإلكتروني', v.website],
            ['مسؤول الاتصال', v.contact_person_name],
            ['وظيفة المسؤول', v.contact_person_job],
            ['موبايل المسؤول', v.contact_person_mobile],
            ['إيميل المسؤول', v.contact_person_email],
            ['العملة الافتراضية', v.default_currency],
            ['الرقم الضريبي', v.tax_id],
            ['السجل التجاري', v.commercial_registry],
            ['الحد الائتماني', v.credit_limit],
            ['البنك / الفرع', (v.bank_name || '-') + ' / ' + (v.bank_branch || '-')],
            ['صاحب الحساب', v.account_holder],
            ['رقم الحساب', v.account_number],
            ['IBAN', v.iban],
            ['SWIFT', v.swift_code],
            ['شروط الدفع', terms[v.payment_terms] || v.payment_terms],
            ['فترة التوريد (أيام)', v.lead_time_days],
        ]);
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            closeVendorModal();
        }
    }
</script>
@endsection