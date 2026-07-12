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
     * تحميل مجاميع الحركات مع الاستعلام لتفادي N+1 عند عرض قائمة المحافظ.
     * كل withSum بيولّد attribute بصيغة {relation}_sum_amount.
     * الحركات المعكوسة (reversed_at) مستبعدة من المجموع — أثرها اتلغى بالكامل.
     */
    public function scopeWithBalanceSums($query)
    {
        $notReversed = fn ($q) => $q->whereNull('reversed_at');

        return $query
            ->withSum(['receipts' => $notReversed], 'amount')
            ->withSum(['revenues' => $notReversed], 'amount')
            ->withSum(['expenses' => $notReversed], 'amount')
            ->withSum(['vendorPayments' => $notReversed], 'amount')
            ->withSum(['transfersOut' => $notReversed], 'amount')
            ->withSum(['transfersIn' => $notReversed], 'amount');
    }

    /**
     * الرصيد الحالي = رصيد بداية المدة + سندات القبض + الإيرادات المباشرة
     *   - المصروفات - سندات دفع الموردين - تحويلات صادرة + تحويلات واردة
     *
     * العملة موحّدة على مستوى المحفظة (يُفرض عند تسجيل أي حركة)، فالجمع سليم.
     * الحركات المعكوسة مستبعدة من الحساب في الحالتين (محمّلة مسبقًا أو مستعلَمة مباشرة).
     * يستخدم المجاميع المحمّلة مسبقًا (scopeWithBalanceSums) إن وُجدت، وإلا يستعلم.
     */
    public function getCurrentBalanceAttribute(): float
    {
        $sum = fn (string $key, string $relation): float => array_key_exists($key, $this->attributes)
            ? (float) $this->attributes[$key]
            : (float) $this->$relation()->whereNull('reversed_at')->sum('amount');

        return (float) $this->opening_balance
            + $sum('receipts_sum_amount', 'receipts')
            + $sum('revenues_sum_amount', 'revenues')
            - $sum('expenses_sum_amount', 'expenses')
            - $sum('vendor_payments_sum_amount', 'vendorPayments')
            - $sum('transfers_out_sum_amount', 'transfersOut')
            + $sum('transfers_in_sum_amount', 'transfersIn');
    }
}
