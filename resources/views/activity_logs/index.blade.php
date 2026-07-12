@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $actionLabels = [
        'created'  => ['ar' => 'أنشأ', 'en' => 'Created', 'color' => 'bg-green-50 text-green-700'],
        'updated'  => ['ar' => 'عدّل', 'en' => 'Updated', 'color' => 'bg-blue-50 text-[#005B9F]'],
        'deleted'  => ['ar' => 'حذف', 'en' => 'Deleted', 'color' => 'bg-red-50 text-red-600'],
        'reversed' => ['ar' => 'عكس عملية', 'en' => 'Reversed', 'color' => 'bg-amber-50 text-amber-700'],
    ];
    $subjectLabels = [
        'Quotation' => ['ar' => 'عرض سعر', 'en' => 'Quotation'],
        'SalesOrder' => ['ar' => 'أمر بيع', 'en' => 'Sales Order'],
        'SalesInvoice' => ['ar' => 'فاتورة بيع', 'en' => 'Sales Invoice'],
        'PurchaseInvoice' => ['ar' => 'فاتورة شراء', 'en' => 'Purchase Invoice'],
        'Expense' => ['ar' => 'مصروف', 'en' => 'Expense'],
        'VendorPayment' => ['ar' => 'سند دفع', 'en' => 'Vendor Payment'],
        'ClientReceipt' => ['ar' => 'سند قبض', 'en' => 'Client Receipt'],
        'WalletTransfer' => ['ar' => 'تحويل حساب', 'en' => 'Account Transfer'],
        'Revenue' => ['ar' => 'إيراد مباشر', 'en' => 'Revenue'],
        'Client' => ['ar' => 'عميل', 'en' => 'Client'],
        'Vendor' => ['ar' => 'مورد', 'en' => 'Vendor'],
        'Item' => ['ar' => 'صنف', 'en' => 'Item'],
    ];
