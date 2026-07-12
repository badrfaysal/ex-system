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

    public function toggle(PeriodLock $periodLock)
    {
        $isAr = app()->getLocale() === 'ar';

        $periodLock->update(['is_active' => !$periodLock->is_active]);

        return back()->with('success', $periodLock->is_active
            ? ($isAr ? 'تم إغلاق الفترة — أي عملية بداخلها هتتقفل.' : 'Period closed — operations within it are now locked.')
            : ($isAr ? 'تم فتح الفترة — العمليات بداخلها بقت متاحة تاني.' : 'Period opened — operations within it are available again.'));
    }

    public function destroy(PeriodLock $periodLock)
    {
        $isAr = app()->getLocale() === 'ar';

        $periodLock->delete();

        return back()->with('success', $isAr ? 'تم حذف الفترة المقفولة.' : 'Period lock deleted.');
    }
}
