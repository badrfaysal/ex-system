<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletTransfer;
use App\Rules\MatchesWalletCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletTransferController extends Controller
{
    public function create(Request $request)
    {
        return view('wallet_transfers.create', [
            'wallets'          => Wallet::orderBy('name')->get(),
            'nextNumber'       => $this->nextNumber(),
            'selectedFromId'   => $request->integer('from_wallet_id') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $isAr = app()->getLocale() === 'ar';

        $data = $request->validate([
            'transfer_number' => 'required|string|unique:wallet_transfers,transfer_number',
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

        $transfer = DB::transaction(fn () => WalletTransfer::create($data));

        return redirect()->route('wallets.show', $transfer->from_wallet_id)
            ->with('success', app()->getLocale() === 'ar' ? 'تم التحويل بنجاح' : 'Transfer completed successfully');
    }

    private function nextNumber(): string
    {
        $last = WalletTransfer::latest('id')->first();
        $seq  = $last ? $last->id + 1 : 1;
        return 'TR-' . now()->format('Y-m') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
