<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'opening_balance', 'currency', 'notes'];

    public function receipts()
    {
        return $this->hasMany(ClientReceipt::class);
    }

    public function revenues()
    {
        return $this->hasMany(Revenue::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function vendorPayments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function transfersOut()
    {
        return $this->hasMany(WalletTransfer::class, 'from_wallet_id');
    }

    public function transfersIn()
    {
        return $this->hasMany(WalletTransfer::class, 'to_wallet_id');
    }

    /**
     * الرصيد الحالي = رصيد بداية المدة + سندات القبض - المصروفات - سندات دفع الموردين - تحويلات صادرة + تحويلات واردة
     */
    public function getCurrentBalanceAttribute(): float
    {
        return (float) $this->opening_balance
            + (float) $this->receipts()->sum('amount')
            - (float) $this->expenses()->sum('amount')
            - (float) $this->vendorPayments()->sum('amount')
            - (float) $this->transfersOut()->sum('amount')
            + (float) $this->transfersIn()->sum('amount');
    }
}
