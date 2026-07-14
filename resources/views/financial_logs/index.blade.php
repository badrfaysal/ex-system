@extends('layouts.app')
@php
    $isAr = app()->getLocale() === 'ar';
    $typeLabels = [
        'receipt'       => ['ar' => 'سند قبض', 'en' => 'Receipt', 'color' => 'bg-[#008A3B]/10 text-[#008A3B]', 'icon' => 'fa-arrow-down text-green-500'],
        'revenue'       => ['ar' => 'إيراد مباشر', 'en' => 'Revenue', 'color' => 'bg-[#008A3B]/10 text-[#008A3B]', 'icon' => 'fa-arrow-down text-green-500'],
        'expense'       => ['ar' => 'مصروف مباشر', 'en' => 'Expense', 'color' => 'bg-red-50 text-red-600', 'icon' => 'fa-arrow-up text-red-500'],
        'vendor_payment'=> ['ar' => 'سند دفع', 'en' => 'Vendor Payment', 'color' => 'bg-red-50 text-red-600', 'icon' => 'fa-arrow-up text-red-500'],
        'transfer_out'  => ['ar' => 'تحويل صادر', 'en' => 'Transfer Out', 'color' => 'bg-amber-50 text-amber-700', 'icon' => 'fa-arrow-up text-amber-500'],
        'transfer_in'   => ['ar' => 'تحويل وارد', 'en' => 'Transfer In', 'color' => 'bg-blue-50 text-[#005B9F]', 'icon' => 'fa-arrow-down text-[#005B9F]'],
    ];
@endphp
@section('header_title', $isAr ? 'سجل الماليات الشامل' : 'Unified Financial Log')

