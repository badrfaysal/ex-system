<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_number', 'quote_date', 'expiry_date', 'opportunity_ref', 'cost_center_name',
        'client_id', 'price_list_id', 'sales_rep', 'currency',
        'status', 'terms',
        'subtotal', 'total_discount', 'tax_amount', 'grand_total',
    ];

    protected $casts = [
        'quote_date'  => 'date',
        'expiry_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function priceList()
    {
        return $this->belongsTo(PriceList::class);
    }

    public function items()
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function sends()
    {
        return $this->hasMany(QuotationSend::class)->latest('sent_at');
    }

    public function salesOrders()
    {
        return $this->hasMany(\App\Models\SalesOrder::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function receipts()
    {
        return $this->hasMany(ClientReceipt::class);
    }

    /**
     * إجمالي الإيراد المحصّل فعليًا (سندات القبض) لمركز التكلفة ده
     */
    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->receipts()->sum('amount');
    }

    /**
     * إجمالي التكلفة: مصروفات + فواتير شراء
     */
    public function getTotalCostAttribute(): float
    {
        return (float) $this->expenses()->sum('amount') + (float) $this->purchaseInvoices()->sum('grand_total');
    }

    public function getProfitAttribute(): float
    {
        return $this->total_revenue - $this->total_cost;
    }

    /**
     * عروض الأسعار اللي عندها فاتورة شراء بدون أمر بيع، أو أمر بيع بدون فاتورة شراء
     */
    public function scopeWithMismatchedDocs($query)
    {
        return $query
            ->where(function ($q) {
                $q->whereHas('purchaseInvoices')->whereDoesntHave('salesOrders');
            })
            ->orWhere(function ($q) {
                $q->whereHas('salesOrders')->whereDoesntHave('purchaseInvoices');
            });
    }
}
