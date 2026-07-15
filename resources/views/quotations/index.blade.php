@extends('layouts.app')
@section('header_title', __('messages.quotations.title'))

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in">

    {{-- ترويسة الشاشة --}}
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
                <i class="fas fa-file-invoice-dollar text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.quotations.title') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('messages.quotations.subtitle') }}</p>
            </div>
        </div>
        <a href="{{ route('quotations.create') }}" class="px-6 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030] transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> {{ __('messages.quotations.add') }}
        </a>
    </div>

    {{-- شريط البحث والفلترة --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('quotations.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.quick_search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.quotations.f_number') }} / {{ __('messages.quotations.col_client') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <div class="w-36">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.common.date_from') ?? 'Date From' }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <div class="w-36">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.common.date_to') ?? 'Date To' }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.quotations.status') }}</label>
                <select name="status" class="w-44 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50">
                    <option value="">{{ __('messages.quotations.all_status') }}</option>
                    <option value="draft" {{ request('status')=='draft'?'selected':'' }}>{{ __('messages.quotations.st_draft') }}</option>
                    <option value="sent" {{ request('status')=='sent'?'selected':'' }}>{{ __('messages.quotations.st_sent') }}</option>
                    <option value="approved" {{ request('status')=='approved'?'selected':'' }}>{{ __('messages.quotations.st_approved') }}</option>
                    <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>{{ __('messages.quotations.st_rejected') }}</option>
                    <option value="converted" {{ request('status')=='converted'?'selected':'' }}>{{ __('messages.quotations.st_converted') }}</option>
                    <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>{{ __('messages.quotations.st_cancelled') }}</option>
                </select>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('quotations.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">{{ __('messages.filter.cancel') }}</a>
                <button type="submit" class="px-6 py-2 bg-[#005B9F] text-white rounded-lg text-sm font-bold hover:bg-blue-800 flex items-center gap-2">
                    <i class="fas fa-filter"></i> {{ __('messages.common.apply') }}
                </button>
            </div>
        </form>
    </div>

    {{-- جدول العروض --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ __('messages.quotations.f_number') }}</th>
                        <th class="p-4">{{ __('messages.quotations.col_client') }}</th>
                        <th class="p-4">{{ __('messages.quotations.col_date') }}</th>
                        <th class="p-4 text-left">{{ __('messages.quotations.col_total') }}</th>
                        <th class="p-4 text-center">{{ __('messages.quotations.col_status') }}</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($quotations as $q)
                        <tr onclick="window.location='{{ route('quotations.show', $q) }}'" class="hover:bg-green-50/40 cursor-pointer transition-colors group">
                            <td class="p-4 font-mono font-bold text-[#008A3B]">{{ $q->quote_number }}</td>
                            <td class="p-4 font-bold text-gray-900">{{ optional($q->client)->displayName() ?? '—' }}</td>
                            <td class="p-4 text-gray-500" dir="ltr">{{ optional($q->quote_date)->format('Y-m-d') }}</td>
                            <td class="p-4 text-left font-bold text-gray-900" dir="ltr">{{ number_format($q->grand_total, 2) }} {{ $q->currency }}</td>
                            <td class="p-4 text-center">
                                @php
                                    $map = [
                                        'draft'        => ['bg-gray-100 text-gray-600', 'bg-gray-400'],
                                        'under_review' => ['bg-amber-50 text-amber-600', 'bg-amber-500'],
                                        'sent'         => ['bg-blue-50 text-[#005B9F]', 'bg-blue-500'],
                                        'approved'     => ['bg-green-50 text-green-600', 'bg-green-500'],
                                        'rejected'     => ['bg-red-50 text-red-600', 'bg-red-500'],
                                        'converted'    => ['bg-green-50 text-green-600', 'bg-green-500'],
                                        'cancelled'    => ['bg-red-50 text-red-600', 'bg-red-500'],
                                        'expired'      => ['bg-amber-50 text-amber-600', 'bg-amber-500'],
                                    ];
                                    $cls = $map[$q->status] ?? $map['draft'];
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold {{ $cls[0] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $cls[1] }}"></span> {{ __('messages.quotations.st_'.$q->status) }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity" onclick="event.stopPropagation()">
                                    <a href="{{ route('quotations.show', $q) }}" class="p-2 rounded-lg text-gray-400 hover:text-[#005B9F] hover:bg-blue-50" title="{{ __('messages.quotations.sl_view') }}"><i class="fas fa-eye"></i></a>
                                    @if(\App\Models\PeriodLock::isDateLocked($q->quote_date))
                                        <span class="p-2 rounded-lg text-red-400 cursor-not-allowed" title="مغلق مالياً"><i class="fas fa-lock"></i></span>
                                    @elseif($q->status === 'draft')
                                        <a href="{{ route('quotations.edit', $q) }}" class="p-2 rounded-lg text-gray-400 hover:text-[#008A3B] hover:bg-green-50" title="{{ __('messages.common.edit') }}"><i class="fas fa-pen"></i></a>
                                    @else
                                        <span class="p-2 rounded-lg text-gray-300 cursor-not-allowed" title="{{ __('messages.quotations.lock_draft_only') }}"><i class="fas fa-lock"></i></span>
                                    @endif
                                </div>
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
