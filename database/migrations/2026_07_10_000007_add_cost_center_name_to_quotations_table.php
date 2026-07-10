<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // اسم مركز التكلفة — يُقترح تلقائيًا عند إنشاء العرض ويمكن تعديله بحرية بعد ذلك
            $table->string('cost_center_name')->nullable()->after('opportunity_ref');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('cost_center_name');
        });
    }
};
