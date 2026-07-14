<?php

namespace App\Models;

use App\Models\Concerns\EnforcesPeriodLock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory, EnforcesPeriodLock;

    protected $periodLockDateColumn = 'invoice_date';

    protected $fillable = [
        'invoice_number', 'vendor_invoice_number', 'quotation_id', 'sales_order_id', 'vendor_id', 'invoice_date', 'currency', 'notes',
        'subtotal', 'total_discount', 'tax_amount', 'grand_total', 'created_by', 'attachments',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'attachments'  => 'array',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->grand_total - $this->paid_amount;
    }
}
