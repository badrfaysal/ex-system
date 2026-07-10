@extends('layouts.app')
@section('header_title', __('messages.clients.title'))

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    {{-- ترويسة الشاشة وزر الإضافة --}}
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.clients.title') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('messages.clients.subtitle') }}</p>
            </div>
        </div>
        <a href="{{ route('clients.create') }}" class="px-6 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030] transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> {{ __('messages.clients.add') }}
        </a>
    </div>

    {{-- شريط الفلاتر الذكي (Filter Bar) --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('clients.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            
            {{-- نوع الفلتر --}}
            <div class="w-full sm:w-auto">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.filter.period') }}</label>
                <select name="date_filter" id="date_filter" onchange="toggleDateInputs()" class="w-full sm:w-48 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
                    <option value="">{{ __('messages.filter.all_times') }}</option>
                    <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>{{ __('messages.filter.yesterday') }}</option>
                    <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>{{ __('messages.filter.this_week') }}</option>
                    <option value="this_year" {{ request('date_filter') == 'this_year' ? 'selected' : '' }}>{{ __('messages.filter.this_year') }}</option>
                    <option value="specific" {{ request('date_filter') == 'specific' ? 'selected' : '' }}>{{ __('messages.filter.specific') }}</option>
                    <option value="range" {{ request('date_filter') == 'range' ? 'selected' : '' }}>{{ __('messages.filter.range_full') }}</option>
                </select>
            </div>

            {{-- يوم محدد (يظهر فقط عند اختياره) --}}
            <div id="specific_date_div" class="{{ request('date_filter') == 'specific' ? 'block' : 'hidden' }} w-full sm:w-auto transition-all">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.filter.choose_day') }}</label>
                <input type="date" name="specific_date" value="{{ request('specific_date') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B]">
            </div>

            {{-- نطاق زمني (يظهر فقط عند اختياره) --}}
            <div id="range_date_div" class="{{ request('date_filter') == 'range' ? 'flex' : 'hidden' }} flex-wrap gap-4 transition-all">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.filter.from_date') }}</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.filter.to_date') }}</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B]">
                </div>
            </div>

            {{-- أزرار البحث --}}
            <div class="flex gap-2 mr-auto">
                <a href="{{ route('clients.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-colors">{{ __('messages.filter.cancel') }}</a>
                <button type="submit" class="px-6 py-2 bg-[#005B9F] text-white rounded-lg text-sm font-bold hover:bg-blue-800 transition-colors flex items-center gap-2">
                    <i class="fas fa-filter"></i> {{ __('messages.common.apply') }}
                </button>
            </div>
        </form>
    </div>

    {{-- كارت الجدول --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        
        {{-- جلب الإعدادات من الكاش وتحويلها لخريطة (Key-Value) لضمان أداء O(1) --}}
        @php
            $lookups = \Illuminate\Support\Facades\Cache::remember('system_settings', 60*60*24, function () {
                return \App\Models\Setting::all()->groupBy('category');
            });
            
            // عمل KeyBy لتسهيل البحث بالـ key_value
            $clientTypesMap = $lookups->get('client_type') ? $lookups->get('client_type')->keyBy('key_value') : collect();
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ __('messages.clients.company') }}</th>
                        <th class="p-4">{{ __('messages.clients.type') }}</th>
                        <th class="p-4">{{ __('messages.clients.country') }}</th>
                        <th class="p-4">{{ __('messages.clients.added') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($clients as $client)
                        @php 
                            // جلب اسم التصنيف الديناميكي O(1)
                            $typeObj = $clientTypesMap->get($client->client_type);
                            $typeName = $typeObj ? $typeObj->display_name : __('messages.common.not_set');
                        @endphp
                        
                        {{-- جعل الصف قابل للضغط (Clickable Row) وتشغيل وظيفة فتح النافذة مع تمرير الاسم المترجم --}}
                        <tr onclick="openClientModal({{ json_encode($client) }}, '{{ $typeName }}')" class="hover:bg-green-50/50 cursor-pointer transition-colors group">
                            <td class="p-4 font-bold text-gray-900 group-hover:text-[#008A3B]">{{ $client->displayName() }}</td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-[#EBF7F0] text-[#008A3B]">
                                    {{ $typeName }}
                                </span>
                            </td>
                            <td class="p-4"><i class="fas fa-globe text-gray-300 ml-1"></i> {{ $client->country }}</td>
                            <td class="p-4 text-gray-500" dir="ltr">{{ $client->created_at->format('Y-m-d') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">{{ __('messages.clients.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($clients->hasPages()) <div class="p-4 border-t border-gray-100 bg-white">{{ $clients->links() }}</div> @endif
    </div>
</div>

{{-- هيكل النافذة المنبثقة (Modal) --}}
<div id="clientModal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl transform scale-95 transition-transform duration-300" id="modalContent">
        
        <div class="flex justify-between items-center p-6 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl">
            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-id-card text-[#008A3B]"></i> {{ __('messages.clients.card_title') }}
            </h3>
            <button onclick="closeClientModal()" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase">{{ __('messages.clients.m_company') }}</p>
                    <p class="text-lg font-bold text-gray-900 mt-1" id="m_company"></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase">{{ __('messages.clients.m_type') }}</p>
                    <p class="text-base font-bold text-[#005B9F] mt-1" id="m_type"></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase">{{ __('messages.clients.m_person') }}</p>
                    <p class="text-base text-gray-800 mt-1 flex items-center gap-2"><i class="fas fa-user-tie text-gray-300"></i> <span id="m_person"></span></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase">{{ __('messages.clients.m_phone') }}</p>
                    <p class="text-base text-gray-800 mt-1 font-mono" id="m_phone" dir="ltr"></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase">{{ __('messages.clients.m_email') }}</p>
                    <p class="text-base text-gray-800 mt-1 font-mono text-right" id="m_email"></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase">{{ __('messages.clients.m_tax') }}</p>
                    <p class="text-base text-gray-800 mt-1 font-mono" id="m_tax"></p>
                </div>
                <div class="md:col-span-2 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-2">{{ __('messages.clients.m_address') }}</p>
                    <p class="text-base text-gray-800 leading-relaxed"><span id="m_country" class="font-bold text-[#008A3B]"></span> - <span id="m_address"></span></p>
                </div>
            </div>
        </div>

        <div class="p-6 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl flex justify-end gap-3">
            <button onclick="closeClientModal()" class="px-5 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium">{{ __('messages.common.close') }}</button>
            <a href="#" id="m_edit_btn" class="px-5 py-2 bg-[#008A3B] text-white rounded-lg hover:bg-[#007030] font-bold flex items-center gap-2">
                <i class="fas fa-pen"></i> {{ __('messages.clients.edit_data') }}
            </a>
        </div>
    </div>
</div>

<script>
    // 1. التحكم في إظهار وإخفاء حقول التاريخ الإضافية
    function toggleDateInputs() {
        const filter = document.getElementById('date_filter').value;
        const specificDiv = document.getElementById('specific_date_div');
        const rangeDiv = document.getElementById('range_date_div');

        specificDiv.classList.add('hidden');
        rangeDiv.classList.add('hidden');
        rangeDiv.classList.remove('flex');

        if (filter === 'specific') {
            specificDiv.classList.remove('hidden');
        } else if (filter === 'range') {
            rangeDiv.classList.remove('hidden');
            rangeDiv.classList.add('flex');
        }
    }

    // 2. دوال النافذة المنبثقة (Modal)
    const modal = document.getElementById('clientModal');
    const modalContent = document.getElementById('modalContent');

    // الدالة الآن تستقبل (client) وتستقبل (typeName) الذي تمت ترجمته مسبقاً في الـ Blade
    function openClientModal(client, typeName) {
        document.getElementById('m_company').innerText = client.company_name;
        document.getElementById('m_person').innerText = client.contact_person || @json(__('messages.common.not_set'));
        document.getElementById('m_phone').innerText = client.phone;
        document.getElementById('m_email').innerText = client.email || @json(__('messages.common.not_reg'));
        document.getElementById('m_tax').innerText = client.tax_id || @json(__('messages.common.not_reg'));
        document.getElementById('m_country').innerText = client.country;
        document.getElementById('m_address').innerText = client.address || @json(__('messages.common.none'));
        
        // وضع الاسم الديناميكي في النافذة
        document.getElementById('m_type').innerText = typeName;

        // تحديث الرابط الخاص بزر "تعديل البيانات" داخل النافذة ليوجه لملف هذا العميل تحديداً
        document.getElementById('m_edit_btn').href = `clients/${client.id}/edit`;

        // إظهار النافذة مع التأثيرات
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modalContent.classList.remove('scale-95');
        }, 10);
    }

    function closeClientModal() {
        modal.classList.add('opacity-0');
        modalContent.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            closeClientModal();
        }
    }
</script>
@endsection