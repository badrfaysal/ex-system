@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'سندات الدفع للموردين' : 'Vendor Payments')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-red-500/10 flex items-center justify-center text-red-600">
                <i class="fas fa-money-check-alt text-2xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'سندات الدفع للموردين' : 'Vendor Payments' }}</h2>
        </div>
        <a href="{{ route('vendor-payments.create') }}" class="px-6 py-2.5 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> {{ $isAr ? 'تسجيل سند دفع' : 'Record Payment' }}
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ $isAr ? 'رقم السند' : 'No.' }}</th>
                        <th class="p-4">{{ $isAr ? 'المورد' : 'Vendor' }}</th>
                        <th class="p-4">{{ $isAr ? 'المبلغ' : 'Amount' }}</th>
                        <th class="p-4">{{ $isAr ? 'التاريخ' : 'Date' }}</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($payments as $payment)
                        <tr class="hover:bg-red-50/30">
                            <td class="p-4 font-mono font-bold text-gray-800" dir="ltr">{{ $payment->payment_number }}</td>
                            <td class="p-4">
                                <a href="{{ route('payables.show', $payment->vendor_id) }}" class="text-[#005B9F] hover:underline font-bold">
                                    {{ $isAr ? optional($payment->vendor)->name_ar : (optional($payment->vendor)->name_en ?: optional($payment->vendor)->name_ar) }}
                                </a>
                            </td>
                            <td class="p-4 font-bold text-red-600" dir="ltr">{{ number_format($payment->amount, 2) }} <span class="text-xs text-gray-400">{{ $payment->currency }}</span></td>
                            <td class="p-4 text-gray-500" dir="ltr">{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td class="p-4 text-left">
                                <a href="{{ route('vendor-payments.edit', $payment) }}" class="text-gray-400 hover:text-red-600" title="{{ $isAr ? 'تعديل' : 'Edit' }}"><i class="fas fa-pen"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد سندات دفع' : 'No payments yet' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages()) <div class="p-4 border-t border-gray-100 bg-white">{{ $payments->links() }}</div> @endif
    </div>
</div>
@endsection
