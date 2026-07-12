@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $cur  = $salesOrder->currency ?? 'EGP';
    $clientDisplay = optional($salesOrder->client)->displayName($isAr ? 'ar' : 'en') ?? '—';

    // خريطة آخر سعر شراء لكل (صنف × مورد) — عشان نقترح السعر تلقائي حسب المورد المختار
    $priceMap = [];
    foreach ($items as $it) {
        foreach ($it->approvedVendors as $v) {
            $priceMap[$it->id][$v->id] = is_null($v->pivot->last_purchase_price) ? null : (float) $v->pivot->last_purchase_price;
        }
    }

    $jsItems = $items->map(function ($i) use ($isAr) {
        $name = $isAr ? ($i->name_ar ?: $i->name_en) : ($i->name_en ?: $i->name_ar);
        return ['id' => $i->id, 'code' => $i->item_code, 'name' => $name, 'uom' => $i->base_uom];
    })->values();

    $soItemsArray = $salesOrder->items->map(function($si) {
        return [
            'item_id'     => $si->item_id,
            'code'        => $si->item_code,
            'description' => $si->description,
            'uom'         => $si->uom,
            'quantity'    => $si->quantity
        ];
    })->values();
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
    <a href="{{ route('sales-orders.show', $salesOrder) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
        <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ $isAr ? 'رجوع لأمر البيع' : 'Back to Sales Order' }}
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
    <div class="px-6 py-3 bg-amber-50 border-b border-amber-100 flex items-start gap-2">
        <i class="fas fa-info-circle text-amber-500 mt-0.5 shrink-0"></i>
        <p class="text-xs text-amber-700 leading-relaxed">
            {{ $isAr
                ? 'فاتورة الشراء دي لمورد واحد بس. اختر المورد أولاً — السعر هيتقترح تلقائي لأصنافه. تقدر تضيف أي صنف من الكتالوج. بمجرد الحفظ تصبح التزامًا فوريًا للمورد.'
                : 'This purchase invoice is for a single vendor. Choose the vendor first — prices will be suggested for their items. You can add any catalog item. Once saved, it becomes an immediate liability.' }}
        </p>
    </div>
</div>

