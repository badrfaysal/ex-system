@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'مراكز التكلفة' : 'Cost Centers')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-600">
                <i class="fas fa-layer-group text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'مراكز التكلفة' : 'Cost Centers' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'كل عرض سعر هو مركز تكلفة مستقل — إيراد مقابل تكلفة' : 'Each quotation is an independent cost center — revenue vs. cost' }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ $isAr ? 'مركز التكلفة' : 'Cost Center' }}</th>
                        <th class="p-4">{{ $isAr ? 'عرض السعر' : 'Quotation' }}</th>
                        <th class="p-4">{{ $isAr ? 'العميل' : 'Client' }}</th>
                        <th class="p-4">{{ $isAr ? 'الإيراد' : 'Revenue' }}</th>
                        <th class="p-4">{{ $isAr ? 'التكلفة' : 'Cost' }}</th>
                        <th class="p-4">{{ $isAr ? 'الربح / الخسارة' : 'Profit / Loss' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($quotations as $q)
                        <tr class="hover:bg-purple-50/30 cursor-pointer" onclick="location.href='{{ route('cost-centers.show', $q) }}'">
                            <td class="p-4 font-bold text-gray-900">{{ $q->cost_center_name ?: '—' }}</td>
                            <td class="p-4 font-mono text-[#005B9F]" dir="ltr">{{ $q->quote_number }}</td>
                            <td class="p-4 text-gray-700">{{ optional($q->client)->displayName($isAr ? 'ar' : 'en') }}</td>
                            <td class="p-4 font-bold text-green-600" dir="ltr">{{ number_format($q->revenue, 2) }}</td>
                            <td class="p-4 font-bold text-red-600" dir="ltr">{{ number_format($q->cost, 2) }}</td>
                            <td class="p-4 font-extrabold {{ $q->profit >= 0 ? 'text-green-700' : 'text-red-700' }}" dir="ltr">
                                {{ $q->profit >= 0 ? '+' : '' }}{{ number_format($q->profit, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد بيانات' : 'No data yet' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quotations->hasPages()) <div class="p-4 border-t border-gray-100 bg-white">{{ $quotations->links() }}</div> @endif
    </div>
</div>
@endsection
