<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number', 'client_id', 'sales_order_id', 'quotation_id',
        'amount', 'currency', 'receipt_date', 'payment_method', 'notes',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
}
