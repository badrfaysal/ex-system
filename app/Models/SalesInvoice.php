<?php

namespace App\Models;

use App\Models\Concerns\EnforcesPeriodLock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    use HasFactory, EnforcesPeriodLock;

    protected $periodLockDateColumn = 'invoice_date';

    protected $fillable = [
        'invoice_number', 'sales_order_id', 'client_id', 'quotation_id', 'invoice_date', 'due_date', 'currency', 'notes',
        'subtotal', 'total_discount', 'tax_amount', 'grand_total', 'created_by', 'attachments',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'attachments'  => 'array',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
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

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->balance_due > 0.01;
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')->where('due_date', '<', now()->toDateString());
    }
}
