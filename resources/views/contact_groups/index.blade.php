@extends('layouts.app')
@section('header_title', __('المجموعات وجهات الاتصال'))

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
                <i class="fas fa-layer-group text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ __('المجموعات وجهات الاتصال') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('إدارة مجموعات السائقين، العمال، الموردين، وغيرها') }}</p>
            </div>
        </div>
        <a href="{{ route('contact-groups.create') }}" class="px-6 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030] transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> {{ __('إضافة مجموعة جديدة') }}
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ __('اسم المجموعة') }}</th>
                        <th class="p-4">{{ __('الوصف') }}</th>
                        <th class="p-4">{{ __('عدد الأفراد/الجهات') }}</th>
                        <th class="p-4">{{ __('إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($contactGroups as $group)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4 font-bold text-gray-900">
                                <a href="{{ route('contact-groups.show', $group->id) }}" class="hover:text-[#008A3B] transition-colors">
                                    {{ $group->name }}
                                </a>
                            </td>
                            <td class="p-4 text-gray-500">{{ $group->description ?: '-' }}</td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-blue-50 text-blue-700">
                                    {{ $group->contacts()->count() }}
                                </span>
                            </td>
                            <td class="p-4 flex gap-2">
                                <a href="{{ route('contact-groups.show', $group->id) }}" class="text-blue-500 hover:text-blue-700 p-2" title="عرض">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('contact-groups.edit', $group->id) }}" class="text-orange-500 hover:text-orange-700 p-2" title="تعديل">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form action="{{ route('contact-groups.destroy', $group->id) }}" method="POST" class="inline-block" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-2" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">{{ __('لا توجد مجموعات مسجلة بعد.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
