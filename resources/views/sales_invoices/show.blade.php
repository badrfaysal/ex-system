@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $cur  = $salesInvoice->currency ?? 'EGP';
    $received = $salesInvoice->received_amount;
    $due = $salesInvoice->balance_due;
@endphp
@section('header_title', $salesInvoice->invoice_number)

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('sales-orders.show', $salesInvoice->salesOrder) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 text-sm font-medium flex items-center gap-2">
            <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ $isAr ? 'رجوع لأمر البيع' : 'Back to Sales Order' }}
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales-invoices.print', $salesInvoice) }}" target="_blank" class="px-5 py-2 bg-[#005B9F] text-white rounded-lg font-bold text-sm hover:bg-blue-800 flex items-center gap-2">
                <i class="fas fa-print"></i> {{ $isAr ? 'طباعة الفاتورة' : 'Print Invoice' }}
            </a>
            <form action="{{ route('sales-invoices.send-email', $salesInvoice) }}" method="POST" class="inline-block">
                @csrf
                <button type="submit" class="px-4 py-2 border border-[#005B9F] text-[#005B9F] rounded-lg font-bold text-sm hover:bg-blue-50 flex items-center gap-2">
                    <i class="fas fa-envelope"></i> {{ $isAr ? 'إرسال عبر الإيميل' : 'Send via Email' }}
                </button>
            </form>
            @if($due > 0)
            <a href="{{ route('client-receipts.create', ['sales_invoice_id' => $salesInvoice->id]) }}" class="px-5 py-2 bg-[#008A3B] text-white rounded-lg font-bold text-sm hover:bg-[#007030] flex items-center gap-2">
                <i class="fas fa-hand-holding-usd"></i> {{ $isAr ? 'تسجيل سند قبض' : 'Record Receipt' }}
            </a>
            @endif
            <a href="{{ route('sales-invoices.index') }}" class="text-sm text-gray-500 hover:text-[#008A3B]">{{ $isAr ? 'كل فواتير البيع' : 'All sales invoices' }}</a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="h-1.5 bg-gradient-to-r from-[#008A3B] to-[#005B9F]"></div>
        <div class="px-8 py-5 flex items-center justify-between border-b border-gray-100">
            <div>
                <p class="text-2xl font-extrabold text-[#008A3B]">{{ $isAr ? 'فاتورة بيع' : 'Sales Invoice' }}</p>
                <p class="font-mono font-bold text-gray-600 mt-1 text-sm" dir="ltr">{{ $salesInvoice->invoice_number }}</p>
            </div>
            <div class="text-{{ $isAr ? 'left' : 'right' }} text-xs text-gray-500 space-y-1">
                <p><span class="text-gray-400">{{ $isAr ? 'أمر البيع:' : 'Sales Order:' }}</span>
                   <a href="{{ route('sales-orders.show', $salesInvoice->salesOrder) }}" class="font-mono font-bold text-[#005B9F] hover:underline">{{ $salesInvoice->salesOrder->so_number }}</a></p>
                <p><span class="text-gray-400">{{ $isAr ? 'العميل:' : 'Client:' }}</span>
                   <span class="font-bold text-gray-800">{{ optional($salesInvoice->client)->displayName($isAr ? 'ar' : 'en') }}</span></p>
                <p><span class="text-gray-400">{{ $isAr ? 'التاريخ:' : 'Date:' }}</span>
                   <span class="font-bold text-gray-800">{{ $salesInvoice->invoice_date->format('Y-m-d') }}</span></p>
                @if($salesInvoice->due_date)
                <p><span class="text-gray-400">{{ $isAr ? 'موعد الاستحقاق:' : 'Due Date:' }}</span>
                   <span class="font-bold {{ $salesInvoice->is_overdue ? 'text-red-600' : 'text-gray-800' }}">{{ $salesInvoice->due_date->format('Y-m-d') }}</span>
                   @if($salesInvoice->is_overdue)
                       <span class="inline-flex items-center gap-1 text-red-600 font-bold"><i class="fas fa-exclamation-triangle"></i> {{ $isAr ? 'متأخر السداد' : 'Overdue' }}</span>
                   @endif
                </p>
                @endif
            </div>
        </div>

        <div class="px-8 py-4">
            <table class="w-full border-collapse text-sm" style="text-align:{{ $isAr ? 'right' : 'left' }}">
                <thead>
                    <tr style="background:#1e293b;color:#fff;">
                        <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'الوصف' : 'Description' }}</th>
                        <th class="px-3 py-2.5 text-center text-[11px] font-bold">{{ $isAr ? 'الكمية' : 'Qty' }}</th>
                        <th class="px-3 py-2.5 text-center text-[11px] font-bold">{{ $isAr ? 'السعر' : 'Price' }}</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold">{{ $isAr ? 'الإجمالي' : 'Total' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesInvoice->items as $line)
                    <tr class="border-b border-gray-100">
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
                    <span class="font-bold text-gray-800" dir="ltr">{{ number_format($salesInvoice->subtotal, 2) }} {{ $cur }}</span>
                </div>
                @php
                    $itemDiscounts = $salesInvoice->total_discount - $salesInvoice->extra_discount;
                @endphp
                @if($itemDiscounts > 0)
                <div class="flex justify-between items-center px-4 py-2.5 border-b border-gray-100 bg-red-50/10">
                    <span class="text-gray-500">{{ $isAr ? 'خصم الأصناف' : 'Item Discounts' }}</span>
                    <span class="font-bold text-red-600" dir="ltr">- {{ number_format($itemDiscounts, 2) }} {{ $cur }}</span>
                </div>
                @endif
                @if($salesInvoice->extra_discount > 0)
                <div class="flex justify-between items-center px-4 py-2.5 border-b border-gray-100 bg-red-50/20">
                    <span class="text-gray-500">{{ $isAr ? 'خصم إضافي' : 'Extra Discount' }}</span>
                    <span class="font-bold text-red-600" dir="ltr">- {{ number_format($salesInvoice->extra_discount, 2) }} {{ $cur }}</span>
                </div>
                @endif
                @if($itemDiscounts > 0 && $salesInvoice->extra_discount > 0)
                <div class="flex justify-between items-center px-4 py-2.5 border-b border-gray-100 bg-red-50/30">
                    <span class="text-gray-500">{{ $isAr ? 'إجمالي الخصومات' : 'Total Discounts' }}</span>
                    <span class="font-bold text-red-600" dir="ltr">- {{ number_format($salesInvoice->total_discount, 2) }} {{ $cur }}</span>
                </div>
                @endif
                @if($salesInvoice->tax_amount > 0)
                <div class="flex justify-between items-center px-4 py-2.5 border-b border-gray-100">
                    <span class="text-gray-500">{{ $isAr ? 'إجمالي الضريبة' : 'Total Tax' }}</span>
                    <span class="font-bold text-gray-800" dir="ltr">+ {{ number_format($salesInvoice->tax_amount, 2) }} {{ $cur }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center px-4 py-3" style="background:#008A3B;">
                    <span class="font-extrabold text-white text-sm">{{ $isAr ? 'الإجمالي النهائي' : 'Grand Total' }}</span>
                    <span class="font-extrabold text-white text-base" dir="ltr">{{ number_format($salesInvoice->grand_total, 2) }} {{ $cur }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 no-print">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-paperclip text-[#005B9F]"></i> {{ $isAr ? 'المرفقات' : 'Attachments' }}
        </h3>
        
        @if(!empty($salesInvoice->attachments) && count($salesInvoice->attachments) > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
            @foreach($salesInvoice->attachments as $attach)
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
        @else
        <p class="text-sm text-gray-400 mb-4">{{ $isAr ? 'لا توجد مرفقات' : 'No attachments' }}</p>
        @endif

        <form action="{{ route('sales-invoices.attachments', $salesInvoice) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-center gap-3 pt-4 border-t border-gray-100">
            @csrf
            <input type="file" name="attachments[]" multiple class="block w-full sm:w-auto text-sm text-slate-500
                file:mr-4 file:py-2 file:px-4
                file:rounded-full file:border-0
                file:text-sm file:font-semibold
                file:bg-[#005B9F]/10 file:text-[#005B9F]
                hover:file:bg-[#005B9F]/20 cursor-pointer border border-gray-200 rounded-full pr-3 py-0.5" required>
            <button type="submit" class="px-5 py-2 bg-[#008A3B] text-white rounded-full font-bold text-sm hover:bg-[#007030] whitespace-nowrap transition-colors w-full sm:w-auto">
                <i class="fas fa-upload {{ $isAr ? 'ml-1' : 'mr-1' }}"></i> {{ $isAr ? 'إضافة مرفقات' : 'Upload' }}
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-hand-holding-usd text-[#008A3B]"></i> {{ $isAr ? 'التحصيل على هذه الفاتورة' : 'Collection on this invoice' }}
        </h3>
        <div class="flex items-center gap-6 mb-4 text-sm">
            <span><span class="text-gray-400">{{ $isAr ? 'المحصّل:' : 'Received:' }}</span> <strong class="text-green-600" dir="ltr">{{ number_format($received, 2) }}</strong></span>
            <span><span class="text-gray-400">{{ $isAr ? 'المتبقي:' : 'Due:' }}</span> <strong class="{{ $due > 0 ? 'text-red-600' : 'text-green-600' }}" dir="ltr">{{ number_format($due, 2) }}</strong></span>
        </div>
        @if($salesInvoice->receipts->isNotEmpty())
        <table class="w-full text-sm">
            <thead><tr class="text-gray-500 text-xs border-b"><th class="py-2">{{ $isAr ? 'السند' : 'Receipt' }}</th><th class="py-2">{{ $isAr ? 'الحساب' : 'Account' }}</th><th class="py-2">{{ $isAr ? 'التاريخ' : 'Date' }}</th><th class="py-2">{{ $isAr ? 'المبلغ' : 'Amount' }}</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($salesInvoice->receipts as $r)
                <tr>
                    <td class="py-2 font-mono">{{ $r->receipt_number }}</td>
                    <td class="py-2">{{ optional($r->wallet)->name ?? '—' }}</td>
                    <td class="py-2" dir="ltr">{{ $r->receipt_date->format('Y-m-d') }}</td>
                    <td class="py-2 font-bold text-green-600" dir="ltr">{{ number_format($r->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-sm text-gray-400">{{ $isAr ? 'لا توجد تحصيلات بعد' : 'No receipts yet' }}</p>
        @endif
    </div>
</div>
@endsection
