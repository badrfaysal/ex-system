@extends('layouts.app')
@section('header_title', 'طباعة الملصقات')

@section('content')
<div class="container mx-auto px-4 max-w-5xl animate-fade-in">
    
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-lg bg-pink-100 flex items-center justify-center text-pink-600">
                <i class="fas fa-print text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900 tracking-tight">طباعة ملصق</h2>
                <p class="text-sm text-gray-500 mt-0.5">إدارة وطباعة ملصقات المنتجات</p>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 mb-6">
        <a href="{{ route('labels.create') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-pink-600 text-pink-600">
            <i class="fas fa-plus ml-2"></i> إصدار ملصق
        </a>
        <a href="{{ route('labels.index') }}" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
            <i class="fas fa-history ml-2"></i> سجل الطباعة
        </a>
    </div>

    <form action="{{ route('labels.print') }}" method="POST" target="_blank" onsubmit="setTimeout(() => { let b = this.querySelector('button[type=submit]'); if(b){ b.disabled=false; b.classList.remove('opacity-75','cursor-not-allowed'); b.innerHTML = b.innerHTML.replace('<i class=\'fas fa-spinner fa-spin mx-1\'></i>', '').trim(); } if(typeof submittedForms !== 'undefined') submittedForms.delete(this); }, 1500);">
        @csrf
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Brand & Item -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">اسم البراند</label>
                    <input type="text" name="brand_name" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="مثال: 7Sta">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">الصنف</label>
                    <select name="item_name" id="item_name" required class="w-full">
                        <option value="">اختر الصنف...</option>
                        @foreach($items as $item)
                            <option value="{{ $item->name_ar }}">{{ $item->name_ar }} ({{ $item->item_code }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Manufacturer -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">الشركة المصنعة</label>
                    <input type="text" name="manufacturer" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="الشركة المصنعة">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">عنوان المصنع</label>
                    <input type="text" name="manufacturer_address" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="عنوان المصنع">
                </div>

                <!-- Exporter -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">الشركة المصدرة (شركتنا)</label>
                    <input type="text" name="exporter" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" value="ايجيبيشن فودز">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">عنوان المصدر</label>
                    <input type="text" name="exporter_address" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" value="اكتوبر , الجيزة , جمهورية مصر العربية">
                </div>

                <!-- Importer -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">اسم المستورد</label>
                    <select name="importer" id="importer" required class="w-full">
                        <option value="">اختر اسم المستورد...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->company_name }}">{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">عنوان المستورد</label>
                    <input type="text" name="importer_address" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="عنوان المستورد">
                </div>

                <!-- Dates & Batch -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">تاريخ الانتاج</label>
                    <input type="text" name="production_date" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="مثال: 2026/08">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">تاريخ الانتهاء</label>
                    <input type="text" name="expiry_date" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="مثال: 2027/10">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">كود التشغيلة (Batch)</label>
                    <input type="text" name="batch_code" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" placeholder="مثال: OP12EP908">
                </div>
                
                <!-- Website -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">الويب سايت / الإيميل</label>
                    <input type="text" name="website" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]" value="WWW.efcexport.com / info@efcexport.com">
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6 mt-6 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <label class="text-sm font-bold text-gray-700">عدد النسخ المطلوبة:</label>
                    <input type="number" name="copies" min="1" value="1" required class="w-24 px-3 py-2 border border-gray-300 rounded-md text-center focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B]">
                </div>
                <button type="submit" class="px-8 py-2.5 bg-pink-600 border border-transparent rounded-md text-white hover:bg-pink-700 font-bold shadow-md flex items-center gap-2">
                    <i class="fas fa-print text-lg"></i> حفظ وطباعة الملصق
                </button>
            </div>
            
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof TomSelect !== 'undefined') {
        new TomSelect('#item_name', {
            create: true,
            sortField: { field: 'text', direction: 'asc' },
            placeholder: 'اختر أو اكتب اسم الصنف...'
        });
        
        new TomSelect('#importer', {
            create: true,
            sortField: { field: 'text', direction: 'asc' },
            placeholder: 'اختر أو اكتب اسم المستورد...'
        });
    }
});
</script>
@endsection

