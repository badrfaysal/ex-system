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
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('notes');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
