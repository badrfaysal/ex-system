@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'فواتير البيع' : 'Sales Invoices')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
                <i class="fas fa-file-invoice-dollar text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'فواتير البيع' : 'Sales Invoices' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'تُنشأ من أمر البيع — يمكن أكثر من فاتورة' : 'Created from sales orders — multiple invoices allowed' }}</p>
            </div>
        </div>
        <a href="{{ route('sales-invoices.create') }}" class="px-6 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030] transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> {{ $isAr ? 'إنشاء فاتورة بيع' : 'Create Sales Invoice' }}
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('sales-invoices.index') }}" method="GET" class="flex flex-wrap items-end gap-4" id="filterForm">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'بحث' : 'Search' }}</label>
                <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="{{ $isAr ? 'رقم الفاتورة / أمر البيع / العميل' : 'Invoice / SO / client' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <div class="w-48">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'ترتيب حسب' : 'Sort By' }}</label>
                <select name="sort" onchange="document.getElementById('filterForm').submit();" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>{{ $isAr ? 'الأحدث' : 'Newest' }}</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>{{ $isAr ? 'الأقدم' : 'Oldest' }}</option>
                    <option value="highest" {{ request('sort') === 'highest' ? 'selected' : '' }}>{{ $isAr ? 'المبلغ (الأعلى)' : 'Highest Amount' }}</option>
                    <option value="lowest" {{ request('sort') === 'lowest' ? 'selected' : '' }}>{{ $isAr ? 'المبلغ (الأقل)' : 'Lowest Amount' }}</option>
                </select>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ $isAr ? 'رقم الفاتورة' : 'Invoice No.' }}</th>
                        <th class="p-4">{{ $isAr ? 'أمر البيع' : 'Sales Order' }}</th>
                        <th class="p-4">{{ $isAr ? 'العميل' : 'Client' }}</th>
                        <th class="p-4">{{ $isAr ? 'التاريخ' : 'Date' }}</th>
                        <th class="p-4">{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-green-50/40 transition-colors cursor-pointer" onclick="location.href='{{ route('sales-invoices.show', $invoice) }}'">
                            <td class="p-4 font-mono font-bold text-gray-800" dir="ltr">{{ $invoice->invoice_number }}</td>
                            <td class="p-4 font-mono text-[#005B9F]" dir="ltr">{{ optional($invoice->salesOrder)->so_number }}</td>
                            <td class="p-4">{{ optional($invoice->client)->displayName($isAr ? 'ar' : 'en') }}</td>
                            <td class="p-4 text-gray-500" dir="ltr">
                                {{ $invoice->invoice_date->format('Y-m-d') }}
                                @if($invoice->is_overdue)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-600 bg-red-50 rounded-full px-2 py-0.5 mt-0.5"><i class="fas fa-exclamation-triangle"></i> {{ $isAr ? 'متأخر' : 'Overdue' }}</span>
                                @endif
                            </td>
                            <td class="p-4 font-bold text-gray-900" dir="ltr">{{ number_format($invoice->grand_total, 2) }} <span class="text-xs text-gray-400">{{ $invoice->currency }}</span></td>
                            <td class="p-4 text-left"><i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }} text-gray-300"></i></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد فواتير بيع' : 'No sales invoices yet' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages()) <div class="p-4 border-t border-gray-100 bg-white">{{ $invoices->links() }}</div> @endif
    </div>
</div>


@endsection
