<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    //
    protected $fillable = [
        'revenue_number', 'wallet_id', 'category', 'description', 
        'amount', 'currency', 'revenue_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'revenue_date' => 'date',
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
