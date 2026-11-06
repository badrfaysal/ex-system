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
                <p class="text-sm text-gray-500 mt-0.5">عرض الملصقات التي تم طباعتها مسبقاً مع إمكانية إعادة الطباعة بنقرة واحدة</p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-gray-200 mb-6">
        <a href="{{ route('labels.detailed') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 flex items-center gap-2">
            <i class="fas fa-file-invoice"></i> الملصق التفصيلي الكامل
        </a>
        <a href="{{ route('labels.compact') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 flex items-center gap-2">
            <i class="fas fa-barcode"></i> الملصق الصغير (50 × 25 مم)
        </a>
        <a href="{{ route('labels.index') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-pink-600 text-pink-600 flex items-center gap-2">
            <i class="fas fa-history"></i> سجل الطباعة
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        @if($labels->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm text-gray-600">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-700 font-bold">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">نوع الملصق</th>
                            <th class="px-4 py-3">البراند / الشركة</th>
                            <th class="px-4 py-3">الصنف</th>
                            <th class="px-4 py-3">كود التشغيلة</th>
                            <th class="px-4 py-3">النسخ</th>
                            <th class="px-4 py-3">تاريخ الطباعة</th>
                            <th class="px-4 py-3 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($labels as $label)
                        @php $isCompact = ($label->batch_code === 'COMPACT'); @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs">{{ $label->id }}</td>
                            <td class="px-4 py-3">
                                @if($isCompact)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-bold bg-pink-100 text-pink-800 border border-pink-200">
                                        <i class="fas fa-barcode text-[10px]"></i> ملصق صغير (50×25)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                        <i class="fas fa-file-invoice text-[10px]"></i> ملصق تفصيلي
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-bold text-gray-800">
                                {{ $label->brand_name }}
                            </td>
                            <td class="px-4 py-3 font-bold text-gray-900">{{ $label->item_name }}</td>
                            <td class="px-4 py-3">
                                @if($isCompact)
                                    <span class="text-xs text-gray-400 font-mono">—</span>
                                @else
                                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-mono font-bold">{{ $label->batch_code }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-bold text-center">{{ $label->copies }}</td>
                            <td class="px-4 py-3 text-xs" dir="ltr">{{ $label->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($isCompact)
                                    <form action="{{ route('labels.compact.print') }}" method="POST" target="_blank" class="inline" onsubmit="setTimeout(() => { let b = this.querySelector('button[type=submit]'); if(b){ b.disabled=false; b.classList.remove('opacity-75','cursor-not-allowed'); b.innerHTML = b.innerHTML.replace('<i class=\'fas fa-spinner fa-spin mx-1\'></i>', '').trim(); } if(typeof submittedForms !== 'undefined') submittedForms.delete(this); }, 1500);">
                                        @csrf
                                        <input type="hidden" name="company_name" value="{{ $label->brand_name ?: ($label->exporter ?: 'ايجيبيشن فودز') }}">
                                        <input type="hidden" name="item_name" value="{{ $label->item_name }}">
                                        <input type="hidden" name="date" value="{{ $label->production_date }}">
                                        <input type="hidden" name="copies" value="{{ $label->copies }}">
                                        <input type="hidden" name="show_logo" value="1">
                                        <input type="hidden" name="label_size" value="50x25">
                                        
                                        <button type="submit" class="text-pink-700 hover:text-pink-900 bg-pink-50 hover:bg-pink-100 border border-pink-200 px-3 py-1.5 rounded-md transition-colors text-xs font-bold inline-flex items-center gap-1.5 shadow-sm">
                                            <i class="fas fa-print"></i> إعادة طباعة
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('labels.detailed.print') }}" method="POST" target="_blank" class="inline" onsubmit="setTimeout(() => { let b = this.querySelector('button[type=submit]'); if(b){ b.disabled=false; b.classList.remove('opacity-75','cursor-not-allowed'); b.innerHTML = b.innerHTML.replace('<i class=\'fas fa-spinner fa-spin mx-1\'></i>', '').trim(); } if(typeof submittedForms !== 'undefined') submittedForms.delete(this); }, 1500);">
                                        @csrf
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
                                        <input type="hidden" name="show_logo" value="1">
                                        
                                        <button type="submit" class="text-slate-800 hover:text-black bg-slate-100 hover:bg-slate-200 border border-slate-300 px-3 py-1.5 rounded-md transition-colors text-xs font-bold inline-flex items-center gap-1.5 shadow-sm">
                                            <i class="fas fa-print"></i> إعادة طباعة
                                        </button>
                                    </form>
                                @endif
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
