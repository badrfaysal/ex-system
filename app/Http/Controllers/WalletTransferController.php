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
            'to_wallet_id'    => 'required|exists:wallets,id',
            'amount'          => 'required|numeric|min:0.01',
            'exchange_rate'   => 'nullable|numeric|min:0.000001',
            'transfer_date'   => 'required|date',
            'notes'           => 'nullable|string',
        ], [
            'from_wallet_id.different' => $isAr ? 'لازم تختار حسابين مختلفين.' : 'Choose two different accounts.',
        ]);

        $data['created_by'] = Auth::id();

        try {
            $transfer = DB::transaction(function () use (&$data) {
                WalletLedger::lockMany([$data['from_wallet_id'], $data['to_wallet_id']]);

                $fromWallet = WalletLedger::lockAndCheck($data['from_wallet_id'], $data['amount']);
                $toWallet = Wallet::find($data['to_wallet_id']);

                $data['currency'] = $fromWallet->currency;
                $data['transfer_number'] = SequenceGenerator::next('TR');
                
                $data['exchange_rate'] = $data['exchange_rate'] ?? 1;
                if ($fromWallet->currency === $toWallet->currency) {
                    $data['exchange_rate'] = 1;
                    $data['converted_amount'] = $data['amount'];
                } else {
                    $data['converted_amount'] = round($data['amount'] * $data['exchange_rate'], 2);
                }

                return WalletTransfer::create($data);
            });
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('wallets.show', $transfer->from_wallet_id)
            ->with('success', app()->getLocale() === 'ar' ? 'تم التحويل بنجاح' : 'Transfer completed successfully');
    }
}
