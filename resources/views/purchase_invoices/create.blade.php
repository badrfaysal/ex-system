@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $cur  = $quotation->currency ?? 'EGP';
    $clientDisplay = optional($quotation->client)->displayName($isAr ? 'ar' : 'en') ?? '—';

    // خريطة موردين معتمدين مرتّبة بالسعر لكل صنف: item_id => [{id, name, price|null}, ...]
    $vendorOptionsFor = function ($item) use ($isAr) {
        if (!$item) return collect();
        return $item->approvedVendors->map(function ($v) use ($isAr) {
            return [
                'id'    => $v->id,
                'name'  => $isAr ? $v->name_ar : ($v->name_en ?: $v->name_ar),
                'price' => is_null($v->pivot->last_purchase_price) ? null : (float) $v->pivot->last_purchase_price,
            ];
        })->sortBy(fn ($v) => $v['price'] ?? INF)->values();
    };

    $itemVendorsMap = [];
    foreach ($quotation->items as $line) {
        if ($line->item && !isset($itemVendorsMap[$line->item_id])) {
            $itemVendorsMap[$line->item_id] = $vendorOptionsFor($line->item);
        }
    }
    foreach ($items as $it) {
        if (!isset($itemVendorsMap[$it->id])) {
            $itemVendorsMap[$it->id] = $vendorOptionsFor($it);
        }
    }

    $jsItems = $items->map(function ($i) use ($isAr) {
        $name = $isAr ? ($i->name_ar ?: $i->name_en) : ($i->name_en ?: $i->name_ar);
        return ['id' => $i->id, 'code' => $i->item_code, 'name' => $name, 'uom' => $i->base_uom];
    })->values();

    $jsAllVendors = $vendors->map(fn ($v) => ['id' => $v->id, 'name' => $isAr ? $v->name_ar : ($v->name_en ?: $v->name_ar)])->values();
@endphp
@section('header_title', $isAr ? 'إنشاء فاتورة شراء' : 'Create Purchase Invoice')

@section('content')
<div class="mb-6 flex justify-between items-center max-w-6xl mx-auto animate-fade-in">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
            <i class="fas fa-file-invoice text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'إنشاء فاتورة شراء' : 'Create Purchase Invoice' }}</h2>
    </div>
    <a href="{{ route('quotations.show', $quotation) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
        <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ $isAr ? 'رجوع لعرض السعر' : 'Back to Quotation' }}
    </a>
</div>

@if($errors->any())
<div class="max-w-6xl mx-auto mb-4 bg-red-50 border border-red-300 text-red-800 rounded-xl p-4">
    <div class="flex items-center gap-2 font-bold mb-2"><i class="fas fa-exclamation-circle"></i> {{ $isAr ? 'يرجى مراجعة الأخطاء التالية:' : 'Please review the following errors:' }}</div>
    <ul class="list-disc list-inside space-y-1 text-sm">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
    <div class="h-1.5 bg-gradient-to-r from-[#005B9F] to-[#008A3B]"></div>
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                <i class="fas fa-file-invoice text-[#005B9F]"></i>
            </div>
            <div>
                <p class="font-bold text-gray-800 text-sm">{{ $isAr ? 'فاتورة شراء من مركز التكلفة' : 'Purchase Invoice for Cost Center' }}</p>
                <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $quotation->quote_number }} — {{ $clientDisplay }}</p>
            </div>
        </div>
    </div>
    <div class="px-6 py-3 bg-amber-50 border-b border-amber-100 flex items-start gap-2">
        <i class="fas fa-info-circle text-amber-500 mt-0.5 shrink-0"></i>
        <p class="text-xs text-amber-700 leading-relaxed">
            {{ $isAr
                ? 'اختر أصناف عرض السعر التي تريد شراءها وحدد المورد لكل صنف. يمكنك أيضاً إضافة أصناف إضافية غير موجودة في عرض السعر. بمجرد الحفظ تصبح فاتورة الشراء التزامًا فوريًا لكل مورد.'
                : 'Choose the quotation items you want to purchase and pick a vendor for each. You can also add extra items not in the quotation. Once saved, the invoice becomes an immediate liability to each vendor.' }}
        </p>
    </div>
