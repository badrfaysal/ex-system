<?php

namespace App\Models;

use App\Models\Concerns\EnforcesPeriodLock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory, EnforcesPeriodLock;

    protected $periodLockDateColumn = 'so_date';

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

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    /**
     * إجمالي المفوتر فعليًا من هذا الأمر (عبر فاتورة بيع واحدة أو أكتر — فوترة جزئية)
     */
    public function getInvoicedAmountAttribute(): float
    {
        return (float) $this->salesInvoices()->sum('grand_total');
    }

    /**
     * أوامر البيع اللي عندها فاتورة شراء بدون فاتورة بيع، أو العكس
     */
    public function scopeWithMismatchedInvoices($query)
    {
        return $query
            ->where(function ($q) {
                $q->whereHas('purchaseInvoices')->whereDoesntHave('salesInvoices');
            })
            ->orWhere(function ($q) {
                $q->whereHas('salesInvoices')->whereDoesntHave('purchaseInvoices');
            });
    }
}
