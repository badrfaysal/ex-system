<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printed_labels', function (Blueprint $table) {
            $table->id();
            $table->string('brand_name')->nullable();
            $table->string('item_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('manufacturer_address')->nullable();
            $table->string('exporter')->nullable();
            $table->string('exporter_address')->nullable();
            $table->string('importer')->nullable();
            $table->string('importer_address')->nullable();
            $table->string('production_date')->nullable();
            $table->string('expiry_date')->nullable();
            $table->string('batch_code')->nullable();
            $table->string('website')->nullable();
            $table->integer('copies')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printed_labels');
    }
};
