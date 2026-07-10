@extends('layouts.app')
@section('header_title', __('messages.clients.add_title'))

@section('content')
{{-- ترويسة الشاشة داخل منطقة المحتوى --}}
<div class="mb-6 flex justify-between items-center animate-fade-in">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
            <i class="fas fa-user-plus text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.clients.add_title') }}</h2>
    </div>
    <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors shadow-sm flex items-center gap-2">
        <i class="fas fa-arrow-right text-sm"></i>
        {{ __('messages.common.back_list') }}
    </a>
</div>

<div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 animate-fade-in">

    <div class="h-2 w-32 bg-[#008A3B] rounded-full mb-8"></div>

    <form action="{{ route('clients.store') }}" method="POST">
        @csrf

        @include('clients._form', ['entity' => null])

        {{-- أزرار الحفظ والإلغاء --}}
        <div class="mt-12 flex justify-end gap-4 border-t border-gray-100 pt-8">
            <button type="reset" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium transition-colors">
                {{ __('messages.common.reset') }}
            </button>
            <button type="submit" class="px-8 py-2.5 bg-[#008A3B] border border-transparent rounded-lg text-white hover:bg-[#007030] font-bold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#008A3B] transition-colors shadow-lg flex items-center gap-2">
                <i class="fas fa-save"></i>
                {{ __('messages.clients.save_client') }}
            </button>
        </div>

    </form>
</div>
@endsection
