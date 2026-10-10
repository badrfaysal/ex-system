@extends('layouts.app')
@section('header_title', 'سجل طباعة الملصقات')

@section('content')
<div class="container mx-auto px-4 max-w-5xl animate-fade-in">
    
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-600">
                <i class="fas fa-history text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900 tracking-tight">سجل طباعة الملصقات</h2>
                <p class="text-sm text-gray-500 mt-0.5">عرض الملصقات التي تم طباعتها مسبقاً</p>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 mb-6">
        <a href="{{ route('labels.create') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
            <i class="fas fa-plus ml-2"></i> إصدار ملصق
        </a>
        <a href="{{ route('labels.index') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-pink-600 text-pink-600">
            <i class="fas fa-history ml-2"></i> سجل الطباعة
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        @if($labels->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm text-gray-600">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 font-bold">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">البراند</th>
                            <th class="px-4 py-3">الصنف</th>
                            <th class="px-4 py-3">كود التشغيلة</th>
                            <th class="px-4 py-3">النسخ</th>
                            <th class="px-4 py-3">تاريخ الطباعة</th>
                            <th class="px-4 py-3 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($labels as $label)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">{{ $label->id }}</td>
                            <td class="px-4 py-3 font-bold text-gray-800">{{ $label->brand_name }}</td>
                            <td class="px-4 py-3">{{ $label->item_name }}</td>
                            <td class="px-4 py-3"><span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-mono">{{ $label->batch_code }}</span></td>
                            <td class="px-4 py-3">{{ $label->copies }}</td>
                            <td class="px-4 py-3" dir="ltr">{{ $label->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-center">
                                <form action="{{ route('labels.print') }}" method="POST" target="_blank" class="inline" onsubmit="setTimeout(() => { let b = this.querySelector('button[type=submit]'); if(b){ b.disabled=false; b.classList.remove('opacity-75','cursor-not-allowed'); b.innerHTML = b.innerHTML.replace('<i class=\'fas fa-spinner fa-spin mx-1\'></i>', '').trim(); } if(typeof submittedForms !== 'undefined') submittedForms.delete(this); }, 1500);">
                                    @csrf
                                    <!-- Hidden inputs to resubmit -->
                                    <input type="hidden" name="brand_name" value="{{ $label->brand_name }}">
                                    <input type="hidden" name="item_name" value="{{ $label->item_name }}">
                                    <input type="hidden" name="manufacturer" value="{{ $label->manufacturer }}">
                                    <input type="hidden" name="manufacturer_address" value="{{ $label->manufacturer_address }}">
                                    <input type="hidden" name="exporter" value="{{ $label->exporter }}">
                                    <input type="hidden" name="exporter_address" value="{{ $label->exporter_address }}">
                                    <input type="hidden" name="importer" value="{{ $label->importer }}">
                                    <input type="hidden" name="importer_address" value="{{ $label->importer_address }}">
                                    <input type="hidden" name="production_date" value="{{ $label->production_date }}">
                                    <input type="hidden" name="expiry_date" value="{{ $label->expiry_date }}">
                                    <input type="hidden" name="batch_code" value="{{ $label->batch_code }}">
                                    <input type="hidden" name="website" value="{{ $label->website }}">
                                    <input type="hidden" name="copies" value="{{ $label->copies }}">
                                    
                                    <button type="submit" class="text-[#008A3B] hover:text-[#007030] bg-[#EBF7F0] hover:bg-[#cceedb] px-3 py-1.5 rounded-md transition-colors text-xs font-bold inline-flex items-center gap-1">
                                        <i class="fas fa-print"></i> إعادة طباعة
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center text-gray-500">
                <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-history text-2xl"></i>
                </div>
                <p class="text-lg font-bold text-gray-700">لا يوجد سجل للطباعة بعد</p>
                <p class="text-sm mt-1">الملصقات التي ستقوم بطباعتها ستظهر هنا</p>
            </div>
        @endif
    </div>

</div>
@endsection
