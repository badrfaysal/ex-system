<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة ملصق (100 × 100 مم) - {{ $data['brand_name'] }} ({{ $data['item_name'] }})</title>

    <style>
        @page {
            size: 100mm 100mm !important;
            margin: 0 !important;
        }

        * {
            box-sizing: border-box !important;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background: #ffffff;
            color: #000000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Tahoma, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== شريط تحكم عائم على الشاشة فقط ===== */
        .no-print-bar {
            position: fixed;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 99999;
            background: #0f172a;
            color: #ffffff;
            padding: 8px 18px;
            border-radius: 40px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: system-ui, sans-serif;
            font-size: 12.5px;
            font-weight: bold;
            flex-wrap: wrap;
            justify-content: center;
        }

        .badge-fixed-size {
            background: #be185d;
            color: #ffffff;
            padding: 4px 14px;
            border-radius: 20px;
            font-weight: 900;
            font-size: 12.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            letter-spacing: 0.3px;
        }

        .toolbar-divider {
            width: 1px;
            height: 20px;
            background: #334155;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-tool {
            background: #334155;
            color: #ffffff;
            border: 1px solid #475569;
            padding: 5px 12px;
            border-radius: 14px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-tool:hover {
            background: #475569;
            border-color: #64748b;
        }

        .btn-print-action {
            background: #db2777;
            color: #ffffff;
            border: none;
            padding: 6px 20px;
            border-radius: 20px;
            font-weight: 900;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(219, 39, 119, 0.4);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-print-action:hover {
            background: #be185d;
            transform: scale(1.02);
        }

        /* ===== الحاوية الرئيسية للملصق (مقاس ثابت 100 × 100 مم) ===== */
        .label-page {
            width: 100mm !important;
            height: 100mm !important;
            max-width: 100mm !important;
            max-height: 100mm !important;
            padding: 1.5mm 3.5mm 1.5mm 3.5mm !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            background: #ffffff !important;
            color: #000000 !important;
            overflow: hidden !important;
            position: relative;
            font-size: 12.5px;
        }

        /* رأس الملصق: اللوجو محاذي لليسار كما في عينة الطابعة */
        .header {
            display: flex;
            justify-content: flex-end; /* في اتجاه RTL يكون في أقصى اليسار */
            align-items: center;
            height: 11mm;
            max-height: 11mm;
            flex-shrink: 0;
            width: 100%;
            margin-bottom: 0.5mm;
        }

        .logo {
            height: 11mm;
            max-height: 11mm;
            width: auto;
            max-width: 45mm;
            object-fit: contain;
            display: block;
        }

        /* اسم البراند والصنف */
        .brand-title {
            font-size: 15px;
            font-weight: 900;
            border-bottom: 1.5px solid #000000;
            padding-bottom: 0.5mm;
            margin-bottom: 0.8mm;
            line-height: 1.15;
            flex-shrink: 0;
            width: 100%;
            word-break: break-word;
        }

        /* أسطر بيانات الملصق متوزعة داخل مساحة الـ 100 مم */
        .content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-evenly;
            overflow: hidden;
            width: 100%;
            margin: 0.3mm 0;
        }

        .line {
            display: flex;
            align-items: baseline;
            width: 100%;
            font-size: 12.5px;
            line-height: 1.2;
            padding: 0.2mm 0;
            font-weight: 800;
        }

        .label {
            white-space: nowrap;
            text-decoration: underline;
            text-underline-offset: 2px;
            flex-shrink: 0;
            font-weight: 900;
            margin-left: 2mm;
        }

        .value {
            font-weight: 800;
            flex-grow: 1;
            word-break: break-word;
        }

        /* صندوق التواريخ والتشغيلة */
        .dates-box {
            font-size: 12.5px;
            border-top: 1.5px dashed #000000;
            padding-top: 0.8mm;
            margin-top: 0.8mm;
            gap: 0.6mm;
            flex-shrink: 0;
            width: 100%;
            font-weight: 900;
            line-height: 1.2;
        }

        .dates-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .batch-row {
            display: flex;
            align-items: baseline;
        }

        /* الفوتر (الموقع الإلكتروني) */
        .footer {
            text-align: center;
            font-size: 10px;
            border-top: 1.5px solid #000000;
            padding-top: 0.8mm;
            margin-top: 0.8mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
            font-weight: 900;
            direction: ltr;
            flex-shrink: 0;
            line-height: 1.1;
        }

        /* ==================== العرض على الشاشة (معاينة واقعية 100×100 مم) ==================== */
        @media screen {
            body {
                background: #f1f5f9;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 75px 20px 40px;
                min-height: 100vh;
                overflow-y: auto;
            }

            .preview-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 25px;
                width: 100%;
            }

            .label-page {
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.08);
                border: 1px dashed #94a3b8;
                border-radius: 4px;
                background: #ffffff;
                margin: 0 auto;
            }
        }

        /* ==================== الطباعة الفعلية (100×100 مم في ستيكر واحد بالضبط) ==================== */
        @media print {
            .no-print-bar {
                display: none !important;
            }

            html, body {
                width: 100% !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
                overflow: hidden !important;
            }

            .preview-container {
                padding: 0 !important;
                margin: 0 !important;
                background: #ffffff !important;
                display: block !important;
                width: 100% !important;
            }

            .label-page {
                width: 100mm !important;
                height: 100mm !important;
                max-width: 100mm !important;
                max-height: 100mm !important;
                margin: 0 auto !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                background: #ffffff !important;
                overflow: hidden !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .label-page:not(:last-child) {
                page-break-after: always !important;
                break-after: page !important;
            }

            .label-page:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
        }
    </style>
</head>
<body>

    {{-- شريط تحكم عائم على الشاشة فقط --}}
    <div class="no-print-bar">
        <div class="badge-fixed-size">
            🏷️ مقاس الستيكر: 100 × 100 مم
        </div>

        <div class="toolbar-divider"></div>

        {{-- أزرار تحكم حجم الخط --}}
        <div class="toolbar-group">
            <button type="button" class="btn-tool" onclick="adjustGlobalFontSize(0.5)" title="تكبير حجم الخط">
                ➕ تكبير الخط
            </button>
            <button type="button" class="btn-tool" onclick="adjustGlobalFontSize(-0.5)" title="تصغير حجم الخط">
                ➖ تصغير الخط
            </button>
            <button type="button" class="btn-tool" onclick="autoFitAllPages(true)" title="إعادة ضبط لملء الستيكر تلقائياً">
                ↕️ ملء الستيكر
            </button>
        </div>

        <div class="toolbar-divider"></div>

        {{-- زر الطباعة --}}
        <button type="button" class="btn-print-action" onclick="window.print()">
            🖨️ طباعة الآن (Print)
        </button>
    </div>

    <div class="preview-container">
        @for($i = 0; $i < $data['copies']; $i++)
            <div class="label-page">
                {{-- رأس الملصق: اللوجو محاذي لليسار كما في عينة الطابعة --}}
                <div class="header">
                    @if(!empty($data['show_logo']))
                        <img src="{{ asset('images/EFC-.png') }}" alt="Logo" class="logo" onerror="this.style.display='none'">
                    @endif
                </div>

                {{-- عنوان البراند والصنف بخط عريض وواضح --}}
                <div class="brand-title">
                    {{ $data['brand_name'] }} - {{ $data['item_name'] }}
                </div>

                {{-- المحتوى: بيانات المصنع والمصدر والمستورد متناسقة بدون أي انقسام --}}
                <div class="content">
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

                {{-- التواريخ والتشغيلة --}}
                <div class="dates-box">
                    <div class="dates-row">
                        <div><span class="label">انتاج:</span> <span class="value" dir="ltr">{{ $data['production_date'] }}</span></div>
                        <div><span class="label">انتهاء:</span> <span class="value" dir="ltr">{{ $data['expiry_date'] }}</span></div>
                    </div>
                    <div class="batch-row">
                        <span class="label">تشغيلة:</span> <span class="value" dir="ltr">{{ $data['batch_code'] }}</span>
                    </div>
                </div>

                {{-- الفوتر: الموقع الإلكتروني --}}
                <div class="footer">
                    {{ $data['website'] }}
                </div>
            </div>
        @endfor
    </div>

    <script>
    let manualDelta = 0;

    function adjustGlobalFontSize(delta) {
        manualDelta += delta;
        document.querySelectorAll('.label-page').forEach(page => {
            const elements = page.querySelectorAll('.brand-title, .line, .dates-box, .footer, .label, .value');
            elements.forEach(el => {
                const computed = parseFloat(window.getComputedStyle(el).fontSize);
                el.style.fontSize = Math.max(8.5, Math.min(25, computed + delta)) + 'px';
            });
        });
    }

    function autoFitAllPages(force = false) {
        document.querySelectorAll('.label-page').forEach(page => {
            if (!force && manualDelta !== 0) return;

            // التأكد 100% أن الارتفاع لا يتجاوز الـ 100 مم لمنع انقسام الستيكر نهائياً
            let guard = 0;
            while (page.scrollHeight > page.clientHeight && guard < 25) {
                page.querySelectorAll('.brand-title, .line, .dates-box, .footer, .label, .value').forEach(el => {
                    const current = parseFloat(window.getComputedStyle(el).fontSize);
                    el.style.fontSize = (current - 0.35) + 'px';
                });
                guard++;
            }
        });
    }

    let isPrinted = false;
    function triggerPrint() {
        if (isPrinted) return;
        isPrinted = true;
        autoFitAllPages(true);
        setTimeout(() => {
            window.print();
        }, 250);
    }

    window.addEventListener('load', triggerPrint);
    window.addEventListener('beforeprint', function () {
        autoFitAllPages(false);
    });
    </script>

</body>
</html>
