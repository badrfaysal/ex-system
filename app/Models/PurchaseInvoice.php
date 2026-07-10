<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'quotation_id', 'invoice_date', 'currency', 'notes',
        'subtotal', 'total_discount', 'tax_amount', 'grand_total',
    ];

    protected $casts = [
        'invoice_date' => 'date',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }
}
