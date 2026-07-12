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

    {{-- ===== المحافظ والأرصدة ===== --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <p class="font-bold text-gray-800 text-sm flex items-center gap-2">
                <i class="fas fa-wallet text-gray-400 text-xs"></i>
                الحسابات البنكية والصناديق المالية (الأرصدة الحالية)
            </p>
            <a href="{{ route('settings.index') }}#wallets" class="text-[10px] bg-blue-50 text-[#005B9F] hover:bg-blue-100 px-2.5 py-1 rounded-full font-bold transition-colors flex items-center gap-1">
                <i class="fas fa-cog"></i> إعدادات الحسابات
            </a>
        </div>

        @if($wallets->isEmpty())
            <div class="px-6 py-14 text-center text-gray-400">
                <i class="fas fa-wallet text-3xl mb-2 block text-gray-200"></i>
                <p class="text-sm">لا توجد محافظ مسجلة بعد</p>
            </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 p-6 bg-gray-50/50">
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

    {{-- ===== وصول سريع (صف كامل تحت المحافظ، بدون سكرول) ===== --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <p class="font-bold text-gray-700 text-sm flex items-center gap-2">
                <i class="fas fa-bolt text-amber-400 text-xs"></i>
                {{ __('messages.dashboard.quick_links') }}
            </p>
        </div>

        @php
        $quickLinks = [
            ['href' => route('quotations.create'),       'icon' => 'fa-file-invoice-dollar', 'cls' => 'text-purple-600 bg-purple-50', 'label' => 'عرض سعر جديد'],
            ['href' => route('clients.create'),          'icon' => 'fa-user-plus',            'cls' => 'text-[#005B9F] bg-blue-50',    'label' => __('messages.dashboard.add_client')],
            ['href' => route('vendors.create'),          'icon' => 'fa-truck',                'cls' => 'text-[#008A3B] bg-green-50',   'label' => __('messages.dashboard.add_vendor')],
            ['href' => route('items.create'),            'icon' => 'fa-plus-circle',          'cls' => 'text-amber-500 bg-amber-50',   'label' => __('messages.dashboard.add_item')],
            ['href' => route('price-lists.create'),      'icon' => 'fa-tags',                 'cls' => 'text-indigo-500 bg-indigo-50', 'label' => 'قائمة أسعار جديدة'],
            ['href' => route('expenses.create'),         'icon' => 'fa-receipt',              'cls' => 'text-red-500 bg-red-50',       'label' => 'تسجيل مصروف'],
            ['href' => route('vendor-payments.create'),  'icon' => 'fa-money-check-alt',      'cls' => 'text-red-600 bg-red-50',       'label' => 'سند دفع لمورد'],
            ['href' => route('wallet-transfers.create'), 'icon' => 'fa-exchange-alt',         'cls' => 'text-amber-600 bg-amber-50',   'label' => 'تحويل بين حسابات'],
            ['href' => route('financial-logs.index'),    'icon' => 'fa-chart-line',           'cls' => 'text-purple-600 bg-purple-50', 'label' => 'سجل الماليات'],
            ['href' => route('sourcing.index'),          'icon' => 'fa-network-wired',        'cls' => 'text-amber-500 bg-amber-50',   'label' => 'ربط الموردين بالأصناف'],
            ['href' => route('reports.index'),           'icon' => 'fa-chart-pie',            'cls' => 'text-purple-600 bg-purple-50', 'label' => 'التقارير والتحليلات'],
            ['href' => route('settings.index'),          'icon' => 'fa-cogs',                 'cls' => 'text-gray-500 bg-gray-100',    'label' => 'الإعدادات'],
        ];
        @endphp

        <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 divide-x divide-x-reverse divide-y divide-gray-50">
            @foreach($quickLinks as $lnk)
            <a href="{{ $lnk['href'] }}" class="flex flex-col items-center justify-center gap-2 py-5 px-2 hover:bg-gray-50 transition-colors group text-center">
                <div class="w-10 h-10 rounded-xl {{ $lnk['cls'] }} flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas {{ $lnk['icon'] }} text-base"></i>
                </div>
                <span class="text-[11px] font-semibold text-gray-700 leading-tight">{{ $lnk['label'] }}</span>
            </a>
            @endforeach
        </div>
    </div>

    {{-- ===== حالة عروض الأسعار (صف أفقي) ===== --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100">
            <p class="font-bold text-gray-800 text-sm flex items-center gap-2">
                <i class="fas fa-chart-pie text-purple-400 text-xs"></i>
                حالة عروض الأسعار
            </p>
        </div>
        @php
        $stDef = [
            'draft'    => ['label' => 'مسودة',  'icon' => 'fa-pencil-alt',   'cls' => 'bg-gray-50   text-gray-600'],
            'sent'     => ['label' => 'مرسل',   'icon' => 'fa-paper-plane',  'cls' => 'bg-blue-50   text-blue-600'],
            'approved' => ['label' => 'معتمد',  'icon' => 'fa-check-circle', 'cls' => 'bg-green-50  text-green-700'],
            'rejected' => ['label' => 'مرفوض', 'icon' => 'fa-times-circle', 'cls' => 'bg-red-50    text-red-600'],
            'expired'  => ['label' => 'منتهي',  'icon' => 'fa-clock',        'cls' => 'bg-amber-50  text-amber-700'],
        ];
        @endphp
        @if($quotationsByStatus->isEmpty())
            <div class="px-6 py-8 text-center">
                <p class="text-sm text-gray-400 mb-2">لا توجد عروض أسعار</p>
                <a href="{{ route('quotations.create') }}" class="inline-flex items-center gap-1.5 text-xs text-[#005B9F] font-bold hover:underline">
                    <i class="fas fa-plus-circle"></i> إنشاء أول عرض سعر
                </a>
            </div>
        @else
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 p-5 bg-gray-50/50">
            @foreach($stDef as $key => $s)
            @php $cnt = $quotationsByStatus->get($key, 0); @endphp
            <a href="{{ route('quotations.index') }}?status={{ $key }}"
                class="flex items-center gap-2 px-3 py-3 rounded-xl {{ $s['cls'] }} hover:opacity-80 transition-opacity">
                <i class="fas {{ $s['icon'] }} text-sm w-4 text-center"></i>
                <span class="text-xs font-semibold flex-1">{{ $s['label'] }}</span>
                <span class="text-lg font-black">{{ $cnt }}</span>
            </a>
            @endforeach
        </div>
        @endif
    </div>

</div>
@endsection
