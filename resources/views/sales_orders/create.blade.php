@extends('layouts.app')
@section('header_title', app()->getLocale() === 'ar' ? 'تحويل لأمر بيع وإنشاء فاتورة بيع' : 'Convert to Sales Order & Create Invoice')

@php
    $isAr        = app()->getLocale() === 'ar';
    $docDir      = $isAr ? 'rtl' : 'ltr';
    $txtAlign    = $isAr ? 'right' : 'left';
    $txtAlignOpp = $isAr ? 'left' : 'right';
    $cur         = $quotation->currency ?? 'EGP';
    $clientDisplay = optional($quotation->client)->displayName($isAr ? 'ar' : 'en') ?? '—';

    $jsExtraItems = $items->map(function ($i) use ($isAr) {
        $name = $isAr ? ($i->name_ar ?: $i->name_en) : ($i->name_en ?: $i->name_ar);
        return ['id' => $i->id, 'code' => $i->item_code, 'name' => $name, 'uom' => $i->base_uom];
    })->values();
@endphp

@section('content')

@if(session('error'))
<div class="mb-4 max-w-5xl mx-auto bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 flex items-center gap-3">
    <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
    <span class="font-medium text-sm">{{ session('error') }}</span>
</div>
@endif

{{-- رجوع --}}
<div class="mb-4 flex items-center justify-between max-w-5xl mx-auto">
    <a href="{{ route('quotations.show', $quotation) }}"
       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
        <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i>
        {{ $isAr ? 'رجوع لعرض السعر' : 'Back to Quotation' }}
    </a>
</div>

{{-- بطاقة المعلومات --}}
<div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5" dir="{{ $docDir }}">
    <div class="h-1.5 bg-gradient-to-r from-[#008A3B] to-[#005B9F]"></div>
    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                <i class="fas fa-file-contract text-green-700"></i>
            </div>
            <div>
                <p class="font-bold text-gray-800 text-sm">{{ $isAr ? 'تحويل عرض السعر لأمر بيع وإنشاء فاتورة بيع' : 'Convert Quotation to Sales Order & Create Invoice' }}</p>
                <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $quotation->quote_number }}</p>
            </div>
        </div>
        <div class="text-{{ $txtAlignOpp }} text-xs text-gray-500 space-y-0.5">
            <p><span class="text-gray-400">{{ $isAr ? 'العميل:' : 'Client:' }}</span>
               <span class="font-bold text-gray-800 {{ $isAr ? 'mr-1' : 'ml-1' }}">{{ $clientDisplay }}</span></p>
            <p><span class="text-gray-400">{{ $isAr ? 'العملة:' : 'Currency:' }}</span>
               <span class="font-bold text-[#005B9F] {{ $isAr ? 'mr-1' : 'ml-1' }}">{{ $cur }}</span></p>
        </div>
    </div>
    <div class="px-6 py-3 bg-amber-50 border-b border-amber-100 flex items-start gap-2">
        <i class="fas fa-info-circle text-amber-500 mt-0.5 shrink-0"></i>
        <p class="text-xs text-amber-700 leading-relaxed">
            {{ $isAr
                ? 'تحويل عرض السعر لاعتماده كأمر بيع. البيانات غير قابلة للتعديل للحفاظ على تطابق أمر البيع مع عرض السعر.'
                : 'Convert quotation to a confirmed sales order. Details are locked to maintain consistency with the quotation.' }}
        </p>
    </div>
</div>