@endphp
@section('header_title', $isAr ? 'سجل العمليات' : 'Activity Log')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-gray-700/10 flex items-center justify-center text-gray-700">
                <i class="fas fa-history text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'سجل العمليات' : 'Activity Log' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'كل حركة عملها أي مستخدم في النظام' : 'Every action performed by any user' }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('activity-logs.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="w-full sm:w-56">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'المستخدم' : 'User' }}</label>
                <select name="user_id" data-search class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:border-[#005B9F]">
                    <option value="">{{ $isAr ? '— كل المستخدمين —' : '— All users —' }}</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'من تاريخ' : 'From' }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#005B9F]">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'إلى تاريخ' : 'To' }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#005B9F]">
            </div>
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'بحث' : 'Search' }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'رقم المرجع' : 'Reference' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#005B9F] bg-gray-50">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-6 py-2 bg-[#005B9F] text-white rounded-lg text-sm font-bold hover:bg-blue-800 transition-colors flex items-center gap-2">
                    <i class="fas fa-filter"></i> {{ $isAr ? 'تطبيق' : 'Apply' }}
                </button>
                @if(request()->anyFilled(['user_id', 'date_from', 'date_to', 'search']))
                <a href="{{ route('activity-logs.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">{{ $isAr ? 'مسح' : 'Clear' }}</a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ $isAr ? 'التاريخ والوقت' : 'Date & Time' }}</th>
                        <th class="p-4">{{ $isAr ? 'المستخدم' : 'User' }}</th>
                        <th class="p-4">{{ $isAr ? 'العملية' : 'Action' }}</th>
                        <th class="p-4">{{ $isAr ? 'النوع' : 'Type' }}</th>
                        <th class="p-4">{{ $isAr ? 'المرجع' : 'Reference' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($logs as $log)
                        @php
                            $act = $actionLabels[$log->action] ?? ['ar' => $log->action, 'en' => $log->action, 'color' => 'bg-gray-50 text-gray-600'];
                            $subj = $subjectLabels[$log->subject_type] ?? ['ar' => $log->subject_type, 'en' => $log->subject_type];
                            $url = '#';
                            switch($log->subject_type) {
                                case 'Quotation': $url = route('quotations.show', $log->subject_id); break;
                                case 'SalesOrder': $url = route('sales-orders.show', $log->subject_id); break;
                                case 'SalesInvoice': $url = route('sales-invoices.show', $log->subject_id); break;
                                case 'PurchaseInvoice': $url = route('purchase-invoices.show', $log->subject_id); break;
                                case 'Client': $url = route('clients.edit', $log->subject_id); break;
                                case 'Vendor': $url = route('vendors.edit', $log->subject_id); break;
                                case 'Item': $url = route('items.edit', $log->subject_id); break;
                                case 'VendorPayment': $url = route('vendor-payments.edit', $log->subject_id); break;
                                case 'Expense': $url = route('expenses.index'); break;
                                case 'ClientReceipt': $url = route('client-receipts.index'); break;
                                case 'WalletTransfer': $url = route('wallets.index'); break;
                                case 'Revenue': $url = route('wallets.index'); break;
                            }
                        @endphp
                        <tr class="hover:bg-[#005B9F]/5 transition-colors cursor-pointer"
                            data-date="{{ $log->created_at->format('Y-m-d') }}"
                            data-time="{{ $log->created_at->format('H:i') }}"
                            data-user="{{ optional($log->user)->name ?? ($isAr ? '—' : '—') }}"
                            data-actioncolor="{{ $act['color'] }}"
                            data-actionname="{{ $isAr ? $act['ar'] : $act['en'] }}"
                            data-subjectname="{{ $isAr ? $subj['ar'] : $subj['en'] }}"
                            data-ref="{{ $log->subject_label ?? '#' . $log->subject_id }}"
                            data-details="{{ $log->description ?? '—' }}"
                            data-url="{{ $url }}"
                            onclick="showActivityModal(this)">
                            <td class="p-4 text-gray-500" dir="ltr">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-4 font-bold text-gray-800">{{ optional($log->user)->name ?? ($isAr ? '—' : '—') }}</td>
                            <td class="p-4"><span class="px-2.5 py-1 rounded-md text-xs font-bold {{ $act['color'] }}">{{ $isAr ? $act['ar'] : $act['en'] }}</span></td>
                            <td class="p-4 text-gray-700">{{ $isAr ? $subj['ar'] : $subj['en'] }}</td>
                            <td class="p-4 font-mono text-[#005B9F]" dir="ltr">
                                @if($url !== '#')
                                    <a href="{{ $url }}" onclick="event.stopPropagation()" class="hover:underline hover:text-blue-700">{{ $log->subject_label ?? '#' . $log->subject_id }} <i class="fas fa-external-link-alt text-[10px] ml-1"></i></a>
                                @else
                                    {{ $log->subject_label ?? '#' . $log->subject_id }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد عمليات مسجّلة' : 'No activity recorded yet' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages()) <div class="p-4 border-t border-gray-100 bg-white">{{ $logs->links() }}</div> @endif
    </div>
</div>

<!-- Modal تفاصيل العملية -->
<div id="activityModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                <i class="fas fa-history text-[#005B9F]"></i> {{ $isAr ? 'تفاصيل العملية' : 'Activity Details' }}
            </h3>
            <button onclick="document.getElementById('activityModal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-100">
                <div>
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'العملية' : 'Action' }}</p>
                    <div id="m-act" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-bold"></div>
                </div>
                <div class="text-{{ $isAr ? 'left' : 'right' }}">
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'المستخدم' : 'User' }}</p>
                    <p id="m-user" class="text-xl font-extrabold text-gray-800"></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'الرقم / المرجع' : 'Ref / Number' }}</p>
                    <div id="m-ref-container"></div>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'التاريخ والوقت' : 'Date & Time' }}</p>
                    <p class="text-sm font-bold text-gray-800" dir="ltr"><span id="m-date"></span> <span id="m-time" class="text-gray-500 font-normal ml-1"></span></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'النوع' : 'Type' }}</p>
                    <p id="m-subj" class="text-sm font-bold text-gray-800"></p>
                </div>
                <div class="col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <p class="text-xs text-gray-400 font-bold mb-1"><i class="fas fa-align-right ml-1"></i> {{ $isAr ? 'التفاصيل / الوصف' : 'Description' }}</p>
                    <p id="m-details" class="text-sm font-bold text-gray-800 leading-relaxed"></p>
                </div>
            </div>
            
            <div class="mt-8">
                <button onclick="document.getElementById('activityModal').classList.add('hidden')" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-3 rounded-xl transition-colors">
                    {{ $isAr ? 'إغلاق' : 'Close' }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function showActivityModal(row) {
        let refHtml = row.dataset.ref;
        if(row.dataset.url && row.dataset.url !== '#') {
            refHtml = `<a href="${row.dataset.url}" class="text-[#005B9F] hover:underline font-mono font-bold text-sm">${row.dataset.ref} <i class="fas fa-external-link-alt text-xs ml-1"></i></a>`;
        } else {
            refHtml = `<p class="text-sm font-mono text-gray-800 font-bold">${row.dataset.ref}</p>`;
        }
        document.getElementById('m-ref-container').innerHTML = refHtml;
        
        document.getElementById('m-date').textContent = row.dataset.date;
        document.getElementById('m-time').textContent = row.dataset.time;
        document.getElementById('m-subj').textContent = row.dataset.subjectname;
        document.getElementById('m-details').textContent = row.dataset.details;
        document.getElementById('m-user').textContent = row.dataset.user;
        
        let actEl = document.getElementById('m-act');
        actEl.className = 'inline-flex items-center px-3 py-1.5 rounded-md text-sm font-bold ' + row.dataset.actioncolor;
        actEl.textContent = row.dataset.actionname;

        document.getElementById('activityModal').classList.remove('hidden');
    }
</script>
@endsection
