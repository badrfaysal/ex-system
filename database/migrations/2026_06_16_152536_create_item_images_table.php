<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('item_images', function (Blueprint $table) {
            $table->id();
            // ربط الصورة بالصنف، مع الحذف التلقائي للصور لو تم حذف الصنف
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('image_path'); // مسار الصورة
            $table->string('category')->nullable(); // فئة الصورة (قبل الفتح، الخ)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('item_images');
    }
};