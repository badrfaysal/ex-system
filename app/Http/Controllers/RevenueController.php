<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Revenue;
use App\Models\Wallet;
use App\Services\SequenceGenerator;
use Illuminate\Support\Facades\DB;

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

        // العملة ترث من المحفظة — المحفظة تعمل بعملة واحدة فقط
        $validated['currency'] = Wallet::findOrFail($validated['wallet_id'])->currency;
        $validated['created_by'] = auth()->id();

        DB::transaction(function () use (&$validated) {
            $validated['revenue_number'] = SequenceGenerator::next('REV');
            Revenue::create($validated);
        });

        return redirect()->back()->with('success', app()->getLocale() === 'ar' ? 'تم إضافة الإيراد المباشر بنجاح' : 'Direct revenue added successfully');
    }
}
