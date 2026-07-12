<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodLock extends Model
{
    protected $fillable = [
        'label', 'start_date', 'end_date', 'is_active', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * فيه فترة مقفولة نشطة بتغطي التاريخ ده؟
     */
    public static function isDateLocked($date): bool
    {
        if (!$date) {
            return false;
        }

        $date = \Illuminate\Support\Carbon::parse($date)->toDateString();

        return static::active()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    /**
     * الفترة المقفولة اللي بتغطي التاريخ ده (لو موجودة) — لعرض تفاصيلها في رسالة الخطأ.
     */
    public static function lockedPeriodFor($date): ?self
    {
        if (!$date) {
            return null;
        }

        $date = \Illuminate\Support\Carbon::parse($date)->toDateString();

        return static::active()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }
}
