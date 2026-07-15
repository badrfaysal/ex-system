@extends('layouts.app')
@section('header_title', app()->getLocale() === 'ar' ? 'أوامر البيع' : 'Sales Orders')

@php $isAr = app()->getLocale() === 'ar'; @endphp

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 flex items-center gap-3">
    <i class="fas fa-check-circle text-green-500"></i>
    <span class="text-sm font-medium">{{ session('success') }}</span>
</div>
@endif

{{-- Header + بحث --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <h1 class="text-xl font-extrabold text-gray-800 flex items-center gap-2">
        <i class="fas fa-file-contract text-[#008A3B]"></i>
        {{ $isAr ? 'أوامر البيع' : 'Sales Orders' }}
    </h1>
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#005B9F]" title="{{ $isAr ? 'من تاريخ' : 'Date From' }}">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#005B9F]" title="{{ $isAr ? 'إلى تاريخ' : 'Date To' }}">
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="{{ $isAr ? 'بحث برقم الأمر أو العميل...' : 'Search by SO# or client...' }}"
            class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:outline-none focus:border-[#005B9F]">
        <button type="submit" class="px-4 py-2 bg-[#005B9F] text-white rounded-lg text-sm font-medium hover:bg-blue-800">
            <i class="fas fa-search"></i>
        </button>
    </form>
</div>

{{-- جدول --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <thead style="background:#1e293b;color:#fff;">
            <tr>
                <th class="px-4 py-3 text-{{ $isAr ? 'right' : 'left' }} font-bold text-xs">{{ $isAr ? 'رقم الأمر' : 'SO#' }}</th>
                <th class="px-4 py-3 text-{{ $isAr ? 'right' : 'left' }} font-bold text-xs">{{ $isAr ? 'العميل' : 'Client' }}</th>
                <th class="px-4 py-3 text-center font-bold text-xs">{{ $isAr ? 'التاريخ' : 'Date' }}</th>
                <th class="px-4 py-3 text-center font-bold text-xs">{{ $isAr ? 'العملة' : 'Currency' }}</th>
                <th class="px-4 py-3 text-{{ $isAr ? 'left' : 'right' }} font-bold text-xs">{{ $isAr ? 'الإجمالي' : 'Grand Total' }}</th>
                <th class="px-4 py-3 text-center font-bold text-xs">{{ $isAr ? 'الحالة' : 'Status' }}</th>
                <th class="px-4 py-3 text-center font-bold text-xs"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $so)
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors {{ $loop->even ? 'bg-gray-50/40' : '' }}">
                <td class="px-4 py-3 font-mono font-bold text-[#008A3B] text-xs" dir="ltr">
                    {{ $so->so_number }}
                </td>
                <td class="px-4 py-3 font-medium text-gray-800 text-xs">
                    {{ optional($so->client)->displayName($isAr ? 'ar' : 'en') ?? '—' }}
                </td>
                <td class="px-4 py-3 text-center text-gray-500 text-xs" dir="ltr">
                    {{ optional($so->so_date)->format('d/m/Y') ?? '—' }}
                </td>
                <td class="px-4 py-3 text-center font-bold text-[#005B9F] text-xs">
                    {{ $so->currency }}
                </td>
                <td class="px-4 py-3 font-extrabold text-gray-900 text-xs text-{{ $isAr ? 'left' : 'right' }}" dir="ltr">
                    {{ number_format($so->grand_total, 2) }}
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border
                        {{ $so->status === 'confirmed' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-600 border-red-200' }}">
                        {{ $so->status === 'confirmed' ? ($isAr ? 'مؤكد' : 'Confirmed') : ($isAr ? 'ملغي' : 'Cancelled') }}
                    </span>
                </td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center gap-2 justify-center">
                        <a href="{{ route('sales-orders.show', $so) }}"
                           class="px-3 py-1.5 bg-[#008A3B] text-white rounded-lg text-xs font-bold hover:bg-[#007030] flex items-center gap-1.5">
                            <i class="fas fa-eye"></i> {{ $isAr ? 'عرض' : 'View' }}
                        </a>
                        {{-- <a href="{{ route('sales-orders.edit', $so) }}"
                           class="px-3 py-1.5 bg-amber-500 text-white rounded-lg text-xs font-bold hover:bg-amber-600 flex items-center gap-1.5">
                            <i class="fas fa-pen"></i> {{ $isAr ? 'تعديل' : 'Edit' }}
                        </a> --}}
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-400 text-sm">
                    <i class="fas fa-inbox text-3xl mb-3 block"></i>
                    {{ $isAr ? 'لا توجد أوامر بيع بعد' : 'No sales orders yet' }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- Pagination --}}
@if($orders->hasPages())
<div class="mt-4">{{ $orders->withQueryString()->links() }}</div>
@endif

@endsection
