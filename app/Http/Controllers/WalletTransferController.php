<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Wallet;
use App\Models\WalletTransfer;
use App\Services\SequenceGenerator;
use App\Services\WalletLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletTransferController extends Controller
{
    public function create(Request $request)
    {
        return view('wallet_transfers.create', [
            'wallets'          => Wallet::orderBy('name')->get(),
            'selectedFromId'   => $request->integer('from_wallet_id') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $isAr = app()->getLocale() === 'ar';

        $data = $request->validate([
            'from_wallet_id'  => 'required|exists:wallets,id|different:to_wallet_id',
            'to_wallet_id'    => [
                'required',
                'exists:wallets,id',
                function ($attribute, $value, $fail) use ($request, $isAr) {
                    $from = Wallet::find($request->input('from_wallet_id'));
                    $to   = Wallet::find($value);
                    if ($from && $to && $from->currency !== $to->currency) {
                        $fail($isAr
                            ? "لا يمكن التحويل بين حسابين بعملتين مختلفتين ({$from->currency} ≠ {$to->currency})."
                            : "Cannot transfer between accounts of different currencies ({$from->currency} ≠ {$to->currency}).");
                    }
                },
            ],
            'amount'          => 'required|numeric|min:0.01',
            'transfer_date'   => 'required|date',
            'notes'           => 'nullable|string',
        ], [
            'from_wallet_id.different' => $isAr ? 'لازم تختار حسابين مختلفين.' : 'Choose two different accounts.',
        ]);

        $data['created_by'] = Auth::id();

        try {
            $transfer = DB::transaction(function () use (&$data) {
                // نقفل الحسابين بترتيب ثابت (حسب id) لتفادي deadlock مع تحويل عكسي متزامن
                WalletLedger::lockMany([$data['from_wallet_id'], $data['to_wallet_id']]);

                // التحقق من كفاية رصيد حساب المصدر — آمن حتى مع طلبات متزامنة على نفس الحساب
                $fromWallet = WalletLedger::lockAndCheck($data['from_wallet_id'], $data['amount']);

                // العملة مقفولة على عملة حساب المصدر — مش بتُقبل من المستخدم أصلاً
                $data['currency'] = $fromWallet->currency;
                $data['transfer_number'] = SequenceGenerator::next('TR');

                return WalletTransfer::create($data);
            });
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('wallets.show', $transfer->from_wallet_id)
            ->with('success', app()->getLocale() === 'ar' ? 'تم التحويل بنجاح' : 'Transfer completed successfully');
    }
}
