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
        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('extra_discount', 15, 2)->default(0)->after('subtotal');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('extra_discount', 15, 2)->default(0)->after('subtotal');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->decimal('extra_discount', 15, 2)->default(0)->after('subtotal');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->decimal('extra_discount', 15, 2)->default(0)->after('subtotal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('extra_discount');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('extra_discount');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn('extra_discount');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('extra_discount');
        });
    }
};
