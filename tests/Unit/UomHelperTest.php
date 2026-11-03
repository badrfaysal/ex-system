<?php

namespace Tests\Unit;

use App\Services\UomHelper;
use PHPUnit\Framework\TestCase;

class UomHelperTest extends TestCase
{
    public function test_translates_arabic_units_to_english()
    {
        $this->assertEquals('Carton', UomHelper::translate('كرتونة', 'en'));
        $this->assertEquals('Carton', UomHelper::translate('كرتونه', 'en'));
        $this->assertEquals('Ton', UomHelper::translate('طن', 'en'));
        $this->assertEquals('Kg', UomHelper::translate('كيلوجرام', 'en'));
        $this->assertEquals('Piece', UomHelper::translate('قطعة', 'en'));
        $this->assertEquals('Piece', UomHelper::translate('قطعه', 'en'));
        $this->assertEquals('Box', UomHelper::translate('علبة', 'en'));
        $this->assertEquals('Bag', UomHelper::translate('شوال', 'en'));
    }

    public function test_translates_english_units_to_arabic()
    {
        $this->assertEquals('كرتونة', UomHelper::translate('Carton', 'ar'));
        $this->assertEquals('كرتونة', UomHelper::translate('carton', 'ar'));
        $this->assertEquals('طن', UomHelper::translate('Ton', 'ar'));
        $this->assertEquals('طن', UomHelper::translate('ton', 'ar'));
        $this->assertEquals('كيلوجرام', UomHelper::translate('kg', 'ar'));
        $this->assertEquals('كيلوجرام', UomHelper::translate('Kilogram', 'ar'));
        $this->assertEquals('قطعة', UomHelper::translate('Piece', 'ar'));
        $this->assertEquals('قطعة', UomHelper::translate('piece', 'ar'));
        $this->assertEquals('علبة', UomHelper::translate('box', 'ar'));
    }

    public function test_handles_null_or_empty()
    {
        $this->assertEquals('—', UomHelper::translate(null, 'en'));
        $this->assertEquals('—', UomHelper::translate('', 'en'));
        $this->assertEquals('—', UomHelper::translate('—', 'en'));
    }

    public function test_options_for_locales()
    {
        $optsEn = UomHelper::options('en');
        $this->assertEquals('Carton', $optsEn['carton']);
        $this->assertEquals('Ton', $optsEn['ton']);

        $optsAr = UomHelper::options('ar');
        $this->assertEquals('كرتونة', $optsAr['carton']);
        $this->assertEquals('طن', $optsAr['ton']);
    }
}
