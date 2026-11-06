@php
    $isAr = app()->getLocale() === 'ar';
    $e = $expense ?? null;
    $isEdit = $e && $e->exists;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'رقم المصروف' : 'Expense No.' }}</label>
        @if($isEdit)
            <input type="text" value="{{ $e->expense_number }}" disabled dir="ltr"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-500 cursor-not-allowed">
        @else
            <input type="text" value="{{ $isAr ? '— يُولَّد تلقائيًا عند الحفظ —' : '— Generated automatically on save —' }}" disabled dir="ltr"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-400 italic cursor-not-allowed">
        @endif
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'تاريخ المصروف' : 'Expense Date' }} <span class="text-red-500">*</span></label>
        <input type="date" name="expense_date" value="{{ old('expense_date', optional($e?->expense_date)->toDateString()) }}" required dir="ltr"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] bg-gray-50 focus:bg-white">
        @error('expense_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
            <i class="fas fa-layer-group text-[#005B9F] mr-1"></i> {{ $isAr ? 'مركز التكلفة (عرض السعر)' : 'Cost Center (Quotation)' }} <span class="text-red-500">*</span>
        </label>
        <select name="quotation_id" required data-search
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
            <option value="" disabled {{ old('quotation_id', $e?->quotation_id) ? '' : 'selected' }}>{{ $isAr ? '— اختر عرض السعر —' : '— Choose quotation —' }}</option>
            @foreach($quotations as $q)
                <option value="{{ $q->id }}" {{ old('quotation_id', $e?->quotation_id) == $q->id ? 'selected' : '' }}>{{ $q->quote_number }}@if($q->cost_center_name) — {{ $q->cost_center_name }}@endif</option>
            @endforeach
        </select>
        @error('quotation_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    {{-- الفئة الرئيسية (الحساب الأب) --}}
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
            <i class="fas fa-sitemap text-[#005B9F] mr-1"></i> {{ $isAr ? 'الفئة الرئيسية / الحساب الأب' : 'Parent Account / Category' }}
        </label>
        <select id="expenseParentFilter"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-50 focus:bg-white focus:outline-none focus:border-[#008A3B] text-sm">
            <option value="">{{ $isAr ? '— جميع الفئات والحسابات —' : '— All Categories —' }}</option>
            @foreach($expenseParents ?? [] as $p)
                <option value="{{ $p->key_value }}">{{ $p->account_code }} — {{ $p->localized_name }}</option>
            @endforeach
        </select>
        <p class="text-[11px] text-gray-400 mt-1"><i class="fas fa-filter text-gray-300"></i> {{ $isAr ? 'اختر الفئة لتصفية بنود المصروفات التابعة لها' : 'Filter expense items by parent category' }}</p>
    </div>

    {{-- بند المصروف (الحساب الفرعي) --}}
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
            <i class="fas fa-file-invoice-dollar text-[#008A3B] mr-1"></i> {{ $isAr ? 'بند المصروف (الحساب الفرعي)' : 'Expense Item' }} <span class="text-red-500">*</span>
        </label>
        <select name="category" id="expenseCategorySelect" required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B] text-sm">
            <option value="" disabled {{ old('category', $e?->category) ? '' : 'selected' }}>{{ $isAr ? '— اختر بند المصروف —' : '— Choose expense item —' }}</option>
            @php
                $groupedCats = $categories->groupBy(fn($item) => $item->parent_key ?: 'other');
            @endphp
            @foreach($groupedCats as $parentKey => $items)
                @php
                    $pModel = ($expenseParents ?? collect())->get($parentKey)
                        ?: ($allExpenseAccounts ?? collect())->firstWhere('key_value', $parentKey)
                        ?: ($allExpenseAccounts ?? collect())->firstWhere('code', $parentKey);
                    $grpLabel = $pModel ? ($pModel->account_code . ' — ' . $pModel->localized_name) : ($isAr ? 'بنود عامة أخرى' : 'Other General Items');
                @endphp
                <optgroup label="{{ $grpLabel }}" data-parent-key="{{ $parentKey }}">
                    @foreach($items as $c)
                        <option value="{{ $c->key_value }}"
                            data-parent="{{ $c->parent_key }}"
                            data-code="{{ $c->account_code }}"
                            data-classification="{{ $c->classification }}"
                            {{ old('category', $e?->category) == $c->key_value ? 'selected' : '' }}>
                            {{ $c->account_code ? "[{$c->account_code}] " : '' }}{{ $c->localized_name }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <div id="categoryMetaDisplay" class="text-xs text-gray-500 mt-1 min-h-[16px]"></div>
        @error('category') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    {{-- الحساب (الخصم منه) — moved up --}}
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
            <i class="fas fa-wallet text-[#005B9F] mr-1"></i> {{ $isAr ? 'الحساب (الخصم منه)' : 'Account (Deduct from)' }} <span class="text-red-500">*</span>
        </label>
        <select name="wallet_id" id="walletSelect" required data-search
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
            <option value="" disabled {{ old('wallet_id', $e?->wallet_id) ? '' : 'selected' }}>{{ $isAr ? '— اختر الحساب —' : '— Choose account —' }}</option>
            @foreach($wallets as $w)
                <option value="{{ $w->id }}" data-currency="{{ $w->currency }}" {{ old('wallet_id', $e?->wallet_id) == $w->id ? 'selected' : '' }}>{{ $w->name }} ({{ $w->currency }})</option>
            @endforeach
        </select>
        @error('wallet_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'الوصف' : 'Description' }}</label>
        <input type="text" name="description" value="{{ old('description', $e?->description) }}"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] bg-gray-50 focus:bg-white">
        @error('description') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المبلغ' : 'Amount' }} <span class="text-red-500">*</span></label>
        <input type="number" step="0.01" min="0.01" name="amount" id="nativeAmount" value="{{ old('amount', $e?->amount) }}" required dir="ltr"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] bg-gray-50 focus:bg-white transition-all">
        @error('amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'العملة' : 'Currency' }} <span class="text-red-500">*</span></label>
        <input type="text" id="currencyDisplay" readonly
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 font-bold cursor-not-allowed"
            value="{{ old('currency', $e?->currency ?? 'EGP') }}">
        <input type="hidden" name="currency" id="nativeCurrency" value="{{ old('currency', $e?->currency ?? 'EGP') }}">
        <p class="text-[10px] text-gray-400 mt-1"><i class="fas fa-info-circle"></i> {{ $isAr ? 'العملة تتحدد تلقائياً حسب الحساب المختار' : 'Currency is set automatically based on selected account' }}</p>
        @error('currency') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div id="exchangeRateContainer" style="display: {{ old('currency', $e?->currency) === 'EGP' || !old('currency', $e?->currency) ? 'none' : 'block' }};">
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'سعر الصرف (إلى الجنيه EGP)' : 'Exchange Rate (to EGP)' }}</label>
        <input type="number" step="0.000001" min="0.000001" name="exchange_rate" id="exchangeRate" value="{{ old('exchange_rate', $e?->exchange_rate ?? 1) }}" dir="ltr"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 bg-amber-50 focus:bg-white">
        <p class="text-[10px] text-amber-600 mt-1"><i class="fas fa-exclamation-circle"></i> {{ $isAr ? 'مطلوب لحساب مركز التكلفة بالعملة الأساسية' : 'Required to calculate cost center in base currency' }}</p>
        @error('exchange_rate') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'ملاحظات' : 'Notes' }}</label>
        <textarea name="notes" rows="3"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] bg-gray-50 focus:bg-white">{{ old('notes', $e?->notes) }}</textarea>
        @error('notes') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

