<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة ملصق</title>
    <style>
        @page {
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
            font-size: 20px; 
            line-height: 1.1; /* Reduced to fit */
            padding: 0.5mm 2mm 1.5mm 2mm; /* Less padding on top */
            page-break-after: always;
            font-weight: 900;
        }
        
        .header {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1mm; /* Reduced */
        }
        
        .logo {
            height: 21mm; /* Increased slightly */
            width: auto;
        }

        .content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1.2mm; /* Added breathing room between lines */
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
            font-size: 16px; /* Reduced to ensure it stays on one line */
            white-space: nowrap;
            overflow: hidden;
            border-top: 2px solid #000;
            padding-top: 1mm;
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
                <div style="font-size: 20px; margin-bottom: 1mm;">
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

            <div style="margin-top: 2mm; font-size: 20px; display: flex; flex-direction: column; gap: 1.2mm;">
                <div style="display: flex; justify-content: space-between;">
                    <div><span style="text-decoration: underline;">انتاج:</span> {{ $data['production_date'] }}</div>
                    <div><span style="text-decoration: underline;">انتهاء:</span> {{ $data['expiry_date'] }}</div>
                </div>
                <div><span>تشغيلة:</span> {{ $data['batch_code'] }}</div>
            </div>

            <div class="footer">
                {{ $data['website'] }}
            </div>
        </div>
    @endfor

</body>
</html>