<form action="{{ route('purchase-invoices.store') }}" method="POST" id="piForm">
    @csrf
    <input type="hidden" name="sales_order_id" value="{{ $salesOrder->id }}">

    <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
        <p class="text-xs text-gray-400 font-mono mb-4">{{ $salesOrder->so_number }} — {{ $clientDisplay }}</p>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المورد' : 'Vendor' }} <span class="text-red-500">*</span></label>
                <select name="vendor_id" id="vendorSelect" required data-search class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                    <option value="" disabled selected>{{ $isAr ? '— اختر مورد —' : '— Choose vendor —' }}</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>{{ $isAr ? $v->name_ar : ($v->name_en ?: $v->name_ar) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'رقم الفاتورة' : 'Invoice No.' }}</label>
                <input type="text" value="{{ $isAr ? '— يُولَّد تلقائيًا عند الحفظ —' : '— Generated automatically on save —' }}" disabled dir="ltr"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-400 italic cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'تاريخ الفاتورة' : 'Invoice Date' }} <span class="text-red-500">*</span></label>
                <input type="date" name="invoice_date" required value="{{ old('invoice_date', now()->toDateString()) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
            </div>
            <input type="hidden" name="currency" value="{{ $cur }}">
        </div>
    </div>

    <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/60 font-bold text-gray-800 text-sm flex items-center gap-2">
            <span class="w-1.5 h-5 bg-[#005B9F] rounded-full"></span> {{ $isAr ? 'أصناف الفاتورة' : 'Invoice Items' }}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[850px]" style="text-align:{{ $isAr ? 'right' : 'left' }}">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-[11px] font-bold">
                        <th class="p-3 min-w-[220px]">{{ $isAr ? 'الوصف' : 'Description' }}</th>
                        <th class="p-3 text-center w-24">{{ $isAr ? 'الكمية' : 'Qty' }}</th>
                        <th class="p-3 text-center w-28">{{ $isAr ? 'سعر الوحدة' : 'Unit Price' }}</th>
                        <th class="p-3 text-center w-20">{{ $isAr ? 'خصم%' : 'Disc%' }}</th>
                        <th class="p-3 text-center w-20">{{ $isAr ? 'ضريبة%' : 'Tax%' }}</th>
                        <th class="p-3 w-28">{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                        <th class="p-3 w-10"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
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
            <button type="button" onclick="addBlankRow()" class="px-4 py-2 border border-dashed border-gray-400 text-gray-600 rounded-lg text-sm hover:border-[#005B9F] hover:text-[#005B9F] flex items-center gap-2">
                <i class="fas fa-plus"></i> {{ $isAr ? 'سطر يدوي' : 'Manual line' }}
            </button>
        </div>
    </div>

    <div class="max-w-6xl mx-auto mb-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="text-xs text-gray-500">{{ $isAr ? 'إجمالي الفاتورة:' : 'Invoice total:' }}</div>
                <div class="font-extrabold text-[#005B9F] text-xl" dir="ltr" id="grandTotal">0.00 <span class="text-xs font-normal text-gray-400">{{ $cur }}</span></div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('sales-orders.show', $salesOrder) }}" class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium">{{ $isAr ? 'إلغاء' : 'Cancel' }}</a>
                <button type="submit" class="px-7 py-2.5 bg-[#008A3B] hover:bg-[#007030] text-white rounded-lg font-bold text-sm flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save"></i> {{ $isAr ? 'حفظ فاتورة الشراء' : 'Save Purchase Invoice' }}
                </button>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    const ITEM_VENDOR_PRICE = @json($priceMap);
    const ALL_ITEMS = @json($jsItems);
    const isAr = @json($isAr);
    const SO_ITEMS = @json($soItemsArray);
    let rowIndex = 0;

    function fmt(n) { return (Math.round(n * 100) / 100).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function selectedVendorId() { return document.getElementById('vendorSelect').value; }

    function suggestedPrice(itemId) {
        const vId = selectedVendorId();
        if (!vId || !itemId) return null;
        const m = ITEM_VENDOR_PRICE[itemId] || {};
        const p = m[vId];
        return (p === undefined || p === null) ? null : p;
    }

    function rowNet(tr) {
        const q = parseFloat(tr.querySelector('.calc-q')?.value || 0);
        const p = parseFloat(tr.querySelector('.calc-p')?.value || 0);
        const d = parseFloat(tr.querySelector('.calc-d')?.value || 0);
        const t = parseFloat(tr.querySelector('.calc-t')?.value || 0);
        const afterDisc = (q * p) - ((q * p) * d / 100);
        const net = afterDisc + (afterDisc * t / 100);
        tr.querySelector('.net-cell').textContent = fmt(net);
        return net;
    }

    function recalcAll() {
        let grand = 0;
        document.querySelectorAll('#itemsBody tr').forEach(tr => grand += rowNet(tr));
        document.getElementById('grandTotal').childNodes[0].textContent = fmt(grand) + ' ';
    }

    function rowTemplate(data) {
        const i = rowIndex++;
        const price = data.item_id ? (suggestedPrice(data.item_id) ?? '') : '';
        const tr = document.createElement('tr');
        tr.className = 'border-b border-gray-100';
        tr.dataset.itemId = data.item_id || '';
        tr.innerHTML = `
            <td class="p-2">
                <input type="text" name="lines[${i}][description]" required value="${(data.description ?? '').replace(/"/g,'&quot;')}"
                    class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:border-[#005B9F]">
                <input type="hidden" name="lines[${i}][item_id]" value="${data.item_id ?? ''}">
                <input type="hidden" name="lines[${i}][item_code]" value="${data.code ?? ''}">
                <input type="hidden" name="lines[${i}][uom]" value="${data.uom ?? ''}">
            </td>
            <td class="p-2"><input type="number" step="any" min="0.001" name="lines[${i}][quantity]" value="${data.quantity ?? 1}" class="calc-q w-full px-2 py-1.5 border border-gray-300 rounded text-center"></td>
            <td class="p-2"><input type="number" step="0.01" min="0" name="lines[${i}][unit_price]" value="${price}" class="calc-p w-full px-2 py-1.5 border border-gray-300 rounded text-center"></td>
            <td class="p-2"><input type="number" step="0.01" min="0" max="100" name="lines[${i}][discount_percent]" value="0" class="calc-d w-full px-2 py-1.5 border border-gray-300 rounded text-center"></td>
            <td class="p-2"><input type="number" step="0.01" min="0" max="100" name="lines[${i}][tax_percent]" value="0" class="calc-t w-full px-2 py-1.5 border border-gray-300 rounded text-center"></td>
            <td class="p-2 font-bold text-[#005B9F] net-cell" dir="ltr">0.00</td>
            <td class="p-2 text-center"><button type="button" onclick="this.closest('tr').remove(); window.piRecalc()" class="text-gray-300 hover:text-red-500"><i class="fas fa-times-circle"></i></button></td>`;
        document.getElementById('itemsBody').appendChild(tr);
        tr.querySelectorAll('.calc-q,.calc-p,.calc-d,.calc-t').forEach(inp => inp.addEventListener('input', recalcAll));
        recalcAll();
    }

    window.piRecalc = recalcAll;
    window.addBlankRow = function () { rowTemplate({ description: '' }); };
    window.addItemById = function (id) {
        const item = ALL_ITEMS.find(x => String(x.id) === String(id));
        if (!item) return;
        rowTemplate({ item_id: item.id, code: item.code, description: item.name, uom: item.uom });
    };

    // إضافة أصناف أمر البيع تلقائياً
    SO_ITEMS.forEach(item => {
        rowTemplate(item);
    });

    // لما المورد يتغير، نحدّث الأسعار المقترحة للأصناف اللي لسه بسعرها الافتراضي فاضي/مقترح
    document.getElementById('vendorSelect').addEventListener('change', function () {
        document.querySelectorAll('#itemsBody tr').forEach(tr => {
            const p = suggestedPrice(tr.dataset.itemId);
            if (p !== null) tr.querySelector('.calc-p').value = p;
        });
        recalcAll();
    });

    setTimeout(function () {
        const picker = document.getElementById('itemPicker');
        if (picker && picker.tomselect) {
            picker.tomselect.destroy();
            const ts = new TomSelect(picker, { allowEmptyOption: true, maxOptions: 400, dropdownParent: 'body' });
            ts.on('item_add', function (value) { addItemById(value); ts.clear(true); ts.setTextboxValue(''); });
        } else if (picker) {
            picker.addEventListener('change', function () { if (this.value) addItemById(this.value); this.value = ''; });
        }
    }, 150);

    document.getElementById('piForm').addEventListener('submit', function (e) {
        if (!document.querySelectorAll('#itemsBody tr').length) {
            e.preventDefault();
            alert(isAr ? 'أضف صنفاً واحداً على الأقل.' : 'Add at least one item.');
        }
    });
})();
</script>
@endsection
