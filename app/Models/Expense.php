<?php

namespace App\Models;

use App\Models\Concerns\EnforcesPeriodLock;
use App\Models\Concerns\Reversible;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory, Reversible, EnforcesPeriodLock;

    protected $periodLockDateColumn = 'expense_date';

    protected $fillable = [
        'expense_number', 'quotation_id', 'category', 'description',
        'vendor_id', 'wallet_id', 'amount', 'currency', 'expense_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'reversed_at'  => 'datetime',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
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
