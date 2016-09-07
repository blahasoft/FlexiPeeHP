<?php

namespace Test\FlexiPeeHP;

use FlexiPeeHP\FakturaVydana;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-04-27 at 17:32:11.
 */
class FakturaVydanaTest extends FlexiBeeRWTest
{
    /**
     * @var FakturaVydana
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new FakturaVydana();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    /**
     * @covers FlexiPeeHP\FakturaVydana::hotovostniUhrada
     */
    public function testhotovostniUhrada()
    {
        $this->makeInvoice();
        $this->object->unsetDataValue('kod');
        $this->object->hotovostniUhrada($this->object->getDataValue('sumCelkZakl'));
        $this->assertEquals(201, $this->object->lastResponseCode,
            _('Invoice settle error'));
    }

    /**
     * @covers FlexiPeeHP\FakturaVydana::sparujPlatbu
     */
    public function testsparujPlatbu()
    {
        $this->makeInvoice();
        $doklad     = new \FlexiPeeHP\PokladniPohyb();
        $value      = $this->object->getDataValue('sumCelkZakl');
        $dataPohybu = [
            'kod' => 'FP'.time(),
            'typDokl' => 'code:STANDARD',
            'typPohybuK' => 'typPohybu.prijem',
            'datVyst' => date("Y-m-d", time() - 60 * 60 * 24),
            'jakUhrK' => 'jakUhrazeno.rucne1',
            'pokladna' => 'code:POKLADNA KČ',
            'generovatSkl' => false,
            'zdrojProSkl' => false,
            'firma' => $this->object->getDataValue('firma'),
            'bezPolozek' => true,
            'poznam' => $this->poznam,
            'primUcet' => 'code:013001',
            'sumZklCelkem' => $value
        ];
        $doklad->takeData($dataPohybu);
        $doklad->insertToFlexiBee();
        $doklad->unsetDataValue('kod');
        $this->object->sparujPlatbu($doklad);
        $this->assertEquals(201, $doklad->lastResponseCode,
            _('Invoice match error'));
    }

    /**
     * Crerate testing invoice
     * 
     * @param array $invoiceData
     */
    public function makeInvoice($invoiceData = [])
    {
        if (!isset($invoiceData['kod'])) {
            $invoiceData['kod'] = 'PeeHP'.time();
        }
        if (!isset($invoiceData['varSym'])) {
            $invoiceData['varSym'] = \Ease\Sand::randomNumber(1000, 99999);
        }
        if (!isset($invoiceData['datVyst'])) {
            $invoiceData['datVyst'] = date("Y-m-d", time() - 60 * 60 * 24);
        }
        if (!isset($invoiceData['typDokl'])) {
            $invoiceData['typDokl'] = 'code:FAKTURA';
        }
        if (!isset($invoiceData['zdrojProSkl'])) {
            $invoiceData['zdrojProSkl'] = false;
        }
        if (!isset($invoiceData['dobropisovano'])) {
            $invoiceData['dobropisovano'] = false;
        }
        if (!isset($invoiceData['bezPolozek'])) {
            $invoiceData['bezPolozek'] = false;
        }

        if (!isset($invoiceData['polozky']) && !$invoiceData['bezPolozek']) {
            $invoiceData['bezPolozek'] = true;
            if (!array_key_exists('sumCelkZakl', $invoiceData)) {
                $scale                      = pow(1000, 2);
                $invoiceData['sumCelkZakl'] = round(mt_rand(10 * $scale,
                        9000 * $scale) / $scale, 2);
                $invoiceData['castkaMen']   = 0;
                $invoiceData['sumCelkem']   = $invoiceData['sumCelkZakl'];
            }
        } else {
            $invoiceData['bezPolozek'] = false;
        }

        if (!isset($invoiceData['firma'])) {
            $adresar = new \FlexiPeeHP\Adresar();

            $adresy = $adresar->getFlexiData(null,
                ['typVztahuK' => 'typVztahu.odberatel']);
            if (count($adresy)) {
                $dodavatel = $adresy[array_rand($adresy)];

                $invoiceData['firma'] = 'code:'.$dodavatel['kod'];
            } else {
                /**
                 * Make Some Address First ...
                 */
                $address              = new \FlexiPeeHP\Adresar();
                $address->setDataValue('nazev', \Ease\Sand::randomString());
                $address->setDataValue('poznam', 'Generated Unit Test Customer');
                $address->setDataValue('typVztahuK', 'typVztahu.odberatel');
                $address->insertToFlexiBee();
                $invoiceData['firma'] = $address;
            }
        }

        if (!isset($invoiceData['poznam'])) {
            $invoiceData['poznam'] = $this->poznam;
        }

        $this->object->takeData($invoiceData);
        $this->object->insertToFlexiBee();

        $id = $this->object->getLastInsertedId();
        $this->object->loadFromFlexiBee((int) $id);
        $this->object->setDataValue('id', $id);
        return $id;
    }

