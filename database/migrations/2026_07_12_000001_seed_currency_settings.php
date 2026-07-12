<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $existing = DB::table('settings')->where('category', 'currency')->pluck('key_value')->all();

        $rows = [
            ['key_value' => 'EGP', 'display_name' => 'جنيه مصري'],
            ['key_value' => 'USD', 'display_name' => 'دولار أمريكي'],
            ['key_value' => 'EUR', 'display_name' => 'يورو'],
            ['key_value' => 'SAR', 'display_name' => 'ريال سعودي'],
            ['key_value' => 'AED', 'display_name' => 'درهم إماراتي'],
        ];

        foreach ($rows as $row) {
            if (in_array($row['key_value'], $existing, true)) {
                continue;
            }

            DB::table('settings')->insert([
                'category'     => 'currency',
                'key_value'    => $row['key_value'],
                'display_name' => $row['display_name'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // مسح كاش الإعدادات عشان تظهر العملات فورًا في الفورمات
        Cache::forget('system_settings');
    }

    public function down(): void
    {
        // نحذف العملات المزروعة فقط — من غير ما نمسّ أي عملات أضافها المستخدم يدويًا
        DB::table('settings')
            ->where('category', 'currency')
            ->whereIn('key_value', ['EGP', 'USD', 'EUR', 'SAR', 'AED'])
            ->delete();

        Cache::forget('system_settings');
    }
};
