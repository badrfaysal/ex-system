<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Wallet;
use Illuminate\Support\Collection;

/**
 * يضمن إن التحقق من رصيد المحفظة والخصم منها عملية آمنة تزامنيًا.
 *
 * المشكلة اللي بيحلّها: لو طلبين وصلوا لنفس المحفظة في نفس اللحظة، وكل واحد
 * قرأ الرصيد قبل ما التاني يخصم، الاتنين ممكن يعدّوا التحقق ويسحبوا فوق
 * الرصيد الفعلي (Time-of-check to time-of-use race). الحل: قفل صف المحفظة
 * (SELECT ... FOR UPDATE) جوه Transaction، فأي طلب تاني على نفس المحفظة
 * بيستنى لحد ما الأول يخلّص (commit/rollback) قبل ما ياخد نسخته من الرصيد.
 *
 * الاستخدام لازم يكون دايمًا جوه DB::transaction(...).
 */
class WalletLedger
{
    /**
     * يقفل صف المحفظة ويتحقق إن المبلغ المطلوب مايتجاوزش الرصيد المتاح.
     *
     * @param  float  $excludeAmount  مبلغ يُضاف مؤقتًا للمتاح — يُستخدم عند التعديل
     *                                 على حركة موجودة (بنرجّع مبلغها القديم قبل ما
     *                                 نتحقق من المبلغ الجديد، عشان التعديل بنفس
     *                                 القيمة ميترفضش غلط).
     *
     * @throws InsufficientBalanceException
     */
    public static function lockAndCheck(int $walletId, float $amount, float $excludeAmount = 0): Wallet
    {
        $wallet = Wallet::lockForUpdate()->findOrFail($walletId);

        $available = $wallet->current_balance + $excludeAmount;

        // مقارنة بدقة عشرية ثابتة لتفادي أخطاء float (خصوصًا لو المبلغ مساوي للمتاح تمامًا)
        if (round($amount, 2) > round($available, 2)) {
            throw new InsufficientBalanceException($wallet, $amount, $available);
        }

        return $wallet;
    }

    /**
     * يقفل عدة محافظ بترتيب ثابت (حسب id) لتفادي deadlock لو حصل تحويل
     * بالعكس (من ب لأ) في نفس اللحظة اللي فيها تحويل من أ لـ ب.
     *
     * @return Collection<int, Wallet>
     */
    public static function lockMany(array $walletIds): Collection
    {
        return Wallet::whereIn('id', array_unique($walletIds))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }
}
