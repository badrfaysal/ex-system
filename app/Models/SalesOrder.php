<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'so_number', 'quotation_id', 'client_id',
        'so_date', 'currency', 'sales_rep', 'terms', 'status',
        'subtotal', 'total_discount', 'tax_amount', 'grand_total',
    ];

    protected $casts = [
        'so_date' => 'date',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(ClientReceipt::class);
    }

    public function getReceivedAmountAttribute(): float
    {
        return (float) $this->receipts()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->grand_total - $this->received_amount;
    }
}
