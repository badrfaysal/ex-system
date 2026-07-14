@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'المصروفات' : 'Expenses')

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
                <i class="fas fa-receipt text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'المصروفات' : 'Expenses' }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'مصروفات كل مركز تكلفة (عرض سعر)' : 'Expenses per cost center (quotation)' }}</p>
            </div>
        </div>
        <a href="{{ route('expenses.create') }}" class="px-6 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030] transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> {{ $isAr ? 'إضافة مصروف' : 'Add Expense' }}
        </a>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('expenses.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ $isAr ? 'بحث' : 'Search' }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'رقم المصروف / عرض السعر / الوصف' : 'Expense no. / quotation / description' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <button type="submit" class="px-6 py-2 bg-[#005B9F] text-white rounded-lg text-sm font-bold hover:bg-blue-800 transition-colors flex items-center gap-2">
                <i class="fas fa-filter"></i> {{ $isAr ? 'تطبيق' : 'Apply' }}
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ $isAr ? 'رقم المصروف' : 'No.' }}</th>
                        <th class="p-4">{{ $isAr ? 'مركز التكلفة' : 'Cost Center' }}</th>
                        <th class="p-4">{{ $isAr ? 'النوع' : 'Category' }}</th>
                        <th class="p-4">{{ $isAr ? 'المبلغ' : 'Amount' }}</th>
                        <th class="p-4">{{ $isAr ? 'التاريخ' : 'Date' }}</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($expenses as $expense)
                        @php
                            $modalData = [
                                'number' => $expense->expense_number,
                                'cost_center' => optional($expense->quotation)->cost_center_name ?: optional($expense->quotation)->quote_number,
                                'quote_number' => optional($expense->quotation)->quote_number,
                                'category' => $expense->category,
                                'description' => $expense->description,
                                'amount' => number_format($expense->amount, 2),
                                'currency' => $expense->currency,
                                'date' => $expense->expense_date->format('Y-m-d'),
                                'notes' => $expense->notes,
                            ];
                        @endphp
                        <tr onclick="openExpenseModal({{ json_encode($modalData) }})" class="hover:bg-blue-50/40 cursor-pointer transition-colors">
                            <td class="p-4 font-mono font-bold text-gray-800" dir="ltr">{{ $expense->expense_number }}</td>
                            <td class="p-4 font-mono text-[#005B9F]" dir="ltr">{{ optional($expense->quotation)->quote_number }}</td>
                            <td class="p-4 text-gray-700">{{ $expense->category }}</td>
                            <td class="p-4 font-bold text-gray-900" dir="ltr">{{ number_format($expense->amount, 2) }} <span class="text-xs text-gray-400">{{ $expense->currency }}</span></td>
                            <td class="p-4 text-gray-500" dir="ltr">{{ $expense->expense_date->format('Y-m-d') }}</td>
                            <td class="p-4 text-left">
                                @if(\App\Models\PeriodLock::isDateLocked($expense->expense_date))
                                    <span class="text-red-400 text-xs px-2 py-1 bg-red-50 rounded" title="{{ $isAr ? 'مغلق مالياً' : 'Locked' }}"><i class="fas fa-lock"></i></span>
                                @else
                                    <a href="{{ route('expenses.edit', $expense) }}" onclick="event.stopPropagation()" class="text-gray-400 hover:text-[#008A3B]"><i class="fas fa-pen"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد مصروفات' : 'No expenses yet' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages()) <div class="p-4 border-t border-gray-100 bg-white">{{ $expenses->links() }}</div> @endif
    </div>
</div>

{{-- ============ Modal تفاصيل المصروف ============ --}}
<div id="expenseModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" role="dialog">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <div class="bg-[#1e293b] text-white px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center">
                    <i class="fas fa-receipt text-sm"></i>
                </div>
                <div>
                    <p id="emNumber" class="font-mono font-bold text-blue-300 text-sm leading-none"></p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $isAr ? 'تفاصيل المصروف' : 'Expense Details' }}</p>
                </div>
            </div>
            <button onclick="closeExpenseModal()" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-white/10 text-gray-400 hover:text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 space-y-3 text-sm">
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="text-[10px] text-gray-400 mb-1">{{ $isAr ? 'مركز التكلفة' : 'Cost Center' }}</p>
                    <p id="emCostCenter" class="font-bold text-gray-800"></p>
                </div>
                <div class="bg-gray-50 rounded-xl p-3">
                    <p class="text-[10px] text-gray-400 mb-1">{{ $isAr ? 'النوع' : 'Category' }}</p>
                    <p id="emCategory" class="font-bold text-gray-800"></p>
                </div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-[10px] text-gray-400 mb-1">{{ $isAr ? 'الوصف' : 'Description' }}</p>
                <p id="emDescription" class="font-bold text-gray-900 leading-snug"></p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-red-50 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-red-500 mb-1">{{ $isAr ? 'المبلغ' : 'Amount' }}</p>
                    <p id="emAmount" class="font-extrabold text-red-700 text-lg" dir="ltr"></p>
                </div>
                <div class="bg-gray-50 rounded-xl p-3 text-center">
                    <p class="text-[10px] text-gray-400 mb-1">{{ $isAr ? 'التاريخ' : 'Date' }}</p>
                    <p id="emDate" class="font-bold text-gray-800" dir="ltr"></p>
                </div>
            </div>
            <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                <p class="text-[10px] text-amber-600 mb-1 font-bold uppercase">{{ $isAr ? 'ملاحظات' : 'Notes' }}</p>
                <p id="emNotes" class="text-amber-800 leading-relaxed whitespace-pre-line"></p>
            </div>
        </div>
    </div>
</div>

<script>
    function openExpenseModal(d) {
        document.getElementById('emNumber').textContent = d.number;
        document.getElementById('emCostCenter').textContent = d.cost_center || d.quote_number || '—';
        document.getElementById('emCategory').textContent = d.category || '—';
        document.getElementById('emDescription').textContent = d.description || @json($isAr ? 'لا يوجد وصف' : 'No description');
        document.getElementById('emAmount').textContent = d.amount + ' ' + d.currency;
        document.getElementById('emDate').textContent = d.date;
        document.getElementById('emNotes').textContent = d.notes || @json($isAr ? 'لا توجد ملاحظات' : 'No notes');

        const modal = document.getElementById('expenseModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeExpenseModal() {
        const modal = document.getElementById('expenseModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeExpenseModal(); });
</script>
@endsection
