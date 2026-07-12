@php
    $isAr = app()->getLocale() === 'ar';
    $w = $wallet ?? null;
    $isEdit = $w && $w->exists;
    $action = $isEdit ? route('wallets.update', $w) : route('wallets.store');
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'اسم المحفظة' : 'Wallet Name' }} <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $w?->name) }}" required
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] bg-gray-50 focus:bg-white">
            @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'النوع' : 'Type' }}</label>
            <select name="type" data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#005B9F]">
                <option value="">{{ $isAr ? '— غير محدد —' : '— Not set —' }}</option>
                @foreach($types as $t)
                    <option value="{{ $t->key_value }}" {{ old('type', $w?->type) == $t->key_value ? 'selected' : '' }}>{{ $t->display_name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'العملة' : 'Currency' }} <span class="text-red-500">*</span></label>
            <select name="currency" required data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#005B9F]">
                @foreach($currencies as $c)
                    <option value="{{ $c->key_value }}" {{ old('currency', $w?->currency ?? 'EGP') == $c->key_value ? 'selected' : '' }}>{{ $c->key_value }} — {{ $c->display_name }}</option>
                @endforeach
            </select>
            <p class="text-[11px] text-amber-600 mt-1.5 flex items-start gap-1.5">
                <i class="fas fa-circle-info mt-0.5"></i>
                <span>{{ $isAr
                    ? 'المحفظة تعمل بعملة واحدة فقط. لن تُقبل أي حركة (قبض / دفع / مصروف / إيراد / تحويل) على هذه المحفظة إلا بهذه العملة.'
                    : 'This wallet operates in a single currency. No transaction (receipt / payment / expense / revenue / transfer) will be accepted on this wallet in any other currency.' }}</span>
            </p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'رصيد بداية المدة' : 'Opening Balance' }} <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', $w?->opening_balance ?? 0) }}" required dir="ltr"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-gray-50 focus:bg-white">
            @error('opening_balance') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'ملاحظات' : 'Notes' }}</label>
            <textarea name="notes" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-gray-50 focus:bg-white">{{ old('notes', $w?->notes) }}</textarea>
        </div>
    </div>

    <div class="mt-10 flex justify-end gap-4 border-t border-gray-100 pt-8">
        <a href="{{ route('wallets.index') }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium transition-colors">
            {{ $isAr ? 'إلغاء' : 'Cancel' }}
        </a>
        <button type="submit" class="px-8 py-2.5 bg-[#005B9F] rounded-lg text-white hover:bg-blue-800 font-bold shadow-lg flex items-center gap-2">
            <i class="fas fa-save"></i> {{ $isEdit ? ($isAr ? 'حفظ التعديلات' : 'Save Changes') : ($isAr ? 'حفظ المحفظة' : 'Save Wallet') }}
        </button>
    </div>
</form>
