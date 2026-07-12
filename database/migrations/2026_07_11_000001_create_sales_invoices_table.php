<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();                 // SI-2026-07-0001
            $table->foreignId('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('quotation_id')->constrained('quotations')->restrictOnDelete(); // مركز التكلفة

            $table->date('invoice_date');
            $table->string('currency')->default('EGP');
            $table->text('notes')->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_discount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('sales_order_id');
            $table->index('quotation_id');
            $table->index('invoice_date');
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items')->restrictOnDelete(); // بند أمر البيع الأصلي — إجباري
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();

            $table->string('item_code')->nullable();
            $table->string('description');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('uom')->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('tax_percent', 8, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);

            $table->timestamps();

            $table->index('sales_invoice_id');
            $table->index('sales_order_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
    }
};
