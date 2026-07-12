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
                                    <form action="{{ route('period-locks.toggle', $lock) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        @if($lock->is_active)
                                            <button type="submit" class="px-4 py-1.5 border border-green-300 text-green-700 rounded-lg text-xs font-bold hover:bg-green-50 flex items-center gap-1.5">
                                                <i class="fas fa-lock-open"></i> {{ $isAr ? 'فتح' : 'Open' }}
                                            </button>
                                        @else
                                            <button type="submit" class="px-4 py-1.5 border border-red-300 text-red-700 rounded-lg text-xs font-bold hover:bg-red-50 flex items-center gap-1.5">
                                                <i class="fas fa-lock"></i> {{ $isAr ? 'قفل' : 'Close' }}
                                            </button>
                                        @endif
                                    </form>
                                    <form action="{{ route('period-locks.destroy', $lock) }}" method="POST" onsubmit="return confirm({{ json_encode($isAr ? 'حذف هذه الفترة نهائيًا؟' : 'Delete this period permanently?') }});">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-300 hover:text-red-600 hover:bg-red-50">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
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
@endsection
