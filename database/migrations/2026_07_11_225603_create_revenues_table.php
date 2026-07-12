<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->string('revenue_number')->unique(); // رقم الحركة
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            
            $table->string('category')->nullable(); // تصنيف الإيراد
            $table->text('description')->nullable(); // الوصف/السبب

            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('EGP');
            $table->date('revenue_date');
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenues');
    }
};
