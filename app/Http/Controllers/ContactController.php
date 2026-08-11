<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_group_id' => 'required|exists:contact_groups,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);
        \App\Models\Contact::create($validated);
        return redirect()->back()->with('success', __('تمت إضافة جهة الاتصال بنجاح.'));
    }

    public function update(Request $request, \App\Models\Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);
        $contact->update($validated);
        return redirect()->back()->with('success', __('تم تعديل جهة الاتصال بنجاح.'));
    }

    public function destroy(\App\Models\Contact $contact)
    {
        $contact->delete();
        return redirect()->back()->with('success', __('تم حذف جهة الاتصال بنجاح.'));
    }
}
