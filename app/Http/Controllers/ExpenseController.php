<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Quotation;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ExpenseController extends Controller
{
    /**
     * قائمة المصروفات
     */
    public function index(Request $request)
    {
        $query = Expense::with(['quotation']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('quotation', fn ($qq) => $qq->where('quote_number', 'like', "%{$search}%"));
            });
        }

        $expenses = $query->latest()->paginate(15)->withQueryString();

        return view('expenses.index', compact('expenses'));
    }

    public function create(Request $request)
    {
        $expense = new Expense([
            'expense_number' => $this->nextNumber(),
            'expense_date'   => now()->toDateString(),
            'currency'       => 'EGP',
            'quotation_id'   => $request->integer('quotation_id') ?: null,
        ]);

        return view('expenses.create', $this->formData() + ['expense' => $expense]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $expense = Expense::create($data);

        return redirect()->route('expenses.index')
            ->with('success', app()->getLocale() === 'ar' ? 'تم حفظ المصروف بنجاح' : 'Expense saved successfully');
    }

    public function edit(Expense $expense)
    {
        return view('expenses.edit', $this->formData() + ['expense' => $expense]);
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $this->validateData($request, $expense->id);

        $expense->update($data);

        return redirect()->route('expenses.index')
            ->with('success', app()->getLocale() === 'ar' ? 'تم تحديث المصروف بنجاح' : 'Expense updated successfully');
    }

    /* ===================== Helpers ===================== */

    private function formData(): array
    {
        $lookups = Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });

        return [
            'quotations' => Quotation::orderByDesc('id')->get(['id', 'quote_number', 'cost_center_name', 'client_id']),
            'categories' => $lookups->get('expense_category') ?? collect(),
            'currencies' => $lookups->get('currency') ?? collect(),
        ];
    }

    private function nextNumber(): string
    {
        $last = Expense::latest('id')->first();
        $seq  = $last ? $last->id + 1 : 1;
        return 'EXP-' . now()->format('Y-m') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function validateData(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'expense_number' => 'required|string|unique:expenses,expense_number' . ($ignoreId ? ",$ignoreId" : ''),
            'quotation_id'   => 'required|exists:quotations,id',
            'category'       => 'required|string',
            'description'    => 'nullable|string',
            'amount'         => 'required|numeric|min:0.01',
            'currency'       => 'required|string',
            'expense_date'   => 'required|date',
            'notes'          => 'nullable|string',
        ]);
    }
}
