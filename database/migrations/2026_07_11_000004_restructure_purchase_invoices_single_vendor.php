<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * فاتورة الشراء بقت لازم مورد واحد بس على مستوى الفاتورة (مش كل سطر بمورده)،
     * ومصدرها بقى أمر البيع مش عرض السعر مباشرة، والأصناف بقت حرة من كل الكتالوج
     * (مش مربوطة ببند عرض سعر معيّن).
     */
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'sales_order_id')) {
                $table->foreignId('sales_order_id')->after('quotation_id')
                    ->constrained('sales_orders')->restrictOnDelete();
                $table->index('sales_order_id');
            }
            if (!Schema::hasColumn('purchase_invoices', 'vendor_id')) {
                $table->foreignId('vendor_id')->after('sales_order_id')
                    ->constrained('vendors')->restrictOnDelete();
                $table->index('vendor_id');
            }
            if (!Schema::hasColumn('purchase_invoices', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoice_items', 'vendor_id')) {
                // Drop foreign key first, then index, then column
                try { $table->dropForeign(['vendor_id']); } catch (\Exception $e) {}
                try { $table->dropIndex(['vendor_id']); } catch (\Exception $e) {}
                $table->dropColumn('vendor_id');
            }
            if (Schema::hasColumn('purchase_invoice_items', 'quotation_item_id')) {
                try { $table->dropForeign(['quotation_item_id']); } catch (\Exception $e) {}
                $table->dropColumn('quotation_item_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->restrictOnDelete();
            $table->foreignId('quotation_item_id')->nullable()->constrained('quotation_items')->nullOnDelete();
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['sales_order_id', 'vendor_id', 'created_by']);
        });
    }
};
