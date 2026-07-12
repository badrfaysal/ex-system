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
            'المالية والمحافظ' => [
                'wallets'          => ['icon' => 'fa-wallet', 'custom' => true, 'title' => 'المحافظ والصناديق'],
                'expense_category' => ['icon' => 'fa-file-invoice-dollar'],
            ],
        ];
        $allUsers     = \App\Models\User::orderBy('name')->get();
        $notifyEmails = \App\Models\Setting::where('category', 'notify_email')->orderBy('display_name')->get();
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
        </div>

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

            {{-- ===== panel المحافظ والصناديق (custom) ===== --}}
            <div id="panel-wallets" class="tab-panel hidden p-6 animate-fade-in">

                <div class="border-b border-gray-100 pb-4 mb-6 flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold text-[#008A3B] flex items-center gap-2">
                            <i class="fas fa-wallet"></i> إعدادات المحافظ والصناديق
                        </h3>
                        <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">
                            أضف محافظ مالية (مثل: بنك، خزينة، عهدة) مع رصيد أول المدة.
                        </p>
                    </div>
                </div>

                @if($errors->any() && session('activeSettingTab') === 'wallets')
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
                </div>
                @endif

                {{-- إضافة محفظة جديدة --}}
                <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 mb-8">
                    <p class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-[#008A3B]"></i> إضافة محفظة جديدة
                    </p>
                    <form action="{{ route('wallets.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" onsubmit="sessionStorage.setItem('activeSettingTab', 'wallets');">
                        @csrf
                        <input type="hidden" name="type" value="bank"> {{-- افتراضي لأنها مش هامة --}}

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">اسم المحفظة / الخزينة <span class="text-red-500">*</span></label>
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
                                <i class="fas fa-save"></i> إضافة المحفظة
                            </button>
                        </div>

                        <p class="sm:col-span-2 lg:col-span-4 text-[11px] text-amber-600 flex items-start gap-1.5 -mt-1">
                            <i class="fas fa-circle-info mt-0.5"></i>
                            <span>المحفظة تعمل بعملة واحدة فقط. لن تُقبل أي حركة (قبض / دفع / مصروف / إيراد / تحويل) على هذه المحفظة إلا بنفس العملة المختارة هنا.</span>
                        </p>
                    </form>
                </div>

                {{-- قائمة المحافظ الحالية --}}
                <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fas fa-list text-gray-400"></i> قائمة المحافظ الحالية
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

        const savedTab = sessionStorage.getItem('activeSettingTab');
        if (savedTab && document.getElementById('panel-' + savedTab)) {
            switchTab(savedTab);
        } else {
            switchTab('client_type');
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
</script>
@endsection