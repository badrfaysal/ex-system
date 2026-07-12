<?php

namespace App\Models;

use App\Models\Concerns\Reversible;
use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    use Reversible;

    protected $fillable = [
        'revenue_number', 'wallet_id', 'category', 'description',
        'amount', 'currency', 'revenue_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'revenue_date' => 'date',
        'reversed_at'  => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
