@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $clientDisplay = optional($salesInvoice->client)->displayName($isAr ? 'ar' : 'en') ?? '—';
    $balance = $salesInvoice->balance_due;
@endphp
@section('header_title', $isAr ? 'تسجيل سند قبض' : 'Record Client Receipt')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex justify-between items-center animate-fade-in">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
                <i class="fas fa-hand-holding-usd text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $isAr ? 'تسجيل سند قبض' : 'Record Client Receipt' }}</h2>
        </div>
        <a href="{{ route('sales-invoices.show', $salesInvoice) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }} text-sm"></i> {{ $isAr ? 'فاتورة البيع' : 'Sales Invoice' }}
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 animate-fade-in">
        <div class="mb-6 p-4 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-400">{{ $isAr ? 'فاتورة البيع' : 'Sales Invoice' }}</p>
                <p class="font-mono font-bold text-gray-800">{{ $salesInvoice->invoice_number }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $clientDisplay }}</p>
            </div>
            <div class="text-{{ $isAr ? 'left' : 'right' }}">
                <p class="text-xs text-gray-400">{{ $isAr ? 'المتبقي' : 'Balance Due' }}</p>
                <p class="font-extrabold text-lg {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}" dir="ltr">{{ number_format($balance, 2) }} {{ $salesInvoice->currency }}</p>
            </div>
        </div>

        <div class="h-2 w-32 bg-[#008A3B] rounded-full mb-8"></div>

        <form action="{{ route('client-receipts.store') }}" method="POST">
            @csrf
            <input type="hidden" name="sales_invoice_id" value="{{ $salesInvoice->id }}">

            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'رقم السند' : 'Receipt No.' }}</label>
                    <input type="text" value="{{ $isAr ? '— يُولَّد تلقائيًا عند الحفظ —' : '— Generated automatically on save —' }}" disabled dir="ltr"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-400 italic cursor-not-allowed">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'المبلغ المحصّل' : 'Amount Received' }} <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required dir="ltr"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] bg-gray-50 focus:bg-white">
                        <p class="text-[11px] text-gray-400 mt-1">{{ $isAr ? 'يمكن أن يكون تحصيل جزئي' : 'Can be a partial payment' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'العملة' : 'Currency' }}</label>
                        <input type="text" name="currency" id="receiptCurrency" value="{{ $salesInvoice->currency }}" readonly dir="ltr"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg font-mono bg-gray-100 text-gray-600 cursor-not-allowed">
                        <p class="text-[11px] text-gray-400 mt-1">{{ $isAr ? 'مقفولة على عملة فاتورة البيع ولا يمكن تغييرها' : 'Locked to the sales invoice currency and cannot be changed' }}</p>
                        @error('currency') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'تاريخ التحصيل' : 'Receipt Date' }} <span class="text-red-500">*</span></label>
                        <input type="date" name="receipt_date" value="{{ old('receipt_date', now()->toDateString()) }}" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] bg-gray-50 focus:bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'طريقة الدفع' : 'Payment Method' }}</label>
                        <select name="payment_method" data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                            <option value="">{{ $isAr ? '— غير محدد —' : '— Not set —' }}</option>
                            @foreach($paymentMethods as $m)
                                <option value="{{ $m->key_value }}" {{ old('payment_method') == $m->key_value ? 'selected' : '' }}>{{ $m->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        <i class="fas fa-wallet text-[#005B9F] mr-1"></i> {{ $isAr ? 'المحفظة (الإضافة إليها)' : 'Wallet (Add to)' }} <span class="text-red-500">*</span>
                    </label>
                    <select name="wallet_id" required data-search class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                        <option value="" disabled {{ old('wallet_id') ? '' : 'selected' }}>{{ $isAr ? '— اختر المحفظة —' : '— Choose wallet —' }}</option>
                        @foreach($wallets as $w)
                            <option value="{{ $w->id }}" data-currency="{{ $w->currency }}" {{ old('wallet_id') == $w->id ? 'selected' : '' }}>{{ $w->name }} ({{ $w->currency }})</option>
                        @endforeach
                    </select>
                    @error('wallet_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'ملاحظات' : 'Notes' }}</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] bg-gray-50 focus:bg-white">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="mt-10 flex justify-end gap-4 border-t border-gray-100 pt-8">
                <a href="{{ route('sales-invoices.show', $salesInvoice) }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium transition-colors">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </a>
                <button type="submit" class="px-8 py-2.5 bg-[#008A3B] rounded-lg text-white hover:bg-[#007030] font-bold shadow-lg flex items-center gap-2">
                    <i class="fas fa-save"></i> {{ $isAr ? 'حفظ سند القبض' : 'Save Receipt' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // فلترة المحفظة على عملة فاتورة البيع الثابتة — تظهر بس المحافظ اللي بنفس العملة
    window.addEventListener('load', function () {
        var cur = document.getElementById('receiptCurrency').value;
        var walletSel = document.querySelector('select[name="wallet_id"]');
        var ts = walletSel ? walletSel.tomselect : null;
        if (!ts) return;

        var allWallets = @json($wallets->map(fn ($w) => ['id' => $w->id, 'name' => $w->name, 'currency' => $w->currency]));
        var oldWalletId = {{ old('wallet_id') ? (int) old('wallet_id') : 'null' }};
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
@endsection
