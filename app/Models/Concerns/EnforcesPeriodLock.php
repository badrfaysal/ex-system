<?php

namespace App\Models\Concerns;

use App\Exceptions\PeriodLockedException;
use App\Models\PeriodLock;

/**
 * يمنع أي إنشاء/تعديل/حذف/عكس على موديل بتاريخ واقع في فترة مقفولة.
 *
 * الموديل اللي بيستخدم الـ trait ده لازم يعرّف $periodLockDateColumn باسم
 * عمود التاريخ اللي بيمثل تاريخ العملية الفعلي (مثلاً invoice_date، receipt_date).
 * بيشتغل على saving() (بتغطي create/update/reverseOperation ده كله بيعدي بـ save())
 * و deleting() — يعني تغطية كاملة من غير ما نلمس كل controller على حدة.
 */
trait EnforcesPeriodLock
{
    protected static function bootEnforcesPeriodLock(): void
    {
        static::saving(function ($model) {
            $model->assertPeriodNotLocked();
        });

        static::deleting(function ($model) {
            $model->assertPeriodNotLocked();
        });
    }

    public function assertPeriodNotLocked(): void
    {
        $column = $this->periodLockDateColumn ?? null;
        if (!$column) {
            return;
        }

        $date = $this->{$column};
        if ($date && PeriodLock::isDateLocked($date)) {
            throw new PeriodLockedException($date);
        }
    }
}
