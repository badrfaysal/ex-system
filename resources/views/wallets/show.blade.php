@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $typeLabels = [
        'receipt'       => ['ar' => 'سند قبض', 'en' => 'Receipt', 'color' => 'bg-green-50 text-green-700'],
        'revenue'       => ['ar' => 'إيراد', 'en' => 'Revenue', 'color' => 'bg-green-50 text-green-700'],
        'expense'       => ['ar' => 'مصروف', 'en' => 'Expense', 'color' => 'bg-red-50 text-red-600'],
        'vendor_payment'=> ['ar' => 'سند دفع مورد', 'en' => 'Vendor Payment', 'color' => 'bg-red-50 text-red-600'],
        'transfer_out'  => ['ar' => 'تحويل صادر', 'en' => 'Transfer Out', 'color' => 'bg-amber-50 text-amber-700'],
        'transfer_in'   => ['ar' => 'تحويل وارد', 'en' => 'Transfer In', 'color' => 'bg-blue-50 text-[#005B9F]'],
    ];
@endphp
@section('header_title', $wallet->name)

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('wallets.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
            <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ $isAr ? 'كل الحسابات' : 'All Accounts' }}
        </a>
        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('revenueModal').classList.remove('hidden')" class="px-5 py-2 bg-[#008A3B] text-white rounded-lg font-bold text-sm hover:bg-green-700 flex items-center gap-2 shadow-sm">
                <i class="fas fa-plus-circle"></i> {{ $isAr ? 'إيراد مباشر' : 'Direct Revenue' }}
            </button>
            <button onclick="document.getElementById('expenseModal').classList.remove('hidden')" class="px-5 py-2 bg-red-600 text-white rounded-lg font-bold text-sm hover:bg-red-700 flex items-center gap-2 shadow-sm">
                <i class="fas fa-minus-circle"></i> {{ $isAr ? 'صرف مباشر' : 'Direct Expense' }}
            </button>

            <div class="w-px h-6 bg-gray-300 mx-1"></div>

            <a href="{{ route('wallets.print', $wallet) }}" target="_blank" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2 shadow-sm">
                <i class="fas fa-print"></i>
            </a>
            <a href="{{ route('wallet-transfers.create', ['from_wallet_id' => $wallet->id]) }}" class="px-5 py-2 bg-[#005B9F] text-white rounded-lg font-bold text-sm hover:bg-blue-800 flex items-center gap-2 shadow-sm">
                <i class="fas fa-exchange-alt"></i> {{ $isAr ? 'تحويل للخارج' : 'Transfer Out' }}
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="h-1.5 bg-gradient-to-r from-[#005B9F] to-blue-700"></div>
        <div class="px-8 py-5 flex items-center justify-between">
            <div>
                <p class="text-2xl font-extrabold text-gray-900">{{ $wallet->name }}</p>
                <p class="text-sm text-gray-400 mt-1">{{ $isAr ? 'كشف الحساب' : 'Account Statement' }} — {{ $wallet->currency }}</p>
            </div>
            <div class="text-{{ $isAr ? 'left' : 'right' }}">
                <p class="text-xs text-gray-400">{{ $isAr ? 'الرصيد الحالي' : 'Current Balance' }}</p>
                <p class="text-2xl font-extrabold {{ $balance >= 0 ? 'text-[#008A3B]' : 'text-red-600' }}" dir="ltr">{{ number_format($balance, 2) }}</p>
                <p class="text-[11px] text-gray-400 mt-1">{{ $isAr ? 'رصيد بداية المدة:' : 'Opening:' }} {{ number_format($wallet->opening_balance, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm border-collapse" style="text-align:{{ $isAr ? 'right' : 'left' }}">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-xs font-bold">
                    <th class="p-4">{{ $isAr ? 'التاريخ' : 'Date' }}</th>
                    <th class="p-4">{{ $isAr ? 'المرجع' : 'Reference' }}</th>
                    <th class="p-4">{{ $isAr ? 'النوع' : 'Type' }}</th>
                    <th class="p-4">{{ $isAr ? 'التفاصيل' : 'Detail' }}</th>
                    <th class="p-4">{{ $isAr ? 'المستخدم' : 'User' }}</th>
                    <th class="p-4">{{ $isAr ? 'المبلغ' : 'Amount' }}</th>
                    <th class="p-4">{{ $isAr ? 'الرصيد الجاري' : 'Running Balance' }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($timeline as $entry)
                    @php $t = $typeLabels[$entry['type']]; @endphp
                    <tr class="hover:bg-gray-50/60 {{ !empty($entry['is_reversed']) ? 'line-through opacity-50' : '' }} {{ !empty($entry['is_reversal']) ? 'bg-amber-50/40 text-amber-900' : '' }}">
                        <td class="p-4 text-gray-500" dir="ltr">{{ optional($entry['date'])->format('Y-m-d') }}</td>
                        <td class="p-4 font-mono text-gray-700">{{ $entry['ref'] }}</td>
                        <td class="p-4"><span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $t['color'] }}">{{ $isAr ? $t['ar'] : $t['en'] }}</span></td>
                        <td class="p-4 text-gray-600">{{ $entry['detail'] ?? '—' }}</td>
                        <td class="p-4 text-gray-500">{{ $entry['user'] ?? '—' }}</td>
                        <td class="p-4 font-bold {{ $entry['amount'] >= 0 ? 'text-[#008A3B]' : 'text-red-600' }}" dir="ltr">
                            {{ $entry['amount'] >= 0 ? '+' : '' }}{{ number_format($entry['amount'], 2) }}
                        </td>
                        <td class="p-4 font-extrabold text-gray-900" dir="ltr">{{ number_format($entry['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد حركات بعد' : 'No transactions yet' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Revenue Modal -->
<div id="revenueModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="bg-green-50 px-6 py-4 border-b border-green-100 flex justify-between items-center">
            <h3 class="font-bold text-[#008A3B] text-lg flex items-center gap-2"><i class="fas fa-plus-circle"></i> {{ $isAr ? 'تسجيل إيراد مباشر' : 'Record Direct Revenue' }}</h3>
            <button onclick="document.getElementById('revenueModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('revenues.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">
            <input type="hidden" name="revenue_date" value="{{ date('Y-m-d') }}">
            
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'المبلغ' : 'Amount' }} *</label>
                <div class="relative">
                    <input type="number" step="0.01" min="0.01" name="amount" required class="w-full pl-4 pr-12 py-2.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#008A3B] focus:border-[#008A3B] font-bold" dir="ltr">
                    <span class="absolute top-1/2 -translate-y-1/2 right-4 text-gray-400 font-bold text-sm">{{ $wallet->currency }}</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'التصنيف / السبب' : 'Category / Reason' }} *</label>
                <input type="text" name="category" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#008A3B] focus:border-[#008A3B] text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'ملاحظات إضافية' : 'Additional Notes' }}</label>
                <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#008A3B] focus:border-[#008A3B] text-sm"></textarea>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" class="flex-1 bg-[#008A3B] text-white font-bold py-2.5 rounded-lg hover:bg-green-700 transition-colors">{{ $isAr ? 'حفظ وإيداع' : 'Save & Deposit' }}</button>
                <button type="button" onclick="document.getElementById('revenueModal').classList.add('hidden')" class="px-6 bg-gray-100 text-gray-700 font-bold py-2.5 rounded-lg hover:bg-gray-200 transition-colors">{{ $isAr ? 'إلغاء' : 'Cancel' }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Expense Modal -->
<div id="expenseModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex justify-between items-center">
            <h3 class="font-bold text-red-800 text-lg flex items-center gap-2"><i class="fas fa-minus-circle"></i> {{ $isAr ? 'تسجيل صرف مباشر' : 'Record Direct Expense' }}</h3>
            <button onclick="document.getElementById('expenseModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('expenses.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="wallet_id" value="{{ $wallet->id }}">
            <input type="hidden" name="expense_date" value="{{ date('Y-m-d') }}">
            <input type="hidden" name="currency" value="{{ $wallet->currency }}">
            <input type="hidden" name="redirect_to" value="{{ route('wallets.show', $wallet) }}">
            
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'المبلغ' : 'Amount' }} *</label>
                <div class="relative">
                    <input type="number" step="0.01" min="0.01" name="amount" required class="w-full pl-4 pr-12 py-2.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-red-500 focus:border-red-500 font-bold" dir="ltr">
                    <span class="absolute top-1/2 -translate-y-1/2 right-4 text-gray-400 font-bold text-sm">{{ $wallet->currency }}</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'بند المصروف' : 'Expense Category' }} *</label>
                <select name="category" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-red-500 focus:border-red-500 text-sm">
                    <option value="">{{ $isAr ? '— اختر البند —' : '-- Select Category --' }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->key_value }}">{{ $cat->display_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'السبب / التفاصيل' : 'Reason / Details' }}</label>
                <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-red-500 focus:border-red-500 text-sm"></textarea>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" class="flex-1 bg-red-600 text-white font-bold py-2.5 rounded-lg hover:bg-red-700 transition-colors">{{ $isAr ? 'حفظ وصرف' : 'Save & Deduct' }}</button>
                <button type="button" onclick="document.getElementById('expenseModal').classList.add('hidden')" class="px-6 bg-gray-100 text-gray-700 font-bold py-2.5 rounded-lg hover:bg-gray-200 transition-colors">{{ $isAr ? 'إلغاء' : 'Cancel' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
