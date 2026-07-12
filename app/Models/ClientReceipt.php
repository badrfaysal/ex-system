<?php

namespace App\Models;

use App\Models\Concerns\Reversible;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientReceipt extends Model
{
    use HasFactory, Reversible;

    protected $fillable = [
        'receipt_number', 'client_id', 'sales_invoice_id', 'quotation_id', 'wallet_id',
        'amount', 'currency', 'receipt_date', 'payment_method', 'notes', 'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'reversed_at'  => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
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
