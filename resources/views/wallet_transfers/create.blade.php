@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'تحويل بين الحسابات' : 'Account Transfer')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex justify-between items-center animate-fade-in">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-600">
                <i class="fas fa-exchange-alt text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $isAr ? 'تحويل بين الحسابات' : 'Account Transfer' }}</h2>
        </div>
        <a href="{{ route('wallets.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }} text-sm"></i> {{ $isAr ? 'الحسابات' : 'Accounts' }}
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 animate-fade-in">
        <div class="h-2 w-32 bg-amber-500 rounded-full mb-8"></div>

        <form action="{{ route('wallet-transfers.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'رقم التحويل' : 'Transfer No.' }}</label>
                    <input type="text" value="{{ $isAr ? '— يُولَّد تلقائيًا عند الحفظ —' : '— Generated automatically on save —' }}" disabled dir="ltr"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-400 italic cursor-not-allowed">
                </div>

                <div class="grid grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'من حساب' : 'From Account' }} <span class="text-red-500">*</span></label>
                        <select name="from_wallet_id" id="fromWalletSelect" required data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-amber-500">
                            <option value="" disabled {{ old('from_wallet_id', $selectedFromId) ? '' : 'selected' }}>{{ $isAr ? '— اختر —' : '— Choose —' }}</option>
                            @foreach($wallets as $w)
                                <option value="{{ $w->id }}" data-currency="{{ $w->currency }}" {{ old('from_wallet_id', $selectedFromId) == $w->id ? 'selected' : '' }}>{{ $w->name }} ({{ number_format($w->current_balance, 2) }} {{ $w->currency }})</option>
                            @endforeach
                        </select>
                        @error('from_wallet_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'إلى حساب' : 'To Account' }} <span class="text-red-500">*</span></label>
                        <select name="to_wallet_id" id="toWalletSelect" required data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-amber-500">
                            <option value="" disabled selected>{{ $isAr ? '— اختر —' : '— Choose —' }}</option>
                            @foreach($wallets as $w)
                                <option value="{{ $w->id }}" data-currency="{{ $w->currency }}" {{ old('to_wallet_id') == $w->id ? 'selected' : '' }}>{{ $w->name }} ({{ $w->currency }})</option>
                            @endforeach
                        </select>
                        @error('to_wallet_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المبلغ المخصوم' : 'Amount Deducted' }} <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0.01" id="transferAmount" name="amount" value="{{ old('amount') }}" required dir="ltr"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-amber-500 bg-gray-50 focus:bg-white pr-16">
                            <span class="absolute right-3 top-2.5 text-sm font-bold text-gray-500" id="fromCurrencyLabel"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المبلغ المستلم' : 'Amount Received' }}</label>
                        <div class="relative">
                            <input type="text" id="transferConvertedAmount" readonly dir="ltr"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-600 cursor-not-allowed pr-16">
                            <span class="absolute right-3 top-2.5 text-sm font-bold text-gray-500" id="toCurrencyLabel"></span>
                        </div>
                    </div>
                </div>

                <div id="exchangeRateContainer" class="hidden bg-blue-50 border border-blue-100 rounded-xl p-5 animate-fade-in mt-2">
                    <div class="flex flex-wrap items-center justify-between mb-4 gap-2">
                        <label class="text-sm font-bold text-blue-800">
                            <i class="fas fa-coins mr-1"></i> {{ $isAr ? 'تحديد سعر الصرف' : 'Exchange Rate' }} <span class="text-red-500">*</span>
                        </label>
                        <button type="button" id="swapRateBtn" class="text-xs px-3 py-1.5 bg-white border border-blue-200 text-blue-700 hover:bg-blue-100 rounded-lg font-bold transition-colors shadow-sm flex items-center gap-1">
                            <i class="fas fa-exchange-alt"></i> {{ $isAr ? 'عكس اتجاه الصرف' : 'Swap Direction' }}
                        </button>
                    </div>
                    
                    <div class="flex items-center justify-center gap-2 sm:gap-4 bg-white p-4 rounded-xl border border-blue-200 shadow-inner">
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
                    
                    <p class="text-xs text-blue-600 font-medium mt-3 text-center" id="rateHelpText"></p>
                    <input type="hidden" id="transferExchangeRate" name="exchange_rate" value="{{ old('exchange_rate', 1) }}">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'تاريخ التحويل' : 'Transfer Date' }} <span class="text-red-500">*</span></label>
                    <input type="date" name="transfer_date" value="{{ old('transfer_date', now()->toDateString()) }}" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-amber-500 bg-gray-50 focus:bg-white">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'ملاحظات' : 'Notes' }}</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-amber-500 bg-gray-50 focus:bg-white">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-10 flex justify-end gap-4 border-t border-gray-100 pt-8">
                <a href="{{ route('wallets.index') }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium transition-colors">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </a>
                <button type="submit" class="px-8 py-2.5 bg-amber-500 rounded-lg text-white hover:bg-amber-600 font-bold shadow-lg flex items-center gap-2">
                    <i class="fas fa-save"></i> {{ $isAr ? 'تنفيذ التحويل' : 'Execute Transfer' }}
                </button>
            </div>
        </form>
    </div>
