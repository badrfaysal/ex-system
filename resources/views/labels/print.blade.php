<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة ملصق</title>
    <style>
        @page {
            size: 50mm 50mm; /* 5x5 cm */
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: 'Arial', sans-serif;
            -webkit-print-color-adjust: exact;
        }

        .label-page {
            width: 100vw;
            height: 98vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            font-size: 13px; /* Slightly lower to allow for more gap */
            line-height: 1.3;
            padding: 1.5mm 2mm;
            page-break-after: always;
            font-weight: 900;
        }
        
        .header {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.5mm;
        }
        
        .logo {
            height: 17mm;
            width: auto;
        }

        .content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5mm; /* Nice breathing space between lines */
        }

        .line {
            display: flex;
            align-items: baseline;
        }

        .label {
            white-space: nowrap;
            text-decoration: underline;
        }

        .value {
            margin-right: 1.5mm;
        }

        .footer {
            text-align: center;
            font-size: 11px;
            border-top: 1px solid #000;
            padding-top: 1.5mm;
            margin-top: auto;
            width: 100%;
        }

        /* Utility classes */
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

    </style>
</head>
<body onload="window.print()">

    @for($i = 0; $i < $data['copies']; $i++)
        <div class="label-page">
            <div class="header" style="justify-content: flex-end;">
                <div>
                    <img src="{{ asset('images/EFC-.png') }}" alt="Logo" class="logo">
                </div>
            </div>

            <div class="content">
                <div style="font-size: 17px; margin-bottom: 2mm;">
                    {{ $data['brand_name'] }} - {{ $data['item_name'] }}
                </div>
                <div class="line">
                    <span class="label">شركة المصنعة:</span>
                    <span class="value">{{ $data['manufacturer'] }}</span>
                </div>
                <div class="line">
                    <span class="label">عنوان:</span>
                    <span class="value">{{ $data['manufacturer_address'] }}</span>
                </div>
                <div class="line">
                    <span class="label">شركة المصدرة:</span>
                    <span class="value">{{ $data['exporter'] }}</span>
                </div>
                <div class="line">
                    <span class="label">عنوان:</span>
                    <span class="value">{{ $data['exporter_address'] }}</span>
                </div>
                <div class="line">
                    <span class="label">مستورد:</span>
                    <span class="value">{{ $data['importer'] }}</span>
                </div>
                <div class="line">
                    <span class="label">عنوان:</span>
                    <span class="value">{{ $data['importer_address'] }}</span>
                </div>
            </div>

            <div style="margin-top: 2mm; font-size: 13px; display: flex; flex-direction: column; gap: 1.5mm;">
                <div><span style="text-decoration: underline;">انتاج:</span> {{ $data['production_date'] }}</div>
                <div><span style="text-decoration: underline;">انتهاء:</span> {{ $data['expiry_date'] }}</div>
                <div><span style="text-decoration: underline;">تشغيلة:</span> {{ $data['batch_code'] }}</div>
            </div>

            <div class="footer">
                {{ $data['website'] }}
            </div>
        </div>
    @endfor

</body>
</html>
