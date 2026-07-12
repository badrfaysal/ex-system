<?php

namespace App\Models;

use App\Models\Concerns\Reversible;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransfer extends Model
{
    use HasFactory, Reversible;

    protected $fillable = [
        'transfer_number', 'from_wallet_id', 'to_wallet_id',
        'amount', 'currency', 'transfer_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'reversed_at'   => 'datetime',
    ];

    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
