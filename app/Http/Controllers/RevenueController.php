<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Revenue;

class RevenueController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'revenue_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $validated['revenue_number'] = 'REV-' . date('Ym') . '-' . str_pad(Revenue::count() + 1, 4, '0', STR_PAD_LEFT);
        $validated['created_by'] = auth()->id();

        Revenue::create($validated);

        return redirect()->back()->with('success', app()->getLocale() === 'ar' ? 'تم إضافة الإيراد المباشر بنجاح' : 'Direct revenue added successfully');
    }
}
