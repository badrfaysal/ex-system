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

        // خدمات أخرى / شهادات مسجلة في الإعدادات
        $service = \App\Models\Setting::findOtherService($this->item_code, $this->description);
        if ($service) {
            if ($locale === 'en' && !empty($service['name_en'])) {
                return $service['name_en'];
            }
            if ($locale === 'ar' && !empty($service['name_ar'])) {
                return $service['name_ar'];
            }
        }

        return $this->description ?? '';
    }

    public function displayUom(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $raw = $this->uom ?: optional($this->item)->base_uom;
        return \App\Services\UomHelper::translate($raw, $locale);
    }

    /**
     * هل هذا السطر يمثل بند شهادات أو خدمات خاصة مميزة؟
     */
    public function isCertificate(): bool
    {
        $code = strtoupper(trim((string) $this->item_code));
        $desc = strtolower(trim((string) $this->description));

        if ($code === 'CERT'
            || str_contains($desc, 'certificate')
            || str_contains($desc, 'شهادات')
            || str_contains($desc, 'شهادة')) {
            return true;
        }

        return \App\Models\Setting::isOtherServiceCodeOrName($this->item_code, $this->description);
    }
}
