@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'الحسابات البنكية والصناديق المالية' : 'Bank Accounts & Cash Boxes')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
                <i class="fas fa-wallet text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'الحسابات البنكية والصناديق المالية' : 'Bank Accounts & Cash Boxes' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'الحسابات البنكية والخزائن النقدية' : 'Bank accounts and cash drawers' }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button onclick="document.getElementById('revenueModal').classList.remove('hidden')" class="px-4 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-green-700 transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> {{ $isAr ? 'إيراد' : 'Revenue' }}
            </button>
            <button onclick="document.getElementById('expenseModal').classList.remove('hidden')" class="px-4 py-2.5 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-minus-circle"></i> {{ $isAr ? 'صرف' : 'Expense' }}
            </button>
            <a href="{{ route('wallet-transfers.create') }}" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-exchange-alt"></i> {{ $isAr ? 'تحويل' : 'Transfer' }}
            </a>
            <a href="{{ route('wallets.create') }}" class="px-4 py-2.5 bg-[#005B9F] text-white rounded-lg font-bold hover:bg-blue-800 transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-plus"></i> {{ $isAr ? 'حساب جديد' : 'New Account' }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($wallets as $wallet)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:border-[#005B9F] transition-colors relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
                        <i class="fas {{ $wallet->type === 'bank' ? 'fa-university' : 'fa-money-bill-wave' }}"></i>
                    </div>
                    <a href="{{ route('wallets.edit', $wallet) }}" class="text-gray-400 hover:text-[#005B9F]"><i class="fas fa-pen text-sm"></i></a>
                </div>
                <p class="font-bold text-gray-900">{{ $wallet->name }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ $wallet->currency }}</p>
                <p class="text-2xl font-extrabold mt-3 {{ $wallet->current_balance >= 0 ? 'text-[#008A3B]' : 'text-red-600' }}" dir="ltr">{{ number_format($wallet->current_balance, 2) }}</p>
                
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <a href="{{ route('wallets.show', $wallet) }}" class="w-full flex justify-center items-center gap-2 py-2 bg-gray-50 hover:bg-[#005B9F] text-[#005B9F] hover:text-white rounded-lg text-sm font-bold transition-colors">
                        <i class="fas fa-file-invoice-dollar"></i> {{ $isAr ? 'كشف حساب' : 'Statement' }}
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                {{ $isAr ? 'لا توجد حسابات بعد' : 'No accounts yet' }}
            </div>
        @endforelse
    </div>
</div>

<!-- Global Revenue Modal -->
<div id="revenueModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="bg-green-50 px-6 py-4 border-b border-green-100 flex justify-between items-center">
            <h3 class="font-bold text-[#008A3B] text-lg flex items-center gap-2"><i class="fas fa-plus-circle"></i> {{ $isAr ? 'تسجيل إيراد سريع' : 'Quick Revenue' }}</h3>
            <button onclick="document.getElementById('revenueModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('revenues.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="revenue_date" value="{{ date('Y-m-d') }}">
            <input type="hidden" name="redirect_to" value="{{ route('wallets.index') }}">
            
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'إلى حساب' : 'To Account' }} *</label>
                <select name="wallet_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#008A3B] focus:border-[#008A3B] text-sm font-bold">
                    <option value="">{{ $isAr ? '— اختر الحساب —' : '-- Select Account --' }}</option>
                    @foreach($wallets as $w)
                        <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->currency }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'المبلغ' : 'Amount' }} *</label>
                <input type="number" step="0.01" min="0.01" name="amount" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#008A3B] focus:border-[#008A3B] font-bold" dir="ltr">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'التصنيف' : 'Category' }} *</label>
                <input type="text" name="category" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#008A3B] focus:border-[#008A3B] text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'البيان / التفاصيل' : 'Details' }}</label>
                <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#008A3B] focus:border-[#008A3B] text-sm"></textarea>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" class="flex-1 bg-[#008A3B] text-white font-bold py-2.5 rounded-lg hover:bg-green-700 transition-colors">{{ $isAr ? 'حفظ' : 'Save' }}</button>
                <button type="button" onclick="document.getElementById('revenueModal').classList.add('hidden')" class="px-6 bg-gray-100 text-gray-700 font-bold py-2.5 rounded-lg hover:bg-gray-200 transition-colors">{{ $isAr ? 'إلغاء' : 'Cancel' }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Global Expense Modal -->
<div id="expenseModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex justify-between items-center">
            <h3 class="font-bold text-red-800 text-lg flex items-center gap-2"><i class="fas fa-minus-circle"></i> {{ $isAr ? 'تسجيل صرف سريع' : 'Quick Expense' }}</h3>
            <button onclick="document.getElementById('expenseModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('expenses.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="expense_date" value="{{ date('Y-m-d') }}">
            <input type="hidden" name="redirect_to" value="{{ route('wallets.index') }}">
            <!-- Note: currency will be deduced from wallet or hardcoded if needed. We'll set it to EGP for now as a fallback -->
            <input type="hidden" name="currency" value="EGP">
            
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'من حساب' : 'From Account' }} *</label>
                <select name="wallet_id" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-red-500 focus:border-red-500 text-sm font-bold">
                    <option value="">{{ $isAr ? '— اختر الحساب —' : '-- Select Account --' }}</option>
                    @foreach($wallets as $w)
                        <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->currency }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'المبلغ' : 'Amount' }} *</label>
                <input type="number" step="0.01" min="0.01" name="amount" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-1 focus:ring-red-500 focus:border-red-500 font-bold" dir="ltr">
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
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'البيان / التفاصيل' : 'Reason / Details' }}</label>
                <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-1 focus:ring-red-500 focus:border-red-500 text-sm"></textarea>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" class="flex-1 bg-red-600 text-white font-bold py-2.5 rounded-lg hover:bg-red-700 transition-colors">{{ $isAr ? 'حفظ' : 'Save' }}</button>
                <button type="button" onclick="document.getElementById('expenseModal').classList.add('hidden')" class="px-6 bg-gray-100 text-gray-700 font-bold py-2.5 rounded-lg hover:bg-gray-200 transition-colors">{{ $isAr ? 'إلغاء' : 'Cancel' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
