<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Wallet;
use App\Models\WalletTransfer;
use App\Rules\MatchesWalletCurrency;
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
                            ? "لا يمكن التحويل بين محفظتين بعملتين مختلفتين ({$from->currency} ≠ {$to->currency})."
                            : "Cannot transfer between wallets of different currencies ({$from->currency} ≠ {$to->currency}).");
                    }
                },
            ],
            'amount'          => 'required|numeric|min:0.01',
            'currency'        => ['required', 'string', new MatchesWalletCurrency('from_wallet_id')],
            'transfer_date'   => 'required|date',
            'notes'           => 'nullable|string',
        ], [
            'from_wallet_id.different' => $isAr ? 'لازم تختار محفظتين مختلفتين.' : 'Choose two different wallets.',
        ]);

        $data['created_by'] = Auth::id();

        try {
            $transfer = DB::transaction(function () use ($data) {
                // نقفل المحفظتين بترتيب ثابت (حسب id) لتفادي deadlock مع تحويل عكسي متزامن
                WalletLedger::lockMany([$data['from_wallet_id'], $data['to_wallet_id']]);

                // التحقق من كفاية رصيد محفظة المصدر — آمن حتى مع طلبات متزامنة على نفس المحفظة
                WalletLedger::lockAndCheck($data['from_wallet_id'], $data['amount']);

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
