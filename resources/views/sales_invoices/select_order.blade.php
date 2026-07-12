@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'اختر أمر البيع' : 'Choose Sales Order')

@section('content')
<div class="max-w-4xl mx-auto animate-fade-in">
    <div class="mb-6 flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
            <i class="fas fa-file-contract text-2xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $isAr ? 'إنشاء فاتورة بيع — اختر أمر البيع' : 'Create Sales Invoice — Choose Sales Order' }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'الفاتورة هتتربط بأمر البيع المختار' : 'The invoice will be linked to the selected sales order' }}</p>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-4">
        <form action="{{ route('sales-invoices.create') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'رقم أمر البيع' : 'SO Number' }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'بحث برقم أمر البيع' : 'Search SO number' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'العميل' : 'Client' }}</label>
                <select name="client_id" data-search class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:border-[#008A3B]">
                    <option value="">{{ $isAr ? '— كل العملاء —' : '— All clients —' }}</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->displayName($isAr ? 'ar' : 'en') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'من تاريخ' : 'Date From' }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'إلى تاريخ' : 'Date To' }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <button type="submit" class="px-6 py-2 bg-[#008A3B] text-white rounded-lg text-sm font-bold hover:bg-[#007030] transition-colors flex items-center gap-2">
                <i class="fas fa-filter"></i> {{ $isAr ? 'تطبيق' : 'Apply' }}
            </button>
            @if(request()->hasAny(['search', 'client_id', 'date_from', 'date_to']))
            <a href="{{ route('sales-invoices.create') }}" class="px-4 py-2 text-gray-500 text-sm hover:text-gray-700">{{ $isAr ? 'مسح الفلاتر' : 'Clear filters' }}</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="divide-y divide-gray-100">
            @forelse($salesOrders as $so)
                <a href="{{ route('sales-invoices.create', ['sales_order_id' => $so->id]) }}"
                   class="flex items-center justify-between px-6 py-4 hover:bg-green-50/50 transition-colors">
                    <div>
                        <p class="font-mono font-bold text-gray-800" dir="ltr">{{ $so->so_number }}</p>
                        <p class="text-sm text-gray-500">{{ optional($so->client)->displayName($isAr ? 'ar' : 'en') }}</p>
                    </div>
                    <div class="text-{{ $isAr ? 'left' : 'right' }}">
                        <p class="font-bold text-[#008A3B]" dir="ltr">{{ number_format($so->grand_total, 2) }} {{ $so->currency }}</p>
                        <p class="text-xs text-gray-400">{{ optional($so->so_date)->format('Y-m-d') }}</p>
                    </div>
                </a>
            @empty
                <p class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد أوامر بيع — حوّل عرض سعر معتمد أولاً.' : 'No sales orders yet — convert an approved quotation first.' }}</p>
            @endforelse
        </div>
        @if($salesOrders->hasPages()) <div class="p-4 border-t border-gray-100">{{ $salesOrders->links() }}</div> @endif
    </div>

    <div class="mt-4">
        <a href="{{ route('sales-invoices.index') }}" class="text-sm text-gray-500 hover:text-[#008A3B]">
            <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ $isAr ? 'رجوع لفواتير البيع' : 'Back to sales invoices' }}
        </a>
    </div>
</div>
@endsection
