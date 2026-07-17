@extends('layouts.app')
@section('header_title', $quotation->quote_number)

@php
    $isAr  = app()->getLocale() === 'ar';
    $docDir = $isAr ? 'rtl' : 'ltr';
    $txtAlign = $isAr ? 'right' : 'left';
    $txtAlignOpp = $isAr ? 'left' : 'right';

    $statusMap = [
        'draft'        => ['label' => __('messages.quotations.st_draft'),        'cls' => 'bg-gray-100 text-gray-600 border-gray-300'],
        'under_review' => ['label' => __('messages.quotations.st_under_review'), 'cls' => 'bg-amber-50 text-amber-600 border-amber-200'],
        'sent'         => ['label' => __('messages.quotations.st_sent'),         'cls' => 'bg-blue-50 text-[#005B9F] border-blue-200'],
        'approved'     => ['label' => __('messages.quotations.st_approved'),     'cls' => 'bg-green-50 text-green-700 border-green-200'],
        'rejected'     => ['label' => __('messages.quotations.st_rejected'),     'cls' => 'bg-red-50 text-red-600 border-red-200'],
        'converted'    => ['label' => __('messages.quotations.st_converted'),    'cls' => 'bg-green-50 text-green-700 border-green-200'],
        'cancelled'    => ['label' => __('messages.quotations.st_cancelled'),    'cls' => 'bg-red-50 text-red-600 border-red-200'],
        'expired'      => ['label' => __('messages.quotations.st_expired'),      'cls' => 'bg-amber-50 text-amber-600 border-amber-200'],
    ];
    $st  = $statusMap[$quotation->status] ?? $statusMap['draft'];
    $cur = $quotation->currency ?? 'EGP';

    // لا يُعدَّل العرض إلا وهو «مسودة» فقط
    $isLocked = $quotation->status !== 'draft';

    // آلة الحالة: الانتقالات المسموح بها من الحالة الحالية
    $allowedNext = \App\Http\Controllers\QuotationController::allowedNext($quotation->status);

    // الإرسال متاح فقط بعد المراجعة والاعتماد
    $canSend = $quotation->status === 'approved';

    // اسم الشركة بحسب اللغة
    $clientDisplay = optional($quotation->client)->displayName($isAr ? 'ar' : 'en') ?? '—';

    // حسابات صف الإجماليات
    $totalQty     = $quotation->items->sum('quantity');
    $totalDisc    = $quotation->items->sum(fn($l) => $l->quantity * $l->list_price * $l->discount_percent / 100);
    $totalNet     = $quotation->items->sum('net_total');

    $jsLines = $quotation->items->map(fn($l) => [
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
        .sig-line    { height: 28px !important; }
        .tot-row     { padding: 4px 14px !important; }
        .tot-final   { padding: 6px 14px !important; }
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
    <a href="{{ route('quotations.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
        <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ __('messages.quotations.back') }}
    </a>
    <div class="flex flex-wrap items-center gap-2">

        <button onclick="window.print()" class="px-5 py-2 bg-[#005B9F] text-white rounded-lg font-bold text-sm hover:bg-blue-800 flex items-center gap-2">
            <i class="fas fa-print"></i> {{ __('messages.quotations.print_btn') }}
        </button>

        {{-- زر تحويل لأمر بيع --}}
        @if($quotation->status === 'converted' || isset($salesOrder))
            {{-- تم التحويل — يعرض رابط أمر البيع --}}
            @if(isset($salesOrder) && $salesOrder)
            <a href="{{ route('sales-orders.show', $salesOrder) }}"
               class="px-5 py-2 bg-green-600 text-white rounded-lg font-bold text-sm flex items-center gap-2 hover:bg-green-700 transition-colors">
                <i class="fas fa-check-double"></i>
                {{ $isAr ? 'تم التحويل' : 'Converted' }}
                <span class="font-mono text-xs opacity-80">{{ $salesOrder->so_number }}</span>
            </a>
            @else
            <button type="button" disabled
                class="px-5 py-2 bg-gray-200 text-gray-400 rounded-lg font-bold text-sm flex items-center gap-2 cursor-not-allowed">
                <i class="fas fa-check-double"></i>
                {{ $isAr ? 'تم التحويل' : 'Converted' }}
            </button>
            @endif
        @elseif(in_array($quotation->status, ['approved', 'sent']))
            <a href="{{ route('sales-orders.create', ['quotation_id' => $quotation->id]) }}"
               class="px-5 py-2 bg-[#008A3B] hover:bg-[#007030] text-white rounded-lg font-bold text-sm flex items-center gap-2 transition-colors">
                <i class="fas fa-file-contract"></i>
                {{ $isAr ? 'تحويل لأمر بيع' : 'Convert to Sales Order' }}
            </a>
        @else
            <button type="button" disabled
                title="{{ $isAr ? 'يجب اعتماد العرض أولاً قبل التحويل' : 'Quotation must be approved before converting' }}"
                class="px-5 py-2 bg-gray-200 text-gray-400 rounded-lg font-bold text-sm flex items-center gap-2 cursor-not-allowed">
                <i class="fas fa-file-contract"></i>
                {{ $isAr ? 'تحويل لأمر بيع' : 'Convert to Sales Order' }}
            </button>
        @endif



        {{-- زر إرسال البريد — متاح فقط لو الحالة «معتمد» وللعميل إيميل --}}
        @if($canSend && optional($quotation->client)->email)
        <button type="button" data-open-send-mail
            class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold text-sm flex items-center gap-2 transition-colors">
            <i class="fas fa-envelope"></i>
            {{ $isAr ? 'إرسال للعميل' : 'Send to Client' }}
        </button>
        @else
        <button type="button" disabled
            title="{{ !optional($quotation->client)->email ? ($isAr ? 'لا يوجد إيميل للعميل' : 'No client email') : __('messages.quotations.send_need_approve') }}"
            class="px-5 py-2 bg-gray-200 text-gray-400 rounded-lg font-bold text-sm flex items-center gap-2 cursor-not-allowed">
            <i class="fas fa-{{ $canSend ? 'envelope' : 'lock' }}"></i>
            {{ $isAr ? 'إرسال للعميل' : 'Send to Client' }}
        </button>
        @endif

        @if($isLocked)
            <button type="button" disabled
                title="{{ __('messages.quotations.lock_draft_only') }}"
                class="px-5 py-2 bg-gray-200 text-gray-400 rounded-lg font-bold text-sm flex items-center gap-2 cursor-not-allowed">
                <i class="fas fa-lock"></i> {{ __('messages.quotations.edit_btn') }}
            </button>
        @else
            <a href="{{ route('quotations.edit', $quotation) }}" class="px-5 py-2 bg-[#008A3B] text-white rounded-lg font-bold text-sm hover:bg-[#007030] flex items-center gap-2">
                <i class="fas fa-pen"></i> {{ __('messages.quotations.edit_btn') }}
            </a>
        @endif
        <form action="{{ route('quotations.clone', $quotation) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium text-sm flex items-center gap-2">
                <i class="fas fa-copy"></i> {{ __('messages.quotations.clone_btn') }}
            </button>
        </form>
    </div>
