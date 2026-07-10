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
    Schema::create('vendor_addresses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
        $table->enum('address_type', ['head_office', 'pickup_shipping', 'other']); // تحديد نوع العنوان [cite: 14]
        $table->text('address_details'); // يتيح إضافة أكثر من عنوان [cite: 14]
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_addresses');
    }
};
