@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'اختر أمر البيع' : 'Choose Sales Order')

@section('content')
<div class="max-w-3xl mx-auto animate-fade-in">
    <div class="mb-6 flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
            <i class="fas fa-file-contract text-2xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $isAr ? 'إنشاء فاتورة بيع — اختر أمر البيع' : 'Create Sales Invoice — Choose Sales Order' }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'الفاتورة هتتربط بأمر البيع المختار' : 'The invoice will be linked to the selected sales order' }}</p>
        </div>
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
