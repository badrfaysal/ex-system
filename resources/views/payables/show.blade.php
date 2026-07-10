@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $docDir = $isAr ? 'rtl' : 'ltr';
    $txtAlign = $isAr ? 'right' : 'left';
    $txtAlignOpp = $isAr ? 'left' : 'right';
    $vendorName = $isAr ? $vendor->name_ar : ($vendor->name_en ?: $vendor->name_ar);
    $totalInvoiced = $timeline->where('type', 'invoice')->sum('amount');
    $totalPaid = -1 * $timeline->where('type', 'payment')->sum('amount');
@endphp
@section('header_title', $vendorName)

@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        aside, header, #pageLoader { display: none !important; }
        main { padding: 0 !important; }
        body, html { background: #fff !important; }
        .print-doc {
            box-shadow: none !important; border: none !important;
            margin: 0 !important; max-width: 100% !important;
            border-radius: 0 !important; font-size: 11px !important;
        }
        .print-doc * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        @page { margin: 8mm 10mm; size: A4 portrait; }
    }
</style>

<div class="max-w-4xl mx-auto">

    {{-- أزرار التحكم --}}
    <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('payables.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
            <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ $isAr ? 'كل الالتزامات' : 'All Payables' }}
        </a>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('vendor-payments.create', ['vendor_id' => $vendor->id]) }}" class="px-5 py-2 bg-red-600 text-white rounded-lg font-bold text-sm hover:bg-red-700 flex items-center gap-2">
                <i class="fas fa-plus"></i> {{ $isAr ? 'تسجيل سند دفع' : 'Record Payment' }}
            </a>
            <button type="button" onclick="window.print()" class="px-5 py-2 bg-[#005B9F] text-white rounded-lg font-bold text-sm hover:bg-blue-800 flex items-center gap-2">
                <i class="fas fa-print"></i> {{ $isAr ? 'طباعة كشف الحساب' : 'Print Statement' }}
            </button>
            @if($vendor->email)
            <button type="button" data-open-send-mail class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold text-sm flex items-center gap-2">
                <i class="fas fa-envelope"></i> {{ $isAr ? 'إرسال بالبريد' : 'Email Statement' }}
            </button>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="no-print mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 flex items-center gap-3">
        <i class="fas fa-check-circle text-green-500 text-lg"></i>
        <span class="font-medium text-sm">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="no-print mb-4 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 flex items-center gap-3">
        <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
        <span class="font-medium text-sm">{{ session('error') }}</span>
    </div>
    @endif

    {{-- ============ المستند القابل للطباعة ============ --}}
    <div class="print-doc bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8" dir="{{ $docDir }}">
        <div class="h-1.5 bg-gradient-to-r from-red-500 to-red-700"></div>

        {{-- ترويسة --}}
        <div class="px-8 pt-5 pb-4 flex items-center justify-between gap-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/EFC-.png') }}" alt="{{ __('messages.app_name') }}" class="h-16 w-auto object-contain" onerror="this.style.display='none'">
                <div>
                    <p class="text-base font-extrabold text-gray-900 leading-tight">{{ __('messages.app_name') }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ __('messages.app_sub') }}</p>
                </div>
            </div>
            <div class="text-{{ $txtAlignOpp }}">
                <p class="text-2xl font-extrabold text-red-600 tracking-tight leading-none">{{ $isAr ? 'كشف حساب مورد' : 'Vendor Statement' }}</p>
                <p class="text-xs text-gray-400 mt-1" dir="ltr">{{ now()->format('Y-m-d') }}</p>
            </div>
        </div>

        {{-- بيانات المورد + الملخص --}}
        <div class="px-8 py-4 grid grid-cols-2 gap-8 border-b border-gray-100 bg-gray-50/50">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ $isAr ? 'المورد' : 'Vendor' }}</p>
                <p class="text-base font-extrabold text-gray-900">{{ $vendorName }}</p>
                @if($vendor->mobile || $vendor->email)
                <p class="text-xs text-gray-400 mt-0.5" dir="ltr">{{ $vendor->mobile }} @if($vendor->mobile && $vendor->email) <span class="mx-1">|</span> @endif {{ $vendor->email }}</p>
                @endif
            </div>
            <div class="text-{{ $txtAlignOpp }}">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">{{ $isAr ? 'الملخص' : 'Summary' }}</p>
                <table class="text-xs w-full" dir="{{ $docDir }}">
                    <tr>
                        <td class="text-gray-400 pb-1.5 {{ $isAr ? 'pl-3' : 'pr-3' }}">{{ $isAr ? 'إجمالي الفواتير:' : 'Total Invoiced:' }}</td>
                        <td class="font-bold text-gray-700 pb-1.5 text-{{ $txtAlignOpp }}" dir="ltr">{{ number_format($totalInvoiced, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray-400 pb-1.5 {{ $isAr ? 'pl-3' : 'pr-3' }}">{{ $isAr ? 'إجمالي المدفوع:' : 'Total Paid:' }}</td>
                        <td class="font-bold text-green-600 pb-1.5 text-{{ $txtAlignOpp }}" dir="ltr">{{ number_format($totalPaid, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-gray-400 {{ $isAr ? 'pl-3' : 'pr-3' }}">{{ $isAr ? 'الباقي:' : 'Remaining:' }}</td>
                        <td class="font-extrabold text-{{ $balance > 0 ? 'red-600' : 'green-600' }} text-{{ $txtAlignOpp }}" dir="ltr">{{ number_format($balance, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- جدول الحركات --}}
        <div class="px-8 py-3">
            <table class="w-full border-collapse text-sm" style="text-align:{{ $txtAlign }}">
                <thead>
                    <tr style="background:#1e293b;color:#fff;">
                        <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'التاريخ' : 'Date' }}</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'المرجع' : 'Reference' }}</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'النوع' : 'Type' }}</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'المبلغ' : 'Amount' }}</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold" style="text-align:{{ $txtAlignOpp }}">{{ $isAr ? 'الرصيد الجاري' : 'Balance' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($timeline as $idx => $entry)
                        <tr class="{{ $idx % 2 === 0 ? '' : 'bg-gray-50/70' }} border-b border-gray-100">
                            <td class="px-3 py-2 text-gray-500 text-xs" dir="ltr">{{ optional($entry['date'])->format('Y-m-d') }}</td>
                            <td class="px-3 py-2 text-xs"><span class="font-mono text-gray-700">{{ $entry['ref'] }}</span></td>
                            <td class="px-3 py-2 text-xs">
                                @if($entry['type'] === 'invoice')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-600">{{ $isAr ? 'فاتورة شراء' : 'Purchase Invoice' }}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-50 text-green-700">{{ $isAr ? 'سند دفع' : 'Payment' }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-bold text-xs {{ $entry['amount'] >= 0 ? 'text-red-600' : 'text-green-600' }}" dir="ltr">
                                {{ $entry['amount'] >= 0 ? '+' : '' }}{{ number_format($entry['amount'], 2) }}
                            </td>
                            <td class="px-3 py-2 font-extrabold text-gray-900 text-xs" style="text-align:{{ $txtAlignOpp }}" dir="ltr">{{ number_format($entry['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد حركات' : 'No transactions yet' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-8 py-4 flex justify-end">
            <div class="rounded-xl px-5 py-3" style="background:#dc2626;">
                <span class="font-extrabold text-white text-sm">{{ $isAr ? 'الرصيد المستحق النهائي: ' : 'Final Balance Due: ' }}</span>
                <span class="font-extrabold text-white text-lg" dir="ltr">{{ number_format($balance, 2) }}</span>
            </div>
        </div>

        <div class="px-4 py-3 border-t border-gray-100 bg-gray-50/60 text-center">
            <div class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2 text-[10px] text-gray-500 font-medium" dir="ltr">
                <span class="flex items-center"><i class="fas fa-map-marker-alt text-[#005B9F] mr-1.5"></i>City Star Towers – Tower 5, 10th District, 6th of October City, Giza, Egypt</span>
                <span class="text-gray-300 hidden xl:inline">|</span>
                <span class="flex items-center whitespace-nowrap"><i class="fas fa-phone-alt text-[#005B9F] mr-1.5"></i>(+20) 15-5772-2227</span>
                <span class="text-gray-300 hidden md:inline">|</span>
                <span class="flex items-center whitespace-nowrap"><i class="fas fa-envelope text-[#005B9F] mr-1.5"></i>info@efcexport.com</span>
            </div>
        </div>
        <div class="h-1 bg-gradient-to-r from-red-500 to-red-700"></div>
    </div>
</div>

{{-- ============ Modal إرسال البريد ============ --}}
@if($vendor->email)
<div id="sendMailModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 no-print" role="dialog">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSendMailModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" dir="{{ $docDir }}">
        <div class="bg-amber-500 text-white px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center">
                    <i class="fas fa-envelope text-sm"></i>
                </div>
                <p class="font-bold text-base leading-none">{{ $isAr ? 'إرسال كشف الحساب' : 'Send Statement' }}</p>
            </div>
            <button type="button" onclick="closeSendMailModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/20">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
                <div class="flex items-center gap-2">
                    <i class="fas fa-at text-gray-400 w-4 text-center"></i>
                    <span class="text-gray-500">{{ $isAr ? 'إرسال إلى:' : 'Send To:' }}</span>
                    <span class="font-bold text-amber-600" dir="ltr">{{ $vendor->email }}</span>
                </div>
            </div>
            <form action="{{ route('payables.send-email', $vendor) }}" method="POST" class="flex items-center gap-3 justify-end">
                @csrf
                <button type="button" onclick="closeSendMailModal()" class="px-5 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 text-sm font-medium">{{ $isAr ? 'إلغاء' : 'Cancel' }}</button>
                <button type="submit" class="px-6 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold text-sm flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i> {{ $isAr ? 'إرسال الآن' : 'Send Now' }}
                </button>
            </form>
        </div>
    </div>
</div>
<script>
    document.querySelectorAll('[data-open-send-mail]').forEach(btn => btn.addEventListener('click', function () {
        const modal = document.getElementById('sendMailModal');
        modal.classList.remove('hidden'); modal.classList.add('flex');
    }));
    function closeSendMailModal() {
        const modal = document.getElementById('sendMailModal');
        modal.classList.add('hidden'); modal.classList.remove('flex');
    }
</script>
@endif
@endsection
