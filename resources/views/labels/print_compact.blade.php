<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة ملصق صغير - {{ $data['item_name'] }}</title>
    <style>
        @page {
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
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
            background: #f1f5f9;
            color: #000000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Tahoma, Arial, sans-serif;
        }

        /* ===== شريط التحكم العلوي (لا يظهر في الطباعة) ===== */
        .no-print {
            width: 100%;
            background: #ffffff;
            padding: 12px 16px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .btn-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-action {
            padding: 7px 13px;
            border-radius: 8px;
            font-weight: 800;
            cursor: pointer;
            font-size: 12.5px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #1e293b;
            transition: all 0.15s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-action:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .btn-primary {
            background: #db2777;
            color: #ffffff;
            border-color: #be185d;
            padding: 7px 18px;
            box-shadow: 0 2px 6px rgba(219, 39, 119, 0.25);
        }

        .btn-primary:hover {
            background: #be185d;
            border-color: #9d174d;
        }

        .divider {
            width: 1px;
            height: 24px;
            background: #e2e8f0;
            margin: 0 4px;
        }

        .tip-box {
            font-size: 12px;
            color: #334155;
            background: #fffbeb;
            border: 1px solid #fde68a;
            padding: 7px 14px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            max-width: 780px;
            line-height: 1.5;
        }

        /* ===== منطقة عرض المعاينة على الشاشة ===== */
        .preview-stage {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            padding: 28px 16px 60px;
            transform: scale(1.65);
            transform-origin: top center;
        }

        /* ===== حاوية الملصق الواحد (بالعرض 50 × 25 مم ومنزلة لأسفل عشان اللوجو والاسم يظهروا كاملين) ===== */
        .compact-label {
            width: 50mm;
            height: 25mm;
            max-width: 50mm;
            max-height: 25mm;
            background: #ffffff;
            border: 1px dashed #94a3b8;
            border-radius: 3px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            padding-bottom: 0.8mm;
            padding-top: 0;
            overflow: hidden;
            page-break-inside: avoid;
            break-inside: avoid;
            box-sizing: border-box;
        }

        /* الصندوق الداخلي منسق لأسفل الملصق مع أبعاد مريحة تمنع قص السطر العلوي */
        .label-box {
            width: 90%;
            max-width: 45mm;
            height: 21.2mm;
            max-height: 21.5mm;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: stretch;
            overflow: hidden;
        }

        /* 1. السطر العلوي: اسم الشركة + اللوجو مكبر وواضح */
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5mm;
            border-bottom: 1.5px solid #000000;
            padding-bottom: 0.5mm;
            height: 6.8mm;
            min-height: 6.8mm;
            max-height: 6.8mm;
            overflow: hidden;
        }

        .header-row.no-logo {
            justify-content: center;
            text-align: center;
        }

        .company-name {
            font-size: 13px;
            font-weight: 900;
            line-height: 1.1;
            color: #000000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .logo-img {
            height: 6.3mm;
            max-height: 6.3mm;
            width: auto;
            max-width: 22mm;
            object-fit: contain;
            display: block;
            flex-shrink: 0;
            filter: grayscale(100%) contrast(160%);
        }

        /* 2. المنتصف: اسم الصنف في النص بالضبط أفقياً ورأسياً */
        .item-row {
            flex: 1;
            min-height: 0;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0.4mm 0.5mm;
            overflow: hidden;
        }

        .item-name {
            font-size: 16.5px;
            font-weight: 900;
            line-height: 1.15;
            color: #000000;
            text-align: center;
            width: 100%;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        /* 3. السطر السفلي: التاريخ متوسطن في النص بالضبط */
        .footer-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5mm;
            border-top: 1.5px solid #000000;
            padding-top: 0.5mm;
            height: 4.4mm;
            min-height: 4.4mm;
            max-height: 4.4mm;
            color: #000000;
            overflow: hidden;
        }

        .date-label {
            font-weight: 900;
            font-size: 10.5px;
            line-height: 1;
        }

        .date-val {
            font-family: 'Courier New', Courier, monospace, sans-serif;
            font-weight: 900;
            font-size: 11.5px;
            line-height: 1;
            letter-spacing: 0.3px;
        }

        /* ===== إعدادات الطباعة الفعلية على طابعة الستيكر الحرارية ===== */
        @media print {
            .no-print {
                display: none !important;
            }

            html, body {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
                min-height: 0 !important;
                background: #ffffff !important;
                overflow: visible !important;
            }

            .preview-stage {
                transform: none !important;
                margin: 0 !important;
                padding: 0 !important;
                gap: 0 !important;
                width: 100% !important;
                display: block !important;
            }

            .compact-label {
                width: 100vw !important;
                height: 98vh !important;
                max-width: 100vw !important;
                max-height: 98vh !important;
                margin: 0 auto !important;
                padding: 0 0 0.8mm 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                background: #ffffff !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-end !important;
                align-items: center !important;
                overflow: hidden !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .compact-label:not(:last-child) {
                page-break-after: always !important;
                break-after: page !important;
            }

            .compact-label:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
        }
    </style>
</head>
<body onload="initPrintPage()">

    <div class="no-print">
        <div class="btn-group">
            <button type="button" onclick="window.print()" class="btn-action btn-primary">
                🖨️ طباعة الآن (Print)
            </button>

            <div class="divider"></div>

            <button type="button" onclick="changeFontSize(1.5)" class="btn-action" title="تكبير حجم خط الصنف">
                ➕ تكبير الخط
            </button>
            <button type="button" onclick="changeFontSize(-1.5)" class="btn-action" title="تصغير حجم خط الصنف">
                ➖ تصغير الخط
            </button>
        </div>
        <div class="tip-box">
            <span>💡 تم ضبط موضع المحتوى لأسفل الستيكر لضمان ظهور اسم الشركة واللوجو كاملاً دون أي قص في رأس الملصق.</span>
        </div>
    </div>

    <div class="preview-stage">
        @for($i = 0; $i < $data['copies']; $i++)
            <div class="compact-label">
                <div class="label-box">
                    {{-- رأس الملصق: اسم الشركة + اللوجو مكبر --}}
                    <div class="header-row {{ empty($data['show_logo']) ? 'no-logo' : '' }}">
                        <span class="company-name">{{ $data['company_name'] }}</span>
                        @if(!empty($data['show_logo']))
                            <img src="{{ asset('images/EFC-.png') }}" alt="Logo" class="logo-img" onerror="this.style.display='none'; this.parentElement.classList.add('no-logo');">
                        @endif
                    </div>

                    {{-- منتصف الملصق: اسم الصنف في النص بالضبط --}}
                    <div class="item-row">
                        <div class="item-name">{{ $data['item_name'] }}</div>
                    </div>

                    {{-- أسفل الملصق: التاريخ في النص بالضبط --}}
                    <div class="footer-row">
                        <span class="date-label">التاريخ:</span>
                        <span class="date-val" dir="ltr">{{ $data['date'] }}</span>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <script>
    let manualFontSize = null;

    function autoFitAllLabels() {
        document.querySelectorAll('.compact-label').forEach(label => {
            const row = label.querySelector('.item-row');
            const item = label.querySelector('.item-name');
            if (!row || !item) return;

            if (manualFontSize !== null) {
                item.style.fontSize = manualFontSize + 'px';
                return;
            }

            let size = 16.5;
            item.style.fontSize = size + 'px';

            while ((item.scrollHeight > row.clientHeight || item.scrollWidth > row.clientWidth) && size > 10) {
                size -= 0.5;
                item.style.fontSize = size + 'px';
            }
        });
    }

    function changeFontSize(delta) {
        if (manualFontSize === null) {
            const firstItem = document.querySelector('.item-name');
            manualFontSize = firstItem ? parseFloat(window.getComputedStyle(firstItem).fontSize) : 16.5;
        }
        manualFontSize = Math.min(26, Math.max(9, manualFontSize + delta));
        autoFitAllLabels();
    }

    window.addEventListener('beforeprint', function () {
        autoFitAllLabels();
    });

    function initPrintPage() {
        autoFitAllLabels();
        setTimeout(() => {
            window.print();
        }, 300);
    }
    </script>

</body>
</html>
