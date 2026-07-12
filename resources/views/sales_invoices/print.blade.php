<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<title>{{ app()->getLocale() === 'ar' ? 'فاتورة بيع' : 'Sales Invoice' }} - {{ $salesInvoice->invoice_number }}</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');

    @page {
        size: A4;
        margin: 0;
    }

    * { box-sizing: border-box; }

    body {
        font-family: 'Cairo', sans-serif;
        font-size: 13px;
        color: #1e293b;
        margin: 0; 
        padding: 0;
        background: #525659;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .a4-page {
        width: 210mm;
        min-height: 297mm;
        margin: 20px auto;
        background: #ffffff;
        padding: 15mm 15mm;
        position: relative;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }

    @media print {
        body { background: #ffffff; }
        .a4-page { margin: 0; padding: 15mm 15mm; box-shadow: none; width: 100%; min-height: auto; }
        /* Hide browser headers and footers */
        @page { margin: 0; }
    }

    /* الخط العلوي والسفلي المزدوج للهوية */
    .top-global-bar { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
    .top-global-bar td { height: 5px; padding: 0; }

    /* ============== الترويسة الرئيسية ============== */
    table.header-layout { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    table.header-layout td { vertical-align: middle; }
    
    .title-main { font-size: 32px; font-weight: 800; color: #008A3B; margin: 0; line-height: 1.1; }
    .quote-number-text { font-size: 16px; font-weight: 700; color: #475569; margin-top: 4px; font-family: monospace; letter-spacing: 1px;}
    .status-badge { display: inline-block; padding: 4px 15px; background: #e0f2fe; color: #0284c7; font-weight: bold; border-radius: 20px; font-size: 13px; margin-top: 8px; border: 1px solid #bae6fd;}

    /* ============== بلوك بيانات العميل وتفاصيل العرض ============== */
    table.info-layout { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
    table.info-layout > tbody > tr > td { border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 15px 5px; vertical-align: top; }
    
    .block-label { font-size: 11px; color: #94a3b8; font-weight: 700; margin-bottom: 8px; text-transform: uppercase;}
    .client-company-name { font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
    .client-details-text { font-size: 13px; color: #64748b; margin-bottom: 4px; }
    .client-phone-line { font-size: 13px; color: #94a3b8; padding-bottom: 4px; border-bottom: 1px dotted #cbd5e1; display: inline-block; width: 180px; font-family: monospace; }
    
    table.meta-data-table { width: 100%; border-collapse: collapse; }
    table.meta-data-table td { padding: 4px 0; font-size: 13px; }
    table.meta-data-table .meta-label { color: #94a3b8; text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; width: 40%; font-weight: 600;}
    table.meta-data-table .meta-value { font-weight: 700; color: #334155; text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}; }
    table.meta-data-table .meta-value-currency { font-weight: 800; color: #005B9F; font-size: 14px; text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}; }

    /* صف المندوب المخصص */
    table.rep-layout { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.rep-layout td { font-size: 12px; padding: 8px 5px; color: #64748b; border-bottom: 1px solid #f1f5f9; }
    .rep-highlight { color: #1e293b; font-weight: 800; }

    /* ============== جدول الأصناف الاحترافي والمحاذاة الصارمة ============== */
    table.items-data-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 13px; table-layout: fixed; }
    table.items-data-table thead td {
        background-color: #1e293b; 
        color: #ffffff;
        padding: 12px 8px;
        font-size: 12px;
        font-weight: 700;
    }
    
    table.items-data-table tbody td { padding: 12px 8px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight: 600;}
    table.items-data-table tbody tr:nth-child(even) td { background-color: #f8fafc; }
    
    .col-center { text-align: center !important; }
    .col-directional { text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }} !important; }
    
    table.items-data-table tbody td.item-code-cell { color: #005B9F; font-weight: 700; font-family: monospace; font-size: 12px; }
    table.items-data-table tbody td.item-description-cell { color: #1e293b; font-weight: 700; }
    table.items-data-table tbody td.item-qty-cell { font-weight: 800; color: #0f172a; }
    table.items-data-table tbody td.item-discount-cell { color: #dc2626; }
    table.items-data-table tbody td.item-total-cell { font-weight: 800; color: #1e293b; }

    table.items-data-table tfoot td { background-color: #f1f5f9; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; padding: 12px 8px; font-weight: 700; color: #475569; }

    /* ============== الشروط وصندوق الإجماليات ============== */
    table.bottom-layout { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    table.bottom-layout td { vertical-align: top; }
    
    .totals-box-wrapper { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; width: 100%; }
    table.totals-summary-table { width: 100%; border-collapse: collapse; }
    table.totals-summary-table td { padding: 10px 15px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }
    table.totals-summary-table .summary-label { color: #64748b; font-weight: 700; }
    table.totals-summary-table .summary-value { text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}; font-weight: 800; color: #1e293b; }
    
    table.totals-summary-table tr.grand-total-row td { background-color: #008A3B; color: #ffffff; padding: 12px 15px; border-bottom: none; }
    table.totals-summary-table tr.grand-total-row .summary-label { color: #ffffff; font-size: 15px; font-weight: 800; }
    table.totals-summary-table tr.grand-total-row .summary-value { color: #ffffff; font-size: 18px; font-weight: 800; }



    /* ============== الباركود ============== */
    .barcode-section { text-align: center; margin-bottom: 20px; }
    .barcode-section img { height: 60px; max-width: 100%; }
    .barcode-text { font-family: monospace; font-size: 14px; color: #475569; margin-top: 5px; font-weight: 700; letter-spacing: 2px;}

    /* ============== التذييل السفلي ============== */
    .footer-address {
        border-top: 1px solid #cbd5e1;
        border-bottom: 4px solid #005B9F;
        padding: 10px 0;
        text-align: center;
        font-size: 11px;
        color: #64748b;
        line-height: 1.6;
        position: relative;
    }
    .footer-address strong { color: #005B9F; }
    
    @media print {
        .no-print { display: none !important; }
    }
</style>
</head>
@php
    $isAr = app()->getLocale() === 'ar';
    $cur = $salesInvoice->currency;
    $qrData = urlencode("Invoice: {$salesInvoice->invoice_number}\nTotal: {$salesInvoice->grand_total} {$cur}\nDate: {$salesInvoice->invoice_date->format('Y-m-d')}");
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={$qrData}";
    $barcodeUrl = "https://barcode.tec-it.com/barcode.ashx?data=" . urlencode($salesInvoice->invoice_number) . "&code=Code128&translate-esc=true&dpi=96";
@endphp
<body onload="setTimeout(() => window.print(), 500)">

<div class="a4-page">
    <table class="top-global-bar">
        <tr>
            <td style="background-color: #008A3B; width: 50%;"></td>
            <td style="background-color: #005B9F; width: 50%;"></td>
        </tr>
    </table>

    <table class="header-layout">
        <tr>
            <td style="width: 50%; text-align: {{ $isAr ? 'right' : 'left' }}; vertical-align: top;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align: top; width:80px; text-align: {{ $isAr ? 'right' : 'left' }};">
                            <img src="{{ $qrUrl }}" alt="QR Code" style="width:75px; height:75px; border: 1px solid #e2e8f0; border-radius: 6px; padding:3px; background:#fff;">
                        </td>
                        <td style="vertical-align: top; padding-{{ $isAr ? 'right' : 'left' }}: 15px; text-align: {{ $isAr ? 'right' : 'left' }};">
                            <div class="title-main">{{ $isAr ? 'فاتورة بيع' : 'Sales Invoice' }}</div>
                            <div class="quote-number-text">{{ $salesInvoice->invoice_number }}</div>
                            <div class="status-badge" style="margin-top: 4px;">{{ $isAr ? 'معتمدة' : 'Approved' }}</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: {{ $isAr ? 'left' : 'right' }};">
                <table align="{{ $isAr ? 'left' : 'right' }}" style="width: auto; border-collapse: collapse;">
                    <tr>
                        <td style="vertical-align: middle; text-align: {{ $isAr ? 'left' : 'right' }}; padding-{{ $isAr ? 'left' : 'right' }}: 15px;">
                            <div style="font-size: 20px; font-weight: 800; color: #1e293b; line-height: 1.2;">EFC Export</div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 600; margin-top: 2px;">{{ __('messages.app_name') }}<br>Egyptian Foods</div>
                        </td>
                        <td style="vertical-align: middle; padding: 0;">
                            @php $logoPath = public_path('images/EFC-.png'); @endphp
                            @if(file_exists($logoPath))
                                <img src="/images/EFC-.png" style="height: 55px; width: auto; display: block; margin: 0;" alt="Logo">
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="info-layout">
        <tr>
            <td style="width: 35%;">
                <div class="block-label">{{ $isAr ? 'تفاصيل الفاتورة' : 'INVOICE DETAILS' }}</div>
                <table class="meta-data-table">
                    <tr>
                        <td class="meta-label">{{ $isAr ? 'التاريخ:' : 'Date:' }}</td>
                        <td class="meta-value" dir="ltr">{{ optional($salesInvoice->invoice_date)->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">{{ $isAr ? 'أمر البيع:' : 'Sales Order:' }}</td>
                        <td class="meta-value" dir="ltr"><span style="color:#005B9F; font-weight:bold;">{{ optional($salesInvoice->salesOrder)->so_number ?? '—' }}</span></td>
                    </tr>
                    <tr>
                        <td class="meta-label">{{ $isAr ? 'العملة:' : 'Currency:' }}</td>
                        <td class="meta-value-currency">{{ $salesInvoice->currency }}</td>
                    </tr>
                </table>
            </td>
            
            <td style="width: 30%; text-align: center; vertical-align: bottom; padding-bottom: 20px;">
                <div class="client-phone-line">{{ optional($salesInvoice->client)->phone ?? '' }} | {{ optional($salesInvoice->client)->email ?? '' }}</div>
            </td>

            <td style="width: 35%; text-align: {{ $isAr ? 'left' : 'right' }};">
                <div class="block-label" style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ $isAr ? 'مفوترة إلى' : 'BILLED TO' }}</div>
                <div class="client-company-name" style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ optional($salesInvoice->client)->company_name ?? '—' }}</div>
                <div class="client-details-text" style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ optional($salesInvoice->client)->contact_person ?? '' }}</div>
            </td>
        </tr>
    </table>

    <table class="rep-layout">
        <tr>
            <td style="text-align: {{ $isAr ? 'right' : 'left' }};">
                <span class="block-label" style="font-size: 12px; display:inline;">{{ $isAr ? 'المندوب:' : 'Sales Rep:' }}</span>
                <span class="rep-highlight">{{ optional($salesInvoice->salesOrder)->sales_rep ?? '—' }}</span>
            </td>
        </tr>
    </table>

    <table class="items-data-table">
        <thead>
            <tr>
                <td style="width: 5%;" class="col-center">#</td>
                <td style="width: 14%;" class="col-center">{{ $isAr ? 'الكود' : 'Code' }}</td>
                <td style="width: 33%;" class="col-directional">{{ $isAr ? 'البيان / الوصف' : 'Description' }}</td>
                <td style="width: 8%;" class="col-center">{{ $isAr ? 'الكمية' : 'Qty' }}</td>
                <td style="width: 12%;" class="col-center">{{ $isAr ? 'السعر' : 'Price' }}</td>
                <td style="width: 10%;" class="col-center">{{ $isAr ? 'الخصم' : 'Disc' }}</td>
                <td style="width: 18%;" class="col-directional">{{ $isAr ? 'الإجمالي' : 'Total' }}</td>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQty  = $salesInvoice->items->sum('quantity');
                $totalDisc = $salesInvoice->items->sum(fn($l) => $l->quantity * $l->unit_price * $l->discount_percent / 100);
            @endphp

            @foreach($salesInvoice->items as $idx => $line)
            <tr>
                <td class="col-center" style="color: #94a3b8;">{{ $idx + 1 }}</td>
                <td class="col-center item-code-cell">{{ $line->item_code ?? '—' }}</td>
                <td class="col-directional item-description-cell">{{ $line->displayDescription($isAr ? 'ar' : 'en') }}</td>
                <td class="col-center item-qty-cell" dir="ltr">{{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }}</td>
                <td class="col-center" dir="ltr">{{ number_format($line->unit_price, 2) }}</td>
                <td class="col-center item-discount-cell">
                    @if($line->discount_percent > 0){{ rtrim(rtrim(number_format($line->discount_percent, 2), '0'), '.') }}%@else—@endif
                </td>
                <td class="col-directional item-total-cell" dir="ltr">{{ number_format($line->net_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="col-directional">
                    {{ $isAr ? 'الإجمالي' : 'Total' }} <span style="font-weight: normal; color: #94a3b8; font-size: 11px;">({{ $salesInvoice->items->count() }} {{ $isAr ? 'صنف' : 'items' }})</span>
                </td>
                <td class="col-center" dir="ltr">{{ rtrim(rtrim(number_format($totalQty, 2), '0'), '.') }}</td>
                <td></td>
                <td class="col-center" style="color: #dc2626;" dir="ltr">
                    @if($totalDisc > 0)- {{ number_format($totalDisc, 2) }}@else—@endif
                </td>
                <td class="col-directional" style="color: #008A3B; font-weight: 800; font-size: 14px;" dir="ltr">
                    {{ number_format($salesInvoice->grand_total, 2) }} <span style="font-size: 10px; color: #94a3b8; font-weight: 600;">{{ $salesInvoice->currency }}</span>
                </td>
            </tr>
        </tfoot>
    </table>

    <table class="bottom-layout">
        <tr>
            <td style="width: 55%; padding-{{ $isAr ? 'left' : 'right' }}: 30px;">
                @if($salesInvoice->notes)
                    <div class="block-label" style="text-align: {{ $isAr ? 'right' : 'left' }};">{{ $isAr ? 'ملاحظات' : 'Notes' }}</div>
                    <div style="font-size: 12px; color: #475569; line-height: 1.6; font-weight: 600;">{!! nl2br(e($salesInvoice->notes)) !!}</div>
                @endif
            </td>
            
            <td style="width: 45%;">
                <div class="totals-box-wrapper">
                    <table class="totals-summary-table">
                        <tr>
                            <td class="summary-label">{{ $isAr ? 'الإجمالي الفرعي' : 'Subtotal' }}</td>
                            <td class="summary-value" dir="ltr">{{ number_format($salesInvoice->subtotal, 2) }} <span style="color: #94a3b8; font-size: 10px;">{{ $salesInvoice->currency }}</span></td>
                        </tr>
                        @if($salesInvoice->total_discount > 0)
                        <tr>
                            <td class="summary-label">{{ $isAr ? 'الخصم' : 'Discount' }}</td>
                            <td class="summary-value" style="color: #dc2626;" dir="ltr">- {{ number_format($salesInvoice->total_discount, 2) }} <span style="color: #fca5a5; font-size: 10px;">{{ $salesInvoice->currency }}</span></td>
                        </tr>
                        @endif
                        @if($salesInvoice->tax_amount > 0)
                        <tr>
                            <td class="summary-label">{{ $isAr ? 'الضريبة' : 'Tax' }}</td>
                            <td class="summary-value" dir="ltr">+ {{ number_format($salesInvoice->tax_amount, 2) }} <span style="color: #94a3b8; font-size: 10px;">{{ $salesInvoice->currency }}</span></td>
                        </tr>
                        @endif
                        <tr class="grand-total-row">
                            <td class="summary-label">{{ $isAr ? 'الإجمالي النهائي' : 'Grand Total' }}</td>
                            <td class="summary-value" dir="ltr">
                                {{ number_format($salesInvoice->grand_total, 2) }}
                                <span style="font-size: 11px; font-weight: 600; opacity: 0.9;">{{ $salesInvoice->currency }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>



    <div class="barcode-section">
        <img src="{{ $barcodeUrl }}" alt="Barcode">
        <div class="barcode-text">{{ $salesInvoice->invoice_number }}</div>
    </div>

    <div class="footer-address">
        <strong>📍 Head Office:</strong> City Star Towers - Tower 5, Apartment 15, 10th District, 6th of October City, Giza, Egypt<br>
        <strong>📞 Phone:</strong> (+20) 15-5772-2227 &nbsp;|&nbsp; <strong>✉️ Email:</strong> info@efcexport.com<br>
        <strong>🌐 Web:</strong> www.efcexport.com
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #005B9F; color: #fff; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-family: Cairo, sans-serif;">{{ $isAr ? 'طباعة مرة أخرى' : 'Print Again' }}</button>
    </div>
</div>

</body>
</html>
