<!DOCTYPE html>
<html lang="{{ $isAr ? 'ar' : 'en' }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { font-family: Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; direction: {{ $isAr ? 'rtl' : 'ltr' }}; }
  .card { background: #ffffff; border-radius: 12px; max-width: 600px; width: 100%; margin: 0 auto; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); box-sizing: border-box; }
  .top-bar { height: 5px; background: linear-gradient(90deg, #dc2626, #991b1b); }
  .header  { background: #1e293b; padding: 22px 30px; color: #fff; }
  .header h1 { font-size: 18px; margin: 0 0 4px; }
  .header p  { font-size: 12px; color: #94a3b8; margin: 0; }
  .body    { padding: 28px 30px; }
  .body p  { font-size: 14px; line-height: 1.7; margin: 0 0 14px; color: #334155; }
  .highlight { background: #fef2f2; border-{{ $isAr ? 'right' : 'left' }}: 4px solid #dc2626; border-radius: 4px; padding: 14px 18px; margin: 20px 0; }
  .highlight p { margin: 4px 0; font-size: 13px; color: #1e293b; }
  .grand { display: inline-block; background: #dc2626; color: #fff; border-radius: 6px; padding: 6px 16px; font-size: 15px; font-weight: bold; margin-top: 4px; }
  @media only screen and (max-width: 480px) {
    body { padding: 10px; }
    .header, .body { padding: 18px 16px; }
  }
</style>
</head>
<body>
<div class="card">
  <div class="top-bar"></div>
  <div class="header">
    <h1>{{ config('mail.from.name') }}</h1>
    <p>{{ $isAr ? 'كشف حساب' : 'Account Statement' }}</p>
  </div>
  <div class="body">
    @if($isAr)
      <p>السادة / {{ $vendorName }}</p>
      <p>تحية طيبة وبعد،</p>
      <p>مرفق بهذا البريد كشف حساب مفصّل يوضح حركة حسابكم معنا.</p>
    @else
      <p>Dear {{ $vendorName }},</p>
      <p>Greetings,</p>
      <p>Please find attached your detailed account statement.</p>
    @endif

    <div class="highlight">
      <p>{{ $isAr ? 'الرصيد المستحق لكم' : 'Balance Due to You' }}: <span class="grand">{{ number_format($balance, 2) }}</span></p>
    </div>

    @if($isAr)
      <p>مرفق بهذا البريد ملف PDF يحتوي على تفاصيل الحركات كاملة.</p>
      <p>مع خالص التقدير،<br><strong>{{ config('mail.from.name') }}</strong></p>
    @else
      <p>Please find the detailed statement attached as a PDF file.</p>
      <p>Best regards,<br><strong>{{ config('mail.from.name') }}</strong></p>
    @endif
  </div>
</div>
</body>
</html>
