<?php

namespace App\Models;

use App\Models\Concerns\EnforcesPeriodLock;
use App\Models\Concerns\Reversible;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorPayment extends Model
{
    use HasFactory, Reversible, EnforcesPeriodLock;

    protected $periodLockDateColumn = 'payment_date';

    protected $fillable = [
        'payment_number', 'vendor_id', 'purchase_invoice_id', 'wallet_id',
        'amount', 'currency', 'payment_date', 'payment_method', 'notes', 'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'reversed_at'  => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
