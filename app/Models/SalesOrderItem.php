<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id', 'item_id', 'item_code', 'description',
        'quantity', 'uom', 'list_price', 'discount_percent', 'tax_percent', 'net_total',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function salesInvoiceItems()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    /**
     * الكمية اللي اتفوترت فعليًا لحد دلوقتي (عبر فاتورة بيع واحدة أو أكتر)
     */
    public function getInvoicedQuantityAttribute(): float
    {
        return (float) $this->salesInvoiceItems()->sum('quantity');
    }

    public function displayDescription(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($this->item) {
            if ($locale === 'en' && !empty($this->item->name_en)) {
                return $this->item->name_en;
            }
            if ($locale === 'ar' && !empty($this->item->name_ar)) {
                return $this->item->name_ar;
            }
        }
        return $this->description ?? '';
    }
}
