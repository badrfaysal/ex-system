<?php

use App\Models\Client;
use App\Models\ClientReceipt;
use App\Models\Expense;
use App\Models\Quotation;
use App\Models\Revenue;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\Wallet;
use App\Models\WalletTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('financial log displays currencies next to amounts and separates totals by currency', function () {
    $user = User::factory()->create();

    $egpWallet = Wallet::create([
        'name'            => 'EGP Bank',
        'type'            => 'bank',
        'currency'        => 'EGP',
        'opening_balance' => 1000,
    ]);

    $usdWallet = Wallet::create([
        'name'            => 'USD Bank',
        'type'            => 'bank',
        'currency'        => 'USD',
        'opening_balance' => 500,
    ]);

    $client = Client::create([
        'company_name' => 'Acme Corp',
        'email'        => 'client@acme.com',
        'phone'        => '01000000000',
        'country'      => 'EG',
        'client_type'  => 'wholesale',
    ]);

    $quotation = Quotation::create([
        'quote_number'     => 'QT-001',
        'client_id'        => $client->id,
        'quote_date'       => '2026-08-01',
        'currency'         => 'EGP',
        'subtotal'         => 2000,
        'grand_total'      => 2000,
        'status'           => 'approved',
    ]);

    $salesOrder = \App\Models\SalesOrder::create([
        'so_number'    => 'SO-001',
        'quotation_id' => $quotation->id,
        'client_id'    => $client->id,
        'so_date'      => '2026-08-01',
        'currency'     => 'EGP',
        'subtotal'     => 2000,
        'grand_total'  => 2000,
    ]);

    $salesInvoice = \App\Models\SalesInvoice::create([
        'invoice_number' => 'SI-001',
        'sales_order_id' => $salesOrder->id,
        'quotation_id'   => $quotation->id,
        'client_id'      => $client->id,
        'invoice_date'   => '2026-08-01',
        'currency'       => 'EGP',
        'subtotal'       => 2000,
        'grand_total'    => 2000,
    ]);

    // 1) Receipt in EGP wallet (In)
    ClientReceipt::create([
        'receipt_number'   => 'RC-001',
        'sales_order_id'   => $salesOrder->id,
        'sales_invoice_id' => $salesInvoice->id,
        'quotation_id'     => $quotation->id,
        'wallet_id'        => $egpWallet->id,
        'client_id'        => $client->id,
        'amount'           => 1500.00,
        'currency'         => 'EGP',
        'receipt_date'     => '2026-08-01',
        'created_by'       => $user->id,
    ]);

    // 2) Revenue in USD wallet (In)
    Revenue::create([
        'revenue_number' => 'REV-001',
        'wallet_id'      => $usdWallet->id,
        'amount'         => 200.00,
        'currency'       => 'USD',
        'category'       => 'Consulting',
        'revenue_date'   => '2026-08-02',
        'created_by'     => $user->id,
    ]);

    // 3) Expense from USD wallet (Out)
    Expense::create([
        'expense_number' => 'EXP-001',
        'quotation_id'   => $quotation->id,
        'wallet_id'      => $usdWallet->id,
        'amount'         => 50.00,
        'currency'       => 'USD',
        'category'       => 'Software',
        'expense_date'   => '2026-08-03',
        'created_by'     => $user->id,
    ]);

    // 4) Expense from EGP wallet (Out)
    Expense::create([
        'expense_number' => 'EXP-002',
        'quotation_id'   => $quotation->id,
        'wallet_id'      => $egpWallet->id,
        'amount'         => 300.00,
        'currency'       => 'EGP',
        'category'       => 'Office',
        'expense_date'   => '2026-08-04',
        'created_by'     => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('financial-logs.index'));

    $response->assertStatus(200);

    // Verify totals passed to view are separated by currency
    $totals = $response->viewData('totalsByCurrency');
    expect($totals)->toBeArray();
    expect($totals['EGP']['in'])->toEqual(1500.00);
    expect($totals['EGP']['out'])->toEqual(300.00);
    expect($totals['USD']['in'])->toEqual(200.00);
    expect($totals['USD']['out'])->toEqual(50.00);

    // Verify view content has currency indicators
    $response->assertSee('EGP');
    $response->assertSee('USD');
    $response->assertSee('1,500.00');
    $response->assertSee('200.00');
    $response->assertSee('300.00');
    $response->assertSee('50.00');
});

test('financial log wallet filter restricts results and totals to selected wallet currency', function () {
    $user = User::factory()->create();

    $egpWallet = Wallet::create(['name' => 'EGP Bank', 'currency' => 'EGP', 'opening_balance' => 1000]);
    $usdWallet = Wallet::create(['name' => 'USD Bank', 'currency' => 'USD', 'opening_balance' => 500]);

    Revenue::create([
        'revenue_number' => 'REV-EGP',
        'wallet_id'      => $egpWallet->id,
        'amount'         => 1000.00,
        'currency'       => 'EGP',
        'category'       => 'Sales',
        'revenue_date'   => '2026-08-01',
        'created_by'     => $user->id,
    ]);

    Revenue::create([
        'revenue_number' => 'REV-USD',
        'wallet_id'      => $usdWallet->id,
        'amount'         => 250.00,
        'currency'       => 'USD',
        'category'       => 'Consulting',
        'revenue_date'   => '2026-08-02',
        'created_by'     => $user->id,
    ]);

    // Request with wallet_id for USD wallet
    $response = $this->actingAs($user)->get(route('financial-logs.index', ['wallet_id' => $usdWallet->id]));
    $response->assertStatus(200);

    $totals = $response->viewData('totalsByCurrency');
    expect($totals)->toHaveKey('USD');
    expect($totals['USD']['in'])->toEqual(250.00);
    expect($totals)->not->toHaveKey('EGP');

    $response->assertSee('REV-USD');
    $response->assertDontSee('REV-EGP');
});

test('financial log handles cross-currency wallet transfers accurately', function () {
    $user = User::factory()->create();

    $usdWallet = Wallet::create(['name' => 'USD Bank', 'currency' => 'USD', 'opening_balance' => 5000]);
    $egpWallet = Wallet::create(['name' => 'EGP Bank', 'currency' => 'EGP', 'opening_balance' => 0]);

    // Transfer 100 USD to EGP with exchange rate 50 => 5000 EGP received
    WalletTransfer::create([
        'transfer_number'  => 'TR-001',
        'from_wallet_id'   => $usdWallet->id,
        'to_wallet_id'     => $egpWallet->id,
        'amount'           => 100.00,
        'converted_amount' => 5000.00,
        'exchange_rate'    => 50.00,
        'currency'         => 'USD',
        'transfer_date'    => '2026-08-05',
        'created_by'       => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('financial-logs.index'));
    $response->assertStatus(200);

    $totals = $response->viewData('totalsByCurrency');
    expect($totals['USD']['out'])->toEqual(100.00);
    expect($totals['EGP']['in'])->toEqual(5000.00);
});
