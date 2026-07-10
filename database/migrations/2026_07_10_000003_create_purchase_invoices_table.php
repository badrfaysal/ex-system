<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();               // PI-2026-07-0001
            $table->foreignId('quotation_id')->constrained('quotations')->restrictOnDelete(); // مركز التكلفة

            $table->date('invoice_date');
            $table->string('currency')->default('EGP');
            $table->text('notes')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_discount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->timestamps();

            $table->index('quotation_id');
            $table->index('invoice_date');
        });

        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete(); // مورد كل سطر لوحده
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('quotation_item_id')->nullable()->constrained('quotation_items')->nullOnDelete(); // null = صنف إضافي

            $table->string('item_code')->nullable();
            $table->string('description');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('uom')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('tax_percent', 8, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);

            $table->timestamps();

            $table->index('purchase_invoice_id');
            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
        Schema::dropIfExists('purchase_invoices');
    }
};
