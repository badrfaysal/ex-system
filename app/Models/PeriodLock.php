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

    protected static function boot()
    {
        parent::boot();
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('active_period_locks');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('active_period_locks');
        });
    }

    public static function getCachedLocks()
    {
        return \Illuminate\Support\Facades\Cache::rememberForever('active_period_locks', function () {
            return static::active()->get();
        });
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

        foreach (static::getCachedLocks() as $lock) {
            if ($date >= $lock->start_date->toDateString() && $date <= $lock->end_date->toDateString()) {
                return true;
            }
        }

        return false;
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

        foreach (static::getCachedLocks() as $lock) {
            if ($date >= $lock->start_date->toDateString() && $date <= $lock->end_date->toDateString()) {
                return $lock;
            }
        }

        return null;
    }
}