</div>

<script>
window.addEventListener('load', function() {
    var walletSel = document.getElementById('walletSelect');
    var currencyDisplay = document.getElementById('currencyDisplay');
    var currencyHidden  = document.getElementById('nativeCurrency');

    function updateCurrencyFromWallet() {
        if (!walletSel) return;
        var opt = walletSel.options[walletSel.selectedIndex];
        if (opt && opt.getAttribute('data-currency')) {
            var cur = opt.getAttribute('data-currency');
            currencyHidden.value = cur;
            currencyDisplay.value = cur;
            var exContainer = document.getElementById('exchangeRateContainer');
            if (cur !== 'EGP') {
                exContainer.style.display = 'block';
            } else {
                exContainer.style.display = 'none';
                document.getElementById('exchangeRate').value = '1';
            }
        }
    }

    if (walletSel) {
        walletSel.addEventListener('change', updateCurrencyFromWallet);
        // Set on load if wallet is pre-selected
        if (walletSel.value) updateCurrencyFromWallet();
    }

    // ===== مزامنة وتصفية الفئة الرئيسية مع بند المصروف =====
    var parentSel = document.getElementById('expenseParentFilter');
    var catSel    = document.getElementById('expenseCategorySelect');
    var metaDisp  = document.getElementById('categoryMetaDisplay');

    function updateCategoryMeta() {
        if (!catSel) return;
        var opt = catSel.options[catSel.selectedIndex];
        if (opt && opt.value) {
            var code = opt.getAttribute('data-code') || '';
            var pKey = opt.getAttribute('data-parent') || '';
            var cls  = opt.getAttribute('data-classification') || '';
            var pOpt = parentSel ? parentSel.querySelector('option[value="' + pKey + '"]') : null;
            var pName = pOpt ? pOpt.textContent : '';

            var parts = [];
            if (code) parts.push('<span class="font-mono font-bold text-[#005B9F]">#' + code + '</span>');
            if (pName) parts.push('<span class="text-gray-500">الفئة: <strong>' + pName + '</strong></span>');
            if (cls) parts.push('<span class="text-gray-400">(' + cls + ')</span>');

            if (metaDisp) {
                metaDisp.innerHTML = parts.length ? '<i class="fas fa-info-circle text-[#005B9F] text-xs"></i> ' + parts.join(' | ') : '';
            }

            if (parentSel && pKey && parentSel.value !== pKey) {
                parentSel.value = pKey;
            }
        } else {
            if (metaDisp) metaDisp.innerHTML = '';
        }
    }

    function filterCategoriesByParent() {
        if (!parentSel || !catSel) return;
        var selectedParent = parentSel.value;
        var optgroups = catSel.querySelectorAll('optgroup');

        optgroups.forEach(function(grp) {
            var grpKey = grp.getAttribute('data-parent-key');
            if (!selectedParent || grpKey === selectedParent) {
                grp.style.display = '';
                grp.querySelectorAll('option').forEach(function(o) { o.hidden = false; });
            } else {
                grp.style.display = 'none';
                grp.querySelectorAll('option').forEach(function(o) { o.hidden = true; });
            }
        });

        // إذا أصبح الاختيار الحالي مخفياً بعد التصفية، أعده للافتراضي
        var currentOpt = catSel.options[catSel.selectedIndex];
        if (currentOpt && currentOpt.value && selectedParent && currentOpt.getAttribute('data-parent') !== selectedParent) {
            catSel.value = '';
            updateCategoryMeta();
        }
    }

    if (parentSel) {
        parentSel.addEventListener('change', filterCategoriesByParent);
    }

    if (catSel) {
        catSel.addEventListener('change', updateCategoryMeta);
        updateCategoryMeta();

        // عند التحميل، لو كان هناك بند مختار مسبقاً (تعديل أو validation error)
        var initOpt = catSel.options[catSel.selectedIndex];
        if (initOpt && initOpt.value) {
            var initP = initOpt.getAttribute('data-parent');
            if (initP && parentSel) {
                parentSel.value = initP;
                filterCategoriesByParent();
                catSel.value = initOpt.value;
            }
        }
    }
});
</script>
