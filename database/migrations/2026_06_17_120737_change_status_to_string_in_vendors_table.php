<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY هو صياغة MySQL فقط — على أي درايفر تاني (زي sqlite في التستات) العمود
        // بيتعرّف كـ string من الأساس، فمفيش داعي للتغيير
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vendors MODIFY status VARCHAR(50) NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vendors MODIFY status ENUM('active','on_hold','blocked') NOT NULL DEFAULT 'active'");
        }
    }
};
