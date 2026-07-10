@extends('layouts.app')
@section('header_title', __('messages.items.title'))

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    {{-- ترويسة الشاشة وزر الإضافة --}}
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-500">
                <i class="fas fa-boxes text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.items.title') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('messages.items.subtitle') }}</p>
            </div>
        </div>
        <a href="{{ route('items.create') }}" class="px-6 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030] transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> {{ __('messages.items.add') }}
        </a>
    </div>

    {{-- شريط الفلاتر الذكي --}}
    <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 mb-6">
        <form id="filterForm" action="{{ route('items.index') }}" method="GET" class="flex flex-col lg:flex-row lg:items-center gap-3">

            {{-- البحث الفوري --}}
            <div class="relative flex-1">
                <i class="fas fa-search absolute top-1/2 -translate-y-1/2 right-4 text-gray-400"></i>
                <input type="text" name="search" id="searchInput" value="{{ request('search') }}" autocomplete="off"
                    placeholder="{{ __('messages.items.search') }}"
                    class="w-full pr-11 pl-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:bg-white transition-colors">
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                {{-- الفترة الزمنية --}}
                <div class="relative">
                    <i class="fas fa-calendar-alt absolute top-1/2 -translate-y-1/2 right-3 text-gray-400 text-sm pointer-events-none"></i>
                    <select name="date_filter" id="date_filter" onchange="onDateFilterChange()"
                        class="appearance-none pr-9 pl-8 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 cursor-pointer">
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
                        class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                </div>

                {{-- نطاق زمني --}}
                <div id="range_date_div" class="{{ request('date_filter') == 'range' ? 'flex' : 'hidden' }} items-center gap-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" onchange="submitFilters()"
                        class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                    <span class="text-gray-400 text-xs">{{ __('messages.filter.to') }}</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" onchange="submitFilters()"
                        class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-amber-500">
                </div>

                {{-- زر مسح الفلتر --}}
                @if(request('search') || request('date_filter') || request('specific_date') || request('date_from'))
                    <a href="{{ route('items.index') }}" title="{{ __('messages.filter.clear') }}"
                        class="w-10 h-10 flex items-center justify-center border border-gray-200 rounded-xl text-gray-500 hover:bg-gray-50 hover:text-red-500 transition-colors shrink-0">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </div>

            <button type="submit" class="sr-only">{{ __('messages.common.apply') }}</button>
        </form>
    </div>

    {{-- كارت الجدول الرئيسي --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ __('messages.items.code') }}</th>
                        <th class="p-4">{{ __('messages.items.barcode') }}</th>
                        <th class="p-4">{{ __('messages.items.name') }}</th>
                        <th class="p-4">{{ __('messages.items.group') }}</th>
                        <th class="p-4">{{ __('messages.items.uom') }}</th>
                        <th class="p-4">{{ __('messages.items.stock') }}</th>
                        <th class="p-4 text-center">{{ __('messages.items.status') }}</th>
                    </tr>
                </thead>
                @php
                    $grpColors = ['bg-green-50 text-green-700','bg-blue-50 text-blue-700','bg-purple-50 text-purple-700','bg-amber-50 text-amber-700','bg-teal-50 text-teal-700','bg-orange-50 text-orange-700'];
                    $grpPhpMap = [];
                    foreach ($settingGroups as $i => $g) {
                        $grpPhpMap[$g->key_value] = ['name' => $g->display_name, 'class' => $grpColors[$i % count($grpColors)]];
                    }
                    $uomPhpMap    = $settingUoms->pluck('display_name', 'key_value')->toArray();
                    $subGrpPhpMap = $settingSubGroups->pluck('display_name', 'key_value')->toArray();
                    $statusPhpMap = [
                        'active'    => ['name' => __('messages.options.item_status.active'),    'class' => 'bg-green-100 text-green-800 border border-green-200'],
                        'suspended' => ['name' => __('messages.options.item_status.suspended'), 'class' => 'bg-amber-100 text-amber-800 border border-amber-200'],
                        'obsolete'  => ['name' => __('messages.options.item_status.obsolete'),  'class' => 'bg-red-100 text-red-800 border border-red-200'],
                    ];
                @endphp

                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($items as $item)
                        @php
                            $iGroup  = $grpPhpMap[$item->item_group] ?? ['name' => $item->item_group ?? '—', 'class' => 'bg-gray-100 text-gray-700'];
                            $iStatus = $statusPhpMap[$item->status]  ?? $statusPhpMap['active'];
                            $iUom    = $uomPhpMap[$item->base_uom]   ?? $item->base_uom;
                        @endphp
                        <tr onclick="openItemModal({{ json_encode($item) }})" class="hover:bg-amber-50/40 cursor-pointer transition-colors group">
                            <td class="p-4 font-mono font-bold text-gray-600 group-hover:text-amber-600">{{ $item->item_code }}</td>
                            <td class="p-4 font-mono text-gray-500">{{ $item->barcode ?? '-' }}</td>
                            <td class="p-4">
                                <p class="font-bold text-gray-900">{{ $item->name_ar }}</p>
                                @if($item->name_en)
                                    <p class="text-xs text-gray-400 font-mono mt-0.5" dir="ltr">{{ $item->name_en }}</p>
                                @endif
                            </td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $iGroup['class'] }}">{{ $iGroup['name'] }}</span>
                                @if($item->sub_category)
                                    <p class="text-xs text-gray-500 mt-1">{{ $subGrpPhpMap[$item->sub_category] ?? $item->sub_category }}</p>
                                @endif
                            </td>
                            <td class="p-4 text-gray-700 font-medium">{{ $iUom }}</td>
                            <td class="p-4">
                                <div class="text-xs text-gray-600">{{ __('messages.items.f_reorder') }}: <span class="font-bold text-gray-900">{{ $item->reorder_point ?? 0 }}</span></div>
                                <div class="text-xs text-red-500 mt-0.5">{{ __('messages.items.f_min') }}: <span class="font-bold">{{ $item->min_stock ?? 0 }}</span></div>
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $iStatus['class'] }}">{{ $iStatus['name'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-gray-500">{{ __('messages.items.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages()) <div class="p-4 border-t border-gray-100 bg-white">{{ $items->links() }}</div> @endif
    </div>
</div>

{{-- النافذة المنبثقة للبطاقة الفنية للصنف (تعرض كامل البيانات) --}}
<div id="itemModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 transition-all duration-300">
    <div class="relative bg-gray-50 rounded-2xl shadow-2xl w-full max-w-4xl transform scale-95 transition-transform duration-300 overflow-hidden flex flex-col max-h-[95vh]" id="modalContent">

        {{-- الهيدر العلوي الأبيض --}}
        <div class="flex items-center justify-between p-5 border-b border-gray-200 bg-white shrink-0">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-500 flex items-center justify-center text-2xl shadow-inner">
                    <i class="fas fa-box"></i>
                </div>
                <div>
                    <h3 id="m_name_ar" class="text-xl sm:text-2xl font-black text-gray-900 leading-tight"></h3>
                    <div class="flex items-center gap-3 mt-1">
                        <span id="m_code2" class="text-xs text-gray-500 font-mono font-bold bg-gray-100 px-2 py-0.5 rounded"></span>
                        <p id="m_name_en" class="text-xs text-gray-400 font-mono" dir="ltr"></p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span id="m_status_badge" class="px-3 py-1 rounded-full text-xs font-bold shadow-sm border"></span>
                <button onclick="closeItemModal()" class="text-gray-400 hover:text-red-500 hover:bg-red-50 w-10 h-10 rounded-xl flex items-center justify-center transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
        </div>

        {{-- منطقة المحتوى القابلة للتمرير --}}
        <div class="overflow-y-auto p-6 sidebar-scroll flex-1">
            <div class="flex flex-col md:flex-row gap-6">
                
                {{-- العمود الأيمن (الصور والملفات) --}}
                <div class="w-full md:w-1/3 flex flex-col gap-4">
                    <div class="aspect-square rounded-2xl bg-white border border-gray-200 overflow-hidden relative shadow-sm flex items-center justify-center group">
                        <img id="m_featured_img" src="" class="w-full h-full object-cover hidden cursor-zoom-in group-hover:scale-105 transition-transform duration-500" onclick="openLightbox(this.src)">
                        <div id="m_featured_placeholder" class="text-gray-300 flex flex-col items-center">
                            <i class="fas fa-image text-5xl mb-2 opacity-50"></i>
                            <span class="text-xs font-medium">{{ __('messages.items.no_img') }}</span>
                        </div>
                    </div>
                    <div id="m_thumb_row" class="hidden flex gap-2 overflow-x-auto pb-2 sidebar-scroll"></div>
                    
                    {{-- زر المرفقات --}}
                    <div id="m_attach_wrap" class="hidden mt-2">
                        <a id="m_attach_link" href="#" target="_blank" class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl transition-colors font-bold text-sm">
                            <i class="fas fa-file-pdf text-lg"></i> {{ __('messages.items.view_attachment') }}
                        </a>
                    </div>
                </div>

                {{-- العمود الأيسر (البيانات الكاملة) --}}
                <div class="w-full md:w-2/3 space-y-6">
                    
                    {{-- شريط التصنيفات والبيانات الهيكلية --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white border border-gray-200 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm">
                            <i class="fas fa-folder text-blue-500 text-base"></i>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 font-bold uppercase">{{ __('messages.items.m_group') }}</span>
                                <span id="m_group_badge" class="font-bold text-gray-800 text-sm mt-0.5"></span>
                            </div>
                        </div>
                        <div class="bg-white border border-gray-200 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm">
                            <i class="fas fa-folder-open text-indigo-500 text-base"></i>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 font-bold uppercase">المجموعة الفرعية</span>
                                <span id="m_sub_category_badge" class="font-bold text-gray-800 text-sm mt-0.5"></span>
                            </div>
                        </div>
                        <div class="bg-white border border-gray-200 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm">
                            <i class="fas fa-balance-scale text-green-500 text-base"></i>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 font-bold uppercase">{{ __('messages.items.m_uom') }}</span>
                                <span id="m_uom_badge" class="font-bold text-gray-800 text-sm mt-0.5"></span>
                            </div>
                        </div>
                        <div class="bg-white border border-gray-200 px-4 py-3 rounded-xl flex items-center gap-3 shadow-sm">
                            <i class="fas fa-barcode text-amber-500 text-base"></i>
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 font-bold uppercase">{{ __('messages.items.m_barcode') }}</span>
                                <span id="m_barcode_val" class="font-bold text-gray-800 font-mono text-sm mt-0.5"></span>
                            </div>
                        </div>
                    </div>

                    {{-- قسم التوريد والمشتريات المعقد --}}
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                        <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2 border-b border-gray-100 pb-2">
                            <i class="fas fa-truck text-[#005B9F]"></i> بيانات التوريد والمشتريات
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 font-medium">{{ __('messages.items.m_vendor') }}</span>
                                <span id="m_vendor" class="font-bold text-gray-900 text-sm"></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 font-medium">{{ __('messages.items.m_supplier') }}</span>
                                <span id="m_sup_part" class="font-bold font-mono text-gray-600 text-sm"></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 font-medium">{{ __('messages.items.m_moq') }}</span>
                                <span id="m_moq" class="font-bold text-amber-600 font-mono text-sm"></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                                <span class="text-xs text-gray-500 font-medium">{{ __('messages.items.m_lead') }}</span>
                                <span id="m_lead_time" class="font-bold text-[#008A3B] text-sm"></span>
                            </div>
                        </div>
                    </div>

                    {{-- قسم محددات المخزون والرقابة الرقمية --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                            <i class="fas fa-chart-line text-amber-500"></i> محددات الرقابة والمخزون الحرج
                        </h4>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-red-50 p-4 rounded-2xl border border-red-100 flex flex-col items-center justify-center text-center shadow-sm">
                                <span class="text-xs text-red-600 font-bold mb-1">{{ __('messages.items.m_safety') }}</span>
                                <span id="m_min" class="text-2xl font-black text-red-700 font-mono"></span>
                            </div>
                            <div class="bg-amber-50 p-4 rounded-2xl border border-amber-100 flex flex-col items-center justify-center text-center shadow-sm">
                                <span class="text-xs text-amber-700 font-bold mb-1">{{ __('messages.items.m_reorder') }}</span>
                                <span id="m_reorder" class="text-2xl font-black text-amber-800 font-mono"></span>
                            </div>
                            <div class="bg-green-50 p-4 rounded-2xl border border-green-100 flex flex-col items-center justify-center text-center shadow-sm">
                                <span class="text-xs text-green-700 font-bold mb-1">{{ __('messages.items.m_max') }}</span>
                                <span id="m_max" class="text-2xl font-black text-green-800 font-mono"></span>
                            </div>
                        </div>
                    </div>

                    {{-- قسم تواريخ وتفاصيل النظام الحسابية --}}
                    <div class="bg-slate-100/80 p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row justify-between gap-4 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-plus-circle opacity-70"></i>
                            <span>تاريخ الإضافة للنظام:</span>
                            <span id="m_created_at" class="font-bold text-slate-700 font-mono"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-edit opacity-70"></i>
                            <span>آخر تحديث مالي/مخزني:</span>
                            <span id="m_updated_at" class="font-bold text-slate-700 font-mono"></span>
                        </div>
                    </div>

                </div>
            </div>

            {{-- معرض صور الجودة الإضافية الفني --}}
            <div id="m_gallery_section" class="hidden mt-8 pt-6 border-t border-gray-200">
                <h4 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-camera text-purple-500"></i> {{ __('messages.items.qc_gallery') }}
                    <span id="m_gallery_count" class="bg-purple-100 text-purple-700 text-xs px-2 py-0.5 rounded-full font-bold ml-1"></span>
                </h4>
                <div id="m_gallery_grid" class="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-6 gap-3"></div>
            </div>
        </div>

        {{-- التذييل وأزرار التحكم --}}
        <div class="p-5 bg-white border-t border-gray-200 flex justify-end gap-3 shrink-0 rounded-b-2xl">
            <button onclick="closeItemModal()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-bold text-sm transition-colors shadow-sm">
                {{ __('messages.common.close') }}
            </button>
            <button onclick="printItem()" class="px-5 py-2.5 bg-white border border-gray-300 text-[#005B9F] rounded-xl hover:bg-blue-50 font-bold text-sm flex items-center gap-2 transition-colors shadow-sm">
                <i class="fas fa-print"></i> {{ __('messages.common.print') }}
            </button>
            <a href="#" id="m_edit_btn" class="px-6 py-2.5 bg-amber-500 text-white rounded-xl hover:bg-amber-600 font-bold text-sm flex items-center gap-2 shadow-sm transition-colors">
                <i class="fas fa-pen"></i> {{ __('messages.items.edit') }}
            </a>
        </div>
    </div>
</div>

{{-- جافا سكريبت التحكم بالفلاتر والـ Popup --}}
<script>
    // عناوين أساسية تُحقن من Laravel — تجعل الكود يعمل من أي subfolder
    const BASE_URL    = @json(url('/'));
    const STORAGE_URL = @json(asset('storage'));
    const ITEMS_URL   = @json(url('items'));

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

    // ===== النافذة المنبثقة (Modal) بالتصميم الجديد وعرض كامل البيانات =====
    const modal = document.getElementById('itemModal');
    const modalContent = document.getElementById('modalContent');
    let currentItem = null;

    // جلب القوائم من الـ DB للترجمة المباشرة
    const groupsMap    = @json($settingGroups->pluck('display_name', 'key_value'));
    const uomsMap      = @json($settingUoms->pluck('display_name', 'key_value'));
    const subGroupsMap = @json($settingSubGroups->pluck('display_name', 'key_value'));

    function openItemModal(item) {
        currentItem = item;
        const notSet   = '{{ __("messages.common.not_set") }}';
        const noVendor = '{{ __("messages.items.m_no_vendor") }}';
        const daysLbl  = '{{ __("messages.common.days") }}';

        // تعبئة النصوص الأساسية الهيدر
        document.getElementById('m_name_ar').innerText = item.name_ar;
        document.getElementById('m_name_en').innerText = item.name_en || '';
        document.getElementById('m_code2').innerText   = item.item_code;
        document.getElementById('m_barcode_val').innerText = item.barcode || '-';

        // المجموعات والوحدات
        const grpLabel = groupsMap[item.item_group] || item.item_group || notSet;
        const uomLabel = uomsMap[item.base_uom]     || item.base_uom   || notSet;
        document.getElementById('m_group_badge').innerText = grpLabel;
        document.getElementById('m_uom_badge').innerText   = uomLabel;
        
        // عرض المجموعة الفرعية بالاسم من الإعدادات
        document.getElementById('m_sub_category_badge').innerText = subGroupsMap[item.sub_category] || item.sub_category || notSet;

        // الحالة وتنسيق البادج الخاص بها
        const statusMap = {
            'active':    ['{{ __("messages.items.st_active") }}',    'bg-green-100 text-green-800 border-green-200'],
            'suspended': ['{{ __("messages.items.st_suspended") }}', 'bg-amber-100 text-amber-800 border-amber-200'],
            'obsolete':  ['{{ __("messages.items.st_obsolete") }}',  'bg-red-100 text-red-800 border-red-200'],
        };
        const [stText, stClass] = statusMap[item.status] || [item.status, 'bg-gray-100 text-gray-800 border-gray-200'];
        const stBadge = document.getElementById('m_status_badge');
        stBadge.innerText = stText;
        stBadge.className = 'px-3 py-1 rounded-full text-xs font-bold shadow-sm border ' + stClass;

        // كروت محددات الرقابة والمخزون
        document.getElementById('m_reorder').innerText = item.reorder_point ?? '0';
        document.getElementById('m_min').innerText     = item.min_stock     ?? '0';
        document.getElementById('m_max').innerText     = item.max_stock     ?? '-';

        // بيانات المشتريات واللوجستيات
        const vendorName = (item.default_vendor && item.default_vendor.name_ar) ? item.default_vendor.name_ar : (item.default_vendor_id ? '#' + item.default_vendor_id : noVendor);
        document.getElementById('m_vendor').innerText    = vendorName;
        document.getElementById('m_sup_part').innerText  = item.supplier_part_number || '-';
        document.getElementById('m_moq').innerText       = item.moq || '1';
        document.getElementById('m_lead_time').innerText = item.lead_time_days ? item.lead_time_days + ' ' + daysLbl : notSet;

        // تواريخ النظام الحسابية الكاملة
        document.getElementById('m_created_at').innerText = item.created_at ? new Date(item.created_at).toLocaleString('ar-EG') : '-';
        document.getElementById('m_updated_at').innerText = item.updated_at ? new Date(item.updated_at).toLocaleString('ar-EG') : '-';

        // المرفقات التقنية (PDF)
        const attachWrap = document.getElementById('m_attach_wrap');
        const attachLink = document.getElementById('m_attach_link');
        if (item.attachment_path) {
            attachLink.href = STORAGE_URL + '/' + item.attachment_path;
            attachWrap.classList.remove('hidden');
        } else {
            attachWrap.classList.add('hidden');
        }

        // رابط تعديل الصنف
        document.getElementById('m_edit_btn').href = ITEMS_URL + '/' + item.id + '/edit';

        // معالجة وإدارة ألبوم الصور وضبط الجودة
        const featuredImg    = document.getElementById('m_featured_img');
        const featuredPh     = document.getElementById('m_featured_placeholder');
        const thumbRow       = document.getElementById('m_thumb_row');
        const gallerySection = document.getElementById('m_gallery_section');
        const galleryGrid    = document.getElementById('m_gallery_grid');
        const galleryCount   = document.getElementById('m_gallery_count');
        const imgCatLabels   = {
            'main': 'الرئيسية', 'before_opening': 'قبل الفتح', 'after_opening': 'بعد الفتح', 'barcode_label': 'ملصق', 'other': 'أخرى'
        };

        galleryGrid.innerHTML = '';
        thumbRow.innerHTML    = '';

        if (item.images && item.images.length > 0) {
            const firstSrc  = STORAGE_URL + '/' + item.images[0].image_path;
            featuredImg.src = firstSrc;
            featuredImg.classList.remove('hidden');
            featuredPh.classList.add('hidden');

            if (item.images.length > 1) {
                thumbRow.classList.remove('hidden');
                item.images.forEach((img, idx) => {
                    const src  = STORAGE_URL + '/' + img.image_path;
                    const th   = document.createElement('img');
                    th.src     = src;
                    th.className = 'h-12 w-16 rounded-lg object-cover border-2 cursor-pointer shrink-0 transition-all ' + (idx === 0 ? 'border-amber-500 opacity-100' : 'border-transparent opacity-60 hover:opacity-100');
                    th.onclick = () => {
                        featuredImg.src = src;
                        thumbRow.querySelectorAll('img').forEach(t => t.className = t.className.replace('border-amber-500 opacity-100','border-transparent opacity-60 hover:opacity-100'));
                        th.className = th.className.replace('border-transparent opacity-60 hover:opacity-100','border-amber-500 opacity-100');
                    };
                    thumbRow.appendChild(th);
                });
            } else {
                thumbRow.classList.add('hidden');
            }

            gallerySection.classList.remove('hidden');
            galleryCount.textContent = item.images.length;
            item.images.forEach(img => {
                const src      = STORAGE_URL + '/' + img.image_path;
                const catLabel = imgCatLabels[img.category] || img.category;
                const card     = document.createElement('div');
                card.className = 'group relative rounded-xl overflow-hidden border border-gray-200 cursor-pointer hover:shadow-lg transition-all';
                card.onclick   = () => openLightbox(src);
                card.innerHTML = `
                    <div class="aspect-square bg-gray-100 overflow-hidden">
                        <img src="${src}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                    </div>
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-gray-900/80 to-transparent pt-4 pb-1.5 px-2">
                        <span class="text-[10px] font-bold text-white">${catLabel}</span>
                    </div>`;
                galleryGrid.appendChild(card);
            });
        } else {
            featuredImg.classList.add('hidden');
            featuredPh.classList.remove('hidden');
            thumbRow.classList.add('hidden');
            gallerySection.classList.add('hidden');
        }

        // تشغيل البوب أب بحركة ناعمة
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    function closeItemModal() {
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    function printItem() {
        if (!currentItem) return;
        const i      = currentItem;
        const isAr   = '{{ app()->getLocale() }}' === 'ar';
        const dir    = isAr ? 'rtl' : 'ltr';
        const align  = isAr ? 'right' : 'left';
        const opp    = isAr ? 'left' : 'right';
        const brand  = '{{ __("messages.app_name") }}';
        const sub    = '{{ __("messages.app_sub") }}';
        const logoUrl = '{{ asset("images/EFC-.png") }}';

        const vendor = (i.default_vendor && i.default_vendor.name_ar)
            ? i.default_vendor.name_ar
            : (i.default_vendor_id ? '#' + i.default_vendor_id : '—');

        const grp = groupsMap[i.item_group] || i.item_group || '—';
        const uom = uomsMap[i.base_uom]     || i.base_uom   || '—';
        const img = (i.images && i.images.length > 0) ? STORAGE_URL + '/' + i.images[0].image_path : '';
        const now = new Date().toLocaleString(isAr ? 'ar-EG' : 'en-GB', { dateStyle: 'long', timeStyle: 'short' });

        const stMap = {
            active:    { label: isAr ? 'نشط'    : 'Active',    cls: 'color:#166534;background:#dcfce7;' },
            suspended: { label: isAr ? 'موقوف'  : 'Suspended', cls: 'color:#854d0e;background:#fef9c3;' },
            obsolete:  { label: isAr ? 'منتهي'  : 'Obsolete',  cls: 'color:#991b1b;background:#fee2e2;' },
        };
        const st = stMap[i.status] || stMap.active;

        // صف بيانات بدون ألوان ولا إيموجي
        const row = (label, val) => `
            <tr>
                <td style="padding:7px 12px;font-size:11.5px;font-weight:700;color:#475569;width:38%;border-bottom:1px solid #f1f5f9;white-space:nowrap;">${label}</td>
                <td style="padding:7px 12px;font-size:11.5px;font-weight:600;color:#0f172a;border-bottom:1px solid #f1f5f9;">${val || '—'}</td>
            </tr>`;

        const sec = (label) => `
            <tr style="background:#f8fafc;">
                <td colspan="2" style="padding:6px 12px;font-size:10px;font-weight:800;color:#64748b;letter-spacing:.06em;text-transform:uppercase;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;">
                    ${label}
                </td>
            </tr>`;

        const stockCell = (label, val) => `
            <td style="text-align:center;padding:10px 6px;border-left:1px solid #e2e8f0;">
                <div style="font-size:10px;color:#64748b;font-weight:700;margin-bottom:3px;">${label}</div>
                <div style="font-size:20px;font-weight:900;color:#0f172a;font-family:monospace;">${val ?? 0}</div>
            </td>`;

        const imgBlock = img
            ? `<img src="${img}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.nextSibling.style.display='flex';">
               <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;">${isAr?'لا توجد صورة':'No image'}</div>`
            : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;">${isAr?'لا توجد صورة':'No image'}</div>`;

        const html = `<!DOCTYPE html>
<html lang="${isAr?'ar':'en'}" dir="${dir}">
<head>
<meta charset="UTF-8">
<title>${i.name_ar}</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
  @page { margin: 12mm 14mm; size: A4 portrait; }
  * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; margin: 0; padding: 0; }
  body { font-family: 'Cairo', Arial, sans-serif; color: #1e293b; font-size: 13px; background: #fff; }
  table { border-collapse: collapse; width: 100%; }
</style>
</head>
<body>

<!-- هيدر -->
<table style="margin-bottom:14px;">
  <tr>
    <td style="vertical-align:middle;">
      <img src="${logoUrl}" alt="${brand}" style="height:52px;width:auto;object-fit:contain;"
           onerror="this.style.display='none'">
    </td>
    <td style="text-align:center;vertical-align:middle;">
      <div style="font-size:15px;font-weight:900;color:#0f172a;">${brand}</div>
      <div style="font-size:10px;color:#64748b;margin-top:1px;">${sub}</div>
    </td>
    <td style="text-align:${opp};vertical-align:middle;">
      <div style="font-size:13px;font-weight:800;color:#005B9F;">${isAr ? 'بطاقة الصنف الفنية' : 'Item Technical Card'}</div>
      <div style="font-size:10px;color:#94a3b8;margin-top:2px;">${now}</div>
    </td>
  </tr>
</table>

<!-- شريط فاصل -->
<div style="height:2px;background:linear-gradient(90deg,#005B9F,#008A3B);margin-bottom:12px;border-radius:1px;"></div>

<!-- اسم الصنف والحالة -->
<table style="margin-bottom:12px;">
  <tr>
    <td style="vertical-align:top;">
      <div style="font-size:18px;font-weight:900;color:#0f172a;line-height:1.25;">${i.name_ar}</div>
      ${i.name_en ? `<div style="font-size:11px;color:#64748b;margin-top:2px;direction:ltr;">${i.name_en}</div>` : ''}
    </td>
    <td style="text-align:${opp};vertical-align:top;white-space:nowrap;padding-${align}:10px;">
      <span style="display:inline-block;padding:3px 12px;border-radius:4px;font-size:11px;font-weight:800;${st.cls}">${st.label}</span>
      <div style="margin-top:5px;font-family:monospace;font-size:11px;font-weight:700;color:#475569;direction:ltr;">${i.item_code}</div>
    </td>
  </tr>
</table>

<!-- الجسم: صورة + جدول بيانات -->
<table style="margin-bottom:12px;border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;">
  <tr style="vertical-align:top;">

    <!-- صورة الصنف -->
    <td style="width:170px;padding:0;border-left:1px solid #e2e8f0;vertical-align:top;">
      <div style="width:170px;height:200px;overflow:hidden;background:#f8fafc;">
        ${imgBlock}
      </div>
      ${i.barcode ? `<div style="text-align:center;padding:6px;border-top:1px solid #e2e8f0;font-family:monospace;font-size:10px;color:#64748b;direction:ltr;">${i.barcode}</div>` : ''}
    </td>

    <!-- البيانات الأساسية -->
    <td style="vertical-align:top;padding:0;">
      <table style="width:100%;">
        ${sec(isAr ? 'البيانات الأساسية' : 'Basic Information')}
        ${row(isAr ? 'المجموعة' : 'Group', grp)}
        ${row(isAr ? 'وحدة القياس' : 'Unit of Measure', uom)}
        ${row(isAr ? 'الباركود' : 'Barcode', `<span style="direction:ltr;display:inline-block;">${i.barcode||'—'}</span>`)}
        ${sec(isAr ? 'بيانات التوريد' : 'Procurement')}
        ${row(isAr ? 'المورد الرئيسي' : 'Main Vendor', vendor)}
        ${row(isAr ? 'رقم القطعة (مورد)' : 'Supplier P/N', i.supplier_part_number||'—')}
        ${row(isAr ? 'الحد الأدنى للطلب' : 'Min. Order Qty (MOQ)', i.moq ? String(i.moq) + (isAr?' وحدة':' units') : '—')}
        ${row(isAr ? 'مدة التوريد المتوقعة' : 'Lead Time', i.lead_time_days ? i.lead_time_days + (isAr?' يوم':' days') : '—')}
      </table>
    </td>
  </tr>
</table>

<!-- محددات المخزون -->
<div style="font-size:10px;font-weight:800;color:#64748b;letter-spacing:.06em;text-transform:uppercase;margin-bottom:6px;">
    ${isAr ? 'محددات رقابة المخزون' : 'Inventory Control Limits'}
</div>
<table style="border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;margin-bottom:16px;text-align:center;">
  <tr>
    <td style="width:33%;padding:10px 6px;text-align:center;">
        <div style="font-size:10px;color:#64748b;font-weight:700;margin-bottom:3px;">${isAr?'حد الأمان':'Safety Stock'}</div>
        <div style="font-size:22px;font-weight:900;color:#0f172a;font-family:monospace;">${i.min_stock ?? 0}</div>
    </td>
    <td style="width:33%;padding:10px 6px;text-align:center;border-right:1px solid #e2e8f0;border-left:1px solid #e2e8f0;">
        <div style="font-size:10px;color:#64748b;font-weight:700;margin-bottom:3px;">${isAr?'نقطة إعادة الطلب':'Reorder Point'}</div>
        <div style="font-size:22px;font-weight:900;color:#0f172a;font-family:monospace;">${i.reorder_point ?? 0}</div>
    </td>
    <td style="width:33%;padding:10px 6px;text-align:center;">
        <div style="font-size:10px;color:#64748b;font-weight:700;margin-bottom:3px;">${isAr?'الحد الأقصى':'Max Stock'}</div>
        <div style="font-size:22px;font-weight:900;color:#0f172a;font-family:monospace;">${i.max_stock ?? '—'}</div>
    </td>
  </tr>
</table>

<!-- توقيعات -->
<table style="margin-top:6px;">
  <tr>
    <td style="width:40%;text-align:center;border-top:1px dashed #cbd5e1;padding-top:8px;font-size:10.5px;color:#94a3b8;">${isAr?'مسؤول المخازن':'Warehouse Manager'}</td>
    <td style="width:20%;"></td>
    <td style="width:40%;text-align:center;border-top:1px dashed #cbd5e1;padding-top:8px;font-size:10.5px;color:#94a3b8;">${isAr?'مدير المشتريات':'Procurement Manager'}</td>
  </tr>
</table>

<!-- تذييل -->
<div style="margin-top:14px;padding-top:6px;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;font-size:9.5px;color:#94a3b8;">
    <span>${brand} &mdash; ${sub}</span>
    <span style="direction:ltr;">${i.item_code} &nbsp;|&nbsp; ${now}</span>
</div>

</body></html>`;

        const w = window.open('', '_blank', 'width=860,height=720');
        if (!w) { alert(isAr ? 'فضلاً اسمح بالنوافذ المنبثقة.' : 'Please allow pop-ups.'); return; }
        w.document.write(html);
        w.document.close();
        w.focus();
        setTimeout(() => w.print(), 500);
    }

    window.onclick = function(event) {
        if (event.target == modal) closeItemModal();
    }

    function openLightbox(src) {
        document.getElementById('lightbox')?.remove();
        const lb = document.createElement('div');
        lb.id = 'lightbox';
        lb.className = 'fixed inset-0 z-[9999] bg-slate-900/95 backdrop-blur-sm flex items-center justify-center p-4 cursor-zoom-out animate-fade-in';
        lb.innerHTML = `
            <button onclick="event.stopPropagation(); document.getElementById('lightbox').remove()" class="absolute top-6 right-6 text-white/50 hover:text-white bg-white/10 hover:bg-white/20 p-3 rounded-full transition-all z-10">
                <i class="fas fa-times text-xl"></i>
            </button>
            <img src="${src}" class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl select-none transition-transform">`;
        lb.onclick = () => lb.remove();
        document.body.appendChild(lb);
    }
</script>
@endsection