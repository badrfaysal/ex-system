@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'سندات القبض من العملاء' : 'Client Receipts')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
                <i class="fas fa-hand-holding-usd text-2xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'سندات القبض من العملاء' : 'Client Receipts' }}</h2>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ $isAr ? 'رقم السند' : 'No.' }}</th>
                        <th class="p-4">{{ $isAr ? 'العميل' : 'Client' }}</th>
                        <th class="p-4">{{ $isAr ? 'أمر البيع' : 'Sales Order' }}</th>
                        <th class="p-4">{{ $isAr ? 'المبلغ' : 'Amount' }}</th>
                        <th class="p-4">{{ $isAr ? 'التاريخ' : 'Date' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($receipts as $receipt)
                        <tr class="hover:bg-green-50/30">
                            <td class="p-4 font-mono font-bold text-gray-800" dir="ltr">{{ $receipt->receipt_number }}</td>
                            <td class="p-4">
                                <a href="{{ route('receivables.show', $receipt->client_id) }}" class="text-[#005B9F] hover:underline font-bold">
                                    {{ optional($receipt->client)->displayName($isAr ? 'ar' : 'en') }}
                                </a>
                            </td>
                            <td class="p-4">
                                <a href="{{ route('sales-orders.show', $receipt->sales_order_id) }}" class="font-mono text-gray-600 hover:underline" dir="ltr">
                                    {{ optional($receipt->salesOrder)->so_number }}
                                </a>
                            </td>
                            <td class="p-4 font-bold text-[#008A3B]" dir="ltr">{{ number_format($receipt->amount, 2) }} <span class="text-xs text-gray-400">{{ $receipt->currency }}</span></td>
                            <td class="p-4 text-gray-500" dir="ltr">{{ $receipt->receipt_date->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد سندات قبض' : 'No receipts yet' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($receipts->hasPages()) <div class="p-4 border-t border-gray-100 bg-white">{{ $receipts->links() }}</div> @endif
    </div>
</div>
@endsection
