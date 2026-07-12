<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();                          // اسم المحفظة — لازم يبقى فريد
            $table->string('type')->nullable();                        // من settings: category=wallet_type (بنك/نقدية)
            $table->decimal('opening_balance', 15, 2)->default(0);      // رصيد بداية المدة
            $table->string('currency')->default('EGP');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
