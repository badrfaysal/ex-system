<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<title>{{ app()->getLocale() === 'ar' ? 'فاتورة شراء' : 'Purchase Invoice' }} - {{ $purchaseInvoice->invoice_number }}</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');

    @page { size: A4; margin: 0; }
    * { box-sizing: border-box; }

    body {
        font-family: 'Cairo', sans-serif; font-size: 12px; color: #1e293b;
        margin: 0; padding: 0; background: #525659;
        -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;
    }

    /* الصفحة بارتفاع A4 ثابت — المحتوى بيتصغّر تلقائياً لو زاد عشان يفضل صفحة واحدة */
    .a4-page {
        width: 210mm; height: 297mm; margin: 20px auto; background: #fff;
        padding: 10mm 11mm; position: relative; box-shadow: 0 0 10px rgba(0,0,0,0.2); overflow: hidden;
    }
    .a4-inner { transform-origin: top center; }

    @media print {
        body { background: #fff; }
        .a4-page { margin: 0; box-shadow: none; }
    }

    .top-global-bar { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .top-global-bar td { height: 5px; padding: 0; }

    table.header-layout { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.header-layout td { vertical-align: middle; }
    .title-main { font-size: 24px; font-weight: 800; color: #005B9F; margin: 0; line-height: 1.1; }
    .quote-number-text { font-size: 14px; font-weight: 700; color: #475569; margin-top: 3px; font-family: monospace; letter-spacing: 1px; }
    .status-badge { display: inline-block; padding: 3px 12px; background: #fee2e2; color: #dc2626; font-weight: bold; border-radius: 20px; font-size: 12px; margin-top: 5px; border: 1px solid #fecaca; }

    table.info-layout { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.info-layout > tbody > tr > td { border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 9px 5px; vertical-align: top; }
    .block-label { font-size: 10px; color: #94a3b8; font-weight: 700; margin-bottom: 6px; text-transform: uppercase; }
    .client-company-name { font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 3px; }
    .client-details-text { font-size: 12px; color: #64748b; margin-bottom: 3px; }
    .client-phone-line { font-size: 12px; color: #94a3b8; padding-bottom: 4px; border-bottom: 1px dotted #cbd5e1; display: inline-block; width: 180px; font-family: monospace; }

    table.meta-data-table { width: 100%; border-collapse: collapse; }
    table.meta-data-table td { padding: 3px 0; font-size: 12px; }
    table.meta-data-table .meta-label { color: #94a3b8; text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; width: 40%; font-weight: 600; }
    table.meta-data-table .meta-value { font-weight: 700; color: #334155; text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}; }
    table.meta-data-table .meta-value-currency { font-weight: 800; color: #005B9F; font-size: 13px; text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}; }

    table.items-data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 12px; table-layout: fixed; }
    table.items-data-table thead td { background-color: #1e293b; color: #fff; padding: 8px; font-size: 11px; font-weight: 700; }
    table.items-data-table tbody td { padding: 7px 8px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight: 600; }
    table.items-data-table tbody tr:nth-child(even) td { background-color: #f8fafc; }
    .col-center { text-align: center !important; }
    .col-directional { text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }} !important; }
    table.items-data-table tbody td.item-code-cell { color: #005B9F; font-weight: 700; font-family: monospace; font-size: 11px; }
    table.items-data-table tbody td.item-description-cell { color: #1e293b; font-weight: 700; }
    table.items-data-table tbody td.item-qty-cell { font-weight: 800; color: #0f172a; }
    table.items-data-table tbody td.item-discount-cell { color: #dc2626; }
    table.items-data-table tbody td.item-total-cell { font-weight: 800; color: #1e293b; }
    table.items-data-table tfoot td { background-color: #f1f5f9; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; padding: 8px; font-weight: 700; color: #475569; }

    table.bottom-layout { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    table.bottom-layout td { vertical-align: top; }
    .totals-box-wrapper { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; width: 100%; }
    table.totals-summary-table { width: 100%; border-collapse: collapse; }
    table.totals-summary-table td { padding: 8px 14px; font-size: 12px; border-bottom: 1px solid #f1f5f9; }
    table.totals-summary-table .summary-label { color: #64748b; font-weight: 700; }
    table.totals-summary-table .summary-value { text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}; font-weight: 800; color: #1e293b; }
    table.totals-summary-table tr.grand-total-row td { background-color: #005B9F; color: #fff; padding: 10px 14px; border-bottom: none; }
    table.totals-summary-table tr.grand-total-row .summary-label { color: #fff; font-size: 14px; font-weight: 800; }
    table.totals-summary-table tr.grand-total-row .summary-value { color: #fff; font-size: 17px; font-weight: 800; }

    .barcode-section { text-align: center; margin-bottom: 10px; }
    .barcode-section img { height: 40px; max-width: 100%; }
    .barcode-text { font-family: monospace; font-size: 13px; color: #475569; margin-top: 4px; font-weight: 700; letter-spacing: 2px; }

    .footer-address { border-top: 1px solid #cbd5e1; border-bottom: 4px solid #005B9F; padding: 8px 0; text-align: center; font-size: 10px; color: #64748b; line-height: 1.5; }
    .footer-address strong { color: #005B9F; }

    @media print { .no-print { display: none !important; } }
</style>
</head>
@php
    $isAr = app()->getLocale() === 'ar';
    $cur = $purchaseInvoice->currency;
    $vendorName = $isAr ? $purchaseInvoice->vendor->name_ar : ($purchaseInvoice->vendor->name_en ?: $purchaseInvoice->vendor->name_ar);
    $qrData = urlencode("Invoice: {$purchaseInvoice->invoice_number}\nTotal: {$purchaseInvoice->grand_total} {$cur}\nDate: {$purchaseInvoice->invoice_date->format('Y-m-d')}");
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={$qrData}";
    $barcodeUrl = "https://barcode.tec-it.com/barcode.ashx?data=" . urlencode($purchaseInvoice->invoice_number) . "&code=Code128&translate-esc=true&dpi=96";
@endphp
<body>

<div class="a4-page">
  <div class="a4-inner">
    <table class="top-global-bar">
        <tr>
            <td style="background-color: #005B9F; width: 50%;"></td>
            <td style="background-color: #008A3B; width: 50%;"></td>
        </tr>
    </table>

    <table class="header-layout">
        <tr>
            <td style="width: 50%; text-align: {{ $isAr ? 'right' : 'left' }}; vertical-align: top;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align: top; width:70px; text-align: {{ $isAr ? 'right' : 'left' }};">
                            <img src="{{ $qrUrl }}" alt="QR Code" style="width:62px; height:62px; border: 1px solid #e2e8f0; border-radius: 6px; padding:3px; background:#fff;">
                        </td>
                        <td style="vertical-align: top; padding-{{ $isAr ? 'right' : 'left' }}: 14px; text-align: {{ $isAr ? 'right' : 'left' }};">
                            <div class="title-main">{{ $isAr ? 'فاتورة شراء' : 'Purchase Invoice' }}</div>
                            <div class="quote-number-text">{{ $purchaseInvoice->invoice_number }}</div>
                            <div class="status-badge">{{ $isAr ? 'التزام مسجّل' : 'Recorded' }}</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; text-align: {{ $isAr ? 'left' : 'right' }};">
                <table align="{{ $isAr ? 'left' : 'right' }}" style="width: auto; border-collapse: collapse;">
                    <tr>
                        <td style="vertical-align: middle; text-align: {{ $isAr ? 'left' : 'right' }}; padding-{{ $isAr ? 'left' : 'right' }}: 14px;">
                            <div style="font-size: 18px; font-weight: 800; color: #1e293b; line-height: 1.2;">EFC Export</div>
                            <div style="font-size: 10px; color: #64748b; font-weight: 600; margin-top: 2px;">{{ __('messages.app_name') }}<br>Egyptian Foods</div>
                        </td>
                        <td style="vertical-align: middle; padding: 0;">
                            @php $logoPath = public_path('images/EFC-.png'); @endphp
                            @if(file_exists($logoPath))
                                <img src="/images/EFC-.png" style="height: 48px; width: auto; display: block; margin: 0;" alt="Logo">
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
                        <td class="meta-value" dir="ltr">{{ optional($purchaseInvoice->invoice_date)->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">{{ $isAr ? 'مركز التكلفة:' : 'Cost Center:' }}</td>
                        <td class="meta-value" dir="ltr"><span style="color:#005B9F; font-weight:bold;">{{ optional($purchaseInvoice->quotation)->quote_number ?? '—' }}</span></td>
                    </tr>
                    @if($purchaseInvoice->vendor_invoice_number)
                    <tr>
                        <td class="meta-label">{{ $isAr ? 'رقم فاتورة المورد:' : 'Vendor Invoice No.:' }}</td>
                        <td class="meta-value" dir="ltr">{{ $purchaseInvoice->vendor_invoice_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="meta-label">{{ $isAr ? 'العملة:' : 'Currency:' }}</td>
                        <td class="meta-value-currency">{{ $purchaseInvoice->currency }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 30%; text-align: center; vertical-align: bottom; padding-bottom: 16px;">
                <div class="client-phone-line">{{ $purchaseInvoice->vendor->mobile ?? '' }} | {{ $purchaseInvoice->vendor->email ?? '' }}</div>
            </td>
            <td style="width: 35%; text-align: {{ $isAr ? 'left' : 'right' }};">
                <div class="block-label" style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ $isAr ? 'المورد' : 'PURCHASED FROM' }}</div>
                <div class="client-company-name" style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ $vendorName ?? '—' }}</div>
                <div class="client-details-text" style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ $purchaseInvoice->vendor->contact_person_name ?? '' }}</div>
            </td>
        </tr>
    </table>

    <table class="items-data-table">
        <thead>
            <tr>
                <td style="width: 5%;" class="col-center">#</td>
                <td style="width: 14%;" class="col-center">{{ $isAr ? 'الكود' : 'Code' }}</td>
                <td style="width: 37%;" class="col-directional">{{ $isAr ? 'البيان / الوصف' : 'Description' }}</td>
                <td style="width: 9%;" class="col-center">{{ $isAr ? 'الكمية' : 'Qty' }}</td>
                <td style="width: 13%;" class="col-center">{{ $isAr ? 'السعر' : 'Price' }}</td>
                <td style="width: 10%;" class="col-center">{{ $isAr ? 'الخصم' : 'Disc' }}</td>
                <td style="width: 18%;" class="col-directional">{{ $isAr ? 'الإجمالي' : 'Total' }}</td>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQty  = $purchaseInvoice->items->sum('quantity');
                $totalDisc = $purchaseInvoice->items->sum(fn($l) => $l->quantity * $l->unit_price * $l->discount_percent / 100);
            @endphp
            @foreach($purchaseInvoice->items as $idx => $line)
            <tr>
                <td class="col-center" style="color: #94a3b8;">{{ $idx + 1 }}</td>
                <td class="col-center item-code-cell">{{ $line->item_code ?? '—' }}</td>
                <td class="col-directional item-description-cell">{{ $line->description }}</td>
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
                    {{ $isAr ? 'الإجمالي' : 'Total' }} <span style="font-weight: normal; color: #94a3b8; font-size: 11px;">({{ $purchaseInvoice->items->count() }} {{ $isAr ? 'صنف' : 'items' }})</span>
                </td>
                <td class="col-center" dir="ltr">{{ rtrim(rtrim(number_format($totalQty, 2), '0'), '.') }}</td>
                <td></td>
                <td class="col-center" style="color: #dc2626;" dir="ltr">
                    @if($totalDisc > 0)- {{ number_format($totalDisc, 2) }}@else—@endif
                </td>
                <td class="col-directional" style="color: #005B9F; font-weight: 800; font-size: 13px;" dir="ltr">
                    {{ number_format($purchaseInvoice->grand_total, 2) }} <span style="font-size: 10px; color: #94a3b8; font-weight: 600;">{{ $purchaseInvoice->currency }}</span>
                </td>
            </tr>
        </tfoot>
    </table>

    <table class="bottom-layout">
        <tr>
            <td style="width: 55%; padding-{{ $isAr ? 'left' : 'right' }}: 30px;">
                @if($purchaseInvoice->notes)
                    <div class="block-label" style="text-align: {{ $isAr ? 'right' : 'left' }};">{{ $isAr ? 'ملاحظات' : 'Notes' }}</div>
                    <div style="font-size: 11px; color: #475569; line-height: 1.5; font-weight: 600;">{!! nl2br(e($purchaseInvoice->notes)) !!}</div>
                @endif
            </td>
            <td style="width: 45%;">
                <div class="totals-box-wrapper">
                    <table class="totals-summary-table">
                        <tr>
                            <td class="summary-label">{{ $isAr ? 'الإجمالي الفرعي' : 'Subtotal' }}</td>
                            <td class="summary-value" dir="ltr">{{ number_format($purchaseInvoice->subtotal, 2) }} <span style="color: #94a3b8; font-size: 10px;">{{ $purchaseInvoice->currency }}</span></td>
                        </tr>
                        @if($purchaseInvoice->total_discount > 0)
                        <tr>
                            <td class="summary-label">{{ $isAr ? 'الخصم' : 'Discount' }}</td>
                            <td class="summary-value" style="color: #dc2626;" dir="ltr">- {{ number_format($purchaseInvoice->total_discount, 2) }} <span style="color: #fca5a5; font-size: 10px;">{{ $purchaseInvoice->currency }}</span></td>
                        </tr>
                        @endif
                        @if($purchaseInvoice->tax_amount > 0)
                        <tr>
                            <td class="summary-label">{{ $isAr ? 'الضريبة' : 'Tax' }}</td>
                            <td class="summary-value" dir="ltr">+ {{ number_format($purchaseInvoice->tax_amount, 2) }} <span style="color: #94a3b8; font-size: 10px;">{{ $purchaseInvoice->currency }}</span></td>
                        </tr>
                        @endif
                        <tr class="grand-total-row">
                            <td class="summary-label">{{ $isAr ? 'الإجمالي النهائي' : 'Grand Total' }}</td>
                            <td class="summary-value" dir="ltr">
                                {{ number_format($purchaseInvoice->grand_total, 2) }}
                                <span style="font-size: 11px; font-weight: 600; opacity: 0.9;">{{ $purchaseInvoice->currency }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="barcode-section">
        <img src="{{ $barcodeUrl }}" alt="Barcode">
        <div class="barcode-text">{{ $purchaseInvoice->invoice_number }}</div>
    </div>

    <div class="footer-address">
        <strong>📍 Head Office:</strong> City Star Towers - Tower 5, Apartment 15, 10th District, 6th of October City, Giza, Egypt<br>
        <strong>📞 Phone:</strong> (+20) 15-5772-2227 &nbsp;|&nbsp; <strong>✉️ Email:</strong> info@efcexport.com &nbsp;|&nbsp; <strong>🌐</strong> www.efcexport.com
    </div>
  </div>
</div>

<div class="no-print" style="text-align: center; margin: 16px 0 30px;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #005B9F; color: #fff; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; font-family: Cairo, sans-serif;">{{ $isAr ? 'طباعة' : 'Print' }}</button>
</div>

<script>
    // احتواء تلقائي: يصغّر المحتوى ليدخل في صفحة A4 واحدة لو زاد عن ارتفاعها
    function fitToPage() {
        var page = document.querySelector('.a4-page');
        var inner = document.querySelector('.a4-inner');
        if (!page || !inner) return;
        var cs = getComputedStyle(page);
        var avail = page.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
        inner.style.transform = 'none';
        var actual = inner.scrollHeight;
        var scale = actual > avail ? (avail / actual) : 1;
        inner.style.transform = 'scale(' + scale + ')';
    }

    function fitThenPrint() { fitToPage(); setTimeout(function () { window.print(); }, 300); }

    window.addEventListener('load', function () {
        fitToPage();
        setTimeout(fitThenPrint, 600);
    });
    window.addEventListener('resize', fitToPage);
</script>

</body>
</html>
