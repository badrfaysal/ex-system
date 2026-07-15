@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'فواتير الشراء' : 'Purchase Invoices')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
                <i class="fas fa-file-invoice text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'فواتير الشراء' : 'Purchase Invoices' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'إدارة فواتير المشتريات المرتبطة بأوامر البيع' : 'Manage purchase invoices linked to sales orders' }}</p>
            </div>
        </div>
        <a href="{{ route('purchase-invoices.create') }}" class="px-5 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold text-sm hover:bg-[#007030] flex items-center gap-2 shadow-sm transition-colors">
            <i class="fas fa-plus"></i> {{ $isAr ? 'إنشاء فاتورة شراء' : 'Create Purchase Invoice' }}
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('purchase-invoices.index') }}" method="GET" class="flex flex-wrap items-end gap-4" id="filterForm">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'بحث' : 'Search' }}</label>
                <input type="text" name="search" id="searchInput" value="{{ request('search') }}" placeholder="{{ $isAr ? 'رقم الفاتورة / المورد' : 'Invoice no. / Vendor' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <div class="w-36">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'من تاريخ' : 'Date From' }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" onchange="document.getElementById('filterForm').submit();" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <div class="w-36">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'إلى تاريخ' : 'Date To' }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" onchange="document.getElementById('filterForm').submit();" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
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
                        <th class="p-4">{{ $isAr ? 'المورد' : 'Vendor' }}</th>
                        <th class="p-4">{{ $isAr ? 'التاريخ' : 'Date' }}</th>
                        <th class="p-4">{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-blue-50/40 transition-colors cursor-pointer" onclick="location.href='{{ route('purchase-invoices.show', $invoice) }}'">
                            <td class="p-4 font-mono font-bold text-gray-800" dir="ltr">
                                {{ $invoice->invoice_number }}
                                @if($invoice->vendor_invoice_number)
                                    <div class="text-[11px] font-normal text-gray-400 mt-0.5">{{ $isAr ? 'رقم المورد:' : 'Vendor No.:' }} {{ $invoice->vendor_invoice_number }}</div>
                                @endif
                            </td>
                            <td class="p-4 font-bold text-[#005B9F]">
                                {{ optional($invoice->vendor)->name_ar ?: optional($invoice->vendor)->name_en }}
                            </td>
                            <td class="p-4 text-gray-500" dir="ltr">{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                            <td class="p-4 font-bold text-gray-900" dir="ltr">{{ number_format($invoice->grand_total, 2) }} <span class="text-xs text-gray-400">{{ $invoice->currency }}</span></td>
                            <td class="p-4 text-left"><i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }} text-gray-300"></i></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد فواتير شراء' : 'No purchase invoices yet' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages()) <div class="p-4 border-t border-gray-100 bg-white">{{ $invoices->links() }}</div> @endif
    </div>
</div>


@endsection
