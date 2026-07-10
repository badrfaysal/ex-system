<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // التحقق أولاً: هل العمود غير موجود؟ إذاً قم بإضافته
        if (!Schema::hasColumn('vendors', 'vendor_rating')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('vendor_rating', 10)->nullable()->after('status');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('vendors', 'vendor_rating')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropColumn('vendor_rating');
            });
        }
    }
};