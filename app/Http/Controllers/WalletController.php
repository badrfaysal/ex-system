<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WalletController extends Controller
{
    public function index()
    {
        $wallets = Wallet::withBalanceSums()->orderBy('name')->get();

        $lookups = Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });
        $categories = $lookups->get('expense_category') ?? collect();

        return view('wallets.index', compact('wallets', 'categories'));
    }

    public function create()
    {
        return view('wallets.create', $this->formData() + ['wallet' => new Wallet(['currency' => 'EGP'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $wallet = Wallet::create($data);

        return redirect()->back()
            ->with('success', app()->getLocale() === 'ar' ? 'تم إنشاء المحفظة بنجاح' : 'Wallet created successfully');
    }

    public function edit(Wallet $wallet)
    {
        return view('wallets.edit', $this->formData() + ['wallet' => $wallet]);
    }

    public function update(Request $request, Wallet $wallet)
    {
        $data = $this->validateData($request, $wallet->id);

        $wallet->update($data);

        return redirect()->back()
            ->with('success', app()->getLocale() === 'ar' ? 'تم تحديث المحفظة بنجاح' : 'Wallet updated successfully');
    }

    public function destroy(Wallet $wallet)
    {
        // التحقق إذا كان هناك حركات مرتبطة بالمحفظة
        if ($wallet->receipts()->exists() || $wallet->expenses()->exists() || $wallet->vendorPayments()->exists() || $wallet->transfersOut()->exists() || $wallet->transfersIn()->exists()) {
            return redirect()->back()->with('error', app()->getLocale() === 'ar' ? 'لا يمكن حذف المحفظة لوجود حركات مالية مرتبطة بها.' : 'Cannot delete wallet because it has financial transactions.');
        }

        $wallet->delete();

        return redirect()->back()
            ->with('success', app()->getLocale() === 'ar' ? 'تم حذف المحفظة بنجاح' : 'Wallet deleted successfully');
    }

    /**
     * كشف حساب المحفظة — كل الحركات الداخلة والخارجة بالترتيب الزمني، مع مين عملها
     */
    public function show(Wallet $wallet)
    {
        [$timeline, $balance] = $this->buildTimeline($wallet);

        $lookups = Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });
        $categories = $lookups->get('expense_category') ?? collect();

        return view('wallets.show', compact('wallet', 'timeline', 'balance', 'categories'));
    }

    /**
     * طباعة كشف حساب المحفظة بالتصميم الرسمي
     */
    public function print(Wallet $wallet)
    {
        [$timeline, $balance] = $this->buildTimeline($wallet);

        return view('wallets.print', compact('wallet', 'timeline', 'balance'));
    }

    private function buildTimeline(Wallet $wallet): array
    {
        $wallet->load(['receipts.client', 'receipts.creator', 'receipts.reversedByUser', 'revenues.creator', 'revenues.reversedByUser', 'expenses.creator', 'expenses.reversedByUser', 'vendorPayments.vendor', 'vendorPayments.creator', 'vendorPayments.reversedByUser', 'transfersOut.toWallet', 'transfersOut.creator', 'transfersOut.reversedByUser', 'transfersIn.fromWallet', 'transfersIn.creator', 'transfersIn.reversedByUser']);

        $entries = collect();

        foreach ($wallet->receipts as $r) {
            $entries->push(['date' => $r->receipt_date, 'created_at' => $r->created_at, 'type' => 'receipt', 'ref' => $r->receipt_number, 'detail' => optional($r->client)->displayName(), 'amount' => $r->amount, 'user' => optional($r->creator)->name, 'link' => null, 'is_reversed' => $r->reversed_at !== null]);
            if ($r->reversed_at) {
                $entries->push(['date' => \Carbon\Carbon::parse($r->reversed_at), 'created_at' => $r->reversed_at, 'type' => 'receipt', 'ref' => 'REV-' . $r->receipt_number, 'detail' => 'عكس سند قبض: ' . $r->reversal_reason, 'amount' => -$r->amount, 'user' => optional($r->reversedByUser)->name ?? 'System', 'link' => null, 'is_reversal' => true]);
            }
        }
        foreach ($wallet->revenues as $rev) {
            $entries->push(['date' => $rev->revenue_date, 'created_at' => $rev->created_at, 'type' => 'revenue', 'ref' => $rev->revenue_number, 'detail' => $rev->category, 'amount' => $rev->amount, 'user' => optional($rev->creator)->name, 'link' => null, 'is_reversed' => $rev->reversed_at !== null]);
            if ($rev->reversed_at) {
                $entries->push(['date' => \Carbon\Carbon::parse($rev->reversed_at), 'created_at' => $rev->reversed_at, 'type' => 'revenue', 'ref' => 'REV-' . $rev->revenue_number, 'detail' => 'عكس إيراد: ' . $rev->reversal_reason, 'amount' => -$rev->amount, 'user' => optional($rev->reversedByUser)->name ?? 'System', 'link' => null, 'is_reversal' => true]);
            }
        }
        foreach ($wallet->expenses as $e) {
            $entries->push(['date' => $e->expense_date, 'created_at' => $e->created_at, 'type' => 'expense', 'ref' => $e->expense_number, 'detail' => $e->category, 'amount' => -1 * $e->amount, 'user' => optional($e->creator)->name, 'link' => null, 'is_reversed' => $e->reversed_at !== null]);
            if ($e->reversed_at) {
                $entries->push(['date' => \Carbon\Carbon::parse($e->reversed_at), 'created_at' => $e->reversed_at, 'type' => 'expense', 'ref' => 'REV-' . $e->expense_number, 'detail' => 'عكس مصروف: ' . $e->reversal_reason, 'amount' => $e->amount, 'user' => optional($e->reversedByUser)->name ?? 'System', 'link' => null, 'is_reversal' => true]);
            }
        }
        foreach ($wallet->vendorPayments as $p) {
            $entries->push(['date' => $p->payment_date, 'created_at' => $p->created_at, 'type' => 'vendor_payment', 'ref' => $p->payment_number, 'detail' => optional($p->vendor)->name_ar, 'amount' => -1 * $p->amount, 'user' => optional($p->creator)->name, 'link' => null, 'is_reversed' => $p->reversed_at !== null]);
            if ($p->reversed_at) {
                $entries->push(['date' => \Carbon\Carbon::parse($p->reversed_at), 'created_at' => $p->reversed_at, 'type' => 'vendor_payment', 'ref' => 'REV-' . $p->payment_number, 'detail' => 'عكس دفعة مورد: ' . $p->reversal_reason, 'amount' => $p->amount, 'user' => optional($p->reversedByUser)->name ?? 'System', 'link' => null, 'is_reversal' => true]);
            }
        }
        foreach ($wallet->transfersOut as $t) {
            $entries->push(['date' => $t->transfer_date, 'created_at' => $t->created_at, 'type' => 'transfer_out', 'ref' => $t->transfer_number, 'detail' => optional($t->toWallet)->name, 'amount' => -1 * $t->amount, 'user' => optional($t->creator)->name, 'link' => null, 'is_reversed' => $t->reversed_at !== null]);
            if ($t->reversed_at) {
                $entries->push(['date' => \Carbon\Carbon::parse($t->reversed_at), 'created_at' => $t->reversed_at, 'type' => 'transfer_out', 'ref' => 'REV-' . $t->transfer_number, 'detail' => 'عكس تحويل صادر: ' . $t->reversal_reason, 'amount' => $t->amount, 'user' => optional($t->reversedByUser)->name ?? 'System', 'link' => null, 'is_reversal' => true]);
            }
        }
        foreach ($wallet->transfersIn as $t) {
            $entries->push(['date' => $t->transfer_date, 'created_at' => $t->created_at, 'type' => 'transfer_in', 'ref' => $t->transfer_number, 'detail' => optional($t->fromWallet)->name, 'amount' => $t->amount, 'user' => optional($t->creator)->name, 'link' => null, 'is_reversed' => $t->reversed_at !== null]);
            if ($t->reversed_at) {
                $entries->push(['date' => \Carbon\Carbon::parse($t->reversed_at), 'created_at' => $t->reversed_at, 'type' => 'transfer_in', 'ref' => 'REV-' . $t->transfer_number, 'detail' => 'عكس تحويل وارد: ' . $t->reversal_reason, 'amount' => -$t->amount, 'user' => optional($t->reversedByUser)->name ?? 'System', 'link' => null, 'is_reversal' => true]);
            }
        }

        $timeline = $entries->sortBy('created_at')->values();

        $running = (float) $wallet->opening_balance;
        $timeline = $timeline->map(function ($entry) use (&$running) {
            $running += $entry['amount'];
            $entry['balance'] = $running;
            return $entry;
        });

        return [$timeline, $running];
    }

    private function formData(): array
    {
        $lookups = Cache::remember('system_settings', 60 * 60 * 24, function () {
            return Setting::all()->groupBy('category');
        });

        return [
            'types'      => $lookups->get('wallet_type') ?? collect(),
            'currencies' => $lookups->get('currency') ?? collect(),
        ];
    }

    private function validateData(Request $request, $ignoreId = null): array
    {
        return $request->validate([
            'name'            => 'required|string|max:255|unique:wallets,name' . ($ignoreId ? ",$ignoreId" : ''),
            'type'            => 'nullable|string',
            'opening_balance' => 'required|numeric',
            'currency'        => 'required|string',
            'notes'           => 'nullable|string',
        ], [
            'name.unique' => app()->getLocale() === 'ar' ? 'اسم المحفظة ده مستخدم قبل كده — اختر اسم تاني.' : 'This wallet name is already taken — choose another.',
        ]);
    }
}
