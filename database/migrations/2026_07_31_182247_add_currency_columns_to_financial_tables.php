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
        Schema::table('wallet_transfers', function (Blueprint $table) {
            $table->decimal('converted_amount', 15, 2)->nullable()->after('amount');
            $table->decimal('exchange_rate', 15, 6)->default(1)->after('converted_amount');
        });

        $tables = ['revenues', 'expenses', 'client_receipts', 'vendor_payments'];
        foreach ($tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->decimal('foreign_amount', 15, 2)->nullable()->after('amount');
                $table->string('foreign_currency', 3)->nullable()->after('foreign_amount');
                $table->decimal('exchange_rate', 15, 6)->default(1)->after('foreign_currency');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transfers', function (Blueprint $table) {
            $table->dropColumn(['converted_amount', 'exchange_rate']);
        });

        $tables = ['revenues', 'expenses', 'client_receipts', 'vendor_payments'];
        foreach ($tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropColumn(['foreign_amount', 'foreign_currency', 'exchange_rate']);
            });
        }
    }
};
