@extends('layouts.app')
@section('header_title', __('messages.quotations.sent_log_title'))

@php $isAr = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in">

    {{-- ترويسة الشاشة --}}
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-600">
                <i class="fas fa-paper-plane text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.quotations.sent_log_title') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('messages.quotations.sent_log_sub') }}</p>
            </div>
        </div>
        <a href="{{ route('quotations.index') }}" class="px-5 py-2.5 border border-gray-300 rounded-lg font-bold text-gray-600 hover:bg-gray-50 transition-colors flex items-center gap-2">
            <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ __('messages.quotations.back') }}
        </a>
    </div>

    {{-- شريط البحث --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('quotations.sent-log') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.quick_search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('messages.quotations.sl_number') }} / {{ __('messages.quotations.sl_client') }} / {{ __('messages.quotations.sl_to') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-amber-500 bg-gray-50">
            </div>
            <div class="flex gap-2">
                <a href="{{ route('quotations.sent-log') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">{{ __('messages.filter.cancel') }}</a>
                <button type="submit" class="px-6 py-2 bg-amber-500 text-white rounded-lg text-sm font-bold hover:bg-amber-600 flex items-center gap-2">
                    <i class="fas fa-search"></i> {{ __('messages.common.apply') }}
                </button>
            </div>
        </form>
    </div>

    {{-- الجدول --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-{{ $isAr ? 'right' : 'left' }} border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ __('messages.quotations.sl_number') }}</th>
                        <th class="p-4">{{ __('messages.quotations.sl_client') }}</th>
                        <th class="p-4">{{ __('messages.quotations.sl_to') }}</th>
                        <th class="p-4 text-center">{{ __('messages.quotations.sl_cc') }}</th>
                        <th class="p-4">{{ __('messages.quotations.sl_by') }}</th>
                        <th class="p-4 text-{{ $isAr ? 'left' : 'right' }}">{{ __('messages.quotations.sl_total') }}</th>
                        <th class="p-4">{{ __('messages.quotations.sl_when') }}</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($sends as $s)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="p-4 font-mono font-bold text-[#008A3B]">{{ $s->quote_number }}</td>
                            <td class="p-4 font-bold text-gray-900">{{ $s->client_name ?? '—' }}</td>
                            <td class="p-4 text-gray-600" dir="ltr">
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="fas fa-at text-gray-300 text-xs"></i>{{ $s->sent_to }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                @php $ccCount = is_array($s->cc_emails) ? count($s->cc_emails) : 0; @endphp
                                @if($ccCount > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-[#005B9F]"
                                        title="{{ is_array($s->cc_emails) ? implode(', ', $s->cc_emails) : '' }}">
                                        <i class="fas fa-users text-[10px]"></i> {{ $ccCount }} {{ __('messages.quotations.sl_count_cc') }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="p-4 text-gray-500">{{ $s->sent_by ?? '—' }}</td>
                            <td class="p-4 text-{{ $isAr ? 'left' : 'right' }} font-bold text-gray-900" dir="ltr">
                                {{ number_format($s->grand_total, 2) }} {{ $s->currency }}
                            </td>
                            <td class="p-4 text-gray-500 whitespace-nowrap" dir="ltr">
                                <div class="font-medium text-gray-700">{{ $s->sent_at->format('Y-m-d') }}</div>
                                <div class="text-xs text-gray-400">{{ $s->sent_at->format('h:i A') }}</div>
                            </td>
                            <td class="p-4 text-{{ $isAr ? 'left' : 'right' }}">
                                @if($s->quotation)
                                    <a href="{{ route('quotations.show', $s->quotation) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-[#005B9F] bg-blue-50 hover:bg-blue-100 transition-colors">
                                        <i class="fas fa-eye"></i> {{ __('messages.quotations.sl_view') }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-12 text-center text-gray-400">
                            <i class="fas fa-paper-plane text-4xl mb-3 opacity-30 block"></i>
                            {{ __('messages.quotations.sl_no_data') }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sends->hasPages()) <div class="p-4 border-t border-gray-100">{{ $sends->links() }}</div> @endif
    </div>
</div>
@endsection
