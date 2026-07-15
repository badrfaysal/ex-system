@extends('layouts.app')
@section('header_title', $salesOrder->so_number)

@php
    $isAr        = app()->getLocale() === 'ar';
    $docDir      = $isAr ? 'rtl' : 'ltr';
    $txtAlign    = $isAr ? 'right' : 'left';
    $txtAlignOpp = $isAr ? 'left' : 'right';
    $cur         = $salesOrder->currency ?? 'EGP';
    $clientDisplay = optional($salesOrder->client)->displayName($isAr ? 'ar' : 'en') ?? '—';

    $totalQty  = $salesOrder->items->sum('quantity');
    $totalDisc = $salesOrder->items->sum(fn($l) => $l->quantity * $l->list_price * $l->discount_percent / 100);
    $totalNet  = $salesOrder->items->sum('net_total');

    $jsLines = $salesOrder->items->map(fn($l) => [
        'code'  => $l->item_code ?? '—',
        'desc'  => $l->displayDescription($isAr ? 'ar' : 'en'),
        'qty'   => rtrim(rtrim(number_format($l->quantity, 2), '0'), '.'),
        'uom'   => $l->uom ?? '—',
        'price' => number_format($l->list_price, 2),
        'disc'  => $l->discount_percent > 0 ? number_format($l->discount_percent, 2).'%' : '—',
        'tax'   => $l->tax_percent > 0      ? number_format($l->tax_percent, 2).'%'      : '—',
        'net'   => number_format($l->net_total, 2),
    ])->values();
@endphp

@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        aside, header, #pageLoader { display: none !important; }
        main { padding: 0 !important; }
        body, html { background: #fff !important; }
        .print-doc {
            box-shadow: none !important; border: none !important;
            margin: 0 !important; max-width: 100% !important;
            border-radius: 0 !important; font-size: 11px !important;
        }
        .print-doc * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { margin: 8mm 10mm; size: A4 portrait; }
        .doc-header  { padding: 10px 20px !important; }
        .doc-info    { padding: 8px 20px !important; }
        .doc-table   { padding: 4px 20px !important; }
        .doc-footer-section { padding: 6px 20px !important; }
        .doc-footer  { padding: 5px 20px !important; }
        .tbl-row td  { padding: 5px 8px !important; }
        .tbl-head th { padding: 6px 8px !important; }
        .tot-row     { padding: 4px 14px !important; }
        .tot-final   { padding: 6px 14px !important; }
        .so-grid     { display: block !important; }
    }
    .so-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        align-items: start;
    }
    @media (min-width: 1024px) {
        .so-grid { grid-template-columns: 260px minmax(0, 1fr) 260px; }
    }
</style>

{{-- Flash messages --}}
@if(session('success'))
<div class="no-print mb-4 max-w-5xl mx-auto bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 flex items-center gap-3">
    <i class="fas fa-check-circle text-green-500 text-lg"></i>
    <span class="font-medium text-sm">{{ session('success') }}</span>
</div>
@endif
@if(session('error'))
<div class="no-print mb-4 max-w-5xl mx-auto bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 flex items-center gap-3">
    <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
    <span class="font-medium text-sm">{{ session('error') }}</span>
</div>
@endif

