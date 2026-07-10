@php
    $isEdit = isset($priceList) && $priceList->exists;
    $action = $isEdit ? route('price-lists.update', $priceList) : route('price-lists.store');
    $existingPrices = $existingPrices ?? collect();

    // تجهيز البيانات للـ JS (نتجنب المصفوفات داخل @json مباشرة لتفادي خطأ التحليل)
    $jsItems = $items->map(function ($i) {
        return ['id' => $i->id, 'code' => $i->item_code, 'name' => $i->name_ar, 'uom' => $i->base_uom];
    })->values();

    $jsPreload = array_values(old('items', $isEdit
        ? $priceList->items->map(function ($pli) {
            return ['item_id' => $pli->item_id, 'price' => $pli->price];
        })->toArray()
        : []));
@endphp

<div class="container mx-auto px-4 max-w-7xl animate-fade-in">

    {{-- أخطاء التحقق --}}
    @if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-300 text-red-800 rounded-xl p-4">
        <div class="flex items-center gap-2 font-bold mb-2"><i class="fas fa-exclamation-circle"></i> يرجى مراجعة الأخطاء التالية:</div>
        <ul class="list-disc list-inside space-y-1 text-sm">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ $action }}" method="POST" id="priceListForm">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- شريط العنوان والأزرار --}}
        <div class="bg-[#1e293b] text-white rounded-2xl shadow-sm px-6 py-4 mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="text-sm text-gray-300 flex items-center gap-2">
                <i class="fas fa-tags"></i>
                <span>{{ __('messages.app_name') }} ERP</span>
                <i class="fas fa-angle-left text-xs"></i>
                <span>{{ __('messages.nav.sales_mgmt') }}</span>
                <i class="fas fa-angle-left text-xs"></i>
                <span class="text-white font-bold">{{ $isEdit ? __('messages.price_lists.edit_title') : __('messages.price_lists.add_title') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-5 py-2 bg-[#005B9F] hover:bg-blue-800 rounded-lg font-bold text-sm flex items-center gap-2 shadow">
                    <i class="fas fa-save"></i> {{ __('messages.price_lists.save') }}
                </button>
                <button type="button" onclick="loadAllItems()" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg font-bold text-sm flex items-center gap-2">
                    <i class="fas fa-bolt text-amber-400"></i> {{ __('messages.price_lists.fetch_items') }}
                </button>
                <button type="button" onclick="bulkEditPrices()" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg font-bold text-sm flex items-center gap-2">
                    <i class="fas fa-percent text-green-400"></i> {{ app()->getLocale() === 'ar' ? 'تعديل جماعي %' : 'Bulk Edit %' }}
                </button>
            </div>
        </div>

        {{-- بيانات القائمة --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-gray-100">
                <span class="text-[#005B9F] font-bold text-lg flex items-center gap-2"><i class="fas fa-info-circle"></i> {{ __('messages.price_lists.sub') }}</span>
                <label class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500">{{ __('messages.price_lists.list_status') }}:</span>
                    <select name="status" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm bg-white font-bold">
                        <option value="active" {{ old('status', $isEdit ? $priceList->status : 'active') == 'active' ? 'selected' : '' }}>{{ __('messages.price_lists.st_active') }}</option>
                        <option value="inactive" {{ old('status', $isEdit ? $priceList->status : '') == 'inactive' ? 'selected' : '' }}>{{ __('messages.price_lists.st_inactive') }}</option>
                    </select>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.price_lists.f_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required value="{{ old('name', $isEdit ? $priceList->name : '') }}" placeholder="{{ __('messages.price_lists.f_name_ph') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.price_lists.f_code') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="code" required dir="ltr" value="{{ old('code', $isEdit ? $priceList->code : $nextCode ?? '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-right font-mono bg-gray-50 focus:outline-none focus:border-[#008A3B]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.price_lists.f_currency') }} <span class="text-red-500">*</span></label>
                    <select name="default_currency" required data-search class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                        @foreach($currencies as $c)
                            <option value="{{ $c->key_value }}" {{ old('default_currency', $isEdit ? $priceList->default_currency : 'EGP') == $c->key_value ? 'selected' : '' }}>{{ $c->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.price_lists.f_from') }}</label>
                    <input type="date" name="valid_from" value="{{ old('valid_from', $isEdit ? optional($priceList->valid_from)->format('Y-m-d') : now()->format('Y-m-d')) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.price_lists.f_to') }}</label>
                    <input type="date" name="valid_to" value="{{ old('valid_to', $isEdit ? optional($priceList->valid_to)->format('Y-m-d') : '') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                </div>
            </div>
        </div>

        {{-- جدول الأصناف --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-8 pb-12">            <div class="px-6 py-4 border-b border-gray-100 font-bold text-gray-800 flex items-center gap-2">
                <span class="w-1.5 h-5 bg-[#005B9F] rounded-full"></span>
                {{ __('messages.price_lists.items_table') }}
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse" id="itemsTable">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                            <th class="p-3 w-12 text-center">#</th>
                            <th class="p-3 w-40">{{ __('messages.price_lists.th_code') }}</th>
                            <th class="p-3">{{ __('messages.price_lists.th_name') }}</th>
                            <th class="p-3 w-28 text-center">{{ __('messages.price_lists.th_uom') }}</th>
                            <th class="p-3 w-48">{{ __('messages.price_lists.th_price') }}</th>
                            <th class="p-3 w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody" class="divide-y divide-gray-100 text-sm"></tbody>
                </table>
            </div>

            {{-- صف إضافة يدوية --}}
            <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/60">
                <select id="manualPicker" data-search class="w-full md:w-96 px-4 py-2 border border-gray-300 rounded-lg bg-white text-sm">
                    <option value="">{{ __('messages.price_lists.add_row') }}</option>
                    @foreach($items as $it)
                        <option value="{{ $it->id }}">{{ $it->item_code }} — {{ $it->name_ar }}</option>
                    @endforeach
                </select>
            </div>
            <div id="emptyHint" class="px-6 py-10 text-center text-gray-400 text-sm">
                <i class="fas fa-box-open text-3xl mb-2 opacity-30 block"></i>
                {{ __('messages.price_lists.no_rows') }}
            </div>
        </div>

        {{-- شريط الحفظ السفلي --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-4 mb-12 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-gray-400 flex items-center gap-2">
                <i class="fas fa-info-circle"></i>
                {{ app()->getLocale() === 'ar' ? 'راجع الأسعار جيداً قبل الحفظ' : 'Please review the prices before saving' }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('price-lists.index') }}" class="px-5 py-2.5 border border-gray-300 rounded-lg font-bold text-sm text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                    <i class="fas fa-times"></i> {{ __('messages.common.cancel') }}
                </a>
                <button type="submit" class="px-8 py-2.5 bg-[#005B9F] hover:bg-blue-800 text-white rounded-lg font-bold text-sm flex items-center gap-2 shadow">
                    <i class="fas fa-save"></i> {{ __('messages.price_lists.save') }}
                </button>
            </div>
        </div>
    </form>
</div>

{{-- بيانات الأصناف والأسعار الحالية --}}
<script>
    const ALL_ITEMS = @json($jsItems);
    const UOM_MAP   = @json($uoms);
    const EXISTING  = @json($existingPrices); // {item_id: price}

    let rowIndex = 0;

    function uomLabel(key) {
        if (!key) return '—';
        return UOM_MAP[key] || key;
    }

    function addItemRow(item, price) {
        // تفادي التكرار
        if (document.querySelector(`#itemsBody tr[data-item="${item.id}"]`)) return;

        const i = rowIndex++;
        const tr = document.createElement('tr');
        tr.setAttribute('data-item', item.id);
        tr.className = 'hover:bg-blue-50/30';
        tr.innerHTML = `
            <td class="p-3 text-center text-gray-400 row-num"></td>
            <td class="p-3 font-mono text-[#005B9F] font-bold">${item.code ?? '—'}
                <input type="hidden" name="items[${i}][item_id]" value="${item.id}">
            </td>
            <td class="p-3 font-bold text-gray-800">${item.name ?? ''}</td>
            <td class="p-3 text-center text-gray-500">${uomLabel(item.uom)}</td>
            <td class="p-3">
                <input type="number" step="0.01" min="0" name="items[${i}][price]" value="${price ?? 0}"
                    class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-right font-bold bg-amber-50/40 focus:outline-none focus:border-[#008A3B] price-input">
            </td>
            <td class="p-3 text-center">
                <button type="button" onclick="removeRow(this)" class="text-gray-300 hover:text-red-500" title="@lang('messages.price_lists.remove_row')"><i class="fas fa-times-circle"></i></button>
            </td>`;
        document.getElementById('itemsBody').appendChild(tr);
        renumber();
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        renumber();
    }

    function renumber() {
        const rows = document.querySelectorAll('#itemsBody tr');
        rows.forEach((r, idx) => r.querySelector('.row-num').innerText = idx + 1);
        document.getElementById('emptyHint').style.display = rows.length ? 'none' : 'block';
    }

    function loadAllItems() {
        ALL_ITEMS.forEach(it => addItemRow(it, EXISTING[it.id] ?? 0));
    }

    function bulkEditPrices() {
        const pct = prompt(@json(app()->getLocale() === 'ar' ? 'أدخل نسبة التعديل % (موجبة للزيادة، سالبة للخصم):' : 'Enter % change (positive to raise, negative to discount):'));
        if (pct === null || pct === '') return;
        const factor = 1 + (parseFloat(pct) / 100);
        if (isNaN(factor)) return;
        document.querySelectorAll('#itemsBody .price-input').forEach(inp => {
            inp.value = (parseFloat(inp.value || 0) * factor).toFixed(2);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // تحميل الصفوف الحالية (وضع التعديل) أو القديمة بعد خطأ
        const preload = @json($jsPreload);
        if (preload && preload.length) {
            preload.forEach(row => {
                const item = ALL_ITEMS.find(x => String(x.id) === String(row.item_id));
                if (item) addItemRow(item, row.price);
            });
        }
        renumber();

        // اختيار يدوي - ننتظر Tom Select يتهيأ أولاً ثم نستمع لحدث التغيير
        const picker = document.getElementById('manualPicker');
        // ننتظر Tom Select يتهيأ ثم نستمع لحدث item_add (يطلق فور اختيار الصنف)
        setTimeout(function () {
            const ts = picker.tomselect;
            if (ts) {
                ts.on('item_add', function (value) {
                    const item = ALL_ITEMS.find(x => String(x.id) === String(value));
                    if (item) addItemRow(item, EXISTING[item.id] ?? 0);
                    // إعادة ضبط Tom Select بالكامل عشان الأصناف تظهر من جديد
                    ts.clear(true);
                    ts.setTextboxValue('');
                    ts.lastQuery = null;
                    ts.refreshOptions(false);
                });
            } else {
                picker.addEventListener('change', function () {
                    const item = ALL_ITEMS.find(x => String(x.id) === String(this.value));
                    if (item) addItemRow(item, EXISTING[item.id] ?? 0);
                    this.value = '';
                });
            }
        }, 100);
    });
</script>
