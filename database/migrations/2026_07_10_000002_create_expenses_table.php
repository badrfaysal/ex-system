<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();              // EXP-2026-07-0001
            $table->foreignId('quotation_id')->constrained('quotations')->restrictOnDelete(); // مركز التكلفة

            $table->string('category');                               // من settings: category=expense_category
            $table->string('description')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();

            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency')->default('EGP');
            $table->date('expense_date');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('quotation_id');
            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
