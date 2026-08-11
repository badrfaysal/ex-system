<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactGroupController extends Controller
{
    public function index()
    {
        $contactGroups = \App\Models\ContactGroup::all();
        return view('contact_groups.index', compact('contactGroups'));
    }

    public function create()
    {
        return view('contact_groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        \App\Models\ContactGroup::create($validated);
        return redirect()->route('contact-groups.index')->with('success', __('تم إضافة المجموعة بنجاح.'));
    }

    public function show(\App\Models\ContactGroup $contactGroup)
    {
        // Load the contacts for this group
        $contactGroup->load('contacts');
        return view('contact_groups.show', compact('contactGroup'));
    }

    public function edit(\App\Models\ContactGroup $contactGroup)
    {
        return view('contact_groups.edit', compact('contactGroup'));
    }

    public function update(Request $request, \App\Models\ContactGroup $contactGroup)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $contactGroup->update($validated);
        return redirect()->route('contact-groups.index')->with('success', __('تم تعديل المجموعة بنجاح.'));
    }

    public function destroy(\App\Models\ContactGroup $contactGroup)
    {
        $contactGroup->delete();
        return redirect()->route('contact-groups.index')->with('success', __('تم حذف المجموعة بنجاح.'));
    }
}