</div>

{{-- ============ بطاقة إدارة الحالة (آلة الحالة) ============ --}}
@php
    // أيقونات الحالات لعرض أوضح
    $stIcons = [
        'draft'        => 'fa-pencil-alt',
        'under_review' => 'fa-hourglass-half',
        'sent'         => 'fa-paper-plane',
        'approved'     => 'fa-check-circle',
        'rejected'     => 'fa-times-circle',
        'converted'    => 'fa-exchange-alt',
        'cancelled'    => 'fa-ban',
        'expired'      => 'fa-clock',
    ];
@endphp
<div class="no-print mb-4 max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-100 bg-gray-50/60 flex items-center gap-2">
        <i class="fas fa-route text-[#005B9F]"></i>
        <span class="font-bold text-gray-700 text-sm">{{ __('messages.quotations.status_box') }}</span>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $st['cls'] }} {{ $isAr ? 'mr-auto' : 'ml-auto' }}">
            <i class="fas {{ $stIcons[$quotation->status] ?? 'fa-circle' }} text-[10px]"></i> {{ $st['label'] }}
        </span>
    </div>

    @if(empty($allowedNext))
        {{-- حالة نهائية --}}
        <div class="px-5 py-4 flex items-center gap-2 text-sm text-gray-500">
            <i class="fas fa-lock text-gray-400"></i>
            {{ __('messages.quotations.status_final') }}
        </div>
    @else
        <form action="{{ route('quotations.update-status', $quotation) }}" method="POST"
              class="px-5 py-4 flex flex-wrap items-end gap-3">
            @csrf
            @method('PATCH')
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.quotations.status_change') }}</label>
                <select name="status" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 focus:outline-none focus:border-[#005B9F]">
                    @foreach($allowedNext as $next)
                        <option value="{{ $next }}">{{ $statusMap[$next]['label'] ?? $next }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                class="px-6 py-2 bg-[#005B9F] text-white rounded-lg font-bold text-sm hover:bg-blue-800 flex items-center gap-2 transition-colors">
                <i class="fas fa-check"></i> {{ __('messages.quotations.status_apply') }}
            </button>
        </form>
        @if($quotation->status === 'draft')
        <div class="px-5 pb-4 -mt-1">
            <p class="text-xs text-amber-600 flex items-center gap-1.5">
                <i class="fas fa-info-circle"></i>
                {{ $isAr ? 'بعد اعتماد العرض يمكنك إرساله للعميل.' : 'After approving the quotation you can send it to the client.' }}
            </p>
        </div>
        @endif
    @endif
</div>



{{-- ============ المستند ============ --}}
<div class="print-doc bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden max-w-5xl mx-auto mb-8" dir="{{ $docDir }}">

    {{-- شريط علوي --}}
    <div class="h-1.5 bg-gradient-to-r from-[#005B9F] to-[#008A3B]"></div>

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
            <p class="text-2xl font-extrabold text-[#005B9F] tracking-tight leading-none">{{ __('messages.quotations.show_title') }}</p>
            <p class="font-mono font-bold text-gray-600 mt-1 text-sm" dir="ltr">{{ $quotation->quote_number }}</p>
            @if($quotation->cost_center_name)
            <p class="text-xs text-purple-600 mt-1 flex items-center gap-1 justify-{{ $isAr ? 'start' : 'end' }}">
                <i class="fas fa-layer-group"></i>
                <a href="{{ route('cost-centers.show', $quotation) }}" class="hover:underline">{{ $quotation->cost_center_name }}</a>
            </p>
            @endif
            <span class="inline-block mt-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $st['cls'] }}">{{ $st['label'] }}</span>
        </div>
    </div>

    {{-- معلومات العميل والعرض --}}
    <div class="doc-info px-8 py-4 grid grid-cols-2 gap-8 border-b border-gray-100 bg-gray-50/50">
        <div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ __('messages.quotations.show_to') }}</p>
            <div class="flex items-start gap-4">
                {{-- QR كود: أقصى اليمين --}}
                @if($quotation->client)
              <a href="{{ route('clients.quotations', $quotation->client) }}"
                   id="clientQrLink" class="shrink-0 inline-block rounded-lg border border-gray-200 p-1.5 bg-white hover:border-[#005B9F] transition-colors text-center"
                   title="{{ $isAr ? 'امسح لعرض جميع عروضك' : 'Scan to view all your quotations' }}">
                    <div id="clientQr" dir="ltr"></div>
                    <p class="text-[9px] text-gray-400 mt-1 leading-tight" style="max-width:78px">
                        <i class="fas fa-qrcode text-gray-300 mb-0.5 block text-center text-[10px]"></i>
                        {{ $isAr ? 'امسح لعرض كل عروضك' : 'Scan to view all your quotations' }}
                    </p>
                </a>
                @endif
                <div class="flex-1">
                    <p class="text-base font-extrabold text-gray-900 leading-snug">{{ $clientDisplay }}</p>
                    @if(optional($quotation->client)->contact_person)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $quotation->client->contact_person }}</p>
                    @endif
                    @if(optional($quotation->client)->phone || optional($quotation->client)->email)
                        <p class="text-xs text-gray-400 mt-0.5" dir="ltr">
                            {{ optional($quotation->client)->phone }}
                            @if(optional($quotation->client)->phone && optional($quotation->client)->email)
                                <span class="text-gray-300 mx-1">|</span>
                            @endif
                            {{ optional($quotation->client)->email }}
                        </p>
                    @endif
                    @if($quotation->sales_rep)
                        <p class="text-xs text-gray-500 mt-2 pt-2 border-t border-gray-200">
                            <span class="text-gray-400">{{ __('messages.quotations.show_rep') }}:</span>
                            <span class="font-bold">{{ $quotation->sales_rep }}</span>
                        </p>
                    @endif
                </div>
            </div>
        </div>
        <div class="text-{{ $txtAlignOpp }}">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ __('messages.quotations.show_details') }}</p>
            <table class="text-xs w-full" dir="{{ $docDir }}">
                <tr>
                    <td class="text-gray-400 pb-1.5 {{ $isAr ? 'pl-3' : 'pr-3' }}">{{ __('messages.quotations.show_issue') }}</td>
                    <td class="font-bold text-gray-700 pb-1.5 text-{{ $txtAlignOpp }}" dir="ltr">{{ optional($quotation->quote_date)->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="text-gray-400 pb-1.5 {{ $isAr ? 'pl-3' : 'pr-3' }}">{{ __('messages.quotations.show_valid') }}</td>
                    <td class="font-bold text-gray-700 pb-1.5 text-{{ $txtAlignOpp }}" dir="ltr">{{ optional($quotation->expiry_date)->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="text-gray-400 {{ $isAr ? 'pl-3' : 'pr-3' }}">{{ __('messages.quotations.show_currency') }}</td>
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
                    <th class="px-3 py-2.5 text-[11px] font-bold w-28">{{ __('messages.quotations.show_th_code') }}</th>
                    <th class="px-3 py-2.5 text-[11px] font-bold">{{ __('messages.quotations.show_th_desc') }}</th>
                    <th class="px-3 py-2.5 text-center text-[11px] font-bold ">{{ __('messages.quotations.show_th_qty') }}</th>
                    <th class="px-3 py-2.5 text-center text-[11px] font-bold ">{{ __('messages.quotations.show_th_price') }}</th>
                    <th class="px-3 py-2.5 text-center text-[11px] font-bold ">{{ __('messages.quotations.show_th_disc') }}</th>
                    <th class="px-3 py-2.5 text-[11px] font-bold w-32" style="text-align:{{ $txtAlignOpp }}">{{ __('messages.quotations.show_th_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $idx => $line)
                    <tr class="tbl-row {{ $idx % 2 === 0 ? '' : 'bg-gray-50/70' }} border-b border-gray-100">
                        <td class="px-3 py-2 text-center text-gray-400 text-[11px]">{{ $idx + 1 }}</td>
                        <td class="px-3 py-2 text-[11px]" dir="ltr">
                            <button type="button"
                                onclick="openLineModal({{ $idx }})"
                                class="no-print font-mono font-extrabold text-[#005B9F] hover:underline cursor-pointer bg-transparent border-0 p-0 leading-none">
                                {{ $line->item_code ?? '—' }}
                            </button>
                            <span class="hidden print:inline font-mono font-bold text-[#005B9F]">{{ $line->item_code ?? '—' }}</span>
                        </td>
                        <td class="px-3 py-2 text-gray-800 font-medium text-xs">{{ $line->displayDescription($isAr ? 'ar' : 'en') }}</td>
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
                    {{-- خلية الإجمالي تمتد على #، الكود، الوصف --}}
                    <td colspan="3" class="px-3 py-2.5 font-extrabold text-gray-700 text-xs tracking-wide"
                        style="text-align:{{ $txtAlign }}">
                        {{ $isAr ? 'الإجمالي' : 'Total' }}
                        <span class="font-normal text-gray-400 text-[10px] mr-1">
                            ({{ $quotation->items->count() }} {{ $isAr ? 'صنف' : 'items' }})
                        </span>
                    </td>
                    {{-- إجمالي الكمية --}}
                    <td class="px-3 py-2.5 text-center font-extrabold text-gray-800 text-xs" dir="ltr">
                        {{ rtrim(rtrim(number_format($totalQty, 2), '0'), '.') }}
                    </td>
                    {{-- سعر الوحدة — فارغ --}}
                    <td class="px-3 py-2.5 text-center text-gray-300 text-xs">—</td>
                    {{-- إجمالي الخصم بالقيمة --}}
                    <td class="px-3 py-2.5 text-center font-bold text-red-600 text-xs" dir="ltr">
                        @if($totalDisc > 0)
                            - {{ number_format($totalDisc, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    {{-- إجمالي الأصناف الصافي --}}
                    <td class="px-3 py-2.5 font-extrabold text-[#005B9F] text-sm" style="text-align:{{ $txtAlignOpp }}" dir="ltr">
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
            @if($quotation->terms)
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ __('messages.quotations.show_terms') }}</p>
                <p class="text-xs text-gray-500 leading-relaxed whitespace-pre-line">{{ $quotation->terms }}</p>
            @endif
        </div>
        <div class="rounded-xl border border-gray-200 overflow-hidden text-xs">
            <div class="tot-row flex justify-between items-center px-4 py-2.5 border-b border-gray-100">
                <span class="text-gray-500">{{ __('messages.quotations.show_subtotal') }}</span>
                <span class="font-bold text-gray-800" dir="ltr">{{ number_format($quotation->subtotal, 2) }} <span class="text-gray-400">{{ $cur }}</span></span>
            </div>
            @php
                $itemDiscounts = $quotation->total_discount - $quotation->extra_discount;
            @endphp
            @if($itemDiscounts > 0)
            <div class="tot-row flex justify-between items-center px-4 py-2.5 border-b border-gray-100 bg-red-50/10">
                <span class="text-gray-500">{{ $isAr ? 'خصم الأصناف' : 'Item Discounts' }}</span>
                <span class="font-bold text-red-600" dir="ltr">- {{ number_format($itemDiscounts, 2) }} <span class="text-red-300">{{ $cur }}</span></span>
            </div>
            @endif
            @if($quotation->extra_discount > 0)
            <div class="tot-row flex justify-between items-center px-4 py-2.5 border-b border-gray-100 bg-red-50/20">
                <span class="text-gray-500">{{ $isAr ? 'خصم إضافي' : 'Extra Discount' }}</span>
                <span class="font-bold text-red-600" dir="ltr">- {{ number_format($quotation->extra_discount, 2) }} <span class="text-red-300">{{ $cur }}</span></span>
            </div>
            @endif
            @if($itemDiscounts > 0 && $quotation->extra_discount > 0)
            <div class="tot-row flex justify-between items-center px-4 py-2.5 border-b border-gray-100 bg-red-50/30">
                <span class="text-gray-500">{{ $isAr ? 'إجمالي الخصومات' : 'Total Discounts' }}</span>
                <span class="font-bold text-red-600" dir="ltr">- {{ number_format($quotation->total_discount, 2) }} <span class="text-red-300">{{ $cur }}</span></span>
            </div>
            @endif
            @if($quotation->tax_amount > 0)
            <div class="tot-row flex justify-between items-center px-4 py-2.5 border-b border-gray-100">
                <span class="text-gray-500">{{ __('messages.quotations.show_tax') }}</span>
                <span class="font-bold text-gray-800" dir="ltr">+ {{ number_format($quotation->tax_amount, 2) }} <span class="text-gray-400">{{ $cur }}</span></span>
            </div>
            @endif
            <div class="tot-final flex justify-between items-center px-4 py-3" style="background:#005B9F;">
                <span class="font-extrabold text-white text-sm">{{ __('messages.quotations.show_grand') }}</span>
                <span class="font-extrabold text-white text-base" dir="ltr">{{ number_format($quotation->grand_total, 2) }} <span class="text-xs opacity-80">{{ $cur }}</span></span>
            </div>
        </div>
    </div>

    {{-- باركود رقم عرض السعر (تحت خالص) --}}
    <div class="doc-barcode px-8 pt-4 pb-3 border-t border-gray-100 flex items-center justify-center" dir="ltr">
        <svg id="quoteBarcode" style="max-width:100%; height:auto;"></svg>
    </div>

    {{-- تذييل الويب الجديد والمُنسق في سطر واحد --}}
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

    <div class="h-1 bg-gradient-to-r from-[#005B9F] to-[#008A3B]"></div>
</div>

{{-- مكتبتا الباركود و QR + التهيئة --}}
<style>
    /* ضبط شكل نص الباركود — خط أحادي عريض وأسود نظيف */
    #quoteBarcode text {
        font-family: 'Courier New', monospace !important;
        font-weight: 700 !important;
        fill: #0f172a !important;
        letter-spacing: 1px;
    }
    #clientQr img, #clientQr canvas { display: block; }
</style>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs@master/qrcode.min.js"></script>
<script>
    (function () {
        let done = false;
        function renderCodes() {
            if (done) return;

            // ===== الباركود =====
            if (typeof JsBarcode !== 'undefined') {
                try {
                    JsBarcode("#quoteBarcode", @json($quotation->quote_number), {
                        format: "CODE128",
                        width: 1.6,
                        height: 42,
                        fontSize: 14,
                        font: "monospace",
                        fontOptions: "bold",
                        textMargin: 4,
                        margin: 6,
                        background: "#ffffff",
                        lineColor: "#0f172a",
                        displayValue: true
                    });
                } catch (e) { /* تجاهل */ }
            }

            // ===== QR كود العميل =====
            const qrBox = document.getElementById('clientQr');
            if (qrBox && typeof QRCode !== 'undefined' && !qrBox.hasChildNodes()) {
                try {
                    new QRCode(qrBox, {
                        text: @json($quotation->client ? route('clients.quotations', $quotation->client) : ''),
                        width: 78,
                        height: 78,
                        colorDark: "#0f172a",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } catch (e) { /* تجاهل */ }
            }

            if (typeof JsBarcode !== 'undefined' && typeof QRCode !== 'undefined') done = true;
        }
        if (document.readyState !== 'loading') renderCodes();
        else document.addEventListener('DOMContentLoaded', renderCodes);
        window.addEventListener('load', renderCodes);
    })();
</script>

{{-- ============ Modal بيانات الصنف ============ --}}
<div id="lineModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 no-print" role="dialog">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeLineModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fade-in overflow-hidden" dir="{{ $docDir }}">
        <div class="bg-[#1e293b] text-white px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center">
                    <i class="fas fa-box text-sm"></i>
                </div>
                <div>
                    <p id="lmCode" class="font-mono font-bold text-blue-300 text-sm leading-none"></p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ __('messages.quotations.lm_title') }}</p>
                </div>
            </div>
            <button onclick="closeLineModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 text-gray-400 hover:text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 space-y-3 text-sm">
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-xs text-gray-400 mb-1">{{ __('messages.quotations.lm_desc') }}</p>
                <p id="lmDesc" class="font-bold text-gray-900 leading-snug"></p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-gray-400 mb-1">{{ __('messages.quotations.lm_qty') }}</p>
                    <p id="lmQty" class="font-extrabold text-gray-800 text-lg" dir="ltr"></p>
                    <p id="lmUom" class="text-[10px] text-gray-400 mt-0.5"></p>
                </div>
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-gray-400 mb-1">{{ __('messages.quotations.lm_price') }}</p>
                    <p id="lmPrice" class="font-extrabold text-gray-800 text-lg" dir="ltr"></p>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $cur }}</p>
                </div>
                <div class="bg-amber-50 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-amber-600 mb-1">{{ __('messages.quotations.lm_disc') }}</p>
                    <p id="lmDisc" class="font-bold text-amber-700 text-base" dir="ltr"></p>
                </div>
                <div class="bg-purple-50 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-purple-50 mb-1">{{ __('messages.quotations.lm_tax') }}</p>
                    <p id="lmTax" class="font-bold text-purple-700 text-base" dir="ltr"></p>
                </div>
            </div>
            <div class="flex justify-between items-center bg-[#005B9F] text-white rounded-xl px-5 py-3">
                <span class="font-bold text-sm">{{ __('messages.quotations.lm_net') }}</span>
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
</script>

{{-- ============ Modal إرسال البريد ============ --}}
<style>
    /* أنيميشن طيران الورقة كـ ايميل */
    @keyframes paper-fly-{{ $isAr ? 'rtl' : 'ltr' }} {
        0%   { transform: translate(0, 0) rotate(0deg) scale(1); opacity: 1; }
        40%  { transform: translate({{ $isAr ? '40px' : '-40px' }}, -10px) rotate({{ $isAr ? '15' : '-15' }}deg) scale(.95); opacity: 1; }
        100% { transform: translate({{ $isAr ? '180px' : '-180px' }}, -60px) rotate({{ $isAr ? '35' : '-35' }}deg) scale(.4); opacity: 0; }
    }
    .paper-fly { animation: paper-fly-{{ $isAr ? 'rtl' : 'ltr' }} 1.4s ease-in forwards; }

    /* الـ envelope bouncing */
    @keyframes env-bounce {
        0%, 100% { transform: translateY(0) scale(1); }
        50%      { transform: translateY(-8px) scale(1.05); }
    }
    .env-bounce { animation: env-bounce 1.2s ease-in-out infinite; }

    /* خطوط متحركة بتطلع من الايميل */
    @keyframes signal-pulse {
        0%   { transform: scale(.8); opacity: .7; }
        100% { transform: scale(1.8); opacity: 0; }
    }
    .signal-ring {
        position: absolute; inset: 0;
        border-radius: 9999px;
        border: 2px solid #f59e0b;
        animation: signal-pulse 1.5s ease-out infinite;
    }
    .signal-ring.delay-1 { animation-delay: .5s; }
    .signal-ring.delay-2 { animation-delay: 1s; }

    /* progress shimmer */
    @keyframes shimmer {
        0%   { background-position: -200px 0; }
        100% { background-position: 200px 0; }
    }
    .progress-shimmer {
        background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 50%, #fbbf24 100%);
        background-size: 200px 100%;
        animation: shimmer 1.2s linear infinite;
    }

    /* النقاط المتحركة (typing) */
    @keyframes dot-flash {
        0%, 80%, 100% { opacity: .3; transform: scale(.8); }
        40%           { opacity: 1; transform: scale(1.2); }
    }
    .dot { display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #f59e0b; margin: 0 2px; animation: dot-flash 1.4s infinite both; }
    .dot:nth-child(2) { animation-delay: .2s; }
    .dot:nth-child(3) { animation-delay: .4s; }

    /* fade-in/out states */
    .state-block { transition: opacity .3s ease; }
    .state-hidden { display: none; }
</style>

<div id="sendMailModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 no-print" role="dialog">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="sendMailBackdrop"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md animate-fade-in overflow-hidden" dir="{{ $docDir }}">

        {{-- =========== الحالة 1: تأكيد الإرسال =========== --}}
        <div id="state-confirm" class="state-block">
            <div class="bg-amber-500 text-white px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center">
                        <i class="fas fa-envelope text-sm"></i>
                    </div>
                    <div>
                        <p class="font-bold text-base leading-none">{{ $isAr ? 'إرسال عرض السعر' : 'Send Quotation' }}</p>
                        <p class="text-xs opacity-80 mt-0.5">{{ $quotation->quote_number }}</p>
                    </div>
                </div>
                <button type="button" onclick="closeSendMailModal()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/20 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user text-gray-400 w-4 text-center"></i>
                        <span class="text-gray-500">{{ $isAr ? 'العميل:' : 'Client:' }}</span>
                        <span class="font-bold text-gray-800">{{ $clientDisplay }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-at text-gray-400 w-4 text-center"></i>
                        <span class="text-gray-500">{{ $isAr ? 'إرسال إلى:' : 'Send To:' }}</span>
                        <span class="font-bold text-amber-600" dir="ltr">{{ optional($quotation->client)->email }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-paper-plane text-gray-400 w-4 text-center"></i>
                        <span class="text-gray-500">{{ $isAr ? 'إرسال من:' : 'Send From:' }}</span>
                        <span class="font-mono text-xs text-gray-600" dir="ltr">{{ config('mail.from.address') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-paperclip text-gray-400 w-4 text-center"></i>
                        <span class="text-gray-500">{{ $isAr ? 'المرفق:' : 'Attachment:' }}</span>
                        <span class="text-gray-600 font-mono text-xs" dir="ltr">Quotation-{{ $quotation->quote_number }}.pdf</span>
                    </div>
                    @php $ccCount = \App\Models\Setting::where('category', 'notify_email')->count(); @endphp
                    @if($ccCount > 0)
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-200">
                        <i class="fas fa-users text-gray-400 w-4 text-center"></i>
                        <span class="text-gray-500">{{ $isAr ? 'نسخة (CC):' : 'CC:' }}</span>
                        <span class="font-bold text-[#005B9F]">
                            {{ $ccCount }} {{ $isAr ? 'من الإدارة' : 'management' }}
                        </span>
                    </div>
                    @endif
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-xs text-blue-700 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i>
                    {{ $isAr ? 'بعد الإرسال تصبح حالة العرض «مرسل» نهائياً ولا يمكن تعديلها.' : 'After sending, the status becomes «Sent» permanently and cannot be changed.' }}
                </div>

                <form id="sendMailForm" action="{{ route('quotations.send-email', $quotation) }}" method="POST" class="flex flex-col gap-3">
                    @csrf
                    <div class="flex items-center gap-3 justify-end pt-1">
                        <button type="button" onclick="closeSendMailModal()"
                            class="px-5 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 text-sm font-medium transition-colors">
                            {{ $isAr ? 'إلغاء' : 'Cancel' }}
                        </button>
                        <button type="submit" id="sendMailSubmitBtn"
                            class="px-6 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold text-sm flex items-center gap-2 transition-colors">
                            <i class="fas fa-paper-plane"></i>
                            {{ $isAr ? 'إرسال الآن' : 'Send Now' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- =========== الحالة 2: جاري الإرسال (الأنيميشن) =========== --}}
        <div id="state-sending" class="state-block state-hidden">
            <div class="px-8 py-10 text-center">
                {{-- منصة الأنيميشن --}}
                <div class="relative h-32 flex items-center justify-center mb-6">
                    {{-- حلقات الإشارة --}}
                    <div class="relative w-20 h-20 flex items-center justify-center">
                        <div class="signal-ring"></div>
                        <div class="signal-ring delay-1"></div>
                        <div class="signal-ring delay-2"></div>

                        {{-- أيقونة الإيميل --}}
                        <div class="relative w-20 h-20 bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl flex items-center justify-center shadow-lg env-bounce">
                            <i class="fas fa-envelope text-3xl text-white"></i>
                        </div>
                    </div>

                    {{-- الورقة الطائرة --}}
                    <div class="absolute top-2 {{ $isAr ? 'right-1/2 translate-x-1/2' : 'left-1/2 -translate-x-1/2' }} paper-fly">
                        <div class="w-10 h-12 bg-white border border-gray-300 rounded shadow-md flex flex-col items-center justify-center gap-1 p-1">
                            <div class="w-6 h-0.5 bg-gray-300 rounded"></div>
                            <div class="w-7 h-0.5 bg-gray-300 rounded"></div>
                            <div class="w-5 h-0.5 bg-gray-300 rounded"></div>
                            <i class="fas fa-file-pdf text-red-500 text-xs mt-0.5"></i>
                        </div>
                    </div>
                </div>

                <p class="font-bold text-lg text-gray-800 mb-1">
                    {{ $isAr ? 'جارٍ إرسال عرض السعر' : 'Sending Quotation' }}
                    <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                </p>
                <p class="text-xs text-gray-500 mb-5" dir="ltr">{{ optional($quotation->client)->email }}</p>

                {{-- progress bar --}}
                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full w-full progress-shimmer"></div>
                </div>

                <p class="text-[11px] text-gray-400 mt-4">
                    <i class="fas fa-shield-alt"></i>
                    {{ $isAr ? 'يتم تشفير المرفق وإرساله عبر SMTP آمن' : 'Attachment is encrypted & sent via secure SMTP' }}
                </p>
            </div>
        </div>

    </div>
</div>

<script>
    const sendMailModal   = document.getElementById('sendMailModal');
    const stateConfirm    = document.getElementById('state-confirm');
    const stateSending    = document.getElementById('state-sending');
    const sendMailForm    = document.getElementById('sendMailForm');
    const sendMailBtn     = document.getElementById('sendMailSubmitBtn');
    const sendMailBackdrop = document.getElementById('sendMailBackdrop');

    let sendMailIsSubmitting = false;

    function openSendMailModal() {
        // إعادة الحالة للوضع الابتدائي عند فتح الـ modal
        sendMailIsSubmitting = false;
        stateConfirm.classList.remove('state-hidden');
        stateSending.classList.add('state-hidden');
        sendMailModal.classList.remove('hidden');
        sendMailModal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeSendMailModal() {
        // لا نسمح بالإغلاق أثناء الإرسال
        if (sendMailIsSubmitting) return;
        sendMailModal.classList.add('hidden');
        sendMailModal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    // ربط زرار الفتح الأصلي
    document.querySelectorAll('[data-open-send-mail]').forEach(btn => {
        btn.addEventListener('click', openSendMailModal);
    });

    // ربط أي زرار قديم بالـ onclick القديم
    const legacyBtn = document.querySelector('button[onclick*="sendMailModal"]');
    if (legacyBtn) {
        legacyBtn.removeAttribute('onclick');
        legacyBtn.addEventListener('click', openSendMailModal);
    }

    // الـ backdrop يقفل الـ modal لو مش بنبعت
    sendMailBackdrop.addEventListener('click', closeSendMailModal);

    // submit مع منع التكرار
    if (sendMailForm) {
        sendMailForm.addEventListener('submit', function (e) {
            if (sendMailIsSubmitting) {
                e.preventDefault();
                return;
            }
            sendMailIsSubmitting = true;

            // تعطيل الزرار فوراً
            sendMailBtn.disabled = true;
            sendMailBtn.style.pointerEvents = 'none';
            sendMailBtn.style.opacity = '.6';

            // التحول لحالة الأنيميشن
            stateConfirm.classList.add('state-hidden');
            stateSending.classList.remove('state-hidden');

            // نسيب الـ form يتـ submit طبيعي
        });
    }

    // مسح خاص بـ ESC — مش يقفل لو بنبعت
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeLineModal();
            closeSendMailModal();
        }
    });
</script>
@endsection