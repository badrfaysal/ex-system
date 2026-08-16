<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $existing = DB::table('settings')->where('category', 'country')->pluck('key_value')->all();

        $rows = [
            ['key_value' => 'EG', 'display_name' => 'مصر'],
            ['key_value' => 'SA', 'display_name' => 'السعودية'],
            ['key_value' => 'AE', 'display_name' => 'الإمارات'],
            ['key_value' => 'KW', 'display_name' => 'الكويت'],
            ['key_value' => 'QA', 'display_name' => 'قطر'],
            ['key_value' => 'BH', 'display_name' => 'البحرين'],
            ['key_value' => 'OM', 'display_name' => 'عمان'],
            ['key_value' => 'JO', 'display_name' => 'الأردن'],
        ];

        foreach ($rows as $row) {
            if (in_array($row['key_value'], $existing, true)) {
                continue;
            }

            DB::table('settings')->insert([
                'category'     => 'country',
                'key_value'    => $row['key_value'],
                'display_name' => $row['display_name'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        Cache::forget('system_settings');
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('category', 'country')
            ->whereIn('key_value', ['EG', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM', 'JO'])
            ->delete();

        Cache::forget('system_settings');
    }
};
