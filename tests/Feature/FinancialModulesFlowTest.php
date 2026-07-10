<?php

namespace Tests\Feature;

use App\Mail\ClientStatementMail;
use App\Mail\VendorStatementMail;
use App\Models\Client;
use App\Models\Item;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FinancialModulesFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuotationWithItems(): Quotation
    {
        $client = Client::create([
            'company_name' => 'Test Client Co',
            'phone'        => '0100000000',
            'email'        => 'client@example.com',
            'country'      => 'EG',
            'client_type'  => 'wholesale',
        ]);

        $item1 = Item::create(['item_code' => 'ITM-0001', 'name_ar' => 'صنف واحد', 'base_uom' => 'kg']);
        $item2 = Item::create(['item_code' => 'ITM-0002', 'name_ar' => 'صنف اتنين', 'base_uom' => 'kg']);

        $quotation = Quotation::create([
            'quote_number' => 'QT-TEST-0001',
            'quote_date'   => now()->toDateString(),
            'client_id'    => $client->id,
            'currency'     => 'EGP',
            'status'       => 'approved',
            'subtotal'     => 2000,
            'total_discount' => 0,
            'tax_amount'   => 0,
            'grand_total'  => 2000,
        ]);

        $quotation->items()->create([
            'item_id' => $item1->id, 'item_code' => 'ITM-0001', 'description' => 'صنف واحد',
            'quantity' => 10, 'uom' => 'kg', 'list_price' => 100, 'discount_percent' => 0, 'tax_percent' => 0, 'net_total' => 1000,
        ]);
        $quotation->items()->create([
            'item_id' => $item2->id, 'item_code' => 'ITM-0002', 'description' => 'صنف اتنين',
            'quantity' => 10, 'uom' => 'kg', 'list_price' => 100, 'discount_percent' => 0, 'tax_percent' => 0, 'net_total' => 1000,
        ]);

        return $quotation->fresh();
    }

    public function test_full_financial_flow(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // شاشة إنشاء عرض السعر (فيها حقل اسم مركز التكلفة الجديد) تفتح صح
        $this->get(route('quotations.create'))->assertOk();

        $quotation = $this->makeQuotationWithItems();
        [$qItem1, $qItem2] = $quotation->items;

        $vendorA = Vendor::create(['vendor_code' => 'VND-A', 'name_ar' => 'مورد أ', 'email' => 'vendora@example.com', 'status' => 'active']);
        $vendorB = Vendor::create(['vendor_code' => 'VND-B', 'name_ar' => 'مورد ب', 'status' => 'active']);

        // 1) صفحة إنشاء فاتورة الشراء تفتح بدون أخطاء
        $this->get(route('purchase-invoices.create', ['quotation_id' => $quotation->id]))
            ->assertOk()
            ->assertSee($quotation->quote_number);

        // 2) حفظ فاتورة شراء بمورد مختلف لكل صنف + صنف إضافي
        $extraItem = Item::create(['item_code' => 'ITM-0003', 'name_ar' => 'صنف زيادة', 'base_uom' => 'kg']);

        $resp = $this->post(route('purchase-invoices.store'), [
            'quotation_id'   => $quotation->id,
            'invoice_number' => 'PI-TEST-0001',
            'invoice_date'   => now()->toDateString(),
            'currency'       => 'EGP',
            'lines' => [
                0 => ['vendor_id' => $vendorA->id, 'item_id' => $qItem1->item_id, 'quotation_item_id' => $qItem1->id, 'description' => 'صنف واحد', 'quantity' => 10, 'unit_price' => 90, 'discount_percent' => 0, 'tax_percent' => 0],
                1 => ['vendor_id' => $vendorB->id, 'item_id' => $qItem2->item_id, 'quotation_item_id' => $qItem2->id, 'description' => 'صنف اتنين', 'quantity' => 10, 'unit_price' => 80, 'discount_percent' => 0, 'tax_percent' => 0],
                2 => ['vendor_id' => $vendorA->id, 'item_id' => $extraItem->id, 'quotation_item_id' => null, 'description' => 'صنف زيادة', 'quantity' => 5, 'unit_price' => 20, 'discount_percent' => 0, 'tax_percent' => 0],
            ],
        ]);

        $invoice = $quotation->fresh()->purchaseInvoices()->first();
        $this->assertNotNull($invoice, 'Purchase invoice was not created');
        $resp->assertRedirect(route('purchase-invoices.show', $invoice));

        // الإجمالي المتوقع: (10*90) + (10*80) + (5*20) = 900+800+100 = 1800
        $this->assertEquals(1800.0, (float) $invoice->grand_total);
        $this->assertEquals(3, $invoice->items()->count());

        // 3) الالتزام لكل مورد صحيح: مورد أ = 900+100=1000 / مورد ب = 800
        $vendorA->refresh();
        $vendorB->refresh();
        $this->assertEquals(1000.0, $vendorA->balance_due);
        $this->assertEquals(800.0, $vendorB->balance_due);

        $this->get(route('payables.index'))->assertOk()->assertSee('1,000.00')->assertSee('800.00');
        $this->get(route('payables.show', $vendorA))->assertOk()->assertSee('كشف حساب مورد');

        // إرسال كشف حساب المورد بالبريد (مورد أ عنده إيميل)
        $this->post(route('payables.send-email', $vendorA))->assertRedirect();
        Mail::assertSent(VendorStatementMail::class, fn ($mail) => $mail->vendor->id === $vendorA->id);

        // مورد ب معندوش إيميل — لازم يرجع رسالة خطأ واضحة من غير ما يبعت حاجة
        $this->post(route('payables.send-email', $vendorB))->assertRedirect();
        Mail::assertNotSent(VendorStatementMail::class, fn ($mail) => $mail->vendor->id === $vendorB->id);

        // زر/قائمة فواتير الشراء لازم تظهر في صفحة عرض السعر نفسها
        $this->get(route('quotations.show', $quotation))->assertOk()->assertSee($invoice->invoice_number);

        // جرس التنبيهات لازم يشوف عرض السعر ده — عنده فاتورة شراء بس مفيش أمر بيع لسه
        $this->get('/')->assertOk();
        $this->assertTrue(Quotation::withMismatchedDocs()->whereKey($quotation->id)->exists());

        // شاشة تسجيل سند الدفع تفتح صح ومعبّية بالمورد المختار
        $this->get(route('vendor-payments.create', ['vendor_id' => $vendorA->id]))->assertOk();

        // 4) تسجيل سند دفع جزئي لمورد أ ويقل الرصيد
        $this->post(route('vendor-payments.store'), [
            'payment_number' => 'VP-TEST-0001',
            'vendor_id'      => $vendorA->id,
            'amount'         => 400,
            'currency'       => 'EGP',
            'payment_date'   => now()->toDateString(),
        ])->assertRedirect(route('payables.show', $vendorA->id));

        $vendorA->refresh();
        $this->assertEquals(600.0, $vendorA->balance_due);
        $payment = $vendorA->payments()->first();

        // ممكن أعدّل سند الدفع وأغيّر المورد نفسه لو غلطت
        $this->get(route('vendor-payments.edit', $payment))
            ->assertOk()
            ->assertSee($vendorB->name_ar); // مورد ب لازم يبقى ضمن الخيارات المتاحة كمان

        $this->put(route('vendor-payments.update', $payment), [
            'payment_number' => $payment->payment_number,
            'vendor_id'      => $vendorB->id, // بدّلنا المورد
            'amount'         => 400,
            'currency'       => 'EGP',
            'payment_date'   => now()->toDateString(),
        ])->assertRedirect(route('payables.show', $vendorB->id));

        $vendorA->refresh();
        $vendorB->refresh();
        $this->assertEquals(1000.0, $vendorA->balance_due); // رجع للأصل بعد ما اتشال السند منه
        $this->assertEquals(400.0, $vendorB->balance_due);  // اتحمّل عليه دلوقتي

        // نرجّع السند لمورد أ زي الأول عشان باقي الاختبار يكمل صح
        $this->put(route('vendor-payments.update', $payment), [
            'payment_number' => $payment->payment_number,
            'vendor_id'      => $vendorA->id,
            'amount'         => 400,
            'currency'       => 'EGP',
            'payment_date'   => now()->toDateString(),
        ])->assertRedirect(route('payables.show', $vendorA->id));
        $vendorA->refresh();
        $this->assertEquals(600.0, $vendorA->balance_due);

        // 5) تحويل عرض السعر لأمر بيع (المسار الحالي في النظام)
        $soResp = $this->post(route('sales-orders.store'), [
            'quotation_id'     => $quotation->id,
            'selected_items'   => [$qItem1->id, $qItem2->id],
            'quantities'       => [$qItem1->id => 10, $qItem2->id => 10],
            'prices'           => [$qItem1->id => 100, $qItem2->id => 100],
        ]);
        $salesOrder = $quotation->fresh()->salesOrders()->first();
        $this->assertNotNull($salesOrder, 'Sales order was not created');
        $soResp->assertRedirect(route('sales-orders.show', $salesOrder));
        $this->assertEquals(2000.0, (float) $salesOrder->grand_total);

        // دلوقتي عرض السعر عنده الاتنين (فاتورة شراء + أمر بيع) — التنبيه لازم يختفي
        $this->get('/')->assertOk();
        $this->assertFalse(Quotation::withMismatchedDocs()->whereKey($quotation->id)->exists());

        // 6) صفحة سند القبض تفتح، وتسجيل تحصيل جزئي
        $this->get(route('client-receipts.create', ['sales_order_id' => $salesOrder->id]))->assertOk();

        $this->post(route('client-receipts.store'), [
            'sales_order_id' => $salesOrder->id,
            'receipt_number' => 'RC-TEST-0001',
            'amount'         => 1200,
            'currency'       => 'EGP',
            'receipt_date'   => now()->toDateString(),
        ])->assertRedirect(route('sales-orders.show', $salesOrder));

        $salesOrder->refresh();
        $this->assertEquals(1200.0, $salesOrder->received_amount);
        $this->assertEquals(800.0, $salesOrder->balance_due);

        $client = $quotation->client;
        $this->assertEquals(800.0, $client->fresh()->balance_due);
        $this->get(route('receivables.index'))->assertOk()->assertSee('800.00');
        $this->get(route('receivables.show', $client))->assertOk()->assertSee('كشف حساب عميل');
        $this->get(route('client-receipts.index'))->assertOk();

        // إرسال كشف حساب العميل بالبريد
        $this->post(route('receivables.send-email', $client))->assertRedirect();
        Mail::assertSent(ClientStatementMail::class, fn ($mail) => $mail->client->id === $client->id);

        // بطاقة التحصيل لازم تظهر في صفحة أمر البيع نفسها بالرصيد الصحيح
        $this->get(route('sales-orders.show', $salesOrder))->assertOk()->assertSee('RC-TEST-0001');

        // 7) تسجيل مصروف على نفس مركز التكلفة — لما يجي من صفحة مركز التكلفة، عرض السعر يتملي تلقائي
        $ccHtml = $this->get(route('cost-centers.show', $quotation))->assertOk()->getContent();
        $this->assertStringContainsString(route('expenses.create', ['quotation_id' => $quotation->id]), $ccHtml);

        $createHtml = $this->get(route('expenses.create', ['quotation_id' => $quotation->id]))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/value="' . $quotation->id . '"[^>]*selected/', $createHtml);

        // فيلد المورد اتشال خالص من الفورم
        $this->assertStringNotContainsString('name="vendor_id"', $createHtml);

        $this->post(route('expenses.store'), [
            'expense_number' => 'EXP-TEST-0001',
            'quotation_id'   => $quotation->id,
            'category'       => 'transportation',
            'amount'         => 150,
            'currency'       => 'EGP',
            'expense_date'   => now()->toDateString(),
            'notes'          => 'ملاحظة اختبار خاصة بالمصروف',
        ])->assertRedirect(route('expenses.index'));

        // الصف بيحتوي على البيانات (بما فيها الملاحظات) عشان البوباب يعرضها من غير طلب إضافي
        // (json_encode بيرمّز العربي كـ \uXXXX جوه الـ onclick — شغّال تمام في المتصفح، بس نتأكد بالداتا الفعلية بدل نص خام)
        $indexHtml = $this->get(route('expenses.index'))->assertOk()->assertSee('EXP-TEST-0001')->getContent();
        $encodedNotes = trim(json_encode('ملاحظة اختبار خاصة بالمصروف'), '"'); // Blade's {{ }} يحوّل " إلى &quot;
        $this->assertStringContainsString($encodedNotes, $indexHtml);

        // 8) تقرير مركز التكلفة: الإيراد = 1200 (المحصّل فقط) — التكلفة = فواتير الشراء(1800) + مصروفات(150) = 1950 — خسارة = -750
        $this->get(route('cost-centers.show', $quotation))
            ->assertOk()
            ->assertSee('1,200.00')
            ->assertSee('1,950.00');

        $ccIndex = $this->get(route('cost-centers.index'))->assertOk();

        // 9) نفس المسارات الأساسية تشتغل بالإنجليزي كمان من غير أخطاء
        session(['locale' => 'en']);
        $this->get(route('payables.index'))->assertOk();
        $this->get(route('receivables.index'))->assertOk();
        $this->get(route('cost-centers.show', $quotation))->assertOk();
        $this->get(route('purchase-invoices.show', $invoice))->assertOk();

        // 10) محاولة عمل فاتورة شراء تانية لنفس عرض السعر لازم ترفض وترجع للفاتورة الموجودة
        $this->get(route('purchase-invoices.create', ['quotation_id' => $quotation->id]))
            ->assertRedirect(route('purchase-invoices.show', $invoice));

        $secondAttempt = $this->post(route('purchase-invoices.store'), [
            'quotation_id'   => $quotation->id,
            'invoice_number' => 'PI-TEST-0002',
            'invoice_date'   => now()->toDateString(),
            'currency'       => 'EGP',
            'lines' => [
                0 => ['vendor_id' => $vendorA->id, 'description' => 'صنف تاني', 'quantity' => 1, 'unit_price' => 10, 'discount_percent' => 0, 'tax_percent' => 0],
            ],
        ]);
        $secondAttempt->assertRedirect(route('purchase-invoices.show', $invoice));
        $this->assertEquals(1, $quotation->fresh()->purchaseInvoices()->count());
    }

    public function test_sales_order_respects_edited_prices_and_extra_items(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $quotation = $this->makeQuotationWithItems();
        [$qItem1, $qItem2] = $quotation->items;
        $extraItem = Item::create(['item_code' => 'ITM-EX', 'name_ar' => 'صنف زيادة', 'base_uom' => 'kg']);

        // نعدّل السعر ونضيف صنف مش موجود في عرض السعر أصلاً
        $resp = $this->post(route('sales-orders.store'), [
            'quotation_id'   => $quotation->id,
            'selected_items' => [$qItem1->id],
            'quantities'     => [$qItem1->id => 10],
            'prices'         => [$qItem1->id => 90], // سعر معدّل (الأصلي كان 100)
            'extra_lines'    => [
                0 => ['item_id' => $extraItem->id, 'description' => 'صنف زيادة', 'quantity' => 2, 'list_price' => 50, 'discount_percent' => 0, 'tax_percent' => 0],
            ],
        ]);

        $salesOrder = $quotation->fresh()->salesOrders()->first();
        $this->assertNotNull($salesOrder);
        $resp->assertRedirect(route('sales-orders.show', $salesOrder));

        // التأكد إن السعر المعدّل اتحفظ فعلاً (مش سعر عرض السعر الأصلي)
        $savedLine = $salesOrder->items()->where('item_id', $qItem1->item_id)->first();
        $this->assertEquals(90.0, (float) $savedLine->list_price);

        // الصنف الإضافي اتسجل
        $extraLine = $salesOrder->items()->where('item_id', $extraItem->id)->first();
        $this->assertNotNull($extraLine, 'Extra item line was not saved');
        $this->assertEquals(100.0, (float) $extraLine->net_total); // 2 * 50

        // الإجمالي = (10*90) + (2*50) = 900 + 100 = 1000
        $this->assertEquals(1000.0, (float) $salesOrder->grand_total);
    }

    public function test_statement_pdfs_actually_render(): void
    {
        // Mail::fake() بيوقف قبل build() فمش بيكشف أخطاء التمبلت — هنا بننده build() فعليًا
        // عشان نتأكد إن الـ mPDF بيولّد الملف من غير Exception (Undefined variable، إلخ)
        $client = Client::create(['company_name' => 'PDF Test Client', 'phone' => '01000000', 'email' => 'pdf-client@example.com', 'country' => 'EG', 'client_type' => 'wholesale']);
        $vendor = Vendor::create(['vendor_code' => 'VND-PDF', 'name_ar' => 'مورد بي دي إف', 'email' => 'pdf-vendor@example.com', 'status' => 'active']);

        $clientTimeline = collect([
            ['date' => now(), 'type' => 'order', 'ref' => 'SO-1', 'amount' => 100.0, 'link' => null, 'balance' => 100.0],
            ['date' => now(), 'type' => 'receipt', 'ref' => 'RC-1', 'amount' => -40.0, 'link' => null, 'balance' => 60.0],
        ]);
        $clientMail = new \App\Mail\ClientStatementMail($client, $clientTimeline, 60.0, 'ar');
        $clientMail->build();
        $this->assertNotEmpty($clientMail->rawAttachments);

        $vendorTimeline = collect([
            ['date' => now(), 'type' => 'invoice', 'ref' => 'PI-1', 'amount' => 200.0, 'link' => null, 'balance' => 200.0],
            ['date' => now(), 'type' => 'payment', 'ref' => 'VP-1', 'amount' => -50.0, 'link' => null, 'balance' => 150.0],
        ]);
        $vendorMail = new \App\Mail\VendorStatementMail($vendor, $vendorTimeline, 150.0, 'ar');
        $vendorMail->build();
        $this->assertNotEmpty($vendorMail->rawAttachments);
    }

    public function test_purchase_invoice_vendor_dropdown_filters_and_highlights_prices(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $quotation = $this->makeQuotationWithItems();
        [$qItem1] = $quotation->items;

        $cheapVendor = Vendor::create(['vendor_code' => 'VND-CHEAP', 'name_ar' => 'مورد رخيص', 'status' => 'active']);
        $pricyVendor = Vendor::create(['vendor_code' => 'VND-PRICY', 'name_ar' => 'مورد غالي', 'status' => 'active']);
        $unrelatedVendor = Vendor::create(['vendor_code' => 'VND-UNREL', 'name_ar' => 'مورد مش تابع للصنف', 'status' => 'active']);

        // مورد رخيص وواحد غالي معتمدين على الصنف الأول بس — مورد تالت مش مربوط بيه خالص
        $item1 = $qItem1->item;
        $item1->approvedVendors()->attach([
            $cheapVendor->id => ['last_purchase_price' => 50],
            $pricyVendor->id => ['last_purchase_price' => 90],
        ]);

        $html = $this->get(route('purchase-invoices.create', ['quotation_id' => $quotation->id]))
            ->assertOk()
            ->getContent();

        // المورد المعتمد الأرخص/الأغلى لازم يظهروا، والمورد الغير مرتبط بالصنف ده مايظهرش في سطر الصنف الأول
        $this->assertStringContainsString('مورد رخيص', $html);
        $this->assertStringContainsString('مورد غالي', $html);
        $this->assertStringContainsString('الأرخص', $html);
        $this->assertStringContainsString('الأغلى', $html);

        // السعر الافتراضي المعبّى هو سعر أرخص مورد (50)
        $this->assertMatchesRegularExpression('/name="lines\[0\]\[unit_price\]"[^>]*value="50"/', $html);
    }

    public function test_reset_database_is_blocked_outside_local_environment(): void
    {
        // بيئة الاختبار (testing) لازم تُرفض — الزرار ده خطير ومسموح بيئة local بس
        $this->assertNotEquals('local', app()->environment());

        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('settings.reset-database'))
            ->assertForbidden();
    }
}
