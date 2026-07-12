<?php

namespace App\Models\Concerns;

use App\Models\User;

/**
 * يضيف قدرة "عكس العملية" للسندات المالية (قبض/دفع/مصروف/إيراد/تحويل).
 *
 * العملية المعكوسة بتفضل موجودة في السجل (شفافية تدقيق) لكن بتتشال من
 * حساب رصيد المحفظة والإجماليات — بدل ما نمسحها أو نعدّل مبلغها، وده
 * بيحافظ على أثر تعديل واضح: مين عكسها وإمتى وليه.
 */
trait Reversible
{
    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }

    public function reversedByUser()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function scopeNotReversed($query)
    {
        return $query->whereNull('reversed_at');
    }

    /**
     * يعكس أثر العملية بالكامل — بيتسجّل مين عملها وإمتى وليه، من غير ما يمسح السطر الأصلي.
     */
    public function reverseOperation(string $reason, int $byUserId): void
    {
        $this->forceFill([
            'reversed_at'     => now(),
            'reversed_by'     => $byUserId,
            'reversal_reason' => $reason,
        ])->save();
    }
}