{{-- أزرار التحكم --}}
<div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3 max-w-5xl mx-auto">
    <a href="{{ route('sales-orders.index') }}"
       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
        <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i>
        {{ $isAr ? 'قائمة أوامر البيع' : 'Sales Orders' }}
    </a>

    <div class="flex flex-wrap items-center gap-2">
        {{-- رابط عرض السعر الأصلي --}}
        @if($salesOrder->quotation)
        <a href="{{ route('quotations.show', $salesOrder->quotation) }}"
           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
            <i class="fas fa-file-invoice-dollar text-[#005B9F]"></i>
            {{ $isAr ? 'عرض السعر الأصلي' : 'Source Quotation' }}
            <span class="font-mono text-xs text-gray-400">{{ $salesOrder->quotation->quote_number }}</span>
        </a>
        @endif

        @if($salesOrder->status !== 'cancelled')
            {{-- إنشاء فاتورة بيع --}}
            <a href="{{ route('sales-invoices.create', ['sales_order_id' => $salesOrder->id]) }}"
                class="px-5 py-2 bg-[#008A3B] text-white rounded-lg font-bold text-sm hover:bg-[#007030] flex items-center gap-2 transition-colors">
                <i class="fas fa-file-invoice"></i>
                {{ $isAr ? 'إنشاء فاتورة بيع' : 'Create Sales Invoice' }}
            </a>

            {{-- إنشاء فاتورة شراء --}}
            <a href="{{ route('purchase-invoices.create', ['sales_order_id' => $salesOrder->id]) }}"
                class="px-5 py-2 bg-[#005B9F] hover:bg-blue-800 text-white rounded-lg font-bold text-sm flex items-center gap-2 transition-colors">
                <i class="fas fa-file-invoice-dollar"></i>
                {{ $isAr ? 'إنشاء فاتورة شراء' : 'Create Purchase Invoice' }}
            </a>
        @else
            <button disabled class="px-5 py-2 bg-gray-200 text-gray-400 rounded-lg font-bold text-sm flex items-center gap-2 cursor-not-allowed" title="{{ $isAr ? 'أمر البيع ملغي' : 'Sales Order Cancelled' }}">
                <i class="fas fa-file-invoice"></i>
                {{ $isAr ? 'إنشاء فاتورة بيع' : 'Create Sales Invoice' }}
            </button>
            <button disabled class="px-5 py-2 bg-gray-200 text-gray-400 rounded-lg font-bold text-sm flex items-center gap-2 cursor-not-allowed" title="{{ $isAr ? 'أمر البيع ملغي' : 'Sales Order Cancelled' }}">
                <i class="fas fa-file-invoice-dollar"></i>
                {{ $isAr ? 'إنشاء فاتورة شراء' : 'Create Purchase Invoice' }}
            </button>
        @endif



        {{-- تحديث الحالة --}}
        <form action="{{ route('sales-orders.update', $salesOrder) }}" method="POST" class="flex items-center gap-2">
            @csrf
            @method('PATCH')
            <select name="status" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 focus:outline-none focus:border-[#005B9F]">
                <option value="confirmed" {{ $salesOrder->status === 'confirmed' ? 'selected' : '' }}>{{ $isAr ? 'مؤكد' : 'Confirmed' }}</option>
                <option value="cancelled" {{ $salesOrder->status === 'cancelled' ? 'selected' : '' }}>{{ $isAr ? 'ملغي' : 'Cancelled' }}</option>
                <option value="completed" {{ $salesOrder->status === 'completed' ? 'selected' : '' }}>{{ $isAr ? 'مكتمل' : 'Completed' }}</option>
            </select>
        </form>

        <button onclick="window.print()"
            class="px-5 py-2 bg-[#005B9F] text-white rounded-lg font-bold text-sm hover:bg-blue-800 flex items-center gap-2">
            <i class="fas fa-print"></i>
            {{ $isAr ? 'طباعة' : 'Print' }}
        </button>
    </div>
</div>

{{-- ============ فواتير البيع / أمر البيع / فواتير الشراء ============ --}}
<div class="so-grid max-w-[1500px] mx-auto mb-8">

    {{-- فواتير البيع --}}
    <div class="no-print bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden self-start">
        <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50/60 flex items-center gap-2">
            <i class="fas fa-file-invoice text-[#008A3B] text-xs"></i>
            <span class="font-bold text-gray-700 text-xs">{{ $isAr ? 'فواتير البيع' : 'Sales Invoices' }}</span>
            <span class="{{ $isAr ? 'mr-auto' : 'ml-auto' }} text-[11px] font-bold text-[#008A3B] bg-[#008A3B]/10 rounded-full px-2 py-0.5">{{ $salesOrder->salesInvoices->count() }}</span>
        </div>
        @if($salesOrder->salesInvoices->isNotEmpty())
        <ul class="divide-y divide-gray-100">
            @foreach($salesOrder->salesInvoices as $si)
            <li>
                <a href="{{ route('sales-invoices.show', $si) }}" class="block px-4 py-2.5 hover:bg-gray-50/60 transition-colors">
                    <div class="font-mono font-bold text-[#008A3B] text-xs hover:underline">{{ $si->invoice_number }}</div>
                    <div class="flex items-center justify-between mt-0.5">
                        <span class="text-gray-400 text-[11px]" dir="ltr">{{ $si->invoice_date->format('Y-m-d') }}</span>
                        <span class="font-bold text-green-600 text-[11px]" dir="ltr">{{ number_format($si->grand_total, 2) }} {{ $si->currency }}</span>
                    </div>
                </a>
            </li>
            @endforeach
        </ul>
        @else
        <p class="px-4 py-4 text-[11px] text-gray-400 text-center">{{ $isAr ? 'لا توجد فواتير بيع بعد' : 'No sales invoices yet' }}</p>
        @endif
    </div>

    {{-- ============ المستند ============ --}}
    <div class="print-doc bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8" dir="{{ $docDir }}">

    <div class="h-1.5 bg-gradient-to-r from-[#008A3B] to-[#005B9F]"></div>

    {{-- ترويسة --}}
    <div class="doc-header px-8 pt-5 pb-4 flex items-center justify-between gap-4 border-b border-gray-100">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/EFC-.png') }}" alt="{{ __('messages.app_name') }}"
                 class="h-16 w-auto object-contain" onerror="this.style.display='none'">
            <div>
                <p class="text-base font-extrabold text-gray-900 leading-tight">{{ __('messages.app_name') }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ __('messages.app_sub') }}</p>
            </div>
        </div>
        <div class="text-{{ $txtAlignOpp }}">
            <p class="text-2xl font-extrabold text-[#008A3B] tracking-tight leading-none">
                {{ $isAr ? 'أمر البيع' : 'Sales Order' }}
            </p>
            <p class="font-mono font-bold text-gray-600 mt-1 text-sm" dir="ltr">{{ $salesOrder->so_number }}</p>
            @php
                $badgeClasses = 'bg-green-50 text-green-700 border-green-200';
                $badgeText = $isAr ? 'مؤكد' : 'Confirmed';
                if ($salesOrder->status === 'cancelled') {
                    $badgeClasses = 'bg-red-50 text-red-600 border-red-200';
                    $badgeText = $isAr ? 'ملغي' : 'Cancelled';
                } elseif ($salesOrder->status === 'completed') {
                    $badgeClasses = 'bg-blue-50 text-blue-700 border-blue-200';
                    $badgeText = $isAr ? 'مكتمل' : 'Completed';
                }
            @endphp
            <span class="inline-block mt-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeClasses }}">
                {{ $badgeText }}
            </span>
        </div>
    </div>

    {{-- معلومات العميل والأمر --}}
    <div class="doc-info px-8 py-4 grid grid-cols-2 gap-8 border-b border-gray-100 bg-gray-50/50">
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                {{ $isAr ? 'إلى' : 'TO' }}
            </p>
            <p class="text-base font-extrabold text-gray-900 leading-snug">{{ $clientDisplay }}</p>
            @if(optional($salesOrder->client)->contact_person)
                <p class="text-xs text-gray-500 mt-0.5">{{ $salesOrder->client->contact_person }}</p>
            @endif
            @if(optional($salesOrder->client)->phone || optional($salesOrder->client)->email)
                <p class="text-xs text-gray-400 mt-0.5" dir="ltr">
                    {{ optional($salesOrder->client)->phone }}
                    @if(optional($salesOrder->client)->phone && optional($salesOrder->client)->email)
                        <span class="text-gray-300 mx-1">|</span>
                    @endif
                    {{ optional($salesOrder->client)->email }}
                </p>
            @endif
            @if($salesOrder->sales_rep)
                <p class="text-xs text-gray-500 mt-2 pt-2 border-t border-gray-200">
                    <span class="text-gray-400">{{ $isAr ? 'المندوب:' : 'Rep:' }}</span>
                    <span class="font-bold {{ $isAr ? 'mr-1' : 'ml-1' }}">{{ $salesOrder->sales_rep }}</span>
                </p>
            @endif
        </div>
        <div class="text-{{ $txtAlignOpp }}">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
                {{ $isAr ? 'تفاصيل الأمر' : 'ORDER DETAILS' }}
            </p>
            <table class="text-xs w-full" dir="{{ $docDir }}">
                <tr>
                    <td class="text-gray-400 pb-1.5 {{ $isAr ? 'pl-3' : 'pr-3' }}">{{ $isAr ? 'التاريخ:' : 'Date:' }}</td>
                    <td class="font-bold text-gray-700 pb-1.5 text-{{ $txtAlignOpp }}" dir="ltr">
                        {{ optional($salesOrder->so_date)->format('d/m/Y') ?? '—' }}
                    </td>
                </tr>
                @if($salesOrder->quotation)
                <tr>
                    <td class="text-gray-400 pb-1.5 {{ $isAr ? 'pl-3' : 'pr-3' }}">{{ $isAr ? 'مرجع العرض:' : 'Ref. Quote:' }}</td>
                    <td class="font-mono font-bold text-[#005B9F] pb-1.5 text-{{ $txtAlignOpp }}" dir="ltr">
                        {{ $salesOrder->quotation->quote_number }}
                    </td>
                </tr>
                @endif
                <tr>
                    <td class="text-gray-400 {{ $isAr ? 'pl-3' : 'pr-3' }}">{{ $isAr ? 'العملة:' : 'Currency:' }}</td>
                    <td class="font-extrabold text-[#005B9F] text-{{ $txtAlignOpp }}">{{ $cur }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- جدول الأصناف --}}
    <div class="doc-table px-8 py-3">
        <table class="w-full border-collapse" style="text-align:{{ $txtAlign }}">
            <thead>
                <tr class="tbl-head" style="background:#1e293b;color:#fff;">
                    <th class="px-3 py-2.5 text-center text-[11px] font-bold w-8">#</th>
                    <th class="px-3 py-2.5 text-[11px] font-bold w-28">{{ $isAr ? 'الكود' : 'Code' }}</th>
                    <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'الوصف' : 'Description' }}</th>
                    <th class="px-3 py-2.5 text-center text-[11px] font-bold">{{ $isAr ? 'الكمية' : 'Qty' }}</th>
                    <th class="px-3 py-2.5 text-center text-[11px] font-bold">{{ $isAr ? 'السعر' : 'Price' }}</th>
                    <th class="px-3 py-2.5 text-center text-[11px] font-bold">{{ $isAr ? 'الخصم' : 'Disc%' }}</th>
                    <th class="px-3 py-2.5 text-[11px] font-bold w-32" style="text-align:{{ $txtAlignOpp }}">{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salesOrder->items as $idx => $line)
                    <tr class="tbl-row {{ $idx % 2 === 0 ? '' : 'bg-gray-50/70' }} border-b border-gray-100">
                        <td class="px-3 py-2 text-center text-gray-400 text-[11px]">{{ $idx + 1 }}</td>
                        <td class="px-3 py-2 text-[11px] font-mono font-extrabold text-[#005B9F]" dir="ltr">
                            {{ $line->item_code ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-gray-800 font-medium text-xs">
                            {{ $line->displayDescription($isAr ? 'ar' : 'en') }}
                        </td>
                        <td class="px-3 py-2 text-center font-bold text-gray-700 text-xs" dir="ltr">
                            {{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }}
                        </td>
                        <td class="px-3 py-2 text-center text-gray-600 text-xs" dir="ltr">
                            {{ number_format($line->list_price, 2) }}
                        </td>
                        <td class="px-3 py-2 text-center text-gray-400 text-xs" dir="ltr">
                            @if($line->discount_percent > 0)
                                {{ rtrim(rtrim(number_format($line->discount_percent, 2), '0'), '.') }}%
                            @else —
                            @endif
                        </td>
                        <td class="px-3 py-2 font-extrabold text-gray-900 text-xs" style="text-align:{{ $txtAlignOpp }}" dir="ltr">
                            {{ number_format($line->net_total, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f1f5f9;border-top:2px solid #cbd5e1;">
                    <td colspan="3" class="px-3 py-2.5 font-extrabold text-gray-700 text-xs tracking-wide"
                        style="text-align:{{ $txtAlign }}">
                        {{ $isAr ? 'الإجمالي' : 'Total' }}
                        <span class="font-normal text-gray-400 text-[10px] mr-1">
                            ({{ $salesOrder->items->count() }} {{ $isAr ? 'صنف' : 'items' }})
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-center font-extrabold text-gray-800 text-xs" dir="ltr">
                        {{ rtrim(rtrim(number_format($totalQty, 2), '0'), '.') }}
                    </td>
                    <td class="px-3 py-2.5 text-center text-gray-300 text-xs">—</td>
                    <td class="px-3 py-2.5 text-center font-bold text-red-600 text-xs" dir="ltr">
                        @if($totalDisc > 0) - {{ number_format($totalDisc, 2) }} @else — @endif
                    </td>
                    <td class="px-3 py-2.5 font-extrabold text-[#008A3B] text-sm" style="text-align:{{ $txtAlignOpp }}" dir="ltr">
                        {{ number_format($totalNet, 2) }}
                        <span class="text-[10px] font-normal text-gray-400 mr-0.5">{{ $cur }}</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- الشروط + الإجماليات --}}
    <div class="doc-footer-section px-8 py-4 grid grid-cols-2 gap-8 items-start">
        <div>
            @if($salesOrder->terms)
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ $isAr ? 'الشروط والأحكام' : 'Terms & Conditions' }}</p>
                <p class="text-xs text-gray-500 leading-relaxed whitespace-pre-line">{{ $salesOrder->terms }}</p>
            @endif
        </div>
        <div class="rounded-xl border border-gray-200 overflow-hidden text-xs">
            <div class="tot-row flex justify-between items-center px-4 py-2.5 border-b border-gray-100">
                <span class="text-gray-500">{{ $isAr ? 'الإجمالي قبل الخصم' : 'Subtotal' }}</span>
                <span class="font-bold text-gray-800" dir="ltr">{{ number_format($salesOrder->subtotal, 2) }} <span class="text-gray-400">{{ $cur }}</span></span>
            </div>
            @if($salesOrder->total_discount > 0)
            <div class="tot-row flex justify-between items-center px-4 py-2.5 border-b border-gray-100 bg-red-50/30">
                <span class="text-gray-500">{{ $isAr ? 'إجمالي الخصم' : 'Total Discount' }}</span>
                <span class="font-bold text-red-600" dir="ltr">- {{ number_format($salesOrder->total_discount, 2) }} <span class="text-red-300">{{ $cur }}</span></span>
            </div>
            @endif
            @if($salesOrder->tax_amount > 0)
            <div class="tot-row flex justify-between items-center px-4 py-2.5 border-b border-gray-100">
                <span class="text-gray-500">{{ $isAr ? 'الضريبة' : 'Tax' }}</span>
                <span class="font-bold text-gray-800" dir="ltr">+ {{ number_format($salesOrder->tax_amount, 2) }} <span class="text-gray-400">{{ $cur }}</span></span>
            </div>
            @endif
            <div class="tot-final flex justify-between items-center px-4 py-3" style="background:#008A3B;">
                <span class="font-extrabold text-white text-sm">{{ $isAr ? 'الإجمالي النهائي' : 'Grand Total' }}</span>
                <span class="font-extrabold text-white text-base" dir="ltr">
                    {{ number_format($salesOrder->grand_total, 2) }}
                    <span class="text-xs opacity-80">{{ $cur }}</span>
                </span>
            </div>
        </div>
    </div>

    {{-- باركود رقم أمر البيع --}}
    <div class="px-8 pt-4 pb-3 border-t border-gray-100 flex items-center justify-center" dir="ltr">
        <svg id="soBarcode" style="max-width:100%; height:auto;"></svg>
    </div>

    {{-- تذييل --}}
    <div class="doc-footer px-4 py-3 border-t border-gray-100 bg-gray-50/60 text-center">
        <div class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2 text-[10px] text-gray-500 font-medium" dir="ltr">
            <span class="flex items-center"><i class="fas fa-map-marker-alt text-[#005B9F] mr-1.5"></i>Head Office: City Star Towers – Tower 5, Apartment 15, 10th District, 6th of October City, Giza, Egypt</span>
            <span class="text-gray-300 hidden xl:inline">|</span>
            <span class="flex items-center whitespace-nowrap"><i class="fas fa-phone-alt text-[#005B9F] mr-1.5"></i>(+20) 15-5772-2227</span>
            <span class="text-gray-300 hidden md:inline">|</span>
            <span class="flex items-center whitespace-nowrap"><i class="fas fa-envelope text-[#005B9F] mr-1.5"></i>info@efcexport.com</span>
            <span class="text-gray-300 hidden md:inline">|</span>
            <span class="flex items-center whitespace-nowrap"><i class="fas fa-globe text-[#005B9F] mr-1.5"></i>www.efcexport.com</span>
        </div>
    </div>

    <div class="h-1 bg-gradient-to-r from-[#008A3B] to-[#005B9F]"></div>
    </div>

    {{-- فواتير الشراء --}}
    <div class="no-print bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden self-start">
        <div class="px-4 py-2.5 border-b border-gray-100 bg-gray-50/60 flex items-center gap-2">
            <i class="fas fa-file-invoice-dollar text-[#005B9F] text-xs"></i>
            <span class="font-bold text-gray-700 text-xs">{{ $isAr ? 'فواتير الشراء' : 'Purchase Invoices' }}</span>
            <span class="{{ $isAr ? 'mr-auto' : 'ml-auto' }} text-[11px] font-bold text-[#005B9F] bg-[#005B9F]/10 rounded-full px-2 py-0.5">{{ $salesOrder->purchaseInvoices->count() }}</span>
        </div>
        @if($salesOrder->purchaseInvoices->isNotEmpty())
        <ul class="divide-y divide-gray-100">
            @foreach($salesOrder->purchaseInvoices as $pi)
            <li>
                <a href="{{ route('purchase-invoices.show', $pi) }}" class="block px-4 py-2.5 hover:bg-gray-50/60 transition-colors">
                    <div class="font-mono font-bold text-[#005B9F] text-xs hover:underline">{{ $pi->invoice_number }}</div>
                    <div class="flex items-center justify-between mt-0.5">
                        <span class="text-gray-400 text-[11px]" dir="ltr">{{ $pi->invoice_date->format('Y-m-d') }}</span>
                        <span class="font-bold text-red-600 text-[11px]" dir="ltr">{{ number_format($pi->grand_total, 2) }} {{ $pi->currency }}</span>
                    </div>
                </a>
            </li>
            @endforeach
        </ul>
        @else
        <p class="px-4 py-4 text-[11px] text-gray-400 text-center">{{ $isAr ? 'لا توجد فواتير شراء بعد' : 'No purchase invoices yet' }}</p>
        @endif
        @if($salesOrder->quotation)
        <a href="{{ route('cost-centers.show', $salesOrder->quotation) }}" class="block px-4 py-2 text-[11px] text-[#005B9F] hover:underline border-t border-gray-100 bg-gray-50/40">
            {{ $isAr ? 'تقرير مركز التكلفة' : 'Cost center report' }} <i class="fas fa-chart-line"></i>
        </a>
        @endif
    </div>

</div>

{{-- ============ التحصيلات ============ --}}
@php 
    $allReceipts = $salesOrder->salesInvoices->flatMap->receipts;
    $received = $allReceipts->sum('amount'); 
    $due = $salesOrder->grand_total - $received; 
@endphp
<div class="no-print mt-6 max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/60 flex items-center gap-2">
        <i class="fas fa-hand-holding-usd text-[#008A3B]"></i>
        <span class="font-bold text-gray-700 text-sm">{{ $isAr ? 'التحصيل' : 'Collection' }}</span>
        <span class="{{ $isAr ? 'mr-auto' : 'ml-auto' }} text-sm">
            <span class="text-gray-400">{{ $isAr ? 'المحصّل:' : 'Received:' }}</span>
            <span class="font-bold text-green-600" dir="ltr">{{ number_format($received, 2) }}</span>
            <span class="text-gray-300 mx-1">/</span>
            <span class="text-gray-400">{{ $isAr ? 'المتبقي:' : 'Due:' }}</span>
            <span class="font-bold {{ $due > 0 ? 'text-red-600' : 'text-green-600' }}" dir="ltr">{{ number_format($due, 2) }}</span>
            <span class="text-xs text-gray-400">{{ $cur }}</span>
        </span>
    </div>
    @if($allReceipts->isNotEmpty())
    <table class="w-full text-sm" style="text-align:{{ $txtAlign }}">
        <tbody class="divide-y divide-gray-100">
            @foreach($allReceipts as $r)
            <tr>
                <td class="px-5 py-2.5 font-mono text-gray-700">{{ $r->receipt_number }}</td>
                <td class="px-5 py-2.5 text-gray-500" dir="ltr">{{ $r->receipt_date->format('Y-m-d') }}</td>
                <td class="px-5 py-2.5 font-bold text-green-600 text-{{ $txtAlignOpp }}" dir="ltr">{{ number_format($r->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<style>
    #soBarcode text {
        font-family: 'Courier New', monospace !important;
        font-weight: 700 !important;
        fill: #0f172a !important;
        letter-spacing: 1px;
    }
</style>
<script>
    (function () {
        function renderBarcode() {
            if (typeof JsBarcode !== 'undefined') {
                try {
                    JsBarcode("#soBarcode", @json($salesOrder->so_number), {
                        format: "CODE128",
                        width: 1.6, height: 42, fontSize: 14,
                        font: "monospace", fontOptions: "bold",
                        textMargin: 4, margin: 6,
                        background: "#ffffff", lineColor: "#0f172a",
                        displayValue: true
                    });
                } catch (e) {}
            }
        }
        if (document.readyState !== 'loading') renderBarcode();
        else document.addEventListener('DOMContentLoaded', renderBarcode);
        window.addEventListener('load', renderBarcode);
    })();
</script>

{{-- Modal بيانات الصنف --}}
<div id="lineModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 no-print" role="dialog">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeLineModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" dir="{{ $docDir }}">
        <div class="bg-[#1e293b] text-white px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center">
                    <i class="fas fa-box text-sm"></i>
                </div>
                <div>
                    <p id="lmCode" class="font-mono font-bold text-blue-300 text-sm leading-none"></p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $isAr ? 'بيانات الصنف' : 'Item Details' }}</p>
                </div>
            </div>
            <button onclick="closeLineModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 text-gray-400 hover:text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 space-y-3 text-sm">
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs text-gray-400 mb-1">{{ $isAr ? 'الوصف' : 'Description' }}</p>
                <p id="lmDesc" class="font-bold text-gray-900 leading-snug"></p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-gray-400 mb-1">{{ $isAr ? 'الكمية' : 'Qty' }}</p>
                    <p id="lmQty" class="font-extrabold text-gray-800 text-lg" dir="ltr"></p>
                    <p id="lmUom" class="text-[10px] text-gray-400 mt-0.5"></p>
                </div>
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-gray-400 mb-1">{{ $isAr ? 'السعر' : 'Price' }}</p>
                    <p id="lmPrice" class="font-extrabold text-gray-800 text-lg" dir="ltr"></p>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $cur }}</p>
                </div>
                <div class="bg-amber-50 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-amber-600 mb-1">{{ $isAr ? 'الخصم' : 'Discount' }}</p>
                    <p id="lmDisc" class="font-bold text-amber-700 text-base" dir="ltr"></p>
                </div>
                <div class="bg-purple-50 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-purple-600 mb-1">{{ $isAr ? 'الضريبة' : 'Tax' }}</p>
                    <p id="lmTax" class="font-bold text-purple-700 text-base" dir="ltr"></p>
                </div>
            </div>
            <div class="flex justify-between items-center bg-[#008A3B] text-white rounded-xl px-5 py-3">
                <span class="font-bold text-sm">{{ $isAr ? 'الصافي' : 'Net Total' }}</span>
                <span id="lmNet" class="font-extrabold text-lg" dir="ltr"></span>
            </div>
        </div>
    </div>
</div>

<script>
    const LINES = @json($jsLines);

    function openLineModal(idx) {
        const l = LINES[idx];
        if (!l) return;
        document.getElementById('lmCode').textContent  = l.code;
        document.getElementById('lmDesc').textContent  = l.desc;
        document.getElementById('lmQty').textContent   = l.qty;
        document.getElementById('lmUom').textContent   = l.uom !== '—' ? l.uom : '';
        document.getElementById('lmPrice').textContent = l.price;
        document.getElementById('lmDisc').textContent  = l.disc;
        document.getElementById('lmTax').textContent   = l.tax;
        document.getElementById('lmNet').textContent   = l.net + ' {{ $cur }}';
        const modal = document.getElementById('lineModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeLineModal() {
        const modal = document.getElementById('lineModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLineModal(); });
</script>
@endsection
