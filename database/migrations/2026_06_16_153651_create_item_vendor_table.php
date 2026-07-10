<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('item_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->decimal('last_purchase_price', 10, 2)->nullable(); // آخر سعر شراء
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('item_vendor');
    }
};