@php
    $isAr = app()->getLocale() === 'ar';
    $p = $payment ?? null;
    $isEdit = $p && $p->exists;
    $action = $isEdit ? route('vendor-payments.update', $p) : route('vendor-payments.store');
@endphp

<form action="{{ $action }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="grid grid-cols-1 gap-6">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'رقم السند' : 'Payment No.' }}</label>
            @if($isEdit)
                <input type="text" value="{{ $p->payment_number }}" disabled dir="ltr"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-500 cursor-not-allowed">
            @else
                <input type="text" value="{{ $isAr ? '— يُولَّد تلقائيًا عند الحفظ —' : '— Generated automatically on save —' }}" disabled dir="ltr"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-400 italic cursor-not-allowed">
            @endif
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المورد' : 'Vendor' }} <span class="text-red-500">*</span></label>
            @if($selectedVendorId && !$isEdit)
                {{-- المورد مقفول — جاي من صفحة المستحقات --}}
                <input type="hidden" name="vendor_id" value="{{ $selectedVendorId }}">
                <select disabled class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed">
                    @foreach($vendors as $v)
                        @if($v->id == $selectedVendorId)
                            <option selected>{{ $isAr ? $v->name_ar : ($v->name_en ?: $v->name_ar) }}</option>
                        @endif
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1"><i class="fas fa-lock text-gray-300"></i> {{ $isAr ? 'المورد مقفول — تم اختياره من صفحة المستحقات' : 'Vendor locked — selected from payables page' }}</p>
            @else
                <select name="vendor_id" required data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-red-500">
                    <option value="" disabled {{ old('vendor_id', $selectedVendorId) ? '' : 'selected' }}>{{ $isAr ? '— اختر مورد —' : '— Choose vendor —' }}</option>
                    @foreach($vendors as $v)
                        <option value="{{ $v->id }}" {{ old('vendor_id', $selectedVendorId) == $v->id ? 'selected' : '' }}>{{ $isAr ? $v->name_ar : ($v->name_en ?: $v->name_ar) }}</option>
                    @endforeach
                </select>
            @endif
            @error('vendor_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المبلغ' : 'Amount' }} <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $p?->amount) }}" required dir="ltr"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-red-500 bg-gray-50 focus:bg-white">
                @error('amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'العملة' : 'Currency' }} <span class="text-red-500">*</span></label>
                <select name="currency" required data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-red-500">
                    @foreach($currencies as $c)
                        <option value="{{ $c->key_value }}" {{ old('currency', $p?->currency ?? 'EGP') == $c->key_value ? 'selected' : '' }}>{{ $c->key_value }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'تاريخ الدفع' : 'Payment Date' }} <span class="text-red-500">*</span></label>
                <input type="date" name="payment_date" value="{{ old('payment_date', optional($p?->payment_date)->toDateString() ?? now()->toDateString()) }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-red-500 bg-gray-50 focus:bg-white">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'طريقة الدفع' : 'Payment Method' }}</label>
                <select name="payment_method" data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-red-500">
                    <option value="">{{ $isAr ? '— غير محدد —' : '— Not set —' }}</option>
                    @foreach($paymentMethods as $m)
                        <option value="{{ $m->key_value }}" {{ old('payment_method', $p?->payment_method) == $m->key_value ? 'selected' : '' }}>{{ $m->display_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                <i class="fas fa-wallet text-red-400 mr-1"></i> {{ $isAr ? 'المحفظة (الخصم منها)' : 'Wallet (Deduct from)' }} <span class="text-red-500">*</span>
            </label>
            <select name="wallet_id" required data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-red-500">
                <option value="" disabled {{ old('wallet_id', $p?->wallet_id) ? '' : 'selected' }}>{{ $isAr ? '— اختر المحفظة —' : '— Choose wallet —' }}</option>
                @foreach($wallets as $w)
                    <option value="{{ $w->id }}" data-currency="{{ $w->currency }}" {{ old('wallet_id', $p?->wallet_id) == $w->id ? 'selected' : '' }}>{{ $w->name }} ({{ $w->currency }})</option>
                @endforeach
            </select>
            @error('wallet_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'ملاحظات' : 'Notes' }}</label>
            <textarea name="notes" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-red-500 bg-gray-50 focus:bg-white">{{ old('notes', $p?->notes) }}</textarea>
        </div>
    </div>

    <div class="mt-10 flex justify-end gap-4 border-t border-gray-100 pt-8">
        <a href="{{ route('payables.index') }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium transition-colors">
            {{ $isAr ? 'إلغاء' : 'Cancel' }}
        </a>
        <button type="submit" class="px-8 py-2.5 bg-red-600 rounded-lg text-white hover:bg-red-700 font-bold shadow-lg flex items-center gap-2">
            <i class="fas fa-save"></i> {{ $isEdit ? ($isAr ? 'حفظ التعديلات' : 'Save Changes') : ($isAr ? 'حفظ سند الدفع' : 'Save Payment') }}
        </button>
    </div>
</form>
