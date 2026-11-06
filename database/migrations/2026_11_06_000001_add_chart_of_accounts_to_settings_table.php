<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'code')) {
                $table->string('code', 50)->nullable()->after('key_value');
            }
            if (!Schema::hasColumn('settings', 'name_en')) {
                $table->string('name_en', 255)->nullable()->after('display_name');
            }
            if (!Schema::hasColumn('settings', 'classification')) {
                $table->string('classification', 255)->nullable()->after('parent_key');
            }
            if (!Schema::hasColumn('settings', 'nature')) {
                $table->string('nature', 50)->default('Debit')->after('classification');
            }
            if (!Schema::hasColumn('settings', 'allow_posting')) {
                $table->boolean('allow_posting')->default(true)->after('nature');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $cols = ['code', 'name_en', 'classification', 'nature', 'allow_posting'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
