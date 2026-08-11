@extends('layouts.app')
@section('header_title', $contactGroup->name)

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in relative">
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#008A3B]/10 flex items-center justify-center text-[#008A3B]">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $contactGroup->name }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ $contactGroup->description ?: 'إدارة أفراد هذه المجموعة' }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('contact-groups.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2">
                <i class="fas fa-arrow-right"></i> عودة
            </a>
            <button onclick="document.getElementById('addContactModal').classList.remove('hidden')" class="px-6 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030] transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-plus"></i> إضافة جهة اتصال (فرد جديد)
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 text-green-700 rounded-lg border border-green-200">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 text-red-700 rounded-lg border border-red-200">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">الاسم</th>
                        <th class="p-4">رقم الهاتف</th>
                        <th class="p-4">ملاحظات</th>
                        <th class="p-4">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($contactGroup->contacts as $contact)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4 font-bold text-gray-900">{{ $contact->name }}</td>
                            <td class="p-4 text-gray-700 font-mono" dir="ltr">{{ $contact->phone ?: '-' }}</td>
                            <td class="p-4 text-gray-500">{{ $contact->notes ?: '-' }}</td>
                            <td class="p-4 flex gap-2">
                                <button onclick="editContact({{ json_encode($contact) }})" class="text-orange-500 hover:text-orange-700 p-2" title="تعديل">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form action="{{ route('contacts.destroy', $contact->id) }}" method="POST" class="inline-block" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-2" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">لا توجد جهات اتصال مسجلة في هذه المجموعة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal إضافة جهة اتصال -->
<div id="addContactModal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">إضافة فرد جديد للمجموعة</h3>
        <form action="{{ route('contacts.store') }}" method="POST">
            @csrf
            <input type="hidden" name="contact_group_id" value="{{ $contactGroup->id }}">
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">الاسم <span class="text-red-500">*</span></label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">رقم الهاتف</label>
                <input type="text" name="phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">ملاحظات (مثال: نوع السيارة، وقت الدوام...)</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]"></textarea>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addContactModal').classList.add('hidden')" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg font-bold">إلغاء</button>
                <button type="submit" class="px-5 py-2 bg-[#008A3B] text-white rounded-lg font-bold">حفظ الفرد</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal تعديل جهة اتصال -->
<div id="editContactModal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm overflow-y-auto h-full w-full flex items-center justify-center p-4">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">تعديل بيانات الفرد</h3>
        <form id="editContactForm" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">الاسم <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="edit_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">رقم الهاتف</label>
                <input type="text" name="phone" id="edit_phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]">
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">ملاحظات</label>
                <textarea name="notes" id="edit_notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#008A3B]"></textarea>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editContactModal').classList.add('hidden')" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg font-bold">إلغاء</button>
                <button type="submit" class="px-5 py-2 bg-[#008A3B] text-white rounded-lg font-bold">حفظ التعديلات</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editContact(contact) {
        document.getElementById('editContactForm').action = '/contacts/' + contact.id;
        document.getElementById('edit_name').value = contact.name;
        document.getElementById('edit_phone').value = contact.phone || '';
        document.getElementById('edit_notes').value = contact.notes || '';
        document.getElementById('editContactModal').classList.remove('hidden');
    }
</script>
@endsection
