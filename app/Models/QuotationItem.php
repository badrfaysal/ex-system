<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id', 'item_id', 'item_code', 'description',
        'quantity', 'uom', 'list_price', 'discount_percent', 'tax_percent', 'net_total',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * يرجع البيان بحسب اللغة الحالية:
     * - لو السطر مرتبط بصنف وعنده اسم باللغة المطلوبة، نستخدمه.
     * - وإلا نرجع للـ description المحفوظ في سطر العرض.
     */
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
