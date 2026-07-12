<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Expense;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\Wallet;
use App\Rules\MatchesWalletCurrency;
use App\Services\SequenceGenerator;
use App\Services\WalletLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            'expense_date' => now()->toDateString(),
            'currency'     => 'EGP',
            'quotation_id' => $request->integer('quotation_id') ?: null,
        ]);

        return view('expenses.create', $this->formData() + ['expense' => $expense]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['created_by'] = auth()->id();

        try {
            $expense = DB::transaction(function () use ($data) {
                // قفل صف المحفظة والتحقق من كفاية الرصيد — آمن حتى مع طلبات متزامنة
                WalletLedger::lockAndCheck($data['wallet_id'], $data['amount']);

                $data['expense_number'] = SequenceGenerator::next('EXP');

                return Expense::create($data);
            });
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        $msg = app()->getLocale() === 'ar' ? 'تم حفظ المصروف بنجاح' : 'Expense saved successfully';

        if ($request->has('redirect_to')) {
            return redirect($request->redirect_to)->with('success', $msg);
        }

        return redirect()->route('expenses.index')->with('success', $msg);
    }

    public function edit(Expense $expense)
    {
        return view('expenses.edit', $this->formData() + ['expense' => $expense]);
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $this->validateData($request);
        $data['created_by'] = auth()->id();

        try {
            DB::transaction(function () use ($data, $expense) {
                // رجّع المبلغ القديم للمتاح قبل ما نتحقق من الجديد — عشان التعديل بنفس القيمة أو بمبلغ أقل ميترفضش غلط
                WalletLedger::lockAndCheck($data['wallet_id'], $data['amount'], excludeAmount: (float) $expense->amount);

                $expense->update($data);
            });
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

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
            'wallets'    => Wallet::orderBy('name')->get(['id', 'name', 'currency']),
        ];
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'quotation_id' => 'nullable|exists:quotations,id',
            'category'     => 'required|string',
            'description'  => 'nullable|string',
            'wallet_id'    => 'required|exists:wallets,id',
            'amount'       => 'required|numeric|min:0.01',
            'currency'     => ['required', 'string', new MatchesWalletCurrency],
            'expense_date' => 'required|date',
            'notes'        => 'nullable|string',
        ]);
    }
}
