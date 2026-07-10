<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة عميل - EFC</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

    <div class="container mx-auto px-4 py-8 max-w-5xl">
        
        {{-- ترويسة الشاشة --}}
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800 border-r-4 border-[#008A3B] pr-3">
                إضافة عميل جديد
            </h2>
            <a href="{{ route('clients.index') }}" class="text-[#005B9F] hover:underline font-medium">
                العودة للقائمة
            </a>
        </div>

        {{-- كارت الفورم --}}
        <div class="bg-white rounded-lg shadow-md p-6 border-t-4 border-[#008A3B]">
            <form action="{{ route('clients.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    {{-- اسم الشركة --}}
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">اسم الشركة <span class="text-red-500">*</span></label>
                        <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] transition-colors">
                        @error('company_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- الشخص المسؤول --}}
                    <div>
                        <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">الشخص المسؤول</label>
                        <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person') }}"
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] transition-colors">
                        @error('contact_person') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- رقم الهاتف --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">رقم الهاتف <span class="text-red-500">*</span></label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required dir="ltr"
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] text-right transition-colors">
                        @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- الايميل --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" dir="ltr"
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] text-right transition-colors">
                        @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- الدولة --}}
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">الدولة <span class="text-red-500">*</span></label>
                        <select id="country" name="country" required
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] transition-colors bg-white">
                            <option value="" disabled selected>اختر الدولة...</option>
                            <option value="EG" {{ old('country') == 'EG' ? 'selected' : '' }}>مصر</option>
                            <option value="SA" {{ old('country') == 'SA' ? 'selected' : '' }}>السعودية</option>
                            <option value="AE" {{ old('country') == 'AE' ? 'selected' : '' }}>الإمارات</option>
                        </select>
                        @error('country') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- الرقم الضريبي --}}
                    <div>
                        <label for="tax_id" class="block text-sm font-medium text-gray-700 mb-1">الرقم الضريبي</label>
                        <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id') }}"
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] transition-colors">
                        @error('tax_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- تصنيف العميل --}}
                    <div>
                        <label for="client_type" class="block text-sm font-medium text-gray-700 mb-1">تصنيف العميل <span class="text-red-500">*</span></label>
                        <select id="client_type" name="client_type" required
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] transition-colors bg-white">
                            <option value="" disabled selected>اختر التصنيف...</option>
                            <option value="wholesale" {{ old('client_type') == 'wholesale' ? 'selected' : '' }}>عملاء جملة</option>
                            <option value="retail" {{ old('client_type') == 'retail' ? 'selected' : '' }}>تجزئة</option>
                            <option value="international" {{ old('client_type') == 'international' ? 'selected' : '' }}>دوليين</option>
                        </select>
                        @error('client_type') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- العنوان (يأخذ عرض الشاشة بالكامل) --}}
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">العنوان بالتفصيل</label>
                        <textarea id="address" name="address" rows="3"
                            class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] transition-colors">{{ old('address') }}</textarea>
                        @error('address') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                </div>

                {{-- أزرار الحفظ والإلغاء --}}
                <div class="mt-8 flex justify-end gap-3 border-t border-gray-200 pt-5">
                    <button type="reset" class="px-5 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 font-medium transition-colors">
                        تفريغ الحقول
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#008A3B] border border-transparent rounded-md text-white hover:bg-[#007030] font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#008A3B] transition-colors shadow-sm">
                        حفظ العميل
                    </button>
                </div>
                
            </form>
        </div>
    </div>

</body>
</html>