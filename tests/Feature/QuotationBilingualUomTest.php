<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Item;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationBilingualUomTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_item_display_uom_method()
    {
        $item = Item::create([
            'item_code' => 'ITM-001',
            'name_ar'   => 'بامية مجمدة',
            'name_en'   => 'Frozen Okra',
            'base_uom'  => 'carton',
        ]);

        $qItem1 = new QuotationItem([
            'uom'         => 'كرتونة',
            'description' => 'Frozen Okra',
        ]);
        $qItem1->setRelation('item', $item);

        $this->assertEquals('Carton', $qItem1->displayUom('en'));
        $this->assertEquals('كرتونة', $qItem1->displayUom('ar'));

        $qItem2 = new QuotationItem([
            'uom'         => 'طن',
            'description' => 'Hibiscus',
        ]);
        $this->assertEquals('Ton', $qItem2->displayUom('en'));
        $this->assertEquals('طن', $qItem2->displayUom('ar'));

        // Fallback to item base_uom when line uom is empty
        $qItem3 = new QuotationItem([
            'uom'         => null,
            'description' => 'Frozen Okra',
        ]);
        $qItem3->setRelation('item', $item);
        $this->assertEquals('Carton', $qItem3->displayUom('en'));
        $this->assertEquals('كرتونة', $qItem3->displayUom('ar'));
    }

    public function test_quotation_show_and_pdf_switch_unit_language_based_on_locale()
    {
        $user = User::factory()->create();
        $client = Client::create([
            'company_name'    => 'شركة التصدير العالمية',
            'company_name_en' => 'Global Export Co.',
            'phone'           => '01000000000',
            'country'         => 'EG',
            'client_type'     => 'wholesale',
        ]);

        $itemCarton = Item::create([
            'item_code' => 'ITM-00008',
            'name_ar'   => 'بامية إكسترا مجمدة',
            'name_en'   => 'Frozen okra extra',
            'base_uom'  => 'carton',
        ]);

        $itemTon = Item::create([
            'item_code' => 'ITM-00030',
            'name_ar'   => 'كركديه',
            'name_en'   => 'HIBISCUS',
            'base_uom'  => 'ton',
        ]);

        $quotation = Quotation::create([
            'quote_number' => 'QT-2026-10-0012',
            'quote_date'   => now()->toDateString(),
            'client_id'    => $client->id,
            'currency'     => 'USD',
            'status'       => 'approved',
        ]);

        QuotationItem::create([
            'quotation_id'     => $quotation->id,
            'item_id'          => $itemCarton->id,
            'item_code'        => $itemCarton->item_code,
            'description'      => $itemCarton->name_ar,
            'quantity'         => 125,
            'uom'              => 'كرتونة',
            'list_price'       => 19.00,
            'discount_percent' => 0,
            'tax_percent'      => 0,
            'net_total'        => 2375.00,
        ]);

        QuotationItem::create([
            'quotation_id'     => $quotation->id,
            'item_id'          => $itemTon->id,
            'item_code'        => $itemTon->item_code,
            'description'      => $itemTon->name_ar,
            'quantity'         => 1,
            'uom'              => 'طن',
            'list_price'       => 3800.00,
            'discount_percent' => 0,
            'tax_percent'      => 0,
            'net_total'        => 3800.00,
        ]);

        // 1. English View
        $responseEn = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('quotations.show', $quotation));

        $responseEn->assertOk();
        $contentEn = $responseEn->getContent();
        $this->assertStringContainsString('Carton', $contentEn);
        $this->assertStringContainsString('Ton', $contentEn);

        // 2. Arabic View
        $responseAr = $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get(route('quotations.show', $quotation));

        $responseAr->assertOk();
        $contentAr = $responseAr->getContent();
        $this->assertStringContainsString('كرتونة', $contentAr);
        $this->assertStringContainsString('طن', $contentAr);

        // 3. English PDF View
        $pdfHtmlEn = view('quotations.pdf', ['quotation' => $quotation->fresh(['items']), 'isAr' => false])->render();
        $this->assertStringContainsString('Carton', $pdfHtmlEn);
        $this->assertStringContainsString('Ton', $pdfHtmlEn);

        // 4. Arabic PDF View
        $pdfHtmlAr = view('quotations.pdf', ['quotation' => $quotation->fresh(['items']), 'isAr' => true])->render();
        $this->assertStringContainsString('كرتونة', $pdfHtmlAr);
        $this->assertStringContainsString('طن', $pdfHtmlAr);
    }
}
