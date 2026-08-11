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

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'نوع المصروف' : 'Category' }} <span class="text-red-500">*</span></label>
        <select name="category" required data-search
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
            <option value="" disabled {{ old('category', $e?->category) ? '' : 'selected' }}>{{ $isAr ? '— اختر النوع —' : '— Choose category —' }}</option>
            @foreach($categories as $c)
                <option value="{{ $c->key_value }}" {{ old('category', $e?->category) == $c->key_value ? 'selected' : '' }}>{{ $c->display_name }}</option>
            @endforeach
        </select>
        @error('category') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'الوصف' : 'Description' }}</label>
        <input type="text" name="description" value="{{ old('description', $e?->description) }}"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] bg-gray-50 focus:bg-white">
        @error('description') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المبلغ الأساسي (نفس عملة الحساب)' : 'Native Amount' }} <span class="text-red-500">*</span></label>
        <input type="number" step="0.01" min="0.01" name="amount" id="nativeAmount" value="{{ old('amount', $e?->amount) }}" required dir="ltr"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] bg-gray-50 focus:bg-white transition-all">
        @error('amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'العملة الأساسية (يجب أن تطابق الحساب)' : 'Native Currency' }} <span class="text-red-500">*</span></label>
        <select name="currency" id="nativeCurrency" required data-search
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed">
            @foreach($currencies as $c)
                <option value="{{ $c->key_value }}" {{ old('currency', $e?->currency ?? 'EGP') == $c->key_value ? 'selected' : '' }}>{{ $c->key_value }} — {{ $c->display_name }}</option>
            @endforeach
        </select>
        @error('currency') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div class="md:col-span-2 bg-blue-50 p-5 rounded-xl border border-blue-100 mt-2">
        <h4 class="text-blue-800 font-bold mb-4 flex items-center gap-2">
            <i class="fas fa-globe"></i> {{ $isAr ? 'الدفع بعملة أجنبية (اختياري)' : 'Foreign Currency Payment (Optional)' }}
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">{{ $isAr ? 'المبلغ الأجنبي' : 'Foreign Amount' }}</label>
                <input type="number" step="0.01" min="0.01" name="foreign_amount" id="foreignAmount" value="{{ old('foreign_amount', $e?->foreign_amount) }}" dir="ltr"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500 bg-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">{{ $isAr ? 'العملة الأجنبية' : 'Foreign Currency' }}</label>
                <select name="foreign_currency" id="foreignCurrency" data-search class="w-full px-3 py-2 border border-gray-300 rounded bg-white focus:outline-none focus:border-blue-500">
                    <option value="">{{ $isAr ? '— اختر —' : '-- Choose --' }}</option>
                    @foreach($currencies as $c)
                        <option value="{{ $c->key_value }}" {{ old('foreign_currency', $e?->foreign_currency) == $c->key_value ? 'selected' : '' }}>{{ $c->key_value }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">{{ $isAr ? 'سعر الصرف' : 'Exchange Rate' }}</label>
                <input type="number" step="0.000001" min="0.000001" name="exchange_rate" id="exchangeRate" value="{{ old('exchange_rate', $e?->exchange_rate ?? 1) }}" dir="ltr"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500 bg-white">
            </div>
        </div>
        <p class="text-[11px] text-gray-500 mt-2"><i class="fas fa-info-circle"></i> {{ $isAr ? 'عند إدخال مبلغ أجنبي، سيتم حساب المبلغ الأساسي تلقائياً وإغلاقه لتفادي التعديل اليدوي.' : 'When foreign amount is provided, native amount is calculated automatically and locked.' }}</p>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
            <i class="fas fa-wallet text-[#005B9F] mr-1"></i> {{ $isAr ? 'الحساب (الخصم منه)' : 'Account (Deduct from)' }} <span class="text-red-500">*</span>
        </label>
        <select name="wallet_id" required data-search
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
            <option value="" disabled {{ old('wallet_id', $e?->wallet_id) ? '' : 'selected' }}>{{ $isAr ? '— اختر الحساب —' : '— Choose account —' }}</option>
            @foreach($wallets as $w)
                <option value="{{ $w->id }}" data-currency="{{ $w->currency }}" {{ old('wallet_id', $e?->wallet_id) == $w->id ? 'selected' : '' }}>{{ $w->name }} ({{ $w->currency }})</option>
            @endforeach
        </select>
        @error('wallet_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
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
    var walletSel = document.querySelector('select[name="wallet_id"]');
    var nativeCurrencySel = document.getElementById('nativeCurrency');
    
    var fAmount = document.getElementById('foreignAmount');
    var fRate = document.getElementById('exchangeRate');
    var nAmount = document.getElementById('nativeAmount');

    // Auto update native currency from wallet selection
    function updateWalletCurrency() {
        if (!walletSel || !nativeCurrencySel) return;
        var opt = walletSel.options[walletSel.selectedIndex];
        if (opt && opt.getAttribute('data-currency')) {
            var ts = nativeCurrencySel.tomselect;
            var cur = opt.getAttribute('data-currency');
            if (ts) ts.setValue(cur);
            else nativeCurrencySel.value = cur;
        }
    }

    if (walletSel) {
        walletSel.addEventListener('change', updateWalletCurrency);
    }

    function calcNative() {
        var fa = parseFloat(fAmount.value);
        if (!isNaN(fa) && fa > 0) {
            var r = parseFloat(fRate.value) || 1;
            nAmount.value = (fa * r).toFixed(2);
            nAmount.readOnly = true;
            nAmount.classList.add('bg-gray-100', 'cursor-not-allowed');
        } else {
            nAmount.readOnly = false;
            nAmount.classList.remove('bg-gray-100', 'cursor-not-allowed');
        }
    }

    if (fAmount && fRate) {
        fAmount.addEventListener('input', calcNative);
        fRate.addEventListener('input', calcNative);
        calcNative();
    }
});
</script>
