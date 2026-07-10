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
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('default_price_list_id')->nullable()->after('address');
            $table->string('default_sales_rep', 255)->nullable()->after('default_price_list_id');
            $table->string('default_currency', 10)->nullable()->after('default_sales_rep');

            $table->foreign('default_price_list_id')->references('id')->on('price_lists')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['default_price_list_id']);
            $table->dropColumn(['default_price_list_id', 'default_sales_rep', 'default_currency']);
        });
    }
};
