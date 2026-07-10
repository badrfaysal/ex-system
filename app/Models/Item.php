<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code', 'barcode', 'name_ar', 'name_en', 'item_group', 'sub_group', 'base_uom',
        'reorder_point', 'min_stock', 'max_stock', 'default_vendor_id', 'supplier_part_number',
        'moq', 'lead_time_days', 'status', 'image_path', 'attachment_path','sub_category',
    'lead_time_days'
    ];


    public function defaultVendor()
    {
        // نربط الصنف بمودل المورد عن طريق عمود default_vendor_id
        return $this->belongsTo(Vendor::class, 'default_vendor_id');
    }

    /**
     * علاقة الصنف بالصور المتعددة (One to Many)
     */
    public function images()
    {
        return $this->hasMany(ItemImage::class);
    }
    
    public function approvedVendors() {
        return $this->belongsToMany(Vendor::class, 'item_vendor')
                    ->withPivot('last_purchase_price')
                    ->withTimestamps();
    }
    
}