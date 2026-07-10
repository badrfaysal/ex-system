@php $rtl = app()->getLocale() === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.system_title') }}</title>

    {{-- منع وميض الثيم: نطبّق الوضع الليلي قبل رسم الصفحة --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>

    {{-- خطوط احترافية رسمية --}}
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <style>
        /* تطبيق الخطوط الاحترافية الرسمية */
        :root { --font-ar: 'Almarai', sans-serif; --font-en: 'Inter', sans-serif; }
        html[dir="rtl"] body { font-family: var(--font-ar); }
        html[dir="ltr"] body { font-family: var(--font-en); }
        body { font-family: var(--font-ar); letter-spacing: -0.3px; }
        
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
        .animate-fade-in { animation: fadeIn .3s ease-out both; }

        #pageLoader { position: fixed; top: 0; right: 0; left: 0; height: 2px; z-index: 9999;
            background: #005B9F; transform: scaleX(0); transform-origin: right;
            transition: transform .3s ease; opacity: 0; }
        #pageLoader.active { opacity: 1; transform: scaleX(0.85); }

        /* ====================== الوضع الليلي (Dark Mode) ====================== */
        html.dark { color-scheme: dark; }
        html.dark body { background-color: #0f172a; color: #e2e8f0; }
        html.dark .sidebar-scroll::-webkit-scrollbar-thumb { background: #475569; }

        /* الأسطح */
        html.dark .bg-white { background-color: #1e293b; }
        html.dark .bg-gray-50 { background-color: #172033; }
        html.dark .bg-gray-50\/50 { background-color: rgba(23, 32, 51, 0.6); }
        html.dark .bg-gray-100 { background-color: #334155; }
        html.dark .bg-gray-200 { background-color: #475569; }
        html.dark .hover\:bg-gray-50:hover { background-color: #243049; }
        html.dark .hover\:bg-gray-100:hover { background-color: #334155; }
        html.dark .hover\:bg-white:hover { background-color: #1e293b; }
        html.dark .focus\:bg-white:focus { background-color: #0f172a; }

        /* النصوص */
        html.dark .text-gray-900 { color: #f1f5f9; }
        html.dark .text-gray-800 { color: #e2e8f0; }
        html.dark .text-gray-700 { color: #cbd5e1; }
        html.dark .text-gray-600 { color: #aab6c7; }
        html.dark .text-gray-500 { color: #94a3b8; }
        html.dark .text-gray-400 { color: #7587a0; }
        html.dark .text-gray-300 { color: #5b6b85; }

        /* الحدود والفواصل */
        html.dark .border-gray-50,
        html.dark .border-gray-100,
        html.dark .border-gray-200,
        html.dark .border-gray-300 { border-color: #334155; }
        html.dark .divide-gray-100 > :not([hidden]) ~ :not([hidden]),
        html.dark .divide-gray-200 > :not([hidden]) ~ :not([hidden]) { border-color: #334155; }
        html.dark .ring-white { --tw-ring-color: #1e293b; }

        /* حقول الإدخال */
        html.dark input,
        html.dark select,
        html.dark textarea { background-color: #0f172a; color: #e2e8f0; border-color: #475569; }
        html.dark input::placeholder,
        html.dark textarea::placeholder { color: #64748b; }
        html.dark select option { background-color: #1e293b; color: #e2e8f0; }
        html.dark input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.85); }

        /* عند الطباعة بالكامل: إخفاء الإطار وإظهار المحتوى فقط */
        @media print {
            aside, header, #pageLoader { display: none !important; }
            main { padding: 0 !important; }
        }

        /* ===== Tom Select — مخصص لتصميم النظام ===== */
        .ts-wrapper { width: 100%; }
        .ts-wrapper .ts-control {
            font-family: var(--font-ar) !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            padding: 0.4rem 0.75rem !important;
            min-height: 40px;
            background: #fff !important;
            box-shadow: none !important;
            outline: none !important;
            cursor: text;
            gap: 4px;
        }
        .ts-wrapper.focus .ts-control,
        .ts-wrapper.focus .ts-control:focus,
        .ts-wrapper .ts-control:focus-within {
            border-color: #008A3B !important;
            box-shadow: 0 0 0 2px rgba(0,138,59,.12) !important;
            outline: none !important;
        }
        /* إزالة أي outline أو border داخلي */
        .ts-wrapper .ts-control input,
        .ts-wrapper .ts-control input:focus {
            font-family: var(--font-ar) !important;
            color: #1f2937;
            outline: none !important;
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
        }
        .ts-wrapper .ts-control input::placeholder { color: #9ca3af; }
        .ts-wrapper .ts-control .item { line-height: 1.5; }
        /* زر المسح */
        .ts-wrapper .clear-button { color: #9ca3af; opacity: 1; font-size: 14px; }
        .ts-wrapper .clear-button:hover { color: #ef4444; }
        .ts-dropdown {
            font-family: var(--font-ar) !important;
            border-radius: 0.5rem !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 8px 24px rgba(0,0,0,.1) !important;
            margin-top: 3px;
            font-size: .875rem;
            outline: none !important;
        }
        .ts-dropdown .option { padding: .45rem .75rem; }
        .ts-dropdown .option:hover,
        .ts-dropdown .option.active { background: #f0fdf4; color: #15803d; }
        .ts-dropdown .option.selected { background: #dcfce7; color: #166534; font-weight: 700; }
        .ts-dropdown .optgroup-header { font-size:.7rem; font-weight:700; color:#6b7280; padding:.4rem .75rem; text-transform:uppercase; }
        html.dark .ts-wrapper .ts-control { background:#0f172a !important; border-color:#475569 !important; color:#e2e8f0; }
        html.dark .ts-dropdown { background:#1e293b; border-color:#334155; }
        html.dark .ts-dropdown .option:hover,
        html.dark .ts-dropdown .option.active { background:#134e2a; color:#4ade80; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <div id="pageLoader"></div>

    <div class="flex min-h-screen">

        {{-- Sidebar active-link style --}}
        <style>
            .sb-link { display:flex;align-items:center;gap:10px;padding:7px 14px;border-radius:8px;font-size:13px;transition:all .15s;color:#4b5563; }
            .sb-link:hover { background:#f8fafc;color:#008A3B; }
            .sb-link.active { background:#EBF7F0;color:#008A3B;font-weight:700;border-inline-start:3px solid #008A3B; }
            .sb-sub { display:block;padding:6px 12px;border-radius:6px;font-size:12.5px;transition:all .15s;color:#6b7280; }
            .sb-sub:hover { background:#f8fafc;color:#008A3B; }
            .sb-sub.active { background:#EBF7F0;color:#008A3B;font-weight:700;border-inline-start:3px solid #008A3B; }
            .sb-section { font-size:10px;font-weight:800;color:#9ca3af;letter-spacing:.08em;text-transform:uppercase;padding:10px 14px 4px; }
            .sb-group-icon { font-size:13px;width:18px;text-align:center;flex-shrink:0; }
            .sb-indent { {{ $rtl ? 'padding-right:32px;margin-right:18px;border-right:1.5px solid #f1f5f9;' : 'padding-left:32px;margin-left:18px;border-left:1.5px solid #f1f5f9;' }} }
        </style>

        {{-- 1. القائمة الجانبية (Sidebar) --}}
        <aside class="w-64 bg-white shadow-xl flex flex-col fixed top-0 {{ $rtl ? 'right-0 border-l' : 'left-0 border-r' }} h-full z-40 border-gray-100">

            {{-- ترويسة اللوجو --}}
            <div class="px-4 py-4 border-b border-gray-100 flex flex-col items-center gap-2 bg-white">
                <img src="{{ asset('images/EFC-.png') }}" alt="EFC Logo" class="h-16 w-auto object-contain">
                <div class="text-center leading-tight">
                    <h1 class="text-sm font-bold text-gray-900">{{ __('messages.app_name') }}</h1>
                </div>
            </div>

            {{-- روابط التنقل --}}
            <nav id="sidebar-nav" class="flex-grow py-2 sidebar-scroll overflow-y-auto">

                {{-- الرئيسية --}}
                <a href="{{ url('/') }}" class="sb-link mx-2 {{ request()->is('/') || request()->is('efc/public') ? 'active' : '' }}">
                    <i class="fas fa-th-large sb-group-icon text-[#008A3B]"></i>
                    <span>{{ __('messages.nav.dashboard') }}</span>
                </a>

                {{-- ===== البيانات الأساسية ===== --}}
                <p class="sb-section">{{ __('messages.nav.basic_data') }}</p>

                {{-- العملاء --}}
                <div class="mx-2 mb-0.5">
                    <div class="sb-link cursor-default hover:bg-transparent hover:text-gray-500 text-gray-600">
                        <i class="fas fa-users sb-group-icon text-[#008A3B]"></i>
                        <span class="font-semibold text-[13px]">{{ __('messages.nav.clients') }}</span>
                    </div>
                    <div class="sb-indent space-y-0.5">
                        <a href="{{ route('clients.index') }}"  class="sb-sub {{ request()->routeIs('clients.index')  ? 'active' : '' }}">{{ __('messages.nav.view_clients') }}</a>
                        <a href="{{ route('clients.create') }}" class="sb-sub {{ request()->routeIs('clients.create') ? 'active' : '' }}">{{ __('messages.nav.add_client') }}</a>
                    </div>
                </div>

                {{-- الموردون --}}
                <div class="mx-2 mb-0.5">
                    <div class="sb-link cursor-default hover:bg-transparent hover:text-gray-500 text-gray-600">
                        <i class="fas fa-truck sb-group-icon text-[#005B9F]"></i>
                        <span class="font-semibold text-[13px]">{{ __('messages.nav.vendors') }}</span>
                    </div>
                    <div class="sb-indent space-y-0.5">
                        <a href="{{ route('vendors.index') }}"  class="sb-sub {{ request()->routeIs('vendors.index')  ? 'active' : '' }}">{{ __('messages.nav.view_vendors') }}</a>
                        <a href="{{ route('vendors.create') }}" class="sb-sub {{ request()->routeIs('vendors.create') ? 'active' : '' }}">{{ __('messages.nav.add_vendor') }}</a>
                    </div>
                </div>

                {{-- الأصناف --}}
                <div class="mx-2 mb-0.5">
                    <div class="sb-link cursor-default hover:bg-transparent hover:text-gray-500 text-gray-600">
                        <i class="fas fa-box sb-group-icon text-amber-500"></i>
                        <span class="font-semibold text-[13px]">{{ __('messages.nav.items') }}</span>
                    </div>
                    <div class="sb-indent space-y-0.5">
                        <a href="{{ route('items.index') }}"  class="sb-sub {{ request()->routeIs('items.index')  ? 'active' : '' }}">{{ __('messages.nav.view_items') }}</a>
                        <a href="{{ route('items.create') }}" class="sb-sub {{ request()->routeIs('items.create') ? 'active' : '' }}">{{ __('messages.nav.add_item') }}</a>
                    </div>
                </div>

                {{-- التوريد --}}
                <div class="mx-2 mb-0.5">
                    <div class="sb-link cursor-default hover:bg-transparent hover:text-gray-500 text-gray-600">
                        <i class="fas fa-network-wired sb-group-icon text-indigo-500"></i>
                        <span class="font-semibold text-[13px]">{{ __('messages.nav.sourcing') }}</span>
                    </div>
                    <div class="sb-indent space-y-0.5">
                        <a href="{{ route('sourcing.index') }}" class="sb-sub {{ request()->routeIs('sourcing.*') ? 'active' : '' }}">{{ __('messages.nav.sourcing_link') }}</a>
                    </div>
                </div>

                {{-- ===== إدارة المبيعات ===== --}}
                <p class="sb-section">{{ __('messages.nav.sales_mgmt') }}</p>

                {{-- عروض الأسعار --}}
                <div class="mx-2 mb-0.5">
                    <div class="sb-link cursor-default hover:bg-transparent hover:text-gray-500 text-gray-600">
                        <i class="fas fa-file-invoice-dollar sb-group-icon text-[#008A3B]"></i>
                        <span class="font-semibold text-[13px]">{{ __('messages.nav.quotations') }}</span>
                    </div>
                    <div class="sb-indent space-y-0.5">
                        <a href="{{ route('quotations.index') }}"  class="sb-sub {{ request()->routeIs('quotations.index')  ? 'active' : '' }}">{{ __('messages.nav.view_quotations') }}</a>
                        <a href="{{ route('quotations.create') }}" class="sb-sub {{ request()->routeIs('quotations.create') ? 'active' : '' }}">{{ __('messages.nav.add_quotation') }}</a>
                        <a href="{{ route('quotations.sent-log') }}" class="sb-sub {{ request()->routeIs('quotations.sent-log') ? 'active' : '' }}">{{ __('messages.nav.sent_log') }}</a>
                    </div>
                </div>

                {{-- أوامر البيع --}}
                <div class="mx-2 mb-0.5">
                    <div class="sb-link cursor-default hover:bg-transparent hover:text-gray-500 text-gray-600">
                        <i class="fas fa-file-contract sb-group-icon text-[#008A3B]"></i>
                        <span class="font-semibold text-[13px]">{{ $rtl ? 'أوامر البيع' : 'Sales Orders' }}</span>
                    </div>
                    <div class="sb-indent space-y-0.5">
                        <a href="{{ route('sales-orders.index') }}" class="sb-sub {{ request()->routeIs('sales-orders.*') ? 'active' : '' }}">
                            {{ $rtl ? 'كل أوامر البيع' : 'All Sales Orders' }}
                        </a>
                    </div>
                </div>

                {{-- قوائم الأسعار --}}
                <div class="mx-2 mb-0.5">
                    <div class="sb-link cursor-default hover:bg-transparent hover:text-gray-500 text-gray-600">
                        <i class="fas fa-tags sb-group-icon text-[#005B9F]"></i>
                        <span class="font-semibold text-[13px]">{{ __('messages.nav.price_lists') }}</span>
                    </div>
                    <div class="sb-indent space-y-0.5">
                        <a href="{{ route('price-lists.index') }}"  class="sb-sub {{ request()->routeIs('price-lists.index')  ? 'active' : '' }}">{{ __('messages.nav.view_price_lists') }}</a>
                        <a href="{{ route('price-lists.create') }}" class="sb-sub {{ request()->routeIs('price-lists.create') ? 'active' : '' }}">{{ __('messages.nav.add_price_list') }}</a>
                    </div>
                </div>

                {{-- ===== الإدارة المالية ===== --}}
                <p class="sb-section">{{ $rtl ? 'الإدارة المالية' : 'Financial Management' }}</p>

                <a href="{{ route('cost-centers.index') }}" class="sb-link mx-2 {{ request()->routeIs('cost-centers.*') ? 'active' : '' }}">
                    <i class="fas fa-layer-group sb-group-icon text-purple-500"></i>
                    <span>{{ $rtl ? 'مراكز التكلفة' : 'Cost Centers' }}</span>
                </a>

                <div class="mx-2 mb-0.5">
                    <div class="sb-link cursor-default hover:bg-transparent hover:text-gray-500 text-gray-600">
                        <i class="fas fa-receipt sb-group-icon text-amber-500"></i>
                        <span class="font-semibold text-[13px]">{{ $rtl ? 'المصروفات' : 'Expenses' }}</span>
                    </div>
                    <div class="sb-indent space-y-0.5">
                        <a href="{{ route('expenses.index') }}" class="sb-sub {{ request()->routeIs('expenses.index') ? 'active' : '' }}">{{ $rtl ? 'عرض المصروفات' : 'View Expenses' }}</a>
                        <a href="{{ route('expenses.create') }}" class="sb-sub {{ request()->routeIs('expenses.create') ? 'active' : '' }}">{{ $rtl ? 'إضافة مصروف' : 'Add Expense' }}</a>
                    </div>
                </div>

                <a href="{{ route('purchase-invoices.index') }}" class="sb-link mx-2 {{ request()->routeIs('purchase-invoices.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice sb-group-icon text-[#005B9F]"></i>
                    <span>{{ $rtl ? 'فواتير الشراء' : 'Purchase Invoices' }}</span>
                </a>

                <a href="{{ route('payables.index') }}" class="sb-link mx-2 {{ request()->routeIs('payables.*') || request()->routeIs('vendor-payments.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar sb-group-icon text-red-500"></i>
                    <span>{{ $rtl ? 'الالتزامات (موردين)' : 'Payables' }}</span>
                </a>

                <a href="{{ route('receivables.index') }}" class="sb-link mx-2 {{ request()->routeIs('receivables.*') || request()->routeIs('client-receipts.*') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-usd sb-group-icon text-[#008A3B]"></i>
                    <span>{{ $rtl ? 'المستحقات (عملاء)' : 'Receivables' }}</span>
                </a>

                {{-- ===== النظام ===== --}}
                <p class="sb-section">{{ __('messages.nav.system') }}</p>

                <a href="{{ route('settings.index') }}" class="sb-link mx-2 {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="fas fa-cogs sb-group-icon text-gray-400"></i>
                    <span>{{ __('messages.nav.settings') }}</span>
                </a>

                @if(app()->environment('local'))
                <form action="{{ route('settings.reset-database') }}" method="POST" class="mx-2 mt-1"
                    onsubmit="return confirm({{ json_encode($rtl ? 'تصفير قاعدة البيانات بالكامل؟ هذا الإجراء لا يمكن التراجع عنه.' : 'Reset the entire database? This cannot be undone.') }});">
                    @csrf
                    <button type="submit" class="sb-link w-full text-red-500 hover:bg-red-50 hover:text-red-600">
                        <i class="fas fa-skull-crossbones sb-group-icon text-red-500"></i>
                        <span>{{ $rtl ? 'تصفير قاعدة البيانات' : 'Reset Database' }}</span>
                    </button>
                </form>
                @endif

            </nav>

            {{-- تذييل السايد بار --}}
            <div class="px-3 py-3 border-t border-gray-100 bg-gray-50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-[#005B9F] flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        {{ mb_substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-xs text-gray-900 truncate">{{ auth()->user()->name ?? '' }}</p>
                        <p class="text-[10px] text-gray-400 truncate font-mono">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="flex-shrink-0">
                        @csrf
                        <button type="submit" title="تسجيل الخروج"
                            class="text-gray-400 hover:text-red-500 transition-colors p-1">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var active = document.querySelector('#sidebar-nav .active');
                    if (active) active.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                });
            </script>
        </aside>

        {{-- 2. منطقة المحتوى الرئيسي --}}
        <main class="flex-1 {{ $rtl ? 'pr-64' : 'pl-64' }} min-h-screen">

            {{-- بار علوي بسيط (Topbar) --}}
            <header class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-30">
                <div class="px-6 py-4 flex justify-between items-center gap-4">
                    <div class="text-sm text-gray-500 whitespace-nowrap">
                        {{ __('messages.home') }} / <span class="text-gray-900 font-medium">@yield('header_title', __('messages.nav.dashboard'))</span>
                    </div>

                    {{-- بحث سريع عام في العملاء --}}
                    <form action="{{ route('clients.index') }}" method="GET" class="hidden md:flex flex-1 max-w-md mx-auto">
                        <div class="relative w-full">
                            <i class="fas fa-search absolute top-1/2 -translate-y-1/2 {{ $rtl ? 'right-4' : 'left-4' }} text-gray-400 text-sm"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.quick_search') }}"
                                class="w-full {{ $rtl ? 'pr-11 pl-4' : 'pl-11 pr-4' }} py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors">
                        </div>
                    </form>

                    <div class="flex items-center gap-4">
                        {{-- زر تبديل اللغة عربي/إنجليزي --}}
                        <a href="{{ route('lang.switch', $rtl ? 'en' : 'ar') }}" title="{{ __('messages.lang_switch') }}"
                            class="flex items-center gap-1.5 h-9 px-3 rounded-lg text-sm font-bold text-gray-600 hover:text-[#005B9F] hover:bg-gray-50 transition-colors">
                            <i class="fas fa-globe"></i> {{ __('messages.lang_switch') }}
                        </a>
                        {{-- زر تبديل الوضع الليلي --}}
                        <button onclick="toggleDarkMode()" id="darkToggle" title="تبديل الوضع الليلي"
                            class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 hover:text-[#005B9F] hover:bg-gray-50 transition-colors">
                            <i class="fas fa-moon text-lg" id="darkIcon"></i>
                        </button>
                        {{-- تنبيهات: فاتورة شراء بدون أمر بيع أو العكس --}}
                        <div class="relative">
                            <button type="button" onclick="toggleNotifBell()" id="notifBellBtn" class="relative text-gray-400 hover:text-[#005B9F] transition-colors">
                                <i class="fas fa-bell text-xl"></i>
                                @if(($navNotifications ?? collect())->isNotEmpty())
                                <span class="absolute -top-1.5 {{ $rtl ? '-left-1.5' : '-right-1.5' }} bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[16px] h-4 flex items-center justify-center px-1">
                                    {{ $navNotifications->count() }}
                                </span>
                                @endif
                            </button>
                            <div id="notifBellDropdown" class="hidden absolute {{ $rtl ? 'left-0' : 'right-0' }} mt-2 w-80 bg-white rounded-xl shadow-2xl border border-gray-100 z-50 overflow-hidden" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
                                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 font-bold text-sm text-gray-700">
                                    {{ $rtl ? 'تنبيهات — مستندات ناقصة' : 'Alerts — Missing Documents' }}
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    @forelse(($navNotifications ?? collect()) as $n)
                                        <a href="{{ $n['action_route'] }}" class="block px-4 py-3 border-b border-gray-50 hover:bg-amber-50 transition-colors">
                                            <p class="text-xs font-mono text-gray-400 mb-1">{{ $n['quotation']->quote_number }}</p>
                                            <p class="text-sm font-bold text-gray-800">
                                                {{ optional($n['quotation']->client)->displayName($rtl ? 'ar' : 'en') }}
                                            </p>
                                            <p class="text-xs text-amber-600 mt-1 flex items-center gap-1.5">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                {{ $n['missing'] === 'sales_order'
                                                    ? ($rtl ? 'فيه فاتورة شراء بدون أمر بيع — اضغط للإنشاء' : 'Has a purchase invoice but no sales order — click to create')
                                                    : ($rtl ? 'فيه أمر بيع بدون فاتورة شراء — اضغط للإنشاء' : 'Has a sales order but no purchase invoice — click to create') }}
                                            </p>
                                        </a>
                                    @empty
                                        <p class="px-4 py-8 text-center text-sm text-gray-400">{{ $rtl ? 'لا توجد تنبيهات' : 'No alerts' }}</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <script>
                            function toggleNotifBell() {
                                document.getElementById('notifBellDropdown').classList.toggle('hidden');
                            }
                            document.addEventListener('click', function (e) {
                                const btn = document.getElementById('notifBellBtn');
                                const dd = document.getElementById('notifBellDropdown');
                                if (dd && !dd.classList.contains('hidden') && !dd.contains(e.target) && !btn.contains(e.target)) {
                                    dd.classList.add('hidden');
                                }
                            });
                        </script>
                        <div class="h-8 w-px bg-gray-200"></div>
                        <span class="text-sm font-medium whitespace-nowrap">{{ __('messages.welcome') }}</span>
                    </div>
                </div>
            </header>

            {{-- المحتوى المتغير --}}
            <div class="p-8">
                {{-- رسائل النجاح / الخطأ العامة --}}
                @if (session('success'))
                    <div class="max-w-7xl mx-auto mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3.5 shadow-sm animate-fade-in">
                        <i class="fas fa-check-circle text-green-500 text-lg"></i>
                        <span class="font-bold">{{ session('success') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="max-w-7xl mx-auto mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-3.5 shadow-sm animate-fade-in">
                        <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                        <span class="font-bold">{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>

        </main>
    </div>

    {{-- سكريبت عام: الوضع الليلي + شريط التحميل --}}
    <script>
        // تبديل الوضع الليلي مع الحفظ في المتصفح
        function applyThemeIcon() {
            var icon = document.getElementById('darkIcon');
            if (!icon) return;
            if (document.documentElement.classList.contains('dark')) {
                icon.className = 'fas fa-sun text-lg';
            } else {
                icon.className = 'fas fa-moon text-lg';
            }
        }
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            try {
                localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            } catch (e) {}
            applyThemeIcon();
        }
        applyThemeIcon();

        // إعدادات الطباعة (لغة الواجهة الحالية)
        const PRINT_CFG = {
            dir:       @json($rtl ? 'rtl' : 'ltr'),
            lang:      @json(app()->getLocale()),
            align:     @json($rtl ? 'right' : 'left'),
            brand:     @json(__('messages.app_name')),
            brandSub:  @json(__('messages.app_sub')),
            logoUrl:   @json(asset('images/EFC-.png')),
            sysTitle:  @json(__('messages.system_title')),
            refLabel:  @json(__('messages.print.ref')),
            dateLabel: @json(__('messages.print.printed_on')),
            footer:    @json(__('messages.print.footer')),
            signature: @json(__('messages.print.signature')),
        };

        // طباعة بيانات أي بطاقة في مستند احترافي منسّق (يُستخدم في كل البوب أب)
        function printData(title, subtitle, rows) {
            var w = window.open('', '_blank', 'width=860,height=680');
            if (!w) { alert(PRINT_CFG.lang === 'ar' ? 'فضلاً اسمح بالنوافذ المنبثقة لتفعيل الطباعة.' : 'Please allow pop-ups to enable printing.'); return; }

            var body = rows.map(function (r, i) {
                var val = (r[1] === null || r[1] === undefined || r[1] === '') ? '—' : String(r[1]);
                return '<tr class="' + (i % 2 ? 'alt' : '') + '"><th>' + r[0] + '</th><td>' + val + '</td></tr>';
            }).join('');

            var ref = 'EFC-' + Date.now().toString().slice(-6);
            var now = new Date().toLocaleString(PRINT_CFG.lang === 'ar' ? 'ar-EG' : 'en-GB',
                { dateStyle: 'medium', timeStyle: 'short' });
            var opp = PRINT_CFG.align === 'right' ? 'left' : 'right';

            w.document.write(
                '<!DOCTYPE html><html lang="' + PRINT_CFG.lang + '" dir="' + PRINT_CFG.dir + '"><head><meta charset="UTF-8"><title>' + title + '</title>' +
                '<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">' +
                '<style>' +
                '@page{margin:16mm;}' +
                '*{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
                'body{font-family:"Cairo",Arial,sans-serif;color:#1e293b;margin:0;font-size:13px;line-height:1.5;}' +
                '.sheet{max-width:780px;margin:0 auto;}' +
                /* الترويسة */
                '.head{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:16px 24px;' +
                    'background:linear-gradient(135deg,#005B9F 0%,#008A3B 100%);color:#fff;border-radius:10px;}' +
                '.logo-wrap{display:flex;align-items:center;gap:10px;}' +
                '.logo-wrap img{height:48px;width:auto;object-fit:contain;flex-shrink:0;}' +
                '.brand{font-size:15px;font-weight:800;line-height:1.2;}' +
                '.brand small{display:block;font-size:11px;font-weight:400;opacity:.85;margin-top:2px;}' +
                '.doc{text-align:' + opp + ';}' +
                '.doc .t{font-size:18px;font-weight:700;line-height:1.2;}' +
                '.doc .s{font-size:12px;opacity:.9;margin-top:3px;}' +
                /* شريط المعلومات */
                '.meta{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;' +
                    'margin:14px 2px 18px;padding:10px 14px;background:#f1f5f9;border-radius:8px;' +
                    'font-size:11.5px;color:#475569;}' +
                '.meta b{color:#0f172a;font-weight:700;}' +
                /* الجدول */
                'table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid #e2e8f0;' +
                    'border-radius:8px;overflow:hidden;}' +
                'th,td{padding:11px 14px;font-size:13px;text-align:' + PRINT_CFG.align + ';vertical-align:top;' +
                    'border-bottom:1px solid #eef2f7;}' +
                'tr:last-child th,tr:last-child td{border-bottom:none;}' +
                'th{background:#f8fafc;width:34%;color:#334155;font-weight:700;' +
                    'border-' + opp + ':1px solid #eef2f7;}' +
                'td{color:#0f172a;font-weight:500;}' +
                'tr.alt th{background:#f1f5f9;} tr.alt td{background:#fcfdfe;}' +
                /* التوقيع والتذييل */
                '.sign{display:flex;justify-content:space-between;gap:40px;margin-top:34px;}' +
                '.sign div{flex:1;border-top:1.5px solid #cbd5e1;padding-top:8px;font-size:11.5px;color:#64748b;text-align:center;}' +
                '.foot{margin-top:22px;font-size:10.5px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;padding-top:10px;}' +
                '</style></head><body><div class="sheet">' +
                '<div class="head">' +
                    '<div class="logo-wrap">' +
                        '<img src="' + PRINT_CFG.logoUrl + '" onerror="this.style.display=\'none\'">' +
                        '<div class="brand">' + PRINT_CFG.brand + (PRINT_CFG.brandSub ? '<small>' + PRINT_CFG.brandSub + '</small>' : '') + '</div>' +
                    '</div>' +
                    '<div class="doc"><div class="t">' + title + '</div><div class="s">' + (subtitle || '') + '</div></div>' +
                '</div>' +
                '<div class="meta"><span>' + PRINT_CFG.refLabel + ': <b>' + ref + '</b></span>' +
                    '<span>' + PRINT_CFG.dateLabel + ': <b>' + now + '</b></span></div>' +
                '<table>' + body + '</table>' +
                '<div class="sign"><div>' + PRINT_CFG.signature + '</div><div>' + PRINT_CFG.signature + '</div></div>' +
                '<div class="foot">' + PRINT_CFG.footer + '</div>' +
                '</div></body></html>'
            );
            w.document.close();
            w.focus();
            setTimeout(function () { w.print(); }, 450);
        }

        // ===== Tom Select: تفعيل تلقائي على كل select[data-search] =====
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('select[data-search]').forEach(function (el) {
                if (el._tomSelect) return; // لا تعيد التهيئة
                var opts = {
                    allowEmptyOption: true,
                    maxOptions: 300,
                    render: {
                        no_results: function () {
                            return '<div class="no-results" style="padding:.5rem .75rem;color:#9ca3af;font-size:.8rem;">لا توجد نتائج</div>';
                        }
                    }
                };
                // Only add clear button on non-required selects
                if (!el.required) {
                    opts.plugins = ['clear_button'];
                }
                new TomSelect(el, opts);
            });
        });

        // شريط تحميل علوي عند مغادرة الصفحة (تنقّل/فلترة)
        var pageLoader = document.getElementById('pageLoader');
        window.addEventListener('beforeunload', function () {
            if (pageLoader) pageLoader.classList.add('active');
        });
        // إخفاؤه عند العودة للصفحة من الكاش (زر الرجوع)
        window.addEventListener('pageshow', function () {
            if (pageLoader) pageLoader.classList.remove('active');
        });
    </script>

</body>
</html>