@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $fmt = fn($n) => number_format((float) $n, 2);
@endphp
@section('header_title', $isAr ? 'التقارير والتحليلات' : 'Reports & Analytics')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in">

    {{-- ===== ترويسة + فلتر الفترة ===== --}}
    <div class="mb-6 bg-white px-6 py-4 rounded-2xl border border-gray-100 shadow-sm flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                <i class="fas fa-chart-line text-2xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $isAr ? 'التقارير والتحليلات' : 'Reports & Analytics' }}</h2>
                <p class="text-sm text-gray-400 mt-0.5">{{ $isAr ? 'تحليل شامل لكل بيانات النظام — مبيعات، مشتريات، مخزون، ماليات، مراكز تكلفة' : 'A full breakdown of every dataset in the system' }}</p>
            </div>
        </div>
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[11px] font-bold text-gray-500 mb-1">{{ $isAr ? 'من تاريخ' : 'From' }}</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-purple-500">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-500 mb-1">{{ $isAr ? 'إلى تاريخ' : 'To' }}</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-purple-500">
            </div>
            <button type="submit" class="px-5 py-2.5 bg-purple-600 text-white rounded-lg font-bold text-sm hover:bg-purple-700 transition-colors flex items-center gap-2">
                <i class="fas fa-filter"></i> {{ $isAr ? 'تطبيق' : 'Apply' }}
            </button>
        </form>
    </div>

    <p class="text-[11px] text-gray-400 mb-4 -mt-2">{{ $isAr ? 'المقاييس اللحظية (الأرصدة، المخزون، المستحقات الحالية) بتعكس آخر حالة دايمًا. المقاييس الزمنية (العروض، الفواتير، المصروفات) بتخص الفترة المختارة فقط.' : 'Point-in-time metrics always reflect current state. Flow metrics respect the selected date range.' }}</p>

    {{-- ===== كروت المؤشرات الرئيسية ===== --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
        @php
        $kpiCards = [
            ['label' => $isAr ? 'العملاء (إجمالي / جدد)' : 'Clients (Total / New)', 'value' => $kpis['clients_total'], 'sub' => '+' . $kpis['clients_new'], 'icon' => 'fa-users', 'cls' => 'bg-blue-50 text-[#005B9F]'],
            ['label' => $isAr ? 'الموردون' : 'Vendors', 'value' => $kpis['vendors_total'], 'sub' => null, 'icon' => 'fa-truck', 'cls' => 'bg-green-50 text-[#008A3B]'],
            ['label' => $isAr ? 'الأصناف (نشط)' : 'Items (Active)', 'value' => $kpis['items_total'], 'sub' => $kpis['items_active'] . ' ' . ($isAr ? 'نشط' : 'active'), 'icon' => 'fa-box', 'cls' => 'bg-amber-50 text-amber-500'],
            ['label' => $isAr ? 'عروض الأسعار (الفترة)' : 'Quotations (Period)', 'value' => $kpis['quotations_count'], 'sub' => $fmt($kpis['quotations_value']), 'icon' => 'fa-file-invoice-dollar', 'cls' => 'bg-purple-50 text-purple-500'],
            ['label' => $isAr ? 'أوامر البيع (الفترة)' : 'Sales Orders (Period)', 'value' => $kpis['sales_orders_count'], 'sub' => null, 'icon' => 'fa-file-contract', 'cls' => 'bg-indigo-50 text-indigo-500'],
            ['label' => $isAr ? 'فواتير البيع (الفترة)' : 'Sales Invoices (Period)', 'value' => $kpis['sales_invoices_count'], 'sub' => $fmt($kpis['sales_invoices_value']), 'icon' => 'fa-file-invoice', 'cls' => 'bg-teal-50 text-teal-600'],
            ['label' => $isAr ? 'فواتير الشراء (الفترة)' : 'Purchase Invoices (Period)', 'value' => $kpis['purchase_invoices_count'], 'sub' => $fmt($kpis['purchase_invoices_value']), 'icon' => 'fa-file-invoice', 'cls' => 'bg-blue-50 text-[#005B9F]'],
            ['label' => $isAr ? 'صافي التدفق النقدي (الفترة)' : 'Net Cash Flow (Period)', 'value' => $fmt($kpis['net_cash_flow']), 'sub' => null, 'icon' => 'fa-exchange-alt', 'cls' => $kpis['net_cash_flow'] >= 0 ? 'bg-green-50 text-[#008A3B]' : 'bg-red-50 text-red-600', 'small' => true],
            ['label' => $isAr ? 'مستحقات على العملاء' : 'Receivables Outstanding', 'value' => $fmt($kpis['receivables_outstanding']), 'sub' => null, 'icon' => 'fa-hand-holding-usd', 'cls' => 'bg-blue-50 text-[#005B9F]', 'small' => true],
            ['label' => $isAr ? 'التزامات للموردين' : 'Payables Outstanding', 'value' => $fmt($kpis['payables_outstanding']), 'sub' => null, 'icon' => 'fa-file-invoice-dollar', 'cls' => 'bg-red-50 text-red-600', 'small' => true],
        ];
        @endphp
        @foreach($kpiCards as $card)
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between min-h-[110px]">
            <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl {{ $card['cls'] }} flex items-center justify-center">
                    <i class="fas {{ $card['icon'] }} text-base"></i>
                </div>
                <span class="{{ ($card['small'] ?? false) ? 'text-xl' : 'text-2xl' }} font-black text-gray-900 leading-none" dir="ltr">{{ $card['value'] }}</span>
            </div>
            <div class="mt-3">
                <p class="text-xs font-semibold text-gray-500 leading-tight">{{ $card['label'] }}</p>
                @if($card['sub'])<p class="text-[10px] text-gray-400 mt-0.5" dir="ltr">{{ $card['sub'] }}</p>@endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- ===== الرسوم البيانية: تدفق نقدي + اتجاه عروض الأسعار ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="font-bold text-gray-800 text-sm mb-4 flex items-center gap-2"><i class="fas fa-chart-bar text-green-500 text-xs"></i> {{ $isAr ? 'التدفق النقدي الشهري (آخر 6 شهور)' : 'Monthly Cash Flow (Last 6 Months)' }}</p>
            <canvas id="cashFlowChart" height="180"></canvas>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="font-bold text-gray-800 text-sm mb-4 flex items-center gap-2"><i class="fas fa-chart-line text-purple-500 text-xs"></i> {{ $isAr ? 'اتجاه عروض الأسعار (آخر 6 شهور)' : 'Quotation Trend (Last 6 Months)' }}</p>
            <canvas id="quotationChart" height="180"></canvas>
        </div>
    </div>

    {{-- ===== قمع المبيعات + أفضل العملاء/الموردين/الأصناف ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100"><p class="font-bold text-gray-800 text-sm flex items-center gap-2"><i class="fas fa-filter text-purple-400 text-xs"></i> {{ $isAr ? 'قمع عروض الأسعار (كل الفترات)' : 'Quotation Funnel (All Time)' }}</p></div>
            @php
                $stLabels = ['draft'=>['ar'=>'مسودة','cls'=>'bg-gray-50 text-gray-600'],'under_review'=>['ar'=>'قيد المراجعة','cls'=>'bg-yellow-50 text-yellow-700'],'sent'=>['ar'=>'مرسل','cls'=>'bg-blue-50 text-blue-600'],'approved'=>['ar'=>'معتمد','cls'=>'bg-green-50 text-green-700'],'rejected'=>['ar'=>'مرفوض','cls'=>'bg-red-50 text-red-600'],'converted'=>['ar'=>'محوّل','cls'=>'bg-teal-50 text-teal-700'],'cancelled'=>['ar'=>'ملغي','cls'=>'bg-gray-100 text-gray-500'],'expired'=>['ar'=>'منتهي','cls'=>'bg-amber-50 text-amber-700']];
                $funnelTotal = $salesFunnel->sum('cnt');
            @endphp
            <div class="p-4 space-y-2">
                @forelse($salesFunnel as $row)
                    @php $meta = $stLabels[$row->status] ?? ['ar'=>$row->status,'cls'=>'bg-gray-50 text-gray-600']; @endphp
                    <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ $meta['cls'] }}">
                        <span class="text-sm font-semibold flex-1">{{ $isAr ? $meta['ar'] : $row->status }}</span>
                        <span class="text-[11px] font-mono opacity-70" dir="ltr">{{ $fmt($row->total) }}</span>
                        <span class="text-lg font-black w-8 text-center">{{ $row->cnt }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-6">{{ $isAr ? 'لا توجد بيانات' : 'No data' }}</p>
                @endforelse
                @if($funnelTotal > 0)
                    @php $convertedCnt = $salesFunnel->firstWhere('status', 'converted')->cnt ?? 0; @endphp
                    <p class="text-[11px] text-gray-400 text-center pt-2">{{ $isAr ? 'معدل التحويل لأمر بيع' : 'Conversion rate' }}: <span class="font-bold text-gray-600">{{ round($convertedCnt / $funnelTotal * 100, 1) }}%</span></p>
                @endif
            </div>
        </div>

        <div class="grid grid-rows-3 gap-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <p class="font-bold text-gray-700 text-xs mb-2 flex items-center gap-2"><i class="fas fa-crown text-blue-400"></i> {{ $isAr ? 'أفضل 5 عملاء (الفترة)' : 'Top 5 Clients (Period)' }}</p>
                @forelse($topClients as $c)
                    <div class="flex justify-between text-xs py-1 border-b border-gray-50 last:border-0">
                        <span class="text-gray-700 font-semibold truncate">{{ $isAr ? $c->company_name : ($c->company_name_en ?: $c->company_name) }}</span>
                        <span class="font-mono text-[#005B9F] font-bold" dir="ltr">{{ $fmt($c->period_total) }}</span>
                    </div>
                @empty
                    <p class="text-[11px] text-gray-400 py-2">{{ $isAr ? 'لا توجد بيانات' : 'No data' }}</p>
                @endforelse
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <p class="font-bold text-gray-700 text-xs mb-2 flex items-center gap-2"><i class="fas fa-crown text-green-400"></i> {{ $isAr ? 'أفضل 5 موردين (الفترة)' : 'Top 5 Vendors (Period)' }}</p>
                @forelse($topVendors as $v)
                    <div class="flex justify-between text-xs py-1 border-b border-gray-50 last:border-0">
                        <span class="text-gray-700 font-semibold truncate">{{ $isAr ? $v->name_ar : ($v->name_en ?: $v->name_ar) }}</span>
                        <span class="font-mono text-[#008A3B] font-bold" dir="ltr">{{ $fmt($v->period_total) }}</span>
                    </div>
                @empty
                    <p class="text-[11px] text-gray-400 py-2">{{ $isAr ? 'لا توجد بيانات' : 'No data' }}</p>
                @endforelse
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <p class="font-bold text-gray-700 text-xs mb-2 flex items-center gap-2"><i class="fas fa-crown text-amber-400"></i> {{ $isAr ? 'أفضل 5 أصناف مبيعًا (الفترة)' : 'Top 5 Selling Items (Period)' }}</p>
                @forelse($topItems as $i)
                    <div class="flex justify-between text-xs py-1 border-b border-gray-50 last:border-0">
                        <span class="text-gray-700 font-semibold truncate">{{ $i->description }}</span>
                        <span class="font-mono text-amber-600 font-bold" dir="ltr">{{ number_format($i->total_qty, 0) }}</span>
                    </div>
                @empty
                    <p class="text-[11px] text-gray-400 py-2">{{ $isAr ? 'لا توجد بيانات' : 'No data' }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== توزيعات: الأصناف/الموردين/العملاء ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="font-bold text-gray-700 text-xs mb-3 flex items-center gap-2"><i class="fas fa-layer-group text-amber-400"></i> {{ $isAr ? 'الأصناف حسب المجموعة' : 'Items by Group' }}</p>
            @php $maxGroup = $itemsByGroup->max('cnt') ?: 1; @endphp
            <div class="space-y-2">
                @forelse($itemsByGroup as $g)
                    <div>
                        <div class="flex justify-between text-[11px] text-gray-600 mb-0.5"><span>{{ $g->item_group ?? ($isAr ? 'غير مصنف' : 'Uncategorized') }}</span><span class="font-bold">{{ $g->cnt }}</span></div>
                        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-amber-400" style="width: {{ $g->cnt / $maxGroup * 100 }}%"></div></div>
                    </div>
                @empty
                    <p class="text-[11px] text-gray-400">{{ $isAr ? 'لا توجد أصناف' : 'No items' }}</p>
                @endforelse
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="font-bold text-gray-700 text-xs mb-3 flex items-center gap-2"><i class="fas fa-truck text-green-400"></i> {{ $isAr ? 'الموردون حسب الحالة' : 'Vendors by Status' }}</p>
            @php $vStLabels = ['active'=>$isAr?'نشط':'Active','on_hold'=>$isAr?'موقوف مؤقتًا':'On Hold','blocked'=>$isAr?'محظور':'Blocked']; @endphp
            <div class="space-y-2">
                @forelse($vendorsByStatus as $s)
                    <div class="flex justify-between items-center text-xs py-1.5 px-3 rounded-lg bg-gray-50">
                        <span class="font-semibold text-gray-600">{{ $vStLabels[$s->status] ?? $s->status }}</span>
                        <span class="font-black text-gray-800">{{ $s->cnt }}</span>
                    </div>
                @empty
                    <p class="text-[11px] text-gray-400">{{ $isAr ? 'لا يوجد موردون' : 'No vendors' }}</p>
                @endforelse
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <p class="font-bold text-gray-700 text-xs mb-3 flex items-center gap-2"><i class="fas fa-users text-blue-400"></i> {{ $isAr ? 'العملاء حسب النوع' : 'Clients by Type' }}</p>
            @php $cTypeLabels = ['wholesale'=>$isAr?'جملة':'Wholesale','retail'=>$isAr?'تجزئة':'Retail','international'=>$isAr?'دولي':'International']; @endphp
            <div class="space-y-2">
                @forelse($clientsByType as $t)
                    <div class="flex justify-between items-center text-xs py-1.5 px-3 rounded-lg bg-gray-50">
                        <span class="font-semibold text-gray-600">{{ $cTypeLabels[$t->client_type] ?? $t->client_type }}</span>
                        <span class="font-black text-gray-800">{{ $t->cnt }}</span>
                    </div>
                @empty
                    <p class="text-[11px] text-gray-400">{{ $isAr ? 'لا يوجد عملاء' : 'No clients' }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== المصروفات حسب البند + أرصدة المحافظ ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="font-bold text-gray-800 text-sm mb-3 flex items-center gap-2"><i class="fas fa-receipt text-red-400 text-xs"></i> {{ $isAr ? 'المصروفات حسب البند (الفترة)' : 'Expenses by Category (Period)' }}</p>
            @php $maxExp = $expenseByCategory->max('total') ?: 1; @endphp
            <div class="space-y-2.5">
                @forelse($expenseByCategory as $e)
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1"><span>{{ $e->category ?? ($isAr ? 'غير مصنف' : 'Uncategorized') }}</span><span class="font-bold font-mono" dir="ltr">{{ $fmt($e->total) }} ({{ $e->cnt }})</span></div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full bg-red-400" style="width: {{ $e->total / $maxExp * 100 }}%"></div></div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-6">{{ $isAr ? 'لا توجد مصروفات في هذه الفترة' : 'No expenses in this period' }}</p>
                @endforelse
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="font-bold text-gray-800 text-sm mb-3 flex items-center gap-2"><i class="fas fa-wallet text-purple-400 text-xs"></i> {{ $isAr ? 'أرصدة المحافظ (اللحظية)' : 'Wallet Balances (Live)' }}</p>
            <div class="space-y-2">
                @forelse($walletBalances as $w)
                    <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50">
                        <span class="text-xs font-semibold text-gray-700">{{ $w->name }}</span>
                        <span class="text-sm font-black {{ $w->current_balance >= 0 ? 'text-[#008A3B]' : 'text-red-600' }}" dir="ltr">{{ $fmt($w->current_balance) }} <span class="text-[10px] text-gray-400 font-normal">{{ $w->currency }}</span></span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-6">{{ $isAr ? 'لا توجد محافظ' : 'No wallets' }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== مراكز التكلفة: الأعلى ربحًا/خسارة ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="font-bold text-gray-800 text-sm mb-3 flex items-center gap-2"><i class="fas fa-trophy text-green-400 text-xs"></i> {{ $isAr ? 'أعلى 5 مراكز تكلفة ربحًا' : 'Top 5 Most Profitable Cost Centers' }}</p>
            @forelse($costCenters['top_profitable'] as $q)
                <a href="{{ route('cost-centers.show', $q) }}" class="flex justify-between items-center text-xs py-2 border-b border-gray-50 last:border-0 hover:bg-gray-50 -mx-2 px-2 rounded transition-colors">
                    <span class="text-gray-700 font-semibold truncate">{{ $q->cost_center_name ?: $q->quote_number }}</span>
                    <span class="font-mono text-[#008A3B] font-bold" dir="ltr">+{{ $fmt($q->profit) }}</span>
                </a>
            @empty
                <p class="text-[11px] text-gray-400 py-2">{{ $isAr ? 'لا توجد مراكز تكلفة رابحة' : 'No profitable cost centers' }}</p>
            @endforelse
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="font-bold text-gray-800 text-sm mb-3 flex items-center gap-2"><i class="fas fa-exclamation-triangle text-red-400 text-xs"></i> {{ $isAr ? 'أعلى 5 مراكز تكلفة خسارة' : 'Top 5 Loss-Making Cost Centers' }}</p>
            @forelse($costCenters['top_losses'] as $q)
                <a href="{{ route('cost-centers.show', $q) }}" class="flex justify-between items-center text-xs py-2 border-b border-gray-50 last:border-0 hover:bg-gray-50 -mx-2 px-2 rounded transition-colors">
                    <span class="text-gray-700 font-semibold truncate">{{ $q->cost_center_name ?: $q->quote_number }}</span>
                    <span class="font-mono text-red-600 font-bold" dir="ltr">{{ $fmt($q->profit) }}</span>
                </a>
            @empty
                <p class="text-[11px] text-gray-400 py-2">{{ $isAr ? 'لا توجد مراكز تكلفة خاسرة' : 'No loss-making cost centers' }}</p>
            @endforelse
        </div>
    </div>

    {{-- ===== لمحة عن النظام ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-bold text-gray-500 mb-2 flex items-center gap-2"><i class="fas fa-network-wired text-amber-400"></i> {{ $isAr ? 'فجوات التوريد' : 'Sourcing Gaps' }}</p>
            <p class="text-2xl font-black text-gray-900">{{ $sourcingGaps['items_without_vendor'] }} <span class="text-xs font-normal text-gray-400">/ {{ $sourcingGaps['items_total'] }}</span></p>
            <p class="text-[11px] text-gray-400 mt-1">{{ $isAr ? 'صنف من غير مورد معتمد' : 'items with no approved vendor' }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-bold text-gray-500 mb-2 flex items-center gap-2"><i class="fas fa-tags text-indigo-400"></i> {{ $isAr ? 'قوائم الأسعار' : 'Price Lists' }}</p>
            <p class="text-2xl font-black text-gray-900">{{ $priceLists['active'] }} <span class="text-xs font-normal text-gray-400">/ {{ $priceLists['total'] }}</span></p>
            <p class="text-[11px] text-gray-400 mt-1">{{ $isAr ? 'قائمة نشطة من الإجمالي' : 'active out of total' }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-bold text-gray-500 mb-2 flex items-center gap-2"><i class="fas fa-history text-gray-400"></i> {{ $isAr ? 'نشاط المستخدمين (الفترة)' : 'User Activity (Period)' }}</p>
            <p class="text-2xl font-black text-gray-900">{{ $activity['total_actions'] }}</p>
            <p class="text-[11px] text-gray-400 mt-1">
                {{ $activity['top_user_name'] ? ($isAr ? 'الأكثر نشاطًا: ' : 'Most active: ') . $activity['top_user_name'] . ' (' . $activity['top_user_count'] . ')' : ($isAr ? 'لا يوجد نشاط' : 'No activity') }}
                @if($activity['reversed_count'] > 0)
                    <span class="text-amber-600">— {{ $activity['reversed_count'] }} {{ $isAr ? 'عملية معكوسة' : 'reversed' }}</span>
                @endif
            </p>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const cfLabels = @json($cashFlowTrend->pluck('label'));
    const cfIn     = @json($cashFlowTrend->pluck('cash_in'));
    const cfOut    = @json($cashFlowTrend->pluck('cash_out'));

    new Chart(document.getElementById('cashFlowChart'), {
        type: 'bar',
        data: {
            labels: cfLabels,
            datasets: [
                { label: {!! json_encode($isAr ? 'وارد' : 'In') !!}, data: cfIn, backgroundColor: '#008A3B', borderRadius: 4 },
                { label: {!! json_encode($isAr ? 'منصرف' : 'Out') !!}, data: cfOut, backgroundColor: '#ef4444', borderRadius: 4 },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
            scales: { y: { beginAtZero: true, ticks: { font: { size: 10 } } }, x: { ticks: { font: { size: 10 } } } }
        }
    });

    const qLabels = @json($quotationTrend->pluck('label'));
    const qCounts = @json($quotationTrend->pluck('count'));

    new Chart(document.getElementById('quotationChart'), {
        type: 'line',
        data: {
            labels: qLabels,
            datasets: [{
                label: {!! json_encode($isAr ? 'عدد عروض الأسعار' : 'Quotations Count') !!},
                data: qCounts,
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124,58,237,.1)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointBackgroundColor: '#7c3aed',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } }, x: { ticks: { font: { size: 10 } } } }
        }
    });
</script>
@endsection
