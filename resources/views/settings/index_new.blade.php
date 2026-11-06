@extends('layouts.app')
@section('header_title', __('messages.settings.header'))

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">

    {{-- ترويسة الشاشة --}}
    <div class="mb-8 border-b border-gray-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-[#005B9F] text-white flex items-center justify-center shadow-md">
                <i class="fas fa-sliders-h text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ __('messages.settings.title') }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ __('messages.settings.subtitle') }}</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-[#008A3B] text-green-800 px-4 py-3 shadow-sm flex items-center gap-3">
            <i class="fas fa-check-circle text-[#008A3B] text-lg"></i>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif

    @php
        $settingsMenu = [
            __('messages.settings.grp_clients') => [
                'client_type'    => ['icon' => 'fa-users'],
                'country'        => ['icon' => 'fa-globe'],
            ],
            __('messages.settings.grp_vendors') => [
                'vendor_group'   => ['icon' => 'fa-truck'],
                'vendor_status'  => ['icon' => 'fa-user-shield'],
                'currency'       => ['icon' => 'fa-money-bill-wave'],
                'payment_method' => ['icon' => 'fa-credit-card'],
            ],
            __('messages.settings.grp_items') => [
                'item_group'        => ['icon' => 'fa-boxes'],
                'item_sub_category' => ['icon' => 'fa-sitemap'],
                'uom'               => ['icon' => 'fa-weight-hanging'],
                'item_status'       => ['icon' => 'fa-tags'],
            ],
            'النظام والمستخدمون' => [
                'users'         => ['icon' => 'fa-user-cog', 'custom' => true],
                'notify_emails' => ['icon' => 'fa-bell', 'custom' => true],
            ],
            'المالية والحسابات' => [
                'wallets'          => ['icon' => 'fa-wallet', 'custom' => true, 'title' => 'الحسابات البنكية والصناديق المالية'],
                'doc_bank_info'    => ['icon' => 'fa-university', 'custom' => true, 'title' => 'بيانات البنك بالمستندات'],
                'other_service'    => ['icon' => 'fa-concierge-bell', 'custom' => true, 'title' => 'خدمات أخرى (الشهادات والخدمات)'],
                'expense_category' => ['icon' => 'fa-sitemap', 'custom' => true, 'title' => 'دليل بنود المصروفات (شجرة الحسابات)'],
            ],
        ];
        $allUsers          = \App\Models\User::orderBy('name')->get();
        $notifyEmails      = \App\Models\Setting::where('category', 'notify_email')->orderBy('display_name')->get();
        $docBankInfo       = \App\Models\Setting::documentBankDetails();
        $otherServicesList = \App\Models\Setting::getOtherServices();
    @endphp

    <div class="flex flex-col md:flex-row gap-8 items-start">
        
        {{-- القائمة الجانبية للإعدادات --}}
        <div class="w-full md:w-1/4 shrink-0 bg-white border border-gray-200 rounded-lg shadow-sm p-4 sticky top-24">
            @foreach($settingsMenu as $groupName => $items)
                <div class="mb-6 last:mb-0">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 px-2">{{ $groupName }}</h4>
                    <ul class="space-y-1">
                        @foreach($items as $key => $data)
                            <li>
                                <button type="button" onclick="switchTab('{{ $key }}')" id="btn-{{ $key }}"
                                    class="tab-btn w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-[#005B9F] transition-all text-right">
                                    <i class="fas {{ $data['icon'] }} w-5 text-center opacity-70"></i>
                                    <span>{{ isset($data['title']) ? $data['title'] : __('messages.settings.lk.'.$key.'.title') }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="mt-8 pt-6 border-t border-gray-200">
                <button type="button" onclick="confirmReset()" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-md text-sm font-bold text-red-600 bg-red-50 hover:bg-red-100 hover:text-red-700 transition-colors border border-red-200">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>تصفير قاعدة البيانات</span>
                </button>
            </div>
        </div>

        <form id="resetDatabaseForm" action="{{ route('settings.reset-database') }}" method="POST" class="hidden">
            @csrf
            <input type="hidden" name="confirm_text" id="confirmTextInput">
        </form>

        <script>
        function confirmReset() {
            if (confirm('تنبيه خطير! هل أنت متأكد من رغبتك في حذف جميع بيانات النظام (فواتير، عملاء، موردين، أصناف، سندات، إلخ)؟ سيتم الاحتفاظ بالمستخدمين والإعدادات الأساسية فقط.')) {
                let text = prompt('لتأكيد عملية الحذف النهائية، يرجى كتابة كلمة: تصفير');
                if (text === 'تصفير') {
                    document.getElementById('confirmTextInput').value = text;
                    document.getElementById('resetDatabaseForm').submit();
                } else if (text !== null) {
                    alert('الكلمة غير مطابقة، تم إلغاء العملية.');
                }
            }
        }
        </script>

        {{-- مساحة عرض المحتوى --}}
        <div class="w-full md:w-3/4 bg-white border border-gray-200 rounded-lg shadow-sm min-h-[500px]">
            @foreach($settingsMenu as $groupName => $items)
                @foreach($items as $key => $data)
                @if(!empty($data['custom'])) @continue @endif
                    <div id="panel-{{ $key }}" class="tab-panel hidden p-6 animate-fade-in">
                        
                        <div class="border-b border-gray-100 pb-4 mb-6">
                            <h3 class="text-xl font-bold text-[#005B9F] flex items-center gap-2">
                                <i class="fas {{ $data['icon'] }}"></i> {{ __('messages.settings.lk.'.$key.'.title') }}
                            </h3>
                            <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">{{ __('messages.settings.lk.'.$key.'.desc') }}</p>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-8">
                            @if($key === 'item_sub_category')
                            {{-- ===== فورم المجموعات الفرعية (multi-parent + multi-name) ===== --}}
                            @php $parentGroups = $settings->get('item_group') ?? collect(); @endphp
                            <form action="{{ route('settings.store') }}" method="POST" class="flex flex-col gap-4">
                                @csrf
                                <input type="hidden" name="category" value="{{ $key }}">

                                {{-- اختيار المجموعات الرئيسية (متعددة) --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                        <i class="fas fa-link text-[#005B9F] text-xs me-1"></i>
                                        {{ __('messages.settings.parent_group') }}
                                        <span class="text-gray-400 font-normal text-[11px] ms-1">(يمكن اختيار أكثر من مجموعة)</span>
                                    </label>
                                    <select name="parent_keys[]" id="parentKeysSelect" multiple
                                        class="w-full border border-gray-300 rounded bg-white focus:outline-none text-sm">
                                        @foreach($parentGroups as $pg)
                                            <option value="{{ $pg->key_value }}">{{ $pg->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- الأسماء (متعددة — صفوف ديناميكية) --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                        {{ __('messages.settings.add_new') }}
                                        <span class="text-gray-400 font-normal text-[11px] ms-1">(يمكن إضافة أكثر من مجموعة في المرة الواحدة)</span>
                                    </label>
                                    <div id="subcatNameRows" class="flex flex-col gap-2">
                                        <div class="subcat-row flex gap-2 items-center">
                                            <input type="text" name="display_names[]" required
                                                placeholder="{{ __('messages.settings.item_ph') }}"
                                                class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] bg-white">
                                            <button type="button" onclick="removeSubcatRow(this)"
                                                class="w-8 h-8 flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 rounded transition-colors flex-shrink-0" title="حذف السطر">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addSubcatRow()"
                                        class="mt-2 inline-flex items-center gap-1.5 text-xs text-[#005B9F] font-bold hover:underline">
                                        <i class="fas fa-plus text-[10px]"></i> إضافة سطر آخر
                                    </button>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="px-6 py-2 bg-[#005B9F] text-white text-sm font-bold rounded hover:bg-[#004680] transition-colors flex items-center gap-2">
                                        <i class="fas fa-save"></i> {{ __('messages.settings.save_item') }}
                                    </button>
                                </div>
                            </form>

                            @else
                            {{-- ===== فورم عادي لبقية الأنواع ===== --}}
                            <form action="{{ route('settings.store') }}" method="POST" class="flex flex-col gap-3">
                                @csrf
                                <input type="hidden" name="category" value="{{ $key }}">
                                <div class="flex flex-col sm:flex-row items-end gap-4">
                                    <div class="flex-1 w-full">
                                        <label class="block text-xs font-bold text-gray-700 mb-1.5">{{ __('messages.settings.add_new') }}</label>
                                        <input type="text" name="display_name" required placeholder="{{ __('messages.settings.item_ph') }}"
                                            class="w-full px-4 py-2 text-sm border border-gray-300 rounded focus:outline-none focus:border-[#005B9F] focus:ring-1 focus:ring-[#005B9F] bg-white">
                                    </div>
                                    <button type="submit" class="px-6 py-2 bg-[#005B9F] text-white text-sm font-bold rounded hover:bg-[#004680] transition-colors flex items-center gap-2 h-[38px]">
                                        <i class="fas fa-plus"></i> {{ __('messages.settings.save_item') }}
                                    </button>
                                </div>
                            </form>
                            @endif
                        </div>

                        <div class="border border-gray-200 rounded overflow-hidden">
                            <table class="w-full text-right text-sm">
                                <thead class="bg-gray-100 text-gray-700">
                                    <tr>
                                        <th class="p-3 font-bold border-b border-gray-200 w-12 text-center">#</th>
                                        <th class="p-3 font-bold border-b border-gray-200">{{ __('messages.settings.col_display') }}</th>
                                        @if($key === 'item_sub_category')
                                        <th class="p-3 font-bold border-b border-gray-200">{{ __('messages.settings.col_parent') }}</th>
                                        @endif
                                        <th class="p-3 font-bold border-b border-gray-200 w-20 text-center">{{ __('messages.settings.col_action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @php
                                        $settingItems = $settings->get($key) ?? collect();
                                        $parentGroupsMap = ($settings->get('item_group') ?? collect())->keyBy('key_value');
                                    @endphp

                                    @forelse($settingItems as $index => $item)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="p-3 text-center text-gray-400">{{ $index + 1 }}</td>
                                            <td class="p-3 font-bold text-gray-900">{{ $item->display_name }}</td>
                                            @if($key === 'item_sub_category')
                                            <td class="p-3">
                                                @php
                                                    $parentKeys = json_decode($item->parent_key, true);
                                                    if (!is_array($parentKeys)) {
                                                        $parentKeys = $item->parent_key ? [$item->parent_key] : [];
                                                    }
                                                @endphp
                                                @if(count($parentKeys))
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($parentKeys as $pk)
                                                            @if($parentGroupsMap->has($pk))
                                                                <span class="inline-flex items-center gap-1 text-xs font-bold bg-[#005B9F]/10 text-[#005B9F] px-2 py-0.5 rounded-full">
                                                                    <i class="fas fa-tag text-[9px]"></i>
                                                                    {{ $parentGroupsMap->get($pk)->display_name }}
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-xs text-gray-400 italic">{{ __('messages.settings.no_parent') }}</span>
                                                @endif
                                            </td>
                                            @endif
                                            <td class="p-3 text-center">
                                                <form action="{{ route('settings.destroy', $item->id) }}" method="POST" onsubmit="return confirm('{{ __('messages.common.confirm_del') }}');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-gray-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded transition-colors" title="{{ __('messages.common.delete') }}">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $key === 'item_sub_category' ? 4 : 3 }}" class="p-10 text-center text-gray-500 bg-gray-50/50">
                                                <i class="fas fa-inbox text-3xl text-gray-300 mb-3 block"></i>
                                                {{ __('messages.settings.empty') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                @endforeach
            @endforeach

            {{-- ===== panel المستخدمين (custom) ===== --}}
            <div id="panel-users" class="tab-panel hidden p-6 animate-fade-in">

                <div class="border-b border-gray-100 pb-4 mb-6">
                    <h3 class="text-xl font-bold text-[#005B9F] flex items-center gap-2">
                        <i class="fas fa-user-cog"></i> إدارة المستخدمين
                    </h3>
                    <p class="text-sm text-gray-500 mt-1.5">إضافة مستخدمين جدد أو تعديل بيانات المستخدمين الحاليين.</p>
                </div>

                @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
                @endif

                {{-- إضافة مستخدم جديد --}}
                <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 mb-8">
                    <p class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-user-plus text-[#005B9F]"></i> إضافة مستخدم جديد
                    </p>
                    <form action="{{ route('users.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">الاسم الكامل</label>
                            <input type="text" name="name" required placeholder="أحمد محمد"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني</label>
                            <input type="email" name="email" required placeholder="user@example.com" dir="ltr"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">كلمة المرور</label>
                            <input type="password" name="password" required placeholder="6 أحرف على الأقل"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">تأكيد كلمة المرور</label>
                            <input type="password" name="password_confirmation" required placeholder="أعد كتابة كلمة المرور"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F]">
                        </div>
                        <div class="sm:col-span-2 flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-[#005B9F] text-white text-sm font-bold rounded-lg hover:bg-[#004680] flex items-center gap-2">
                                <i class="fas fa-save"></i> حفظ المستخدم
                            </button>
                        </div>
                    </form>
                </div>

                {{-- قائمة المستخدمين الحاليين --}}
                <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-users text-gray-400"></i> المستخدمون الحاليون
                </p>
                <div class="space-y-3">
                    @forelse($allUsers as $usr)
                    <div class="bg-white border border-gray-200 rounded-xl p-4">

                        {{-- رأس بطاقة المستخدم --}}
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-[#005B9F] flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                {{ mb_substr($usr->name, 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-gray-900 text-sm">{{ $usr->name }}</p>
                                <p class="text-xs text-gray-400 font-mono" dir="ltr">{{ $usr->email }}</p>
                            </div>
                            @if($usr->id === auth()->id())
                            <span class="text-[10px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full font-bold border border-green-200">أنت</span>
                            @endif
                        </div>

                        {{-- فورم التعديل --}}
                        <form action="{{ route('users.update', $usr) }}" method="POST"
                            class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @csrf @method('PUT')
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">الاسم الكامل</label>
                                <input type="text" name="name" value="{{ $usr->name }}" required
                                    class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#005B9F] bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">البريد الإلكتروني</label>
                                <input type="email" name="email" value="{{ $usr->email }}" required dir="ltr"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#005B9F] bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">
                                    كلمة مرور جديدة
                                    <span class="text-gray-400 font-normal">(اتركها فارغة للإبقاء على الحالية)</span>
                                </label>
                                <input type="password" name="password" placeholder="••••••" autocomplete="new-password"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#005B9F] bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-500 mb-1">تأكيد كلمة المرور</label>
                                <input type="password" name="password_confirmation" placeholder="••••••" autocomplete="new-password"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#005B9F] bg-gray-50">
                            </div>
                            <div class="sm:col-span-2 flex items-center justify-between pt-1">
                                <button type="submit"
                                    class="px-4 py-1.5 bg-[#008A3B] text-white text-xs font-bold rounded-lg hover:bg-[#007030] flex items-center gap-1.5 transition-colors">
                                    <i class="fas fa-save"></i> حفظ التعديلات
                                </button>
                            </div>
                        </form>

                        {{-- زر الحذف — خارج فورم التعديل تماماً --}}
                        @if($usr->id !== auth()->id())
                        <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end">
                            <form action="{{ route('users.destroy', $usr) }}" method="POST"
                                onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="px-3 py-1.5 text-red-500 hover:bg-red-50 text-xs font-bold rounded-lg flex items-center gap-1.5 border border-red-200 transition-colors">
                                    <i class="fas fa-user-times"></i> حذف المستخدم
                                </button>
                            </form>
                        </div>
                        @endif

                    </div>
                    @empty
                    <div class="text-center py-10 text-gray-400">
                        <i class="fas fa-users text-3xl mb-3 block"></i>
                        <p class="text-sm">لا يوجد مستخدمون بعد</p>
                    </div>
                    @endforelse
                </div>

            </div>

            {{-- ===== panel إشعارات الإدارة (custom) ===== --}}
            <div id="panel-notify_emails" class="tab-panel hidden p-6 animate-fade-in">

                <div class="border-b border-gray-100 pb-4 mb-6">
                    <h3 class="text-xl font-bold text-[#005B9F] flex items-center gap-2">
                        <i class="fas fa-bell"></i> إشعارات الإدارة
                    </h3>
                    <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                        أضف بريد المديرين وأصحاب الشركة. عند إرسال أي عرض سعر لعميل، تُرسَل نسخة (CC) لهؤلاء تلقائياً لإبلاغهم.
                    </p>
                </div>

                @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
                @endif

                {{-- إضافة بريد جديد --}}
                <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 mb-8">
                    <p class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-[#005B9F]"></i> إضافة بريد للإشعارات
                    </p>
                    <form action="{{ route('settings.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @csrf
                        <input type="hidden" name="category" value="notify_email">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">الاسم / المنصب <span class="text-gray-400 font-normal">(اختياري)</span></label>
                            <input type="text" name="name" placeholder="مثال: المدير العام"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني <span class="text-red-500">*</span></label>
                            <input type="email" name="email" required placeholder="manager@example.com" dir="ltr"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] text-left">
                        </div>
                        <div class="sm:col-span-2 flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-[#005B9F] text-white text-sm font-bold rounded-lg hover:bg-[#004680] flex items-center gap-2">
                                <i class="fas fa-save"></i> إضافة
                            </button>
                        </div>
                    </form>
                </div>

                {{-- قائمة الإيميلات الحالية --}}
                <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-paper-plane text-gray-400"></i> قائمة المستلمين الحاليين
                </p>
                <div class="space-y-2">
                    @forelse($notifyEmails as $ne)
                    <div class="bg-white border border-gray-200 rounded-xl p-3 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                            <i class="fas fa-user-tie text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 text-sm truncate">{{ $ne->display_name }}</p>
                            <p class="text-xs text-gray-400 font-mono truncate" dir="ltr">{{ $ne->key_value }}</p>
                        </div>
                        <form action="{{ route('settings.destroy', $ne->id) }}" method="POST"
                            onsubmit="return confirm('إزالة هذا البريد من قائمة الإشعارات؟')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="px-3 py-1.5 text-red-500 hover:bg-red-50 text-xs font-bold rounded-lg flex items-center gap-1.5 border border-red-200 transition-colors flex-shrink-0">
                                <i class="fas fa-trash-alt"></i> حذف
                            </button>
                        </form>
                    </div>
                    @empty
                    <div class="text-center py-10 text-gray-400">
                        <i class="fas fa-bell-slash text-3xl mb-3 block"></i>
                        <p class="text-sm">لا توجد إيميلات للإشعارات بعد</p>
                    </div>
                    @endforelse
                </div>

            </div>

            {{-- ===== panel الحسابات البنكية والصناديق المالية (custom) ===== --}}
            <div id="panel-wallets" class="tab-panel hidden p-6 animate-fade-in">

                <div class="border-b border-gray-100 pb-4 mb-6 flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold text-[#008A3B] flex items-center gap-2">
                            <i class="fas fa-wallet"></i> إعدادات الحسابات البنكية والصناديق المالية
                        </h3>
                        <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                            أضف حسابات مالية (مثل: بنك، خزينة، عهدة) مع رصيد أول المدة.
                        </p>
                    </div>
                </div>

                @if($errors->any() && session('activeSettingTab') === 'wallets')
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
                </div>
                @endif

                {{-- إضافة حساب جديد --}}
                <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 mb-8">
                    <p class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-[#008A3B]"></i> إضافة حساب جديد
                    </p>
                    <form action="{{ route('wallets.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" onsubmit="sessionStorage.setItem('activeSettingTab', 'wallets');">
                        @csrf
                        <input type="hidden" name="type" value="bank"> {{-- افتراضي لأنها مش هامة --}}

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">اسم الحساب / الخزينة <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="مثال: خزينة الشركة الرئيسية"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">العملة <span class="text-red-500">*</span></label>
                            <select name="currency" required
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:outline-none focus:border-[#008A3B]">
                                @forelse($settings->get('currency') ?? [] as $c)
                                    <option value="{{ $c->key_value }}" {{ old('currency', 'EGP') == $c->key_value ? 'selected' : '' }}>{{ $c->key_value }} — {{ $c->display_name }}</option>
                                @empty
                                    <option value="EGP" selected>EGP</option>
                                @endforelse
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">رصيد أول المدة <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance') }}" required placeholder="0.00" dir="ltr"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full py-2 bg-[#008A3B] text-white text-sm font-bold rounded-lg hover:bg-[#007030] flex items-center justify-center gap-2 h-[38px]">
                                <i class="fas fa-save"></i> إضافة الحساب
                            </button>
                        </div>

                        <p class="sm:col-span-2 lg:col-span-4 text-[11px] text-amber-600 flex items-start gap-1.5 -mt-1">
                            <i class="fas fa-circle-info mt-0.5"></i>
                            <span>الحساب يعمل بعملة واحدة فقط. لن تُقبل أي حركة (قبض / دفع / مصروف / إيراد / تحويل) على هذا الحساب إلا بنفس العملة المختارة هنا.</span>
                        </p>
                    </form>
                </div>

                {{-- قائمة الحسابات الحالية --}}
                <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-list text-gray-400"></i> قائمة الحسابات الحالية
                </p>
                <div class="border border-gray-200 rounded overflow-hidden">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="p-3 font-bold border-b border-gray-200">الاسم</th>
                                <th class="p-3 font-bold border-b border-gray-200 w-24 text-center">العملة</th>
                                <th class="p-3 font-bold border-b border-gray-200">رصيد أول المدة</th>
                                <th class="p-3 font-bold border-b border-gray-200">الرصيد الحالي</th>
                                <th class="p-3 font-bold border-b border-gray-200 w-20 text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($wallets as $wallet)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-3 font-bold text-gray-900">{{ $wallet->name }}</td>
                                    <td class="p-3 text-center"><span class="inline-block px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-xs font-mono font-bold">{{ $wallet->currency }}</span></td>
                                    <td class="p-3 font-mono text-gray-600" dir="ltr">{{ number_format($wallet->opening_balance, 2) }}</td>
                                    <td class="p-3 font-black text-[#005B9F]" dir="ltr">{{ number_format($wallet->current_balance, 2) }}</td>
                                    <td class="p-3 text-center">
                                        <form action="{{ route('wallets.destroy', $wallet->id ?? 0) }}" method="POST" onsubmit="sessionStorage.setItem('activeSettingTab', 'wallets'); return confirm('تأكيد الحذف؟');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded transition-colors" title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-10 text-center text-gray-500 bg-gray-50/50">
                                        <i class="fas fa-wallet text-3xl text-gray-300 mb-3 block"></i>
                                        لا توجد محافظ مسجلة بعد.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            {{-- ===== panel بيانات البنك بالمستندات (custom) ===== --}}
            <div id="panel-doc_bank_info" class="tab-panel hidden p-6 animate-fade-in">

                <div class="border-b border-gray-100 pb-4 mb-6">
                    <h3 class="text-xl font-bold text-[#005B9F] flex items-center gap-2">
                        <i class="fas fa-university"></i> بيانات البنك وملاحظات المستندات
                    </h3>
                    <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                        تُعرض هذه البيانات تلقائياً في أسفل <strong>عروض الأسعار</strong> و<strong>أوامر البيع</strong> و<strong>فواتير البيع</strong> (في شاشة العرض وملفات الطباعة والـ PDF).
                    </p>
                </div>

                <form action="{{ route('settings.store') }}" method="POST" class="space-y-5 max-w-3xl" onsubmit="sessionStorage.setItem('activeSettingTab', 'doc_bank_info');">
                    @csrf
                    <input type="hidden" name="category" value="doc_bank_info">

                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-200 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">
                                <i class="fas fa-pallet text-[#008A3B] me-1"></i> ملاحظة أعلى بيانات البنك (اختياري)
                            </label>
                            <input type="text" name="note" value="{{ old('note', $docBankInfo['note']) }}" dir="ltr"
                                placeholder="مثال: The price includes pallets."
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white font-semibold">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-200">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1">Beneficiary Name (اسم المستفيد / الشركة)</label>
                                <input type="text" name="beneficiary_name" value="{{ old('beneficiary_name', $docBankInfo['beneficiary_name']) }}" dir="ltr"
                                    placeholder="مثال: Ismailia National Company for food industries (FOODICO)"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Bank Name (اسم البنك)</label>
                                <input type="text" name="bank_name" value="{{ old('bank_name', $docBankInfo['bank_name']) }}" dir="ltr"
                                    placeholder="مثال: QNB Al AHLI"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Swift Code (سويفت كود)</label>
                                <input type="text" name="swift_code" value="{{ old('swift_code', $docBankInfo['swift_code']) }}" dir="ltr"
                                    placeholder="مثال: QNBAEGCxxxx"
                                    class="w-full px-3 py-2 text-sm font-mono border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">IBAN (رقم الآيبان)</label>
                                <input type="text" name="iban" value="{{ old('iban', $docBankInfo['iban']) }}" dir="ltr"
                                    placeholder="مثال: EG680037070808402031980204271"
                                    class="w-full px-3 py-2 text-sm font-mono border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Acc # USD (رقم الحساب بالدولار)</label>
                                <input type="text" name="acc_usd" value="{{ old('acc_usd', $docBankInfo['acc_usd']) }}" dir="ltr"
                                    placeholder="مثال: 20319802042-71"
                                    class="w-full px-3 py-2 text-sm font-mono border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>
                        </div>
                    </div>

                    {{-- معاينة لشكل الظهور في المستندات --}}
                    <div class="border border-dashed border-gray-300 rounded-xl p-4 bg-white" dir="ltr">
                        <p class="text-[11px] text-gray-400 font-bold mb-2 text-right" dir="rtl">معاينة الشكل في المستندات:</p>
                        @if(array_filter($docBankInfo))
                        <div class="text-xs text-gray-900 space-y-1 font-sans">
                            @if(!empty($docBankInfo['note']))
                                <p class="font-bold mb-2">{{ $docBankInfo['note'] }}</p>
                            @endif
                            @if(!empty($docBankInfo['beneficiary_name']))
                                <p><span class="font-bold">Beneficiary Name:</span> {{ $docBankInfo['beneficiary_name'] }}</p>
                            @endif
                            @if(!empty($docBankInfo['bank_name']))
                                <p><span class="font-bold">Bank Name:</span> {{ $docBankInfo['bank_name'] }}</p>
                            @endif
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                @if(!empty($docBankInfo['iban']))
                                    <p><span class="font-bold">IBAN:</span> <span class="font-mono">{{ $docBankInfo['iban'] }}</span></p>
                                @endif
                                @if(!empty($docBankInfo['acc_usd']))
                                    <p><span class="font-bold">Acc # USD:</span> <span class="font-mono">{{ $docBankInfo['acc_usd'] }}</span></p>
                                @endif
                            </div>
                            @if(!empty($docBankInfo['swift_code']))
                                <p><span class="font-bold">Swift Code:</span> <span class="font-mono">{{ $docBankInfo['swift_code'] }}</span></p>
                            @endif
                        </div>
                        @else
                        <div class="text-center py-4 text-xs text-gray-400 font-sans" dir="rtl">
                            <i class="fas fa-info-circle text-gray-300 mb-1 block text-base"></i>
                            لم يتم إدخال بيانات بنكية حتى الآن. أدخل بياناتك أعلاه واضغط "حفظ" لتظهر تلقائياً في فواتيرك وعروض الأسعار.
                        </div>
                        @endif
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2.5 bg-[#005B9F] text-white text-sm font-bold rounded-lg hover:bg-[#004680] transition-colors flex items-center gap-2 shadow-sm">
                            <i class="fas fa-save"></i> حفظ بيانات البنك
                        </button>
                    </div>
                </form>

            </div>

            {{-- ===== panel خدمات أخرى (custom) ===== --}}
            <div id="panel-other_service" class="tab-panel hidden p-6 animate-fade-in">

                <div class="border-b border-gray-100 pb-4 mb-6">
                    <h3 class="text-xl font-bold text-[#005B9F] flex items-center gap-2">
                        <i class="fas fa-concierge-bell"></i> خدمات أخرى (الشهادات والخدمات الخاصة)
                    </h3>
                    <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                        تُستخدم هذه الخدمات لإضافتها كبنود خاصة في <strong>عروض الأسعار</strong> و<strong>أوامر البيع</strong> و<strong>فواتير البيع</strong> (مثل الشهادات، خدمة الحراسة، التغليف والتجهيز) مع دعم كامل للغتين العربية والإنجليزية.
                    </p>
                </div>

                {{-- فورم إضافة خدمة جديدة --}}
                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200 mb-8">
                    <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-[#008A3B]"></i> إضافة خدمة خاصة جديدة
                    </h4>
                    <form action="{{ route('settings.store') }}" method="POST" class="space-y-4" onsubmit="sessionStorage.setItem('activeSettingTab', 'other_service');">
                        @csrf
                        <input type="hidden" name="category" value="other_service">

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">اسم الخدمة (بالعربي) <span class="text-red-500">*</span></label>
                                <input type="text" name="name_ar" required placeholder="مثال: خدمة حراسة"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Service Name (English)</label>
                                <input type="text" name="name_en" dir="ltr" placeholder="e.g. Guarding Service"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">كود الخدمة (اختياري)</label>
                                <input type="text" name="code" dir="ltr" placeholder="مثال: SRV-GUARD أو CERT"
                                    class="w-full px-3 py-2 text-sm font-mono uppercase border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">السعر الافتراضي (اختياري)</label>
                                <input type="number" step="0.01" min="0" name="default_price" dir="ltr" placeholder="سعر ثابت أو اتركه فارغاً"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="px-5 py-2 bg-[#008A3B] text-white text-sm font-bold rounded-lg hover:bg-[#007030] transition-colors flex items-center gap-2 shadow-sm">
                                <i class="fas fa-plus"></i> إضافة الخدمة
                            </button>
                        </div>
                    </form>
                </div>

                {{-- جدول الخدمات الحالية --}}
                <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-gray-100 text-gray-700 border-b border-gray-200">
                            <tr>
                                <th class="p-3 font-bold w-12 text-center">#</th>
                                <th class="p-3 font-bold">الكود</th>
                                <th class="p-3 font-bold">اسم الخدمة (عربي)</th>
                                <th class="p-3 font-bold">Service Name (EN)</th>
                                <th class="p-3 font-bold text-center">السعر الافتراضي</th>
                                <th class="p-3 font-bold w-20 text-center">حذف</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($otherServicesList as $idx => $srv)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-3 text-center text-gray-400 font-bold">{{ $idx + 1 }}</td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-slate-100 text-slate-800 border border-slate-300" dir="ltr">
                                            {{ $srv['code'] }}
                                        </span>
                                    </td>
                                    <td class="p-3 font-bold text-gray-900">{{ $srv['name_ar'] }}</td>
                                    <td class="p-3 font-medium text-gray-700 font-sans" dir="ltr">{{ $srv['name_en'] }}</td>
                                    <td class="p-3 text-center text-gray-600 font-mono">
                                        @if(!empty($srv['default_price']))
                                            {{ number_format((float) $srv['default_price'], 2) }}
                                        @else
                                            <span class="text-xs text-gray-400 italic">يدوي عند الإدخال</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        @if(!empty($srv['id']))
                                        <form action="{{ route('settings.destroy', $srv['id']) }}" method="POST"
                                            onsubmit="sessionStorage.setItem('activeSettingTab', 'other_service'); return confirm('هل أنت متأكد من حذف هذه الخدمة؟');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded transition-colors" title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                        @else
                                        <span class="text-[11px] text-gray-400">افتراضي</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-gray-500 bg-gray-50/50">
                                        <i class="fas fa-concierge-bell text-3xl text-gray-300 mb-3 block"></i>
                                        لا توجد خدمات مضافة حتى الآن.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            {{-- ===== panel بنود المصروفات وشجرة الحسابات (custom) ===== --}}
            <div id="panel-expense_category" class="tab-panel hidden p-6 animate-fade-in">

                @php
                    $allExpenseCats = $settings->get('expense_category') ?? collect();
                    $expenseParents = $allExpenseCats->filter(fn($a) => empty($a->parent_key))->sortBy('code');
                    $expenseChildren = $allExpenseCats->filter(fn($a) => !empty($a->parent_key))->sortBy('code');
                    $parentMap = $expenseParents->keyBy('key_value');
                    foreach ($expenseParents as $p) {
                        if ($p->code && !$parentMap->has($p->code)) {
                            $parentMap->put($p->code, $p);
                        }
                    }
                    $postableCount = $allExpenseCats->where('allow_posting', true)->count();
                @endphp

                <div class="border-b border-gray-100 pb-4 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-[#005B9F] flex items-center gap-2">
                            <i class="fas fa-sitemap"></i> دليل بنود المصروفات (شجرة الحسابات)
                        </h3>
                        <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                            هيكلة الحسابات وبنود التكاليف والمصروفات وفق شجرة الحسابات المالية: كود الحساب، والاسم بالعربي والإنجليزي، والحساب الأب، والتصنيف، والطبيعة، وقابلية الترحيل.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" onclick="openExpenseForm('child')" class="px-4 py-2 bg-[#008A3B] text-white text-xs font-bold rounded-lg hover:bg-[#007030] transition-colors flex items-center gap-1.5 shadow-sm">
                            <i class="fas fa-plus"></i> إضافة بند فرعي
                        </button>
                        <button type="button" onclick="openExpenseForm('parent')" class="px-3.5 py-2 bg-[#005B9F] text-white text-xs font-bold rounded-lg hover:bg-[#004680] transition-colors flex items-center gap-1.5 shadow-sm">
                            <i class="fas fa-folder-plus"></i> إضافة فئة / حساب أب
                        </button>
                    </div>
                </div>

                {{-- إحصائيات سريعة --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-50 to-white p-4 rounded-xl border border-blue-100 shadow-xs flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-500 font-semibold block">إجمالي الحسابات</span>
                            <span class="text-2xl font-black text-[#005B9F]">{{ $allExpenseCats->count() }}</span>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
                            <i class="fas fa-list-ol text-lg"></i>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-indigo-50 to-white p-4 rounded-xl border border-indigo-100 shadow-xs flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-500 font-semibold block">الفئات والحسابات الرئيسية (الآباء)</span>
                            <span class="text-2xl font-black text-indigo-700">{{ $expenseParents->count() }}</span>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-700">
                            <i class="fas fa-folder-open text-lg"></i>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-50 to-white p-4 rounded-xl border border-emerald-100 shadow-xs flex items-center justify-between">
                        <div>
                            <span class="text-xs text-gray-500 font-semibold block">البنود الفرعية (تقبل الترحيل)</span>
                            <span class="text-2xl font-black text-[#008A3B]">{{ $postableCount }}</span>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center text-[#008A3B]">
                            <i class="fas fa-check-circle text-lg"></i>
                        </div>
                    </div>
                </div>

                {{-- فورم إضافة حساب جديد (قابل للطي) --}}
                <div id="addExpenseAccountBox" class="bg-gray-50 p-5 rounded-xl border border-gray-200 mb-8 transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <h4 id="expenseFormTitle" class="text-sm font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-plus-circle text-[#008A3B]"></i> إضافة حساب / بند مصروف جديد
                        </h4>
                        <button type="button" onclick="toggleExpenseForm()" class="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1">
                            <i id="expenseFormToggleIcon" class="fas fa-chevron-up"></i>
                        </button>
                    </div>

                    <form id="addExpenseAccountForm" action="{{ route('settings.store') }}" method="POST" class="space-y-4" onsubmit="sessionStorage.setItem('activeSettingTab', 'expense_category');">
                        @csrf
                        <input type="hidden" name="category" value="expense_category">

                        {{-- نوع الحساب (رئيسي أو فرعي) --}}
                        <div class="flex flex-wrap items-center gap-6 p-3 bg-white border border-gray-200 rounded-lg">
                            <span class="text-xs font-bold text-gray-700">نوع الحساب:</span>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-gray-800">
                                <input type="radio" name="account_type" value="child" checked onchange="toggleAccountType('child')" class="text-[#008A3B] focus:ring-[#008A3B]">
                                <span>بند فرعي (تابع لحساب أب - يقبل تسجيل المصروفات)</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-gray-800">
                                <input type="radio" name="account_type" value="parent" onchange="toggleAccountType('parent')" class="text-[#005B9F] focus:ring-[#005B9F]">
                                <span>حساب رئيسي (فئة أب تجميعية)</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {{-- الحساب الأب --}}
                            <div id="parentKeyContainer">
                                <label class="block text-xs font-bold text-gray-700 mb-1">
                                    <i class="fas fa-folder text-[#005B9F] me-1"></i> الحساب الأب (الفئة الرئيسية) <span class="text-red-500">*</span>
                                </label>
                                <select name="parent_key" id="accountParentSelect" onchange="onParentSelected(this)"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                                    <option value="">— اختر الحساب الأب —</option>
                                    @foreach($expenseParents as $parent)
                                        <option value="{{ $parent->key_value }}" data-classification="{{ $parent->classification }}" data-code="{{ $parent->account_code }}">
                                            {{ $parent->account_code }} — {{ $parent->display_name }} @if($parent->name_en)({{ $parent->name_en }})@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- كود الحساب --}}
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-bold text-gray-700">كود الحساب <span class="text-red-500">*</span></label>
                                    <button type="button" onclick="autoGenerateCode()" class="text-[11px] text-[#005B9F] font-bold hover:underline flex items-center gap-1 cursor-pointer" title="توليد الكود التالي تلقائياً">
                                        <i class="fas fa-magic text-[10px]"></i> توليد تلقائي
                                    </button>
                                </div>
                                <input type="text" name="code" id="accountCodeInput" required placeholder="يُولّد تلقائياً عند اختيار الفئة" dir="ltr"
                                    class="w-full px-3 py-2 text-sm font-mono font-bold text-gray-900 border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            {{-- اسم الحساب بالعربي --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">اسم الحساب بالعربية <span class="text-red-500">*</span></label>
                                <input type="text" name="display_name" required placeholder="مثال: نقل بري وشحن مبرد"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            {{-- اسم الحساب بالإنجليزي --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Account Name (EN)</label>
                                <input type="text" name="name_en" dir="ltr" placeholder="e.g. Refrigerated land freight"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            {{-- التصنيف --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">التصنيف (Classification)</label>
                                <input type="text" name="classification" id="accountClassificationInput" list="classificationsList" placeholder="مثال: Cost of sales and shipments"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                                <datalist id="classificationsList">
                                    <option value="Cost of sales and shipments">تكلفة المبيعات والشحنات</option>
                                    <option value="Operating expenses">مصروفات التشغيل والإدارة</option>
                                    <option value="Finance and other results">نتائج التمويل والبنود الأخرى</option>
                                    <option value="General and administrative expenses">مصروفات عمومية وإدارية</option>
                                    <option value="Selling and marketing expenses">مصروفات بيعية وتسويقية</option>
                                </datalist>
                            </div>

                            {{-- الطبيعة المحاسبية --}}
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الطبيعة المحاسبية</label>
                                <select name="nature" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                                    <option value="Debit" selected>مدين (Debit)</option>
                                    <option value="Credit">دائن (Credit)</option>
                                    <option value="Memo">حساب رقابي (Memo)</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2 border-t border-gray-200">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="allow_posting" id="allowPostingCheckbox" value="1" checked
                                    class="w-4 h-4 text-[#008A3B] rounded border-gray-300 focus:ring-[#008A3B]">
                                <span class="text-xs font-bold text-gray-700">يسمح بالترحيل (يقبل تسجيل مصروفات مباشرة عليه في النظام)</span>
                            </label>

                            <button type="submit" class="px-6 py-2.5 bg-[#008A3B] text-white text-sm font-bold rounded-lg hover:bg-[#007030] transition-colors flex items-center justify-center gap-2 shadow-sm">
                                <i class="fas fa-save"></i> حفظ الحساب
                            </button>
                        </div>
                    </form>
                </div>

                {{-- شريط البحث والتصفية --}}
                <div class="bg-white p-3 border border-gray-200 rounded-xl mb-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="relative w-full sm:w-72">
                        <i class="fas fa-search absolute right-3 top-3 text-gray-400 text-xs"></i>
                        <input type="text" id="expenseSearchInput" onkeyup="filterExpenseTable()" placeholder="بحث بالكود أو الاسم بالعربي أو الإنجليزي..."
                            class="w-full pr-9 pl-3 py-2 text-xs border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F]">
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <select id="expenseFilterParent" onchange="filterExpenseTable()" class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            <option value="">جميع الفئات والحسابات الرئيسية</option>
                            @foreach($expenseParents as $p)
                                <option value="{{ $p->key_value }}">{{ $p->account_code }} — {{ $p->display_name }}</option>
                            @endforeach
                        </select>

                        <select id="expenseFilterPosting" onchange="filterExpenseTable()" class="px-3 py-2 text-xs border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            <option value="">جميع الحالات</option>
                            <option value="yes">يقبل الترحيل فقط (نعم)</option>
                            <option value="no">حسابات رئيسية تجميعية (لا)</option>
                        </select>
                    </div>
                </div>

                {{-- جدول شجرة الحسابات المنظمة --}}
                <div class="border border-gray-200 rounded-xl overflow-hidden shadow-xs bg-white">
                    <table class="w-full text-right text-xs" id="expenseAccountsTable">
                        <thead class="bg-gray-100 text-gray-700 border-b border-gray-200 font-bold">
                            <tr>
                                <th class="p-3 w-28 text-center">كود الحساب</th>
                                <th class="p-3">اسم الحساب بالعربية</th>
                                <th class="p-3">Account Name (EN)</th>
                                <th class="p-3 w-44">الحساب الأب</th>
                                <th class="p-3">التصنيف</th>
                                <th class="p-3 w-24 text-center">الطبيعة</th>
                                <th class="p-3 w-28 text-center">يسمح بالترحيل</th>
                                <th class="p-3 w-28 text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($expenseParents as $parent)
                                @php
                                    $children = $allExpenseCats->filter(fn($c) => $c->parent_key == $parent->key_value || $c->parent_key == $parent->code)->sortBy('code');
                                    $parentJson = json_encode([
                                        'id'             => $parent->id,
                                        'code'           => $parent->account_code,
                                        'display_name'   => $parent->display_name,
                                        'name_en'        => $parent->name_en,
                                        'parent_key'     => '',
                                        'classification' => $parent->classification,
                                        'nature'         => $parent->nature ?: 'Debit',
                                        'allow_posting'  => $parent->allow_posting ? 1 : 0,
                                    ], JSON_UNESCAPED_UNICODE);
                                @endphp

                                {{-- صف الحساب الأب (الفئة الرئيسية) --}}
                                <tr class="parent-row bg-slate-50/80 hover:bg-slate-100/80 font-bold border-r-4 border-r-[#005B9F] transition-colors"
                                    data-account-id="{{ $parent->id }}"
                                    data-parent-key="{{ $parent->key_value }}"
                                    data-code="{{ $parent->account_code }}"
                                    data-posting="{{ $parent->allow_posting ? 'yes' : 'no' }}">
                                    <td class="p-3 text-center font-mono text-sm text-[#005B9F]">
                                        {{ $parent->account_code }}
                                    </td>
                                    <td class="p-3 text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-folder-open text-[#005B9F] text-sm"></i>
                                            <span class="text-sm">{{ $parent->display_name }}</span>
                                            <span class="text-[10px] bg-[#005B9F]/10 text-[#005B9F] px-1.5 py-0.5 rounded font-normal">({{ $children->count() }} بنود)</span>
                                        </div>
                                    </td>
                                    <td class="p-3 text-gray-600 font-mono text-xs" dir="ltr">
                                        {{ $parent->name_en ?: '—' }}
                                    </td>
                                    <td class="p-3 text-gray-400 italic">
                                        <span class="inline-flex items-center gap-1 text-[11px] bg-gray-200/70 text-gray-600 px-2 py-0.5 rounded-full font-medium">
                                            <i class="fas fa-crown text-[9px] text-amber-500"></i> رئيسي
                                        </span>
                                    </td>
                                    <td class="p-3 text-gray-700">
                                        <span class="inline-flex items-center gap-1 text-[11px] bg-blue-50 text-[#005B9F] px-2 py-0.5 rounded-md font-medium">
                                            {{ $parent->classification ?: '—' }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="font-mono text-[11px] font-bold text-gray-600">{{ $parent->nature ?: 'Debit' }}</span>
                                    </td>
                                    <td class="p-3 text-center">
                                        @if($parent->allow_posting)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                                <i class="fas fa-check text-[9px]"></i> نعم
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">
                                                <i class="fas fa-layer-group text-[9px]"></i> لا (تجميعي)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" onclick="addChildUnderParent('{{ $parent->key_value }}', '{{ $parent->account_code }}', '{{ addslashes($parent->classification ?? '') }}')"
                                                class="text-[#008A3B] hover:bg-emerald-50 p-1.5 rounded transition-colors" title="إضافة بند فرعي تحت هذا الحساب">
                                                <i class="fas fa-plus text-xs"></i>
                                            </button>
                                            <button type="button" onclick='openEditModal({!! $parentJson !!})'
                                                class="text-gray-500 hover:text-[#005B9F] hover:bg-blue-50 p-1.5 rounded transition-colors" title="تعديل">
                                                <i class="fas fa-pen text-xs"></i>
                                            </button>
                                            <form action="{{ route('settings.destroy', $parent->id) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الحساب الرئيسي؟ لن يمكن الحذف إذا كانت تتبعه بنود فرعية.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded transition-colors" title="حذف">
                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                {{-- صفوف البنود الفرعية التابعة لهذا الأب --}}
                                @foreach($children as $child)
                                    @php
                                        $childJson = json_encode([
                                            'id'             => $child->id,
                                            'code'           => $child->account_code,
                                            'display_name'   => $child->display_name,
                                            'name_en'        => $child->name_en,
                                            'parent_key'     => $child->parent_key,
                                            'classification' => $child->classification,
                                            'nature'         => $child->nature ?: 'Debit',
                                            'allow_posting'  => $child->allow_posting ? 1 : 0,
                                        ], JSON_UNESCAPED_UNICODE);
                                    @endphp
                                    <tr class="child-row hover:bg-blue-50/30 transition-colors"
                                        data-account-id="{{ $child->id }}"
                                        data-parent-key="{{ $parent->key_value }}"
                                        data-code="{{ $child->account_code }}"
                                        data-posting="{{ $child->allow_posting ? 'yes' : 'no' }}">
                                        <td class="p-3 text-center font-mono font-bold text-gray-800">
                                            {{ $child->account_code }}
                                        </td>
                                        <td class="p-3">
                                            <div class="flex items-center gap-2 pr-6">
                                                <span class="text-gray-300 font-mono text-sm leading-none">↳</span>
                                                <i class="fas fa-file-invoice text-emerald-600 text-xs"></i>
                                                <span class="font-medium text-gray-900">{{ $child->display_name }}</span>
                                            </div>
                                        </td>
                                        <td class="p-3 text-gray-600 font-mono text-xs" dir="ltr">
                                            {{ $child->name_en ?: '—' }}
                                        </td>
                                        <td class="p-3">
                                            <span class="inline-flex items-center gap-1 text-[11px] bg-slate-100 text-gray-700 px-2 py-0.5 rounded-full font-mono">
                                                <i class="fas fa-level-up-alt text-[9px] text-[#005B9F]"></i>
                                                {{ $parent->account_code }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-gray-600">
                                            {{ $child->classification ?: $parent->classification ?: '—' }}
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="font-mono text-[11px] text-gray-600">{{ $child->nature ?: 'Debit' }}</span>
                                        </td>
                                        <td class="p-3 text-center">
                                            @if($child->allow_posting)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full">
                                                    <i class="fas fa-check text-[9px]"></i> نعم
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">
                                                    لا
                                                </span>
                                            @endif
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" onclick='openEditModal({!! $childJson !!})'
                                                    class="text-gray-400 hover:text-[#005B9F] hover:bg-blue-50 p-1.5 rounded transition-colors" title="تعديل">
                                                    <i class="fas fa-pen text-xs"></i>
                                                </button>
                                                <form action="{{ route('settings.destroy', $child->id) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف بند المصروف هذا؟');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-gray-400 hover:text-red-600 hover:bg-red-50 p-1.5 rounded transition-colors" title="حذف">
                                                        <i class="fas fa-trash-alt text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                            @empty
                                <tr>
                                    <td colspan="8" class="p-10 text-center text-gray-500 bg-gray-50/50">
                                        <i class="fas fa-sitemap text-3xl text-gray-300 mb-3 block"></i>
                                        لا توجد حسابات مصروفات مسجلة حتى الآن.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            {{-- ===== مودال تعديل حساب / بند مصروف ===== --}}
            <div id="editExpenseAccountModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs hidden flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full p-6 border border-gray-100 animate-scale-up" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                        <h4 class="text-base font-bold text-[#005B9F] flex items-center gap-2">
                            <i class="fas fa-edit"></i> تعديل حساب / بند المصروف
                        </h4>
                        <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 text-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form id="editExpenseAccountForm" action="" method="POST" class="space-y-4" onsubmit="sessionStorage.setItem('activeSettingTab', 'expense_category');">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الحساب الأب (الفئة)</label>
                                <select name="parent_key" id="editParentKey" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                                    <option value="">— بدون حساب أب (حساب رئيسي) —</option>
                                    @foreach($expenseParents as $parent)
                                        <option value="{{ $parent->key_value }}">
                                            {{ $parent->account_code }} — {{ $parent->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">كود الحساب <span class="text-red-500">*</span></label>
                                <input type="text" name="code" id="editCode" required dir="ltr"
                                    class="w-full px-3 py-2 text-sm font-mono border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">اسم الحساب بالعربية <span class="text-red-500">*</span></label>
                                <input type="text" name="display_name" id="editDisplayName" required
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Account Name (EN)</label>
                                <input type="text" name="name_en" id="editNameEn" dir="ltr"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">التصنيف</label>
                                <input type="text" name="classification" id="editClassification" list="classificationsList"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">الطبيعة المحاسبية</label>
                                <select name="nature" id="editNature" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#005B9F] bg-white">
                                    <option value="Debit">مدين (Debit)</option>
                                    <option value="Credit">دائن (Credit)</option>
                                    <option value="Memo">حساب رقابي (Memo)</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-2">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="allow_posting" id="editAllowPosting" value="1"
                                    class="w-4 h-4 text-[#008A3B] rounded border-gray-300 focus:ring-[#008A3B]">
                                <span class="text-xs font-bold text-gray-700">يسمح بالترحيل (يقبل تسجيل مصروفات مباشرة عليه)</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                            <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition-colors">
                                إلغاء
                            </button>
                            <button type="submit" class="px-5 py-2 bg-[#005B9F] hover:bg-[#004680] text-white text-xs font-bold rounded-lg transition-colors flex items-center gap-1.5 shadow-sm">
                                <i class="fas fa-save"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Tom Select CDN --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<style>
/* ===== Tom Select — إعادة تهيئة لأسلوب التطبيق ===== */
#parentKeysSelect + .ts-wrapper { width: 100%; }
#parentKeysSelect + .ts-wrapper .ts-control {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 6px 10px;
    min-height: 42px;
    background: #fff;
    box-shadow: none;
    outline: none !important;
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: center;
    cursor: text;
}
#parentKeysSelect + .ts-wrapper.focus .ts-control {
    border-color: #005B9F;
    box-shadow: 0 0 0 2px rgba(0,91,159,.12);
}
#parentKeysSelect + .ts-wrapper .ts-control .item {
    background: #EBF7F0;
    color: #005B9F;
    font-size: 12px;
    font-weight: 700;
    border-radius: 20px;
    padding: 2px 8px 2px 10px;
    display: flex;
    align-items: center;
    gap: 4px;
    border: 1px solid #b2d8f7;
    white-space: nowrap;
}
#parentKeysSelect + .ts-wrapper .ts-control .item .remove {
    color: #005B9F;
    font-size: 14px;
    line-height: 1;
    opacity: .6;
    margin: 0;
    padding: 0 0 0 2px;
    border: none;
    background: none;
    cursor: pointer;
}
#parentKeysSelect + .ts-wrapper .ts-control .item .remove:hover { opacity: 1; }
#parentKeysSelect + .ts-wrapper .ts-control input {
    font-size: 13px;
    color: #374151;
    min-width: 120px;
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    padding: 2px 0;
}
#parentKeysSelect + .ts-wrapper .ts-control input::placeholder { color: #9ca3af; }
#parentKeysSelect + .ts-wrapper .ts-dropdown {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
    margin-top: 4px;
    background: #fff;
    overflow: hidden;
    z-index: 9999;
}
#parentKeysSelect + .ts-wrapper .ts-dropdown .ts-dropdown-content { max-height: 220px; overflow-y: auto; padding: 4px; }
#parentKeysSelect + .ts-wrapper .ts-dropdown .option {
    padding: 8px 12px;
    font-size: 13px;
    color: #374151;
    border-radius: 6px;
    cursor: pointer;
}
#parentKeysSelect + .ts-wrapper .ts-dropdown .option:hover,
#parentKeysSelect + .ts-wrapper .ts-dropdown .option.active { background: #EBF7F0; color: #008A3B; }
#parentKeysSelect + .ts-wrapper .ts-dropdown .option.selected { background: #f0f9ff; color: #005B9F; font-weight: 700; }
#parentKeysSelect + .ts-wrapper .ts-dropdown .option.selected::after {
    content: "✓";
    float: left;
    color: #008A3B;
    font-weight: 800;
}
#parentKeysSelect + .ts-wrapper .ts-dropdown .no-results { padding: 10px 12px; color: #9ca3af; font-size: 12px; text-align: center; }
</style>

<script>
    // ===== Tab switching =====
    function switchTab(tabKey) {
        document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('bg-[#005B9F]/10', 'text-[#005B9F]', 'border-r-4', 'border-[#005B9F]', 'font-bold');
            btn.classList.add('text-gray-600', 'hover:bg-gray-50');
        });

        const targetPanel = document.getElementById('panel-' + tabKey);
        if (targetPanel) targetPanel.classList.remove('hidden');

        const targetBtn = document.getElementById('btn-' + tabKey);
        if (targetBtn) {
            targetBtn.classList.remove('text-gray-600', 'hover:bg-gray-50');
            targetBtn.classList.add('bg-[#005B9F]/10', 'text-[#005B9F]', 'border-r-4', 'border-[#005B9F]', 'font-bold');
        }

        sessionStorage.setItem('activeSettingTab', tabKey);
    }

    window.onload = function () {
        // تهيئة Tom Select على قائمة المجموعات الرئيسية المتعددة
        const parentEl = document.getElementById('parentKeysSelect');
        if (parentEl) {
            new TomSelect(parentEl, {
                plugins: ['remove_button'],
                placeholder: 'اختر مجموعة رئيسية أو أكثر...',
                maxOptions: 500,
                create: false,
                closeAfterSelect: false,
                hideSelected: false,
                render: {
                    no_results: () => '<div class="no-results">لا توجد نتائج</div>',
                    option: (data, escape) =>
                        `<div class="option">${escape(data.text)}</div>`,
                    item: (data, escape) =>
                        `<div class="item">${escape(data.text)}</div>`,
                }
            });
        }

        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById('panel-' + hash)) {
            switchTab(hash);
        } else {
            const savedTab = sessionStorage.getItem('activeSettingTab');
            if (savedTab && document.getElementById('panel-' + savedTab)) {
                switchTab(savedTab);
            } else {
                switchTab('client_type');
            }
        }

        // تهيئة كود الحساب التلقائي لبنود المصروفات
        const pSel = document.getElementById('accountParentSelect');
        const cInput = document.getElementById('accountCodeInput');
        if (pSel && cInput && !cInput.value) {
            if (!pSel.value && pSel.options.length > 1) {
                pSel.selectedIndex = 1;
            }
            if (pSel.value) {
                onParentSelected(pSel);
            }
        }
    };

    // ===== Dynamic rows for sub-category names =====
    function addSubcatRow() {
        const container = document.getElementById('subcatNameRows');
        const firstRow  = container.querySelector('.subcat-row');
        const clone     = firstRow.cloneNode(true);
        clone.querySelector('input').value = '';
        clone.querySelector('input').required = true;
        container.appendChild(clone);
        clone.querySelector('input').focus();
    }

    function removeSubcatRow(btn) {
        const container = document.getElementById('subcatNameRows');
        const rows = container.querySelectorAll('.subcat-row');
        if (rows.length > 1) {
            btn.closest('.subcat-row').remove();
        } else {
            // إذا بقي صف واحد فقط نمسح قيمته بدل الحذف
            btn.closest('.subcat-row').querySelector('input').value = '';
        }
    }

    // ===== دوال إدارة شجرة الحسابات وبنود المصروفات =====
    function generateNextAccountCode(parentKey) {
        if (!parentKey) {
            // حساب رئيسي جديد (بدون أب)
            var parentRows = document.querySelectorAll('#expenseAccountsTable tr.parent-row');
            var maxParent6 = 0;
            var maxAny = 0;
            parentRows.forEach(function(r) {
                var c = parseInt(r.getAttribute('data-code'));
                if (c) {
                    if (c > maxAny) maxAny = c;
                    if (c >= 100000 && c <= 900000 && c % 100000 === 0 && c > maxParent6) {
                        maxParent6 = c;
                    }
                }
            });
            if (maxParent6 >= 500000 && maxParent6 < 900000) {
                return (maxParent6 + 100000).toString();
            }
            if (maxAny > 0) {
                return (maxAny + 1).toString();
            }
            return '800000';
        }

        var parentSelect = document.getElementById('accountParentSelect');
        var selectedOpt = parentSelect ? parentSelect.querySelector('option[value="' + parentKey + '"]') : null;
        var parentCode = selectedOpt ? (selectedOpt.getAttribute('data-code') || selectedOpt.value) : parentKey;
        var pStr = parentCode ? parentCode.toString() : '';

        // البادئة المتوقعة للأبناء
        var expectedPrefix = '';
        if (/^\d{6}$/.test(pStr) && pStr.endsWith('0000')) {
            // كود 6 أرقام مثل 500000 -> بادئة الأبناء 51
            expectedPrefix = (parseInt(pStr.substring(0, 2)) + 1).toString();
        } else if (pStr.length >= 2) {
            expectedPrefix = pStr.substring(0, 2);
        }

        var maxChildCode = 0;
        var childRows = document.querySelectorAll('#expenseAccountsTable tr.child-row');
        childRows.forEach(function(r) {
            var pk = r.getAttribute('data-parent-key');
            if (pk === parentKey || pk === parentCode) {
                var cStr = (r.getAttribute('data-code') || '').trim();
                var c = parseInt(cStr);
                if (c && (!expectedPrefix || cStr.startsWith(expectedPrefix))) {
                    if (c > maxChildCode) {
                        maxChildCode = c;
                    }
                }
            }
        });

        if (maxChildCode > 0) {
            return (maxChildCode + 1).toString();
        }

        // لو لم يكن له أبناء بعد يحملون نفس البادئة:
        if (/^\d{6}$/.test(pStr) && pStr.endsWith('0000')) {
            return expectedPrefix + '0001';
        }

        var num = parseInt(parentCode);
        if (!isNaN(num)) {
            return (num + 1).toString();
        }

        return parentCode + '-01';
    }

    function autoGenerateCode() {
        var isParent = false;
        var radios = document.getElementsByName('account_type');
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked && radios[i].value === 'parent') {
                isParent = true;
                break;
            }
        }

        var codeInput = document.getElementById('accountCodeInput');
        if (!codeInput) return;

        if (isParent) {
            codeInput.value = generateNextAccountCode('');
        } else {
            var parentSelect = document.getElementById('accountParentSelect');
            var pVal = parentSelect ? parentSelect.value : '';
            if (!pVal && parentSelect && parentSelect.options.length > 1) {
                parentSelect.selectedIndex = 1;
                onParentSelected(parentSelect);
            } else {
                codeInput.value = generateNextAccountCode(pVal);
            }
        }
    }

    function toggleExpenseForm() {
        var box = document.getElementById('addExpenseAccountBox');
        var icon = document.getElementById('expenseFormToggleIcon');
        if (!box) return;
        if (box.classList.contains('hidden')) {
            box.classList.remove('hidden');
            if (icon) icon.className = 'fas fa-chevron-up';
        } else {
            box.classList.add('hidden');
            if (icon) icon.className = 'fas fa-chevron-down';
        }
    }

    function openExpenseForm(type) {
        var box = document.getElementById('addExpenseAccountBox');
        if (!box) return;
        box.classList.remove('hidden');
        var icon = document.getElementById('expenseFormToggleIcon');
        if (icon) icon.className = 'fas fa-chevron-up';

        var radios = document.getElementsByName('account_type');
        for (var i = 0; i < radios.length; i++) {
            radios[i].checked = (radios[i].value === type);
        }
        toggleAccountType(type);

        box.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function toggleAccountType(type) {
        var parentContainer = document.getElementById('parentKeyContainer');
        var parentSelect    = document.getElementById('accountParentSelect');
        var postingCheck    = document.getElementById('allowPostingCheckbox');
        var title           = document.getElementById('expenseFormTitle');
        var codeInput       = document.getElementById('accountCodeInput');

        if (type === 'parent') {
            if (parentContainer) parentContainer.style.display = 'none';
            if (parentSelect) parentSelect.required = false;
            if (parentSelect) parentSelect.value = '';
            if (postingCheck) postingCheck.checked = false; // الحسابات الرئيسية تجميعية
            if (title) title.innerHTML = '<i class="fas fa-folder-plus text-[#005B9F]"></i> إضافة حساب رئيسي (فئة أب تجميعية)';
            if (codeInput) codeInput.value = generateNextAccountCode('');
        } else {
            if (parentContainer) parentContainer.style.display = 'block';
            if (parentSelect) parentSelect.required = true;
            if (postingCheck) postingCheck.checked = true; // البنود الفرعية تقبل الترحيل
            if (title) title.innerHTML = '<i class="fas fa-plus-circle text-[#008A3B]"></i> إضافة بند مصروف فرعي (نازل من حساب أب)';

            if (parentSelect) {
                if (!parentSelect.value && parentSelect.options.length > 1) {
                    parentSelect.selectedIndex = 1;
                }
                onParentSelected(parentSelect);
            }
        }
    }

    function onParentSelected(selectEl) {
        var opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) return;

        var cls = opt.getAttribute('data-classification');
        if (cls) {
            document.getElementById('accountClassificationInput').value = cls;
        }

        var nextCode = generateNextAccountCode(opt.value);
        var codeInput = document.getElementById('accountCodeInput');
        if (codeInput) {
            codeInput.value = nextCode;
        }
    }

    function addChildUnderParent(parentKey, parentCode, parentCls) {
        openExpenseForm('child');
        var pSel = document.getElementById('accountParentSelect');
        if (pSel) {
            pSel.value = parentKey;
            onParentSelected(pSel);
        }
        if (parentCls) {
            document.getElementById('accountClassificationInput').value = parentCls;
        }
    }

    function filterExpenseTable() {
        var queryInput = document.getElementById('expenseSearchInput');
        var query = queryInput ? (queryInput.value || '').toLowerCase().trim() : '';
        var parentFilterEl = document.getElementById('expenseFilterParent');
        var filterParent = parentFilterEl ? parentFilterEl.value : '';
        var postingFilterEl = document.getElementById('expenseFilterPosting');
        var filterPosting = postingFilterEl ? postingFilterEl.value : '';

        var rows = document.querySelectorAll('#expenseAccountsTable tbody tr');
        rows.forEach(function(row) {
            var text = row.textContent.toLowerCase();
            var matchesQuery = !query || text.indexOf(query) !== -1;

            var rowParentKey = row.getAttribute('data-parent-key');
            var matchesParent = !filterParent || (rowParentKey === filterParent);

            var rowPosting = row.getAttribute('data-posting');
            var matchesPosting = !filterPosting || (rowPosting === filterPosting);

            if (matchesQuery && matchesParent && matchesPosting) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function openEditModal(data) {
        var modal = document.getElementById('editExpenseAccountModal');
        var form = document.getElementById('editExpenseAccountForm');
        form.action = '/settings/' + data.id;

        document.getElementById('editCode').value = data.code || '';
        document.getElementById('editDisplayName').value = data.display_name || '';
        document.getElementById('editNameEn').value = data.name_en || '';
        document.getElementById('editParentKey').value = data.parent_key || '';
        document.getElementById('editClassification').value = data.classification || '';
        document.getElementById('editNature').value = data.nature || 'Debit';
        document.getElementById('editAllowPosting').checked = (data.allow_posting == 1);

        modal.classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editExpenseAccountModal').classList.add('hidden');
    }
</script>
@endsection