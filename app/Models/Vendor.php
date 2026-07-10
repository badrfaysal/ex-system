<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_code', 'name_ar', 'name_en', 'legal_name', 'vendor_group', 
        'status', 'block_reason', 'phone', 'mobile', 'email', 'website',
        'contact_person_name', 'contact_person_job', 'contact_person_mobile', 'contact_person_email',
        'default_currency', 'tax_id', 'commercial_registry', 'credit_limit',
        'payment_terms', 'payment_method', 'bank_name', 'bank_branch',
        'account_holder', 'account_number', 'iban', 'swift_code', 'lead_time_days', 'vendor_rating',
        'attachment_path'
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'default_vendor_id');
    }


    public function approvedItems() {
        return $this->belongsToMany(Item::class, 'item_vendor')
                    ->withPivot('last_purchase_price')
                    ->withTimestamps();
    }

    public function invoiceItems()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    /**
     * الرصيد المستحق للمورد: إجمالي أسطر فواتير الشراء - إجمالي المدفوع
     */
    public function getBalanceDueAttribute(): float
    {
        $invoiced = (float) $this->invoiceItems()->sum('net_total');
        $paid     = (float) $this->payments()->sum('amount');
        return $invoiced - $paid;
    }
}