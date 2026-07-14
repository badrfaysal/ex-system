<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<title>{{ app()->getLocale() === 'ar' ? 'كشف حساب' : 'Account Statement' }} - {{ $wallet->name }}</title>
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
        @page { margin: 0; }
    }

    .top-global-bar { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
    .top-global-bar td { height: 5px; padding: 0; }

    table.header-layout { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    table.header-layout td { vertical-align: middle; }
    
    .title-main { font-size: 32px; font-weight: 800; color: #008A3B; margin: 0; line-height: 1.1; }
    .quote-number-text { font-size: 16px; font-weight: 700; color: #475569; margin-top: 4px; font-family: monospace; letter-spacing: 1px;}
    .status-badge { display: inline-block; padding: 4px 15px; background: #e0f2fe; color: #0284c7; font-weight: bold; border-radius: 20px; font-size: 13px; margin-top: 8px; border: 1px solid #bae6fd;}

    table.info-layout { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
    table.info-layout > tbody > tr > td { border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 15px 5px; vertical-align: top; }
    
    .block-label { font-size: 11px; color: #94a3b8; font-weight: 700; margin-bottom: 8px; text-transform: uppercase;}
    
    table.meta-data-table { width: 100%; border-collapse: collapse; }
    table.meta-data-table td { padding: 4px 0; font-size: 13px; }
    table.meta-data-table .meta-label { color: #94a3b8; text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; width: 40%; font-weight: 600;}
    table.meta-data-table .meta-value { font-weight: 700; color: #334155; text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}; }
    table.meta-data-table .meta-value-currency { font-weight: 800; color: #005B9F; font-size: 14px; text-align: {{ app()->getLocale() === 'ar' ? 'left' : 'right' }}; }

    table.items-data-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 13px; table-layout: fixed; }
    table.items-data-table thead td { background-color: #1e293b; color: #ffffff; padding: 12px 8px; font-size: 12px; font-weight: 700; }
    table.items-data-table tbody td { padding: 12px 8px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; font-weight: 600;}
    table.items-data-table tbody tr:nth-child(even) td { background-color: #f8fafc; }
    
    .col-center { text-align: center !important; }
    .col-directional { text-align: {{ app()->getLocale() === 'ar' ? 'right' : 'left' }} !important; }

    table.items-data-table tfoot td { background-color: #f1f5f9; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; padding: 12px 8px; font-weight: 700; color: #475569; }

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

    .barcode-section { text-align: center; margin-bottom: 20px; }
    .barcode-section img { height: 60px; max-width: 100%; }
    .barcode-text { font-family: monospace; font-size: 14px; color: #475569; margin-top: 5px; font-weight: 700; letter-spacing: 2px;}

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
    $cur = $wallet->currency;
    $qrData = urlencode("Wallet: {$wallet->name}\nBalance: {$balance} {$cur}\nDate: " . date('Y-m-d'));
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={$qrData}";
    $walletRef = 'W-' . str_pad($wallet->id, 4, '0', STR_PAD_LEFT);
    $barcodeUrl = "https://barcode.tec-it.com/barcode.ashx?data=" . urlencode($walletRef) . "&code=Code128&translate-esc=true&dpi=96";

    $typeLabels = [
        'receipt'       => ['ar' => 'سند قبض', 'en' => 'Receipt'],
        'revenue'       => ['ar' => 'إيراد', 'en' => 'Revenue'],
        'expense'       => ['ar' => 'مصروف', 'en' => 'Expense'],
        'vendor_payment'=> ['ar' => 'سند دفع مورد', 'en' => 'Vendor Payment'],
        'transfer_out'  => ['ar' => 'تحويل صادر', 'en' => 'Transfer Out'],
        'transfer_in'   => ['ar' => 'تحويل وارد', 'en' => 'Transfer In'],
    ];
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
                            <div class="title-main">{{ $isAr ? 'كشف حساب' : 'Account Statement' }}</div>
                            <div class="quote-number-text">{{ $wallet->name }}</div>
                            <div class="status-badge" style="margin-top: 4px;">{{ $isAr ? 'حساب جاري' : 'Current Account' }}</div>
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
            <td style="width: 45%;">
                <div class="block-label">{{ $isAr ? 'تفاصيل الحساب' : 'ACCOUNT DETAILS' }}</div>
                <table class="meta-data-table">
                    <tr>
                        <td class="meta-label">{{ $isAr ? 'تاريخ الطباعة:' : 'Date:' }}</td>
                        <td class="meta-value" dir="ltr">{{ now()->format('d/m/Y - h:i A') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">{{ $isAr ? 'نوع الحساب:' : 'Account Type:' }}</td>
                        <td class="meta-value">{{ $wallet->type === 'bank' ? ($isAr ? 'حساب بنكي' : 'Bank Account') : ($isAr ? 'خزينة نقدية' : 'Cash Drawer') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">{{ $isAr ? 'العملة:' : 'Currency:' }}</td>
                        <td class="meta-value-currency">{{ $wallet->currency }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 55%;">
            </td>
        </tr>
    </table>

    <table class="items-data-table">
        <thead>
            <tr>
                <td style="width: 5%;" class="col-center">#</td>
                <td style="width: 15%;" class="col-center">{{ $isAr ? 'التاريخ' : 'Date' }}</td>
                <td style="width: 15%;" class="col-center">{{ $isAr ? 'المرجع' : 'Reference' }}</td>
                <td style="width: 15%;" class="col-center">{{ $isAr ? 'النوع' : 'Type' }}</td>
                <td style="width: 20%;" class="col-directional">{{ $isAr ? 'التفاصيل' : 'Detail' }}</td>
                <td style="width: 15%;" class="col-center">{{ $isAr ? 'المبلغ' : 'Amount' }}</td>
                <td style="width: 15%;" class="col-center">{{ $isAr ? 'الرصيد' : 'Balance' }}</td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="col-center" style="color: #94a3b8;">—</td>
                <td class="col-center">—</td>
                <td class="col-center" style="font-family: monospace;">—</td>
                <td class="col-center">{{ $isAr ? 'رصيد افتتاحي' : 'Opening Balance' }}</td>
                <td class="col-directional">—</td>
                <td class="col-center" dir="ltr">
                    <span style="color: {{ $wallet->opening_balance >= 0 ? '#008A3B' : '#dc2626' }};">
                        {{ number_format($wallet->opening_balance, 2) }}
                    </span>
                </td>
                <td class="col-center" style="font-weight: 800; color: #005B9F;" dir="ltr">{{ number_format($wallet->opening_balance, 2) }}</td>
            </tr>

            @forelse($timeline as $idx => $entry)
            @php $t = $typeLabels[$entry['type']]; @endphp
            <tr style="{{ !empty($entry['is_reversed']) ? 'text-decoration: line-through; opacity: 0.5;' : '' }} {{ !empty($entry['is_reversal']) ? 'background-color: #fef3c7;' : '' }}">
                <td class="col-center" style="color: #94a3b8;">{{ $idx + 1 }}</td>
                <td class="col-center">{{ optional($entry['date'])->format('Y-m-d') }}</td>
                <td class="col-center" style="font-family: monospace;">{{ $entry['ref'] }}</td>
                <td class="col-center">{{ $isAr ? $t['ar'] : $t['en'] }}</td>
                <td class="col-directional">{{ $entry['detail'] ?? '—' }}</td>
                <td class="col-center" dir="ltr">
                    <span style="color: {{ $entry['amount'] >= 0 ? '#008A3B' : '#dc2626' }}; font-weight: 800;">
                        {{ $entry['amount'] > 0 ? '+' : '' }}{{ number_format($entry['amount'], 2) }}
                    </span>
                </td>
                <td class="col-center" style="font-weight: 800; color: #005B9F;" dir="ltr">{{ number_format($entry['balance'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="col-center" style="color: #94a3b8;">{{ $isAr ? 'لا توجد حركات بعد' : 'No transactions yet' }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="bottom-layout">
        <tr>
            <td style="width: 55%; padding-{{ $isAr ? 'left' : 'right' }}: 30px;">
                <div style="font-size: 11px; color: #64748b; line-height: 1.6;">
                    {{ $isAr ? 'ملاحظة: هذا السجل يشمل كافة الحركات المالية المسجلة على الحساب حتى تاريخ الطباعة.' : 'Note: This statement includes all financial transactions recorded on the account up to the date of printing.' }}
                </div>
            </td>
            
            <td style="width: 45%;">
                <div class="totals-box-wrapper">
                    <table class="totals-summary-table">
                        <tr class="grand-total-row">
                            <td class="summary-label">{{ $isAr ? 'الرصيد النهائي الجاري' : 'Running Balance' }}</td>
                            <td class="summary-value" dir="ltr">
                                {{ number_format($balance, 2) }}
                                <span style="font-size: 11px; font-weight: 600; opacity: 0.9;">{{ $wallet->currency }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="barcode-section">
        <img src="{{ $barcodeUrl }}" alt="Barcode">
        <div class="barcode-text">{{ $wallet->name }}</div>
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
