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
    Schema::create('clients', function (Blueprint $table) {
        $table->id();
        $table->string('company_name');
        $table->string('contact_person')->nullable();
        $table->string('phone');
        $table->string('email')->nullable();
        $table->string('country');
        $table->string('tax_id')->nullable();
        $table->enum('client_type', ['wholesale', 'retail', 'international']);
        $table->text('address')->nullable();
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
