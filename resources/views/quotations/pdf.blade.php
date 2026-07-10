<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
    @page { margin: 8mm 10mm; }

    * { box-sizing: border-box; }

    body {
        font-family: dejavusans, sans-serif;
        font-size: 11px;
        color: #1e293b;
        margin: 0; padding: 0;
    }

    /* الخط العلوي والسفلي المزدوج للهوية */
    .top-global-bar { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .top-global-bar td { height: 4px; padding: 0; }

    /* ============== الترويسة الرئيسية ============== */
    table.header-layout { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
    table.header-layout td { vertical-align: middle; }
    
    .title-main { font-size: 26px; font-weight: bold; color: #005B9F; margin: 0; line-height: 1.1; }
    .quote-number-text { font-size: 13px; font-weight: bold; color: #64748b; margin-top: 4px; font-family: monospace; }

    /* ============== بلوك بيانات العميل وتفاصيل العرض ============== */
    table.info-layout { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
    table.info-layout > tbody > tr > td { border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 12px 4px; vertical-align: top; }
    
    .block-label { font-size: 9px; color: #94a3b8; font-weight: bold; margin-bottom: 6px; }
    .client-company-name { font-size: 15px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
    .client-details-text { font-size: 11px; color: #64748b; margin-bottom: 2px; }
    .client-phone-line { font-size: 11px; color: #64748b; padding-bottom: 2px; border-bottom: 1px dotted #cbd5e1; display: inline-block; width: 140px; font-family: monospace; }
    
    table.meta-data-table { width: 100%; border-collapse: collapse; }
    table.meta-data-table td { padding: 3px 0; font-size: 11px; }
    table.meta-data-table .meta-label { color: #94a3b8; text-align: right; width: 40%; }
    table.meta-data-table .meta-value { font-weight: bold; color: #334155; text-align: left; }
    table.meta-data-table .meta-value-currency { font-weight: bold; color: #005B9F; font-size: 12px; text-align: left; }

    /* صف المندوب المخصص */
    table.rep-layout { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    table.rep-layout td { font-size: 11px; padding: 6px 4px; color: #64748b; border-bottom: 1px solid #f1f5f9; }
    .rep-highlight { color: #1e293b; font-weight: bold; }

    /* ============== جدول الأصناف الاحترافي والمحاذاة الصارمة ============== */
    table.items-data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; table-layout: fixed; }
    table.items-data-table thead td {
        background-color: #1e293b; 
        color: #ffffff;
        padding: 9px 6px;
        font-size: 10px;
        font-weight: bold;
    }
    
    table.items-data-table tbody td { padding: 9px 6px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
    table.items-data-table tbody tr:nth-child(even) td { background-color: #f8fafc; }
    
    /* فئات التحكم بالمحاذاة الموحدة لمنع أي انحراف */
    .col-center { text-align: center !important; }
    .col-directional { text-align: {{ $isAr ? 'right' : 'left' }} !important; }
    
    table.items-data-table tbody td.item-code-cell { color: #005B9F; font-weight: bold; font-family: monospace; font-size: 10px; }
    table.items-data-table tbody td.item-description-cell { color: #1e293b; font-weight: bold; }
    table.items-data-table tbody td.item-qty-cell { font-weight: bold; color: #475569; }
    table.items-data-table tbody td.item-discount-cell { color: #dc2626; }
    table.items-data-table tbody td.item-total-cell { font-weight: bold; color: #1e293b; }

    /* تذييل الجدول (الإجمالي) */
    table.items-data-table tfoot td { background-color: #f1f5f9; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; padding: 9px 6px; font-weight: bold; color: #475569; }

    /* ============== الشروط وصندوق الإجماليات ============== */
    table.bottom-layout { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
    table.bottom-layout td { vertical-align: top; }
    
    .totals-box-wrapper { border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; width: 100%; }
    table.totals-summary-table { width: 100%; border-collapse: collapse; }
    table.totals-summary-table td { padding: 7px 12px; font-size: 11px; border-bottom: 1px solid #f1f5f9; }
    table.totals-summary-table .summary-label { color: #64748b; font-weight: bold; }
    table.totals-summary-table .summary-value { text-align: {{ $isAr ? 'left' : 'right' }}; font-weight: bold; color: #1e293b; }
    
    table.totals-summary-table tr.grand-total-row td { background-color: #005B9F; color: #ffffff; padding: 9px 12px; border-bottom: none; }
    table.totals-summary-table tr.grand-total-row .summary-label { color: #ffffff; font-size: 12px; font-weight: bold; }
    table.totals-summary-table tr.grand-total-row .summary-value { color: #ffffff; font-size: 14px; font-weight: bold; }

    /* ============== التوقيعات ============== */
    table.signature-layout { width: 100%; border-collapse: collapse; margin-top: 35px; }
    table.signature-layout td { width: 50%; text-align: center; vertical-align: top; }
    .signature-dash-line { border-bottom: 1px dashed #cbd5e1; width: 65%; margin: 0 auto 6px; }
    .signature-title-label { font-size: 10px; color: #94a3b8; font-weight: bold; }
    .signature-value-name { font-size: 11px; font-weight: bold; color: #475569; margin-top: 4px; }

    /* ============== التذييل السفلي ============== */
    table.footer-layout { width: 100%; border-collapse: collapse; margin-top: 25px; border-top: 2px solid #008A3B; padding-top: 6px; }
    table.footer-layout td { font-size: 9px; color: #94a3b8; }
</style>
</head>
<body>

{{-- شريط ثنائي اللون علوي للهوية البصرية --}}
<table class="top-global-bar">
    <tr>
        <td style="background-color: #005B9F; width: 50%;"></td>
        <td style="background-color: #008A3B; width: 50%;"></td>
    </tr>
</table>

{{-- ========== الترويسة الهيكلية (اللوجو أقصى اليمين، والكلمة أقصى اليسار) ========== --}}
<table class="header-layout">
    <tr>
        {{-- اللوجو واسم الشركة (أقصى اليمين) --}}
        <td style="width: 50%; vertical-align: top; text-align: {{ $isAr ? 'right' : 'left' }};">
            <table align="{{ $isAr ? 'right' : 'left' }}" style="width: auto; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: middle; padding: 0;">
                        @php $logoPath = public_path('images/EFC-.png'); @endphp
                        @if(file_exists($logoPath))
                            <img src="{{ $logoPath }}" style="height: 38px; width: auto; display: block; margin: 0;" alt="Logo">
                        @else
                            <h2 style="color:#008A3B; margin:0; font-size:18px;">EFC Export</h2>
                        @endif
                    </td>
                    <td style="vertical-align: middle; text-align: {{ $isAr ? 'right' : 'left' }}; padding-{{ $isAr ? 'right' : 'left' }}: 10px;">
                        <div style="font-size: 15px; font-weight: bold; color: #1e293b; line-height: 1.2;">{{ __('messages.app_name') }}</div>
                        <div style="font-size: 9px; color: #94a3b8; font-weight: normal; margin-top: 1px;">Egyptian Foods</div>
                    </td>
                </tr>
            </table>
        </td>

        {{-- عنوان العرض ورقمه (أقصى اليسار) --}}
        <td style="width: 50%; text-align: {{ $isAr ? 'left' : 'right' }}; vertical-align: top;">
            <div class="title-main">{{ $isAr ? 'عرض سعر' : 'Price Quotation' }}</div>
            <div class="quote-number-text">{{ $quotation->quote_number }}</div>
        </td>
    </tr>
</table>

{{-- ========== بلوك بيانات العميل وتفاصيل العرض ========== --}}
<table class="info-layout">
    <tr>
        {{-- تفاصيل العرض المالي --}}
        <td style="width: 35%;">
            <div class="block-label">{{ $isAr ? 'تفاصيل العرض' : 'QUOTATION DETAILS' }}</div>
            <table class="meta-data-table">
                <tr>
                    <td class="meta-label">{{ $isAr ? 'تاريخ الإصدار' : 'Issue Date' }}</td>
                    <td class="meta-value">{{ optional($quotation->quote_date)->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">{{ $isAr ? 'صالح حتى' : 'Valid Until' }}</td>
                    <td class="meta-value">{{ optional($quotation->expiry_date)->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">{{ $isAr ? 'العملة' : 'Currency' }}</td>
                    <td class="meta-value-currency">{{ $quotation->currency }}</td>
                </tr>
            </table>
        </td>

        {{-- رقم الهاتف بالمنتصف --}}
        <td style="width: 30%; text-align: center; vertical-align: bottom; padding-bottom: 15px;">
            <div class="client-phone-line">{{ optional($quotation->client)->phone ?? '' }}</div>
        </td>

        {{-- بيانات العميل --}}
        <td style="width: 35%; text-align: {{ $isAr ? 'left' : 'right' }};">
            <div class="block-label">{{ $isAr ? 'مقدّم إلى' : 'PREPARED FOR' }}</div>
            <div class="client-company-name">{{ optional($quotation->client)->company_name ?? '—' }}</div>
            <div class="client-details-text">{{ optional($quotation->client)->contact_person ?? '' }}</div>
        </td>
    </tr>
</table>

{{-- صف المندوب --}}
<table class="rep-layout">
    <tr>
        <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
            @if($quotation->sales_rep)
                <span class="block-label" style="font-size: 11px;">{{ $isAr ? 'مندوب:' : 'Sales Rep:' }}</span>
                <span class="rep-highlight">{{ $quotation->sales_rep }}</span>
            @endif
        </td>
    </tr>
</table>

{{-- ========== جدول الحسابات والأصناف بالمحاذاة الموحدة الصارمة ========== --}}
<table class="items-data-table">
    <thead>
        <tr>
            <td style="width: 5%;" class="col-center">#</td>
            <td style="width: 14%;" class="col-center">{{ $isAr ? 'الكود' : 'Code' }}</td>
            <td style="width: 33%;" class="col-directional">{{ $isAr ? 'البيان / الوصف' : 'Description' }}</td>
            <td style="width: 8%;" class="col-center">{{ $isAr ? 'الكمية' : 'Qty' }}</td>
            <td style="width: 12%;" class="col-center">{{ $isAr ? 'سعر الوحدة' : 'Unit Price' }}</td>
            <td style="width: 10%;" class="col-center">{{ $isAr ? 'خصم%' : 'Disc%' }}</td>
            <td style="width: 18%;" class="col-directional">{{ $isAr ? 'الإجمالي' : 'Total' }}</td>
        </tr>
    </thead>
    <tbody>
        @php
            $totalQty  = $quotation->items->sum('quantity');
            $totalDisc = $quotation->items->sum(fn($l) => $l->quantity * $l->list_price * $l->discount_percent / 100);
            $totalNet  = $quotation->items->sum('net_total');
        @endphp

        @foreach($quotation->items as $idx => $line)
        <tr>
            <td class="col-center" style="color: #94a3b8;">{{ $idx + 1 }}</td>
            <td class="col-center item-code-cell">{{ $line->item_code ?? '—' }}</td>
            <td class="col-directional item-description-cell">{{ $line->displayDescription($isAr ? 'ar' : 'en') }}</td>
            <td class="col-center item-qty-cell">{{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }}</td>
            <td class="col-center">{{ number_format($line->list_price, 2) }}</td>
            <td class="col-center item-discount-cell">
                @if($line->discount_percent > 0){{ rtrim(rtrim(number_format($line->discount_percent, 2), '0'), '.') }}%@else—@endif
            </td>
            <td class="col-directional item-total-cell">{{ number_format($line->net_total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="col-directional">
                {{ $isAr ? 'الإجمالي' : 'Total' }} <span style="font-weight: normal; color: #94a3b8; font-size: 9px;">({{ $quotation->items->count() }} {{ $isAr ? 'صنف' : 'items' }})</span>
            </td>
            <td class="col-center">{{ rtrim(rtrim(number_format($totalQty, 2), '0'), '.') }}</td>
            <td></td>
            <td class="col-center" style="color: #dc2626;">
                @if($totalDisc > 0)- {{ number_format($totalDisc, 2) }}@else—@endif
            </td>
            <td class="col-directional" style="color: #005B9F; font-weight: bold; font-size: 12px;">
                {{ number_format($totalNet, 2) }} <span style="font-size: 8px; color: #94a3b8; font-weight: normal;">{{ $quotation->currency }}</span>
            </td>
        </tr>
    </tfoot>
</table>

{{-- ========== قوالب الشروط القانونية وصناديق المال ========== --}}
<table class="bottom-layout">
    <tr>
        {{-- الشروط والأحكام --}}
        <td style="width: 55%; padding-{{ $isAr ? 'left' : 'right' }}: 25px;">
            @if($quotation->terms)
                <div class="block-label" style="text-align: {{ $isAr ? 'right' : 'left' }};">{{ $isAr ? 'الشروط والأحكام' : 'Terms & Conditions' }}</div>
                <div style="font-size: 10px; color: #475569; line-height: 1.6;">{!! nl2br(e($quotation->terms)) !!}</div>
            @endif
        </td>
        
        {{-- صندوق الإجماليات --}}
        <td style="width: 45%;">
            <div class="totals-box-wrapper">
                <table class="totals-summary-table">
                    <tr>
                        <td class="summary-label">{{ $isAr ? 'المجموع' : 'Subtotal' }}</td>
                        <td class="summary-value">{{ number_format($quotation->subtotal, 2) }} <span style="color: #94a3b8; font-size: 8px;">{{ $quotation->currency }}</span></td>
                    </tr>
                    @if($quotation->total_discount > 0)
                    <tr>
                        <td class="summary-label">{{ $isAr ? 'الخصم' : 'Discount' }}</td>
                        <td class="summary-value" style="color: #dc2626;">- {{ number_format($quotation->total_discount, 2) }} <span style="color: #fca5a5; font-size: 8px;">{{ $quotation->currency }}</span></td>
                    </tr>
                    @endif
                    @if($quotation->tax_amount > 0)
                    <tr>
                        <td class="summary-label">{{ $isAr ? 'الضريبة' : 'Tax' }}</td>
                        <td class="summary-value">+ {{ number_format($quotation->tax_amount, 2) }} <span style="color: #94a3b8; font-size: 8px;">{{ $quotation->currency }}</span></td>
                    </tr>
                    @endif
                    <tr class="grand-total-row">
                        <td class="summary-label">{{ $isAr ? 'الإجمالي النهائي' : 'Grand Total' }}</td>
                        <td class="summary-value">
                            {{ number_format($quotation->grand_total, 2) }}
                            <span style="font-size: 9px; font-weight: normal; opacity: 0.85;">{{ $quotation->currency }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

{{-- ========== التوقيعات المعتمدة ========== --}}
<table class="signature-layout">
    <tr>
        <td>
            <div class="signature-dash-line"></div>
            <div class="signature-title-label">{{ $isAr ? 'اعتماد العميل' : 'Client Approval' }}</div>
        </td>
        <td>
            <div class="signature-dash-line"></div>
            <div class="signature-title-label">{{ $isAr ? 'المندوب / القسم' : 'Sales Rep / Department' }}</div>
            @if($quotation->sales_rep)
                <div class="signature-value-name">{{ $quotation->sales_rep }}</div>
            @endif
        </td>
    </tr>
</table>

{{-- ========== التذييل السفلي ========== --}}
<table class="footer-layout">
    <tr>
        <td style="text-align: {{ $isAr ? 'right' : 'left' }}; font-family: monospace;">
            {{ $quotation->quote_number }} | {{ optional($quotation->quote_date)->format('Y-m-d') }}
        </td>
        <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
            — {{ __('messages.app_name') }}
        </td>
    </tr>
</table>

{{-- ========== باركود رقم عرض السعر (تحت خالص) ========== --}}
<div style="text-align: center; margin-top: 18px;" dir="ltr">
    <barcode code="{{ $quotation->quote_number }}" type="C128B" size="0.9" height="0.7" />
    <div style="font-family: monospace; font-size: 10px; color: #475569; margin-top: 2px; letter-spacing: 1px;">
        {{ $quotation->quote_number }}
    </div>
</div>

</body>
</html>