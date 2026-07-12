@extends('layouts.app')
@section('header_title', __('messages.sourcing.header'))

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">
    
    {{-- ترويسة الشاشة --}}
    <div class="mb-6 border-b border-slate-200 pb-4 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2 tracking-tight">
                <i class="fas fa-network-wired text-[#005B9F]"></i> {{ __('messages.sourcing.title') }}
            </h2>
            <p class="text-sm text-slate-500 mt-1 font-medium">{{ __('messages.sourcing.subtitle') }}</p>
        </div>
        <button onclick="toggleAttachForm()" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-sm text-sm font-bold hover:bg-slate-50 transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-link text-[#008A3B]"></i> {{ __('messages.sourcing.link_btn') }}
        </button>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-[#008A3B] text-green-800 px-4 py-3 text-sm font-bold shadow-sm flex items-center gap-2">
            <i class="fas fa-check-circle text-[#008A3B]"></i> {{ session('success') }}
        </div>
    @endif

    {{-- فورم الربط السريع (مخفي افتراضياً يظهر عند الطلب لتوفير المساحة) --}}
    <div id="attachFormContainer" class="hidden bg-slate-50 border border-slate-200 rounded-sm p-5 mb-8 shadow-sm animate-fade-in">
        <h4 class="text-sm font-bold text-slate-800 mb-4 pb-2 border-b border-slate-200"><i class="fas fa-plus-circle text-[#008A3B]"></i> {{ __('messages.sourcing.form_title') }}</h4>
        <form action="{{ route('sourcing.attach') }}" method="POST" class="flex flex-col md:flex-row gap-4 items-end">
            @csrf
            <div class="flex-1 w-full relative">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">{{ __('messages.sourcing.step1') }}</label>
                <input type="hidden" name="item_id" id="attachItemId" required>
                <input type="text" id="attachItemSearch" autocomplete="off" placeholder="{{ __('messages.sourcing.step1_ph') }}"
                    class="w-full px-3 py-2 border border-slate-300 rounded-sm focus:outline-none focus:border-[#008A3B] text-sm bg-white"
                    oninput="debouncedSearch('attachItem')" onfocus="debouncedSearch('attachItem')">
                <div id="attachItemDropdown" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-slate-200 shadow-lg rounded-sm hidden max-h-52 overflow-y-auto"></div>
            </div>
            <div class="flex-1 w-full relative">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">{{ __('messages.sourcing.step2') }}</label>
                <input type="hidden" name="vendor_id" id="attachVendorId" required>
                <input type="text" id="attachVendorSearch" autocomplete="off" placeholder="{{ __('messages.sourcing.step2_ph') }}"
                    class="w-full px-3 py-2 border border-slate-300 rounded-sm focus:outline-none focus:border-[#008A3B] text-sm bg-white"
                    oninput="debouncedSearch('attachVendor')" onfocus="debouncedSearch('attachVendor')">
                <div id="attachVendorDropdown" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-slate-200 shadow-lg rounded-sm hidden max-h-52 overflow-y-auto"></div>
            </div>
            <div class="w-full md:w-48">
                <label class="block text-xs font-bold text-slate-600 mb-1.5">{{ __('messages.sourcing.price') }}</label>
                <input type="number" name="last_purchase_price" step="0.01" class="w-full px-3 py-2 border border-slate-300 rounded-sm focus:outline-none focus:border-[#008A3B] text-sm bg-white" placeholder="0.00">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-6 py-2 bg-[#008A3B] text-white font-bold rounded-sm hover:bg-[#007030] transition-colors h-[38px] text-sm whitespace-nowrap shadow-sm">
                    {{ __('messages.sourcing.save_link') }}
                </button>
                <button type="button" onclick="toggleAttachForm()" class="px-4 py-2 bg-white border border-slate-300 text-slate-600 rounded-sm hover:bg-slate-50 transition-colors h-[38px] text-sm">
                    {{ __('messages.common.cancel') }}
                </button>
            </div>
        </form>
    </div>

    {{-- منطقة محركات البحث الذكية --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        
        {{-- محرك بحث الأصناف --}}
        <div class="bg-white p-5 rounded-sm border border-slate-200 shadow-sm relative">
            <label class="block text-sm font-bold text-amber-600 mb-2 flex items-center gap-2">
                <i class="fas fa-box-open"></i> {{ __('messages.sourcing.find_vendors') }}
            </label>
            <div class="relative">
                <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="searchItemInput" autocomplete="off" placeholder="{{ __('messages.sourcing.item_search') }}"
                    class="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-sm text-sm focus:outline-none focus:border-amber-500 focus:bg-white transition-colors"
                    oninput="debouncedSearch('searchItem')" onfocus="debouncedSearch('searchItem')">
            </div>
            {{-- قائمة النتائج المنسدلة للأصناف --}}
            <div id="itemDropdown" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-slate-200 shadow-lg rounded-sm hidden max-h-60 overflow-y-auto mx-5">
                </div>
        </div>

        {{-- محرك بحث الموردين --}}
        <div class="bg-white p-5 rounded-sm border border-slate-200 shadow-sm relative">
            <label class="block text-sm font-bold text-[#005B9F] mb-2 flex items-center gap-2">
                <i class="fas fa-truck"></i> {{ __('messages.sourcing.find_items') }}
            </label>
            <div class="relative">
                <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="searchVendorInput" autocomplete="off" placeholder="{{ __('messages.sourcing.vendor_search') }}"
                    class="w-full pr-10 pl-3 py-2.5 bg-slate-50 border border-slate-300 rounded-sm text-sm focus:outline-none focus:border-[#005B9F] focus:bg-white transition-colors"
                    oninput="debouncedSearch('searchVendor')" onfocus="debouncedSearch('searchVendor')">
            </div>
            {{-- قائمة النتائج المنسدلة للموردين --}}
            <div id="vendorDropdown" class="absolute z-40 left-0 right-0 mt-1 bg-white border border-slate-200 shadow-lg rounded-sm hidden max-h-60 overflow-y-auto mx-5">
                </div>
        </div>

    </div>

    {{-- منطقة عرض النتائج (تتغير ديناميكياً بناءً على اختيار المستخدم) --}}
    <div id="resultsArea" class="bg-white border border-slate-200 rounded-sm shadow-sm min-h-[400px] p-6 relative">
        <div id="emptyState" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
            <i class="fas fa-project-diagram text-5xl mb-4 opacity-30"></i>
            <p class="font-bold text-base text-slate-500">{{ __('messages.sourcing.empty_title') }}</p>
            <p class="text-sm mt-1">{{ __('messages.sourcing.empty_hint') }}</p>
        </div>
        
        <div id="detailContent" class="hidden animate-fade-in">
            </div>
    </div>

</div>

{{-- نافذة عرض البيانات المصغرة (Quick View Modal) --}}
<div id="quickModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 transition-opacity">
    <div class="bg-white rounded-sm shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform" id="modalContent">
        <div class="p-4 bg-slate-800 text-white flex justify-between items-center">
            <h3 id="modalTitle" class="font-bold text-lg"></h3>
            <button onclick="closeModal()" class="text-slate-300 hover:text-white transition-colors"><i class="fas fa-times text-lg"></i></button>
        </div>
        <div class="p-6" id="modalBody"></div>
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end">
            <button onclick="closeModal()" class="px-5 py-2 bg-white border border-slate-300 rounded-sm text-sm font-bold text-slate-700 hover:bg-slate-100 transition-colors">{{ __('messages.sourcing.close_win') }}</button>
        </div>
    </div>
</div>

{{-- محرك الجافاسكريبت فائق السرعة --}}
<script>
    // عناوين أساسية تعمل من أي subfolder
    const VENDORS_URL = @json(url('vendors'));
    const ITEMS_URL   = @json(url('items'));
    const SEARCH_ITEMS_URL   = @json(route('sourcing.search-items'));
    const SEARCH_VENDORS_URL = @json(route('sourcing.search-vendors'));
    const ITEM_DETAIL_URL    = @json(url('sourcing/items'));   // + /{id}
    const VENDOR_DETAIL_URL  = @json(url('sourcing/vendors')); // + /{id}

    // إظهار/إخفاء فورم الربط
    function toggleAttachForm() {
        const form = document.getElementById('attachFormContainer');
        form.classList.toggle('hidden');
    }

    // إغلاق القوائم المنسدلة عند الضغط في أي مكان بالشاشة
    document.addEventListener('click', function(event) {
        [['searchItemInput', 'itemDropdown'], ['searchVendorInput', 'vendorDropdown'],
         ['attachItemSearch', 'attachItemDropdown'], ['attachVendorSearch', 'attachVendorDropdown']]
            .forEach(([inputId, dropdownId]) => {
                if (!event.target.closest('#' + inputId) && !event.target.closest('#' + dropdownId)) {
                    document.getElementById(dropdownId).classList.add('hidden');
                }
            });
    });

    // ---------------------------------------------------------
    // بحث AJAX عام (debounced) — بيُستخدم لصناديق البحث الأربعة في الصفحة
    // ---------------------------------------------------------
    const debounceTimers = {};

    function debouncedSearch(kind) {
        clearTimeout(debounceTimers[kind]);
        debounceTimers[kind] = setTimeout(() => runSearch(kind), 250);
    }

    async function runSearch(kind) {
        const isItem = kind === 'searchItem' || kind === 'attachItem';
        const inputEl = document.getElementById(kind === 'searchItem' ? 'searchItemInput'
            : kind === 'searchVendor' ? 'searchVendorInput'
            : kind === 'attachItem' ? 'attachItemSearch' : 'attachVendorSearch');
        const dropdownId = kind === 'searchItem' ? 'itemDropdown'
            : kind === 'searchVendor' ? 'vendorDropdown'
            : kind === 'attachItem' ? 'attachItemDropdown' : 'attachVendorDropdown';
        const dropdown = document.getElementById(dropdownId);
        const q = inputEl.value.trim();
        const url = (isItem ? SEARCH_ITEMS_URL : SEARCH_VENDORS_URL) + '?q=' + encodeURIComponent(q);

        dropdown.innerHTML = `<div class="p-4 text-center text-sm text-slate-400">{{ app()->getLocale() === 'ar' ? '...جارٍ البحث' : 'Searching...' }}</div>`;
        dropdown.classList.remove('hidden');

        let results = [];
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            results = await res.json();
        } catch (e) {
            dropdown.innerHTML = `<div class="p-4 text-center text-sm text-red-400">{{ app()->getLocale() === 'ar' ? 'حدث خطأ في البحث' : 'Search failed' }}</div>`;
            return;
        }

        if (!results.length) {
            dropdown.innerHTML = `<div class="p-4 text-center text-sm text-slate-400 font-medium">${isItem ? '{{ __('messages.sourcing.no_results_i') }}' : '{{ __('messages.sourcing.no_results_v') }}'}</div>`;
            return;
        }

        const onClickHandler = kind === 'searchItem' ? 'selectItem' : kind === 'searchVendor' ? 'selectVendor'
            : kind === 'attachItem' ? 'pickAttachItem' : 'pickAttachVendor';
        const hoverClass = isItem ? 'hover:bg-amber-50 group-hover:text-amber-700' : 'hover:bg-blue-50 group-hover:text-[#005B9F]';
        const code = isItem ? 'item_code' : 'vendor_code';

        dropdown.innerHTML = results.map(r => `
            <div onclick='${onClickHandler}(${r.id}, ${JSON.stringify(r.name_ar)})' class="p-3 border-b border-slate-100 ${hoverClass} cursor-pointer transition-colors flex justify-between items-center group">
                <span class="font-bold text-slate-700 text-sm">${r.name_ar}</span>
                <span class="text-xs font-mono text-slate-400">${r[code]}</span>
            </div>
        `).join('');
    }

    // ---------------------------------------------------------
    // اختيار صنف/مورد من صناديق البحث الرئيسية — بيجيب التفاصيل الكاملة عند الاختيار بس
    // ---------------------------------------------------------
    async function selectItem(id) {
        document.getElementById('itemDropdown').classList.add('hidden');
        document.getElementById('searchVendorInput').value = ''; // تصفير بحث الموردين
        const item = await (await fetch(`${ITEM_DETAIL_URL}/${id}`, { headers: { 'Accept': 'application/json' } })).json();
        document.getElementById('searchItemInput').value = item.name_ar;
        renderItemResults(item);
    }

    async function selectVendor(id) {
        document.getElementById('vendorDropdown').classList.add('hidden');
        document.getElementById('searchItemInput').value = ''; // تصفير بحث الأصناف
        const vendor = await (await fetch(`${VENDOR_DETAIL_URL}/${id}`, { headers: { 'Accept': 'application/json' } })).json();
        document.getElementById('searchVendorInput').value = vendor.name_ar;
        renderVendorResults(vendor);
    }

    // ---------------------------------------------------------
    // اختيار صنف/مورد من فورم الربط السريع — بس بيسجّل الـ id، مفيش تفاصيل لازمة
    // ---------------------------------------------------------
    function pickAttachItem(id, name) {
        document.getElementById('attachItemDropdown').classList.add('hidden');
        document.getElementById('attachItemId').value = id;
        document.getElementById('attachItemSearch').value = name;
    }

    function pickAttachVendor(id, name) {
        document.getElementById('attachVendorDropdown').classList.add('hidden');
        document.getElementById('attachVendorId').value = id;
        document.getElementById('attachVendorSearch').value = name;
    }

    // ---------------------------------------------------------
    // دوال عرض النتائج في منطقة العمل
    // ---------------------------------------------------------
    function renderItemResults(item) {
        document.getElementById('emptyState').style.display = 'none';
        const detailArea = document.getElementById('detailContent');
        
        let html = `
            <div class="mb-8 pb-4 border-b border-slate-200 flex justify-between items-start">
                <div>
                    <h2 class="text-3xl font-bold text-slate-800"><i class="fas fa-box-open text-amber-500 ml-2"></i>${item.name_ar}</h2>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="text-sm font-mono bg-slate-100 px-2 py-1 rounded-sm text-slate-600 border border-slate-200">{{ __('messages.items.code') }}: ${item.item_code}</span>
                        <span class="text-sm font-bold text-slate-500">{{ __('messages.sourcing.total_vendors') }} <span class="text-slate-800">${item.approved_vendors.length}</span></span>
                    </div>
                </div>
                <button onclick='openItemModal(${JSON.stringify(item).replace(/'/g, "\\'")})' class="px-4 py-2 bg-slate-100 border border-slate-300 text-slate-700 text-sm font-bold rounded-sm hover:bg-slate-200 transition-colors shadow-sm">
                    <i class="fas fa-info-circle"></i> {{ __('messages.sourcing.item_card') }}
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        `;
        
        if(item.approved_vendors.length === 0) {
            html += `<div class="col-span-full p-6 bg-orange-50 text-orange-800 text-sm font-bold border border-orange-200 rounded-sm text-center flex flex-col items-center gap-2"><i class="fas fa-exclamation-triangle text-2xl"></i>{{ __('messages.sourcing.no_vend_item') }}</div>`;
        }

        item.approved_vendors.forEach(v => {
            html += `
                <div class="bg-white p-5 border border-slate-200 rounded-sm shadow-sm hover:border-[#005B9F] transition-all relative overflow-hidden group">
                    <div class="absolute right-0 top-0 bottom-0 w-1 bg-[#005B9F] group-hover:w-2 transition-all"></div>
                    <div class="font-bold text-slate-800 text-base mb-1 pr-2">${v.name_ar}</div>
                    <div class="text-xs text-slate-400 font-mono mb-4 pr-2">{{ __('messages.vendors.code') }}: ${v.vendor_code}</div>

                    <div class="bg-slate-50 border border-slate-100 rounded-sm p-3 flex justify-between items-center mb-4">
                        <span class="text-xs font-bold text-slate-500">{{ __('messages.sourcing.avg_price') }}</span>
                        <span class="text-sm font-bold text-[#008A3B] font-mono">${v.pivot.last_purchase_price || '{{ __("messages.sourcing.no_price") }}'}</span>
                    </div>

                    <button onclick='openVendorModal(${JSON.stringify(v).replace(/'/g, "\\'")})' class="w-full py-2 bg-white border border-[#005B9F] text-[#005B9F] text-xs font-bold rounded-sm hover:bg-[#005B9F] hover:text-white transition-colors">
                        {{ __('messages.sourcing.view_vendor') }}
                    </button>
                </div>
            `;
        });
        
        detailArea.innerHTML = html + `</div>`;
        detailArea.classList.remove('hidden');
    }

    function renderVendorResults(vendor) {
        document.getElementById('emptyState').style.display = 'none';
        const detailArea = document.getElementById('detailContent');
        
        let html = `
            <div class="mb-8 pb-4 border-b border-slate-200 flex justify-between items-start">
                <div>
                    <h2 class="text-3xl font-bold text-slate-800"><i class="fas fa-truck text-[#005B9F] ml-2"></i>${vendor.name_ar}</h2>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="text-sm font-mono bg-slate-100 px-2 py-1 rounded-sm text-slate-600 border border-slate-200">{{ __('messages.vendors.code') }}: ${vendor.vendor_code}</span>
                        <span class="text-sm font-bold text-slate-500">{{ __('messages.sourcing.total_items') }} <span class="text-slate-800">${vendor.approved_items.length}</span></span>
                    </div>
                </div>
                <button onclick='openVendorModal(${JSON.stringify(vendor).replace(/'/g, "\\'")})' class="px-4 py-2 bg-slate-100 border border-slate-300 text-slate-700 text-sm font-bold rounded-sm hover:bg-slate-200 transition-colors shadow-sm">
                    <i class="fas fa-info-circle"></i> {{ __('messages.sourcing.vendor_file') }}
                </button>
            </div>
            
            <div class="bg-white border border-slate-200 rounded-sm overflow-hidden shadow-sm">
                <table class="w-full text-sm text-right border-collapse">
                    <thead class="bg-slate-100 border-b border-slate-200 text-slate-700">
                        <tr>
                            <th class="p-4 font-bold w-32">{{ __('messages.sourcing.col_code') }}</th>
                            <th class="p-4 font-bold">{{ __('messages.sourcing.col_name') }}</th>
                            <th class="p-4 font-bold w-40 text-center">{{ __('messages.sourcing.col_price') }}</th>
                            <th class="p-4 font-bold w-32 text-center">{{ __('messages.sourcing.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
        `;

        if(vendor.approved_items.length === 0) {
            html += `<tr><td colspan="4" class="p-10 text-center text-slate-500 bg-slate-50 font-medium"><i class="fas fa-box-open text-3xl mb-3 block opacity-40"></i>{{ __('messages.sourcing.no_item_vend') }}</td></tr>`;
        }

        vendor.approved_items.forEach(i => {
            html += `
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="p-4 font-mono text-xs text-slate-500">${i.item_code}</td>
                    <td class="p-4 font-bold text-slate-800">${i.name_ar}</td>
                    <td class="p-4 text-center">
                        <span class="bg-green-50 text-[#008A3B] border border-green-200 px-3 py-1 rounded-sm font-bold font-mono text-xs">
                            ${i.pivot.last_purchase_price || '-'}
                        </span>
                    </td>
                    <td class="p-4 text-center">
                        <button onclick='openItemModal(${JSON.stringify(i).replace(/'/g, "\\'")})' class="text-xs text-amber-600 border border-amber-600 px-3 py-1.5 rounded-sm hover:bg-amber-600 hover:text-white font-bold transition-colors">
                            {{ __('messages.sourcing.item_card') }}
                        </button>
                    </td>
                </tr>
            `;
        });
        
        detailArea.innerHTML = html + `</tbody></table></div>`;
        detailArea.classList.remove('hidden');
    }

    // ---------------------------------------------------------
    // دوال النوافذ المنبثقة (Modals)
    // ---------------------------------------------------------
    const modal = document.getElementById('quickModal');
    
    function openVendorModal(vendor) {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-truck text-[#005B9F] ml-2"></i> {{ __("messages.sourcing.qv_title") }}';
        document.getElementById('modalBody').innerHTML = `
            <div class="space-y-4 text-sm text-slate-700 bg-slate-50 p-4 border border-slate-200 rounded-sm">
                <div class="flex border-b border-slate-200 pb-3"><strong class="w-32 text-slate-800">{{ __('messages.sourcing.qv_name') }}</strong> <span>${vendor.name_ar}</span></div>
                <div class="flex border-b border-slate-200 pb-3"><strong class="w-32 text-slate-800">{{ __('messages.sourcing.qv_code') }}</strong> <span class="font-mono bg-white px-2 py-0.5 border border-slate-200 rounded text-xs">${vendor.vendor_code}</span></div>
                <div class="flex border-b border-slate-200 pb-3"><strong class="w-32 text-slate-800">{{ __('messages.sourcing.qv_phone') }}</strong> <span dir="ltr" class="font-mono">${vendor.mobile || '{{ __("messages.common.not_reg") }}'}</span></div>
                <div class="flex pb-1"><strong class="w-32 text-slate-800">{{ __('messages.sourcing.qv_tax') }}</strong> <span class="font-mono">${vendor.tax_id || '{{ __("messages.common.not_reg") }}'}</span></div>
            </div>
            <div class="mt-5 text-left">
                <a href="${VENDORS_URL}/${vendor.id}/edit" class="inline-flex items-center gap-2 bg-[#005B9F] text-white px-4 py-2 rounded-sm text-xs font-bold hover:bg-[#004680] transition-colors">
                    <i class="fas fa-external-link-alt"></i> {{ __('messages.sourcing.qv_open') }}
                </a>
            </div>
        `;
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.remove('opacity-0', 'scale-95'), 10);
    }

    function openItemModal(item) {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-box-open text-amber-500 ml-2"></i> {{ __("messages.sourcing.qi_title") }}';
        document.getElementById('modalBody').innerHTML = `
            <div class="space-y-4 text-sm text-slate-700 bg-slate-50 p-4 border border-slate-200 rounded-sm">
                <div class="flex border-b border-slate-200 pb-3"><strong class="w-32 text-slate-800">{{ __('messages.sourcing.qi_name') }}</strong> <span>${item.name_ar}</span></div>
                <div class="flex border-b border-slate-200 pb-3"><strong class="w-32 text-slate-800">{{ __('messages.sourcing.qi_code') }}</strong> <span class="font-mono bg-white px-2 py-0.5 border border-slate-200 rounded text-xs">${item.item_code}</span></div>
                <div class="flex border-b border-slate-200 pb-3"><strong class="w-32 text-slate-800">{{ __('messages.sourcing.qi_barcode') }}</strong> <span class="font-mono">${item.barcode || '{{ __("messages.common.not_reg") }}'}</span></div>
                <div class="flex pb-1"><strong class="w-32 text-slate-800">{{ __('messages.sourcing.qi_reorder') }}</strong> <span class="text-red-600 font-bold bg-red-50 px-2 py-0.5 rounded border border-red-200">${item.reorder_point || 0}</span></div>
            </div>
            <div class="mt-5 text-left">
                <a href="${ITEMS_URL}/${item.id}/edit" class="inline-flex items-center gap-2 bg-amber-500 text-white px-4 py-2 rounded-sm text-xs font-bold hover:bg-amber-600 transition-colors">
                    <i class="fas fa-external-link-alt"></i> {{ __('messages.sourcing.qi_open') }}
                </a>
            </div>
        `;
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.remove('opacity-0', 'scale-95'), 10);
    }

    function closeModal() {
        modal.classList.add('opacity-0', 'scale-95');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }
</script>
@endsection