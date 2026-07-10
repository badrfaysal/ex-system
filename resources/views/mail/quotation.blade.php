<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; direction: {{ $isAr ? 'rtl' : 'ltr' }}; }
  .card { background: #ffffff; border-radius: 12px; max-width: 600px; margin: 0 auto; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .top-bar { height: 5px; background: linear-gradient(90deg, #005B9F, #008A3B); }
  .header  { background: #1e293b; padding: 22px 30px; color: #fff; }
  .header h1 { font-size: 18px; margin: 0 0 4px; }
  .header p  { font-size: 12px; color: #94a3b8; margin: 0; }
  .body    { padding: 28px 30px; }
  .body p  { font-size: 14px; line-height: 1.7; margin: 0 0 14px; color: #334155; }
  .highlight { background: #f0f9ff; border-{{ $isAr ? 'right' : 'left' }}: 4px solid #005B9F; border-radius: 4px; padding: 14px 18px; margin: 20px 0; }
  .highlight p { margin: 4px 0; font-size: 13px; color: #1e293b; }
  .highlight .num { font-size: 16px; font-weight: bold; color: #005B9F; }
  .grand { display: inline-block; background: #005B9F; color: #fff; border-radius: 6px; padding: 6px 16px; font-size: 15px; font-weight: bold; margin-top: 4px; }
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 30px; font-size: 11px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>
<div class="card">
  <div class="top-bar"></div>
  <div class="header">
    <h1>{{ config('mail.from.name') }}</h1>
    <p>{{ $isAr ? 'عرض الأسعار' : 'Price Quotation' }}</p>
  </div>
  <div class="body">
    @php $clientName = optional($quotation->client)->displayName($isAr ? 'ar' : 'en'); @endphp
    @if($isAr)
      <p>السادة / {{ $clientName }}</p>
      <p>تحية طيبة وبعد،</p>
      <p>يسعدنا تقديم عرض الأسعار التالي لكم، ونأمل أن يلقى قبولكم.</p>
    @else
      <p>Dear {{ $clientName }},</p>
      <p>Greetings,</p>
      <p>Please find below our price quotation for your reference.</p>
    @endif

    <div class="highlight">
      <p>{{ $isAr ? 'رقم العرض' : 'Quotation No.' }}: <span class="num">{{ $quotation->quote_number }}</span></p>
      <p>{{ $isAr ? 'تاريخ الإصدار' : 'Issue Date' }}: {{ optional($quotation->quote_date)->format('d/m/Y') }}</p>
      @if($quotation->expiry_date)
      <p>{{ $isAr ? 'صالح حتى' : 'Valid Until' }}: {{ optional($quotation->expiry_date)->format('d/m/Y') }}</p>
      @endif
      <p>{{ $isAr ? 'الإجمالي النهائي' : 'Grand Total' }}: <span class="grand">{{ number_format($quotation->grand_total, 2) }} {{ $quotation->currency }}</span></p>
    </div>

    @if($isAr)
      <p>مرفق بهذا البريد ملف PDF يحتوي على تفاصيل العرض الكاملة.</p>
      <p>نرجو الاطلاع عليه وإبلاغنا بموافقتكم أو أي استفسار في أقرب وقت.</p>
      <p>مع خالص التقدير،<br><strong>{{ config('mail.from.name') }}</strong></p>
    @else
      <p>Please find the detailed quotation attached as a PDF file.</p>
      <p>We look forward to your approval or any inquiries at your earliest convenience.</p>
      <p>Best regards,<br><strong>{{ config('mail.from.name') }}</strong></p>
    @endif
  </div>
  <div class="footer">
    {{ config('mail.from.address') }} &mdash; {{ config('mail.from.name') }}
  </div>
</div>
</body>
</html>
