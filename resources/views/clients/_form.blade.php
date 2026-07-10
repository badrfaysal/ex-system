@php $entity = $entity ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

    {{-- اسم الشركة (عربي) --}}
    <div>
        <label for="company_name" class="block text-sm font-semibold text-gray-700 mb-1.5">
            {{ __('messages.clients.f_company') }}
            <span class="text-gray-400 text-xs font-normal me-1">(عربي)</span>
            <span class="text-red-500">*</span>
        </label>
        <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $entity?->company_name) }}" required dir="rtl"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors bg-gray-50 focus:bg-white">
        @error('company_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    {{-- اسم الشركة (إنجليزي) --}}
    <div>
        <label for="company_name_en" class="block text-sm font-semibold text-gray-700 mb-1.5">
            {{ __('messages.clients.f_company') }}
            <span class="text-gray-400 text-xs font-normal me-1">(English)</span>
        </label>
        <input type="text" id="company_name_en" name="company_name_en" value="{{ old('company_name_en', $entity?->company_name_en) }}" dir="ltr"
            placeholder="Company name in English"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors bg-gray-50 focus:bg-white text-left">
        @error('company_name_en') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    {{-- الشخص المسؤول --}}
    <div>
        <label for="contact_person" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.clients.f_person') }}</label>
        <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person', $entity?->contact_person) }}"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors bg-gray-50 focus:bg-white">
        @error('contact_person') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    {{-- رقم الهاتف --}}
    <div>
        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.clients.f_phone') }} <span class="text-red-500">*</span></label>
        <div class="relative">
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                <i class="fas fa-phone-alt"></i>
            </div>
            <input type="tel" id="phone" name="phone" value="{{ old('phone', $entity?->phone) }}" required dir="ltr"
                class="w-full pr-11 pl-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] text-right transition-colors bg-gray-50 focus:bg-white">
        </div>
        @error('phone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    {{-- الايميل --}}
    <div>
        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.clients.f_email') }}</label>
        <div class="relative">
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                <i class="fas fa-envelope"></i>
            </div>
            <input type="email" id="email" name="email" value="{{ old('email', $entity?->email) }}" dir="ltr"
                class="w-full pr-11 pl-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] text-right transition-colors bg-gray-50 focus:bg-white">
        </div>
        @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    {{-- الدولة --}}
    <div>
        <label for="country" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.clients.f_country') }} <span class="text-red-500">*</span></label>
        <select id="country" name="country" required
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors bg-gray-50 focus:bg-white">
            <option value="" disabled {{ old('country', $entity?->country) ? '' : 'selected' }}>{{ __('messages.options.country.choose') }}</option>
            <option value="EG" {{ old('country', $entity?->country) == 'EG' ? 'selected' : '' }}>{{ __('messages.options.country.EG') }}</option>
            <option value="SA" {{ old('country', $entity?->country) == 'SA' ? 'selected' : '' }}>{{ __('messages.options.country.SA') }}</option>
            <option value="AE" {{ old('country', $entity?->country) == 'AE' ? 'selected' : '' }}>{{ __('messages.options.country.AE') }}</option>
        </select>
        @error('country') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    {{-- الرقم الضريبي --}}
    <div>
        <label for="tax_id" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.clients.f_tax') }}</label>
        <input type="text" id="tax_id" name="tax_id" value="{{ old('tax_id', $entity?->tax_id) }}"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors bg-gray-50 focus:bg-white">
        @error('tax_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    {{-- تصنيف العميل --}}
    <div>
  <label for="client_type" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.clients.f_type') }} <span class="text-red-500">*</span></label>
<select id="client_type" name="client_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
    <option value="" disabled selected>{{ __('messages.clients.f_type_ph') }}</option>
    @foreach($clientTypes as $type)
        <option value="{{ $type->key_value }}" 
            {{ old('client_type', $entity->client_type ?? '') == $type->key_value ? 'selected' : '' }}>
            {{ $type->display_name }}
        </option>
    @endforeach
</select>
        @error('client_type') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

    {{-- العنوان (يأخذ عرض الشاشة بالكامل) --}}
    <div class="md:col-span-2">
        <label for="address" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ __('messages.clients.f_address') }}</label>
        <textarea id="address" name="address" rows="3"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-[#008A3B] focus:ring-1 focus:ring-[#008A3B] transition-colors bg-gray-50 focus:bg-white">{{ old('address', $entity?->address) }}</textarea>
        @error('address') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>

</div>

{{-- قسم إعدادات المبيعات الافتراضية --}}
<div class="mt-8 pt-6 border-t border-gray-100">
    <div class="flex items-center gap-2 mb-5">
        <span class="w-1 h-5 bg-[#008A3B] rounded-full"></span>
        <h3 class="font-bold text-gray-800">إعدادات المبيعات الافتراضية</h3>
        <span class="text-xs text-gray-400 mr-1">(اختيارية — تُملأ تلقائياً في عروض الأسعار)</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- قائمة الأسعار الافتراضية --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                <i class="fas fa-tags text-[#005B9F] mr-1"></i> قائمة الأسعار الافتراضية
            </label>
            <select name="default_price_list_id" data-search
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                <option value="">— بدون قائمة افتراضية —</option>
                @foreach($priceLists as $pl)
                    <option value="{{ $pl->id }}"
                        {{ old('default_price_list_id', $entity?->default_price_list_id) == $pl->id ? 'selected' : '' }}>
                        {{ $pl->code }} — {{ $pl->name }}
                    </option>
                @endforeach
            </select>
            @error('default_price_list_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        {{-- مندوب المبيعات الافتراضي --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                <i class="fas fa-user-tie text-[#008A3B] mr-1"></i> مندوب المبيعات
            </label>
            <input type="text" name="default_sales_rep"
                value="{{ old('default_sales_rep', $entity?->default_sales_rep) }}"
                placeholder="اسم المندوب المسؤول عن هذا العميل"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B] bg-gray-50 focus:bg-white">
            @error('default_sales_rep') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        {{-- العملة الافتراضية --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                <i class="fas fa-coins text-amber-500 mr-1"></i> العملة الافتراضية
            </label>
            <select name="default_currency" data-search
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                <option value="">— بدون عملة افتراضية —</option>
                @foreach($currencies as $c)
                    <option value="{{ $c->key_value }}"
                        {{ old('default_currency', $entity?->default_currency) == $c->key_value ? 'selected' : '' }}>
                        {{ $c->display_name }}
                    </option>
                @endforeach
            </select>
            @error('default_currency') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>

    </div>
</div>
