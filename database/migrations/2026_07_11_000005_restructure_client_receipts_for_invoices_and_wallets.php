<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * سند القبض بقى بيتسجّل على فاتورة بيع محددة (مش أمر بيع مباشرة)،
     * ولازم يختار محفظة يستقبل فيها المبلغ.
     */
    public function up(): void
    {
        Schema::table('client_receipts', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropColumn('sales_order_id');

            $table->foreignId('sales_invoice_id')->after('client_id')
                ->constrained('sales_invoices')->restrictOnDelete();
            $table->foreignId('wallet_id')->after('quotation_id')
                ->constrained('wallets')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('sales_invoice_id');
            $table->index('wallet_id');
        });
    }

    public function down(): void
    {
        Schema::table('client_receipts', function (Blueprint $table) {
            $table->dropForeign(['sales_invoice_id']);
            $table->dropForeign(['wallet_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['sales_invoice_id', 'wallet_id', 'created_by']);

            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->restrictOnDelete();
        });
    }
};
