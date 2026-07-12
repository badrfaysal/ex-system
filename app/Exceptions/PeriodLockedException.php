<?php

namespace App\Exceptions;

use App\Models\PeriodLock;
use Exception;
use Illuminate\Support\Carbon;

class PeriodLockedException extends Exception
{
    public function __construct(public $date, ?PeriodLock $period = null)
    {
        $isAr = app()->getLocale() === 'ar';
        $dateStr = Carbon::parse($date)->format('Y-m-d');
        $period = $period ?? PeriodLock::lockedPeriodFor($date);

        $rangeStr = $period
            ? $period->start_date->format('Y-m-d') . ' → ' . $period->end_date->format('Y-m-d')
            : null;

        parent::__construct($isAr
            ? "هذه العملية بتاريخ {$dateStr} وقعت في فترة تم إغلاقها" . ($rangeStr ? " ({$rangeStr})" : '') . " — لا يمكن إنشاء أو تعديل أو حذف أو عكس أي عملية في هذه الفترة."
            : "This operation dated {$dateStr} falls within a closed period" . ($rangeStr ? " ({$rangeStr})" : '') . " — creating, editing, deleting, or reversing operations in this period is not allowed.");
    }
}
