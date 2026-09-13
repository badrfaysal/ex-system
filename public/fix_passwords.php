<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$response = $kernel->handle(
    $request = Request::capture()
);

$output = "<h1>System Fix & Update</h1>";

$output .= "<h2>Database Schema Fixes (Part 2):</h2><ul>";

try {
    // 1. Expenses
    if (!Schema::hasColumn('expenses', 'exchange_rate')) {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency');
        });
        $output .= "<li>Added <code>exchange_rate</code> to expenses.</li>";
    }
    if (!Schema::hasColumn('expenses', 'base_amount')) {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('base_amount', 15, 2)->default(0)->after('exchange_rate');
        });
        DB::statement('UPDATE expenses SET exchange_rate = 1, base_amount = amount');
        $output .= "<li><span style='color:green'>Added</span> <code>base_amount</code> to expenses.</li>";
    }

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
        DB::statement('UPDATE purchase_invoices SET exchange_rate = 1, base_grand_total = grand_total');
        $output .= "<li><span style='color:green'>Added</span> <code>base_grand_total</code> to purchase_invoices.</li>";
    }

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
        DB::statement('UPDATE client_receipts SET exchange_rate = 1, base_amount = amount');
        $output .= "<li><span style='color:green'>Added</span> <code>base_amount</code> to client_receipts.</li>";
    }

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
        DB::statement('UPDATE sales_invoices SET exchange_rate = 1, base_grand_total = grand_total');
        $output .= "<li><span style='color:green'>Added</span> <code>base_grand_total</code> to sales_invoices.</li>";
    }

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
        DB::statement('UPDATE vendor_payments SET exchange_rate = 1, base_amount = amount');
        $output .= "<li><span style='color:green'>Added</span> <code>base_amount</code> to vendor_payments.</li>";
    }
    
    // Also include the previous fix just in case it missed anything
    if (!Schema::hasColumn('wallet_transfers', 'converted_amount')) {
        Schema::table('wallet_transfers', function (Blueprint $table) {
            $table->decimal('converted_amount', 15, 2)->nullable()->after('amount');
            $table->decimal('exchange_rate', 15, 6)->default(1)->after('converted_amount');
        });
        $output .= "<li><span style='color:green'>Added</span> <code>converted_amount</code> to wallet_transfers.</li>";
    }

    // Add client_code to clients
    if (!Schema::hasColumn('clients', 'client_code')) {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('client_code', 50)->nullable()->unique()->after('id');
        });
        $clients = DB::table('clients')->orderBy('id')->get();
        foreach ($clients as $client) {
            DB::table('clients')->where('id', $client->id)->update([
                'client_code' => 'CLT-' . str_pad($client->id, 4, '0', STR_PAD_LEFT),
            ]);
        }
        $output .= "<li><span style='color:green'>Added</span> <code>client_code</code> to clients.</li>";
    }

    // Add extra_discount to financial tables
    $discountTables = ['quotations', 'sales_orders', 'sales_invoices', 'purchase_invoices'];
    foreach ($discountTables as $dt) {
        if (!Schema::hasColumn($dt, 'extra_discount')) {
            Schema::table($dt, function (Blueprint $table) {
                $table->decimal('extra_discount', 15, 2)->default(0)->after('subtotal');
            });
            $output .= "<li><span style='color:green'>Added</span> <code>extra_discount</code> to $dt.</li>";
        }
    }

    // Drop unique constraint from cost_center_name to prevent duplication errors on identical generated names
    try {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique('quotations_cost_center_name_unique');
        });
        $output .= "<li><span style='color:green'>Dropped</span> unique constraint on <code>cost_center_name</code> in quotations.</li>";
    } catch (\Exception $e) {
        // Ignore if it's already dropped or doesn't exist
        $output .= "<li>Note: Unique constraint on <code>cost_center_name</code> already dropped or not found.</li>";
    }
    
    // Also increase length of cost_center_name to prevent truncation
    try {
        DB::statement('ALTER TABLE quotations MODIFY cost_center_name VARCHAR(255) NULL;');
        $output .= "<li><span style='color:green'>Updated</span> <code>cost_center_name</code> length to 255 in quotations.</li>";
    } catch (\Exception $e) {}

    // Delete the specific quotation requested by the user
    try {
        $quoteToDelete = DB::table('quotations')->where('quote_number', 'QT-2026-09-0001')->first();
        if ($quoteToDelete) {
            DB::table('quotation_items')->where('quotation_id', $quoteToDelete->id)->delete();
            DB::table('quotations')->where('id', $quoteToDelete->id)->delete();
            // Also delete activity logs for this quotation by subject_id
            DB::table('activity_logs')->where('subject_type', 'LIKE', '%Quotation%')->where('subject_id', $quoteToDelete->id)->delete();
            $output .= "<li><span style='color:green'>Deleted</span> quotation <code>QT-2026-09-0001</code> successfully.</li>";
        }
        
        $deletedLogs = DB::table('activity_logs')->where('subject_label', 'QT-2026-09-0001')->delete();
        if ($deletedLogs > 0) {
            $output .= "<li><span style='color:green'>Deleted</span> $deletedLogs stray activity logs for <code>QT-2026-09-0001</code> successfully.</li>";
        }
    } catch (\Exception $e) {
        $output .= "<li>Note: Could not delete quotation QT-2026-09-0001: " . $e->getMessage() . "</li>";
    }

    // Delete the specific client CLT-0003 (تجربه 1)
    try {
        $clientToDelete = DB::table('clients')->where('client_code', 'CLT-0003')->first();
        if ($clientToDelete) {
            DB::table('clients')->where('id', $clientToDelete->id)->delete();
            DB::table('activity_logs')->where('subject_type', 'LIKE', '%Client%')->where('subject_id', $clientToDelete->id)->delete();
            $output .= "<li><span style='color:green'>Deleted</span> client <code>CLT-0003</code> (تجربه 1) successfully.</li>";
        }
        
        $deletedClientLogs = DB::table('activity_logs')->where('subject_label', 'تجربه 1')->delete();
        if ($deletedClientLogs > 0) {
            $output .= "<li><span style='color:green'>Deleted</span> $deletedClientLogs activity logs for <code>تجربه 1</code> successfully.</li>";
        }
    } catch (\Exception $e) {
        $output .= "<li>Note: Could not delete client CLT-0003: " . $e->getMessage() . "</li>";
    }

    // Delete the specific item ITM-00006 (تجربه)
    try {
        $itemToDelete = DB::table('items')->where('item_code', 'ITM-00006')->first();
        if ($itemToDelete) {
            DB::table('item_vendor')->where('item_id', $itemToDelete->id)->delete();
            DB::table('item_images')->where('item_id', $itemToDelete->id)->delete();
            DB::table('items')->where('id', $itemToDelete->id)->delete();
            DB::table('activity_logs')->where('subject_type', 'LIKE', '%Item%')->where('subject_id', $itemToDelete->id)->delete();
            $output .= "<li><span style='color:green'>Deleted</span> item <code>ITM-00006</code> (تجربه) successfully.</li>";
        }
        
        $deletedItemLogs = DB::table('activity_logs')->where('subject_label', 'LIKE', '%تجربه%')->where('subject_type', 'LIKE', '%Item%')->delete();
        if ($deletedItemLogs > 0) {
            $output .= "<li><span style='color:green'>Deleted</span> $deletedItemLogs activity logs for item <code>تجربه</code> successfully.</li>";
        }
    } catch (\Exception $e) {
        $output .= "<li>Note: Could not delete item ITM-00006: " . $e->getMessage() . "</li>";
    }

} catch (\Exception $e) {
    $output .= "<li><span style='color:red'>Error updating schema:</span> " . $e->getMessage() . "</li>";
}
$output .= "</ul>";

$output .= "<br><p style='color:red;'><strong>Important:</strong> Please delete this file from your server after running it to maintain security.</p>";

echo $output;
exit;