</div>

@php
    // بيتحسب هنا في متغيّر عادي بدل ما يتحط جوه @json() مباشرة — تجنّبًا لباج معروف
    // في Blade بيقطع الكود لما @json() يحتوي على closure متداخل بعدد أقواس/مفاتيح معيّن
    $walletsJs = $wallets->map(fn ($w) => [
        'id' => $w->id, 'name' => $w->name, 'currency' => $w->currency, 'balance' => $w->current_balance,
    ]);
@endphp
<script>
    window.addEventListener('load', function () {
        var fromSel = document.getElementById('fromWalletSelect');
        var toSel = document.getElementById('toWalletSelect');
        var amountInput = document.getElementById('transferAmount');
        var rateInput = document.getElementById('transferExchangeRate');
        var rateContainer = document.getElementById('exchangeRateContainer');
        var convertedOutput = document.getElementById('transferConvertedAmount');
        var fromLabel = document.getElementById('fromCurrencyLabel');
        var toLabel = document.getElementById('toCurrencyLabel');
        
        if (!fromSel || !toSel) return;

        var allWallets = @json($walletsJs);
        var oldToId = {{ old('to_wallet_id') ? (int) old('to_wallet_id') : 'null' }};

        function getWalletData(id) {
            return allWallets.find(w => String(w.id) === String(id));
        }

        var uiRateInput = document.getElementById('uiRateInput');
        var rateInput = document.getElementById('transferExchangeRate');
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
            var fromWallet = getWalletData(fromSel.value);
            var toWallet = getWalletData(toSel.value);
            var amt = parseFloat(amountInput.value) || 0;
            
            if (!fromWallet || !toWallet) {
                convertedOutput.value = '';
                fromLabel.innerText = '';
                toLabel.innerText = '';
                return;
            }

            fromLabel.innerText = fromWallet.currency;
            toLabel.innerText = toWallet.currency;

            if (fromWallet.currency !== toWallet.currency) {
                rateContainer.classList.remove('hidden');
                curA = fromWallet.currency;
                curB = toWallet.currency;
                updateRateUI();
                
                var hiddenRate = parseFloat(rateInput.value) || 1;
                convertedOutput.value = (amt * hiddenRate).toFixed(2);
            } else {
                rateContainer.classList.add('hidden');
                convertedOutput.value = amt.toFixed(2);
            }
        }

        function filterToOptions(preserveValue) {
            var ts = toSel.tomselect;
            if (!ts) return;

            var fromId = fromSel.value;
            // تسمح بأي عملة، فقط تمنع اختيار نفس المحفظة
            var matching = allWallets.filter(function (w) { return String(w.id) !== String(fromId); });
            var valueToRestore = preserveValue !== undefined ? preserveValue : ts.getValue();

            ts.clearOptions();
            matching.forEach(function (w) {
                ts.addOption({ value: String(w.id), text: w.name + ' (' + w.currency + ')' });
            });
            ts.refreshOptions(false);

            var stillValid = matching.some(function (w) { return String(w.id) === String(valueToRestore); });
            if (stillValid && valueToRestore !== null && valueToRestore !== '') {
                ts.setValue(String(valueToRestore), true);
            } else {
                ts.clear(true);
            }
            calculate();
        }

        filterToOptions(oldToId);

        fromSel.addEventListener('change', function () {
            filterToOptions();
        });
        toSel.addEventListener('change', calculate);
        amountInput.addEventListener('input', calculate);
        // We removed rateInput event listener because it's driven by uiRateDirect and uiRateInverse
    });
</script>
@endsection
