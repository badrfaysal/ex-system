<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Expenses
        if (!Schema::hasColumn('expenses', 'exchange_rate')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency');
            });
        }
        if (!Schema::hasColumn('expenses', 'base_amount')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->decimal('base_amount', 15, 2)->default(0)->after('exchange_rate');
            });
        }
        DB::statement('UPDATE expenses SET exchange_rate = 1, base_amount = amount');

        // 2. Purchase Invoices
        if (!Schema::hasColumn('purchase_invoices', 'exchange_rate')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency');
            });
        }
        if (!Schema::hasColumn('purchase_invoices', 'base_grand_total')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->decimal('base_grand_total', 15, 2)->default(0)->after('grand_total');
            });
        }
        DB::statement('UPDATE purchase_invoices SET exchange_rate = 1, base_grand_total = grand_total');

        // 3. Client Receipts
        if (!Schema::hasColumn('client_receipts', 'exchange_rate')) {
            Schema::table('client_receipts', function (Blueprint $table) {
                $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency');
            });
        }
        if (!Schema::hasColumn('client_receipts', 'base_amount')) {
            Schema::table('client_receipts', function (Blueprint $table) {
                $table->decimal('base_amount', 15, 2)->default(0)->after('exchange_rate');
            });
        }
        DB::statement('UPDATE client_receipts SET exchange_rate = 1, base_amount = amount');

        // 4. Sales Invoices
        if (!Schema::hasColumn('sales_invoices', 'exchange_rate')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency');
            });
        }
        if (!Schema::hasColumn('sales_invoices', 'base_grand_total')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->decimal('base_grand_total', 15, 2)->default(0)->after('grand_total');
            });
        }
        DB::statement('UPDATE sales_invoices SET exchange_rate = 1, base_grand_total = grand_total');

        // 5. Vendor Payments
        if (!Schema::hasColumn('vendor_payments', 'exchange_rate')) {
            Schema::table('vendor_payments', function (Blueprint $table) {
                $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency');
            });
        }
        if (!Schema::hasColumn('vendor_payments', 'base_amount')) {
            Schema::table('vendor_payments', function (Blueprint $table) {
                $table->decimal('base_amount', 15, 2)->default(0)->after('exchange_rate');
            });
        }
        DB::statement('UPDATE vendor_payments SET exchange_rate = 1, base_amount = amount');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'base_amount')) $table->dropColumn('base_amount');
            // We don't drop exchange_rate if it was already there before this migration to avoid breaking older code
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'base_grand_total']);
        });

        Schema::table('client_receipts', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'base_amount']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'base_grand_total']);
        });

        Schema::table('vendor_payments', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'base_amount']);
        });
    }
};
