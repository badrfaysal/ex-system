@php
    $isAr = app()->getLocale() === 'ar';
    $e = $expense ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'رقم المصروف' : 'Expense No.' }} <span class="text-red-500">*</span></label>
        <input type="text" name="expense_number" value="{{ old('expense_number', $e?->expense_number) }}" required dir="ltr"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] bg-gray-50 focus:bg-white">
        @error('expense_number') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
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
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المبلغ' : 'Amount' }} <span class="text-red-500">*</span></label>
        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $e?->amount) }}" required dir="ltr"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] bg-gray-50 focus:bg-white">
        @error('amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'العملة' : 'Currency' }} <span class="text-red-500">*</span></label>
        <select name="currency" required data-search
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
            @foreach($currencies as $c)
                <option value="{{ $c->key_value }}" {{ old('currency', $e?->currency ?? 'EGP') == $c->key_value ? 'selected' : '' }}>{{ $c->key_value }} — {{ $c->display_name }}</option>
            @endforeach
        </select>
        @error('currency') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
            <i class="fas fa-wallet text-[#005B9F] mr-1"></i> {{ $isAr ? 'المحفظة (الخصم منها)' : 'Wallet (Deduct from)' }} <span class="text-red-500">*</span>
        </label>
        <select name="wallet_id" required data-search
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
            <option value="" disabled {{ old('wallet_id', $e?->wallet_id) ? '' : 'selected' }}>{{ $isAr ? '— اختر المحفظة —' : '— Choose wallet —' }}</option>
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
