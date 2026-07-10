<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // مفيش unique constraint على (category, key_value) في جدول settings — نتأكد يدويًا قبل الإدخال
        $existing = DB::table('settings')
            ->where('category', 'expense_category')
            ->pluck('key_value')
            ->all();

        $rows = [
            ['key_value' => 'transportation', 'display_name' => 'تنقلات'],
            ['key_value' => 'wages', 'display_name' => 'أجور'],
            ['key_value' => 'other', 'display_name' => 'أخرى'],
        ];

        foreach ($rows as $row) {
            if (in_array($row['key_value'], $existing, true)) {
                continue;
            }

            DB::table('settings')->insert([
                'category'     => 'expense_category',
                'key_value'    => $row['key_value'],
                'display_name' => $row['display_name'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('category', 'expense_category')->delete();
    }
};
