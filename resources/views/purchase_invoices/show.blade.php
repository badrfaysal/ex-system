@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $cur  = $purchaseInvoice->currency ?? 'EGP';
@endphp
@section('header_title', $purchaseInvoice->invoice_number)

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('quotations.show', $purchaseInvoice->quotation) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
            <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ $isAr ? 'رجوع لعرض السعر' : 'Back to Quotation' }}
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('purchase-invoices.print', $purchaseInvoice) }}" target="_blank" class="px-5 py-2 bg-[#005B9F] text-white rounded-lg font-bold text-sm hover:bg-blue-800 flex items-center gap-2">
                <i class="fas fa-print"></i> {{ $isAr ? 'طباعة الفاتورة' : 'Print Invoice' }}
            </a>
            <a href="{{ route('purchase-invoices.index') }}" class="text-sm text-gray-500 hover:text-[#005B9F]">{{ $isAr ? 'كل فواتير الشراء' : 'All purchase invoices' }}</a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="h-1.5 bg-gradient-to-r from-[#005B9F] to-[#008A3B]"></div>
        <div class="px-8 py-5 flex items-center justify-between border-b border-gray-100">
            <div>
                <p class="text-2xl font-extrabold text-[#005B9F]">{{ $isAr ? 'فاتورة شراء' : 'Purchase Invoice' }}</p>
                <p class="font-mono font-bold text-gray-600 mt-1 text-sm" dir="ltr">{{ $purchaseInvoice->invoice_number }}</p>
            </div>
            <div class="text-{{ $isAr ? 'left' : 'right' }} text-xs text-gray-500 space-y-1">
                <p><span class="text-gray-400">{{ $isAr ? 'مركز التكلفة:' : 'Cost Center:' }}</span>
                   <a href="{{ route('cost-centers.show', $purchaseInvoice->quotation) }}" class="font-mono font-bold text-[#005B9F] hover:underline">{{ $purchaseInvoice->quotation->quote_number }}</a></p>
                <p><span class="text-gray-400">{{ $isAr ? 'التاريخ:' : 'Date:' }}</span>
                   <span class="font-bold text-gray-800">{{ $purchaseInvoice->invoice_date->format('Y-m-d') }}</span></p>
                @if($purchaseInvoice->vendor_invoice_number)
                <p><span class="text-gray-400">{{ $isAr ? 'رقم فاتورة المورد:' : 'Vendor Invoice No.:' }}</span>
                   <span class="font-mono font-bold text-gray-800">{{ $purchaseInvoice->vendor_invoice_number }}</span></p>
                @endif
            </div>
        </div>

        <div class="px-8 py-4">
            <table class="w-full border-collapse text-sm" style="text-align:{{ $isAr ? 'right' : 'left' }}">
                <thead>
                    <tr style="background:#1e293b;color:#fff;">
                        <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'المورد' : 'Vendor' }}</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'الوصف' : 'Description' }}</th>
                        <th class="px-3 py-2.5 text-center text-[11px] font-bold">{{ $isAr ? 'الكمية' : 'Qty' }}</th>
                        <th class="px-3 py-2.5 text-center text-[11px] font-bold">{{ $isAr ? 'السعر' : 'Price' }}</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseInvoice->items as $idx => $line)
                        <tr class="border-b border-gray-100 {{ $idx === 0 ? 'border-t-2 border-t-blue-100' : '' }}">
                            @if($idx === 0)
                            <td class="px-3 py-2 font-bold text-[#005B9F]" rowspan="{{ $purchaseInvoice->items->count() }}">
                                {{ $isAr ? optional($purchaseInvoice->vendor)->name_ar : (optional($purchaseInvoice->vendor)->name_en ?: optional($purchaseInvoice->vendor)->name_ar) }}
                            </td>
                            @endif
                            <td class="px-3 py-2 text-gray-800">{{ $line->displayDescription($isAr ? 'ar' : 'en') }}</td>
                            <td class="px-3 py-2 text-center" dir="ltr">{{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }}</td>
                            <td class="px-3 py-2 text-center" dir="ltr">{{ number_format($line->unit_price, 2) }}</td>
                            <td class="px-3 py-2 font-bold" dir="ltr">{{ number_format($line->net_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-8 py-4 flex justify-end">
            <div class="w-72 rounded-xl border border-gray-200 overflow-hidden text-sm">
                <div class="flex justify-between items-center px-4 py-2.5 border-b border-gray-100">
                    <span class="text-gray-500">{{ $isAr ? 'الإجمالي الفرعي' : 'Subtotal' }}</span>
                    <span class="font-bold text-gray-800" dir="ltr">{{ number_format($purchaseInvoice->subtotal, 2) }} {{ $cur }}</span>
                </div>
                <div class="flex justify-between items-center px-4 py-3" style="background:#005B9F;">
                    <span class="font-extrabold text-white text-sm">{{ $isAr ? 'الإجمالي النهائي' : 'Grand Total' }}</span>
                    <span class="font-extrabold text-white text-base" dir="ltr">{{ number_format($purchaseInvoice->grand_total, 2) }} {{ $cur }}</span>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($purchaseInvoice->attachments) && count($purchaseInvoice->attachments) > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-paperclip text-[#005B9F]"></i> {{ $isAr ? 'المرفقات' : 'Attachments' }}
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($purchaseInvoice->attachments as $attach)
                @php
                    $isImage = str_starts_with($attach['type'] ?? '', 'image/');
                    $url = asset('storage/' . $attach['path']);
                @endphp
                <a href="{{ $url }}" target="_blank" class="group relative block bg-gray-50 border border-gray-100 rounded-xl overflow-hidden hover:border-[#005B9F] transition-colors" title="{{ $attach['name'] ?? '' }}">
                    @if($isImage)
                        <div class="aspect-square bg-gray-100 w-full overflow-hidden">
                            <img src="{{ $url }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" alt="{{ $attach['name'] ?? '' }}">
                        </div>
                    @else
                        <div class="aspect-square bg-gray-100 w-full flex items-center justify-center text-gray-400 group-hover:text-[#005B9F] transition-colors">
                            <i class="fas fa-file-alt text-4xl"></i>
                        </div>
                    @endif
                    <div class="p-2 text-center bg-white">
                        <p class="text-[10px] text-gray-600 truncate font-semibold">{{ $attach['name'] ?? 'File' }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- التزامات المورد --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-hand-holding-usd text-red-500"></i> {{ $isAr ? 'الالتزام للمورد عن هذه الفاتورة' : 'Liability for vendor from this invoice' }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('payables.show', $purchaseInvoice->vendor_id) }}" class="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-[#005B9F] transition-colors">
                <span class="font-bold text-gray-800">{{ $isAr ? optional($purchaseInvoice->vendor)->name_ar : (optional($purchaseInvoice->vendor)->name_en ?: optional($purchaseInvoice->vendor)->name_ar) }}</span>
                <span class="font-extrabold text-red-600" dir="ltr">{{ number_format($purchaseInvoice->grand_total, 2) }} {{ $cur }}</span>
            </a>
        </div>
    </div>
</div>
@endsection
