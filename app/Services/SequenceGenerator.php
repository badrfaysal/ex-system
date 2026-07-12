<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * توليد أرقام مستندات فريدة وآمنة تزامنيًا (VP-2026-07-0001 وهكذا).
 *
 * المشكلة اللي بيحلّها: الطريقة القديمة كانت بتاخد "آخر id + 1" كرقم تسلسلي،
 * فلو طلبين وصلوا في نفس اللحظة كان الاتنين ممكن ياخدوا نفس الرقم — التاني
 * كان بيفشل بخطأ unique constraint غير واضح للمستخدم. هنا بنستخدم صف عداد
 * مخصص مقفول (SELECT ... FOR UPDATE) بدل قراءة آخر id، فمفيش سباق ممكن يحصل
 * حتى مع طلبات متزامنة كتير.
 */
class SequenceGenerator
{
    public static function next(string $prefix): string
    {
        return DB::transaction(function () use ($prefix) {
            // idempotent — لو الصف مش موجود يتعمل بأمان حتى مع تزامن
            DB::table('document_counters')->insertOrIgnore([
                'key'         => $prefix,
                'next_number' => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $counter = DB::table('document_counters')->where('key', $prefix)->lockForUpdate()->first();
            $seq = $counter->next_number;

            DB::table('document_counters')->where('key', $prefix)->update([
                'next_number' => $seq + 1,
                'updated_at'  => now(),
            ]);

            return $prefix . '-' . now()->format('Y-m') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
        });
    }
}
