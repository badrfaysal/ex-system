<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * client_type كان ENUM محصور في (wholesale/retail/international) بس النظام
     * (settings category=client_type + بيانات العرض التجريبية) بيستخدم قيم زي
     * supermarket/restaurant/corporate — نفس الإصلاح اللي اتعمل قبل كده لـ vendors.status
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE clients MODIFY client_type VARCHAR(50) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE clients MODIFY client_type ENUM('wholesale','retail','international') NOT NULL");
        }
    }
};
