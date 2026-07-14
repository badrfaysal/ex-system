<?php

namespace App\Http\Controllers;

use App\Models\PeriodLock;
use Illuminate\Http\Request;

class PeriodLockController extends Controller
{
    public function index()
    {
        $locks = PeriodLock::with('creator')->orderByDesc('start_date')->get();

        return view('period_locks.index', compact('locks'));
    }

    public function store(Request $request)
    {
        $isAr = app()->getLocale() === 'ar';

        $data = $request->validate([
            'label'      => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $data['is_active'] = true;
        $data['created_by'] = auth()->id();

        PeriodLock::create($data);

        return back()->with('success', $isAr ? 'تم إنشاء الفترة المقفولة بنجاح.' : 'Period lock created successfully.');
    }

    public function toggle(Request $request, PeriodLock $periodLock)
    {
        $isAr = app()->getLocale() === 'ar';

        if ($periodLock->is_active) {
            // It is currently locked, we are OPENING it.
            $request->validate([
                'open_reason' => 'required|string|max:1000',
            ]);

            $periodLock->update([
                'is_active'   => false,
                'opened_at'   => now(),
                'opened_by'   => auth()->id(),
                'open_reason' => $request->open_reason,
            ]);

            return back()->with('success', $isAr ? 'تم فتح الفترة - العمليات متاحة الآن.' : 'Period opened - operations are available again.');
        } else {
            // It is currently open, we are CLOSING it.
            $periodLock->update([
                'is_active'   => true,
                'reclosed_at' => now(),
                'reclosed_by' => auth()->id(),
            ]);

            return back()->with('success', $isAr ? 'تم إغلاق الفترة - العمليات بداخلها مقفلة الآن.' : 'Period closed - operations within it are now locked.');
        }
    }
}