</div>

<form action="{{ route('purchase-invoices.store') }}" method="POST" id="piForm">
    @csrf
    <input type="hidden" name="quotation_id" value="{{ $quotation->id }}">

    <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'رقم الفاتورة' : 'Invoice No.' }} <span class="text-red-500">*</span></label>
                <input type="text" name="invoice_number" required dir="ltr" value="{{ old('invoice_number', $nextInvoiceNumber) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono bg-gray-50 focus:outline-none focus:border-[#008A3B]">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'تاريخ الفاتورة' : 'Invoice Date' }} <span class="text-red-500">*</span></label>
                <input type="date" name="invoice_date" required value="{{ old('invoice_date', now()->toDateString()) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'العملة' : 'Currency' }} <span class="text-red-500">*</span></label>
                <input type="text" name="currency" required dir="ltr" value="{{ old('currency', $cur) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono bg-gray-50 focus:outline-none focus:border-[#008A3B]">
            </div>
        </div>
    </div>

    {{-- أصناف عرض السعر --}}
    <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/60 font-bold text-gray-800 text-sm flex items-center gap-2">
            <span class="w-1.5 h-5 bg-[#005B9F] rounded-full"></span> {{ $isAr ? 'أصناف عرض السعر' : 'Quotation Items' }}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[900px]" style="text-align:{{ $isAr ? 'right' : 'left' }}">
                <thead>
                    <tr style="background:#1e293b;color:#fff;">
                        <th class="px-3 py-2.5 text-center w-10">
                            <input type="checkbox" id="checkAll" checked class="w-4 h-4 accent-blue-600 cursor-pointer">
                        </th>
                        <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'الوصف' : 'Description' }}</th>
                        <th class="px-3 py-2.5 text-center text-[11px] font-bold w-24">{{ $isAr ? 'الكمية' : 'Qty' }}</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold w-56">{{ $isAr ? 'المورد' : 'Vendor' }}</th>
                        <th class="px-3 py-2.5 text-center text-[11px] font-bold w-28">{{ $isAr ? 'سعر الوحدة' : 'Unit Price' }}</th>
                        <th class="px-3 py-2.5 text-center text-[11px] font-bold w-20">{{ $isAr ? 'خصم%' : 'Disc%' }}</th>
                        <th class="px-3 py-2.5 text-center text-[11px] font-bold w-20">{{ $isAr ? 'ضريبة%' : 'Tax%' }}</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold w-28">{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotation->items as $idx => $line)
                        @php
                            $itemVendors = $itemVendorsMap[$line->item_id] ?? collect();
                            $hasApproved = $itemVendors->isNotEmpty();
                            $priced = $itemVendors->filter(fn ($v) => $v['price'] !== null);
                            $minPrice = $priced->min('price');
                            $maxPrice = $priced->max('price');
                            $showHighlight = $priced->count() > 1 && $minPrice != $maxPrice;
                            $defaultVendor = $hasApproved ? $itemVendors->first() : null;
                            $defaultVendorId = $defaultVendor['id'] ?? null;
                            $defaultPrice = $defaultVendor['price'] ?? null;
                        @endphp
                        <tr class="qi-row {{ $idx % 2 === 0 ? '' : 'bg-gray-50/70' }} border-b border-gray-100"
                            data-item-id="{{ $line->item_id }}">
                            <td class="px-3 py-2.5 text-center">
                                <input type="checkbox" class="qi-check w-4 h-4 accent-blue-600 cursor-pointer" checked>
                            </td>
                            <td class="px-3 py-2 text-gray-800 font-medium text-xs">
                                {{ $line->displayDescription($isAr ? 'ar' : 'en') }}
                                <input type="hidden" class="qi-input" name="lines[{{ $idx }}][description]" value="{{ $line->displayDescription($isAr ? 'ar' : 'en') }}">
                                <input type="hidden" class="qi-input" name="lines[{{ $idx }}][item_id]" value="{{ $line->item_id }}">
                                <input type="hidden" class="qi-input" name="lines[{{ $idx }}][quotation_item_id]" value="{{ $line->id }}">
                                <input type="hidden" class="qi-input" name="lines[{{ $idx }}][item_code]" value="{{ $line->item_code }}">
                                <input type="hidden" class="qi-input" name="lines[{{ $idx }}][uom]" value="{{ $line->uom }}">
                            </td>
                            <td class="px-3 py-2 text-center" dir="ltr">
                                <input type="number" step="any" min="0.001" class="qi-input calc-q w-20 text-center border border-gray-300 rounded-lg px-2 py-1 text-xs font-bold"
                                    name="lines[{{ $idx }}][quantity]" value="{{ rtrim(rtrim(number_format($line->quantity, 4, '.', ''), '0'), '.') }}">
                            </td>
                            <td class="px-3 py-2">
                                <select class="qi-input vendor-select w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white"
                                    name="lines[{{ $idx }}][vendor_id]" required>
                                    <option value="" disabled {{ $defaultVendorId ? '' : 'selected' }}>{{ $isAr ? '— اختر مورد —' : '— Choose vendor —' }}</option>
                                    @if($hasApproved)
                                        @foreach($itemVendors as $v)
                                            @php
                                                $isMin = $showHighlight && $v['price'] !== null && $v['price'] == $minPrice;
                                                $isMax = $showHighlight && $v['price'] !== null && $v['price'] == $maxPrice;
                                                $style = $isMin ? 'color:#16a34a;font-weight:bold;' : ($isMax ? 'color:#dc2626;font-weight:bold;' : '');
                                                $priceLabel = $v['price'] !== null ? number_format($v['price'], 2) : ($isAr ? 'بدون سعر' : 'no price');
                                                $tag = $isMin ? ($isAr ? ' — الأرخص' : ' — Cheapest') : ($isMax ? ($isAr ? ' — الأغلى' : ' — Priciest') : '');
                                            @endphp
                                            <option value="{{ $v['id'] }}" style="{{ $style }}" data-price="{{ $v['price'] ?? '' }}" {{ $defaultVendorId == $v['id'] ? 'selected' : '' }}>
                                                {{ $v['name'] }} ({{ $priceLabel }}){{ $tag }}
                                            </option>
                                        @endforeach
                                    @else
                                        @foreach($vendors as $v)
                                            <option value="{{ $v->id }}">{{ $isAr ? $v->name_ar : ($v->name_en ?: $v->name_ar) }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @if(!$hasApproved)
                                <p class="text-[10px] text-amber-600 mt-1">{{ $isAr ? 'لا يوجد مورد معتمد لهذا الصنف — عرض كل الموردين' : 'No approved vendor for this item — showing all vendors' }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center" dir="ltr">
                                <input type="number" step="0.01" min="0" class="qi-input calc-p w-24 text-center border border-gray-300 rounded-lg px-2 py-1 text-xs font-bold"
                                    name="lines[{{ $idx }}][unit_price]" value="{{ $defaultPrice }}">
                            </td>
                            <td class="px-3 py-2 text-center" dir="ltr">
                                <input type="number" step="0.01" min="0" max="100" class="qi-input calc-d w-16 text-center border border-gray-300 rounded-lg px-2 py-1 text-xs"
                                    name="lines[{{ $idx }}][discount_percent]" value="0">
                            </td>
                            <td class="px-3 py-2 text-center" dir="ltr">
                                <input type="number" step="0.01" min="0" max="100" class="qi-input calc-t w-16 text-center border border-gray-300 rounded-lg px-2 py-1 text-xs"
                                    name="lines[{{ $idx }}][tax_percent]" value="0">
                            </td>
                            <td class="px-3 py-2 font-extrabold text-gray-900 text-xs net-cell" dir="ltr">0.00</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- أصناف إضافية --}}
    <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/60 font-bold text-gray-800 text-sm flex items-center gap-2">
            <span class="w-1.5 h-5 bg-amber-500 rounded-full"></span> {{ $isAr ? 'أصناف إضافية (غير موجودة في عرض السعر)' : 'Extra Items (not in quotation)' }}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[900px]" style="text-align:{{ $isAr ? 'right' : 'left' }}">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-[11px] font-bold">
                        <th class="p-3 min-w-[200px]">{{ $isAr ? 'الوصف' : 'Description' }}</th>
                        <th class="p-3 text-center w-24">{{ $isAr ? 'الكمية' : 'Qty' }}</th>
                        <th class="p-3 w-56">{{ $isAr ? 'المورد' : 'Vendor' }}</th>
                        <th class="p-3 text-center w-28">{{ $isAr ? 'سعر الوحدة' : 'Unit Price' }}</th>
                        <th class="p-3 text-center w-20">{{ $isAr ? 'خصم%' : 'Disc%' }}</th>
                        <th class="p-3 text-center w-20">{{ $isAr ? 'ضريبة%' : 'Tax%' }}</th>
                        <th class="p-3 w-28">{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                        <th class="p-3 w-10"></th>
                    </tr>
                </thead>
                <tbody id="extraItemsBody"></tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/60 flex flex-wrap items-center gap-3">
            <select id="itemPicker" data-search class="w-full md:w-80 px-4 py-2 border border-gray-300 rounded-lg bg-white text-sm">
                <option value="">{{ $isAr ? '— اختر صنفاً لإضافته —' : '— Pick an item to add —' }}</option>
                @foreach($items as $it)
                    @php $nm = $isAr ? ($it->name_ar ?: $it->name_en) : ($it->name_en ?: $it->name_ar); @endphp
                    <option value="{{ $it->id }}">{{ $it->item_code }} — {{ $nm }}</option>
                @endforeach
            </select>
            <button type="button" onclick="addBlankExtraRow()" class="px-4 py-2 border border-dashed border-gray-400 text-gray-600 rounded-lg text-sm hover:border-amber-500 hover:text-amber-600 flex items-center gap-2">
                <i class="fas fa-plus"></i> {{ $isAr ? 'سطر يدوي' : 'Manual line' }}
            </button>
        </div>
    </div>

    <div class="max-w-6xl mx-auto mb-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="text-xs text-gray-500">{{ $isAr ? 'إجمالي الفاتورة (تقديري):' : 'Invoice total (estimated):' }}</div>
                <div class="font-extrabold text-[#005B9F] text-xl" dir="ltr" id="grandTotal">0.00 <span class="text-xs font-normal text-gray-400" id="curLabel">{{ $cur }}</span></div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('quotations.show', $quotation) }}" class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium">{{ $isAr ? 'إلغاء' : 'Cancel' }}</a>
                <button type="submit" class="px-7 py-2.5 bg-[#008A3B] hover:bg-[#007030] text-white rounded-lg font-bold text-sm flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save"></i> {{ $isAr ? 'حفظ فاتورة الشراء' : 'Save Purchase Invoice' }}
                </button>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    // item_id => [{id, name, price|null}, ...] مرتّبة بالسعر
    const ITEM_VENDORS = @json($itemVendorsMap);
    const ALL_VENDORS  = @json($jsAllVendors);
    const EXTRA_ITEMS  = @json($jsItems);
    const isAr         = @json($isAr);
    let extraIndex      = {{ $quotation->items->count() }};

    function fmt(n) { return (Math.round(n * 100) / 100).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

    function vendorPrice(itemId, vendorId) {
        const list = ITEM_VENDORS[itemId] || [];
        const found = list.find(v => String(v.id) === String(vendorId));
        return found ? found.price : null; // null = مفيش سعر مسجّل
    }

    function rowNet(tr) {
        const q = parseFloat(tr.querySelector('.calc-q')?.value || 0);
        const p = parseFloat(tr.querySelector('.calc-p')?.value || 0);
        const d = parseFloat(tr.querySelector('.calc-d')?.value || 0);
        const t = parseFloat(tr.querySelector('.calc-t')?.value || 0);
        const base = q * p;
        const afterDisc = base - (base * d / 100);
        const net = afterDisc + (afterDisc * t / 100);
        const cell = tr.querySelector('.net-cell');
        if (cell) cell.textContent = fmt(net);
        return net;
    }

    function recalcAll() {
        let grand = 0;
        document.querySelectorAll('.qi-row').forEach(tr => {
            const checked = tr.querySelector('.qi-check').checked;
            tr.querySelectorAll('.qi-input').forEach(inp => inp.disabled = !checked);
            tr.style.opacity = checked ? '1' : '0.4';
            if (checked) grand += rowNet(tr);
        });
        document.querySelectorAll('.extra-row').forEach(tr => { grand += rowNet(tr); });
        document.getElementById('grandTotal').childNodes[0].textContent = fmt(grand) + ' ';
    }

    // ربط أحداث صفوف عرض السعر
    document.querySelectorAll('.qi-row').forEach(tr => {
        tr.querySelector('.qi-check').addEventListener('change', recalcAll);
        tr.querySelectorAll('.calc-q,.calc-p,.calc-d,.calc-t').forEach(inp => inp.addEventListener('input', recalcAll));
        const vendorSel = tr.querySelector('.vendor-select');
        vendorSel.addEventListener('change', function () {
            const price = vendorPrice(tr.dataset.itemId, this.value);
            tr.querySelector('.calc-p').value = price !== null ? price : '';
            recalcAll();
        });
    });

    document.getElementById('checkAll').addEventListener('change', function () {
        document.querySelectorAll('.qi-check').forEach(cb => cb.checked = this.checked);
        recalcAll();
    });

    // بناء خيارات المورد لصنف معيّن — الموردين المعتمدين مرتّبين بالسعر مع تمييز الأرخص/الأغلى، أو كل الموردين لو مفيش معتمدين
    function buildVendorOptions(itemId) {
        const approved = ITEM_VENDORS[itemId] || [];
        let html = '<option value="" disabled selected>' + (isAr ? '— اختر مورد —' : '— Choose vendor —') + '</option>';

        if (approved.length) {
            const priced = approved.filter(v => v.price !== null);
            const minP = priced.length ? Math.min(...priced.map(v => v.price)) : null;
            const maxP = priced.length ? Math.max(...priced.map(v => v.price)) : null;
            const showHighlight = priced.length > 1 && minP !== maxP;

            approved.forEach(v => {
                const isMin = showHighlight && v.price === minP;
                const isMax = showHighlight && v.price === maxP;
                const style = isMin ? 'color:#16a34a;font-weight:bold;' : (isMax ? 'color:#dc2626;font-weight:bold;' : '');
                const priceLabel = v.price !== null ? v.price.toFixed(2) : (isAr ? 'بدون سعر' : 'no price');
                const tag = isMin ? (isAr ? ' — الأرخص' : ' — Cheapest') : (isMax ? (isAr ? ' — الأغلى' : ' — Priciest') : '');
                html += `<option value="${v.id}" style="${style}">${v.name} (${priceLabel})${tag}</option>`;
            });
        } else {
            ALL_VENDORS.forEach(v => { html += `<option value="${v.id}">${v.name}</option>`; });
        }
        return { html, hasApproved: approved.length > 0 };
    }

    // صفوف الأصناف الإضافية
    function extraRowTemplate(data) {
        const i = extraIndex++;
        const tr = document.createElement('tr');
        tr.className = 'extra-row border-b border-gray-100 bg-amber-50/30';
        tr.dataset.itemId = data.item_id || '';

        const { html: vendorOptions, hasApproved } = buildVendorOptions(data.item_id);

        tr.innerHTML = `
            <td class="p-2">
                <input type="text" name="lines[${i}][description]" required value="${(data.description ?? '').replace(/"/g,'&quot;')}"
                    class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:border-amber-500">
                <input type="hidden" name="lines[${i}][item_id]" value="${data.item_id ?? ''}">
                <input type="hidden" name="lines[${i}][item_code]" value="${data.code ?? ''}">
                <input type="hidden" name="lines[${i}][uom]" value="${data.uom ?? ''}">
            </td>
            <td class="p-2"><input type="number" step="any" min="0.001" name="lines[${i}][quantity]" value="1" class="calc-q w-full px-2 py-1.5 border border-gray-300 rounded text-center"></td>
            <td class="p-2">
                <select name="lines[${i}][vendor_id]" required class="vendor-select w-full px-2 py-1.5 border border-gray-300 rounded text-xs bg-white">${vendorOptions}</select>
                ${!hasApproved ? `<p class="text-[10px] text-amber-600 mt-1">${isAr ? 'لا يوجد مورد معتمد لهذا الصنف — عرض كل الموردين' : 'No approved vendor for this item — showing all vendors'}</p>` : ''}
            </td>
            <td class="p-2"><input type="number" step="0.01" min="0" name="lines[${i}][unit_price]" value="0" class="calc-p w-full px-2 py-1.5 border border-gray-300 rounded text-center"></td>
            <td class="p-2"><input type="number" step="0.01" min="0" max="100" name="lines[${i}][discount_percent]" value="0" class="calc-d w-full px-2 py-1.5 border border-gray-300 rounded text-center"></td>
            <td class="p-2"><input type="number" step="0.01" min="0" max="100" name="lines[${i}][tax_percent]" value="0" class="calc-t w-full px-2 py-1.5 border border-gray-300 rounded text-center"></td>
            <td class="p-2 font-bold text-amber-700 net-cell" dir="ltr">0.00</td>
            <td class="p-2 text-center"><button type="button" onclick="this.closest('tr').remove(); document.dispatchEvent(new Event('pi-recalc'))" class="text-gray-300 hover:text-red-500"><i class="fas fa-times-circle"></i></button></td>`;

        document.getElementById('extraItemsBody').appendChild(tr);
        tr.querySelectorAll('.calc-q,.calc-p,.calc-d,.calc-t').forEach(inp => inp.addEventListener('input', recalcAll));
        tr.querySelector('.vendor-select').addEventListener('change', function () {
            const price = vendorPrice(tr.dataset.itemId, this.value);
            tr.querySelector('.calc-p').value = price !== null ? price : '';
            recalcAll();
        });
        recalcAll();
    }

    window.addBlankExtraRow = function () { extraRowTemplate({ description: '' }); };
    window.addExtraItemById = function (id) {
        const item = EXTRA_ITEMS.find(x => String(x.id) === String(id));
        if (!item) return;
        extraRowTemplate({ item_id: item.id, code: item.code, description: item.name, uom: item.uom });
    };

    document.addEventListener('pi-recalc', recalcAll);

    setTimeout(function () {
        const picker = document.getElementById('itemPicker');
        if (picker && picker.tomselect) {
            // القائمة المنسدلة كانت بتتقص لأنها جوه بطاقة overflow-hidden — نعيد تهيئتها
            // بحيث تظهر مباشرة داخل body وتفلت من القص
            picker.tomselect.destroy();
            const ts = new TomSelect(picker, {
                allowEmptyOption: true,
                maxOptions: 300,
                dropdownParent: 'body',
            });
            ts.on('item_add', function (value) {
                addExtraItemById(value);
                ts.clear(true);
                ts.setTextboxValue('');
            });
        } else if (picker) {
            picker.addEventListener('change', function () { if (this.value) addExtraItemById(this.value); this.value = ''; });
        }
    }, 150);

    document.getElementById('piForm').addEventListener('submit', function (e) {
        const anyChecked = [...document.querySelectorAll('.qi-check')].some(c => c.checked);
        const anyExtra = document.querySelectorAll('.extra-row').length > 0;
        if (!anyChecked && !anyExtra) {
            e.preventDefault();
            alert(isAr ? 'اختر صنفاً واحداً على الأقل أو أضف صنفاً إضافيًا.' : 'Select at least one item or add an extra item.');
        }
    });

    recalcAll();
})();
</script>
@endsection
