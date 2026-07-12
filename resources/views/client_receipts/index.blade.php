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

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('client-receipts.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'بحث' : 'Search' }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'رقم السند أو اسم العميل' : 'Receipt no. or client name' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <button type="submit" class="px-6 py-2 bg-[#008A3B] text-white rounded-lg text-sm font-bold hover:bg-[#007030] transition-colors flex items-center gap-2">
                <i class="fas fa-filter"></i> {{ $isAr ? 'تطبيق' : 'Apply' }}
            </button>
            @if(request()->filled('search'))
            <a href="{{ route('client-receipts.index') }}" class="px-4 py-2 text-gray-500 text-sm hover:text-gray-700">{{ $isAr ? 'مسح' : 'Clear' }}</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ $isAr ? 'رقم السند' : 'No.' }}</th>
                        <th class="p-4">{{ $isAr ? 'العميل' : 'Client' }}</th>
                        <th class="p-4">{{ $isAr ? 'فاتورة البيع' : 'Sales Invoice' }}</th>
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
                                @if($receipt->sales_invoice_id)
                                <a href="{{ route('sales-invoices.show', $receipt->sales_invoice_id) }}" class="font-mono text-gray-600 hover:underline" dir="ltr">
                                    {{ optional($receipt->salesInvoice)->invoice_number }}
                                </a>
                                @else
                                <span class="text-gray-400">—</span>
                                @endif
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
