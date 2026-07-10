<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            // التحقق قبل الإضافة لتجنب خطأ التكرار
            if (!Schema::hasColumn('items', 'sub_category')) {
                $table->string('sub_category')->nullable()->after('item_group');
            }
            
            if (!Schema::hasColumn('items', 'lead_time_days')) {
                $table->integer('lead_time_days')->nullable()->after('moq');
            }
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'sub_category')) {
                $table->dropColumn('sub_category');
            }
            if (Schema::hasColumn('items', 'lead_time_days')) {
                $table->dropColumn('lead_time_days');
            }
        });
    }
};