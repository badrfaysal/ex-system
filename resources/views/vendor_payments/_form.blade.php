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
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المبلغ المدفوع (من الفاتورة)' : 'Amount Paid (Invoice)' }} <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0.01" max="{{ $balance + (float) ($p?->amount ?? 0) }}" name="amount" id="paymentAmount" value="{{ old('amount', $p?->amount) }}" required dir="ltr"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-red-500 bg-gray-50 focus:bg-white">
                <p class="text-[11px] text-gray-400 mt-1">{{ $isAr ? 'يمكن أن يكون دفع جزئي — مينفعش يتعدى المتبقي' : "Can be a partial payment — can't exceed the balance due" }}</p>
                @error('amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'عملة السداد' : 'Payment Currency' }}</label>
                <input type="text" name="currency" id="paymentCurrency" value="{{ $purchaseInvoice->currency }}" readonly dir="ltr"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-600 cursor-not-allowed">
                <p class="text-[11px] text-gray-400 mt-1">{{ $isAr ? 'مقفولة على عملة فاتورة الشراء' : 'Locked to the purchase invoice currency' }}</p>
                @error('currency') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        <div id="exchangeRateContainer" class="hidden bg-blue-50 border border-blue-100 rounded-xl p-5 animate-fade-in mt-4">
            <div class="flex flex-wrap items-center justify-between mb-4 gap-2">
                <label class="text-sm font-bold text-blue-800">
                    <i class="fas fa-coins mr-1"></i> {{ $isAr ? 'تحديد سعر الصرف' : 'Exchange Rate' }} <span class="text-red-500">*</span>
                </label>
                <button type="button" id="swapRateBtn" class="text-xs px-3 py-1.5 bg-white border border-blue-200 text-blue-700 hover:bg-blue-100 rounded-lg font-bold transition-colors shadow-sm flex items-center gap-1">
                    <i class="fas fa-exchange-alt"></i> {{ $isAr ? 'عكس اتجاه الصرف' : 'Swap Direction' }}
                </button>
            </div>
            
            <div class="flex items-center justify-center gap-2 sm:gap-4 bg-white p-4 rounded-xl border border-blue-200 shadow-inner mb-4">
                <div class="text-center w-1/3">
                    <span class="block text-xl font-black text-gray-800">1</span>
                    <span class="block text-sm font-bold text-gray-500" id="rateBaseCurrency"></span>
                </div>
                
                <div class="text-gray-400 font-bold text-lg">=</div>
                
                <div class="w-1/3">
                    <input type="number" step="0.000001" min="0.000001" id="uiRateInput" dir="ltr"
                        class="w-full px-2 py-2 border-2 border-blue-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-center font-mono text-xl font-bold text-blue-800 shadow-sm transition-all">
                </div>

                <div class="text-center w-1/3">
                    <span class="block text-xl font-black text-transparent select-none">-</span>
                    <span class="block text-sm font-bold text-gray-500" id="rateTargetCurrency"></span>
                </div>
            </div>
            
            <p class="text-xs text-blue-600 font-medium mb-3 text-center" id="rateHelpText"></p>
            
            <div>
                <label class="block text-xs font-semibold text-blue-800 mb-1">
                    <i class="fas fa-wallet mr-1"></i> {{ $isAr ? 'المبلغ الفعلي' : 'Wallet Amount' }}
                </label>
                <input type="text" id="convertedAmount" readonly dir="ltr"
                    class="w-full px-3 py-2 border border-blue-200 rounded-lg bg-blue-100 text-blue-800 cursor-not-allowed font-bold">
            </div>
            
            <input type="hidden" id="exchangeRate" name="exchange_rate" value="{{ old('exchange_rate', $p?->exchange_rate ?? 1) }}">
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
                <i class="fas fa-wallet text-red-400 mr-1"></i> {{ $isAr ? 'الحساب (الخصم منه)' : 'Account (Deduct from)' }} <span class="text-red-500">*</span>
            </label>
            <select name="wallet_id" required data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-red-500">
                <option value="" disabled {{ old('wallet_id', $p?->wallet_id) ? '' : 'selected' }}>{{ $isAr ? '— اختر الحساب —' : '— Choose account —' }}</option>
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

@php
    $walletsJs = $wallets->map(fn ($w) => ['id' => $w->id, 'name' => $w->name, 'currency' => $w->currency]);
@endphp
<script>
    window.addEventListener('load', function () {
        var cur = document.getElementById('paymentCurrency').value;
        var walletSel = document.querySelector('select[name="wallet_id"]');
        var amtInput = document.getElementById('paymentAmount');
        var rateInput = document.getElementById('exchangeRate');
        var convertedOutput = document.getElementById('convertedAmount');
        var rateContainer = document.getElementById('exchangeRateContainer');
        var allWallets = @json($walletsJs);

        var uiRateInput = document.getElementById('uiRateInput');
        var swapRateBtn = document.getElementById('swapRateBtn');
        var rateBaseCurrency = document.getElementById('rateBaseCurrency');
        var rateTargetCurrency = document.getElementById('rateTargetCurrency');
        var rateHelpText = document.getElementById('rateHelpText');
        var rateDirection = 'direct'; 
        
        var curA = '';
        var curB = '';
        var isAr = {{ $isAr ? 'true' : 'false' }};

        var hiddenRateInitial = parseFloat(rateInput.value) || 1;
        if(uiRateInput && !uiRateInput.value) {
            uiRateInput.value = rateDirection === 'direct' ? hiddenRateInitial : (1 / hiddenRateInitial).toFixed(6);
        }

        function updateRateUI() {
            if(rateDirection === 'direct') {
                rateBaseCurrency.innerText = curA;
                rateTargetCurrency.innerText = curB;
                rateHelpText.innerText = isAr ? ('أدخل كم يعادل الـ 1 ' + curA + ' بوحدة الـ ' + curB) : ('Enter how much 1 ' + curA + ' equals in ' + curB);
            } else {
                rateBaseCurrency.innerText = curB;
                rateTargetCurrency.innerText = curA;
                rateHelpText.innerText = isAr ? ('أدخل كم يعادل الـ 1 ' + curB + ' بوحدة الـ ' + curA) : ('Enter how much 1 ' + curB + ' equals in ' + curA);
            }
        }

        if(swapRateBtn) {
            swapRateBtn.addEventListener('click', function() {
                rateDirection = rateDirection === 'direct' ? 'inverse' : 'direct';
                var val = parseFloat(uiRateInput.value) || 0;
                if(val > 0) {
                    uiRateInput.value = (1 / val).toFixed(6);
                }
                updateRateUI();
                calculate();
            });
        }

        if(uiRateInput) {
            uiRateInput.addEventListener('input', function() {
                var val = parseFloat(uiRateInput.value) || 0;
                if(val > 0) {
                    rateInput.value = rateDirection === 'direct' ? val : (1 / val).toFixed(6);
                }
                calculate();
            });
        }

        function calculate() {
            if (!walletSel) return;
            var wId = walletSel.value;
            var wallet = allWallets.find(w => String(w.id) === String(wId));
            var amt = parseFloat(amtInput.value) || 0;

            if (wallet && wallet.currency !== cur) {
                rateContainer.classList.remove('hidden');
                
                curA = cur;
                curB = wallet.currency;
                updateRateUI();

                var hiddenRate = parseFloat(rateInput.value) || 1;
                convertedOutput.value = (amt * hiddenRate).toFixed(2) + ' ' + wallet.currency;
            } else {
                rateContainer.classList.add('hidden');
            }
        }

        if (walletSel) {
            walletSel.addEventListener('change', calculate);
        }
        if (amtInput) amtInput.addEventListener('input', calculate);
        // We removed rateInput listener since it's updated via ui rates
        
        calculate();
    });
</script>
