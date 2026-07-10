@extends('layouts.app')
@section('header_title', $client->displayName())

@php
    $isAr = app()->getLocale() === 'ar';
    $cur  = $client->default_currency ?? 'EGP';
    $statusMap = [
        'draft'     => ['label' => __('messages.quotations.st_draft'),     'cls' => 'bg-gray-100 text-gray-600',  'dot' => 'bg-gray-400'],
        'sent'      => ['label' => __('messages.quotations.st_sent'),      'cls' => 'bg-blue-50 text-[#005B9F]',  'dot' => 'bg-blue-500'],
        'approved'  => ['label' => __('messages.quotations.st_approved'),  'cls' => 'bg-green-50 text-green-600', 'dot' => 'bg-green-500'],
        'rejected'  => ['label' => __('messages.quotations.st_rejected'),  'cls' => 'bg-red-50 text-red-600',     'dot' => 'bg-red-500'],
        'converted' => ['label' => __('messages.quotations.st_converted'), 'cls' => 'bg-green-50 text-green-600', 'dot' => 'bg-green-500'],
        'cancelled' => ['label' => __('messages.quotations.st_cancelled'), 'cls' => 'bg-red-50 text-red-600',     'dot' => 'bg-red-500'],
        'expired'   => ['label' => __('messages.quotations.st_expired'),   'cls' => 'bg-amber-50 text-amber-600', 'dot' => 'bg-amber-500'],
    ];
@endphp

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in">

    {{-- بطاقة بيانات العميل --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="h-1.5 bg-gradient-to-r from-[#005B9F] to-[#008A3B]"></div>
        <div class="p-6 flex flex-col md:flex-row md:items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F] shrink-0">
                <i class="fas fa-building text-2xl"></i>
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-900">{{ $client->displayName() }}</h2>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 mt-1.5 text-sm text-gray-500">
                    @if($client->contact_person)
                        <span class="flex items-center gap-1.5"><i class="fas fa-user text-gray-300 text-xs"></i> {{ $client->contact_person }}</span>
                    @endif
                    @if($client->phone)
                        <span class="flex items-center gap-1.5" dir="ltr"><i class="fas fa-phone text-gray-300 text-xs"></i> {{ $client->phone }}</span>
                    @endif
                    @if($client->email)
                        <span class="flex items-center gap-1.5" dir="ltr"><i class="fas fa-at text-gray-300 text-xs"></i> {{ $client->email }}</span>
                    @endif
                </div>
            </div>
            <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-50 flex items-center gap-2 shrink-0">
                <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ __('messages.quotations.back') }}
            </a>
        </div>
    </div>

    {{-- بطاقات الإحصائيات --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-400 font-bold mb-1">{{ __('messages.quotations.cq_stat_count') }}</p>
            <p class="text-2xl font-extrabold text-gray-900">{{ number_format($stats['count']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-400 font-bold mb-1">{{ __('messages.quotations.cq_stat_total') }}</p>
            <p class="text-2xl font-extrabold text-[#005B9F]" dir="ltr">{{ number_format($stats['total'], 2) }} <span class="text-xs text-gray-400">{{ $cur }}</span></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-400 font-bold mb-1">{{ __('messages.quotations.cq_stat_approved') }}</p>
            <p class="text-2xl font-extrabold text-green-600">{{ number_format($stats['approved']) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <p class="text-xs text-gray-400 font-bold mb-1">{{ __('messages.quotations.cq_stat_sent') }}</p>
            <p class="text-2xl font-extrabold text-[#005B9F]">{{ number_format($stats['sent']) }}</p>
        </div>
    </div>

    {{-- جدول العروض --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <i class="fas fa-file-invoice-dollar text-[#008A3B]"></i>
            <span class="font-bold text-gray-800">{{ __('messages.quotations.client_quotes_title') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-{{ $isAr ? 'right' : 'left' }} border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ __('messages.quotations.f_number') }}</th>
                        <th class="p-4">{{ __('messages.quotations.col_date') }}</th>
                        <th class="p-4">{{ __('messages.quotations.show_valid') }}</th>
                        <th class="p-4 text-{{ $isAr ? 'left' : 'right' }}">{{ __('messages.quotations.col_total') }}</th>
                        <th class="p-4 text-center">{{ __('messages.quotations.col_status') }}</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($quotations as $q)
                        @php $cls = $statusMap[$q->status] ?? $statusMap['draft']; @endphp
                        <tr onclick="window.location='{{ route('quotations.show', $q) }}'" class="hover:bg-green-50/40 cursor-pointer transition-colors">
                            <td class="p-4 font-mono font-bold text-[#008A3B]">{{ $q->quote_number }}</td>
                            <td class="p-4 text-gray-500" dir="ltr">{{ optional($q->quote_date)->format('Y-m-d') }}</td>
                            <td class="p-4 text-gray-500" dir="ltr">{{ optional($q->expiry_date)->format('Y-m-d') ?? '—' }}</td>
                            <td class="p-4 text-{{ $isAr ? 'left' : 'right' }} font-bold text-gray-900" dir="ltr">{{ number_format($q->grand_total, 2) }} {{ $q->currency }}</td>
                            <td class="p-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold {{ $cls['cls'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $cls['dot'] }}"></span> {{ $cls['label'] }}
                                </span>
                            </td>
                            <td class="p-4 text-{{ $isAr ? 'left' : 'right' }}">
                                <i class="fas fa-angle-{{ $isAr ? 'left' : 'right' }} text-gray-300"></i>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-12 text-center text-gray-400">
                            <i class="fas fa-file-invoice-dollar text-4xl mb-3 opacity-30 block"></i>
                            {{ __('messages.quotations.no_data') }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quotations->hasPages()) <div class="p-4 border-t border-gray-100">{{ $quotations->links() }}</div> @endif
    </div>
</div>
@endsection
