@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $cur  = $salesOrder->currency ?? 'EGP';
    $clientDisplay = optional($salesOrder->client)->displayName($isAr ? 'ar' : 'en') ?? '—';
@endphp
@section('header_title', $isAr ? 'إنشاء فاتورة بيع' : 'Create Sales Invoice')

@section('content')
<div class="mb-6 flex justify-between items-center max-w-5xl mx-auto animate-fade-in">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
            <i class="fas fa-file-invoice-dollar text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'إنشاء فاتورة بيع' : 'Create Sales Invoice' }}</h2>
    </div>
    <a href="{{ route('sales-orders.show', $salesOrder) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
        <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ $isAr ? 'رجوع لأمر البيع' : 'Back to Sales Order' }}
    </a>
</div>

@if(session('error'))
<div class="max-w-5xl mx-auto mb-4 bg-red-50 border border-red-300 text-red-800 rounded-xl p-4 text-sm">{{ session('error') }}</div>
@endif
@if(session('warning'))
<div class="max-w-5xl mx-auto mb-4 bg-amber-50 border border-amber-300 text-amber-800 rounded-xl p-4 text-sm">{{ session('warning') }}</div>
@endif

@if($errors->any())
<div class="max-w-5xl mx-auto mb-6 bg-red-50 border-l-4 border-red-500 rounded-r-xl p-5 shadow-sm animate-fade-in">
    <div class="flex items-center gap-3 mb-3">
        <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
        <h3 class="text-red-800 font-bold text-lg">{{ $isAr ? 'عفواً، راجع الأخطاء التالية:' : 'Please review the following errors:' }}</h3>
    </div>
    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
    <div class="h-1.5 bg-gradient-to-r from-[#008A3B] to-[#005B9F]"></div>
    <div class="px-6 py-3 bg-green-50 border-b border-green-100 flex items-start gap-2">
        <i class="fas fa-info-circle text-green-600 mt-0.5 shrink-0"></i>
        <p class="text-xs text-green-800 leading-relaxed">
            {{ $isAr
                ? 'اختر الأصناف والكميات المراد فوترتها. تقدر تعمل أكتر من فاتورة لنفس أمر البيع (فوترة جزئية). بمجرد الحفظ يصبح المبلغ مستحقًا على العميل.'
                : 'Select items and quantities to invoice. You can create multiple invoices for the same sales order (partial billing). Once saved, the amount becomes a client receivable.' }}
        </p>
    </div>
</div>

