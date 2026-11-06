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

    /**
     * يرجع وحدة القياس بحسب اللغة الحالية (عربي / إنجليزي):
     * يترجم الوحدة سواء كانت مسجلة بالعربي أو الإنجليزي أو كود برمجي.
     */
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