@section('content')
<div class="max-w-7xl mx-auto animate-fade-in">
    
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
                <i class="fas fa-file-invoice-dollar text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'سجل الماليات' : 'Financial Log' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'جميع حركات الوارد والمنصرف من كل الحسابات البنكية والصناديق المالية' : 'All incoming and outgoing transactions across all bank accounts & cash boxes' }}</p>
            </div>
        </div>
    </div>

    <!-- الإحصائيات -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-green-50 flex items-center justify-center text-green-600">
                <i class="fas fa-arrow-down text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-bold mb-1">{{ $isAr ? 'إجمالي الوارد (خلال الفترة)' : 'Total In (Period)' }}</p>
                <p class="text-2xl font-extrabold text-[#008A3B]" dir="ltr">{{ number_format($totalIn, 2) }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-red-600">
                <i class="fas fa-arrow-up text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-bold mb-1">{{ $isAr ? 'إجمالي المنصرف (خلال الفترة)' : 'Total Out (Period)' }}</p>
                <p class="text-2xl font-extrabold text-red-600" dir="ltr">{{ number_format($totalOut, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- الفلاتر -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
        <form method="GET" action="{{ route('financial-logs.index') }}" class="flex flex-col sm:flex-row gap-4 items-end flex-wrap">
            <div class="flex-1 w-full min-w-[200px]">
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'بحث' : 'Search' }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'رقم المستند أو التفاصيل' : 'Document no. or detail' }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'من تاريخ' : 'Date From' }}</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'إلى تاريخ' : 'Date To' }}</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'نوع الحركة' : 'Transaction Type' }}</label>
                <select name="type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-bold">
                    <option value="" {{ $type == '' ? 'selected' : '' }}>{{ $isAr ? 'الكل' : 'All' }}</option>
                    <option value="transfer" {{ $type == 'transfer' ? 'selected' : '' }}>{{ $isAr ? 'تحويلات فقط' : 'Transfers Only' }}</option>
                    <option value="revenue" {{ $type == 'revenue' ? 'selected' : '' }}>{{ $isAr ? 'إيراد فقط' : 'Revenue Only' }}</option>
                    <option value="expense" {{ $type == 'expense' ? 'selected' : '' }}>{{ $isAr ? 'مصروف فقط' : 'Expense Only' }}</option>
                    <option value="receipt" {{ $type == 'receipt' ? 'selected' : '' }}>{{ $isAr ? 'سندات قبض فقط' : 'Receipts Only' }}</option>
                    <option value="vendor_payment" {{ $type == 'vendor_payment' ? 'selected' : '' }}>{{ $isAr ? 'سندات دفع فقط' : 'Vendor Payments Only' }}</option>
                </select>
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-gray-600 mb-1">{{ $isAr ? 'ترتيب حسب' : 'Sort By' }}</label>
                <select name="sort" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-bold">
                    <option value="date_desc" {{ $sort == 'date_desc' ? 'selected' : '' }}>{{ $isAr ? 'التاريخ (الأحدث أولاً)' : 'Date (Newest)' }}</option>
                    <option value="date_asc" {{ $sort == 'date_asc' ? 'selected' : '' }}>{{ $isAr ? 'التاريخ (الأقدم أولاً)' : 'Date (Oldest)' }}</option>
                    <option value="amount_desc" {{ $sort == 'amount_desc' ? 'selected' : '' }}>{{ $isAr ? 'المبلغ (الأكبر)' : 'Amount (Highest)' }}</option>
                    <option value="amount_asc" {{ $sort == 'amount_asc' ? 'selected' : '' }}>{{ $isAr ? 'المبلغ (الأصغر)' : 'Amount (Lowest)' }}</option>
                </select>
            </div>
            <div>
                <button type="submit" class="px-6 py-2.5 bg-[#005B9F] text-white rounded-lg font-bold hover:bg-blue-800 transition-colors w-full sm:w-auto h-[42px] flex items-center justify-center gap-2">
                    <i class="fas fa-filter"></i> {{ $isAr ? 'تطبيق الفلتر' : 'Apply Filter' }}
                </button>
            </div>
            @if(request()->anyFilled(['search', 'date_from', 'date_to', 'type', 'sort']))
                <div>
                    <a href="{{ route('financial-logs.index') }}" class="px-4 py-2.5 bg-gray-100 text-gray-600 rounded-lg font-bold hover:bg-gray-200 transition-colors w-full sm:w-auto h-[42px] flex items-center justify-center">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            @endif
        </form>
    </div>

    <!-- الجدول -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm" style="text-align:{{ $isAr ? 'right' : 'left' }}">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 font-bold">
                        <th class="p-4 whitespace-nowrap">{{ $isAr ? 'التاريخ والوقت' : 'Date & Time' }}</th>
                        <th class="p-4 whitespace-nowrap">{{ $isAr ? 'نوع الحركة' : 'Type' }}</th>
                        <th class="p-4 whitespace-nowrap">{{ $isAr ? 'الرقم / المرجع' : 'Ref / Number' }}</th>
                        <th class="p-4">{{ $isAr ? 'الحساب' : 'Account' }}</th>
                        <th class="p-4">{{ $isAr ? 'التفاصيل / الجهة' : 'Details' }}</th>
                        <th class="p-4 whitespace-nowrap">{{ $isAr ? 'المبلغ' : 'Amount' }}</th>
                        <th class="p-4 whitespace-nowrap">{{ $isAr ? 'المستخدم' : 'User' }}</th>
                        <th class="p-4 whitespace-nowrap text-center">{{ $isAr ? 'إجراء' : 'Action' }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($logs as $log)
                        @php
                            $t = $typeLabels[$log->type] ?? $typeLabels['revenue'];
                            $isReversed = !is_null($log->reversed_at);
                            $isLocked = \App\Models\PeriodLock::isDateLocked($log->transaction_date);
                        @endphp
                        <tr class="hover:bg-[#005B9F]/5 transition-colors cursor-pointer {{ $isReversed ? 'opacity-60' : '' }}"
                            data-ref="{{ $log->ref }}"
                            data-date="{{ \Carbon\Carbon::parse($log->transaction_date)->format('Y-m-d') }}"
                            data-time="{{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}"
                            data-typeicon="{{ $t['icon'] }}"
                            data-typecolor="{{ $t['color'] }}"
                            data-typename="{{ $isAr ? $t['ar'] : $t['en'] }}"
                            data-wallet="{{ $log->wallet_name }}"
                            data-details="{{ $log->detail ?? '—' }}"
                            data-amount="{{ number_format($log->amount, 2) }}"
                            data-amountdir="{{ $log->amount >= 0 ? 1 : -1 }}"
                            data-user="{{ $log->user_name }}"
                            data-reversed="{{ $isReversed ? '1' : '0' }}"
                            data-reversereason="{{ $log->reversal_reason ?? '' }}"
                            onclick="showDetailsModal(this)">
                            <td class="p-4 text-gray-500" dir="ltr">
                                <div class="font-bold text-gray-800">{{ \Carbon\Carbon::parse($log->transaction_date)->format('Y-m-d') }}</div>
                                <div class="text-[11px]">{{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}</div>
                            </td>
                            <td class="p-4">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold {{ $t['color'] }}">
                                    <i class="fas {{ $t['icon'] }}"></i>
                                    {{ $isAr ? $t['ar'] : $t['en'] }}
                                </div>
                                @if($isReversed)
                                    <div class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-gray-200 text-gray-600">
                                        <i class="fas fa-rotate-left"></i> {{ $isAr ? 'معكوسة' : 'Reversed' }}
                                    </div>
                                @endif
                            </td>
                            <td class="p-4 font-mono text-xs text-gray-500 {{ $isReversed ? 'line-through' : '' }}">{{ $log->ref }}</td>
                            <td class="p-4 font-bold text-gray-800">{{ $log->wallet_name }}</td>
                            <td class="p-4 text-gray-600 max-w-xs truncate" title="{{ $log->detail ?? '—' }}">{{ $log->detail ?? '—' }}</td>
                            <td class="p-4 font-extrabold {{ $isReversed ? 'line-through text-gray-400' : ($log->amount >= 0 ? 'text-[#008A3B]' : 'text-red-600') }}" dir="ltr">
                                {{ $log->amount >= 0 ? '+' : '' }}{{ number_format($log->amount, 2) }}
                            </td>
                            <td class="p-4 text-gray-500 text-xs">{{ $log->user_name }}</td>
                            <td class="p-4 text-center">
                                @if($isReversed)
                                    <span class="text-[11px] text-gray-400">—</span>
                                @elseif($isLocked)
                                    <span class="text-[11px] text-red-500 font-bold bg-red-50 px-2 py-1 rounded border border-red-100" title="{{ $isAr ? 'الفترة مغلقة' : 'Period Locked' }}">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                @else
                                    <button type="button" onclick="event.stopPropagation(); openReverseModal('{{ $log->source_type }}', {{ $log->id }}, {{ Js::from($log->ref) }})"
                                        class="text-[11px] px-2.5 py-1 rounded-md border border-amber-300 text-amber-700 hover:bg-amber-50 font-bold transition-colors whitespace-nowrap">
                                        <i class="fas fa-rotate-left"></i> {{ $isAr ? 'عكس' : 'Reverse' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-12 text-center text-gray-400">
                                <i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i>
                                <p class="font-bold">{{ $isAr ? 'لا توجد حركات مالية' : 'No financial logs found' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100 bg-gray-50/50">
            {{ $logs->links('pagination::tailwind') }}
        </div>
    </div>
</div>

<!-- Modal التفاصيل -->
<div id="detailsModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                <i class="fas fa-search-dollar text-[#005B9F]"></i> {{ $isAr ? 'تفاصيل الحركة المالية' : 'Transaction Details' }}
            </h3>
            <button onclick="document.getElementById('detailsModal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-100">
                <div>
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'نوع الحركة' : 'Type' }}</p>
                    <div id="m-type" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-bold"></div>
                </div>
                <div class="text-{{ $isAr ? 'left' : 'right' }}">
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'المبلغ' : 'Amount' }}</p>
                    <p id="m-amount" class="text-3xl font-extrabold" dir="ltr"></p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'الرقم / المرجع' : 'Ref / Number' }}</p>
                    <p id="m-ref" class="text-sm font-mono text-gray-800 font-bold"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'التاريخ والوقت' : 'Date & Time' }}</p>
                    <p class="text-sm font-bold text-gray-800" dir="ltr"><span id="m-date"></span> <span id="m-time" class="text-gray-500 font-normal ml-1"></span></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'الحساب' : 'Account' }}</p>
                    <p id="m-wallet" class="text-sm font-bold text-[#005B9F]"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-bold mb-1">{{ $isAr ? 'المستخدم' : 'User' }}</p>
                    <p id="m-user" class="text-sm font-bold text-gray-800"></p>
                </div>
                <div class="col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <p class="text-xs text-gray-400 font-bold mb-1"><i class="fas fa-align-right ml-1"></i> {{ $isAr ? 'التفاصيل / الجهة' : 'Details' }}</p>
                    <p id="m-details" class="text-sm font-bold text-gray-800"></p>
                </div>
                <div id="m-reversed-box" class="hidden col-span-2 bg-gray-100 p-4 rounded-xl border border-gray-200">
                    <p class="text-xs text-gray-500 font-bold mb-1"><i class="fas fa-rotate-left ml-1"></i> {{ $isAr ? 'سبب العكس' : 'Reversal Reason' }}</p>
                    <p id="m-reverse-reason" class="text-sm font-bold text-gray-700"></p>
                </div>
            </div>

            <div class="mt-8">
                <button onclick="document.getElementById('detailsModal').classList.add('hidden')" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-3 rounded-xl transition-colors">
                    {{ $isAr ? 'إغلاق' : 'Close' }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal عكس العملية -->
<div id="reverseModal" class="hidden fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-amber-50">
            <h3 class="font-bold text-amber-800 text-lg flex items-center gap-2">
                <i class="fas fa-rotate-left"></i> {{ $isAr ? 'عكس العملية' : 'Reverse Operation' }}
            </h3>
            <button type="button" onclick="closeReverseModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="reverseForm" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-600">
                    {{ $isAr ? 'هتُعكس العملية' : 'You are about to reverse operation' }}
                    <span id="reverseRefLabel" class="font-mono font-bold text-gray-900"></span>
                    {{ $isAr ? '— هيتلغى أثرها بالكامل من رصيد الحساب، وهتفضل ظاهرة في السجل معلّم عليها إنها معكوسة.' : '— its effect will be fully removed from the account balance, and it will remain visible in the log marked as reversed.' }}
                </p>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">{{ $isAr ? 'سبب العكس' : 'Reversal Reason' }} <span class="text-red-500">*</span></label>
                    <textarea name="reversal_reason" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-amber-500" placeholder="{{ $isAr ? 'مثال: خطأ في المبلغ / الحساب' : 'e.g. wrong amount / account' }}"></textarea>
                </div>
            </div>
            <div class="px-6 pb-6 flex gap-2">
                <button type="submit" class="flex-1 bg-amber-600 text-white font-bold py-2.5 rounded-lg hover:bg-amber-700 transition-colors">
                    {{ $isAr ? 'تأكيد العكس' : 'Confirm Reversal' }}
                </button>
                <button type="button" onclick="closeReverseModal()" class="px-6 bg-gray-100 text-gray-700 font-bold py-2.5 rounded-lg hover:bg-gray-200 transition-colors">
                    {{ $isAr ? 'إلغاء' : 'Cancel' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const REVERSE_URL_BASE = @json(url('financial-logs'));

    function openReverseModal(sourceType, id, ref) {
        document.getElementById('reverseForm').action = `${REVERSE_URL_BASE}/${sourceType}/${id}/reverse`;
        document.getElementById('reverseRefLabel').textContent = ref;
        document.getElementById('reverseModal').classList.remove('hidden');
    }

    function closeReverseModal() {
        document.getElementById('reverseModal').classList.add('hidden');
    }

    function showDetailsModal(row) {
        document.getElementById('m-ref').textContent = row.dataset.ref;
        document.getElementById('m-date').textContent = row.dataset.date;
        document.getElementById('m-time').textContent = row.dataset.time;
        document.getElementById('m-wallet').textContent = row.dataset.wallet;
        document.getElementById('m-details').textContent = row.dataset.details;
        document.getElementById('m-user').textContent = row.dataset.user;

        const reversedBox = document.getElementById('m-reversed-box');
        if (row.dataset.reversed === '1') {
            reversedBox.classList.remove('hidden');
            document.getElementById('m-reverse-reason').textContent = row.dataset.reversereason || '—';
        } else {
            reversedBox.classList.add('hidden');
        }

        let typeEl = document.getElementById('m-type');
        typeEl.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-bold ' + row.dataset.typecolor;
        typeEl.innerHTML = '<i class="fas ' + row.dataset.typeicon + '"></i> ' + row.dataset.typename;

        let amountEl = document.getElementById('m-amount');
        let isPositive = parseInt(row.dataset.amountdir) >= 0;
        amountEl.textContent = (isPositive ? '+' : '') + row.dataset.amount;
        amountEl.className = 'text-3xl font-extrabold ' + (isPositive ? 'text-[#008A3B]' : 'text-red-600');

        document.getElementById('detailsModal').classList.remove('hidden');
    }
</script>
@endsection
