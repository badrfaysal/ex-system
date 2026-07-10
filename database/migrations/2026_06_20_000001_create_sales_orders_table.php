<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number')->unique();                   // SO-2026-06-0001
            $table->foreignId('quotation_id')->nullable()
                  ->constrained('quotations')->nullOnDelete();        // عرض السعر الأصلي
            $table->foreignId('client_id')
                  ->constrained('clients')->cascadeOnDelete();

            $table->date('so_date');
            $table->string('currency')->default('EGP');
            $table->string('sales_rep')->nullable();
            $table->string('cost_center')->nullable();
            $table->text('terms')->nullable();
            $table->string('status')->default('confirmed');          // confirmed / cancelled

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total_discount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->timestamps();

            // indexes للحقول الأكثر استخداماً في البحث والفلترة — O(1) lookup
            $table->index('quotation_id');   // جلب أوامر بيع عرض معين
            $table->index('client_id');      // أوامر بيع عميل معين
            $table->index('so_date');        // فلترة بالتاريخ
            $table->index('status');         // فلترة بالحالة
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')
                  ->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()
                  ->constrained('items')->nullOnDelete();

            $table->string('item_code')->nullable();
            $table->string('description');
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('uom')->nullable();
            $table->decimal('list_price', 15, 2)->default(0);
            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('tax_percent', 8, 2)->default(0);
            $table->decimal('net_total', 15, 2)->default(0);

            $table->timestamps();

            // الـ FK هو الـ index الأكثر استخداماً — O(1) بالـ PK
            $table->index('sales_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
