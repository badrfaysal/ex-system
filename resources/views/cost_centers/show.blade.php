@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $cur  = $quotation->currency ?? 'EGP';
@endphp
@section('header_title', $quotation->quote_number)

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('cost-centers.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
            <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ $isAr ? 'كل مراكز التكلفة' : 'All Cost Centers' }}
        </a>
        <a href="{{ route('quotations.show', $quotation) }}" class="text-sm text-[#005B9F] hover:underline font-bold">{{ $isAr ? 'عرض السعر الأصلي' : 'Original Quotation' }} <i class="fas fa-external-link-alt text-xs"></i></a>
    </div>

    {{-- ملخص الربح/الخسارة --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="h-1.5 bg-gradient-to-r from-purple-500 to-purple-700"></div>
        <div class="px-8 py-5 border-b border-gray-100">
            @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-2.5 text-sm flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500"></i> {{ session('success') }}
            </div>
            @endif

            <form action="{{ route('cost-centers.update', $quotation) }}" method="POST" class="flex items-center gap-2 mb-2" id="renameForm">
                @csrf
                @method('PATCH')
                <i class="fas fa-layer-group text-purple-500"></i>
                <input type="text" name="cost_center_name" value="{{ $quotation->cost_center_name }}"
                    class="flex-1 text-xl font-extrabold text-gray-900 border-0 border-b-2 border-transparent hover:border-gray-200 focus:border-purple-400 focus:outline-none bg-transparent px-1 py-0.5 transition-colors"
                    onchange="document.getElementById('renameForm').submit()">
                <button type="submit" class="text-xs px-3 py-1.5 bg-purple-50 text-purple-600 rounded-lg font-bold hover:bg-purple-100 flex items-center gap-1.5">
                    <i class="fas fa-save"></i> {{ $isAr ? 'حفظ الاسم' : 'Save name' }}
                </button>
            </form>
            <p class="text-sm text-gray-500 font-mono" dir="ltr">{{ $quotation->quote_number }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ optional($quotation->client)->displayName($isAr ? 'ar' : 'en') }}</p>
        </div>
        <div class="grid grid-cols-3 divide-x divide-gray-100" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
            <div class="p-6 text-center">
                <p class="text-xs text-gray-400 mb-1">{{ $isAr ? 'الإيراد المحصّل' : 'Revenue Collected' }}</p>
                <p class="text-xl font-extrabold text-green-600" dir="ltr">{{ number_format($revenue, 2) }}</p>
            </div>
            <div class="p-6 text-center">
                <p class="text-xs text-gray-400 mb-1">{{ $isAr ? 'إجمالي التكلفة' : 'Total Cost' }}</p>
                <p class="text-xl font-extrabold text-red-600" dir="ltr">{{ number_format($cost, 2) }}</p>
            </div>
            <div class="p-6 text-center {{ $profit >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
                <p class="text-xs text-gray-400 mb-1">{{ $isAr ? 'الربح / الخسارة' : 'Profit / Loss' }}</p>
                <p class="text-xl font-extrabold {{ $profit >= 0 ? 'text-green-700' : 'text-red-700' }}" dir="ltr">
                    {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit, 2) }} {{ $cur }}
                </p>
            </div>
        </div>
    </div>

    {{-- فواتير الشراء --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/60 font-bold text-gray-800 text-sm flex items-center gap-2">
            <i class="fas fa-file-invoice text-[#005B9F]"></i> {{ $isAr ? 'فواتير الشراء' : 'Purchase Invoices' }}
        </div>
        <table class="w-full text-sm" style="text-align:{{ $isAr ? 'right' : 'left' }}">
            <tbody class="divide-y divide-gray-100">
                @forelse($quotation->purchaseInvoices as $pi)
                    <tr class="hover:bg-gray-50/60">
                        <td class="p-4"><a href="{{ route('purchase-invoices.show', $pi) }}" class="font-mono text-[#005B9F] hover:underline">{{ $pi->invoice_number }}</a></td>
                        <td class="p-4 text-gray-500" dir="ltr">{{ $pi->invoice_date->format('Y-m-d') }}</td>
                        <td class="p-4 font-bold text-red-600" dir="ltr">{{ number_format($pi->grand_total, 2) }} {{ $pi->currency }}</td>
                    </tr>
                @empty
                    <tr><td class="p-6 text-center text-gray-400">{{ $isAr ? 'لا توجد فواتير شراء' : 'No purchase invoices' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- المصروفات --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/60 font-bold text-gray-800 text-sm flex items-center justify-between">
            <span class="flex items-center gap-2"><i class="fas fa-receipt text-amber-500"></i> {{ $isAr ? 'المصروفات' : 'Expenses' }}</span>
            <a href="{{ route('expenses.create', ['quotation_id' => $quotation->id]) }}" class="text-xs px-3 py-1.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030]">{{ $isAr ? 'إضافة مصروف' : 'Add Expense' }}</a>
        </div>
        <table class="w-full text-sm" style="text-align:{{ $isAr ? 'right' : 'left' }}">
            <tbody class="divide-y divide-gray-100">
                @forelse($quotation->expenses as $e)
                    <tr class="hover:bg-gray-50/60">
                        <td class="p-4 font-mono text-gray-700">{{ $e->expense_number }}</td>
                        <td class="p-4 text-gray-600">{{ $e->category }}</td>
                        <td class="p-4 text-gray-500" dir="ltr">{{ $e->expense_date->format('Y-m-d') }}</td>
                        <td class="p-4 font-bold text-red-600" dir="ltr">{{ number_format($e->amount, 2) }} {{ $e->currency }}</td>
                    </tr>
                @empty
                    <tr><td class="p-6 text-center text-gray-400">{{ $isAr ? 'لا توجد مصروفات' : 'No expenses' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- سندات القبض --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/60 font-bold text-gray-800 text-sm flex items-center gap-2">
            <i class="fas fa-hand-holding-usd text-green-600"></i> {{ $isAr ? 'سندات القبض' : 'Client Receipts' }}
        </div>
        <table class="w-full text-sm" style="text-align:{{ $isAr ? 'right' : 'left' }}">
            <tbody class="divide-y divide-gray-100">
                @forelse($quotation->receipts as $r)
                    <tr class="hover:bg-gray-50/60">
                        <td class="p-4 font-mono text-gray-700">{{ $r->receipt_number }}</td>
                        <td class="p-4 text-gray-500" dir="ltr">{{ $r->receipt_date->format('Y-m-d') }}</td>
                        <td class="p-4 font-bold text-green-600" dir="ltr">{{ number_format($r->amount, 2) }} {{ $r->currency }}</td>
                    </tr>
                @empty
                    <tr><td class="p-6 text-center text-gray-400">{{ $isAr ? 'لا توجد سندات قبض' : 'No receipts' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
