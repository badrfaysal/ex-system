@php $entity = $entity ?? null; @endphp

<div class="space-y-8">

    {{-- القسم الأول: البيانات الأساسية --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
            <i class="fas fa-info-circle"></i>
            <span>1. البيانات الأساسية للمورد (General Information)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- كود المورد --}}
            <div>
                <label for="vendor_code" class="block text-sm font-semibold text-gray-700 mb-1.5">كود المورد (تلقائي / يدوي)</label>
                <input type="text" id="vendor_code" name="vendor_code" value="{{ old('vendor_code', $entity?->vendor_code) }}" placeholder="يترك فارغاً للتوليد التلقائي"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] bg-gray-50 transition-colors">
                @error('vendor_code') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- الاسم التجاري - عربي --}}
            <div>
                <label for="name_ar" class="block text-sm font-semibold text-gray-700 mb-1.5">الاسم التجاري (عربي) <span class="text-red-500">*</span></label>
                <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar', $entity?->name_ar) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors">
                @error('name_ar') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- الاسم التجاري - إنجليزي --}}
            <div>
                <label for="name_en" class="block text-sm font-semibold text-gray-700 mb-1.5">الاسم التجاري (إنجليزي)</label>
                <input type="text" id="name_en" name="name_en" value="{{ old('name_en', $entity?->name_en) }}" dir="ltr"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] text-right transition-colors">
                @error('name_en') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- الاسم القانوني للشركة --}}
            <div class="md:col-span-2">
                <label for="legal_name" class="block text-sm font-semibold text-gray-700 mb-1.5">الاسم القانوني للشركة (حسب السجل التجاري)</label>
                <input type="text" id="legal_name" name="legal_name" value="{{ old('legal_name', $entity?->legal_name) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors">
                @error('legal_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- مجموعة الموردين --}}
            <div>
                <label for="vendor_group" class="block text-sm font-semibold text-gray-700 mb-1.5">مجموعة الموردين <span class="text-red-500">*</span></label>
                <select id="vendor_group" name="vendor_group" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors bg-white">
                    <option value="" disabled {{ old('vendor_group', $entity?->vendor_group) ? '' : 'selected' }}>اختر المجموعة...</option>
                    <option value="local" {{ old('vendor_group', $entity?->vendor_group) == 'local' ? 'selected' : '' }}>موردين محليين</option>
                    <option value="international" {{ old('vendor_group', $entity?->vendor_group) == 'international' ? 'selected' : '' }}>موردين خارجيين / استيراد</option>
                    <option value="subcontractor" {{ old('vendor_group', $entity?->vendor_group) == 'subcontractor' ? 'selected' : '' }}>مقاولي باطن</option>
                    <option value="government" {{ old('vendor_group', $entity?->vendor_group) == 'government' ? 'selected' : '' }}>جهات حكومية</option>
                </select>
                @error('vendor_group') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- حالة المورد --}}
            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-1.5">حالة المورد</label>
                <select id="status" name="status" onchange="toggleBlockReason()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors bg-white">
                    <option value="active" {{ old('status', $entity?->status) == 'active' ? 'selected' : '' }}>نشط Active</option>
                    <option value="on_hold" {{ old('status', $entity?->status) == 'on_hold' ? 'selected' : '' }}>موقوف مؤقتاً On Hold</option>
                    <option value="blocked" {{ old('status', $entity?->status) == 'blocked' ? 'selected' : '' }}>محظور Blocked</option>
                </select>
            </div>

            {{-- سبب الحظر (يظهر برمجياً عند اختيار محظور) --}}
            <div id="block_reason_div" class="md:col-span-2 hidden">
                <label for="block_reason" class="block text-sm font-semibold text-gray-700 mb-1.5 text-red-600">سبب الحظر <span class="text-red-500">*</span></label>
                <input type="text" id="block_reason" name="block_reason" value="{{ old('block_reason', $entity?->block_reason) }}" placeholder="إجباري عند حظر المورد"
                    class="w-full px-4 py-2 border border-red-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500 transition-colors">
                @error('block_reason') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    {{-- القسم الثاني: بيانات الاتصال والعناوين ومسؤول الاتصال --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#005B9F] font-bold text-lg">
            <i class="fas fa-address-book"></i>
            <span>2. بيانات الاتصال والعناوين (Contact & Address Details)</span>
        </div>

        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-phone text-gray-400"></i> بيانات الاتصال الرئيسية:</h4>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div>
                <label for="phone" class="block text-sm text-gray-600 mb-1">الهاتف الأرضي</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $entity?->phone) }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
            </div>
            <div>
                <label for="mobile" class="block text-sm text-gray-600 mb-1">المحمول <span class="text-red-500">*</span></label>
                <input type="text" id="mobile" name="mobile" value="{{ old('mobile', $entity?->mobile) }}" required dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
            </div>
            <div>
                <label for="email" class="block text-sm text-gray-600 mb-1">البريد الإلكتروني الرسمي</label>
                <input type="email" id="email" name="email" value="{{ old('email', $entity?->email) }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
            </div>
            <div>
                <label for="website" class="block text-sm text-gray-600 mb-1">الموقع الإلكتروني</label>
                <input type="url" id="website" name="website" value="{{ old('website', $entity?->website) }}" placeholder="https://" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
            </div>
        </div>

        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-user-tie text-gray-400"></i> مسؤول الاتصال الرئيسي (Primary Contact Person):</h4>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8 bg-gray-50 p-4 rounded-xl">
            <div>
                <label for="contact_person_name" class="block text-sm text-gray-600 mb-1">الاسم بالكامل</label>
                <input type="text" id="contact_person_name" name="contact_person_name" value="{{ old('contact_person_name', $entity?->contact_person_name) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label for="contact_person_job" class="block text-sm text-gray-600 mb-1">الوظيفة (مثل: مدير المبيعات)</label>
                <input type="text" id="contact_person_job" name="contact_person_job" value="{{ old('contact_person_job', $entity?->contact_person_job) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label for="contact_person_mobile" class="block text-sm text-gray-600 mb-1">رقم الموبايل المباشر</label>
                <input type="text" id="contact_person_mobile" name="contact_person_mobile" value="{{ old('contact_person_mobile', $entity?->contact_person_mobile) }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
            </div>
            <div>
                <label for="contact_person_email" class="block text-sm text-gray-600 mb-1">البريد الإلكتروني المباشر</label>
                <input type="email" id="contact_person_email" name="contact_person_email" value="{{ old('contact_person_email', $entity?->contact_person_email) }}" dir="ltr" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-right">
            </div>
        </div>

        {{-- جدول العناوين المتعددة --}}
        <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2 text-sm"><i class="fas fa-map-marked-alt text-gray-400"></i> جدول العناوين المتعددة (Multi-Address Grid):</h4>
        <div class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-inner">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-200 text-sm font-semibold text-gray-700">
                        <th class="p-3 w-1/4">نوع العنوان</th>
                        <th class="p-3 w-3/4">تفاصيل العنوان بالكامل</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-gray-100">
                        <td class="p-3">
                            <span class="text-xs bg-blue-50 text-blue-700 font-bold px-2 py-1 rounded">عنوان الإدارة الرئيسي</span>
                        </td>
                        <td class="p-2">
                            <input type="text" name="address_head" value="{{ old('address_head') }}" placeholder="شارع، مدينة، محافظة..." class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                        </td>
                    </tr>
                    <tr>
                        <td class="p-3">
                            <span class="text-xs bg-green-50 text-green-700 font-bold px-2 py-1 rounded">عنوان المخازن / المصنع</span>
                        </td>
                        <td class="p-2">
                            <input type="text" name="address_pickup" value="{{ old('address_pickup') }}" placeholder="مكان تحرك واستلام البضاعة..." class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- القسم الثالث: البيانات المالية والضرائب --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
            <i class="fas fa-calculator"></i>
            <span>3. البيانات المالية والضرائب (Financials & Taxation)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            {{-- العملة الافتراضية --}}
            <div>
                <label for="default_currency" class="block text-sm font-semibold text-gray-700 mb-1.5">العملة الافتراضية <span class="text-red-500">*</span></label>
                <select id="default_currency" name="default_currency" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="EGP" {{ old('default_currency', $entity?->default_currency) == 'EGP' ? 'selected' : '' }}>جنيه مصري (EGP)</option>
                    <option value="USD" {{ old('default_currency', $entity?->default_currency) == 'USD' ? 'selected' : '' }}>دولار أمريكي (USD)</option>
                    <option value="EUR" {{ old('default_currency', $entity?->default_currency) == 'EUR' ? 'selected' : '' }}>يورو (EUR)</option>
                </select>
            </div>

            {{-- رقم التسجيل الضريبي --}}
            <div>
                <label for="tax_id" class="block text-sm font-semibold text-gray-700 mb-1.5">رقم التسجيل الضريبي <span class="text-red-500">*</span></label>
                <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id', $entity?->tax_id) }}" required placeholder="حتمي للربط الإلكتروني"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                @error('tax_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- رقم السجل التجاري --}}
            <div>
                <label for="commercial_registry" class="block text-sm font-semibold text-gray-700 mb-1.5">رقم السجل التجاري</label>
                <input type="text" id="commercial_registry" name="commercial_registry" value="{{ old('commercial_registry', $entity?->commercial_registry) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            {{-- الحد الائتماني للمورد --}}
            <div>
                <label for="credit_limit" class="block text-sm font-semibold text-gray-700 mb-1.5">الحد الائتماني للمورد</label>
                <div class="relative rounded-lg shadow-sm">
                    <input type="number" step="0.01" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $entity?->credit_limit) }}" placeholder="0.00"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                </div>
            </div>
        </div>
    </div>

    {{-- القسم الرابع: شروط الدفع واللوجستيات --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#005B9F] font-bold text-lg">
            <i class="fas fa-hand-holding-usd"></i>
            <span>4. شروط الدفع واللوجستيات (Payment terms & Logistics)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            {{-- شروط الدفع --}}
            <div>
                <label for="payment_terms" class="block text-sm font-semibold text-gray-700 mb-1.5">شروط الدفع المتفق عليها</label>
                <select id="payment_terms" name="payment_terms" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="cash" {{ old('payment_terms', $entity?->payment_terms) == 'cash' ? 'selected' : '' }}>نقداً Cash</option>
                    <option value="advance" {{ old('payment_terms', $entity?->payment_terms) == 'advance' ? 'selected' : '' }}>دفع مقدم Advance</option>
                    <option value="30_days" {{ old('payment_terms', $entity?->payment_terms) == '30_days' ? 'selected' : '' }}>آجل 30 يوماً</option>
                    <option value="60_days" {{ old('payment_terms', $entity?->payment_terms) == '60_days' ? 'selected' : '' }}>آجل 60 يوماً</option>
                    <option value="cod" {{ old('payment_terms', $entity?->payment_terms) == 'cod' ? 'selected' : '' }}>الدفع عند الاستلام</option>
                </select>
            </div>

            {{-- طريقة الدفع المفضلة --}}
            <div>
                <label for="payment_method" class="block text-sm font-semibold text-gray-700 mb-1.5">طريقة الدفع المفضلة</label>
                <select id="payment_method" name="payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white">
                    <option value="bank_transfer" {{ old('payment_method', $entity?->payment_method) == 'bank_transfer' ? 'selected' : '' }}>تحويل بنكي</option>
                    <option value="check" {{ old('payment_method', $entity?->payment_method) == 'check' ? 'selected' : '' }}>شيك</option>
                    <option value="cash" {{ old('payment_method', $entity?->payment_method) == 'cash' ? 'selected' : '' }}>نقدي</option>
                </select>
            </div>

            {{-- فترة التوريد المعتادة --}}
            <div>
                <label for="lead_time_days" class="block text-sm font-semibold text-gray-700 mb-1.5">فترة التوريد المعتادة (Lead Time)</label>
                <div class="relative">
                    <input type="number" id="lead_time_days" name="lead_time_days" value="{{ old('lead_time_days', $entity?->lead_time_days) }}" placeholder="مثال: 5"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg pl-12">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400 bg-gray-100 border-r border-gray-300 px-3 rounded-l-lg pointer-events-none">أيام</div>
                </div>
            </div>
        </div>

        {{-- تفاصيل الحساب البنكي --}}
        <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2 text-sm"><i class="university text-gray-400"></i> بيانات الحساب البنكي للمورد (Bank Details):</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-xl">
            <div>
                <label for="bank_name" class="block text-sm text-gray-600 mb-1">اسم البنك</label>
                <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $entity?->bank_name) }}" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label for="bank_branch" class="block text-sm text-gray-600 mb-1">فرع البنك</label>
                <input type="text" id="bank_branch" name="bank_branch" value="{{ old('bank_branch', $entity?->bank_branch) }}" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label for="account_holder" class="block text-sm text-gray-600 mb-1">اسم صاحب الحساب</label>
                <input type="text" id="account_holder" name="account_holder" value="{{ old('account_holder', $entity?->account_holder) }}" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label for="account_number" class="block text-sm text-gray-600 mb-1">رقم الحساب</label>
                <input type="text" id="account_number" name="account_number" value="{{ old('account_number', $entity?->account_number) }}" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label for="iban" class="block text-sm text-gray-600 mb-1">كود الـ IBAN</label>
                <input type="text" id="iban" name="iban" value="{{ old('iban', $entity?->iban) }}" dir="ltr" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-right">
            </div>
            <div>
                <label for="swift_code" class="block text-sm text-gray-600 mb-1">كود الـ SWIFT Code</label>
                <input type="text" id="swift_code" name="swift_code" value="{{ old('swift_code', $entity?->swift_code) }}" dir="ltr" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-right">
            </div>
        </div>
    </div>

    {{-- القسم الخامس: المرفقات والمستندات القانونية --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-2 pb-4 mb-6 border-b border-gray-100 text-[#008A3B] font-bold text-lg">
            <i class="fas fa-paperclip"></i>
            <span>5. المرفقات والتنبيهات القانونية (Attachments & Reminders)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">المستندات القانونية (شهادة الضرائب، السجل، رخصة التصنيع، شهادات الجودة ISO)</label>
                <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:border-[#008A3B] transition-colors bg-gray-50">
                    <div class="space-y-1 text-center">
                        <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                        <div class="flex text-sm text-gray-600">
                            <label for="attachments" class="relative cursor-pointer bg-white rounded-md font-medium text-[#005B9F] hover:text-[#008A3B] focus-within:outline-none">
                                <span>اختر الملفات من جهازك</span>
                                <input id="attachments" name="attachments[]" type="file" multiple class="sr-only">
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">PDF, PNG, JPG تصل إلى 10 ميجا بايت للملف</p>
                    </div>
                </div>
            </div>
            <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-4 flex items-start gap-3">
                <i class="fas fa-shield-alt text-[#005B9F] text-lg mt-0.5"></i>
                <div class="text-sm text-gray-700 leading-relaxed">
                    <span class="font-bold block text-gray-900 mb-1">ملاحظة أمنية وقانونية:</span>
                    يرجى التأكد من رفع صور ضوئية واضحة ومحدثة لضمان قانونية التعامل مع المورد وتجنب أي عقوبات قانونية أو غرامات أثناء الفحص الضريبي.
                </div>
            </div>
        </div>
    </div>

</div>

{{-- سكريبت إظهار حقل سبب الحظر عند اختيار "محظور" --}}
<script>
    function toggleBlockReason() {
        var statusSelect = document.getElementById('status');
        var reasonDiv = document.getElementById('block_reason_div');
        var reasonInput = document.getElementById('block_reason');

        if (statusSelect.value === 'blocked') {
            reasonDiv.classList.remove('hidden');
            reasonInput.required = true;
        } else {
            reasonDiv.classList.add('hidden');
            reasonInput.required = false;
        }
    }
    // تشغيل الدالة عند التحميل لضبط الحالة المحددة مسبقاً
    document.addEventListener('DOMContentLoaded', toggleBlockReason);
</script>
