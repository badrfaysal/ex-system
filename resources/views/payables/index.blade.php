@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'الالتزامات (موردين)' : 'Payables (Vendors)')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-red-500/10 flex items-center justify-center text-red-600">
                <i class="fas fa-file-invoice-dollar text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'الالتزامات' : 'Payables' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'الفلوس اللي عليك للموردين' : 'Money you owe to vendors' }}</p>
            </div>
        </div>
        <div class="flex bg-gray-100 p-1 rounded-xl">
            <a href="{{ route('payables.index', ['tab' => 'active', 'search' => request('search'), 'sort' => request('sort')]) }}"
               class="px-5 py-2 text-sm font-bold rounded-lg transition-colors {{ request('tab', 'active') === 'active' ? 'bg-white shadow-sm text-red-600' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $isAr ? 'النشط' : 'Active' }}
            </a>
            <a href="{{ route('payables.index', ['tab' => 'paid', 'search' => request('search'), 'sort' => request('sort')]) }}"
               class="px-5 py-2 text-sm font-bold rounded-lg transition-colors {{ request('tab') === 'paid' ? 'bg-white shadow-sm text-red-600' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $isAr ? 'المسدد' : 'Paid' }}
            </a>
        </div>
    </div>

    {{-- كروت الملخص --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-400 mb-1">{{ $isAr ? 'إجمالي الفواتير' : 'Total Invoiced' }}</p>
            <p class="text-2xl font-extrabold text-gray-900" dir="ltr">{{ number_format($summary['invoiced'], 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-400 mb-1">{{ $isAr ? 'إجمالي المدفوع' : 'Total Paid' }}</p>
            <p class="text-2xl font-extrabold text-green-600" dir="ltr">{{ number_format($summary['paid'], 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5" style="background:linear-gradient(135deg,#fef2f2,#ffffff);">
            <p class="text-xs text-gray-400 mb-1">{{ $isAr ? 'إجمالي الرصيد المستحق' : 'Total Balance Due' }}</p>
            <p class="text-2xl font-extrabold text-red-600" dir="ltr">{{ number_format($summary['balance'], 2) }}</p>
        </div>
    </div>

    {{-- فلتر وترتيب --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('payables.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <input type="hidden" name="tab" value="{{ request('tab', 'active') }}">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'بحث بالمورد' : 'Search Vendor' }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'اسم المورد أو الكود' : 'Vendor name or code' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-red-500 bg-gray-50">
            </div>
            <div class="w-full sm:w-56">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'الترتيب' : 'Sort By' }}</label>
                <select name="sort" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:border-red-500">
                    <option value="balance_desc" {{ $sort === 'balance_desc' ? 'selected' : '' }}>{{ $isAr ? 'الرصيد: الأعلى أولاً' : 'Balance: Highest First' }}</option>
                    <option value="balance_asc" {{ $sort === 'balance_asc' ? 'selected' : '' }}>{{ $isAr ? 'الرصيد: الأقل أولاً' : 'Balance: Lowest First' }}</option>
                    <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>{{ $isAr ? 'الأحدث' : 'Newest' }}</option>
                    <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>{{ $isAr ? 'الأقدم' : 'Oldest' }}</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-filter"></i> {{ $isAr ? 'تطبيق' : 'Apply' }}
                </button>
                @if(request('search'))
                <a href="{{ route('payables.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">{{ $isAr ? 'مسح' : 'Clear' }}</a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ $isAr ? 'المورد' : 'Vendor' }}</th>
                        <th class="p-4">{{ $isAr ? 'إجمالي الفواتير' : 'Total Invoiced' }}</th>
                        <th class="p-4">{{ $isAr ? 'إجمالي المدفوع' : 'Total Paid' }}</th>
                        <th class="p-4">{{ $isAr ? 'الرصيد المستحق' : 'Balance Due' }}</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($vendors as $vendor)
                        <tr class="hover:bg-red-50/30 cursor-pointer" onclick="location.href='{{ route('payables.show', $vendor) }}'">
                            <td class="p-4 font-bold text-gray-900">{{ $isAr ? $vendor->name_ar : ($vendor->name_en ?: $vendor->name_ar) }}</td>
                            <td class="p-4 text-gray-600" dir="ltr">{{ number_format($vendor->invoiced_total ?? 0, 2) }}</td>
                            <td class="p-4 text-gray-600" dir="ltr">{{ number_format($vendor->paid_total ?? 0, 2) }}</td>
                            <td class="p-4 font-extrabold {{ $vendor->balance > 0 ? 'text-red-600' : 'text-green-600' }}" dir="ltr">{{ number_format($vendor->balance, 2) }}</td>
                            <td class="p-4 text-left">
                                <a href="{{ route('vendor-payments.create', ['vendor_id' => $vendor->id]) }}" onclick="event.stopPropagation()" class="text-xs px-3 py-1.5 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700">
                                    {{ $isAr ? 'تسجيل دفعة' : 'Pay' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد التزامات' : 'No payables yet' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
