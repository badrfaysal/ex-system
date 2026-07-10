<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['category', 'display_name', 'key_value', 'parent_key'];

    // هذه الدالة السحرية ستمسح الكاش تلقائياً عند إضافة أو تعديل أي إعداد
    protected static function booted()
    {
        static::saved(fn() => Cache::forget('system_settings'));
        static::deleted(fn() => Cache::forget('system_settings'));
    }
}