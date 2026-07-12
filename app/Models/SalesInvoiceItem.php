<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_invoice_id', 'sales_order_item_id', 'item_id',
        'item_code', 'description', 'quantity', 'uom', 'unit_price',
        'discount_percent', 'tax_percent', 'net_total',
    ];

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
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
