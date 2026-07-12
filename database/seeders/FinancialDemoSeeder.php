<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Vendor;
use App\Models\Item;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\ClientReceipt;
use App\Models\VendorPayment;
use App\Models\Wallet;
use App\Models\WalletTransfer;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('⏳ جاري إضافة الحركات المالية المتكاملة...');

        $adminId = User::first()->id ?? 1;

        // 1. إنشاء محافظ
        $wallets = [
            Wallet::firstOrCreate(['name' => 'الخزينة الرئيسية'], ['currency' => 'EGP', 'opening_balance' => 500000, 'type' => 'cash']),
            Wallet::firstOrCreate(['name' => 'البنك الأهلي المصري'], ['currency' => 'EGP', 'opening_balance' => 1200000, 'type' => 'bank']),
            Wallet::firstOrCreate(['name' => 'بنك CIB - حساب دولار'], ['currency' => 'USD', 'opening_balance' => 50000, 'type' => 'bank']),
            Wallet::firstOrCreate(['name' => 'صندوق المصروفات النثرية'], ['currency' => 'EGP', 'opening_balance' => 15000, 'type' => 'cash']),
        ];

        // تحويل محفظة للتجربة
        WalletTransfer::create([
            'transfer_number' => 'TR-2026-07-0001',
            'transfer_date'   => Carbon::now()->subDays(5),
            'from_wallet_id'  => $wallets[1]->id,
            'to_wallet_id'    => $wallets[3]->id,
            'amount'          => 15000,
            'currency'        => 'EGP',
            'created_by'      => $adminId,
            'notes'           => 'تغذية صندوق النثريات',
        ]);

        // 3. المصروفات
        $expenseCategories = ['transportation', 'wages', 'other'];
        $quotationsForExpenses = Quotation::all();
        
        for ($i = 1; $i <= 10; $i++) {
            $amt = rand(500, 5000);
            $w = $wallets[array_rand([0,3])]; // خزينة أو نثريات
            Expense::create([
                'expense_number' => 'EXP-' . Carbon::now()->format('Y-m') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'expense_date'   => Carbon::now()->subDays(rand(1, 30)),
                'category'       => $expenseCategories[array_rand($expenseCategories)],
                'amount'         => $amt,
                'currency'       => 'EGP',
                'wallet_id'      => $w->id,
                'quotation_id'   => $quotationsForExpenses->isNotEmpty() ? $quotationsForExpenses->random()->id : null,
                'created_by'     => $adminId,
                'description'    => 'مصروف تجريبي ' . $i,
            ]);
        }

        $clients = Client::all();
        $vendors = Vendor::all();
        $items = Item::all();

        if ($clients->isEmpty() || $vendors->isEmpty() || $items->isEmpty()) {
            $this->command->error('❌ لا يوجد عملاء أو موردين أو أصناف. برجاء تشغيل FakeDataSeeder أولاً.');
            return;
        }

        // 4. تحويل بعض عروض الأسعار إلى أوامر بيع وفواتير
        $quotations = Quotation::where('status', 'converted')->orWhere('status', 'sent')->take(5)->get();
        
        foreach ($quotations as $qIndex => $q) {
            // أمر البيع
            $so = SalesOrder::create([
                'so_number'      => 'SO-' . Carbon::now()->format('Y-m') . '-' . str_pad($qIndex + 1, 4, '0', STR_PAD_LEFT),
                'so_date'        => Carbon::now()->subDays(rand(10, 20)),
                'client_id'      => $q->client_id,
                'quotation_id'   => $q->id,
                'currency'       => $q->currency,
                'status'         => 'completed',
                'subtotal'       => $q->subtotal,
                'total_discount' => $q->total_discount,
                'tax_amount'     => $q->tax_amount,
                'grand_total'    => $q->grand_total,
            ]);

            foreach ($q->items as $qItem) {
                $so->items()->create([
                    'item_id'          => $qItem->item_id,
                    'item_code'        => $qItem->item_code,
                    'description'      => $qItem->description,
                    'quantity'         => $qItem->quantity,
                    'uom'              => $qItem->uom,
                    'list_price'       => $qItem->list_price, // fallback
                    'discount_percent' => $qItem->discount_percent,
                    'tax_percent'      => $qItem->tax_percent,
                    'net_total'        => $qItem->net_total,
                ]);
            }

            // فاتورة البيع
            $inv = SalesInvoice::create([
                'invoice_number' => 'INV-' . Carbon::now()->format('Y-m') . '-' . str_pad($qIndex + 1, 4, '0', STR_PAD_LEFT),
                'invoice_date'   => Carbon::now()->subDays(rand(5, 10)),
                'client_id'      => $q->client_id,
                'quotation_id'   => $q->id,
                'sales_order_id' => $so->id,
                'currency'       => $q->currency,
                'subtotal'       => $q->subtotal,
                'total_discount' => $q->total_discount,
                'tax_amount'     => $q->tax_amount,
                'grand_total'    => $q->grand_total,
            ]);

            foreach ($so->items as $soItem) {
                $inv->items()->create([
                    'item_id'          => $soItem->item_id,
                    'item_code'        => $soItem->item_code,
                    'description'      => $soItem->description,
                    'quantity'         => $soItem->quantity,
                    'uom'              => $soItem->uom,
                    'unit_price'       => $soItem->list_price,
                    'discount_percent' => $soItem->discount_percent,
                    'tax_percent'      => $soItem->tax_percent,
                    'net_total'        => $soItem->net_total,
                ]);
            }

            // سند قبض (تحصيل جزء أو كل)
            $receiptAmount = rand(0, 1) ? $inv->grand_total : round($inv->grand_total * 0.5, 2);
            $w = $wallets[1]; // البنك
            ClientReceipt::create([
                'receipt_number' => 'RC-' . Carbon::now()->format('Y-m') . '-' . str_pad($qIndex + 1, 4, '0', STR_PAD_LEFT),
                'receipt_date'   => Carbon::now()->subDays(rand(1, 5)),
                'client_id'      => $q->client_id,
                'sales_invoice_id'=> $inv->id,
                'quotation_id'   => $q->id,
                'amount'         => $receiptAmount,
                'currency'       => $q->currency,
                'wallet_id'      => $w->id,
                'payment_method' => 'bank_transfer',
                'created_by'     => $adminId,
            ]);
        }

        // 5. فواتير شراء وسداد للموردين
        for ($i = 1; $i <= 6; $i++) {
            $vendor = $vendors->random();
            $subtotal = rand(10000, 50000);
            $tax = $subtotal * 0.14;
            $grand = $subtotal + $tax;

            $pi = PurchaseInvoice::create([
                'invoice_number' => 'PI-' . Carbon::now()->format('Y-m') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'invoice_date'   => Carbon::now()->subDays(rand(10, 20)),
                'vendor_id'      => $vendor->id,
                'quotation_id'   => $quotations->random()->id,
                'sales_order_id' => SalesOrder::first()->id,
                'currency'       => 'EGP',
                'subtotal'       => $subtotal,
                'tax_amount'     => $tax,
                'grand_total'    => $grand,
            ]);

            // منتجات عشوائية للفاتورة
            $chosenItems = $items->random(rand(2, 5));
            foreach ($chosenItems as $item) {
                $pi->items()->create([
                    'item_id'    => $item->id,
                    'item_code'  => $item->item_code,
                    'description'=> $item->name_ar,
                    'quantity'   => rand(50, 500),
                    'uom'        => $item->base_uom,
                    'unit_price' => rand(10, 100),
                    'net_total'  => rand(500, 5000),
                ]);
            }

            // سند دفع للمورد
            $payAmount = rand(0, 1) ? $grand : round($grand * 0.4, 2);
            $w = $wallets[1];
            VendorPayment::create([
                'payment_number' => 'VP-' . Carbon::now()->format('Y-m') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'payment_date'   => Carbon::now()->subDays(rand(1, 9)),
                'vendor_id'      => $vendor->id,
                'purchase_invoice_id' => $pi->id,
                'amount'         => $payAmount,
                'currency'       => 'EGP',
                'wallet_id'      => $w->id,
                'payment_method' => 'bank_transfer',
                'created_by'     => $adminId,
            ]);
        }

        $this->command->info('✅ تم إضافة المحافظ، مراكز التكلفة، أوامر البيع، الفواتير، السندات، والمصروفات بنجاح!');
    }
}
