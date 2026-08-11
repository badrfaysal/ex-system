@extends('layouts.app')
@section('header_title', __('إضافة مجموعة جديدة'))

@section('content')
<div class="container mx-auto px-4 max-w-3xl animate-fade-in">
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('إضافة مجموعة جديدة') }}</h2>
        <a href="{{ route('contact-groups.index') }}" class="text-gray-500 hover:text-gray-700 transition-colors">
            <i class="fas fa-arrow-right"></i> {{ __('عودة') }}
        </a>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg border border-red-200">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('contact-groups.store') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('اسم المجموعة (مثال: سواقين، عمال...)') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] transition-colors">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('وصف (اختياري)') }}</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] transition-colors">{{ old('description') }}</textarea>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('contact-groups.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200 transition-colors">
                    {{ __('إلغاء') }}
                </a>
                <button type="submit" class="px-6 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030] transition-colors">
                    <i class="fas fa-save"></i> {{ __('حفظ المجموعة') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