<form action="{{ route('sales-invoices.store') }}" method="POST" id="siForm">
    @csrf
    <input type="hidden" name="sales_order_id" value="{{ $salesOrder->id }}">

    <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
        <p class="text-xs text-gray-400 font-mono mb-4">{{ $salesOrder->so_number }} — {{ $clientDisplay }}</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
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
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'موعد الاستحقاق' : 'Due Date' }} <span class="text-red-500">*</span></label>
                <input type="date" name="due_date" required value="{{ old('due_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                <p class="text-[11px] text-gray-400 mt-1">{{ $isAr ? 'لو العميل ما سددش قبل هذا التاريخ هتظهر الفاتورة في التنبيهات' : "If the client hasn't paid by this date, the invoice will show up in alerts" }}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'عملة الفاتورة' : 'Invoice Currency' }} <span class="text-red-500">*</span></label>
                <select name="currency" id="invoiceCurrency" required dir="ltr"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono bg-white focus:outline-none focus:border-[#008A3B]">
                    @foreach($currencies as $c)
                        <option value="{{ $c->key_value }}" {{ old('currency', $cur) == $c->key_value ? 'selected' : '' }}>{{ $c->key_value }} — {{ $c->display_name }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-amber-600 mt-1">{{ $isAr ? 'تنبيه: العملة نهائية بعد الحفظ ولا يمكن تغييرها — وهتبقى إلزامية عند تحصيل أي سند قبض لهذه الفاتورة.' : 'Note: the currency is final once saved and cannot be changed later — it will be enforced on any receipt collected against this invoice.' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'ملاحظات' : 'Notes' }}</label>
                <textarea name="notes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/60 font-bold text-gray-800 text-sm flex items-center gap-2">
            <span class="w-1.5 h-5 bg-[#008A3B] rounded-full"></span> {{ $isAr ? 'أصناف الفاتورة' : 'Invoice Items' }}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm" style="text-align:{{ $isAr ? 'right' : 'left' }}">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-[11px] font-bold">
                        <th class="p-3 w-10"></th>
                        <th class="p-3">{{ $isAr ? 'الوصف' : 'Description' }}</th>
                        <th class="p-3 text-center">{{ $isAr ? 'الكمية في الأمر' : 'SO Qty' }}</th>
                        <th class="p-3 text-center">{{ $isAr ? 'متبقي للفوترة' : 'Remaining' }}</th>
                        <th class="p-3 text-center">{{ $isAr ? 'كمية الفاتورة' : 'Invoice Qty' }}</th>
                        <th class="p-3 text-center">{{ $isAr ? 'السعر' : 'Price' }}</th>
                        <th class="p-3">{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $row)
                        @php 
                            $item = $row['item']; 
                            $isZero = $row['remaining'] == 0;
                        @endphp
                        <tr class="border-b border-gray-100 si-line {{ $isZero ? 'opacity-50' : '' }}" data-id="{{ $item->id }}">
                            <td class="p-3 text-center">
                                <input type="checkbox" name="selected_items[]" value="{{ $item->id }}" class="si-check w-4 h-4 rounded" {{ $isZero ? '' : 'checked' }}>
                            </td>
                            <td class="p-3 text-gray-800">{{ $item->displayDescription($isAr ? 'ar' : 'en') }}</td>
                            <td class="p-3 text-center" dir="ltr">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                            <td class="p-3 text-center font-bold text-amber-600" dir="ltr">{{ rtrim(rtrim(number_format($row['remaining'], 2), '0'), '.') }}</td>
                            <td class="p-3">
                                <input type="number" step="any" min="0.001" name="quantities[{{ $item->id }}]"
                                    value="{{ old('quantities.'.$item->id, $isZero ? '' : $row['remaining']) }}"
                                    class="si-qty w-full px-2 py-1.5 border border-gray-300 rounded text-center"
                                    data-discount="{{ $item->discount_percent }}" data-tax="{{ $item->tax_percent }}"
                                    {{ $isZero ? 'disabled' : '' }}>
                            </td>
                            <td class="p-3 text-center" dir="ltr">
                                <input type="number" step="any" min="0" name="prices[{{ $item->id }}]"
                                    value="{{ old('prices.'.$item->id, $item->list_price) }}"
                                    class="si-price w-full px-2 py-1.5 border border-gray-300 rounded text-center"
                                    {{ $isZero ? 'disabled' : '' }}>
                            </td>
                            <td class="p-3 font-bold text-[#008A3B] si-net" dir="ltr">0.00</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- بنود إضافية (Extra Lines) --}}
    <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/60 font-bold text-gray-800 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-1.5 h-5 bg-indigo-500 rounded-full"></span> {{ $isAr ? 'أصناف إضافية (خارج أمر البيع)' : 'Extra Lines' }}
            </div>
            <button type="button" onclick="addExtraLine()" class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold hover:bg-indigo-100 flex items-center gap-1 transition-colors">
                <i class="fas fa-plus"></i> {{ $isAr ? 'إضافة صنف' : 'Add Item' }}
            </button>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="w-full text-sm" id="extraLinesTable">
                <thead>
                    <tr class="text-gray-400 text-xs text-{{ $isAr ? 'right' : 'left' }}">
                        <th class="pb-2 font-medium w-1/4">{{ $isAr ? 'الصنف / الوصف' : 'Item / Description' }} <span class="text-red-400">*</span></th>
                        <th class="pb-2 font-medium w-24">{{ $isAr ? 'الكمية' : 'Qty' }} <span class="text-red-400">*</span></th>
                        <th class="pb-2 font-medium w-24">{{ $isAr ? 'الوحدة' : 'UOM' }}</th>
                        <th class="pb-2 font-medium w-28">{{ $isAr ? 'السعر' : 'Price' }} <span class="text-red-400">*</span></th>
                        <th class="pb-2 font-medium w-20">{{ $isAr ? 'خصم %' : 'Disc %' }}</th>
                        <th class="pb-2 font-medium w-20">{{ $isAr ? 'ضريبة %' : 'Tax %' }}</th>
                        <th class="pb-2 font-medium w-24">{{ $isAr ? 'الصافي' : 'Net' }}</th>
                        <th class="pb-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="extraLinesBody">
                    <!-- يتم إضافتها بالـ JS -->
                </tbody>
            </table>
        </div>
    </div>

    <div class="max-w-5xl mx-auto mb-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <div>
                    <div class="text-xs text-gray-500">{{ $isAr ? 'الإجمالي' : 'Subtotal' }}</div>
                    <div class="font-bold text-gray-800 text-lg" dir="ltr" id="subtotalDisplay">0.00</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">{{ $isAr ? 'إجمالي الضريبة' : 'Tax total' }}</div>
                    <div class="font-bold text-gray-800 text-lg" dir="ltr" id="taxTotalDisplay">0.00</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">{{ $isAr ? 'إجمالي الفاتورة:' : 'Invoice total:' }}</div>
                    <div class="font-extrabold text-[#008A3B] text-xl" dir="ltr" id="grandTotal">0.00 <span class="text-xs font-normal text-gray-400" id="grandTotalCur">{{ $cur }}</span></div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('sales-orders.show', $salesOrder) }}" class="px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium">{{ $isAr ? 'إلغاء' : 'Cancel' }}</a>
                <button type="submit" class="px-7 py-2.5 bg-[#008A3B] hover:bg-[#007030] text-white rounded-lg font-bold text-sm flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save"></i> {{ $isAr ? 'حفظ فاتورة البيع' : 'Save Sales Invoice' }}
                </button>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    // 1. حساب سطور أمر البيع الأساسية
    function lineNet(tr) {
        const chk = tr.querySelector('.si-check');
        if (!chk || !chk.checked) return { net: 0, afterDisc: 0, tax: 0 };
        const q = parseFloat(tr.querySelector('.si-qty').value || 0);
        const p = parseFloat(tr.querySelector('.si-price').value || 0);
        const d = parseFloat(tr.querySelector('.si-qty').dataset.discount || 0);
        const t = parseFloat(tr.querySelector('.si-qty').dataset.tax || 0);
        const base = q * p;
        const afterDisc = base - (base * d / 100);
        const tax = afterDisc * t / 100;
        const net = afterDisc + tax;
        if(tr.querySelector('.si-net')) tr.querySelector('.si-net').textContent = net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        return { net, afterDisc, tax };
    }

    // 2. حساب سطور الأصناف الإضافية
    function calcExtraLineNet(tr) {
        const q = parseFloat(tr.querySelector('.ex-qty').value || 0);
        const p = parseFloat(tr.querySelector('.ex-price').value || 0);
        const d = parseFloat(tr.querySelector('.ex-disc').value || 0);
        const t = parseFloat(tr.querySelector('.ex-tax').value || 0);
        const base = q * p;
        const afterDisc = base - (base * d / 100);
        const tax = afterDisc * t / 100;
        const net = afterDisc + tax;
        if(tr.querySelector('.ex-net')) tr.querySelector('.ex-net').textContent = net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        return { net, afterDisc, tax };
    }

    function recalc() {
        let subtotal = 0, taxTotal = 0;
        document.querySelectorAll('.si-line').forEach(tr => {
            const r = lineNet(tr);
            subtotal += r.afterDisc;
            taxTotal += r.tax;
        });
        document.querySelectorAll('.ex-line').forEach(tr => {
            const r = calcExtraLineNet(tr);
            subtotal += r.afterDisc;
            taxTotal += r.tax;
        });
        const grand = subtotal + taxTotal;
        document.getElementById('subtotalDisplay').textContent = subtotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('taxTotalDisplay').textContent = taxTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('grandTotal').childNodes[0].textContent = grand.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ';
    }

    document.querySelectorAll('.si-check, .si-qty, .si-price').forEach(el => el.addEventListener('input', recalc));
    document.querySelectorAll('.si-check').forEach(el => {
        el.addEventListener('change', function() {
            const tr = this.closest('tr');
            const qty = tr.querySelector('.si-qty');
            const price = tr.querySelector('.si-price');
            qty.disabled = !this.checked;
            price.disabled = !this.checked;
            
            // If checked and qty is empty, default it to 1 just to prevent validation errors if they want to invoice it again
            if (this.checked && !qty.value) {
                qty.value = 1;
            } else if (!this.checked) {
                qty.value = '';
            }
            
            if(this.checked) {
                tr.classList.remove('opacity-50');
            } else {
                tr.classList.add('opacity-50');
            }
            recalc();
        });
    });
    recalc();

    const invoiceCurrencySel = document.getElementById('invoiceCurrency');
    if (invoiceCurrencySel) {
        invoiceCurrencySel.addEventListener('change', function () {
            document.getElementById('grandTotalCur').textContent = this.value;
        });
    }

    // 3. إدارة سطور الإضافي (Extra Lines)
    let extraIndex = 0;
    const isAr = @json($isAr);
    const itemsList = @json($itemsList);
    let itemsOptions = '<option value="">' + (isAr ? 'صنف حر (بدون كود)' : 'Free item (no code)') + '</option>';
    itemsList.forEach(item => {
        itemsOptions += `<option value="${item.id}" data-desc="${item.name_ar}" data-uom="${item.base_uom||''}">${item.name_ar} (${item.item_code})</option>`;
    });

    window.addExtraLine = function() {
        const tbody = document.getElementById('extraLinesBody');
        const tr = document.createElement('tr');
        tr.className = 'border-b border-gray-50 ex-line group';
        tr.innerHTML = `
            <td class="py-2 pr-2 align-top">
                <select name="extra_lines[${extraIndex}][item_id]" class="w-full text-xs p-1.5 border border-gray-200 rounded mb-1 ex-item-select">
                    ${itemsOptions}
                </select>
                <input type="text" name="extra_lines[${extraIndex}][description]" required placeholder="${isAr ? 'وصف الصنف...' : 'Description...'}" class="w-full text-xs p-1.5 border border-gray-200 rounded mt-1 ex-desc focus:border-indigo-500 outline-none">
            </td>
            <td class="py-2 px-1 align-top">
                <input type="number" step="any" min="0.001" name="extra_lines[${extraIndex}][quantity]" required class="w-full text-xs p-1.5 border border-gray-200 rounded ex-qty text-center focus:border-indigo-500 outline-none" value="1" oninput="window.triggerRecalc()">
            </td>
            <td class="py-2 px-1 align-top">
                <input type="text" name="extra_lines[${extraIndex}][uom]" class="w-full text-xs p-1.5 border border-gray-200 rounded ex-uom text-center focus:border-indigo-500 outline-none">
            </td>
            <td class="py-2 px-1 align-top">
                <input type="number" step="any" min="0" name="extra_lines[${extraIndex}][unit_price]" required class="w-full text-xs p-1.5 border border-gray-200 rounded ex-price text-center focus:border-indigo-500 outline-none" value="0" oninput="window.triggerRecalc()">
            </td>
            <td class="py-2 px-1 align-top">
                <input type="number" step="any" min="0" max="100" name="extra_lines[${extraIndex}][discount_percent]" class="w-full text-xs p-1.5 border border-gray-200 rounded ex-disc text-center focus:border-indigo-500 outline-none" value="0" oninput="window.triggerRecalc()">
            </td>
            <td class="py-2 px-1 align-top">
                <input type="number" step="any" min="0" max="100" name="extra_lines[${extraIndex}][tax_percent]" class="w-full text-xs p-1.5 border border-gray-200 rounded ex-tax text-center focus:border-indigo-500 outline-none" value="0" oninput="window.triggerRecalc()">
            </td>
            <td class="py-2 px-1 align-top font-bold text-indigo-600 text-xs text-center ex-net" dir="ltr" style="padding-top:10px;">0.00</td>
            <td class="py-2 pl-2 align-top text-center">
                <button type="button" onclick="this.closest('tr').remove(); window.triggerRecalc();" class="text-gray-300 hover:text-red-500 mt-1"><i class="fas fa-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);

        const newSelect = tr.querySelector('.ex-item-select');
        if (typeof TomSelect !== 'undefined') {
            new TomSelect(newSelect, {
                allowEmptyOption: true,
                maxOptions: 300,
                onChange: function(val) {
                    if (val) {
                        const item = itemsList.find(i => String(i.id) === String(val));
                        if (item) {
                            tr.querySelector('.ex-desc').value = isAr ? (item.name_ar || item.name_en) : (item.name_en || item.name_ar);
                            tr.querySelector('.ex-uom').value = item.base_uom || '';
                        }
                    } else {
                        tr.querySelector('.ex-desc').value = '';
                        tr.querySelector('.ex-uom').value = '';
                    }
                },
                render: {
                    no_results: function () {
                        return '<div class="no-results" style="padding:.5rem .75rem;color:#9ca3af;font-size:.8rem;">' + (isAr ? 'لا توجد نتائج' : 'No results') + '</div>';
                    }
                }
            });
        }

        extraIndex++;
        recalc();
    };

    window.triggerRecalc = recalc;

    document.getElementById('siForm').addEventListener('submit', function (e) {
        const hasMain = document.querySelector('.si-check:checked');
        const hasExtra = document.querySelectorAll('.ex-line').length > 0;
        if (!hasMain && !hasExtra) {
            e.preventDefault();
            alert(@json($isAr ? 'اختر صنفاً واحداً على الأقل أو أضف صنفاً إضافياً.' : 'Select at least one item or add an extra line.'));
        }
    });
})();
</script>
@endsection
