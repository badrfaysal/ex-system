<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // عمود نص حر غير مستخدم — هيتستبدل بـ quotation_id كمرجع فعلي لمركز التكلفة
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('cost_center');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('cost_center');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('cost_center')->nullable();
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('cost_center')->nullable();
        });
    }
};
