<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'حدث خطأ' }} — {{ config('app.name', 'EFC') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #f8fafc; font-family: 'Segoe UI', Tahoma, Arial, sans-serif; color: #1e293b; padding: 20px;
        }
        .card {
            background: #fff; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,.08);
            max-width: 460px; width: 100%; padding: 48px 36px; text-align: center;
        }
        .icon-wrap {
            width: 88px; height: 88px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px; font-size: 34px;
            background: {{ $bg ?? '#FEF2F2' }}; color: {{ $color ?? '#DC2626' }};
        }
        h1 { font-size: 21px; font-weight: 800; margin: 0 0 10px; color: #0f172a; }
        p { font-size: 14px; color: #64748b; line-height: 1.85; margin: 0 0 28px; }
        .code { display: inline-block; font-size: 11px; color: #94a3b8; font-family: monospace; letter-spacing: .05em;
            margin-bottom: 22px; background: #f1f5f9; padding: 3px 10px; border-radius: 999px; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 12px 26px; border-radius: 12px;
            text-decoration: none; font-weight: 700; font-size: 14px; border: none; cursor: pointer;
            transition: background .2s, opacity .2s; font-family: inherit;
        }
        .btn-primary { background: #005B9F; color: #fff; }
        .btn-primary:hover { background: #004680; }
        .btn-secondary { background: #f1f5f9; color: #475569; }
        .btn-secondary:hover { background: #e2e8f0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap"><i class="fas {{ $icon ?? 'fa-triangle-exclamation' }}"></i></div>
        <h1>{{ $title ?? 'حدث خطأ' }}</h1>
        <p>{{ $message ?? 'حصل خطأ غير متوقع. حاول تاني بعد شوية.' }}</p>
        @isset($statusCode)<div class="code">ERROR {{ $statusCode }}</div>@endisset
        <div class="actions">
            @if(!empty($showBack))
                <button type="button" onclick="history.length > 1 ? history.back() : location.href = '/'" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i> رجوع
                </button>
            @endif
            <a href="{{ url('/') }}" class="btn btn-primary"><i class="fas fa-home"></i> الرئيسية</a>
        </div>
    </div>
</body>
</html>
