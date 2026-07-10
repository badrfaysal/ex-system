<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();              // QT-2026-06-0012
            $table->date('quote_date');
            $table->date('expiry_date')->nullable();
            $table->string('opportunity_ref')->nullable();         // فرصة البيع CRM - OPP-7492

            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('price_list_id')->nullable()->constrained('price_lists')->nullOnDelete();

            $table->string('sales_rep')->nullable();               // مندوب المبيعات
            $table->string('currency')->default('EGP');
            $table->string('cost_center')->nullable();             // مركز التكلفة

            $table->string('status')->default('draft');            // draft / sent / converted / cancelled
            $table->text('terms')->nullable();                     // الشروط والأحكام

            // الإجماليات
            $table->decimal('subtotal', 15, 2)->default(0);        // الإجمالي المبدئي
            $table->decimal('total_discount', 15, 2)->default(0);  // إجمالي الخصومات
            $table->decimal('tax_amount', 15, 2)->default(0);      // ضريبة القيمة المضافة
            $table->decimal('grand_total', 15, 2)->default(0);     // الصافي النهائي

            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();

            $table->string('item_code')->nullable();
            $table->string('description');                         // اسم/وصف الصنف (قابل للتعديل)
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('uom')->nullable();                    // الوحدة
            $table->decimal('list_price', 15, 2)->default(0);     // سعر القائمة
            $table->decimal('discount_percent', 8, 2)->default(0);// الخصم %
            $table->decimal('tax_percent', 8, 2)->default(0);     // الضريبة %
            $table->decimal('net_total', 15, 2)->default(0);      // الإجمالي الصافي

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
