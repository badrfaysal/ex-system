@extends('layouts.app')
@section('header_title', __('messages.clients.edit_title'))

@section('content')
{{-- ترويسة الشاشة --}}
<div class="mb-6 flex justify-between items-center animate-fade-in">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
            <i class="fas fa-user-edit text-2xl"></i>
        </div>
        <div>
            <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.clients.edit_title') }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $client->company_name }}</p>
        </div>
    </div>
    <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors shadow-sm flex items-center gap-2">
        <i class="fas fa-arrow-right text-sm"></i>
        {{ __('messages.common.back_list') }}
    </a>
</div>

<div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100 animate-fade-in">

    <div class="h-2 w-32 bg-[#005B9F] rounded-full mb-8"></div>

    <form action="{{ route('clients.update', $client) }}" method="POST">
        @csrf
        @method('PUT')

        @include('clients._form', ['entity' => $client])

        {{-- أزرار الحفظ والحذف --}}
        <div class="mt-12 flex justify-between items-center gap-4 border-t border-gray-100 pt-8">
            <button type="submit" form="deleteClientForm" onclick="return confirm('{{ __('messages.clients.confirm_del') }}')"
                class="px-5 py-2.5 border border-red-200 rounded-lg text-red-600 bg-white hover:bg-red-50 font-medium transition-colors flex items-center gap-2">
                <i class="fas fa-trash-alt"></i>
                {{ __('messages.clients.delete') }}
            </button>
            <div class="flex gap-4">
                <a href="{{ route('clients.index') }}" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-100 font-medium transition-colors">
                    {{ __('messages.common.cancel') }}
                </a>
                <button type="submit" class="px-8 py-2.5 bg-[#008A3B] border border-transparent rounded-lg text-white hover:bg-[#007030] font-bold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#008A3B] transition-colors shadow-lg flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    {{ __('messages.common.save_changes') }}
                </button>
            </div>
        </div>

    </form>

    {{-- فورم منفصل للحذف --}}
    <form id="deleteClientForm" action="{{ route('clients.destroy', $client) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection
