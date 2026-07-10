<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تصحيح نوع عمود vendor_rating من decimal إلى string
     * لأن النظام يخزن تقييمات حرفية (A, B, C, D) وليس أرقاماً.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('vendor_rating', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('vendor_rating', 3, 2)->nullable()->change();
        });
    }
};
