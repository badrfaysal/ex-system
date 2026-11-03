<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة ملصق صغير - {{ $data['item_name'] }}</title>
    @php
        $size = $data['size'] ?? '60x30';
        $dimensions = [
            '50x25' => ['w' => '50mm', 'h' => '25mm', 'title_size' => '13px', 'item_size' => '15px', 'date_size' => '10px', 'logo_h' => '8mm'],
            '60x30' => ['w' => '60mm', 'h' => '30mm', 'title_size' => '14px', 'item_size' => '17px', 'date_size' => '11px', 'logo_h' => '10mm'],
            '70x35' => ['w' => '70mm', 'h' => '35mm', 'title_size' => '15px', 'item_size' => '19px', 'date_size' => '12px', 'logo_h' => '12mm'],
            '80x40' => ['w' => '80mm', 'h' => '40mm', 'title_size' => '16px', 'item_size' => '21px', 'date_size' => '13px', 'logo_h' => '14mm'],
            'auto'  => ['w' => '100vw', 'h' => '98vh', 'title_size' => '15px', 'item_size' => '18px', 'date_size' => '12px', 'logo_h' => '11mm'],
        ];
        $dim = $dimensions[$size] ?? $dimensions['60x30'];
    @endphp
    <style>
        @page {
            @if($size !== 'auto')
                size: {{ $dim['w'] }} {{ $dim['h'] }};
            @else
                size: auto;
            @endif
            margin: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #000000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif, Arial;
        }

        .compact-label {
            width: {{ $dim['w'] }};
            height: {{ $dim['h'] }};
            max-width: {{ $dim['w'] }};
            max-height: {{ $dim['h'] }};
            padding: 1.5mm 2.5mm 1.5mm 2.5mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-after: always;
            page-break-inside: avoid;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            background: #fff;
        }

        @media print {
            .compact-label {
                border: none;
            }
        }

        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2mm;
            border-bottom: 1.5px solid #000000;
            padding-bottom: 1mm;
        }

        .company-name {
            font-size: {{ $dim['title_size'] }};
            font-weight: 900;
            line-height: 1.1;
            color: #000000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .logo-img {
            height: {{ $dim['logo_h'] }};
            width: auto;
            max-width: 26mm;
            object-fit: contain;
            display: block;
        }

        .item-row {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1mm 0;
        }

        .item-name {
            font-size: {{ $dim['item_size'] }};
            font-weight: 900;
            line-height: 1.15;
            color: #000000;
            word-break: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1.5px solid #000000;
            padding-top: 1mm;
            font-size: {{ $dim['date_size'] }};
            font-weight: 800;
            color: #000000;
        }

        .date-badge {
            display: inline-flex;
            align-items: baseline;
            gap: 1mm;
        }

        .date-label {
            font-weight: 800;
        }

        .date-val {
            font-family: 'Courier New', Courier, monospace, sans-serif;
            font-weight: 900;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body onload="window.print()">

    @for($i = 0; $i < $data['copies']; $i++)
        <div class="compact-label">
            {{-- رأس الملصق: اسم الشركة + اللوجو --}}
            <div class="header-row">
                <span class="company-name">{{ $data['company_name'] }}</span>
                @if(!empty($data['show_logo']))
                    <img src="{{ asset('images/EFC-.png') }}" alt="Logo" class="logo-img" onerror="this.style.display='none'">
                @endif
            </div>

            {{-- منتصف الملصق: اسم الصنف بخط واضح وعريض --}}
            <div class="item-row">
                <div class="item-name">{{ $data['item_name'] }}</div>
            </div>

            {{-- أسفل الملصق: التاريخ --}}
            <div class="footer-row">
                <div class="date-badge">
                    <span class="date-label">التاريخ:</span>
                    <span class="date-val" dir="ltr">{{ $data['date'] }}</span>
                </div>
            </div>
        </div>
    @endfor

</body>
</html>
