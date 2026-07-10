<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    // تحديد الحقول المسموح بحفظها لتجنب ثغرة Mass Assignment
    protected $fillable = [
        'company_name',
        'company_name_en',
        'contact_person',
        'phone',
        'email',
        'country',
        'tax_id',
        'client_type',
        'address',
        'default_price_list_id',
        'default_sales_rep',
        'default_currency',
    ];

    public function defaultPriceList()
    {
        return $this->belongsTo(\App\Models\PriceList::class, 'default_price_list_id');
    }

    public function quotations()
    {
        return $this->hasMany(\App\Models\Quotation::class);
    }

    public function salesOrders()
    {
        return $this->hasMany(\App\Models\SalesOrder::class);
    }

    public function receipts()
    {
        return $this->hasMany(\App\Models\ClientReceipt::class);
    }

    /**
     * الرصيد المستحق على العميل: إجمالي أوامر البيع - إجمالي المحصّل
     */
    public function getBalanceDueAttribute(): float
    {
        $ordered   = (float) $this->salesOrders()->sum('grand_total');
        $collected = (float) $this->receipts()->sum('amount');
        return $ordered - $collected;
    }

    /**
     * يرجع اسم الشركة بحسب اللغة الحالية مع fallback للعربي
     */
    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'en' && !empty($this->company_name_en)) {
            return $this->company_name_en;
        }
        return $this->company_name ?? '';
    }
}