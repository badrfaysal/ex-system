@extends('layouts.app')
@php $isAr = app()->getLocale() === 'ar'; @endphp
@section('header_title', $isAr ? 'تعديل سند دفع' : 'Edit Vendor Payment')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex justify-between items-center animate-fade-in">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-red-500/10 flex items-center justify-center text-red-600">
                <i class="fas fa-money-check-alt text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $isAr ? 'تعديل سند دفع' : 'Edit Vendor Payment' }}</h2>
        </div>
        <a href="{{ route('payables.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-arrow-{{ $isAr ? 'right' : 'left' }} text-sm"></i> {{ $isAr ? 'الالتزامات' : 'Payables' }}
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 animate-fade-in">
        <div class="h-2 w-32 bg-red-500 rounded-full mb-8"></div>
        @include('vendor_payments._form', ['payment' => $payment])
    </div>
</div>
@endsection
