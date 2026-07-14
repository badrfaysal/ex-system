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
        Schema::table('period_locks', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('open_reason')->nullable();
            $table->timestamp('reclosed_at')->nullable();
            $table->foreignId('reclosed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('period_locks', function (Blueprint $table) {
            $table->dropForeign(['opened_by']);
            $table->dropForeign(['reclosed_by']);
            $table->dropColumn(['opened_at', 'opened_by', 'open_reason', 'reclosed_at', 'reclosed_by']);
        });
    }
};
