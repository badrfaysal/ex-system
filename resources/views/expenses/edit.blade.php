@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'تعديل مصروف' : 'Edit Expense')

@section('content')
<div class="mb-6 flex justify-between items-center animate-fade-in">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
            <i class="fas fa-receipt text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900">{{ $isAr ? 'تعديل مصروف' : 'Edit Expense' }}</h2>
    </div>
    <a href="{{ route('expenses.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors shadow-sm flex items-center gap-2">
        <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }} text-sm"></i>
        {{ $isAr ? 'العودة للقائمة' : 'Back to list' }}
    </a>
</div>

<div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 animate-fade-in">
    <div class="h-2 w-32 bg-[#008A3B] rounded-full mb-8"></div>

    <form action="{{ route('expenses.update', $expense) }}" method="POST">
        @csrf
        @method('PUT')
        @include('expenses._form', ['expense' => $expense])

        <div class="mt-12 flex justify-end gap-4 border-t border-gray-100 pt-8">
            <a href="{{ route('expenses.index') }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium transition-colors">
                {{ $isAr ? 'إلغاء' : 'Cancel' }}
            </a>
            <button type="submit" class="px-8 py-2.5 bg-[#008A3B] rounded-lg text-white hover:bg-[#007030] font-bold shadow-lg flex items-center gap-2">
                <i class="fas fa-save"></i> {{ $isAr ? 'حفظ التعديلات' : 'Save Changes' }}
            </button>
        </div>
    </form>
</div>
@endsection
