@php
    $isEdit = isset($quotation) && $quotation->exists;
    $action = $isEdit ? route('quotations.update', $quotation) : route('quotations.store');

    $isAr = app()->getLocale() === 'ar';

    // تجهيز البيانات للـ JS (نتجنب المصفوفات داخل @json مباشرة لتفادي خطأ التحليل)
    $jsItems = $items->map(function ($i) use ($isAr) {
        $name = $isAr ? ($i->name_ar ?: $i->name_en) : ($i->name_en ?: $i->name_ar);
        return ['id' => $i->id, 'code' => $i->item_code, 'name' => $name, 'uom' => $i->base_uom];
    })->values();

    $jsPreload = array_values(old('items', $isEdit
        ? $quotation->items->map(function ($it) {
            return [
                'item_id' => $it->item_id, 'item_code' => $it->item_code, 'description' => $it->description,
                'quantity' => $it->quantity, 'uom' => $it->uom, 'list_price' => $it->list_price,
                'discount_percent' => $it->discount_percent, 'tax_percent' => $it->tax_percent,
            ];
        })->toArray()
        : []));
@endphp

<div class="container mx-auto px-4 max-w-7xl animate-fade-in">

    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-300 text-red-800 rounded-xl p-4">
        <div class="flex items-center gap-2 font-bold mb-2"><i class="fas fa-exclamation-circle"></i> يرجى مراجعة الأخطاء التالية:</div>
        <ul class="list-disc list-inside space-y-1 text-sm">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ $action }}" method="POST" id="quoteForm">
        @csrf
        @if($isEdit) @method('PUT') @endif
        {{-- status يُرسل من الـ select الظاهر أدناه --}}

        {{-- شريط العنوان والأزرار --}}
        <div class="bg-[#1e293b] text-white rounded-2xl shadow-sm px-6 py-4 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm text-gray-300 flex items-center gap-2">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>{{ __('messages.app_name') }} ERP</span>
                    <i class="fas fa-angle-left text-xs"></i>
                    <span>{{ __('messages.nav.sales_mgmt') }}</span>
                    <i class="fas fa-angle-left text-xs"></i>
                    <span class="text-white font-bold">{{ $isEdit ? __('messages.quotations.edit_title') : __('messages.quotations.add_title') }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="px-5 py-2 bg-[#008A3B] hover:bg-[#007030] rounded-lg font-bold text-sm flex items-center gap-2 shadow">
                        <i class="fas fa-save"></i> {{ __('messages.quotations.save') }}
                    </button>
                    @if($isEdit)
                        <a href="{{ route('quotations.show', $quotation) }}" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg font-bold text-sm flex items-center gap-2">
                            <i class="fas fa-print"></i> {{ __('messages.quotations.print') }}
                        </a>
                        <button type="button" onclick="document.getElementById('cloneForm').submit()"
                            class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg font-bold text-sm flex items-center gap-2">
                            <i class="fas fa-copy"></i> {{ __('messages.quotations.clone') }}
                        </button>
                    @endif
                    <a href="{{ route('quotations.index') }}" class="px-4 py-2 text-red-300 hover:text-red-200 rounded-lg font-bold text-sm flex items-center gap-2">
                        <i class="fas fa-times"></i> {{ __('messages.common.cancel') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- بيانات الرأس --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-gray-100">
                <span class="text-[#008A3B] font-bold text-lg flex items-center gap-2"><i class="fas fa-info-circle"></i> {{ __('messages.quotations.add_title') }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.quotations.f_number') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="quote_number" required dir="ltr" value="{{ old('quote_number', $quotation->quote_number) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-right font-mono bg-gray-50 focus:outline-none focus:border-[#008A3B]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.quotations.f_date') }} <span class="text-red-500">*</span></label>
                    <input type="date" name="quote_date" id="quoteDateInput" required value="{{ old('quote_date', optional($quotation->quote_date)->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.quotations.f_expiry') }}</label>
                    <input type="date" name="expiry_date" value="{{ old('expiry_date', optional($quotation->expiry_date)->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                </div>

                {{-- حالة عرض السعر — تبدأ دائماً «مسودة» وتُدار بعد الحفظ عبر مسار الاعتماد --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        <i class="fas fa-toggle-on text-[#005B9F] text-xs me-1"></i>
                        {{ __('messages.quotations.status') }}
                    </label>
                    {{-- الحالة مثبتة على مسودة؛ تغيير الحالة يتم من شاشة العرض --}}
                    <input type="hidden" name="status" value="draft">
                    <div class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 flex items-center justify-between">
                        <span class="inline-flex items-center gap-1.5 text-sm font-bold text-gray-500">
                            <i class="fas fa-pencil-alt text-gray-400 text-xs"></i> {{ __('messages.quotations.st_draft') }}
                        </span>
                        <span class="text-[11px] text-gray-400">{{ $isAr ? 'تتغيّر بعد الحفظ' : 'Managed after saving' }}</span>
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.quotations.f_client') }} <span class="text-red-500">*</span></label>
                    <select name="client_id" id="clientIdSelect" required data-search class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                        <option value="">{{ __('messages.quotations.f_client_ph') }}</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', $quotation->client_id) == $client->id ? 'selected' : '' }}>
                                {{ $client->displayName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.quotations.f_opportunity') }}</label>
                    <input type="text" name="opportunity_ref" dir="ltr" value="{{ old('opportunity_ref', $quotation->opportunity_ref) }}" placeholder="{{ __('messages.quotations.f_opp_ph') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-right focus:outline-none focus:border-[#008A3B]">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.quotations.f_price_list') }}</label>
                    <select name="price_list_id" id="priceListSelect" data-search class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                        <option value="">{{ __('messages.quotations.f_pl_ph') }}</option>
                        @foreach($priceLists as $pl)
                            <option value="{{ $pl->id }}" {{ old('price_list_id', $quotation->price_list_id) == $pl->id ? 'selected' : '' }}>
                                {{ $pl->code }} — {{ $pl->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.quotations.f_sales_rep') }}</label>
                    <input type="text" name="sales_rep" value="{{ old('sales_rep', $quotation->sales_rep) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.quotations.f_currency') }} <span class="text-red-500">*</span></label>
                    <select name="currency" id="currencySelect" required data-search class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                        @foreach($currencies as $c)
                            <option value="{{ $c->key_value }}" {{ old('currency', $quotation->currency ?? 'EGP') == $c->key_value ? 'selected' : '' }}>{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        <i class="fas fa-layer-group text-purple-500 me-1"></i>
                        {{ $isAr ? 'اسم مركز التكلفة' : 'Cost Center Name' }}
                    </label>
                    <input type="text" name="cost_center_name" id="costCenterNameInput" value="{{ old('cost_center_name', $quotation->cost_center_name) }}"
                        placeholder="{{ $isAr ? 'سيُقترح اسم تلقائيًا عند اختيار العميل — ويمكن تعديله فورًا' : 'A default name is suggested once you pick a client — edit it right away if you like' }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                </div>
            </div>
        </div>

        {{-- جدول الأصناف --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                <span class="font-bold text-gray-800 flex items-center gap-2"><span class="w-1.5 h-5 bg-[#008A3B] rounded-full"></span> {{ __('messages.quotations.items_table') }}</span>
                <span id="plBadge" class="hidden text-xs text-[#005B9F] bg-blue-50 px-3 py-1 rounded-full flex items-center gap-1.5">
                    <i class="fas fa-tags"></i> <span id="plBadgeText"></span>
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse min-w-[900px]">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-xs font-bold">
                            <th class="p-3 w-10 text-center">#</th>
                            <th class="p-3 w-32">{{ __('messages.quotations.th_code') }}</th>
                            <th class="p-3 min-w-[220px]">{{ __('messages.quotations.th_desc') }}</th>
                            <th class="p-3 w-20 text-center">{{ __('messages.quotations.th_qty') }}</th>
                            <th class="p-3 w-24 text-center">{{ __('messages.quotations.th_uom') }}</th>
                            <th class="p-3 w-28 text-center">{{ __('messages.quotations.th_price') }}</th>
                            <th class="p-3 w-20 text-center">{{ __('messages.quotations.th_disc') }}</th>
                            <th class="p-3 w-20 text-center">{{ __('messages.quotations.th_tax') }}</th>
                            <th class="p-3 w-32 text-left">{{ __('messages.quotations.th_net') }}</th>
                            <th class="p-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="quoteItemsBody" class="divide-y divide-gray-100 text-sm"></tbody>
                </table>
            </div>
            <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/60 flex flex-wrap items-center gap-3">
                <select id="itemPicker" data-search class="w-full md:w-80 px-4 py-2 border border-gray-300 rounded-lg bg-white text-sm">
                    <option value="">{{ __('messages.quotations.add_row') }}</option>
                    @foreach($items as $it)
                        @php $nm = $isAr ? ($it->name_ar ?: $it->name_en) : ($it->name_en ?: $it->name_ar); @endphp
                        <option value="{{ $it->id }}">{{ $it->item_code }} — {{ $nm }}</option>
                    @endforeach
                </select>
                <button type="button" onclick="addBlankRow()" class="px-4 py-2 border border-dashed border-gray-400 text-gray-600 rounded-lg text-sm hover:border-[#008A3B] hover:text-[#008A3B] flex items-center gap-2">
                    <i class="fas fa-plus"></i> {{ app()->getLocale()==='ar' ? 'سطر يدوي (خدمة)' : 'Manual line (service)' }}
                </button>
            </div>
        </div>

        {{-- الشروط والإجماليات --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-12">
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <label class="block font-bold text-gray-800 mb-3 flex items-center gap-2"><i class="fas fa-file-contract text-gray-400"></i> {{ __('messages.quotations.terms_title') }}</label>

                {{-- قوالب جاهزة للشروط والأحكام --}}
                @php
                    $termsPresets = [
                        ['name' => __('messages.quotations.terms_p1_name'), 'text' => __('messages.quotations.terms_p1_text')],
                        ['name' => __('messages.quotations.terms_p2_name'), 'text' => __('messages.quotations.terms_p2_text')],
                    ];
                @endphp
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <span class="text-xs font-bold text-gray-400">{{ __('messages.quotations.terms_presets') }}:</span>
                    @foreach($termsPresets as $idx => $preset)
                        <button type="button" onclick="applyTermsPreset({{ $idx }})"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold text-gray-600 hover:border-[#008A3B] hover:text-[#008A3B] hover:bg-green-50 transition-colors flex items-center gap-1.5">
                            <i class="fas fa-file-alt text-[10px]"></i> {{ $preset['name'] }}
                        </button>
                    @endforeach
                </div>

                <textarea name="terms" id="termsBox" rows="6" placeholder="{{ __('messages.quotations.terms_ph') }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm leading-relaxed focus:outline-none focus:border-[#008A3B]">{{ old('terms', $quotation->terms) }}</textarea>

                <script>
                    const QT_TERMS_PRESETS = @json(array_column($termsPresets, 'text'));
                    function applyTermsPreset(i) {
                        const box = document.getElementById('termsBox');
                        const txt = QT_TERMS_PRESETS[i] || '';
                        // لو فيه نص بالفعل، نتأكد قبل الاستبدال
                        if (box.value.trim() && box.value.trim() !== txt.trim()) {
                            if (!confirm(@json($isAr ? 'سيتم استبدال النص الحالي بالقالب المختار. متابعة؟' : 'This will replace the current text with the selected template. Continue?'))) return;
                        }
                        box.value = txt;
                        box.focus();
                    }
                </script>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">{{ __('messages.quotations.subtotal') }}:</span>
                        <span class="font-bold text-gray-900" dir="ltr"><span id="sumSubtotal">0.00</span> <span class="quote-cur">EGP</span></span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-gray-600">{{ __('messages.quotations.extra_disc') }}:</span>
                        <div class="flex items-center gap-1">
                            <input type="number" step="0.01" min="0" name="extra_discount" id="extraDiscount" value="{{ old('extra_discount', $quotation->extra_discount ?? 0) }}"
                                oninput="recalc()" class="w-24 px-2 py-1 border border-gray-300 rounded text-left text-red-600 font-bold" dir="ltr">
                            <span class="text-red-500 text-xs quote-cur">EGP</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">{{ __('messages.quotations.vat') }}:</span>
                        <span class="font-bold text-gray-900" dir="ltr">+ <span id="sumTax">0.00</span> <span class="quote-cur">EGP</span></span>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                        <span class="font-bold text-[#005B9F]">{{ __('messages.quotations.grand_total') }}:</span>
                        <span class="text-xl font-extrabold text-[#005B9F]" dir="ltr"><span id="sumGrand">0.00</span> <span class="quote-cur">EGP</span></span>
                    </div>
                </div>
            </div>
        </div>
        {{-- شريط الحفظ السفلي --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-4 mb-12 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-gray-400 flex items-center gap-2">
                <i class="fas fa-info-circle"></i>
                {{ $isAr ? 'راجع البيانات جيداً قبل الحفظ' : 'Please review the data before saving' }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('quotations.index') }}" class="px-5 py-2.5 border border-gray-300 rounded-lg font-bold text-sm text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-times"></i> {{ __('messages.common.cancel') }}
                </a>
                <button type="submit" class="px-8 py-2.5 bg-[#008A3B] hover:bg-[#007030] text-white rounded-lg font-bold text-sm flex items-center gap-2 shadow">
                    <i class="fas fa-save"></i> {{ __('messages.quotations.save') }}
                </button>
            </div>
        </div>
    </form>

    {{-- فورم النسخ خارج الفورم الرئيسي تماماً --}}
    @if($isEdit)
    <form id="cloneForm" action="{{ route('quotations.clone', $quotation) }}" method="POST" class="hidden">
        @csrf
    </form>
    @endif

</div>

<script>
    const Q_ITEMS  = @json($jsItems);
    const Q_UOM    = @json($uoms);
    const PL_ITEMS_URL = "{{ url('quotations') }}";
    let qIndex = 0;

    // ===== اقتراح اسم مركز التكلفة تلقائيًا عند اختيار العميل/التاريخ =====
    const CLIENTS_MAP = @json($clients->mapWithKeys(fn($c) => [$c->id => $c->displayName()]));
    const CC_ISAR = @json($isAr);
    let costCenterTouched = @json(!empty(old('cost_center_name', $quotation->cost_center_name)));

    function suggestCostCenterName() {
        if (costCenterTouched) return;
        const input = document.getElementById('costCenterNameInput');
        const clientEl = document.getElementById('clientIdSelect');
        const dateEl = document.getElementById('quoteDateInput');
        if (!input || !clientEl || !clientEl.value) return;

        const clientName = CLIENTS_MAP[clientEl.value] || '';
        const date = dateEl && dateEl.value ? dateEl.value : '';
        input.value = CC_ISAR
            ? `مركز تكلفة العميل ${clientName} بتاريخ ${date}`.trim()
            : `Cost center for ${clientName} dated ${date}`.trim();
    }


    // كاش أسعار قائمة الأسعار المحددة: { item_id: price }
    let PL_PRICES  = {};
    let PL_LOADED  = null; // id القائمة المحملة حالياً

    // تحميل أسعار قائمة معينة في الخلفية
    async function fetchPriceListPrices(plId) {
        if (!plId || plId === PL_LOADED) return;
        try {
            const res  = await fetch(`${PL_ITEMS_URL}/${plId}/price-list-items`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            PL_PRICES  = {};
            (data.items || []).forEach(pli => { PL_PRICES[String(pli.item_id)] = pli.list_price; });
            PL_LOADED  = plId;
            // شارة اسم القائمة
            const badge = document.getElementById('plBadge');
            const badgeTxt = document.getElementById('plBadgeText');
            if (badge && badgeTxt) {
                badgeTxt.textContent = data.currency ? `قائمة أسعار — ${data.currency}` : 'قائمة أسعار نشطة';
                badge.classList.remove('hidden');
            }
        } catch (e) { /* silent */ }
    }

    // Toast notification غير blocking
    function showToast(msg, type) {
        const colors = { warn: 'bg-amber-50 border-amber-300 text-amber-800', info: 'bg-blue-50 border-blue-300 text-blue-800' };
        const icons  = { warn: 'fa-exclamation-triangle text-amber-500', info: 'fa-info-circle text-blue-500' };
        const toast  = document.createElement('div');
        toast.className = `fixed bottom-6 left-6 z-50 flex items-center gap-3 px-5 py-3 rounded-xl border shadow-lg text-sm font-medium animate-fade-in ${colors[type] || colors.info}`;
        toast.innerHTML = `<i class="fas ${icons[type] || icons.info} text-base"></i><span>${msg}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    function uomLabel(key) { return key ? (Q_UOM[key] || key) : ''; }
    function fmt(n) { return (Math.round(n * 100) / 100).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

    function rowTemplate(data) {
        const i = qIndex++;
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-green-50/30 align-top';
        tr.innerHTML = `
            <td class="p-2 text-center text-gray-400 row-num"></td>
            <td class="p-2">
                <input type="text" name="items[${i}][item_code]" value="${data.code ?? ''}" dir="ltr"
                    class="w-full px-2 py-1.5 border border-gray-200 rounded text-right font-mono text-xs bg-gray-50">
                <input type="hidden" name="items[${i}][item_id]" value="${data.item_id ?? ''}">
            </td>
            <td class="p-2">
                <input type="text" name="items[${i}][description]" required value="${(data.description ?? '').replace(/"/g,'&quot;')}"
                    class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:border-[#008A3B]">
            </td>
            <td class="p-2">
                <input type="number" step="0.01" min="0" name="items[${i}][quantity]" value="${data.quantity ?? 1}"
                    oninput="recalc()" class="w-full px-2 py-1.5 border border-gray-300 rounded text-center calc-q">
            </td>
            <td class="p-2">
                <input type="text" name="items[${i}][uom]" value="${data.uom ?? ''}"
                    class="w-full px-2 py-1.5 border border-gray-200 rounded text-center text-xs">
            </td>
            <td class="p-2">
                <input type="number" step="0.01" min="0" name="items[${i}][list_price]" value="${data.list_price ?? 0}"
                    oninput="recalc()" class="w-full px-2 py-1.5 border border-gray-300 rounded text-center calc-p">
            </td>
            <td class="p-2">
                <input type="number" step="0.01" min="0" max="100" name="items[${i}][discount_percent]" value="${data.discount_percent ?? 0}"
                    oninput="recalc()" class="w-full px-2 py-1.5 border border-gray-300 rounded text-center calc-d">
            </td>
            <td class="p-2">
                <input type="number" step="0.01" min="0" max="100" name="items[${i}][tax_percent]" value="${data.tax_percent ?? 14}"
                    oninput="recalc()" class="w-full px-2 py-1.5 border border-gray-300 rounded text-center calc-t">
            </td>
            <td class="p-2 text-left font-bold text-[#008A3B] net-cell" dir="ltr">0.00</td>
            <td class="p-2 text-center">
                <button type="button" onclick="qRemove(this)" class="text-gray-300 hover:text-red-500" title="@lang('messages.quotations.remove_row')"><i class="fas fa-times-circle"></i></button>
            </td>`;
        document.getElementById('quoteItemsBody').appendChild(tr);
        qRenumber();
        recalc();
        return tr;
    }

    function addItemById(id) {
        const item = Q_ITEMS.find(x => String(x.id) === String(id));
        if (!item) return;

        const plEl    = document.getElementById('priceListSelect');
        const plId    = plEl ? plEl.value : '';
        const priceKey = String(item.id);

        let listPrice = 0;
        if (plId) {
            if (PL_PRICES.hasOwnProperty(priceKey)) {
                listPrice = parseFloat(PL_PRICES[priceKey]) || 0;
            } else {
                // الصنف غير موجود في القائمة → toast + سعر يدوي
                showToast('هذا الصنف غير موجود في قائمة الأسعار — أدخل السعر يدوياً', 'warn');
            }
        }

        rowTemplate({ item_id: item.id, code: item.code, description: item.name, uom: uomLabel(item.uom), quantity: 1, list_price: listPrice, discount_percent: 0, tax_percent: 14 });
    }

    function addBlankRow() {
        rowTemplate({ quantity: 1, list_price: 0, discount_percent: 0, tax_percent: 14 });
    }

    function qRemove(btn) { btn.closest('tr').remove(); qRenumber(); recalc(); }

    function qRenumber() {
        const rows = document.querySelectorAll('#quoteItemsBody tr');
        rows.forEach((r, idx) => r.querySelector('.row-num').innerText = idx + 1);
    }

    function recalc() {
        let subtotal = 0, lineDisc = 0, tax = 0;
        document.querySelectorAll('#quoteItemsBody tr').forEach(tr => {
            const q = parseFloat(tr.querySelector('.calc-q').value || 0);
            const p = parseFloat(tr.querySelector('.calc-p').value || 0);
            const d = parseFloat(tr.querySelector('.calc-d').value || 0);
            const t = parseFloat(tr.querySelector('.calc-t').value || 0);
            const base = q * p;
            const discVal = base * d / 100;
            const afterDisc = base - discVal;
            const taxVal = afterDisc * t / 100;
            const net = afterDisc + taxVal;
            tr.querySelector('.net-cell').innerText = fmt(net);
            subtotal += base; lineDisc += discVal; tax += taxVal;
        });
        const extra = parseFloat(document.getElementById('extraDiscount').value || 0);
        const grand = subtotal - lineDisc - extra + tax;
        document.getElementById('sumSubtotal').innerText = fmt(subtotal);
        document.getElementById('sumTax').innerText = fmt(tax);
        document.getElementById('sumGrand').innerText = fmt(grand);
    }

    function syncCurrencyLabel() {
        const sel = document.getElementById('currencySelect');
        const val = sel ? sel.value : 'EGP';
        document.querySelectorAll('.quote-cur').forEach(el => el.innerText = val);
    }

    // تحميل أسعار القائمة لما يتغير الاختيار في صفحة التعديل
    function onPriceListChange(plId) {
        if (!plId) {
            PL_PRICES = {}; PL_LOADED = null;
            const badge = document.getElementById('plBadge');
            if (badge) badge.classList.add('hidden');
            return;
        }
        fetchPriceListPrices(plId);
    }

    document.addEventListener('DOMContentLoaded', function () {
        // إعادة تحميل الصفوف القديمة بعد خطأ أو في وضع التعديل
        const preload = @json($jsPreload);
        if (preload && preload.length) {
            preload.forEach(r => rowTemplate({
                item_id: r.item_id, code: r.item_code, description: r.description,
                uom: r.uom, quantity: r.quantity, list_price: r.list_price,
                discount_percent: r.discount_percent, tax_percent: r.tax_percent
            }));
        }
        recalc();
        syncCurrencyLabel();

        // ربط مباشر على العنصر الأصلي — بيشتغل بغض النظر عن توقيت تهيئة Tom Select
        // (التغيير بيوصل هنا سواء جه من Tom Select أو من العنصر نفسه)
        @if(!$isEdit)
        const ccClientEl = document.getElementById('clientIdSelect');
        const ccDateEl   = document.getElementById('quoteDateInput');
        if (ccClientEl) ccClientEl.addEventListener('change', suggestCostCenterName);
        if (ccDateEl)   ccDateEl.addEventListener('change', suggestCostCenterName);
        @endif
        const ccInputEarly = document.getElementById('costCenterNameInput');
        if (ccInputEarly) ccInputEarly.addEventListener('input', function () { costCenterTouched = true; });

        // ننتظر Tom Select يتهيأ أولاً ثم نربط الأحداث
        setTimeout(function () {
            // تبديل العملة
            const curEl = document.getElementById('currencySelect');
            if (curEl) {
                const curTs = curEl.tomselect;
                if (curTs) {
                    curTs.on('change', function () { syncCurrencyLabel(); });
                } else {
                    curEl.addEventListener('change', syncCurrencyLabel);
                }
            }

            // قائمة الأسعار → تحميل الأسعار في الخلفية فور التغيير
            const plEl = document.getElementById('priceListSelect');
            if (plEl && plEl.tomselect) {
                // تحميل فوري لو في قيمة مبدئية
                if (plEl.value) fetchPriceListPrices(plEl.value);
                plEl.tomselect.on('change', function (val) { onPriceListChange(val); });
            }

            // اختيار العميل → تعبئة تلقائية للعملة وقائمة الأسعار والمندوب
            const clientEl = document.querySelector('select[name="client_id"]');
            @if(!$isEdit)
            if (clientEl && clientEl.tomselect) {
                clientEl.tomselect.on('item_add', function (clientId) {
                    if (!clientId) return;
                    suggestCostCenterName();
                    fetch('{{ url('clients') }}/' + clientId + '/defaults', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(function (data) {
                            // قائمة الأسعار
                            if (plEl && plEl.tomselect && data.default_price_list_id) {
                                plEl.tomselect.setValue(String(data.default_price_list_id));
                                fetchPriceListPrices(String(data.default_price_list_id));
                            }
                            // مندوب المبيعات
                            const repEl = document.querySelector('input[name="sales_rep"]');
                            if (repEl && data.default_sales_rep && !repEl.value) {
                                repEl.value = data.default_sales_rep;
                            }
                            // العملة
                            if (curEl && curEl.tomselect && data.default_currency) {
                                curEl.tomselect.setValue(data.default_currency);
                                syncCurrencyLabel();
                            }
                        })
                        .catch(function () {});
                });
            }
            @endif

            // إضافة صنف عبر الـ picker
            const picker = document.getElementById('itemPicker');
            if (picker) {
                const ts = picker.tomselect;
                if (ts) {
                    ts.on('item_add', function (value) {
                        addItemById(value);
                        // إعادة ضبط Tom Select بالكامل عشان الأصناف تظهر من جديد
                        ts.clear(true);
                        ts.setTextboxValue('');
                        ts.lastQuery = null;
                        ts.refreshOptions(false);
                    });
                } else {
                    picker.addEventListener('change', function () {
                        if (this.value) addItemById(this.value);
                        this.value = '';
                    });
                }
            }
        }, 100);

        // منع الإرسال بدون أصناف
        document.getElementById('quoteForm').addEventListener('submit', function (e) {
            if (!document.querySelectorAll('#quoteItemsBody tr').length) {
                e.preventDefault();
                alert(@json(app()->getLocale()==='ar' ? 'أضف صنفاً واحداً على الأقل للعرض' : 'Add at least one item'));
            }
        });
    });
</script>
