<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    Schema::create('settings', function (Blueprint $table) {
        $table->id();
        $table->string('category')->index(); // نوع القائمة: uom, item_group, client_type...
        $table->string('display_name');      // الاسم الذي يظهر للمستخدم: كيلوجرام، جملة...
        $table->string('key_value');         // القيمة البرمجية التي تُحفظ في الداتا بيز: kg, wholesale...
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
