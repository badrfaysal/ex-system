<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();               // RC-2026-07-0001
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
            $table->foreignId('quotation_id')->constrained('quotations')->restrictOnDelete(); // مركز التكلفة (منسوخ من أمر البيع)

            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency')->default('EGP');
            $table->date('receipt_date');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('client_id');
            $table->index('quotation_id');
            $table->index('receipt_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_receipts');
    }
};