{{-- الفورم --}}
<form action="{{ route('sales-orders.store') }}" method="POST" id="convertForm">
    @csrf
    <input type="hidden" name="quotation_id" value="{{ $quotation->id }}">

    <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5" dir="{{ $docDir }}">
        <table class="w-full border-collapse" style="text-align:{{ $txtAlign }}">
            <thead>
                <tr style="background:#1e293b;color:#fff;">
                    <th class="px-3 py-3 text-center w-10">
                        <input type="checkbox" id="checkAll" checked
                            class="w-4 h-4 accent-green-600 cursor-pointer"
                            title="{{ $isAr ? 'تحديد الكل' : 'Select all' }}">
                    </th>
                    <th class="px-3 py-3 text-center text-[11px] font-bold w-8">#</th>
                    <th class="px-3 py-3 text-[11px] font-bold w-28">{{ $isAr ? 'الكود' : 'Code' }}</th>
                    <th class="px-3 py-3 text-[11px] font-bold">{{ $isAr ? 'الوصف' : 'Description' }}</th>
                    <th class="px-3 py-3 text-center text-[11px] font-bold w-32">{{ $isAr ? 'الكمية' : 'Qty' }}</th>
                    <th class="px-3 py-3 text-center text-[11px] font-bold">{{ $isAr ? 'السعر' : 'Price' }}</th>
                    <th class="px-3 py-3 text-center text-[11px] font-bold">{{ $isAr ? 'الخصم' : 'Disc%' }}</th>
                    <th class="px-3 py-3 text-[11px] font-bold" style="text-align:{{ $txtAlignOpp }}">{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $idx => $line)
                @php
                    $lineBase = $line->quantity * $line->list_price;
                    $lineDisc = $lineBase * $line->discount_percent / 100;
                    $lineAfter = $lineBase - $lineDisc;
                    $lineTax  = $lineAfter * $line->tax_percent / 100;
                @endphp
                <tr class="item-row {{ $idx % 2 === 0 ? '' : 'bg-gray-50/70' }} border-b border-gray-100 transition-opacity"
                    data-list-price="{{ $line->list_price }}"
                    data-disc-percent="{{ $line->discount_percent }}"
                    data-tax-percent="{{ $line->tax_percent }}"
                    data-base="{{ $lineBase }}"
                    data-disc="{{ $lineDisc }}"
                    data-tax="{{ $lineTax }}">
                    <td class="px-3 py-2.5 text-center">
                        <input type="checkbox" name="selected_items[]" value="{{ $line->id }}" checked
                            class="item-check w-4 h-4 accent-green-600 cursor-pointer">
                    </td>
                    <td class="px-3 py-2 text-center text-gray-400 text-[11px]">{{ $idx + 1 }}</td>
                    <td class="px-3 py-2 text-[11px] font-mono font-bold text-[#005B9F]" dir="ltr">
                        {{ $line->item_code ?? '—' }}
                    </td>
                    <td class="px-3 py-2 text-gray-800 font-medium text-xs">
                        {{ $line->displayDescription($isAr ? 'ar' : 'en') }}
                    </td>
                    <td class="px-3 py-2 text-center" dir="ltr">
                        <div class="flex items-center justify-center gap-1">
                            <input type="number"
                                name="quantities[{{ $line->id }}]"
                                value="{{ rtrim(rtrim(number_format($line->quantity, 4, '.', ''), '0'), '.') }}"
                                readonly
                                class="qty-input w-20 text-center border-0 bg-transparent text-xs font-bold text-gray-800 focus:outline-none">
                            @if($line->uom)
                            <span class="text-[10px] text-gray-400">{{ $line->uom }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-3 py-2 text-center" dir="ltr">
                        <input type="number"
                            name="prices[{{ $line->id }}]"
                            value="{{ number_format($line->list_price, 4, '.', '') }}"
                            readonly
                            class="price-input w-24 text-center border-0 bg-transparent text-xs font-bold text-gray-800 focus:outline-none">
                    </td>
                    <td class="px-3 py-2 text-center text-gray-400 text-xs" dir="ltr">
                        @if($line->discount_percent > 0)
                            {{ rtrim(rtrim(number_format($line->discount_percent, 2), '0'), '.') }}%
                        @else —
                        @endif
                    </td>
                    <td class="px-3 py-2 font-extrabold text-gray-900 text-xs" style="text-align:{{ $txtAlignOpp }}" dir="ltr">
                        <span class="row-net">{{ number_format($line->net_total, 2) }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>



    {{-- شريط الإجمالي + حفظ --}}
    <div class="max-w-5xl mx-auto mb-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            {{-- الإجمالي المحسوب --}}
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 border-r border-gray-200 pr-4">
                    <span class="text-sm font-bold text-gray-700">{{ $isAr ? 'الخصم الإضافي:' : 'Extra Discount:' }}</span>
                    <input type="number" step="0.01" min="0" name="extra_discount" id="extraDiscount" value="{{ old('extra_discount', !empty($quotation->extra_discount) ? $quotation->extra_discount : '') }}"
                        oninput="window.recalcSO()" class="w-24 px-2 py-1 border border-gray-300 rounded text-left text-red-600 font-bold" dir="ltr">
                    <span class="text-xs text-gray-400">{{ $cur }}</span>
                </div>
                
                <div class="text-xs text-gray-500 pl-4">{{ $isAr ? 'إجمالي المحدد:' : 'Selected total:' }}</div>
                <div class="font-extrabold text-[#005B9F] text-xl" dir="ltr" id="selectedTotal">
                    {{ number_format($quotation->grand_total, 2) }}
                    <span class="text-xs font-normal text-gray-400">{{ $cur }}</span>
                </div>
                <div class="text-xs text-gray-400 bg-gray-100 rounded-full px-2 py-0.5" id="selectedCount">
                    {{ $quotation->items->count() }} {{ $isAr ? 'صنف' : 'items' }}
                </div>
            </div>

            {{-- أزرار --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('quotations.show', $quotation) }}"
                   class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium transition-colors">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </a>
                <button type="submit" id="submitBtn"
                    class="px-7 py-2.5 bg-[#008A3B] hover:bg-[#007030] text-white rounded-lg font-bold text-sm flex items-center gap-2 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-save"></i>
                    {{ $isAr ? 'حفظ كأمر بيع' : 'Save as Sales Order' }}
                </button>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    const rows        = document.querySelectorAll('.item-row');
    const checks      = document.querySelectorAll('.item-check');
    const qtyInputs   = document.querySelectorAll('.qty-input');
    const priceInputs = document.querySelectorAll('.price-input');
    const checkAll    = document.getElementById('checkAll');
    const totalEl     = document.getElementById('selectedTotal');
    const countEl     = document.getElementById('selectedCount');
    const submitBtn   = document.getElementById('submitBtn');
    const currency    = @json($cur);
    const isAr        = @json($isAr);
    const EXTRA_ITEMS = @json($jsExtraItems);

    function rowRecalc(row, i) {
        const qty     = parseFloat(qtyInputs[i].value)   || 0;
        const price   = parseFloat(priceInputs[i].value) || 0;
        const discPct = parseFloat(row.dataset.discPercent || 0);
        const taxPct  = parseFloat(row.dataset.taxPercent  || 0);

        row.dataset.listPrice = price;

        const base      = qty * price;
        const discVal   = base * discPct / 100;
        const afterDisc = base - discVal;
        const taxVal    = afterDisc * taxPct / 100;

        row.dataset.base = base;
        row.dataset.disc = discVal;
        row.dataset.tax  = taxVal;
        row.querySelector('.row-net').textContent = (afterDisc + taxVal).toFixed(2);
    }



    function recalc() {
        let subtotal = 0, disc = 0, tax = 0, count = 0;
        let extraDisc = parseFloat(document.getElementById('extraDiscount').value) || 0;

        rows.forEach((row, i) => {
            const checked = checks[i].checked;
            row.style.opacity       = checked ? '1' : '0.4';
            qtyInputs[i].disabled   = !checked;
            priceInputs[i].disabled = !checked;

            if (checked) {
                subtotal += parseFloat(row.dataset.base || 0);
                disc     += parseFloat(row.dataset.disc || 0);
                tax      += parseFloat(row.dataset.tax  || 0);
                count++;
            }
        });

        const grand = subtotal - (disc + extraDisc) + tax;
        totalEl.innerHTML = grand.toFixed(2) + ' <span class="text-xs font-normal text-gray-400">' + currency + '</span>';
        countEl.textContent = count + ' ' + (isAr ? 'صنف' : 'items');

        submitBtn.disabled = count === 0;
        checkAll.indeterminate = count > 0 && count < checks.length;
        checkAll.checked = count === checks.length;
    }

    window.recalcSO = recalc;



    qtyInputs.forEach((inp, i) => {
        inp.addEventListener('input', () => { rowRecalc(rows[i], i); recalc(); });
    });

    priceInputs.forEach((inp, i) => {
        inp.addEventListener('input', () => { rowRecalc(rows[i], i); recalc(); });
    });

    checks.forEach(cb => cb.addEventListener('change', recalc));

    checkAll.addEventListener('change', function () {
        checks.forEach(cb => { cb.checked = this.checked; });
        recalc();
    });

    document.getElementById('convertForm').addEventListener('submit', function (e) {
        const anyChecked = [...checks].some(c => c.checked);
        if (!anyChecked) {
            e.preventDefault();
            alert(isAr ? 'اختر صنفاً واحداً على الأقل.' : 'Select at least one item.');
        }
    });

    recalc();
})();
</script>
@endsection
