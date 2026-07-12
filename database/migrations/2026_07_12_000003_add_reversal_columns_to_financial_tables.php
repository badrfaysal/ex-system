<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = ['client_receipts', 'revenues', 'expenses', 'vendor_payments', 'wallet_transfers'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->timestamp('reversed_at')->nullable()->index();
                $t->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
                $t->text('reversal_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('reversed_by');
                $t->dropColumn(['reversed_at', 'reversal_reason']);
            });
        }
    }
};
