@php
    $isAr = app()->getLocale() === 'ar';
    $p = $payment ?? null;
    $isEdit = $p && $p->exists;
    $action = $isEdit ? route('vendor-payments.update', $p) : route('vendor-payments.store');
    
    $vendorDisplay = $isAr ? optional($purchaseInvoice->vendor)->name_ar : (optional($purchaseInvoice->vendor)->name_en ?: optional($purchaseInvoice->vendor)->name_ar);
    $balance = $purchaseInvoice->balance_due;
@endphp

<div class="mb-6 p-4 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-between">
    <div>
        <p class="text-xs text-gray-400">{{ $isAr ? 'فاتورة الشراء' : 'Purchase Invoice' }}</p>
        <p class="font-mono font-bold text-gray-800">{{ $purchaseInvoice->invoice_number }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $vendorDisplay ?? '—' }}</p>
    </div>
    <div class="text-{{ $isAr ? 'left' : 'right' }}">
        <p class="text-xs text-gray-400">{{ $isAr ? 'المتبقي' : 'Balance Due' }}</p>
        <p class="font-extrabold text-lg {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}" dir="ltr">{{ number_format($balance, 2) }} {{ $purchaseInvoice->currency }}</p>
    </div>
</div>

<form action="{{ $action }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif
    
    <input type="hidden" name="purchase_invoice_id" value="{{ $purchaseInvoice->id }}">

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

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المبلغ المدفوع' : 'Amount Paid' }} <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $p?->amount) }}" required dir="ltr"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-red-500 bg-gray-50 focus:bg-white">
                <p class="text-[11px] text-gray-400 mt-1">{{ $isAr ? 'يمكن أن يكون دفع جزئي' : 'Can be a partial payment' }}</p>
                @error('amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'العملة' : 'Currency' }}</label>
                <input type="text" name="currency" id="paymentCurrency" value="{{ $purchaseInvoice->currency }}" readonly dir="ltr"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-600 cursor-not-allowed">
                <p class="text-[11px] text-gray-400 mt-1">{{ $isAr ? 'مقفولة على عملة فاتورة الشراء ولا يمكن تغييرها' : 'Locked to the purchase invoice currency and cannot be changed' }}</p>
                @error('currency') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
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
        <a href="{{ route('payables.show', $purchaseInvoice->vendor_id) }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium transition-colors">
            {{ $isAr ? 'إلغاء' : 'Cancel' }}
        </a>
        <button type="submit" class="px-8 py-2.5 bg-red-600 rounded-lg text-white hover:bg-red-700 font-bold shadow-lg flex items-center gap-2">
            <i class="fas fa-save"></i> {{ $isEdit ? ($isAr ? 'حفظ التعديلات' : 'Save Changes') : ($isAr ? 'حفظ سند الدفع' : 'Save Payment') }}
        </button>
    </div>
</form>

<script>
    window.addEventListener('load', function () {
        var cur = document.getElementById('paymentCurrency').value;
        var walletSel = document.querySelector('select[name="wallet_id"]');
        var ts = walletSel ? walletSel.tomselect : null;
        if (!ts) return;

        var allWallets = @json($wallets->map(fn ($w) => ['id' => $w->id, 'name' => $w->name, 'currency' => $w->currency]));
        var oldWalletId = {{ old('wallet_id', $p?->wallet_id) ? (int) old('wallet_id', $p?->wallet_id) : 'null' }};
        var matching = allWallets.filter(function (w) { return w.currency === cur; });

        ts.clearOptions();
        matching.forEach(function (w) {
            ts.addOption({ value: String(w.id), text: w.name + ' (' + w.currency + ')' });
        });
        ts.refreshOptions(false);

        if (oldWalletId !== null && matching.some(function (w) { return String(w.id) === String(oldWalletId); })) {
            ts.setValue(String(oldWalletId), true);
        }
    });
</script>
