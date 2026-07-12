<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $isAr ? 'فاتورة بيع' : 'Sales Invoice' }} {{ $salesInvoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; border: 1px solid #eee;">
        <h2 style="color: #008A3B;">{{ $isAr ? 'فاتورة بيع جديدة' : 'New Sales Invoice' }}</h2>
        <p>{{ $isAr ? 'مرحباً،' : 'Hello,' }}</p>
        <p>{{ $isAr ? 'مرفق طيه فاتورة البيع رقم:' : 'Attached is the sales invoice number:' }} <strong>{{ $salesInvoice->invoice_number }}</strong></p>
        <br>
        <p><strong>{{ $isAr ? 'الإجمالي النهائي:' : 'Grand Total:' }}</strong> {{ number_format($salesInvoice->grand_total, 2) }} {{ $salesInvoice->currency }}</p>
        <br>
        <p>{{ $isAr ? 'شكراً لتعاملكم معنا.' : 'Thank you for doing business with us.' }}</p>
    </div>
</body>
</html>
