<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();               // VP-2026-07-0001
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('purchase_invoice_id')->nullable()->constrained('purchase_invoices')->nullOnDelete();

            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency')->default('EGP');
            $table->date('payment_date');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('vendor_id');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payments');
    }
};
