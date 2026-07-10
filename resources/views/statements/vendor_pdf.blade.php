<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
    @page { margin: 8mm 10mm; }
    * { box-sizing: border-box; }
    body { font-family: dejavusans, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 0; }

    .top-global-bar { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .top-global-bar td { height: 4px; padding: 0; }

    table.header-layout { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .title-main { font-size: 24px; font-weight: bold; color: #dc2626; margin: 0; line-height: 1.1; }
    .company-name { font-size: 14px; font-weight: bold; color: #0f172a; }

    table.info-layout { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    table.info-layout > tbody > tr > td { border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 12px 4px; vertical-align: top; }
    .block-label { font-size: 9px; color: #94a3b8; font-weight: bold; margin-bottom: 6px; }
    .vendor-name { font-size: 15px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }

    table.summary-table { width: 100%; border-collapse: collapse; }
    table.summary-table td { padding: 3px 0; font-size: 11px; }
    .summary-label { color: #94a3b8; }
    .summary-value { font-weight: bold; color: #334155; text-align: {{ $isAr ? 'left' : 'right' }}; }
    .summary-value-final { font-weight: bold; color: #dc2626; font-size: 13px; text-align: {{ $isAr ? 'left' : 'right' }}; }

    table.tx-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10px; }
    table.tx-table thead td { background-color: #1e293b; color: #ffffff; padding: 8px 6px; font-size: 10px; font-weight: bold; }
    table.tx-table tbody td { padding: 7px 6px; border-bottom: 1px solid #f1f5f9; color: #334155; }
    table.tx-table tbody tr:nth-child(even) td { background-color: #f8fafc; }
    .col-center { text-align: center !important; }
    .type-invoice { color: #dc2626; font-weight: bold; }
    .type-payment { color: #008A3B; font-weight: bold; }
    .amt-positive { color: #dc2626; font-weight: bold; }
    .amt-negative { color: #008A3B; font-weight: bold; }

    .final-box { background: #dc2626; color: #fff; border-radius: 6px; padding: 10px 16px; font-weight: bold; font-size: 13px; text-align: {{ $isAr ? 'left' : 'right' }}; margin-top: 10px; }
</style>
</head>
<body>
    <table class="top-global-bar"><tr><td style="background:#dc2626;"></td></tr></table>

    <table class="header-layout">
        <tr>
            <td style="width:60%;">
                <div class="company-name">{{ config('mail.from.name') }}</div>
            </td>
            <td style="width:40%; text-align:{{ $isAr ? 'left' : 'right' }};">
                <p class="title-main">{{ $isAr ? 'كشف حساب مورد' : 'Vendor Statement' }}</p>
                <p style="font-size:10px;color:#64748b;">{{ now()->format('Y-m-d') }}</p>
            </td>
        </tr>
    </table>

    <table class="info-layout">
        <tr>
            <td style="width:55%;">
                <div class="block-label">{{ $isAr ? 'المورد' : 'VENDOR' }}</div>
                <div class="vendor-name">{{ $vendorName }}</div>
                @if($vendor->mobile || $vendor->email)
                <div style="font-size:10px;color:#64748b;">{{ $vendor->mobile }} {{ $vendor->mobile && $vendor->email ? '|' : '' }} {{ $vendor->email }}</div>
                @endif
            </td>
            <td style="width:45%;">
                <div class="block-label">{{ $isAr ? 'الملخص' : 'SUMMARY' }}</div>
                <table class="summary-table">
                    <tr><td class="summary-label">{{ $isAr ? 'إجمالي الفواتير' : 'Total Invoiced' }}</td><td class="summary-value">{{ number_format($timeline->where('type','invoice')->sum('amount'), 2) }}</td></tr>
                    <tr><td class="summary-label">{{ $isAr ? 'إجمالي المدفوع' : 'Total Paid' }}</td><td class="summary-value">{{ number_format(-1 * $timeline->where('type','payment')->sum('amount'), 2) }}</td></tr>
                    <tr><td class="summary-label">{{ $isAr ? 'الباقي' : 'Remaining' }}</td><td class="summary-value-final">{{ number_format($balance, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="tx-table">
        <thead>
            <tr>
                <td>{{ $isAr ? 'التاريخ' : 'Date' }}</td>
                <td>{{ $isAr ? 'المرجع' : 'Reference' }}</td>
                <td>{{ $isAr ? 'النوع' : 'Type' }}</td>
                <td class="col-center">{{ $isAr ? 'المبلغ' : 'Amount' }}</td>
                <td class="col-center">{{ $isAr ? 'الرصيد' : 'Balance' }}</td>
            </tr>
        </thead>
        <tbody>
            @forelse($timeline as $entry)
            <tr>
                <td>{{ optional($entry['date'])->format('Y-m-d') }}</td>
                <td>{{ $entry['ref'] }}</td>
                <td class="{{ $entry['type'] === 'invoice' ? 'type-invoice' : 'type-payment' }}">
                    {{ $entry['type'] === 'invoice' ? ($isAr ? 'فاتورة شراء' : 'Purchase Invoice') : ($isAr ? 'سند دفع' : 'Payment') }}
                </td>
                <td class="col-center {{ $entry['amount'] >= 0 ? 'amt-positive' : 'amt-negative' }}">{{ $entry['amount'] >= 0 ? '+' : '' }}{{ number_format($entry['amount'], 2) }}</td>
                <td class="col-center">{{ number_format($entry['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:#94a3b8;">{{ $isAr ? 'لا توجد حركات' : 'No transactions' }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="final-box">{{ $isAr ? 'الرصيد المستحق النهائي: ' : 'Final Balance Due: ' }}{{ number_format($balance, 2) }}</div>
</body>
</html>
