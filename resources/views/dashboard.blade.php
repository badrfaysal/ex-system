@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in">

    {{-- ترويسة --}}
    <div class="mb-6 bg-white px-6 py-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ __('messages.dashboard.welcome_title') }}</h2>
            <p class="text-sm text-gray-400 mt-0.5">{{ __('messages.dashboard.welcome_sub') }}</p>
        </div>
        <div class="flex items-center gap-2 bg-[#EBF7F0] text-[#008A3B] px-4 py-2 rounded-xl font-bold text-sm">
            <i class="far fa-calendar-alt"></i>
            <span>{{ now()->format('Y-m-d') }}</span>
        </div>
    </div>

    {{-- ===== كروت الإحصائيات (6 كروت) ===== --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">

        @php
        $statCards = [
            [
                'label' => __('messages.dashboard.total_clients'),
                'value' => $clientsCount,
                'icon'  => 'fa-users',
                'ring'  => 'bg-blue-50 text-[#005B9F]',
                'href'  => route('clients.index'),
                'note'  => null,
            ],
            [
                'label' => __('messages.dashboard.total_vendors'),
                'value' => $vendorsCount,
                'icon'  => 'fa-truck',
                'ring'  => 'bg-green-50 text-[#008A3B]',
                'href'  => route('vendors.index'),
                'note'  => null,
            ],
            [
                'label' => __('messages.dashboard.registered_items'),
                'value' => $itemsCount,
                'icon'  => 'fa-box',
                'ring'  => 'bg-amber-50 text-amber-500',
                'href'  => route('items.index'),
                'note'  => null,
            ],
            [
                'label' => __('messages.dashboard.quotations'),
                'value' => $quotationsCount,
                'icon'  => 'fa-file-invoice-dollar',
                'ring'  => 'bg-purple-50 text-purple-500',
                'href'  => route('quotations.index'),
                'note'  => $quotationsByStatus->get('draft', 0) > 0 ? $quotationsByStatus->get('draft').' مسودة' : null,
            ],
            [
                'label' => __('messages.dashboard.price_lists'),
                'value' => $priceListsCount,
                'icon'  => 'fa-tags',
                'ring'  => 'bg-indigo-50 text-indigo-500',
                'href'  => route('price-lists.index'),
                'note'  => null,
            ],
            [
                'label' => __('messages.dashboard.active_items'),
                'value' => $activeItems,
                'icon'  => 'fa-check-circle',
                'ring'  => 'bg-teal-50 text-teal-500',
                'href'  => route('items.index'),
                'note'  => null,
            ],
        ];
        @endphp

        @foreach($statCards as $card)
        <a href="{{ $card['href'] }}"
            class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all group flex flex-col justify-between min-h-[110px]">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl {{ $card['ring'] }} flex items-center justify-center">
                    <i class="fas {{ $card['icon'] }} text-base"></i>
                </div>
                <span class="text-3xl font-black text-gray-900 leading-none">{{ $card['value'] }}</span>
            </div>
            <div class="mt-3">
                <p class="text-xs font-semibold text-gray-500 leading-tight">{{ $card['label'] }}</p>
                @if($card['note'])
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $card['note'] }}</p>
                @endif
            </div>
        </a>
        @endforeach

    </div>

    {{-- ===== وصول سريع (أفقي بالعرض الكامل) ===== --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100">
            <p class="font-bold text-gray-700 text-sm flex items-center gap-2">
                <i class="fas fa-bolt text-amber-400 text-xs"></i>
                {{ __('messages.dashboard.quick_links') }}
            </p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 divide-x divide-x-reverse divide-gray-100">

            @php
            $quickLinks = [
                ['href' => route('quotations.create'),  'icon' => 'fa-file-invoice-dollar', 'ring' => 'text-purple-500 bg-purple-50', 'hover' => 'hover:bg-purple-50', 'label' => 'عرض سعر جديد'],
                ['href' => route('clients.create'),     'icon' => 'fa-user-plus',            'ring' => 'text-[#005B9F] bg-blue-50',   'hover' => 'hover:bg-blue-50',   'label' => __('messages.dashboard.add_client')],
                ['href' => route('vendors.create'),     'icon' => 'fa-truck',                'ring' => 'text-[#008A3B] bg-green-50',  'hover' => 'hover:bg-green-50',  'label' => __('messages.dashboard.add_vendor')],
                ['href' => route('items.create'),       'icon' => 'fa-plus-circle',          'ring' => 'text-amber-500 bg-amber-50',  'hover' => 'hover:bg-amber-50',  'label' => __('messages.dashboard.add_item')],
                ['href' => route('price-lists.create'), 'icon' => 'fa-tags',                 'ring' => 'text-indigo-500 bg-indigo-50','hover' => 'hover:bg-indigo-50', 'label' => 'قائمة أسعار جديدة'],
                ['href' => route('settings.index'),     'icon' => 'fa-cogs',                 'ring' => 'text-gray-500 bg-gray-100',   'hover' => 'hover:bg-gray-50',   'label' => 'الإعدادات'],
            ];
            @endphp

            @foreach($quickLinks as $lnk)
            <a href="{{ $lnk['href'] }}"
                class="flex flex-col items-center justify-center gap-2 py-5 px-3 {{ $lnk['hover'] }} transition-colors group text-center">
                <div class="w-10 h-10 rounded-xl {{ $lnk['ring'] }} flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas {{ $lnk['icon'] }} text-base"></i>
                </div>
                <span class="text-xs font-semibold text-gray-700 leading-tight">{{ $lnk['label'] }}</span>
            </a>
            @endforeach

        </div>
    </div>

    {{-- ===== الجسم: آخر العمليات + توزيع عروض الأسعار ===== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">

        {{-- المحافظ والأرصدة --}}
        <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <p class="font-bold text-gray-800 text-sm flex items-center gap-2">
                    <i class="fas fa-wallet text-gray-400 text-xs"></i>
                    المحافظ والصناديق (الأرصدة الحالية)
                </p>
                <a href="{{ route('settings.index') }}#wallets" class="text-[10px] bg-blue-50 text-[#005B9F] hover:bg-blue-100 px-2.5 py-1 rounded-full font-bold transition-colors flex items-center gap-1">
                    <i class="fas fa-cog"></i> إعدادات المحافظ
                </a>
            </div>

            @if($wallets->isEmpty())
                <div class="px-6 py-14 text-center text-gray-400">
                    <i class="fas fa-wallet text-3xl mb-2 block text-gray-200"></i>
                    <p class="text-sm">لا توجد محافظ مسجلة بعد</p>
                </div>
            @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6 bg-gray-50/50">
                @foreach($wallets as $wallet)
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#EBF7F0] text-[#008A3B] flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">{{ $wallet->name }}</p>
                            <p class="text-xs text-gray-400">
                                رصيد أول المدة: <span dir="ltr">{{ number_format($wallet->opening_balance, 2) }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="text-left">
                        <p class="text-xs text-gray-400 mb-0.5">الرصيد الحالي</p>
                        <p class="font-black text-lg text-[#005B9F]" dir="ltr">
                            {{ number_format($wallet->current_balance, 2) }} <span class="text-xs text-gray-500 font-normal">{{ $wallet->currency }}</span>
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- توزيع عروض الأسعار حسب الحالة --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <p class="font-bold text-gray-800 text-sm flex items-center gap-2">
                    <i class="fas fa-chart-pie text-purple-400 text-xs"></i>
                    حالة عروض الأسعار
                </p>
            </div>
            @php
            $stDef = [
                'draft'    => ['label' => 'مسودة',  'icon' => 'fa-pencil-alt',   'cls' => 'bg-gray-50   text-gray-600',   'bar' => 'bg-gray-400'],
                'sent'     => ['label' => 'مرسل',   'icon' => 'fa-paper-plane',  'cls' => 'bg-blue-50   text-blue-600',   'bar' => 'bg-blue-400'],
                'approved' => ['label' => 'معتمد',  'icon' => 'fa-check-circle', 'cls' => 'bg-green-50  text-green-700',  'bar' => 'bg-green-500'],
                'rejected' => ['label' => 'مرفوض', 'icon' => 'fa-times-circle', 'cls' => 'bg-red-50    text-red-600',    'bar' => 'bg-red-400'],
                'expired'  => ['label' => 'منتهي',  'icon' => 'fa-clock',        'cls' => 'bg-amber-50  text-amber-700',  'bar' => 'bg-amber-400'],
            ];
            @endphp
            <div class="p-4 space-y-2">
                @forelse($stDef as $key => $s)
                @php $cnt = $quotationsByStatus->get($key, 0); @endphp
                <a href="{{ route('quotations.index') }}?status={{ $key }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ $s['cls'] }} hover:opacity-80 transition-opacity">
                    <i class="fas {{ $s['icon'] }} text-sm w-4 text-center"></i>
                    <span class="text-sm font-semibold flex-1">{{ $s['label'] }}</span>
                    <span class="text-lg font-black">{{ $cnt }}</span>
                </a>
                @empty
                <p class="text-sm text-gray-400 text-center py-6">لا توجد عروض أسعار</p>
                @endforelse
            </div>
            @if($quotationsCount === 0)
            <div class="px-4 pb-4 text-center">
                <a href="{{ route('quotations.create') }}"
                    class="inline-flex items-center gap-2 text-xs text-[#005B9F] font-bold hover:underline">
                    <i class="fas fa-plus-circle"></i> إنشاء أول عرض سعر
                </a>
            </div>
            @endif
        </div>

    </div>

</div>
@endsection