    /**
     * Provizorní zkopírování faktury
     *
     * @link https://www.flexibee.eu/podpora/Tickets/Ticket/View/28848 Chyba při Provádění akcí přes REST API JSON
     * @param \FlexiPeeHP\FakturaVydana $invoice
     * @param array $overide Hodnoty přepisující výchozí v kopii faktury
     * @return \FlexiPeeHP\FakturaVydana
     */
    public function invoiceCopy($invoice, $override = [])
    {
        $invoice2        = new \FlexiPeeHP\FakturaVydana($invoice->getData());
        $invoice2->debug = 1;
        $invoice2->unsetDataValue('id');
        $invoice2->unsetDataValue('kod');
        $polozky         = $invoice2->getDataValue('polozkyFaktury');
        if (!is_null($polozky)) {
            foreach ($polozky as $pid => $polozka) {
                unset($polozky[$pid]['id']);
                unset($polozky[$pid]['doklFak']);
                unset($polozky[$pid]['doklFak@showAs']);
                unset($polozky[$pid]['doklFak@ref']);
            }
            $invoice2->setDataValue('polozkyFaktury', $polozky);
        }
        if (is_null($invoice2->getDataValue('typDokl'))) {
            $invoice2->setDataValue('typDokl', 'code:FAKTURA');
        }
        $invoice2->unsetDataValue('external-ids');

        $today = date('Y-m-d');

        $invoice2->setDataValue('duzpPuv', $today);
        $invoice2->setDataValue('duzpUcto', $today);
        $invoice2->setDataValue('datUcto', $today);
        $invoice2->takeData($override);
        $invoice2->insertToFlexiBee();

        return $invoice2;
    }

    /**
     * @covers FlexiPeeHP\FakturaVydana::odpocetZDD
     */
    public function testodpocetZDD()
    {
        $this->markTestIncomplete('TODO: Write Test');
    }

    /**
     * @covers FlexiPeeHP\FakturaVydana::odpocetZalohy
     */
    public function testodpocetZalohy()
    {
        $itemName = \Ease\Sand::randomString();

        $polozka = [
            "typCenyDphK" => "typCeny.bezDph",
            "typSzbDphK" => "typSzbDph.dphZakl",
            "kopClenKonVykDph" => "true",
            "typPolozkyK" => "typPolozky.obecny",
            'zdrojProSkl' => false,
            'zaloha' => true,
            'nazev' => $itemName,
            'szbDph' => 19.0,
            'cenaMj' => 123,
            "mnozMj" => "1.0",
            'poznam' => $this->poznam,
        ];

        $this->makeInvoice(
            [
                'typDokl' => 'code:ZÁLOHA',
                'polozky' => $polozka,
                'bezPolozek' => false
            ]
        );


        $this->object->hotovostniUhrada($this->object->getDataValue('sumCelkem'));

        $invoice2 = $this->invoiceCopy($this->object,
            ['typDokl' => 'code:FAKTURA']);
        $id       = (int) $invoice2->getLastInsertedId();
        $invoice2->loadFromFlexiBee($id);
        $kod      = $invoice2->getDataValue('kod');
        $invoice2->dataReset();
        $invoice2->setDataValue('id', 'code:'.$kod);

        $result = $invoice2->odpocetZalohy($this->object);

        $this->assertArrayHasKey('success', $result);
        $this->assertEquals('true', $result['success'], 'Matching Error');
    }

}
