<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $existing = DB::table('settings')->where('category', 'wallet_type')->pluck('key_value')->all();

        $rows = [
            ['key_value' => 'bank', 'display_name' => 'بنك'],
            ['key_value' => 'cash', 'display_name' => 'نقدية'],
        ];

        foreach ($rows as $row) {
            if (in_array($row['key_value'], $existing, true)) {
                continue;
            }

            DB::table('settings')->insert([
                'category'     => 'wallet_type',
                'key_value'    => $row['key_value'],
                'display_name' => $row['display_name'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('category', 'wallet_type')->delete();
    }
};
