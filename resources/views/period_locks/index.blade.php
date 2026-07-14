@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'إغلاق الفترات المحاسبية' : 'Period Locks')

@section('content')
<div class="container mx-auto px-4 max-w-5xl animate-fade-in relative">

    <div class="mb-6 flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-pink-500/10 flex items-center justify-center text-pink-600">
            <i class="fas fa-lock text-2xl"></i>
        </div>
        <div>
            <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'إغلاق الفترات المحاسبية' : 'Period Locks' }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $isAr ? 'أي فترة تقفلها هنا يتم منع أي إنشاء أو تعديل أو حذف أو عكس لأي عملية بتاريخ واقع بداخلها' : 'Any period you close here blocks creating, editing, deleting, or reversing any operation dated within it' }}</p>
        </div>
    </div>

    {{-- إنشاء فترة جديدة --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <p class="font-bold text-gray-800 text-sm mb-4 flex items-center gap-2"><i class="fas fa-plus-circle text-pink-500"></i> {{ $isAr ? 'إضافة فترة مقفولة جديدة' : 'Add a New Locked Period' }}</p>
        <form action="{{ route('period-locks.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'اسم الفترة (اختياري)' : 'Label (optional)' }}</label>
                <input type="text" name="label" value="{{ old('label') }}" placeholder="{{ $isAr ? 'مثال: الربع الأول 2026' : 'e.g. Q1 2026' }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pink-500">
                @error('label') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'من تاريخ' : 'Start Date' }} <span class="text-red-500">*</span></label>
                <input type="date" name="start_date" required value="{{ old('start_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pink-500">
                @error('start_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $isAr ? 'إلى تاريخ' : 'End Date' }} <span class="text-red-500">*</span></label>
                <input type="date" name="end_date" required value="{{ old('end_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-pink-500">
                @error('end_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-4">
                <button type="submit" class="px-7 py-2.5 bg-pink-600 hover:bg-pink-700 text-white rounded-lg font-bold text-sm flex items-center gap-2 shadow-sm">
                    <i class="fas fa-lock"></i> {{ $isAr ? 'إنشاء وإغلاق الفترة' : 'Create & Close Period' }}
                </button>
            </div>
        </form>
    </div>

    {{-- قائمة الفترات --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 font-bold text-gray-800 text-sm">
            {{ $isAr ? 'الفترات المسجّلة' : 'Registered Periods' }}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm" style="text-align:{{ $isAr ? 'right' : 'left' }}">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-[11px] font-bold">
                        <th class="p-4">{{ $isAr ? 'الفترة' : 'Period' }}</th>
                        <th class="p-4">{{ $isAr ? 'من' : 'From' }}</th>
                        <th class="p-4">{{ $isAr ? 'إلى' : 'To' }}</th>
                        <th class="p-4">{{ $isAr ? 'أنشأها' : 'Created By' }}</th>
                        <th class="p-4">{{ $isAr ? 'الحالة' : 'Status' }}</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($locks as $lock)
                        <tr class="hover:bg-gray-50/60">
                            <td class="p-4 font-semibold text-gray-800">{{ $lock->label ?: '—' }}</td>
                            <td class="p-4 text-gray-600" dir="ltr">{{ $lock->start_date->format('Y-m-d') }}</td>
                            <td class="p-4 text-gray-600" dir="ltr">{{ $lock->end_date->format('Y-m-d') }}</td>
                            <td class="p-4 text-gray-400 text-xs">{{ optional($lock->creator)->name ?? '—' }}</td>
                            <td class="p-4">
                                @if($lock->is_active)
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-red-600 bg-red-50 rounded-full px-3 py-1"><i class="fas fa-lock"></i> {{ $isAr ? 'مقفولة' : 'Closed' }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-green-600 bg-green-50 rounded-full px-3 py-1"><i class="fas fa-lock-open"></i> {{ $isAr ? 'مفتوحة' : 'Open' }}</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-end gap-2">
                                    @if($lock->is_active)
                                        <button type="button" onclick="openUnlockModal({{ $lock->id }})" class="px-4 py-1.5 border border-green-300 text-green-700 rounded-lg text-xs font-bold hover:bg-green-50 flex items-center gap-1.5">
                                            <i class="fas fa-lock-open"></i> {{ $isAr ? 'فتح' : 'Open' }}
                                        </button>
                                    @else
                                        <form action="{{ route('period-locks.toggle', $lock) }}" method="POST" class="m-0">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-4 py-1.5 border border-red-300 text-red-700 rounded-lg text-xs font-bold hover:bg-red-50 flex items-center gap-1.5">
                                                <i class="fas fa-lock"></i> {{ $isAr ? 'إغلاق' : 'Close' }}
                                            </button>
                                        </form>
                                    @endif

                                    <button type="button" onclick="openHistoryModal({{ $lock->id }})" title="{{ $isAr ? 'سجل الفترة' : 'History' }}" class="w-8 h-8 flex items-center justify-center rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">{{ $isAr ? 'لا توجد فترات مقفولة بعد' : 'No period locks yet' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Unlock Modal --}}
<div id="unlockModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="unlockModalContent">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-green-50">
            <h3 class="font-bold text-green-800 flex items-center gap-2"><i class="fas fa-lock-open"></i> {{ $isAr ? 'فتح فترة مقفولة' : 'Open Locked Period' }}</h3>
            <button type="button" onclick="closeUnlockModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="unlockForm" method="POST" class="p-6">
            @csrf
            @method('PATCH')
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">{{ $isAr ? 'سبب فتح الفترة' : 'Reason for Opening' }} <span class="text-red-500">*</span></label>
                <textarea name="open_reason" required rows="3" placeholder="{{ $isAr ? 'اكتب سبب فتح الفترة المحاسبية لتسجيله في السجل...' : 'Enter the reason for opening this period...' }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-green-500 bg-gray-50"></textarea>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeUnlockModal()" class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-bold hover:bg-gray-50">{{ $isAr ? 'إلغاء' : 'Cancel' }}</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold flex items-center gap-2">
                    <i class="fas fa-check"></i> {{ $isAr ? 'تأكيد الفتح' : 'Confirm Open' }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- History Modal --}}
<div id="historyModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform scale-95 opacity-0 transition-all duration-300" id="historyModalContent">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-blue-50">
            <h3 class="font-bold text-blue-800 flex items-center gap-2"><i class="fas fa-history"></i> {{ $isAr ? 'سجل الفترة' : 'Period History' }}</h3>
            <button type="button" onclick="closeHistoryModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 space-y-6">
            {{-- Created --}}
            <div class="flex gap-4">
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 shrink-0"><i class="fas fa-calendar-plus"></i></div>
                <div>
                    <p class="text-sm font-bold text-gray-900">{{ $isAr ? 'الإنشاء' : 'Creation' }}</p>
                    <p class="text-xs text-gray-500 mt-1" id="histCreatedInfo"></p>
                </div>
            </div>
            {{-- Opened --}}
            <div class="flex gap-4" id="histOpenDiv">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 shrink-0"><i class="fas fa-lock-open"></i></div>
                <div>
                    <p class="text-sm font-bold text-gray-900">{{ $isAr ? 'فتح الفترة' : 'Opened' }}</p>
                    <p class="text-xs text-gray-500 mt-1" id="histOpenedInfo"></p>
                    <div class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-100 text-sm text-gray-700 italic" id="histOpenReason"></div>
                </div>
            </div>
            {{-- Re-Closed --}}
            <div class="flex gap-4" id="histCloseDiv">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 shrink-0"><i class="fas fa-lock"></i></div>
                <div>
                    <p class="text-sm font-bold text-gray-900">{{ $isAr ? 'إعادة الإغلاق' : 'Re-Closed' }}</p>
                    <p class="text-xs text-gray-500 mt-1" id="histClosedInfo"></p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
            <button type="button" onclick="closeHistoryModal()" class="px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 font-bold hover:bg-gray-50">{{ $isAr ? 'إغلاق' : 'Close' }}</button>
        </div>
    </div>
</div>

@php
    $locksDataJson = $locks->map(function($lock) {
        return [
            'id' => $lock->id,
            'created_at' => $lock->created_at ? $lock->created_at->format('Y-m-d H:i') : null,
            'created_by' => optional($lock->creator)->name,
            'opened_at' => $lock->opened_at ? $lock->opened_at->format('Y-m-d H:i') : null,
            'opened_by' => optional($lock->opener)->name,
            'open_reason' => $lock->open_reason,
            'reclosed_at' => $lock->reclosed_at ? $lock->reclosed_at->format('Y-m-d H:i') : null,
            'reclosed_by' => optional($lock->recloser)->name,
        ];
    })->keyBy('id');
@endphp
<script>
    const isAr = {{ $isAr ? 'true' : 'false' }};
    const locksData = @json($locksDataJson);

    function openUnlockModal(id) {
        const baseUrl = "{{ url('/') }}";
        document.getElementById('unlockForm').action = `${baseUrl}/period-locks/${id}/toggle`;
        const m = document.getElementById('unlockModal');
        const mc = document.getElementById('unlockModalContent');
        m.classList.remove('hidden');
        m.classList.add('flex');
        setTimeout(() => {
            mc.classList.remove('scale-95', 'opacity-0');
            mc.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    function closeUnlockModal() {
        const m = document.getElementById('unlockModal');
        const mc = document.getElementById('unlockModalContent');
        mc.classList.remove('scale-100', 'opacity-100');
        mc.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            m.classList.remove('flex');
            m.classList.add('hidden');
        }, 300);
    }

    function openHistoryModal(id) {
        const data = locksData[id];
        
        // Created
        document.getElementById('histCreatedInfo').innerHTML = `${data.created_at || 'N/A'} <br> ${isAr ? 'بواسطة:' : 'By:'} <span class="font-bold">${data.created_by || 'System'}</span>`;
        
        // Opened
        const od = document.getElementById('histOpenDiv');
        if (data.opened_at) {
            od.style.display = 'flex';
            document.getElementById('histOpenedInfo').innerHTML = `${data.opened_at} <br> ${isAr ? 'بواسطة:' : 'By:'} <span class="font-bold">${data.opened_by || 'N/A'}</span>`;
            document.getElementById('histOpenReason').innerText = data.open_reason || '';
        } else {
            od.style.display = 'none';
        }

        // Reclosed
        const cd = document.getElementById('histCloseDiv');
        if (data.reclosed_at) {
            cd.style.display = 'flex';
            document.getElementById('histClosedInfo').innerHTML = `${data.reclosed_at} <br> ${isAr ? 'بواسطة:' : 'By:'} <span class="font-bold">${data.reclosed_by || 'N/A'}</span>`;
        } else {
            cd.style.display = 'none';
        }

        const m = document.getElementById('historyModal');
        const mc = document.getElementById('historyModalContent');
        m.classList.remove('hidden');
        m.classList.add('flex');
        setTimeout(() => {
            mc.classList.remove('scale-95', 'opacity-0');
            mc.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    function closeHistoryModal() {
        const m = document.getElementById('historyModal');
        const mc = document.getElementById('historyModalContent');
        mc.classList.remove('scale-100', 'opacity-100');
        mc.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            m.classList.remove('flex');
            m.classList.add('hidden');
        }, 300);
    }
</script>
@endsection
